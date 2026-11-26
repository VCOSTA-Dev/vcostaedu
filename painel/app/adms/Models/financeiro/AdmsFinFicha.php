<?php

namespace App\adms\Models\financeiro;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\financeiro\AdmsFinanceiro as F;

/**
 * A FICHA FINANCEIRA, no desenho da de origem (Onda 8, financeiro refeito, Parte 1; D-99). Monta tudo o que a tela
 * mostra, do servidor (o link pode ser copiado; a exportação sai com o que a tela mostra):
 *   - o ano letivo escolhido (a pílula): o ano do relógio, se o aluno o tem; senão o mais recente antes dele; senão o
 *     primeiro. Diferente de lá (o defeito do levantamento), a troca de ano muda o cabeçalho, os totais E a tabela;
 *   - o cabeçalho do ano (o segmento ou curso, a série ou módulo, o contrato = a matrícula do ano) e os responsáveis do
 *     ano (na escola; no ensino superior, o próprio aluno é o pagador);
 *   - os três totais e a aba Atual valem para o mesmo recorte: o ano escolhido, os seguintes e o que está em aberto de
 *     anos anteriores. "Pago em AAAA" é o recebido dos títulos do ano escolhido;
 *   - o resumo: "Inadimplente" é ter algum título em aberto vencido (de qualquer ano); o último pagamento é o dia do
 *     recibo mais recente e a soma dos recibos desse dia;
 *   - as abas Atual, Pendências (tudo em aberto) e Anos anteriores (um ano de cada vez, antes do escolhido);
 *   - os filtros: o período (por vencimento ou por recebimento) e a situação, com a contagem de cada uma no recorte da
 *     aba e do período.
 */
final class AdmsFinFicha
{
    public const SITUACOES = ['todos' => 'Todos', 'avencer' => 'A vencer', 'atrasados' => 'Atrasados', 'abertos' => 'Abertos', 'recebidos' => 'Recebidos',
        'baixados' => 'Baixados', 'cancelados' => 'Cancelados', 'prescritos' => 'Prescritos', 'renegociados' => 'Renegociados'];
    /** [rótulo, vale para: 'ambos' ou só 'venc' (os de futuro)] */
    public const PERIODOS = ['todo' => ['Todo o período', 'ambos'], 'hoje' => ['Hoje', 'ambos'], 'ult7' => ['Últimos 7 dias', 'ambos'], 'ult30' => ['Últimos 30 dias', 'ambos'],
        'prox7' => ['Próximos 7 dias', 'venc'], 'prox30' => ['Próximos 30 dias', 'venc'], 'estemes' => ['Este mês', 'ambos'], 'mespassado' => ['Mês passado', 'ambos'],
        'proxmes' => ['Próximo mês', 'venc'], 'personalizado' => ['Personalizado', 'ambos']];
    public const ABAS = ['atual' => 'Atual', 'pendencias' => 'Pendências', 'anteriores' => 'Anos anteriores'];
    /** O modo lote de cada operação: [o rótulo, a situação que pode passar por ela]. Prescrever entra na Parte 3. */
    public const MODOS = ['receber' => ['Receber em lote', 'abertos'], 'renegociar' => ['Renegociar em lote', 'abertos'], 'baixar' => ['Baixar em lote', 'abertos'],
        'cancelar' => ['Cancelar em lote', 'abertos']];

    private static function todos(string $sql, array $p = []): array
    {
        $st = F::pdo()->prepare($sql);
        $st->execute($p);
        return $st->fetchAll(\PDO::FETCH_ASSOC);
    }

    /** O rótulo do número do aluno (D-99): "RM" na escola, "RA" no ensino superior. */
    public static function rotuloNumero(): string
    {
        return \App\adms\Models\helper\AdmsTipoInstituicao::rotuloNumero(); // D-103: o rótulo único
    }

    /** O endereço do aluno numa linha (o pagador no ensino superior), ou ''. */
    public static function enderecoDoAluno(int $alunoId): string
    {
        $e = self::todos('SELECT endereco, numero, complemento, bairro, cidade, uf, cep FROM col_usuarios_enderecos WHERE usuario_id = ? ORDER BY id DESC LIMIT 1', [$alunoId])[0] ?? null;
        return $e ? \App\adms\Models\escola\AdmsResponsaveis::enderecoEmLinha($e) : '';
    }

