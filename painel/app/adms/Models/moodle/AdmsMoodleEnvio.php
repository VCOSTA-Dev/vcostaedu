<?php

namespace App\adms\Models\moodle;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * O ENVIO DE UM ITEM DA FILA ao Moodle (Onda 3, Etapa 8). processar() devolve [situação, código]:
 * situação 'enviado' | 'aviso' | 'falhou' | 'pendente'; o código é o do glossário (VcMoodle::GLOSSARIO).
 *
 * Como cada coisa é identificada no Moodle:
 *   - aluno: procurado pelo idnumber (o RA), depois pelo nome de usuário, depois pelo e-mail; achou, VINCULA (não
 *     duplica). Ao criar: o nome de usuário e o idnumber a partir do RA, o nome e o sobrenome (o primeiro nome e o resto),
 *     o e-mail, a autenticação manual, uma senha aleatória que ninguém guarda (a entrada é pelo link). Sem e-mail, não vai.
 *   - professor: procurado pelo idnumber e pelo e-mail; criado como o aluno.
 *   - oferta: o idMoodle dela; sem ele, procurada pelo idnumber; com "criar", criada na categoria escolhida.
 * O prefixo (parâmetro moodle.prefixo) vai no começo do idnumber e do nome de usuário — as provas usam "prova-".
 */
class AdmsMoodleEnvio
{
    private \PDO $pdo;
    private string $prefixo;

    public function __construct(private \VcMoodle $m, private array $conexao)
    {
        $this->pdo = AdmsMoodle::pdo();
        $this->prefixo = AdmsMoodle::parametro('moodle.prefixo', '');
    }

    /** @var string[] avisos que o envio deixa (a conta suspensa ligada): viram "enviado com aviso" */
    private array $avisos = [];
    /** o e-mail (minúsculo) => os ids das contas do Moodle com ele, de local_vcostaedu_find_users_by_email */
    private static array $porEmail = [];

    public function processar(array $item): array
    {
        $a = $item['aluno_id'] !== null ? (int) $item['aluno_id'] : 0;
        $o = $item['oferta_id'] !== null ? (int) $item['oferta_id'] : 0;
        $this->avisos = [];
        try {
            $r = $this->despachar($item['tipo'], $a, $o);
            /* a conta suspensa ligada: o envio foi, mas a pessoa não entra até a reativação — o operador é avisado */
            return ($r[0] === 'enviado' && $this->avisos) ? ['aviso', $this->avisos[0]] : $r;
        } catch (FalhaMoodle $f) {
            /* a recusa: o código do glossário, a mensagem que o próprio Moodle devolveu (quando há) e, na pendência de
               várias contas, as contas para o operador escolher */
            return ['falhou', $f->codigo, $f->getMessage() !== $f->codigo ? $f->getMessage() : '', $f->contas];
        }
    }

    private function despachar(string $tipo, int $a, int $o): array
    {
        switch ($tipo) {
            case 'aluno':
                $this->aluno($a, true);
                return ['enviado', ''];
            case 'oferta':
                $this->oferta($o);
                $this->atualizarNome($o);
                return ['enviado', ''];
            case 'enturmar':
                return $this->enturmar($a, $o);
            case 'desenturmar':
                return $this->desenturmar($a, $o);
            case 'situacao':
                return $this->situacao($a);
            case 'professor':
                return $this->professor($o);
        }
        return ['falhou', 'erro_interno'];
    }

    /**
     * A busca por e-mail (local_vcostaedu_find_users_by_email): de 1 a 100 e-mails numa chamada; para cada um, os ids
     * das contas que o têm. No lote, os e-mails vão em grupos de até 100 (prepararEmails); um envio avulso pergunta o
     * dele. Devolve os ids (vazio = nenhuma conta).
     */
    public function idsDoEmail(string $email, string $ref): array
    {
        $k = mb_strtolower(trim($email));
        if (!array_key_exists($k, self::$porEmail)) {
            $this->buscarEmails([$email], $ref);
        }
        return self::$porEmail[$k] ?? [];
    }

