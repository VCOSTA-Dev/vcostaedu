<?php

namespace App\adms\Models\escola;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\escola\AdmsResponsaveis as M;
use App\adms\Models\escola\AdmsResponsaveisConvite as Convite;

/**
 * OS CONVITES DE UMA TURMA, EM PASSOS (Onda 7, Etapa 3; D-63) — o padrão das operações longas (a janela de progresso,
 * assets/js/vcostaedu-progresso.js): o estado em adms_instalacao_estado ('responsaveis.convite_lote'), uma pessoa por
 * chamado, a trava (GET_LOCK) contra dois lotes ao mesmo tempo, e a retomada de onde parou.
 *
 * Cada passo gera o convite de uma pessoa e tenta o e-mail. Quem não recebeu por e-mail (sem e-mail, envio não
 * configurado, o servidor recusou) vai para a lista do envio manual. O LINK dessa pessoa vai só na resposta daquele
 * passo, para a tela de quem está fazendo o lote: o estado guardado tem só quem e por quê, nunca o código.
 */
class AdmsResponsaveisConviteLote
{
    public const ESTADO = 'responsaveis.convite_lote';

    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = AdmsEscolaBanco::pdo();
    }

    public function estado(): ?array
    {
        $st = $this->pdo->prepare('SELECT valor FROM adms_instalacao_estado WHERE chave = ?');
        $st->execute([self::ESTADO]);
        $v = $st->fetchColumn();
        return $v ? (json_decode((string) $v, true) ?: null) : null;
    }

    private function gravar(?array $e): void
    {
        if ($e === null) {
            $this->pdo->prepare('DELETE FROM adms_instalacao_estado WHERE chave = ?')->execute([self::ESTADO]);
            return;
        }
        $this->pdo->prepare('INSERT INTO adms_instalacao_estado (chave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)')
            ->execute([self::ESTADO, json_encode($e, JSON_UNESCAPED_UNICODE)]);
    }

    public static function publico(?array $e): ?array
    {
        if ($e === null) {
            return null;
        }
        return ['passo' => (int) $e['feitos'], 'total' => max(1, (int) $e['total']), 'grupoAtual' => 'Os convites da turma', 'falhou' => $e['falhou'] ?? null,
            'concluida' => !empty($e['concluida']), 'turma' => (int) $e['turma'], 'enviados' => (int) $e['enviados'], 'manuais' => $e['manuais'] ?? [],
            'recusados' => $e['recusados'] ?? []];
    }

    private function trava(): string
    {
        return 'vcostaedu_convites_' . substr(sha1((string) $this->pdo->query('SELECT DATABASE()')->fetchColumn()), 0, 30);
    }

    /** Começa o lote da turma (ou retoma o da mesma turma que parou). @return array [ok, mensagem] */
    public function iniciar(int $turma): array
    {
        $e = $this->estado();
        if ($e && empty($e['concluida'])) {
            if ((int) $e['turma'] !== $turma) {
                return [false, 'Há um lote de convites de outra turma em andamento. Termine aquele antes (abra a turma dele e clique em Continuar).'];
            }
            $e['falhou'] = null;
            $this->gravar($e);
            return [true, ''];
        }
        $ids = array_map('intval', array_column(Convite::pendentesDaTurma($turma), 'id'));
        if (!$ids) {
            return [false, 'Nesta turma, todos os responsáveis com papel no ano já têm acesso (ou estão bloqueados).'];
        }
        $this->gravar(['turma' => $turma, 'pessoas' => $ids, 'total' => count($ids), 'feitos' => 0, 'enviados' => 0, 'manuais' => [], 'recusados' => [],
            'falhou' => null, 'concluida' => false, 'inicio' => date('c'), 'por' => (int) ($_SESSION['colUsuario_id'] ?? 0)]);
        return [true, ''];
    }

    /** Um passo: uma pessoa. A resposta leva o link de quem ficou para o envio manual (só nesta resposta). */
    public function passo(): array
    {
        $trava = $this->trava();
        if (!(int) $this->pdo->query("SELECT GET_LOCK('{$trava}', 0)")->fetchColumn()) {
            return ['ocupado' => true];
        }
        try {
            $e = $this->estado();
            if (!$e) {
                return ['erro' => 'Nada em andamento.'];
            }
            if (!empty($e['concluida'])) {
                return self::publico($e);
            }
            $pessoa = (int) ($e['pessoas'][(int) $e['feitos']] ?? 0);
            $novo = null;
            if ($pessoa > 0) {
                $r = Convite::passoTurma((int) $e['turma'], $pessoa);
                if (empty($r['ok'])) {
                    $e['recusados'][] = ['id' => $pessoa, 'nome' => (string) ($r['nome'] ?? (M::pessoa($pessoa)['nome'] ?? '')), 'motivo' => (string) $r['erro']];
                } elseif (!empty($r['enviado'])) {
                    $e['enviados']++;
                } else {
                    $e['manuais'][] = ['id' => $pessoa, 'nome' => (string) $r['nome'], 'motivo' => (string) $r['motivo']];
                    $novo = ['id' => $pessoa, 'nome' => (string) $r['nome'], 'motivo' => (string) $r['motivo'], 'link' => $r['link'], 'texto' => $r['texto'], 'whatsapp' => $r['whatsapp']];
                }
            }
            $e['feitos'] = min((int) $e['total'], (int) $e['feitos'] + 1);
            $e['concluida'] = (int) $e['feitos'] >= (int) $e['total'];
            $this->gravar($e);
            return self::publico($e) + ($novo ? ['manual' => $novo] : []);
        } finally {
            $this->pdo->query("SELECT RELEASE_LOCK('{$trava}')");
        }
    }

    /** Esquece o lote concluído (a tela já mostrou o resultado). */
    public function limparConcluido(): void
    {
        $e = $this->estado();
        if ($e && !empty($e['concluida'])) {
            $this->gravar(null);
        }
    }
}
