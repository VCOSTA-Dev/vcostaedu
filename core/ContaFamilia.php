<?php

/**
 * A CONTA DO RESPONSÁVEL NO PORTAL (Onda 7, Etapa 3; docs/RESPONSAVEIS.md, D-63) — o convite, a senha e a entrada.
 *
 *   - o CONVITE é um link de uso único, com validade (Regras da escola; o padrão, 7 dias). No banco fica só o resumo do
 *     código (sha256); o código vai no FRAGMENTO do link (#c=…), que o navegador não manda ao servidor. Gerar de novo
 *     invalida o anterior. O mesmo registro serve ao "Esqueci a senha" (tipo "senha", 2 horas);
 *   - a SENHA segue a regra das senhas do sistema (core/Senha.php) e é guardada só como resumo (password_hash);
 *   - a ENTRADA é pelo LOGIN da pessoa (Onda 8, D-67: o CPF ou o e-mail escolhido no convite, em
 *     esc_responsaveis.login; core/LoginPortal.php), com a senha; o bloqueado não entra.
 *
 * Arquivo único na raiz, usado pelo painel (gerar o convite) e pelo portal (abrir o convite, entrar, pedir senha nova):
 * recebe a conexão de cada um. Cada convite gerado, cada senha definida e cada pedido de senha vão para a auditoria dos
 * responsáveis. core/.htaccess nega a web.
 */
require_once __DIR__ . '/Senha.php';
require_once __DIR__ . '/LoginPortal.php';

final class VcContaFamilia
{
    /** Validade do link de senha nova (o "Esqueci a senha"), em horas. */
    public const HORAS_SENHA = 2;

    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /** A instalação é uma escola? (os responsáveis só existem lá) */
    public function ehEscola(): bool
    {
        try {
            $st = $this->pdo->prepare("SELECT valor FROM adms_instalacao_estado WHERE chave = 'instituicao.tipo' LIMIT 1");
            $st->execute();
            return (string) $st->fetchColumn() === 'escola';
        } catch (\Throwable $e) {
            return false;
        }
    }

    /** O acesso da família está ligado nesta instalação (a escola, com a migration 0040)? Pela conexão da requisição. */
    public static function ligada(): bool
    {
        static $v = null;
        if ($v !== null) {
            return $v;
        }
        try {
            require_once __DIR__ . '/Banco.php';
            $c = new self(\VcBanco::daRequisicao());
            return $v = $c->ehEscola() && $c->pronto();
        } catch (\Throwable $e) {
            return $v = false;
        }
    }