    /** A matrícula do aluno no ano (a mais nova não substituída): o segmento/curso, a série/módulo e o contrato. */
    public static function anoDoAluno(int $alunoId, int $ano): ?array
    {
        $l = self::todos('SELECT me.id AS contrato, me.ano, c.nome AS segmento, m.codnome AS serie FROM rm_matriculas_ensino me
            LEFT JOIN rm_cursos c ON c.id = me.ensino_id LEFT JOIN rm_cursos_turma_series m ON m.id = me.modulo_id
            WHERE me.usuario_id = ? AND me.ano = ? ORDER BY (me.substituido_em IS NULL) DESC, me.id DESC LIMIT 1', [$alunoId, $ano]);
        return $l[0] ?? null;
    }

    /** Os anos do aluno: os das matrículas e os dos títulos, com o que está em aberto em cada um. @return array<int, array> */
    public static function anos(int $alunoId): array
    {
        $anos = [];
        foreach (self::todos('SELECT DISTINCT ano FROM rm_matriculas_ensino WHERE usuario_id = ? AND ano IS NOT NULL', [$alunoId]) as $l) {
            $anos[(int) $l['ano']] = 0;
        }
        foreach (self::todos("SELECT ano, SUM(situacao = 'aberto') AS abertos FROM fin_titulos WHERE aluno_id = ? GROUP BY ano", [$alunoId]) as $l) {
            $anos[(int) $l['ano']] = (int) $l['abertos'];
        }
        ksort($anos);
        $saida = [];
        foreach ($anos as $ano => $abertos) {
            $m = self::anoDoAluno($alunoId, $ano);
            $saida[$ano] = ['ano' => $ano, 'em_aberto' => $abertos, 'segmento' => $m['segmento'] ?? null, 'serie' => $m['serie'] ?? null, 'contrato' => $m['contrato'] ?? null];
        }
        return $saida;
    }

    /** O ano que a ficha abre: o do relógio, se o aluno o tem; senão o mais recente antes dele; senão o primeiro. */
    public static function anoPadrao(array $anos, ?string $hoje = null): int
    {
        $atual = (int) substr($hoje ?? date('Y-m-d'), 0, 4);
        if (!$anos) {
            return $atual;
        }
        if (isset($anos[$atual])) {
            return $atual;
        }
        $antes = array_filter(array_keys($anos), static fn($a) => $a < $atual);
        return $antes ? max($antes) : min(array_keys($anos));
    }

    /**
     * Os responsáveis do ano (na escola): cada pessoa uma vez, com os papéis somados (o financeiro primeiro), o
     * parentesco e o celular. No ensino superior, o próprio aluno (o pagador). @return list<array>
     */
    public static function responsaveis(int $alunoId, int $ano): array
    {
        if (!\App\adms\Models\helper\AdmsTipoInstituicao::ehEscola()) {
            /* D-120 (a correção do ensaio, B1.1): o CPF do aluno fica em col_usuarios_informacoes, não em col_usuarios */
            $a = self::todos('SELECT u.id, COALESCE(NULLIF(u.nome_social, \'\'), u.nome) AS nome, u.celular, i.cpf, u.email
                FROM col_usuarios u LEFT JOIN col_usuarios_informacoes i ON i.col_user_id = u.id
                WHERE u.id = ? ORDER BY i.id DESC LIMIT 1', [$alunoId])[0] ?? null;
            return $a ? [['id' => (int) $a['id'], 'nome' => $a['nome'], 'papel' => 'O próprio aluno (pagador)', 'parentesco' => '—', 'celular' => (string) $a['celular'], 'cpf' => (string) $a['cpf'],
                'endereco' => self::enderecoDoAluno((int) $a['id']),
                'link' => 'academicos/visualizar/' . (int) $a['id'], 'financeiro' => true, 'aluno' => true]] : [];
        }
        $pessoas = [];
        foreach (self::todos("SELECT d.papel, r.id, r.nome, r.celular, r.cpf, r.endereco, r.numero, r.complemento, r.bairro, r.cidade, r.uf, r.cep, ra.parentesco FROM esc_responsavel_designacoes d JOIN esc_responsaveis r ON r.id = d.responsavel_id
            LEFT JOIN esc_responsavel_alunos ra ON ra.responsavel_id = r.id AND ra.aluno_id = d.aluno_id
            WHERE d.aluno_id = ? AND d.ano = ? AND d.fim IS NULL ORDER BY d.id", [$alunoId, $ano]) as $l) {
            $id = (int) $l['id'];
            $pessoas[$id] ??= ['id' => $id, 'nome' => $l['nome'], 'parentesco' => $l['parentesco'] ?: '—', 'celular' => (string) $l['celular'], 'papeis' => [],
                'cpf' => (string) $l['cpf'], 'endereco' => \App\adms\Models\escola\AdmsResponsaveis::enderecoEmLinha($l), 'link' => 'responsaveis/ver/' . $id, 'aluno' => false];
            $pessoas[$id]['papeis'][$l['papel']] = true;
        }
        foreach ($pessoas as &$p) {
            $fin = isset($p['papeis']['financeiro']);
            $acad = isset($p['papeis']['titular']) || isset($p['papeis']['adicional']);
            $p['financeiro'] = $fin;
            $p['papel'] = $fin && $acad ? 'Financeiro e Acadêmico' : ($fin ? 'Financeiro' : (isset($p['papeis']['titular']) ? 'Acadêmico' : 'Acadêmico (acesso adicional)'));
            $p['ordem'] = $fin && $acad ? 0 : ($fin ? 1 : (isset($p['papeis']['titular']) ? 2 : 3));
        }
        unset($p);
        usort($pessoas, static fn($a, $b) => $a['ordem'] <=> $b['ordem'] ?: strcmp($a['nome'], $b['nome']));
        return $pessoas;
    }

    /** O intervalo de um período, a partir de hoje. @return array{0: ?string, 1: ?string} */
    public static function intervalo(string $periodo, ?string $de, ?string $ate, string $hoje): array
    {
        $d = new \DateTimeImmutable($hoje);
        switch ($periodo) {
            case 'hoje': return [$hoje, $hoje];
            case 'ult7': return [$d->modify('-7 days')->format('Y-m-d'), $hoje];
            case 'ult30': return [$d->modify('-30 days')->format('Y-m-d'), $hoje];
            case 'prox7': return [$hoje, $d->modify('+7 days')->format('Y-m-d')];
            case 'prox30': return [$hoje, $d->modify('+30 days')->format('Y-m-d')];
            case 'estemes': return [$d->format('Y-m-01'), $d->format('Y-m-t')];
            case 'mespassado': $m = $d->modify('first day of last month'); return [$m->format('Y-m-01'), $m->format('Y-m-t')];
            case 'proxmes': $m = $d->modify('first day of next month'); return [$m->format('Y-m-01'), $m->format('Y-m-t')];
            case 'personalizado': return [$de ?: null, $ate ?: null];
            default: return [null, null];
        }
    }

    /** A situação de um título no filtro: a chave do chip (sem o "todos" nem o "abertos"). */
    private static function chave(array $t, string $hoje): string
    {
        return match ($t['situacao']) {
            'aberto' => $t['vencimento'] < $hoje ? 'atrasados' : 'avencer',
            'recebido' => 'recebidos', 'baixado' => 'baixados', 'cancelado' => 'cancelados', 'prescrito' => 'prescritos', 'renegociado' => 'renegociados',
            default => 'todos',
        };
    }

    /** O recorte pedido, limpo (o que não vale volta ao padrão). */
    public static function recorte(array $q, array $anos, ?string $hoje = null): array
    {
        $hoje = $hoje ?? date('Y-m-d');
        $ano = (int) ($q['ano'] ?? 0);
        $ano = isset($anos[$ano]) ? $ano : self::anoPadrao($anos, $hoje);
        $aba = isset(self::ABAS[$q['aba'] ?? '']) ? $q['aba'] : 'atual';
        $campo = ($q['campo'] ?? '') === 'receb' ? 'receb' : 'venc';
        $periodo = isset(self::PERIODOS[$q['periodo'] ?? '']) ? $q['periodo'] : 'todo';
        if ($campo === 'receb' && self::PERIODOS[$periodo][1] === 'venc') {
            $periodo = 'todo'; // os "próximos" não existem no recebimento
        }
        $dataOk = static fn($v) => is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) && strtotime($v) ? $v : null;
        $de = $dataOk($q['de'] ?? null);
        $ate = $dataOk($q['ate'] ?? null);
        $anteriores = array_values(array_filter(array_keys($anos), static fn($a) => $a < $ano));
        rsort($anteriores);
        $anoAnt = (int) ($q['ano_ant'] ?? 0);
        $anoAnt = in_array($anoAnt, $anteriores, true) ? $anoAnt : ($anteriores[0] ?? null);
        $modo = isset(self::MODOS[$q['modo'] ?? '']) ? $q['modo'] : null;
        $sit = isset(self::SITUACOES[$q['situacao'] ?? '']) ? $q['situacao'] : 'todos';
        if ($modo) {
            $sit = self::MODOS[$modo][1]; // no modo lote, a situação é a da operação (fixa)
        }
        return ['ano' => $ano, 'aba' => $aba, 'campo' => $campo, 'periodo' => $periodo, 'de' => $de, 'ate' => $ate, 'situacao' => $sit, 'anteriores' => $anteriores,
            'ano_ant' => $anoAnt, 'modo' => $modo, 'hoje' => $hoje];
    }

    /** Os títulos do aluno com o recibo válido de cada um (o valor pago, a data, quem recebeu). @return list<array> */
    public static function titulos(int $alunoId, string $hoje): array
    {
        $regras = F::regras();
        $linhas = self::todos("SELECT t.*, r.id AS recibo_id, r.numero AS recibo_numero, r.data_recebimento, l.recebido_centavos, u.nome AS recebedor
            FROM fin_titulos t
            LEFT JOIN fin_recibo_titulos l ON l.titulo_id = t.id AND l.situacao = 'valido'
            LEFT JOIN fin_recibos r ON r.id = l.recibo_id
            LEFT JOIN adms_usuarios u ON u.id = r.usuario_id
            WHERE t.aluno_id = ? ORDER BY t.vencimento, t.id", [$alunoId]);
        foreach ($linhas as &$t) {
            $t['chave'] = self::chave($t, $hoje);
            $t['mostrada'] = F::situacaoMostrada($t, $hoje);
            $t['encargos'] = $t['situacao'] === 'aberto' ? F::encargosTitulo($t, $hoje, $regras) : null;
            /* o valor até o desconto e a data do desconto: o desconto das regras vale até o vencimento (os planos com o dia
               do desconto são a Parte 5); sem desconto nas regras, as colunas mostram um traço */
            $t['valor_desconto'] = $regras['desconto_bp'] > 0 ? (int) $t['valor_centavos'] - F::encargos((int) $t['valor_centavos'], (string) $t['vencimento'], (string) $t['vencimento'], $regras)['desconto'] : null;
            $t['data_desconto'] = $regras['desconto_bp'] > 0 ? (string) $t['vencimento'] : null;
            $t['parc'] = $t['parcela'] === null ? '—' : ($t['renegociacao_id'] && $t['parcelas_total'] // D-105: a parcela do acordo como no sistema de origem ("01.03")
                ? AdmsFinRenegociacao::rotuloParcela((int) $t['parcela'], (int) $t['parcelas_total']) : $t['parcela'] . ($t['parcelas_total'] ? '.' . $t['parcelas_total'] : ''));
        }
        unset($t);
        return $linhas;
    }

    /** Monta a ficha inteira. $q: o pedido (ano, aba, campo, periodo, de, ate, situacao, ano_ant, modo). */
    public static function montar(int $alunoId, array $q, ?string $hoje = null): array
    {
        $hoje = $hoje ?? date('Y-m-d');
        $anos = self::anos($alunoId);
        $R = self::recorte($q, $anos, $hoje);
        $ano = $R['ano'];
        $todos = self::titulos($alunoId, $hoje);

        /* o recorte da aba Atual (o mesmo dos totais): o ano escolhido, os seguintes e o em aberto de antes */
        $naAtual = static fn($t) => (int) $t['ano'] >= $ano || $t['situacao'] === 'aberto';
        $tot = ['atraso' => 0, 'atraso_n' => 0, 'avencer' => 0, 'avencer_n' => 0, 'proximo' => null, 'pago' => 0, 'pago_n' => 0];
        $inadimplente = false;
        foreach ($todos as $t) {
            if ($t['chave'] === 'atrasados') {
                $inadimplente = true;
            }
            if ($naAtual($t) && $t['chave'] === 'atrasados') {
                $tot['atraso'] += (int) $t['valor_centavos'];
                $tot['atraso_n']++;
            }
            if ($naAtual($t) && $t['chave'] === 'avencer') {
                $tot['avencer'] += (int) $t['valor_centavos'];
                $tot['avencer_n']++;
                $tot['proximo'] = $tot['proximo'] === null || $t['vencimento'] < $tot['proximo'] ? $t['vencimento'] : $tot['proximo'];
            }
            if ((int) $t['ano'] === $ano && $t['situacao'] === 'recebido') {
                $tot['pago'] += (int) ($t['recebido_centavos'] ?? $t['valor_centavos']);
                $tot['pago_n']++;
            }
        }
        $ult = self::todos("SELECT data_recebimento, SUM(total_centavos) AS total FROM fin_recibos WHERE aluno_id = ? AND situacao = 'valido'
            GROUP BY data_recebimento ORDER BY data_recebimento DESC LIMIT 1", [$alunoId])[0] ?? null;

        /* as abas e o recorte da aba */
        $porAba = [
            'atual' => array_values(array_filter($todos, $naAtual)),
            'pendencias' => array_values(array_filter($todos, static fn($t) => $t['situacao'] === 'aberto')),
            'anteriores' => array_values(array_filter($todos, static fn($t) => $R['ano_ant'] !== null && (int) $t['ano'] === $R['ano_ant'])),
        ];
        $contagemAbas = ['atual' => count($porAba['atual']), 'pendencias' => count($porAba['pendencias'])];
        $lista = $R['modo'] ? array_values(array_filter($todos, static fn($t) => true)) : $porAba[$R['aba']];

        /* o período: pela data de vencimento ou pela do recebimento */
        [$ini, $fim] = self::intervalo($R['periodo'], $R['de'], $R['ate'], $hoje);
        if ($ini !== null || $fim !== null) {
            $lista = array_values(array_filter($lista, static function ($t) use ($R, $ini, $fim) {
                $d = $R['campo'] === 'receb' ? $t['data_recebimento'] : $t['vencimento'];
                return $d !== null && ($ini === null || $d >= $ini) && ($fim === null || $d <= $fim);
            }));
        }
        /* a contagem de cada situação, no recorte da aba e do período */
        $cont = array_fill_keys(array_keys(self::SITUACOES), 0);
        foreach ($lista as $t) {
            $cont['todos']++;
            $cont[$t['chave']]++;
            if ($t['situacao'] === 'aberto') {
                $cont['abertos']++;
            }
        }
        $sit = $R['situacao'];
        $lista = array_values(array_filter($lista, static fn($t) => $sit === 'todos' || $t['chave'] === $sit || ($sit === 'abertos' && $t['situacao'] === 'aberto')));
        if ($R['campo'] === 'receb') {
            usort($lista, static fn($a, $b) => strcmp((string) $a['data_recebimento'], (string) $b['data_recebimento']) ?: $a['id'] <=> $b['id']);
        }
        return [
            'anos' => $anos, 'recorte' => $R, 'cabecalho' => $anos[$ano] ?? ['ano' => $ano, 'segmento' => null, 'serie' => null, 'contrato' => null, 'em_aberto' => 0],
            'responsaveis' => self::responsaveis($alunoId, $ano), 'totais' => $tot,
            'resumo' => ['inadimplente' => $inadimplente, 'ultimo' => $ult],
            'abas' => $contagemAbas, 'contagem' => $cont, 'titulos' => $lista, 'periodo' => [$ini, $fim],
        ];
    }

    /** A busca de outro aluno: o nome, o RA ou (na escola) o nome do responsável. @return list<array> */
    public static function buscar(string $q): array
    {
        $q = trim($q);
        if (mb_strlen($q) < 2) {
            return [];
        }
        $like = '%' . $q . '%';
        $digitos = preg_replace('/\D+/', '', $q);
        $alunos = self::todos("SELECT u.id, u.nome, u.nome_social, u.rm FROM col_usuarios u
            WHERE (u.nome LIKE ? OR u.nome_social LIKE ? OR (? <> '' AND u.rm = ?)) AND u.rm IS NOT NULL AND u.rm <> ''
            ORDER BY u.nome LIMIT 15", [$like, $like, $digitos, $digitos]);
        $saida = array_map(static fn($a) => ['id' => (int) $a['id'], 'nome' => $a['nome_social'] ?: $a['nome'], 'rm' => (string) $a['rm'], 'por' => ''], $alunos);
        if (\App\adms\Models\helper\AdmsTipoInstituicao::ehEscola()) {
            $ja = array_column($saida, 'id');
            foreach (self::todos('SELECT DISTINCT u.id, u.nome, u.nome_social, u.rm, r.nome AS responsavel FROM esc_responsaveis r JOIN esc_responsavel_alunos ra ON ra.responsavel_id = r.id
                JOIN col_usuarios u ON u.id = ra.aluno_id WHERE r.nome LIKE ? ORDER BY u.nome LIMIT 15', [$like]) as $a) {
                if (!in_array((int) $a['id'], $ja, true)) {
                    $saida[] = ['id' => (int) $a['id'], 'nome' => $a['nome_social'] ?: $a['nome'], 'rm' => (string) $a['rm'], 'por' => 'responsável: ' . $a['responsavel']];
                }
            }
        }
        return array_slice($saida, 0, 20);
    }
}
