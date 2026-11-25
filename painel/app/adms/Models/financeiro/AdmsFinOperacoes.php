<?php

namespace App\adms\Models\financeiro;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\financeiro\AdmsFinanceiro as F;

/**
 * AS AÇÕES DO FINANCEIRO E OS DESFAZER (Onda 8, financeiro, Partes 2, 4 e 6; D-98). Os nomes são os mesmos no botão, na
 * confirmação e no histórico: Receber, Baixar, Cancelar, Renegociar, "Desfazer recebimento", "Desfazer baixa", "Desfazer
 * cancelamento", "Desfazer renegociação". Toda ação roda numa transação, com o título travado (SELECT … FOR UPDATE) e a
 * mudança de situação condicionada à situação esperada: o clique duplo e duas pessoas no mesmo título dão uma operação
 * só. O número do envio do formulário é único no histórico (o mesmo envio não grava duas vezes). Nada é apagado: o
 * desfazer marca a operação desfeita e registra a sua própria, com o motivo (obrigatório).
 */
final class AdmsFinOperacoes
{
    public const NOMES = ['receber' => 'Receber', 'baixar' => 'Baixar', 'cancelar' => 'Cancelar', 'renegociar' => 'Renegociar',
        'desfazer_recebimento' => 'Desfazer recebimento', 'desfazer_baixa' => 'Desfazer baixa', 'desfazer_cancelamento' => 'Desfazer cancelamento',
        'desfazer_renegociacao' => 'Desfazer renegociação', 'gerar' => 'Gerado', 'atendimento' => 'Atendimento', 'proposta' => 'Salvar proposta'];

    /** @return array{ok: bool, erro: string, operacao: int} */
    private static function falha(string $erro): array
    {
        return ['ok' => false, 'erro' => $erro, 'operacao' => 0];
    }