    /** A tabela dos convites existe (a migration 0040 já rodou)? */
    public function pronto(): bool
    {
        try {
            $this->pdo->query('SELECT 1 FROM esc_responsavel_convites LIMIT 0');
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function auditar(string $acao, int $pessoa, ?array $depois = null, ?int $por = null, string $origem = 'portal'): void
    {
        $this->pdo->prepare('INSERT INTO esc_responsavel_auditoria (em, por, origem, acao, responsavel_id, depois) VALUES (NOW(), ?, ?, ?, ?, ?)')
            ->execute([$por, $origem, $acao, $pessoa, $depois === null ? null : json_encode($depois, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
    }

    public function pessoa(int $id): ?array
    {
        $st = $this->pdo->prepare('SELECT id, nome, email, cpf, ' . (\VcLoginPortal::temColuna($this->pdo) ? 'login' : 'NULL AS login') . ', celular, senha, bloqueado, ativado_em FROM esc_responsaveis WHERE id = ?');
        $st->execute([$id]);
        return $st->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    /** A validade do convite, em dias (Regras da escola; entre 1 e 30). */
    public function diasConvite(): int
    {
        $st = $this->pdo->prepare("SELECT valor FROM esc_configuracao WHERE chave = 'responsaveis.convite_dias'");
        $st->execute();
        $v = $st->fetchColumn();
        return max(1, min(30, $v === false ? 7 : (int) $v));
    }

    /** A pessoa tem papel em vigor (ou começando) em algum aluno? */
    public function temAluno(int $pessoa): bool
    {
        $st = $this->pdo->prepare('SELECT 1 FROM esc_responsavel_designacoes WHERE responsavel_id = ? AND fim IS NULL LIMIT 1');
        $st->execute([$pessoa]);
        return (bool) $st->fetchColumn();
    }

    /** O primeiro nome (o nome social, se houver) de cada aluno de quem a pessoa tem papel: o convite não mostra mais que isso. */
    public function primeirosNomes(int $pessoa): array
    {
        $st = $this->pdo->prepare('SELECT DISTINCT u.nome, u.nome_social FROM esc_responsavel_designacoes d INNER JOIN col_usuarios u ON u.id = d.aluno_id WHERE d.responsavel_id = ? AND d.fim IS NULL ORDER BY u.nome');
        $st->execute([$pessoa]);
        $nomes = [];
        foreach ($st->fetchAll(\PDO::FETCH_ASSOC) as $a) {
            $nomes[] = explode(' ', trim((string) ($a['nome_social'] ?: $a['nome'])))[0];
        }
        return array_values(array_unique($nomes));
    }

    /** A lista em português: "Ana", "Ana e Bruno", "Ana, Bruno e Carla". */
    public static function lista(array $nomes, string $vazio = ''): string
    {
        if (!$nomes) {
            return $vazio;
        }
        return count($nomes) > 1 ? implode(', ', array_slice($nomes, 0, -1)) . ' e ' . end($nomes) : $nomes[0];
    }

    /**
     * Gera o código (convite ou senha nova): o anterior em aberto deixa de valer. A verificação de quem pode receber
     * fica com quem chama. @return array ['id', 'codigo', 'expira']
     */
    public function gerar(int $pessoa, string $tipo, ?int $por, string $origem): array
    {
        $tipo = $tipo === 'senha' ? 'senha' : 'convite';
        $codigo = bin2hex(random_bytes(24));
        $expira = date('Y-m-d H:i:s', time() + ($tipo === 'senha' ? self::HORAS_SENHA * 3600 : $this->diasConvite() * 86400));
        $this->pdo->prepare('UPDATE esc_responsavel_convites SET cancelado_em = NOW() WHERE responsavel_id = ? AND usado_em IS NULL AND cancelado_em IS NULL')->execute([$pessoa]);
        $this->pdo->prepare('INSERT INTO esc_responsavel_convites (responsavel_id, tipo, resumo, criado_em, expira_em, criado_por) VALUES (?, ?, ?, NOW(), ?, ?)')
            ->execute([$pessoa, $tipo, hash('sha256', $codigo), $expira, $por]);
        $id = (int) $this->pdo->lastInsertId();
        $this->auditar($tipo === 'senha' ? 'senha_pedida' : 'convite_gerado', $pessoa, ['convite' => $id, 'expira' => $expira], $por, $origem);
        return ['id' => $id, 'codigo' => $codigo, 'expira' => $expira];
    }

    /** O código tem a forma de um código (48 letras hexadecimais)? */
    public static function formaValida(string $codigo): bool
    {
        return (bool) preg_match('/^[a-f0-9]{48}$/', $codigo);
    }

    /**
     * O convite (ou o link de senha nova) que vale: não usado, não cancelado, no prazo, de uma pessoa sem bloqueio.
     * @return array|null o convite com o nome e o e-mail da pessoa
     */
    public function convite(string $codigo, bool $travar = false): ?array
    {
        if (!self::formaValida($codigo)) {
            return null;
        }
        $st = $this->pdo->prepare('SELECT c.id, c.responsavel_id, c.tipo, c.expira_em, r.nome, r.email, r.cpf, r.bloqueado
              FROM esc_responsavel_convites c INNER JOIN esc_responsaveis r ON r.id = c.responsavel_id
             WHERE c.resumo = ? AND c.usado_em IS NULL AND c.cancelado_em IS NULL AND c.expira_em > NOW()' . ($travar ? ' FOR UPDATE' : ''));
        $st->execute([hash('sha256', $codigo)]);
        $c = $st->fetch(\PDO::FETCH_ASSOC);
        if (!$c || (int) $c['bloqueado'] === 1) {
            return null;
        }
        return $c;
    }

    /**
     * Define a senha pelo convite (o primeiro acesso) ou pelo link de senha nova. O código vale uma vez só: a mesma
     * transação marca o uso. @return true|string a recusa
     */
    public function definirSenha(string $codigo, string $senha, string $repetida, string $escolha = '')
    {
        $this->pdo->beginTransaction();
        try {
            $c = $this->convite($codigo, true);
            if (!$c) {
                $this->pdo->rollBack();
                return 'Este link é inválido, já foi usado ou expirou. Peça um novo à secretaria da escola.';
            }
            if ($senha !== $repetida) {
                $this->pdo->rollBack();
                return 'As duas senhas não são iguais.';
            }
            $problemas = \VcSenha::problemas($senha, ['nome' => (string) $c['nome'], 'email' => (string) $c['email'], 'usuario' => (string) $c['cpf']]);
            if ($problemas) {
                $this->pdo->rollBack();
                return implode(' ', $problemas);
            }
            $pessoa = (int) $c['responsavel_id'];
            /* Onda 8 (D-67): a conta nasce com o login escolhido (o CPF ou o e-mail do cadastro), se ainda não tem */
            $login = $this->loginDoConvite($pessoa, $escolha);
            if (is_array($login)) {
                $this->pdo->rollBack();
                return $login['recusa'];
            }
            $this->pdo->prepare('UPDATE esc_responsaveis SET senha = ?, ativado_em = COALESCE(ativado_em, NOW()), alterado_em = NOW()' . ($login !== null ? ', login = ?' : '') . ' WHERE id = ?')
                ->execute($login !== null ? [password_hash($senha, PASSWORD_DEFAULT), $login, $pessoa] : [password_hash($senha, PASSWORD_DEFAULT), $pessoa]);
            if ($login !== null) {
                $this->auditar('login_definido', $pessoa, ['login' => \VcLoginPortal::mostrar($login)]);
            }
            $this->pdo->prepare('UPDATE esc_responsavel_convites SET usado_em = NOW() WHERE id = ?')->execute([(int) $c['id']]);
            $this->pdo->prepare('UPDATE esc_responsavel_convites SET cancelado_em = NOW() WHERE responsavel_id = ? AND id <> ? AND usado_em IS NULL AND cancelado_em IS NULL')
                ->execute([$pessoa, (int) $c['id']]);
            $this->auditar($c['tipo'] === 'senha' ? 'senha_trocada' : 'convite_usado', $pessoa, ['convite' => (int) $c['id']]);
            $this->pdo->commit();
            return true;
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * A pessoa pelo LOGIN (Onda 8, D-67): o CPF válido (com ou sem a pontuação) ou o e-mail, como guardados no login da
     * conta. O e-mail e o CPF do cadastro não entram (a conta sem login ainda não criou a senha).
     */
    public function porIdentificador(string $ident): ?array
    {
        $login = \VcLoginPortal::normalizar($ident);
        if ($login === '' || !\VcLoginPortal::temColuna($this->pdo)) {
            return null;
        }
        $st = $this->pdo->prepare('SELECT id FROM esc_responsaveis WHERE login = ? LIMIT 1');
        $st->execute([$login]);
        $id = $st->fetchColumn();
        return $id ? $this->pessoa((int) $id) : null;
    }

    /** As escolhas de login da pessoa (o e-mail e o CPF do cadastro, livres ou em uso). */
    public function opcoesLogin(int $pessoa): array
    {
        $p = $this->pessoa($pessoa);
        return $p ? \VcLoginPortal::opcoes($this->pdo, $p) : [];
    }

    /**
     * O login que a conta ganha no convite: null quando ela já tem (fica o mesmo); o escolhido ('email' | 'cpf'), ou o
     * único que há; sem escolha, o e-mail. ['recusa' => …] quando não há login livre.
     * @return string|array|null
     */
    private function loginDoConvite(int $pessoa, string $escolha)
    {
        $p = $this->pessoa($pessoa);
        if (!$p || !\VcLoginPortal::temColuna($this->pdo) || (string) ($p['login'] ?? '') !== '') {
            return null;
        }
        $op = \VcLoginPortal::opcoes($this->pdo, $p);
        if (!$op) {
            return ['recusa' => 'O seu cadastro não tem e-mail nem CPF válido para ser o login. Peça à secretaria da escola para completar o cadastro.'];
        }
        $esc = null;
        foreach ($op as $o) {
            if ($o['tipo'] === $escolha) {
                $esc = $o;
            }
        }
        $esc = $esc ?? $op[0];
        if (!$esc['livre']) {
            return ['recusa' => \VcLoginPortal::mensagemEmUso($esc['quem'], \VcLoginPortal::mostrar($esc['valor']))];
        }
        return $esc['valor'];
    }

    /**
     * Troca o login da conta (pela ficha, na secretaria): o e-mail ou o CPF do cadastro, se não for o login de outra
     * conta. @return true|string a recusa
     */
    public function trocarLogin(int $pessoa, string $tipo, ?int $por, string $origem = 'tela')
    {
        $p = $this->pessoa($pessoa);
        if (!$p) {
            return 'Responsável não encontrado.';
        }
        foreach (\VcLoginPortal::opcoes($this->pdo, $p) as $o) {
            if ($o['tipo'] !== $tipo) {
                continue;
            }
            if (!$o['livre']) {
                return \VcLoginPortal::mensagemEmUso($o['quem'], \VcLoginPortal::mostrar($o['valor']));
            }
            if ((string) ($p['login'] ?? '') === $o['valor']) {
                return true;
            }
            $this->pdo->prepare('UPDATE esc_responsaveis SET login = ?, alterado_em = NOW() WHERE id = ?')->execute([$o['valor'], $pessoa]);
            $this->auditar('login_trocado', $pessoa, ['antes' => \VcLoginPortal::mostrar($p['login'] ?? null), 'depois' => \VcLoginPortal::mostrar($o['valor'])], $por, $origem);
            return true;
        }
        return $tipo === 'cpf' ? 'O cadastro desta pessoa não tem um CPF válido.' : 'O cadastro desta pessoa não tem e-mail.';
    }

    /**
     * A ENTRADA: o identificador e a senha. @return array ['ok' => pessoa] | ['bloqueado' => true] | null (não confere)
     * Quem ainda não definiu a senha (não usou o convite) não entra.
     */
    public function entrar(string $ident, string $senha): ?array
    {
        $p = $this->porIdentificador($ident);
        if (!$p || empty($p['senha']) || !password_verify($senha, (string) $p['senha'])) {
            return null;
        }
        if ((int) $p['bloqueado'] === 1) {
            return ['bloqueado' => true];
        }
        $this->pdo->prepare('UPDATE esc_responsaveis SET ultimo_acesso = NOW() WHERE id = ?')->execute([(int) $p['id']]);
        if (password_needs_rehash((string) $p['senha'], PASSWORD_DEFAULT)) {
            $this->pdo->prepare('UPDATE esc_responsaveis SET senha = ? WHERE id = ?')->execute([password_hash($senha, PASSWORD_DEFAULT), (int) $p['id']]);
        }
        unset($p['senha']);
        return ['ok' => $p];
    }

    /**
     * O "Esqueci a senha": só para a pessoa com e-mail, sem bloqueio, que já entrou alguma vez (quem nunca entrou usa o
     * convite) e que tem papel em algum aluno. @return array|null ['pessoa', 'codigo', 'expira']
     */
    public function pedirSenha(string $ident): ?array
    {
        $p = $this->porIdentificador($ident);
        if (!$p || (int) $p['bloqueado'] === 1 || empty($p['ativado_em']) || !filter_var((string) $p['email'], FILTER_VALIDATE_EMAIL) || !$this->temAluno((int) $p['id'])) {
            return null;
        }
        $g = $this->gerar((int) $p['id'], 'senha', null, 'portal');
        unset($p['senha']);
        return ['pessoa' => $p, 'codigo' => $g['codigo'], 'expira' => $g['expira'], 'id' => $g['id']];
    }

    /** Marca o envio por e-mail. */
    public function marcarEnviado(int $conviteId, int $pessoa, ?int $por, string $origem): void
    {
        $this->pdo->prepare('UPDATE esc_responsavel_convites SET enviado_em = NOW() WHERE id = ?')->execute([$conviteId]);
        $this->auditar('convite_enviado', $pessoa, ['convite' => $conviteId], $por, $origem);
    }
}