    /** Busca os e-mails em grupos de até 100 por chamada (nunca um por chamada no lote). */
    public function buscarEmails(array $emails, string $ref = 'lote'): void
    {
        $falta = [];
        foreach ($emails as $e) {
            $k = mb_strtolower(trim((string) $e));
            if ($k !== '' && filter_var($k, FILTER_VALIDATE_EMAIL) && !array_key_exists($k, self::$porEmail)) {
                $falta[$k] = $k;
            }
        }
        foreach (array_chunk(array_values($falta), 100) as $grupo) {
            $d = $this->chamar('local_vcostaedu_find_users_by_email', ['emails' => $grupo], $ref);
            foreach ($d as $linha) {
                self::$porEmail[mb_strtolower(trim((string) ($linha['email'] ?? '')))] = array_map('intval', (array) ($linha['userids'] ?? []));
            }
            foreach ($grupo as $k) {
                self::$porEmail[$k] ??= [];
            }
        }
    }

    /**
     * A conta pelo e-mail: nenhuma → null; uma → o registro dela (para o nome de usuário e a suspensão); mais de uma →
     * a pendência (o operador escolhe em Envios ao Moodle).
     */
    private function contaPeloEmail(string $email, string $ref): ?array
    {
        if ($email === '') {
            return null;
        }
        $ids = $this->idsDoEmail($email, $ref);
        if (count($ids) > 1) {
            throw new FalhaMoodle('email_varias_contas', '', $ids);
        }
        if (!$ids) {
            return null;
        }
        return $this->achar('id', (string) $ids[0], $ref);
    }

    /** Ligou uma conta que está suspensa no Moodle: o operador é avisado. */
    private function conferirSuspensa(?array $conta): void
    {
        if ($conta && !empty($conta['suspended'])) {
            $this->avisos[] = 'conta_suspensa';
        }
    }

    /** O operador escolheu a conta (a pendência de várias contas com o mesmo e-mail): o aluno fica ligado a ela. */
    public function ligarConta(int $alunoId, int $moodleId): bool
    {
        $conta = $this->achar('id', (string) $moodleId, 'aluno ' . $alunoId);
        if (!$conta) {
            return false;
        }
        $this->gravarVinculo('aluno', $alunoId, $moodleId, (string) ($conta['username'] ?? ''));
        return true;
    }

    /**
     * A disciplina criada PELO VCOSTAEDU (o idnumber dela é o nosso) acompanha o nome da oferta (o nome completo e o
     * curto); a disciplina só LIGADA (a escolha "vincular", de outro dono) não é renomeada.
     */
    public function atualizarNome(int $ofertaId): void
    {
        $st = $this->pdo->prepare('SELECT o.id, o.materia, o.codtur, o.ano, o.idMoodle FROM rm_professores_materias o WHERE o.id = ?');
        $st->execute([$ofertaId]);
        $o = $st->fetch(\PDO::FETCH_ASSOC);
        if (!$o || (int) $o['idMoodle'] <= 0) {
            return;
        }
        $ref = 'oferta ' . $ofertaId;
        $d = $this->chamar('core_course_get_courses_by_field', ['field' => 'id', 'value' => (int) $o['idMoodle']], $ref);
        $c = $d['courses'][0] ?? null;
        if (!$c || (string) ($c['idnumber'] ?? '') !== $this->prefixo . 'oferta-' . $ofertaId) {
            return;
        }
        [$nome, $curto] = $this->nomesDaOferta($o);
        if ((string) $c['fullname'] !== $nome || (string) $c['shortname'] !== $curto) {
            $this->chamar('core_course_update_courses', ['courses' => [['id' => (int) $o['idMoodle'], 'fullname' => $nome, 'shortname' => $curto]]], $ref);
        }
    }

    /** O nome completo e o nome curto da disciplina no Moodle. */
    private function nomesDaOferta(array $o): array
    {
        return [trim((string) $o['materia']) . ' — ' . trim((string) $o['codtur']) . ' (' . (int) $o['ano'] . ')',
            mb_substr($this->prefixo . trim((string) $o['materia']), 0, 60) . ' · ' . trim((string) $o['codtur']) . ' · ' . (int) $o['id']];
    }