    /** O título travado na transação (ou null). */
    private static function travar(int $tituloId): ?array
    {
        $st = F::pdo()->prepare('SELECT * FROM fin_titulos WHERE id = ? FOR UPDATE');
        $st->execute([$tituloId]);
        return $st->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /** A situação muda só se ainda é a esperada (a outra pessoa, ou o clique repetido, já mudou: nada acontece). */
    private static function mudar(int $tituloId, string $de, string $para): bool
    {
        $st = F::pdo()->prepare('UPDATE fin_titulos SET situacao = ? WHERE id = ? AND situacao = ?');
        $st->execute([$para, $tituloId, $de]);
        return $st->rowCount() === 1;
    }

    /** O envio já gravado (o clique duplo): a resposta é a da primeira vez. */
    private static function jaEnviado(string $envio): ?int
    {
        if ($envio === '') {
            return null;
        }
        $st = F::pdo()->prepare('SELECT id FROM fin_operacoes WHERE envio = ?');
        $st->execute([$envio]);
        $id = $st->fetchColumn();
        return $id === false ? null : (int) $id;
    }

    private static function executar(callable $f): array
    {
        $r = null;
        $ok = \Core\Transacao::executar(static function () use ($f, &$r) {
            $r = $f();
            if (!$r['ok']) {
                \Core\Transacao::falhar($r['erro']);
            }
            return true;
        });
        if ($ok === false) {
            return $r && !$r['ok'] ? $r : self::falha('Nada foi gravado: ' . \Core\Transacao::ultimoErro() . '.');
        }
        return $r;
    }


    /** BAIXAR (quita sem entrada de dinheiro: não entra no caixa) ou CANCELAR (deixa de ser devido); o motivo é obrigatório. */
    public static function baixarOuCancelar(string $acao, int $tituloId, array $d, int $usuario): array
    {
        $para = $acao === 'baixar' ? 'baixado' : 'cancelado';
        $envio = trim((string) ($d['envio'] ?? ''));
        if (($ja = self::jaEnviado($envio)) !== null) {
            return ['ok' => true, 'erro' => '', 'operacao' => $ja];
        }
        $motivo = mb_substr(trim((string) ($d['motivo'] ?? '')), 0, 500);
        if (mb_strlen($motivo) < 5) {
            return self::falha('Escreva o motivo (ao menos 5 letras).');
        }
        return self::executar(static function () use ($acao, $para, $tituloId, $motivo, $envio, $usuario) {
            $t = self::travar($tituloId);
            if (!$t) {
                return self::falha('Título não encontrado.');
            }
            if ($t['situacao'] !== 'aberto') {
                return self::falha('Este título já está ' . mb_strtolower(F::rotulo($t['situacao'])) . ': nada foi gravado.');
            }
            if (!self::mudar($tituloId, 'aberto', $para)) {
                return self::falha('Outra pessoa acabou de mudar este título: nada foi gravado.');
            }
            $op = F::registrar(['titulo_id' => $tituloId, 'aluno_id' => (int) $t['aluno_id'], 'tipo' => $acao, 'data_operacao' => date('Y-m-d'),
                'original_centavos' => (int) $t['valor_centavos'], 'motivo' => $motivo, 'situacao_antes' => 'aberto', 'situacao_depois' => $para, 'envio' => $envio ?: null, 'usuario_id' => $usuario]);
            return ['ok' => true, 'erro' => '', 'operacao' => $op];
        });
    }

    /**
     * BAIXAR ou CANCELAR EM LOTE (D-99, até a Parte 3, que traz a tela própria): os títulos marcados no modo lote, com um
     * motivo só, tudo ou nada (numa transação; cada título travado e conferido em aberto).
     */
    public static function baixarOuCancelarLote(string $acao, int $alunoId, array $d, int $usuario): array
    {
        $para = $acao === 'baixar' ? 'baixado' : 'cancelado';
        $envio = trim((string) ($d['envio'] ?? ''));
        if (($ja = self::jaEnviado($envio)) !== null) {
            return ['ok' => true, 'erro' => '', 'operacao' => $ja];
        }
        $ids = array_values(array_unique(array_filter(array_map('intval', (array) ($d['titulos'] ?? [])))));
        sort($ids);
        $motivo = mb_substr(trim((string) ($d['motivo'] ?? '')), 0, 500);
        if (!$ids) {
            return self::falha('Marque os títulos em aberto.');
        }
        if (mb_strlen($motivo) < 5) {
            return self::falha('Escreva o motivo (ao menos 5 letras).');
        }
        return self::executar(static function () use ($acao, $para, $alunoId, $ids, $motivo, $envio, $usuario) {
            $primeira = 0;
            foreach ($ids as $id) {
                $t = self::travar($id);
                if (!$t || (int) $t['aluno_id'] !== $alunoId) {
                    return self::falha('Um dos títulos não é deste aluno.');
                }
                if ($t['situacao'] !== 'aberto' || !self::mudar($id, 'aberto', $para)) {
                    return self::falha('O título ' . $id . ' já está ' . mb_strtolower(F::rotulo($t['situacao'])) . ': nada foi gravado.');
                }
                $op = F::registrar(['titulo_id' => $id, 'aluno_id' => $alunoId, 'tipo' => $acao, 'data_operacao' => date('Y-m-d'), 'original_centavos' => (int) $t['valor_centavos'],
                    'motivo' => $motivo, 'situacao_antes' => 'aberto', 'situacao_depois' => $para, 'envio' => $primeira ? null : ($envio ?: null), 'usuario_id' => $usuario]);
                $primeira = $primeira ?: $op;
            }
            return ['ok' => true, 'erro' => '', 'operacao' => $primeira];
        });
    }

    /**
     * DESFAZER baixa ou cancelamento: o título volta a aberto (a situação mostrada sai da data de hoje: pode voltar como
     * vencido), a operação desfeita fica marcada e o desfazer é registrado, com o motivo. O Desfazer recebimento é do
     * recibo inteiro (AdmsFinRecibos::estornar, D-99): por aqui, ele é recusado.
     */
    public static function desfazer(string $tipo, int $tituloId, array $d, int $usuario): array
    {
        if ($tipo === 'desfazer_recebimento') {
            return self::falha('O Desfazer recebimento desfaz o recibo inteiro: use o item "Desfazer recebimento" no menu Ações do título.');
        }
        $mapa = ['desfazer_baixa' => ['baixar', 'baixado'], 'desfazer_cancelamento' => ['cancelar', 'cancelado']];
        if (!isset($mapa[$tipo])) {
            return self::falha('Ação desconhecida.');
        }
        [$original, $estado] = $mapa[$tipo];
        $envio = trim((string) ($d['envio'] ?? ''));
        if (($ja = self::jaEnviado($envio)) !== null) {
            return ['ok' => true, 'erro' => '', 'operacao' => $ja];
        }
        $motivo = mb_substr(trim((string) ($d['motivo'] ?? '')), 0, 500);
        if (mb_strlen($motivo) < 5) {
            return self::falha('Escreva o motivo (ao menos 5 letras): o desfazer fica no histórico.');
        }
        return self::executar(static function () use ($tipo, $original, $estado, $tituloId, $motivo, $envio, $usuario) {
            $t = self::travar($tituloId);
            if (!$t) {
                return self::falha('Título não encontrado.');
            }
            if ($t['situacao'] !== $estado) {
                return self::falha('Este título não está ' . mb_strtolower(F::rotulo($estado)) . ': não há o que desfazer.');
            }
            if ($t['renegociacao_id'] && $original === 'cancelar' && self::cancelamentoDaRenegociacao((int) $tituloId)) {
                return self::falha('Esta parcela foi cancelada ao desfazer a renegociação: ela volta só com uma renegociação nova.');
            }
            $st = F::pdo()->prepare("SELECT id FROM fin_operacoes WHERE titulo_id = ? AND tipo = ? AND desfeita_em IS NULL ORDER BY id DESC LIMIT 1");
            $st->execute([$tituloId, $original]);
            $opOriginal = (int) $st->fetchColumn();
            if (!$opOriginal || !self::mudar($tituloId, $estado, 'aberto')) {
                return self::falha('Outra pessoa acabou de mudar este título: nada foi gravado.');
            }
            $op = F::registrar(['titulo_id' => $tituloId, 'aluno_id' => (int) $t['aluno_id'], 'tipo' => $tipo, 'data_operacao' => date('Y-m-d'),
                'original_centavos' => (int) $t['valor_centavos'], 'motivo' => $motivo, 'situacao_antes' => $estado, 'situacao_depois' => 'aberto', 'envio' => $envio ?: null, 'usuario_id' => $usuario]);
            F::pdo()->prepare('UPDATE fin_operacoes SET desfeita_em = NOW(), desfeita_por_operacao = ? WHERE id = ?')->execute([$op, $opOriginal]);
            return ['ok' => true, 'erro' => '', 'operacao' => $op];
        });
    }

    private static function cancelamentoDaRenegociacao(int $tituloId): bool
    {
        $st = F::pdo()->prepare("SELECT COUNT(*) FROM fin_operacoes WHERE titulo_id = ? AND tipo = 'cancelar' AND desfeita_em IS NULL AND renegociacao_id IS NOT NULL");
        $st->execute([$tituloId]);
        return (int) $st->fetchColumn() > 0;
    }

    /** O histórico do aluno (as operações e os atendimentos), o mais novo primeiro. */
    public static function historico(int $alunoId): array
    {
        $st = F::pdo()->prepare('SELECT o.*, t.descricao, u.nome AS usuario FROM fin_operacoes o LEFT JOIN fin_titulos t ON t.id = o.titulo_id LEFT JOIN adms_usuarios u ON u.id = o.usuario_id
            WHERE o.aluno_id = ? ORDER BY o.id DESC');
        $st->execute([$alunoId]);
        return $st->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * DESFAZER RENEGOCIAÇÃO (Parte 6; D-105: o motivo da lista e a observação, obrigatórios): só quando nenhuma parcela do
     * acordo tem recebimento ou baixa (a recusa diz o que desfazer antes); as parcelas do acordo em aberto ficam canceladas
     * (com o motivo) e os originais voltam a aberto (a situação sai da data). O Renegociar é o da tela própria (AdmsFinRenegociacao).
     */
    public static function desfazerRenegociacao(int $renId, array $d, int $usuario): array
    {
        $envio = trim((string) ($d['envio'] ?? ''));
        if (($ja = self::jaEnviado($envio)) !== null) {
            return ['ok' => true, 'erro' => '', 'operacao' => $ja];
        }
        $motivos = AdmsFinRecibos::motivos('desfazer_renegociacao');
        $motivoId = (int) ($d['motivo_id'] ?? 0);
        $obs = mb_substr(trim((string) ($d['observacao'] ?? '')), 0, 360);
        if (!isset($motivos[$motivoId])) {
            return self::falha('Escolha o motivo do desfazer.');
        }
        if (mb_strlen($obs) < 5) {
            return self::falha('Escreva a observação (ao menos 5 letras): o desfazer fica no histórico.');
        }
        $motivo = $motivos[$motivoId] . ': ' . $obs;
        return self::executar(static function () use ($renId, $motivo, $motivoId, $envio, $usuario) {
            $st = F::pdo()->prepare('SELECT * FROM fin_renegociacoes WHERE id = ? FOR UPDATE');
            $st->execute([$renId]);
            $r = $st->fetch(\PDO::FETCH_ASSOC);
            if (!$r || $r['situacao'] !== 'vigente') {
                return self::falha('Esta renegociação não está vigente: não há o que desfazer.');
            }
            $st = F::pdo()->prepare('SELECT * FROM fin_titulos WHERE renegociacao_id = ? ORDER BY parcela FOR UPDATE');
            $st->execute([$renId]);
            $novas = $st->fetchAll(\PDO::FETCH_ASSOC);
            $presas = array_filter($novas, static fn($t) => in_array($t['situacao'], ['recebido', 'baixado', 'renegociado'], true));
            if ($presas) {
                return self::falha('Não dá para desfazer: ' . implode('; ', array_map(static fn($t) => $t['descricao'] . ' está ' . mb_strtolower(F::rotulo($t['situacao'])), $presas))
                    . '. Desfaça antes o recebimento (ou a baixa) ' . (count($presas) > 1 ? 'dessas parcelas' : 'dessa parcela') . '.');
            }
            $primeiraOp = 0;
            foreach ($novas as $t) {
                if ($t['situacao'] === 'aberto') {
                    self::mudar((int) $t['id'], 'aberto', 'cancelado');
                    $op = F::registrar(['titulo_id' => (int) $t['id'], 'aluno_id' => (int) $r['aluno_id'], 'tipo' => 'cancelar', 'data_operacao' => date('Y-m-d'),
                        'original_centavos' => (int) $t['valor_centavos'], 'motivo' => 'Renegociação desfeita: ' . $motivo, 'situacao_antes' => 'aberto', 'situacao_depois' => 'cancelado',
                        'renegociacao_id' => $renId, 'usuario_id' => $usuario]);
                    $primeiraOp = $primeiraOp ?: $op;
                }
            }
            $st = F::pdo()->prepare("SELECT * FROM fin_titulos WHERE renegociado_por = ? AND situacao = 'renegociado' FOR UPDATE");
            $st->execute([$renId]);
            foreach ($st->fetchAll(\PDO::FETCH_ASSOC) as $t) {
                self::mudar((int) $t['id'], 'renegociado', 'aberto');
                $op = F::registrar(['titulo_id' => (int) $t['id'], 'aluno_id' => (int) $r['aluno_id'], 'tipo' => 'desfazer_renegociacao', 'data_operacao' => date('Y-m-d'),
                    'original_centavos' => (int) $t['valor_centavos'], 'motivo' => $motivo, 'situacao_antes' => 'renegociado', 'situacao_depois' => 'aberto', 'renegociacao_id' => $renId,
                    'envio' => $primeiraOp ? null : ($envio ?: null), 'usuario_id' => $usuario]);
                $primeiraOp = $primeiraOp ?: $op;
            }
            F::pdo()->prepare("UPDATE fin_operacoes SET desfeita_em = NOW() WHERE renegociacao_id = ? AND tipo = 'renegociar' AND desfeita_em IS NULL")->execute([$renId]);
            F::pdo()->prepare("UPDATE fin_renegociacoes SET situacao = 'desfeita', desfeita_em = NOW(), motivo_desfazer = ?, motivo_desfazer_id = ?, desfeita_por = ? WHERE id = ?")->execute([$motivo, $motivoId, $usuario, $renId]);
            return ['ok' => true, 'erro' => '', 'operacao' => $primeiraOp];
        });
    }

    /** As renegociações do aluno, com as parcelas novas. */
    public static function renegociacoes(int $alunoId): array
    {
        $st = F::pdo()->prepare('SELECT * FROM fin_renegociacoes WHERE aluno_id = ? ORDER BY id DESC');
        $st->execute([$alunoId]);
        return $st->fetchAll(\PDO::FETCH_ASSOC);
    }

    /** O ATENDIMENTO (Parte 7): o que foi conversado e combinado, em texto livre, com a data e quem atendeu; ligado ou não a um título. */
    public static function atendimento(int $alunoId, array $d, int $usuario): array
    {
        if (($ja = self::jaEnviado(trim((string) ($d['envio'] ?? '')))) !== null) {
            return ['ok' => true, 'erro' => '', 'operacao' => $ja];
        }
        $texto = trim((string) ($d['texto'] ?? ''));
        $data = trim((string) ($d['data'] ?? date('Y-m-d')));
        $titulo = (int) ($d['titulo_id'] ?? 0) ?: null;
        $dt = \DateTime::createFromFormat('Y-m-d', $data);
        if (mb_strlen($texto) < 5) {
            return self::falha('Escreva o que foi combinado (ao menos 5 letras).');
        }
        if (!$dt || $dt->format('Y-m-d') !== $data || $data > date('Y-m-d')) {
            return self::falha('Informe a data do atendimento (até hoje).');
        }
        if ($titulo) {
            $st = F::pdo()->prepare('SELECT aluno_id FROM fin_titulos WHERE id = ?');
            $st->execute([$titulo]);
            if ((int) $st->fetchColumn() !== $alunoId) {
                return self::falha('O título não é deste aluno.');
            }
        }
        $op = F::registrar(['titulo_id' => $titulo, 'aluno_id' => $alunoId, 'tipo' => 'atendimento', 'data_operacao' => $data, 'observacao' => mb_substr($texto, 0, 500),
            'envio' => trim((string) ($d['envio'] ?? '')) ?: null, 'usuario_id' => $usuario]);
        return ['ok' => true, 'erro' => '', 'operacao' => $op];
    }
}
