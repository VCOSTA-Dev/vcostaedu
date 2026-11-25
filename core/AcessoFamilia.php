<?php

/**
 * A BARREIRA DE ACESSO DA FAMÍLIA (Onda 7, Etapa 5; docs/RESPONSAVEIS.md, D-65) — o PONTO ÚNICO que decide o que um
 * responsável pode ver. Dado o responsável, o aluno, o ano letivo e o tipo de informação (acadêmica ou financeira),
 * responde sim ou não, pela designação e pelas liberações daquele ano, em vigor na data (hoje, se não for dada).
 *
 *   - o financeiro: vê o acadêmico e o financeiro daquele aluno, naquele ano;
 *   - o titular: vê o acadêmico; o financeiro, nunca (a não ser que seja também o financeiro);
 *   - o acesso adicional: vê o acadêmico, se a regra da escola o permitir; o financeiro, NUNCA;
 *   - sem papel naquele ano (ou com o papel encerrado antes da data): nada daquele ano;
 *   - o responsável bloqueado: nada.
 *
 * Nada aqui confia na sessão: cada pergunta lê o banco. Toda tela e toda rota do portal do responsável passam por aqui
 * (Familia::exigir), e "Verificações de segurança" confere que passam. "Pode ver o financeiro?" já responde certo, para
 * o módulo financeiro usar quando existir. Arquivo único na raiz, usado pelo painel e pelo portal (recebe a conexão de
 * cada um); core/.htaccess nega a web.
 */
final class VcAcessoFamilia
{
    public const ACADEMICO = 'academico';
    public const FINANCEIRO = 'financeiro';

    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /** O acesso acadêmico adicional está permitido na escola (Regras da escola; o padrão: sim)? */
    public function adicionalPermitido(): bool
    {
        $st = $this->pdo->prepare("SELECT valor FROM esc_configuracao WHERE chave = 'responsaveis.adicional'");
        $st->execute();
        $v = $st->fetchColumn();
        return $v === false || (string) $v !== '0';
    }

    /** O responsável existe e não está bloqueado. */
    public function ativo(int $responsavel): bool
    {
        $st = $this->pdo->prepare('SELECT bloqueado FROM esc_responsaveis WHERE id = ?');
        $st->execute([$responsavel]);
        $b = $st->fetchColumn();
        return $b !== false && (int) $b === 0;
    }

    /** Os papéis em vigor do responsável no aluno, no ano, na data. */
    public function papeis(int $responsavel, int $aluno, int $ano, ?string $data = null): array
    {
        $data = $data ?: date('Y-m-d');
        $st = $this->pdo->prepare('SELECT DISTINCT papel FROM esc_responsavel_designacoes
             WHERE responsavel_id = ? AND aluno_id = ? AND ano = ? AND inicio <= ? AND (fim IS NULL OR fim > ?)');
        $st->execute([$responsavel, $aluno, $ano, $data, $data]);
        return array_column($st->fetchAll(\PDO::FETCH_ASSOC), 'papel');
    }

    /** O PONTO ÚNICO: pode ver a informação do tipo ($tipo: academico | financeiro) deste aluno, neste ano? */
    public function pode(int $responsavel, int $aluno, int $ano, string $tipo, ?string $data = null): bool
    {
        if ($responsavel <= 0 || $aluno <= 0 || $ano <= 0 || !in_array($tipo, [self::ACADEMICO, self::FINANCEIRO], true) || !$this->ativo($responsavel)) {
            return false;
        }
        $papeis = $this->papeis($responsavel, $aluno, $ano, $data);
        if ($tipo === self::FINANCEIRO) {
            return in_array('financeiro', $papeis, true); // o titular e o adicional: nunca
        }
        return in_array('financeiro', $papeis, true) || in_array('titular', $papeis, true)
            || (in_array('adicional', $papeis, true) && $this->adicionalPermitido());
    }

    /** "Pode ver o financeiro?" — sim só para o responsável financeiro daquele aluno, naquele ano, na data. */
    public function podeVerFinanceiro(int $responsavel, int $aluno, int $ano, ?string $data = null): bool
    {
        return $this->pode($responsavel, $aluno, $ano, self::FINANCEIRO, $data);
    }

    /** Os anos em que o responsável tem acesso acadêmico ao aluno (do mais novo). */
    public function anos(int $responsavel, int $aluno): array
    {
        $st = $this->pdo->prepare('SELECT DISTINCT ano FROM esc_responsavel_designacoes WHERE responsavel_id = ? AND aluno_id = ? ORDER BY ano DESC');
        $st->execute([$responsavel, $aluno]);
        return array_values(array_filter(array_map('intval', array_column($st->fetchAll(\PDO::FETCH_ASSOC), 'ano')), fn($a) => $this->pode($responsavel, $aluno, $a, self::ACADEMICO)));
    }

    /** Os filhos: os alunos de que o responsável tem acesso acadêmico em algum ano (id, nome, rm). */
    public function filhos(int $responsavel): array
    {
        if (!$this->ativo($responsavel)) {
            return [];
        }
        $st = $this->pdo->prepare('SELECT DISTINCT u.id, u.nome, u.nome_social, u.rm FROM esc_responsavel_designacoes d INNER JOIN col_usuarios u ON u.id = d.aluno_id
             WHERE d.responsavel_id = ? ORDER BY u.nome');
        $st->execute([$responsavel]);
        return array_values(array_filter($st->fetchAll(\PDO::FETCH_ASSOC), fn($a) => $this->anos($responsavel, (int) $a['id']) !== []));
    }
}
