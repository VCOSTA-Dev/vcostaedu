<?php

namespace App\adms\Models\escola;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\helper\AdmsRead;

require_once dirname(__DIR__, 5) . '/core/LoginPortal.php';

/**
 * OS RESPONSÁVEIS (Onda 7, Etapa 2; docs/RESPONSAVEIS.md, D-62) — só na escola.
 *
 * A pessoa responsável tem cadastro próprio (esc_responsaveis) e pode ser responsável por vários alunos; o parentesco
 * fica em cada par pessoa–aluno (esc_responsavel_alunos). O papel vale por aluno e por ano letivo
 * (esc_responsavel_designacoes):
 *   - financeiro: um só, sempre (a cobrança sai em nome de uma pessoa) — o banco recusa o segundo em vigor;
 *   - titular: o acadêmico titular, um só (o do contrato); pode ser a mesma pessoa do financeiro;
 *   - adicional: o acesso acadêmico adicional, quantos a escola permitir (Regras da escola), cada um com o motivo, a
 *     referência do termo, quem liberou e quando.
 * A designação em vigor não tem fim; a troca encerra a anterior na data da troca, com o motivo, e abre a nova na mesma
 * data — nunca há dois em vigor. Os papéis de um ano não valem para outro. Toda designação, liberação e encerramento
 * vai para a auditoria (quem, quando, o que havia antes, o que ficou, o motivo).
 *
 * As mesmas funções servem às três portas (a tela, a planilha, a interface de integração) e servirão ao passo
 * "Responsáveis" da matrícula e à montagem do contrato (o financeiro e o titular são as partes do contrato).
 */
class AdmsResponsaveis
{
    public const PAPEIS = [
        'financeiro' => 'Responsável financeiro',
        'titular' => 'Responsável acadêmico titular',
        'adicional' => 'Acesso acadêmico adicional',
    ];
    public const PARENTESCOS = ['Mãe', 'Pai', 'Avó', 'Avô', 'Madrasta', 'Padrasto', 'Tia', 'Tio', 'Irmã', 'Irmão', 'Tutor(a) legal', 'Outro'];
    public const SITUACOES = [
        'sem_convite' => 'Sem convite',
        'convite' => 'Convite enviado',
        'ativo' => 'Ativo',
        'bloqueado' => 'Bloqueado',
    ];

    /* ------------------------------------------------------------------ o básico */

    public static function digitos(?string $s): string
    {
        return preg_replace('/\D+/', '', (string) $s);
    }

    /** O CPF com os dígitos verificadores certos (11 dígitos, não todos iguais). */
    public static function cpfValido(string $cpf): bool
    {
        return \App\adms\Models\helper\AdmsCpf::valido($cpf);
    }

    public static function cpfFormatado(?string $cpf): string
    {
        $c = self::digitos($cpf);
        return strlen($c) === 11 ? substr($c, 0, 3) . '.' . substr($c, 3, 3) . '.' . substr($c, 6, 3) . '-' . substr($c, 9) : '';
    }

    public static function celularFormatado(?string $cel): string
    {
        $c = self::digitos($cel);
        if (strlen($c) === 11) {
            return '(' . substr($c, 0, 2) . ') ' . substr($c, 2, 5) . '-' . substr($c, 7);
        }
        if (strlen($c) === 10) {
            return '(' . substr($c, 0, 2) . ') ' . substr($c, 2, 4) . '-' . substr($c, 6);
        }
        return $c;
    }

    private static function pdo(): \PDO
    {
        return AdmsEscolaBanco::pdo();
    }