    /* --------------------------------------------------------------- utilidades ----------------------------------- */

    private function chamar(string $funcao, array $params, string $ref): array
    {
        $r = $this->m->chamar($funcao, $params, $ref);
        if (!$r['ok']) {
            throw new FalhaMoodle($r['codigo'] ?: 'erro', $r['mensagem']);
        }
        return is_array($r['dados']) ? $r['dados'] : [];
    }

    /** O nome de usuário que o Moodle aceita: minúsculas, letras, dígitos e . _ - @. */
    public static function usuarioMoodle(string $s): string
    {
        $s = strtolower(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s) ?: $s);
        return substr(preg_replace('/[^a-z0-9._@-]/', '', $s), 0, 100);
    }

    /** O nome e o sobrenome do Moodle: o primeiro nome e o resto (um nome só: o sobrenome repete o nome). */
    public static function partirNome(string $nome): array
    {
        $p = preg_split('/\s+/', trim($nome), 2);
        return [$p[0] !== '' ? $p[0] : 'Aluno', $p[1] ?? $p[0]];
    }

    /** Uma senha que passa na política padrão do Moodle e que ninguém guarda. */
    private static function senhaAleatoria(): string
    {
        return 'Vc' . bin2hex(random_bytes(12)) . '#9Z';
    }

    private function gravarVinculo(string $tipo, int $local, int $moodle, string $usuario = '', bool $criado = false): void
    {
        if ($tipo !== 'prof_oferta') {
            $this->pdo->prepare('DELETE FROM adms_moodle_vinculos WHERE tipo = ? AND local_id = ?')->execute([$tipo, $local]);
        }
        $this->pdo->prepare('INSERT IGNORE INTO adms_moodle_vinculos (tipo, local_id, moodle_id, usuario, criado) VALUES (?, ?, ?, ?, ?)')
            ->execute([$tipo, $local, $moodle, $usuario !== '' ? $usuario : null, $criado ? 1 : 0]);
    }

    private function papel(string $qual): int
    {
        return match ($qual) {
            'estudante' => (int) AdmsMoodle::parametro('moodle.papel_estudante', '5'),
            'edicao' => (int) AdmsMoodle::parametro('moodle.papel_professor_edicao', '3'),
            default => (int) AdmsMoodle::parametro('moodle.papel_professor', '4'),
        };
    }

    /** Procura um usuário no Moodle por um campo; devolve o registro ou null. */
    private function achar(string $campo, string $valor, string $ref): ?array
    {
        if ($valor === '') {
            return null;
        }
        $d = $this->chamar('core_user_get_users_by_field', ['field' => $campo, 'values' => [$valor]], $ref);
        return $d[0] ?? null;
    }

    /* --------------------------------------------------------------- o aluno -------------------------------------- */

    /** Garante o aluno no Moodle; devolve o id dele lá. Com $atualizar, manda o nome e o e-mail de novo. */
    public function aluno(int $alunoId, bool $atualizar = false): int
    {
        $st = $this->pdo->prepare('SELECT id, rm, nome, nome_social, email FROM col_usuarios WHERE id = ?');
        $st->execute([$alunoId]);
        $u = $st->fetch(\PDO::FETCH_ASSOC);
        if (!$u) {
            throw new FalhaMoodle('erro_interno');
        }
        $ref = 'aluno ' . $alunoId;
        $nome = trim((string) ($u['nome_social'] ?: $u['nome']));
        [$primeiro, $sobrenome] = self::partirNome($nome);
        /* Onda 8, Etapa 2 (D-68): o e-mail pelo modo de Conectar ao Moodle; o da escola é montado aqui e não vai ao cadastro */
        [$email, $semEmail] = AdmsMoodle::emailParaMoodle($alunoId, (string) $u['rm'], (string) $u['email']);
        $this->irmaosDoEmail($alunoId, (string) $u['email']);
        $idMoodle = AdmsMoodle::vinculo('aluno', $alunoId);
        if ($idMoodle) {
            if ($atualizar && $email !== '') {
                $dados = ['id' => $idMoodle, 'firstname' => $primeiro, 'lastname' => $sobrenome];
                /* o e-mail só na conta que o sistema criou: a que já existia no Moodle e foi ligada fica com o dela */
                if ($this->criadaPeloSistema($alunoId)) {
                    $dados['email'] = $email;
                }
                $this->chamar('core_user_update_users', ['users' => [$dados]], $ref);
                if (isset($dados['email'])) {
                    $this->marcarEmail($alunoId, $email, (string) $u['email']);
                }
            }
            return $idMoodle;
        }
        $idnumber = $this->prefixo . $u['rm'];
        $usuario = self::usuarioMoodle($this->prefixo . $u['rm']);
        $achado = $this->achar('idnumber', $idnumber, $ref) ?? $this->achar('username', $usuario, $ref) ?? $this->contaPeloEmail($email, $ref);
        if ($achado) {
            $this->conferirSuspensa($achado);
            $this->gravarVinculo('aluno', $alunoId, (int) $achado['id'], (string) ($achado['username'] ?? ''));
            return (int) $achado['id'];
        }
        if ($email === '') {
            throw new FalhaMoodle($semEmail !== '' ? $semEmail : 'sem_email');
        }
        $d = $this->chamar('core_user_create_users', ['users' => [[
            'username' => $usuario, 'password' => self::senhaAleatoria(), 'auth' => 'manual', 'idnumber' => $idnumber,
            'firstname' => $primeiro, 'lastname' => $sobrenome, 'email' => $email,
        ]]], $ref);
        $id = (int) ($d[0]['id'] ?? 0);
        if (!$id) {
            throw new FalhaMoodle('resposta_invalida');
        }
        $this->gravarVinculo('aluno', $alunoId, $id, $usuario, true);
        $this->marcarEmail($alunoId, $email, (string) $u['email']);
        return $id;
    }

    /** A conta foi com o e-mail do cadastro (1) ou com o da escola (0)? Só a marca: o endereço não é guardado. */
    private function marcarEmail(int $alunoId, string $enviado, string $doCadastro): void
    {
        $proprio = $doCadastro !== '' && mb_strtolower(trim($enviado)) === mb_strtolower(trim($doCadastro)) ? 1 : 0;
        $this->pdo->prepare("UPDATE adms_moodle_vinculos SET email_proprio = ? WHERE tipo = 'aluno' AND local_id = ?")->execute([$proprio, $alunoId]);
    }

    /**
     * O e-mail do cadastro ficou repetido (o irmão chegou com o e-mail da família): o outro aluno, que foi ao Moodle com esse
     * e-mail quando ele ainda era só dele, volta à fila — a conta que o sistema criou passa ao endereço que o modo der.
     */
    private function irmaosDoEmail(int $alunoId, string $doCadastro): void
    {
        $email = mb_strtolower(trim($doCadastro));
        if ($email === '' || AdmsMoodle::emailModo() === 'escola') {
            return;
        }
        /* só quem foi com o e-mail do cadastro (email_proprio): o que já foi com o da escola não volta — nada vai e vem */
        $st = $this->pdo->prepare("SELECT u.id FROM col_usuarios u JOIN adms_moodle_vinculos v ON v.tipo = 'aluno' AND v.local_id = u.id AND v.criado = 1 AND v.email_proprio = 1
                                    WHERE u.col_niveis_acesso_id = 1 AND u.id <> ? AND LOWER(TRIM(u.email)) = ?");
        $st->execute([$alunoId, $email]);
        foreach ($st->fetchAll(\PDO::FETCH_COLUMN) as $outro) {
            $chave = 'aluno:' . (int) $outro . ':';
            $ja = $this->pdo->prepare("SELECT COUNT(*) FROM adms_moodle_envios WHERE chave = ? AND situacao = 'pendente'");
            $ja->execute([$chave]);
            if (!(int) $ja->fetchColumn()) {
                AdmsMoodle::enfileirar('aluno', (int) $outro, null);
            }
        }
    }

    /** A conta do Moodle ligada ao aluno foi criada pelo sistema (e não só ligada a uma que já existia)? */
    private function criadaPeloSistema(int $alunoId): bool
    {
        $st = $this->pdo->prepare("SELECT criado FROM adms_moodle_vinculos WHERE tipo = 'aluno' AND local_id = ? ORDER BY id LIMIT 1");
        $st->execute([$alunoId]);
        return (int) $st->fetchColumn() === 1;
    }

    /** O nome de usuário do aluno no Moodle (para o link de entrada). */
    public function usuarioDoAluno(int $alunoId): string
    {
        $id = AdmsMoodle::vinculo('aluno', $alunoId);
        if (!$id) {
            return '';
        }
        $d = $this->chamar('core_user_get_users_by_field', ['field' => 'id', 'values' => [$id]], 'aluno ' . $alunoId);
        return (string) ($d[0]['username'] ?? '');
    }

    /* --------------------------------------------------------------- o professor ---------------------------------- */

    public function professorUsuario(int $usuarioId): int
    {
        $st = $this->pdo->prepare('SELECT id, nome, email FROM adms_usuarios WHERE id = ?');
        $st->execute([$usuarioId]);
        $u = $st->fetch(\PDO::FETCH_ASSOC);
        if (!$u) {
            throw new FalhaMoodle('erro_interno');
        }
        $ref = 'professor ' . $usuarioId;
        $id = AdmsMoodle::vinculo('professor', $usuarioId);
        if ($id) {
            return $id;
        }
        $email = trim((string) $u['email']);
        $idnumber = $this->prefixo . 'prof-' . $usuarioId;
        $achado = $this->achar('idnumber', $idnumber, $ref) ?? $this->contaPeloEmail($email, $ref);
        if ($achado) {
            $this->conferirSuspensa($achado);
            $this->gravarVinculo('professor', $usuarioId, (int) $achado['id'], (string) ($achado['username'] ?? ''));
            return (int) $achado['id'];
        }
        if ($email === '') {
            throw new FalhaMoodle('sem_email');
        }
        [$primeiro, $sobrenome] = self::partirNome((string) $u['nome']);
        $d = $this->chamar('core_user_create_users', ['users' => [[
            'username' => self::usuarioMoodle($this->prefixo . 'prof' . $usuarioId), 'password' => self::senhaAleatoria(), 'auth' => 'manual',
            'idnumber' => $idnumber, 'firstname' => $primeiro, 'lastname' => $sobrenome, 'email' => $email,
        ]]], $ref);
        $id = (int) ($d[0]['id'] ?? 0);
        if (!$id) {
            throw new FalhaMoodle('resposta_invalida');
        }
        $this->gravarVinculo('professor', $usuarioId, $id);
        return $id;
    }

    /* --------------------------------------------------------------- a oferta ------------------------------------- */

    /** Garante a disciplina ofertada no Moodle; devolve o id do curso lá (gravado no idMoodle da oferta). */
    public function oferta(int $ofertaId): int
    {
        $st = $this->pdo->prepare('SELECT o.id, o.materia, o.codtur, o.ano, o.idMoodle, c.nome curso FROM rm_professores_materias o LEFT JOIN rm_cursos c ON c.id = o.curso_id WHERE o.id = ?');
        $st->execute([$ofertaId]);
        $o = $st->fetch(\PDO::FETCH_ASSOC);
        if (!$o) {
            throw new FalhaMoodle('erro_interno');
        }
        if ((int) $o['idMoodle'] > 0) {
            return (int) $o['idMoodle'];
        }
        $ref = 'oferta ' . $ofertaId;
        $idnumber = $this->prefixo . 'oferta-' . $ofertaId;
        $d = $this->chamar('core_course_get_courses_by_field', ['field' => 'idnumber', 'value' => $idnumber], $ref);
        $id = (int) ($d['courses'][0]['id'] ?? 0);
        if (!$id) {
            if (($this->conexao['disciplina_modo'] ?? 'criar') !== 'criar') {
                throw new FalhaMoodle('sem_disciplina');
            }
            if (empty($this->conexao['categoria_id'])) {
                throw new FalhaMoodle('sem_categoria');
            }
            [$nome, $curto] = $this->nomesDaOferta($o);
            $c = $this->chamar('core_course_create_courses', ['courses' => [[
                'fullname' => $nome, 'shortname' => $curto, 'categoryid' => (int) $this->conexao['categoria_id'], 'idnumber' => $idnumber, 'visible' => 1,
            ]]], $ref);
            $id = (int) ($c[0]['id'] ?? 0);
            if (!$id) {
                throw new FalhaMoodle('resposta_invalida');
            }
        }
        $this->pdo->prepare('UPDATE rm_professores_materias SET idMoodle = ? WHERE id = ?')->execute([$id, $ofertaId]);
        return $id;
    }

    /* --------------------------------------------------------------- inscrições ----------------------------------- */

    /** Inscreve (ou muda a suspensão). "Message was not sent." = aviso, conferido em core_enrol_get_enrolled_users. */
    private function inscrever(int $usuario, int $curso, int $papel, int $suspenso, string $ref): array
    {
        $r = $this->m->chamar('enrol_manual_enrol_users', ['enrolments' => [['roleid' => $papel, 'userid' => $usuario, 'courseid' => $curso, 'suspend' => $suspenso]]], $ref);
        if ($r['ok'] && !$this->temAvisoDeEmail($r)) {
            return ['enviado', ''];
        }
        if ($this->temAvisoDeEmail($r)) {
            return $this->inscrito($usuario, $curso, $ref) ? ['aviso', 'aviso_email'] : ['falhou', 'aviso_email'];
        }
        throw new FalhaMoodle($r['codigo'] ?: 'erro', $r['mensagem']);
    }

    private function temAvisoDeEmail(array $r): bool
    {
        $txt = (string) $r['mensagem'] . ' ' . json_encode($r['dados']);
        return stripos($txt, 'Message was not sent') !== false;
    }

    private function inscrito(int $usuario, int $curso, string $ref): bool
    {
        $d = $this->chamar('core_enrol_get_enrolled_users', ['courseid' => $curso, 'options' => [['name' => 'onlyactive', 'value' => 0]]], $ref);
        foreach ($d as $u) {
            if ((int) ($u['id'] ?? 0) === $usuario) {
                return true;
            }
        }
        return false;
    }

    private function enturmar(int $alunoId, int $ofertaId): array
    {
        $ref = 'aluno ' . $alunoId . ', oferta ' . $ofertaId;
        $u = $this->aluno($alunoId);
        $c = $this->oferta($ofertaId);
        /* a matrícula trancada ou cancelada: a inscrição nasce como a escolha manda */
        $sit = $this->situacaoDaOferta($alunoId, $ofertaId);
        if (in_array($sit, [3, 4], true) && ($this->conexao['trancamento'] ?? 'suspender') === 'remover') {
            return ['enviado', ''];
        }
        $susp = in_array($sit, [3, 4], true) && ($this->conexao['trancamento'] ?? 'suspender') === 'suspender' ? 1 : 0;
        return $this->inscrever($u, $c, $this->papel('estudante'), $susp, $ref);
    }

    private function desenturmar(int $alunoId, int $ofertaId): array
    {
        $u = AdmsMoodle::vinculo('aluno', $alunoId);
        $st = $this->pdo->prepare('SELECT idMoodle FROM rm_professores_materias WHERE id = ?');
        $st->execute([$ofertaId]);
        $c = (int) $st->fetchColumn();
        if (!$u || !$c) {
            return ['enviado', '']; // nunca chegou ao Moodle: nada a tirar
        }
        $this->chamar('enrol_manual_unenrol_users', ['enrolments' => [['userid' => $u, 'courseid' => $c]]], 'aluno ' . $alunoId . ', oferta ' . $ofertaId);
        return ['enviado', ''];
    }

    /** A situação da matrícula do aluno no curso da oferta (1 cursando, 3 trancado, 4 cancelado…), ou 0. */
    private function situacaoDaOferta(int $alunoId, int $ofertaId): int
    {
        $st = $this->pdo->prepare('SELECT m.situacao_id FROM rm_matriculas_curso m JOIN rm_professores_materias o ON o.curso_id = m.curso_id
                                    WHERE m.usuario_id = ? AND o.id = ? ORDER BY (m.situacao_id = 1) DESC, m.id DESC LIMIT 1');
        $st->execute([$alunoId, $ofertaId]);
        return (int) $st->fetchColumn();
    }

    /**
     * O trancamento, o cancelamento e a reativação: em cada disciplina do Moodle em que o aluno está (as ofertas dele já
     * enviadas), a inscrição suspensa, removida ou deixada, conforme a escolha; reativada, a inscrição volta.
     */
    private function situacao(int $alunoId): array
    {
        $u = AdmsMoodle::vinculo('aluno', $alunoId);
        if (!$u) {
            return ['enviado', ''];
        }
        $escolha = $this->conexao['trancamento'] ?? 'suspender';
        $st = $this->pdo->prepare('SELECT DISTINCT o.id, o.idMoodle FROM portal_disciplinas_alunos a JOIN col_usuarios c ON c.rm = a.rm
                                     JOIN rm_professores_materias o ON o.id = a.rm_prof_mat_id WHERE c.id = ? AND o.idMoodle > 0');
        $st->execute([$alunoId]);
        $res = ['enviado', ''];
        foreach ($st->fetchAll(\PDO::FETCH_ASSOC) as $o) {
            $sit = $this->situacaoDaOferta($alunoId, (int) $o['id']);
            $ref = 'aluno ' . $alunoId . ', oferta ' . $o['id'];
            if (in_array($sit, [3, 4], true)) {
                if ($escolha === 'suspender') {
                    $r = $this->inscrever($u, (int) $o['idMoodle'], $this->papel('estudante'), 1, $ref);
                } elseif ($escolha === 'remover') {
                    $this->chamar('enrol_manual_unenrol_users', ['enrolments' => [['userid' => $u, 'courseid' => (int) $o['idMoodle']]]], $ref);
                    $r = ['enviado', ''];
                } else {
                    $r = ['enviado', ''];
                }
            } else {
                $r = $this->inscrever($u, (int) $o['idMoodle'], $this->papel('estudante'), 0, $ref);
            }
            if ($r[0] === 'aviso') {
                $res = $r;
            }
        }
        return $res;
    }

    /**
     * O professor da disciplina: os professores ativos da oferta inscritos com o papel escolhido; o professor que saiu
     * da oferta (e que NÓS inscrevemos) sai da disciplina no Moodle. O coordenador do curso não é enviado.
     */
    private function professor(int $ofertaId): array
    {
        if (empty($this->conexao['professor_enviar'])) {
            return ['enviado', ''];
        }
        $c = $this->oferta($ofertaId);
        $papel = $this->papel(($this->conexao['professor_papel'] ?? 'edicao') === 'edicao' ? 'edicao' : 'sem_edicao');
        $st = $this->pdo->prepare('SELECT DISTINCT usuario_id FROM rm_professores_mat_liga WHERE rm_prof_mat_id = ? AND acesso = 1 AND situacao = 1');
        $st->execute([$ofertaId]);
        $agora = [];
        $res = ['enviado', ''];
        foreach ($st->fetchAll(\PDO::FETCH_COLUMN) as $usuarioId) {
            $u = $this->professorUsuario((int) $usuarioId);
            $agora[] = $u;
            $r = $this->inscrever($u, $c, $papel, 0, 'professor ' . $usuarioId . ', oferta ' . $ofertaId);
            $this->gravarVinculo('prof_oferta', $ofertaId, $u);
            if ($r[0] === 'aviso') {
                $res = $r;
            }
        }
        $st = $this->pdo->prepare("SELECT moodle_id FROM adms_moodle_vinculos WHERE tipo = 'prof_oferta' AND local_id = ?");
        $st->execute([$ofertaId]);
        foreach (array_diff(array_map('intval', $st->fetchAll(\PDO::FETCH_COLUMN)), $agora) as $saiu) {
            $this->chamar('enrol_manual_unenrol_users', ['enrolments' => [['userid' => $saiu, 'courseid' => $c]]], 'oferta ' . $ofertaId);
            $this->pdo->prepare("DELETE FROM adms_moodle_vinculos WHERE tipo = 'prof_oferta' AND local_id = ? AND moodle_id = ?")->execute([$ofertaId, $saiu]);
        }
        return $res;
    }
}