    private static function um(string $sql, array $p = []): ?array
    {
        $st = self::pdo()->prepare($sql);
        $st->execute($p);
        $r = $st->fetch(\PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    private static function todos(string $sql, array $p = []): array
    {
        $st = self::pdo()->prepare($sql);
        $st->execute($p);
        return $st->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    private static function usuario(): ?int
    {
        $u = (int) ($_SESSION['colUsuario_id'] ?? 0);
        return $u ?: null;
    }

    /** As regras da escola para os responsáveis (Regras da escola). */
    public static function adicionalPermitido(): bool
    {
        return AdmsEscolaConfig::valor('responsaveis.adicional') === '1';
    }

    /** 0 = sem limite. */
    public static function adicionalLimite(): int
    {
        return max(0, (int) AdmsEscolaConfig::valor('responsaveis.adicional_limite'));
    }

    /** Os anos letivos da escola (os abertos em Ano letivo e etapas), do mais novo; sem nenhum, o ano corrente. */
    public static function anos(): array
    {
        $anos = array_map('intval', array_column(self::todos('SELECT ano FROM esc_anos ORDER BY ano DESC'), 'ano'));
        return $anos ?: [(int) date('Y')];
    }

    /** A data de hoje (para a troca e o encerramento sem data informada). */
    public static function hoje(): string
    {
        return date('Y-m-d');
    }

    /* ------------------------------------------------------------------ a auditoria */

    public static function auditar(string $acao, ?int $pessoa, ?int $aluno, ?int $ano, $antes, $depois, ?string $motivo, string $origem = 'tela', ?int $por = null): void
    {
        $j = static fn($x) => $x === null ? null : json_encode($x, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        self::pdo()->prepare('INSERT INTO esc_responsavel_auditoria (em, por, origem, acao, responsavel_id, aluno_id, ano, antes, depois, motivo) VALUES (NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?)')
            ->execute([$por ?? self::usuario(), $origem, $acao, $pessoa, $aluno, $ano, $j($antes), $j($depois), $motivo !== null ? mb_substr($motivo, 0, 255) : null]);
    }

    /** A auditoria de um aluno (ou de uma pessoa), da mais nova. */
    public static function historico(?int $aluno, ?int $pessoa = null, int $limite = 60): array
    {
        $w = $aluno ? 'a.aluno_id = ?' : 'a.responsavel_id = ?';
        return self::todos("SELECT a.*, u.nome AS por_nome, r.nome AS pessoa FROM esc_responsavel_auditoria a
              LEFT JOIN adms_usuarios u ON u.id = a.por LEFT JOIN esc_responsaveis r ON r.id = a.responsavel_id
             WHERE {$w} ORDER BY a.id DESC LIMIT " . (int) $limite, [$aluno ?: $pessoa]);
    }

    /* ------------------------------------------------------------------ a pessoa */

    public static function pessoa(int $id): ?array
    {
        return $id > 0 ? self::um('SELECT * FROM esc_responsaveis WHERE id = ?', [$id]) : null;
    }

    /**
     * Limpa e confere o cadastro da pessoa. Para ter acesso ao portal, a pessoa precisa de e-mail ou de CPF (o
     * identificador de entrada) — sem os dois, o cadastro vale, mas o convite pede um deles.
     * @return array{0: ?array, 1: string} os campos, ou a recusa
     */
    public static function limparPessoa(array $d): array
    {
        $nome = trim(preg_replace('/\s+/', ' ', (string) ($d['nome'] ?? '')));
        $email = mb_strtolower(trim((string) ($d['email'] ?? '')));
        $cpf = self::digitos($d['cpf'] ?? '');
        $cel = self::digitos($d['celular'] ?? '');
        if (mb_strlen($nome) < 3) {
            return [null, 'Informe o nome da pessoa responsável.'];
        }
        if (mb_strlen($nome) > 150) {
            return [null, 'O nome tem no máximo 150 caracteres.'];
        }
        if ($email !== '' && (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190)) {
            return [null, 'O e-mail não é válido.'];
        }
        if ($cpf !== '' && !self::cpfValido($cpf)) {
            return [null, 'O CPF não é válido (confira os números).'];
        }
        if ($cel !== '' && (strlen($cel) < 10 || strlen($cel) > 11)) {
            return [null, 'O celular tem o DDD e 8 ou 9 números.'];
        }
        return [['nome' => $nome, 'email' => $email !== '' ? $email : null, 'cpf' => $cpf !== '' ? $cpf : null, 'celular' => $cel !== '' ? $cel : null], ''];
    }

    /** D-99: o endereço da pessoa responsável (o "Ver endereço" da ficha financeira), opcional. */
    public const CAMPOS_ENDERECO = ['endereco' => 160, 'numero' => 20, 'complemento' => 80, 'bairro' => 80, 'cidade' => 80, 'uf' => 2, 'cep' => 9];

    /**
     * Grava o endereço, só quando ele veio no pedido (o formulário da tela manda; a planilha e a interface, não: o endereço
     * que já existe não se perde). @return string '' quando gravou (ou não veio); senão, o motivo
     */
    public static function gravarEndereco(int $id, array $d): string
    {
        if (!array_key_exists('endereco', $d)) {
            return '';
        }
        $v = [];
        foreach (self::CAMPOS_ENDERECO as $k => $max) {
            $x = trim(preg_replace('/\s+/', ' ', (string) ($d[$k] ?? '')));
            if ($k === 'uf') {
                $x = mb_strtoupper($x);
            }
            if ($k === 'cep') {
                $x = self::digitos($x);
                $x = strlen($x) === 8 ? substr($x, 0, 5) . '-' . substr($x, 5) : $x;
            }
            if (mb_strlen($x) > $max) {
                return 'O campo ' . $k . ' do endereço tem no máximo ' . $max . ' caracteres.';
            }
            $v[$k] = $x !== '' ? $x : null;
        }
        self::pdo()->prepare('UPDATE esc_responsaveis SET endereco = ?, numero = ?, complemento = ?, bairro = ?, cidade = ?, uf = ?, cep = ? WHERE id = ?')
            ->execute([$v['endereco'], $v['numero'], $v['complemento'], $v['bairro'], $v['cidade'], $v['uf'], $v['cep'], $id]);
        return '';
    }

    /** O endereço numa linha ("Rua X, 100, apto 2 — Bairro — Cidade/UF — CEP 00000-000"), ou ''. */
    public static function enderecoEmLinha(array $p): string
    {
        $rua = implode(', ', array_filter([(string) ($p['endereco'] ?? ''), (string) ($p['numero'] ?? ''), (string) ($p['complemento'] ?? '')], 'strlen'));
        $cid = (string) ($p['cidade'] ?? '') . (($p['uf'] ?? '') ? '/' . $p['uf'] : '');
        return implode(' — ', array_filter([$rua, (string) ($p['bairro'] ?? ''), $cid, ($p['cep'] ?? '') ? 'CEP ' . $p['cep'] : ''], 'strlen'));
    }

    /** O link do WhatsApp para o celular (só o link "wa.me", sem integração), ou ''. */
    public static function whatsapp(?string $cel): string
    {
        $c = self::digitos($cel);
        return strlen($c) >= 10 ? 'https://wa.me/55' . $c : '';
    }

    /** A pessoa que já existe com este e-mail ou este CPF (a primeira; $exceto: a própria, na edição). */
    public static function procurar(?string $email, ?string $cpf, int $exceto = 0): ?array
    {
        $email = mb_strtolower(trim((string) $email));
        $cpf = self::digitos($cpf);
        if ($email === '' && $cpf === '') {
            return null;
        }
        return self::um('SELECT * FROM esc_responsaveis WHERE id <> ? AND ((? <> \'\' AND email = ?) OR (? <> \'\' AND cpf = ?)) ORDER BY id LIMIT 1', [$exceto, $email, $email, $cpf, $cpf]);
    }

    /**
     * Cria a pessoa. Se já existe alguém com o mesmo e-mail ou CPF, não cria: devolve ['existe' => a pessoa].
     * @return int|array|string o id; ['existe' => pessoa]; ou a recusa
     */
    public static function criar(array $d, string $origem = 'tela', ?int $por = null)
    {
        [$c, $erro] = self::limparPessoa($d);
        if (!$c) {
            return $erro;
        }
        if ($ja = self::procurar($c['email'], $c['cpf'])) {
            return ['existe' => $ja];
        }
        if ($recusa = self::loginDeOutro($c, 0)) {
            return $recusa;
        }
        try {
            self::pdo()->prepare('INSERT INTO esc_responsaveis (nome, email, celular, cpf, criado_por, criado_em) VALUES (?, ?, ?, ?, ?, NOW())')
                ->execute([$c['nome'], $c['email'], $c['celular'], $c['cpf'], $por ?? self::usuario()]);
        } catch (\PDOException $e) {
            return 'A pessoa não foi cadastrada: o e-mail ou o CPF já está em outro cadastro.';
        }
        $id = (int) self::pdo()->lastInsertId();
        self::gravarEndereco($id, $d);
        self::auditar('cadastrar', $id, null, null, null, $c, null, $origem, $por);
        return $id;
    }

    /** @return true|string */
    public static function editar(int $id, array $d)
    {
        $p = self::pessoa($id);
        if (!$p) {
            return 'Responsável não encontrado.';
        }
        [$c, $erro] = self::limparPessoa($d);
        if (!$c) {
            return $erro;
        }
        if ($ja = self::procurar($c['email'], $c['cpf'], $id)) {
            return 'Já existe outro responsável com este e-mail ou este CPF: ' . $ja['nome'] . '.';
        }
        if ($recusa = self::loginDeOutro($c, $id)) {
            return $recusa;
        }
        if ($erroEnd = self::gravarEndereco($id, $d)) {
            return $erroEnd;
        }
        self::pdo()->prepare('UPDATE esc_responsaveis SET nome = ?, email = ?, celular = ?, cpf = ?, alterado_em = NOW() WHERE id = ?')
            ->execute([$c['nome'], $c['email'], $c['celular'], $c['cpf'], $id]);
        $antes = array_intersect_key($p, $c);
        if ($antes != $c) {
            self::auditar('editar', $id, null, null, $antes, $c, null);
        }
        return true;
    }

    /**
     * Onda 8 (D-67): o e-mail ou o CPF que já é o LOGIN de outra conta no portal (um responsável que trocou o cadastro
     * depois de criar a conta) não entra em outro cadastro: dois logins iguais não existem. @return string a recusa, ou ''
     */
    public static function loginDeOutro(array $c, int $exceto): string
    {
        foreach (['email' => 'O e-mail', 'cpf' => 'O CPF'] as $campo => $nome) {
            $v = (string) ($c[$campo] ?? '');
            if ($v !== '' && ($q = \VcLoginPortal::emUso(self::pdo(), $v, $exceto))) {
                return \VcLoginPortal::mensagemEmUso($q, \VcLoginPortal::mostrar(\VcLoginPortal::normalizar($v)));
            }
        }
        return '';
    }

    /** O login do portal, para mostrar (o CPF com a pontuação), ou '' sem conta. */
    public static function loginMostrado(array $p): string
    {
        return \VcLoginPortal::mostrar((string) ($p['login'] ?? ''));
    }

    /** O parentesco da pessoa com o aluno (cria ou atualiza). */
    public static function vincular(int $pessoa, int $aluno, string $parentesco): void
    {
        $parentesco = mb_substr(trim($parentesco) ?: 'Outro', 0, 40);
        self::pdo()->prepare('INSERT INTO esc_responsavel_alunos (responsavel_id, aluno_id, parentesco, criado_em) VALUES (?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE parentesco = VALUES(parentesco)')->execute([$pessoa, $aluno, $parentesco]);
    }

    /**
     * A situação do acesso ao portal: bloqueado; ativo (já definiu a senha); convite enviado (um convite em aberto);
     * sem convite.
     */
    public static function situacaoAcesso(array $p): string
    {
        if (!empty($p['bloqueado'])) {
            return 'bloqueado';
        }
        if (!empty($p['senha'])) {
            return 'ativo';
        }
        if (self::conviteAberto((int) $p['id'])) {
            return 'convite';
        }
        return 'sem_convite';
    }

    /** O convite em aberto (Etapa 3): nem usado, nem cancelado, nem vencido. */
    public static function conviteAberto(int $pessoa): ?array
    {
        static $tem = null;
        $tem ??= (bool) self::um("SELECT 1 AS t FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'esc_responsavel_convites'");
        if (!$tem) {
            return null;
        }
        return self::um('SELECT * FROM esc_responsavel_convites WHERE responsavel_id = ? AND usado_em IS NULL AND cancelado_em IS NULL AND expira_em > NOW() ORDER BY id DESC LIMIT 1', [$pessoa]);
    }

    /**
     * A lista geral: cada pessoa com os alunos, o papel em vigor por ano e a situação do acesso. $busca: nome, e-mail,
     * CPF, celular ou o nome/RA de um aluno.
     */
    public static function listar(string $busca = '', int $limite = 200): array
    {
        $b = trim($busca);
        $par = [];
        $w = '1 = 1';
        if ($b !== '') {
            $dig = self::digitos($b);
            $w = '(r.nome LIKE ? OR r.email LIKE ? OR EXISTS (SELECT 1 FROM esc_responsavel_alunos ra INNER JOIN col_usuarios u ON u.id = ra.aluno_id WHERE ra.responsavel_id = r.id AND (u.nome LIKE ? OR u.rm = ?))'
                . ($dig !== '' ? ' OR r.cpf LIKE ? OR r.celular LIKE ?' : '') . ')';
            $par = ['%' . $b . '%', '%' . $b . '%', '%' . $b . '%', $b];
            if ($dig !== '') {
                $par[] = '%' . $dig . '%';
                $par[] = '%' . $dig . '%';
            }
        }
        $pessoas = self::todos("SELECT r.* FROM esc_responsaveis r WHERE {$w} ORDER BY r.nome LIMIT " . (int) $limite, $par);
        if (!$pessoas) {
            return [];
        }
        $ids = implode(',', array_map(static fn($p) => (int) $p['id'], $pessoas));
        $alunos = [];
        foreach (self::todos("SELECT ra.responsavel_id, ra.parentesco, u.id, u.nome, u.rm FROM esc_responsavel_alunos ra INNER JOIN col_usuarios u ON u.id = ra.aluno_id WHERE ra.responsavel_id IN ({$ids}) ORDER BY u.nome") as $a) {
            $alunos[(int) $a['responsavel_id']][] = $a;
        }
        $papeis = [];
        foreach (self::todos("SELECT d.responsavel_id, d.aluno_id, d.ano, d.papel FROM esc_responsavel_designacoes d WHERE d.responsavel_id IN ({$ids}) AND d.fim IS NULL ORDER BY d.ano DESC") as $d) {
            $papeis[(int) $d['responsavel_id']][(int) $d['aluno_id']][(int) $d['ano']][] = $d['papel'];
        }
        foreach ($pessoas as &$p) {
            $p['alunos'] = $alunos[(int) $p['id']] ?? [];
            $p['papeis'] = $papeis[(int) $p['id']] ?? [];
            $p['situacao'] = self::situacaoAcesso($p);
        }
        return $pessoas;
    }

    /** As pessoas já ligadas ao aluno (para escolher sem digitar de novo). */
    public static function pessoasDoAluno(int $aluno): array
    {
        return self::todos('SELECT r.*, ra.parentesco FROM esc_responsavel_alunos ra INNER JOIN esc_responsaveis r ON r.id = ra.responsavel_id WHERE ra.aluno_id = ? ORDER BY r.nome', [$aluno]);
    }

    /** Os alunos de uma pessoa, com o parentesco e o papel em vigor por ano. */
    public static function alunosDaPessoa(int $pessoa): array
    {
        $alunos = self::todos('SELECT u.id, u.nome, u.rm, ra.parentesco FROM esc_responsavel_alunos ra INNER JOIN col_usuarios u ON u.id = ra.aluno_id WHERE ra.responsavel_id = ? ORDER BY u.nome', [$pessoa]);
        foreach ($alunos as &$a) {
            $a['designacoes'] = self::todos('SELECT * FROM esc_responsavel_designacoes WHERE responsavel_id = ? AND aluno_id = ? ORDER BY ano DESC, fim IS NULL DESC, inicio DESC', [$pessoa, (int) $a['id']]);
        }
        return $alunos;
    }

    /* ------------------------------------------------------------------ o aluno */

    public static function aluno(int $id): ?array
    {
        return self::um('SELECT id, nome, nome_social, rm, email FROM col_usuarios WHERE id = ? AND col_niveis_acesso_id = 1', [$id]);
    }

    public static function alunoPorRa(string $ra): ?array
    {
        return self::um('SELECT id, nome, nome_social, rm, email FROM col_usuarios WHERE rm = ? AND col_niveis_acesso_id = 1 LIMIT 1', [trim($ra)]);
    }

    /** A designação em vigor de um papel único (financeiro ou titular) do aluno no ano. */
    public static function emVigor(int $aluno, int $ano, string $papel): ?array
    {
        return self::um('SELECT d.*, r.nome, r.email, r.celular, r.cpf FROM esc_responsavel_designacoes d INNER JOIN esc_responsaveis r ON r.id = d.responsavel_id
             WHERE d.aluno_id = ? AND d.ano = ? AND d.papel = ? AND d.fim IS NULL LIMIT 1', [$aluno, $ano, $papel]);
    }

    /** Tudo do aluno no ano: o financeiro, o titular, os adicionais em vigor, o histórico das designações e os contatos. */
    public static function doAluno(int $aluno, int $ano): array
    {
        $todas = self::todos('SELECT d.*, r.nome, r.email, r.celular, r.cpf, u.nome AS criado_por_nome, e.nome AS encerrado_por_nome,
                (SELECT ra.parentesco FROM esc_responsavel_alunos ra WHERE ra.responsavel_id = d.responsavel_id AND ra.aluno_id = d.aluno_id LIMIT 1) AS parentesco
              FROM esc_responsavel_designacoes d INNER JOIN esc_responsaveis r ON r.id = d.responsavel_id
              LEFT JOIN adms_usuarios u ON u.id = d.criado_por LEFT JOIN adms_usuarios e ON e.id = d.encerrado_por
             WHERE d.aluno_id = ? AND d.ano = ? ORDER BY d.fim IS NULL DESC, d.inicio DESC, d.id DESC', [$aluno, $ano]);
        $vig = static fn($p) => array_values(array_filter($todas, static fn($d) => $d['papel'] === $p && $d['fim'] === null));
        return [
            'financeiro' => $vig('financeiro')[0] ?? null,
            'titular' => $vig('titular')[0] ?? null,
            'adicionais' => $vig('adicional'),
            'encerradas' => array_values(array_filter($todas, static fn($d) => $d['fim'] !== null)),
            'contatos' => self::contatos($aluno),
            'pessoas' => self::pessoasDoAluno($aluno),
        ];
    }

    /**
     * Designa o financeiro ou o titular do aluno no ano. Se há outro em vigor, é a TROCA: exige o motivo, encerra o
     * anterior na data da troca e abre o novo na mesma data, na mesma transação — nunca há dois em vigor (e o banco
     * recusa, pelo índice único, se duas gravações chegarem juntas).
     * @param array $pessoa ['id' => int] ou os campos de uma pessoa nova (nome, e-mail, celular, CPF); 'parentesco'
     * @return true|string
     */
    public static function designar(int $aluno, int $ano, string $papel, array $pessoa, ?string $data = null, ?string $motivo = null, string $origem = 'tela', ?int $por = null)
    {
        if (!in_array($papel, ['financeiro', 'titular'], true)) {
            return 'Escolha o papel: o responsável financeiro ou o acadêmico titular.';
        }
        if (!self::aluno($aluno)) {
            return 'Aluno não encontrado.';
        }
        if ($ano < 2000 || $ano > 2100) {
            return 'Escolha o ano letivo.';
        }
        $data = self::data($data, $ano);
        if (is_array($data)) {
            return $data[0];
        }
        $motivo = trim((string) $motivo);
        $T = \Core\Transacao::class;
        $erro = '';
        $ok = $T::executar(function () use ($T, $aluno, $ano, $papel, $pessoa, $data, $motivo, $origem, $por, &$erro) {
            $pid = self::pessoaDe($pessoa, $origem, $por, $erro);
            if (!$pid) {
                $T::falhar($erro);
            }
            self::vincular($pid, $aluno, (string) ($pessoa['parentesco'] ?? ''));
            $st = self::pdo()->prepare('SELECT * FROM esc_responsavel_designacoes WHERE aluno_id = ? AND ano = ? AND papel = ? AND fim IS NULL FOR UPDATE');
            $st->execute([$aluno, $ano, $papel]);
            $atual = $st->fetch(\PDO::FETCH_ASSOC) ?: null;
            if ($atual && (int) $atual['responsavel_id'] === $pid) {
                return 'igual';
            }
            if ($atual) {
                if ($motivo === '') {
                    $erro = 'A troca do ' . mb_strtolower(self::PAPEIS[$papel]) . ' pede o motivo.';
                    $T::falhar($erro);
                }
                if ($data < $atual['inicio']) {
                    $erro = 'A data da troca não pode ser antes do início do responsável atual (' . date('d/m/Y', strtotime($atual['inicio'])) . ').';
                    $T::falhar($erro);
                }
                self::pdo()->prepare('UPDATE esc_responsavel_designacoes SET fim = ?, motivo_fim = ?, encerrado_por = ?, encerrado_em = NOW() WHERE id = ?')
                    ->execute([$data, $motivo, $por ?? self::usuario(), (int) $atual['id']]);
            }
            try {
                self::pdo()->prepare('INSERT INTO esc_responsavel_designacoes (aluno_id, ano, responsavel_id, papel, inicio, origem, criado_por, criado_em) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())')
                    ->execute([$aluno, $ano, $pid, $papel, $data, $origem, $por ?? self::usuario()]);
            } catch (\PDOException $e) {
                $erro = 'Nada foi gravado: ' . self::traduzirErro($e->getMessage());
                $T::falhar($erro);
            }
            self::auditar($atual ? 'trocar' : 'designar', $pid, $aluno, $ano, $atual ? ['papel' => $papel, 'responsavel_id' => (int) $atual['responsavel_id'], 'inicio' => $atual['inicio']] : null,
                ['papel' => $papel, 'responsavel_id' => $pid, 'inicio' => $data], $atual ? $motivo : null, $origem, $por);
            return 'gravado';
        });
        if (!$ok) {
            return $erro !== '' ? $erro : 'Nada foi gravado: ' . self::traduzirErro($T::ultimoErro());
        }
        return true;
    }

    /**
     * Libera o acesso acadêmico adicional: só com a regra da escola permitindo, dentro do limite por aluno, com o
     * motivo (obrigatório) e a referência do termo ou documento. Nunca dá alcance ao financeiro.
     * @return true|string
     */
    public static function liberar(int $aluno, int $ano, array $pessoa, ?string $motivo, ?string $referencia, ?string $data = null, ?int $por = null)
    {
        if (!self::adicionalPermitido()) {
            return 'A escola não permite o acesso acadêmico adicional (Regras da escola).';
        }
        if (!self::aluno($aluno)) {
            return 'Aluno não encontrado.';
        }
        $motivo = trim((string) $motivo);
        if (mb_strlen($motivo) < 5) {
            return 'Informe o motivo da liberação.';
        }
        $data = self::data($data, $ano);
        if (is_array($data)) {
            return $data[0];
        }
        $T = \Core\Transacao::class;
        $erro = '';
        $ok = $T::executar(function () use ($T, $aluno, $ano, $pessoa, $motivo, $referencia, $data, $por, &$erro) {
            $pid = self::pessoaDe($pessoa, 'tela', $por, $erro);
            if (!$pid) {
                $T::falhar($erro);
            }
            self::vincular($pid, $aluno, (string) ($pessoa['parentesco'] ?? ''));
            $st = self::pdo()->prepare('SELECT responsavel_id, papel FROM esc_responsavel_designacoes WHERE aluno_id = ? AND ano = ? AND fim IS NULL FOR UPDATE');
            $st->execute([$aluno, $ano]);
            $abertas = $st->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($abertas as $a) {
                if ((int) $a['responsavel_id'] === $pid) {
                    $erro = $a['papel'] === 'adicional' ? 'Esta pessoa já tem o acesso adicional neste ano.' : 'Esta pessoa já é o ' . mb_strtolower(self::PAPEIS[$a['papel']]) . ' neste ano: já acompanha o aluno.';
                    $T::falhar($erro);
                }
            }
            $lim = self::adicionalLimite();
            $usados = count(array_filter($abertas, static fn($a) => $a['papel'] === 'adicional'));
            if ($lim > 0 && $usados >= $lim) {
                $erro = "O limite de acessos adicionais por aluno ({$lim}) já foi atingido neste ano.";
                $T::falhar($erro);
            }
            $ref = mb_substr(trim((string) $referencia), 0, 190);
            try {
                self::pdo()->prepare('INSERT INTO esc_responsavel_designacoes (aluno_id, ano, responsavel_id, papel, inicio, motivo, referencia, origem, criado_por, criado_em) VALUES (?, ?, ?, \'adicional\', ?, ?, ?, \'tela\', ?, NOW())')
                    ->execute([$aluno, $ano, $pid, $data, mb_substr($motivo, 0, 255), $ref !== '' ? $ref : null, $por ?? self::usuario()]);
            } catch (\PDOException $e) {
                $erro = 'Nada foi gravado: ' . self::traduzirErro($e->getMessage());
                $T::falhar($erro);
            }
            self::auditar('liberar', $pid, $aluno, $ano, null, ['papel' => 'adicional', 'responsavel_id' => $pid, 'inicio' => $data, 'referencia' => $ref], $motivo, 'tela', $por);
            return true;
        });
        return $ok ? true : ($erro !== '' ? $erro : 'Nada foi gravado: ' . self::traduzirErro($T::ultimoErro()));
    }

    /**
     * Encerra uma designação (o papel sai; a liberação adicional acaba), com a data e o motivo. O acesso cai na
     * requisição seguinte (a barreira lê a designação a cada tela).
     * @return true|string
     */
    public static function encerrar(int $id, ?string $data, ?string $motivo, string $origem = 'tela', ?int $por = null)
    {
        $d = self::um('SELECT * FROM esc_responsavel_designacoes WHERE id = ?', [$id]);
        if (!$d || $d['fim'] !== null) {
            return 'Esta designação não está em vigor.';
        }
        $motivo = trim((string) $motivo);
        if (mb_strlen($motivo) < 3) {
            return 'Informe o motivo do encerramento.';
        }
        $data = self::data($data, (int) $d['ano']);
        if (is_array($data)) {
            return $data[0];
        }
        if ($data < $d['inicio']) {
            return 'A data do encerramento não pode ser antes do início (' . date('d/m/Y', strtotime($d['inicio'])) . ').';
        }
        self::pdo()->prepare('UPDATE esc_responsavel_designacoes SET fim = ?, motivo_fim = ?, encerrado_por = ?, encerrado_em = NOW() WHERE id = ? AND fim IS NULL')
            ->execute([$data, mb_substr($motivo, 0, 255), $por ?? self::usuario(), $id]);
        self::auditar($d['papel'] === 'adicional' ? 'encerrar_liberacao' : 'encerrar', (int) $d['responsavel_id'], (int) $d['aluno_id'], (int) $d['ano'],
            ['papel' => $d['papel'], 'inicio' => $d['inicio'], 'fim' => null], ['papel' => $d['papel'], 'fim' => $data], $motivo, $origem, $por);
        return true;
    }

    /**
     * "Copiar do ano anterior": o financeiro e o titular em vigor no ano anterior, para conferir (nada é gravado
     * aqui). Os acessos adicionais não vêm: valem para o ano em que foram liberados.
     */
    public static function copiarPrevia(int $aluno, int $ano): array
    {
        $previa = [];
        foreach (['financeiro', 'titular'] as $papel) {
            $antes = self::emVigor($aluno, $ano - 1, $papel);
            $agora = self::emVigor($aluno, $ano, $papel);
            $previa[$papel] = ['antes' => $antes, 'agora' => $agora, 'copia' => $antes && !$agora];
        }
        return $previa;
    }

    /** Grava a cópia conferida (só os papéis ainda vazios no ano). @return int|string quantos foram copiados */
    public static function copiar(int $aluno, int $ano)
    {
        $n = 0;
        foreach (self::copiarPrevia($aluno, $ano) as $papel => $x) {
            if (!$x['copia']) {
                continue;
            }
            $r = self::designar($aluno, $ano, $papel, ['id' => (int) $x['antes']['responsavel_id'], 'parentesco' => (string) ($x['antes']['parentesco'] ?? '')], self::inicioDoAno($ano), null, 'tela');
            if ($r !== true) {
                return $r;
            }
            $n++;
        }
        return $n;
    }

    /** A data de início de uma designação feita hoje para um ano: hoje, se for o ano; senão, o primeiro dia do ano. */
    public static function inicioDoAno(int $ano): string
    {
        return (int) date('Y') === $ano ? self::hoje() : sprintf('%04d-01-01', $ano);
    }

    /* ------------------------------------------------------------------ os contatos */

    public static function contatos(int $aluno): array
    {
        return self::todos('SELECT * FROM esc_responsavel_contatos WHERE aluno_id = ? ORDER BY id', [$aluno]);
    }

    /** @return true|string */
    public static function contatoAdicionar(int $aluno, array $d)
    {
        if (!self::aluno($aluno)) {
            return 'Aluno não encontrado.';
        }
        $nome = trim((string) ($d['nome'] ?? ''));
        $tel = self::digitos($d['telefone'] ?? '');
        $par = trim((string) ($d['parentesco'] ?? ''));
        if (mb_strlen($nome) < 3 || $par === '' || strlen($tel) < 10 || strlen($tel) > 11) {
            return 'O contato pede o nome, o parentesco e o telefone com o DDD.';
        }
        self::pdo()->prepare('INSERT INTO esc_responsavel_contatos (aluno_id, nome, parentesco, telefone, observacao, criado_por, criado_em) VALUES (?, ?, ?, ?, ?, ?, NOW())')
            ->execute([$aluno, mb_substr($nome, 0, 150), mb_substr($par, 0, 40), $tel, mb_substr(trim((string) ($d['observacao'] ?? '')), 0, 255) ?: null, self::usuario()]);
        self::auditar('contato', null, $aluno, null, null, ['nome' => $nome, 'parentesco' => $par], null);
        return true;
    }

    public static function contatoRetirar(int $aluno, int $id): void
    {
        $c = self::um('SELECT * FROM esc_responsavel_contatos WHERE id = ? AND aluno_id = ?', [$id, $aluno]);
        if ($c) {
            self::pdo()->prepare('DELETE FROM esc_responsavel_contatos WHERE id = ?')->execute([$id]);
            self::auditar('contato_retirado', null, $aluno, null, ['nome' => $c['nome'], 'parentesco' => $c['parentesco']], null, null);
        }
    }

    /* ------------------------------------------------------------------ apoio */

    /** A pessoa escolhida (id) ou a nova (os campos): a que já existe pelo e-mail ou CPF é usada, não duplicada. */
    private static function pessoaDe(array $pessoa, string $origem, ?int $por, string &$erro): int
    {
        if (!empty($pessoa['id'])) {
            if (!self::pessoa((int) $pessoa['id'])) {
                $erro = 'Responsável não encontrado.';
                return 0;
            }
            return (int) $pessoa['id'];
        }
        $r = self::criar($pessoa, $origem, $por);
        if (is_int($r)) {
            return $r;
        }
        if (is_array($r)) {
            return (int) $r['existe']['id']; // a mesma pessoa (pelo e-mail ou pelo CPF): não nasce duas vezes
        }
        $erro = $r;
        return 0;
    }

    /** A data informada (dd/mm/aaaa ou aaaa-mm-dd), ou a de hoje (no ano corrente) / o 1º dia (em outro ano). */
    private static function data(?string $data, int $ano)
    {
        $data = trim((string) $data);
        if ($data === '') {
            return self::inicioDoAno($ano);
        }
        if (preg_match('#^(\d{2})/(\d{2})/(\d{4})$#', $data, $m)) {
            $data = "{$m[3]}-{$m[2]}-{$m[1]}";
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data) || !checkdate((int) substr($data, 5, 2), (int) substr($data, 8, 2), (int) substr($data, 0, 4))) {
            return ['A data não é válida.'];
        }
        return $data;
    }

    private static function traduzirErro(string $e): string
    {
        if (str_contains($e, 'um_financeiro_um_titular')) {
            return 'já há um responsável deste papel em vigor neste ano (um só por vez).';
        }
        if (str_contains($e, 'uma_aberta_por_pessoa')) {
            return 'esta pessoa já tem este papel em vigor neste ano.';
        }
        return $e !== '' ? $e : 'erro ao gravar';
    }
}
