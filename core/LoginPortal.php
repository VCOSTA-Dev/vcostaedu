<?php
/*
 * O LOGIN DO PORTAL NA ESCOLA (Onda 8, Etapa 1; D-67) — a regra, num lugar só, para o portal (a entrada, o convite, o
 * "esqueci a senha") e para o painel (o cadastro do aluno e o do responsável, pela tela, pela planilha e pela
 * interface de integração):
 *   - cada conta tem um LOGIN próprio, separado dos dados de cadastro; a entrada procura só nele;
 *   - o login do aluno é o número de matrícula (col_usuarios.rm); o e-mail e o CPF do cadastro do aluno não entram;
 *   - o login do responsável é o CPF ou o e-mail dele, escolhido quando a conta é criada (no convite), guardado em
 *     esc_responsaveis.login (o CPF só com os dígitos; o e-mail em minúsculas);
 *   - o que foi digitado e é um CPF válido é tratado como CPF de responsável; o resto, como login;
 *   - dois logins iguais não existem, entre os alunos e os responsáveis juntos; a matrícula não pode ser um CPF válido.
 * No ensino superior e no curso livre nada disto vale: o aluno entra pelo e-mail do cadastro ou pela matrícula (o RA,
 * ou o CPF, se a instituição escolheu o CPF); o mesmo e-mail em mais de um cadastro pede a matrícula (D-107).
 */

final class VcLoginPortal
{
    /** O CPF com os verificadores certos (só os dígitos; não aceita onze números iguais). */
    public static function cpfValido(string $digitos): bool
    {
        if (!preg_match('/^\d{11}$/', $digitos) || preg_match('/^(\d)\1{10}$/', $digitos)) {
            return false;
        }
        for ($t = 9; $t < 11; $t++) {
            $soma = 0;
            for ($i = 0; $i < $t; $i++) {
                $soma += (int) $digitos[$i] * (($t + 1) - $i);
            }
            $d = ((10 * $soma) % 11) % 10;
            if ((int) $digitos[$t] !== $d) {
                return false;
            }
        }
        return true;
    }

    /** O que foi digitado, se é um CPF válido, com ou sem a pontuação (só dígitos, pontos, traço e espaços): os 11 dígitos. */
    public static function comoCpf(string $digitado): ?string
    {
        $t = trim($digitado);
        if ($t === '' || !preg_match('/^[\d.\-\s]+$/', $t)) {
            return null;
        }
        $d = preg_replace('/\D/', '', $t);
        return self::cpfValido($d) ? $d : null;
    }

    /** O login na forma guardada: o CPF só com os dígitos; o resto sem os espaços das pontas e em minúsculas. */
    public static function normalizar(string $digitado): string
    {
        return self::comoCpf($digitado) ?? mb_strtolower(trim($digitado), 'UTF-8');
    }

    /**
     * Quem já usa este login, fora a própria conta: ['tipo' => 'aluno'|'responsavel', 'nome' => …] ou null.
     * O aluno, pela matrícula (só quando o login é número); o responsável, pelo login guardado.
     */
    public static function emUso(\PDO $pdo, string $login, int $excetoResponsavel = 0, int $excetoAluno = 0): ?array
    {
        $login = self::normalizar($login);
        if ($login === '') {
            return null;
        }
        if (self::temColuna($pdo)) {
            $st = $pdo->prepare('SELECT nome FROM esc_responsaveis WHERE login = ? AND id <> ? LIMIT 1');
            $st->execute([$login, $excetoResponsavel]);
            if (($nome = $st->fetchColumn()) !== false) {
                return ['tipo' => 'responsavel', 'nome' => (string) $nome];
            }
        }
        if (preg_match('/^\d{1,11}$/', $login)) {
            $st = $pdo->prepare('SELECT nome FROM col_usuarios WHERE rm = ? AND col_niveis_acesso_id = 1 AND id <> ? LIMIT 1');
            $st->execute([(int) $login, $excetoAluno]);
            if (($nome = $st->fetchColumn()) !== false) {
                return ['tipo' => 'aluno', 'nome' => (string) $nome];
            }
        }
        return null;
    }

    /** A recusa do login em uso, com o que fazer. */
    public static function mensagemEmUso(array $quem, string $login): string
    {
        return $quem['tipo'] === 'aluno'
            ? "\"{$login}\" já é o login (a matrícula) de um aluno no portal. Use outro."
            : "\"{$login}\" já é o login de outro responsável no portal ({$quem['nome']}). Use o outro identificador da pessoa ou, se o cadastro dela mudou, troque antes o login dela na ficha.";
    }

    /**
     * As escolhas de login de um responsável: o e-mail e o CPF do cadastro, cada um com a marca de livre ou em uso.
     * @return array<int, array{tipo: string, valor: string, rotulo: string, livre: bool, quem: ?array}>
     */
    public static function opcoes(\PDO $pdo, array $pessoa): array
    {
        $o = [];
        $email = mb_strtolower(trim((string) ($pessoa['email'] ?? '')), 'UTF-8');
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $q = self::emUso($pdo, $email, (int) ($pessoa['id'] ?? 0));
            $o[] = ['tipo' => 'email', 'valor' => $email, 'rotulo' => 'o e-mail ' . $email, 'livre' => $q === null, 'quem' => $q];
        }
        $cpf = preg_replace('/\D/', '', (string) ($pessoa['cpf'] ?? ''));
        if (self::cpfValido($cpf)) {
            $q = self::emUso($pdo, $cpf, (int) ($pessoa['id'] ?? 0));
            $o[] = ['tipo' => 'cpf', 'valor' => $cpf, 'rotulo' => 'o CPF ' . self::cpfFormatado($cpf), 'livre' => $q === null, 'quem' => $q];
        }
        return $o;
    }

    /** O login para mostrar: o CPF com a pontuação; o e-mail como está. */
    public static function mostrar(?string $login): string
    {
        $login = (string) $login;
        return preg_match('/^\d{11}$/', $login) ? self::cpfFormatado($login) : $login;
    }

    public static function cpfFormatado(string $cpf): string
    {
        return preg_match('/^\d{11}$/', $cpf) ? substr($cpf, 0, 3) . '.' . substr($cpf, 3, 3) . '.' . substr($cpf, 6, 3) . '-' . substr($cpf, 9) : $cpf;
    }

    /** A coluna do login existe (a etapa do banco 0044 aplicada). */
    public static function temColuna(\PDO $pdo): bool
    {
        static $tem = null;
        if ($tem === null) {
            try {
                $tem = (bool) $pdo->query("SHOW COLUMNS FROM esc_responsaveis LIKE 'login'")->fetch();
            } catch (\Throwable $e) {
                $tem = false;
            }
        }
        return $tem;
    }

    /**
     * Fechamento (D-107): no ensino superior e no curso livre, o aluno entra também pelo E-MAIL do cadastro. Os alunos
     * com esse e-mail (sem maiúsculas nem espaços nas pontas), qualquer situação: o bloqueado recebe o aviso de sempre.
     * Na escola, nunca: lá o aluno entra só pelo RM.
     * @return list<int> os ids (no máximo 10)
     */
    public static function alunosPorEmail(\PDO $pdo, string $digitado): array
    {
        $email = mb_strtolower(trim($digitado), 'UTF-8');
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [];
        }
        $st = $pdo->prepare('SELECT id FROM col_usuarios WHERE col_niveis_acesso_id = 1 AND LOWER(TRIM(email)) = ? ORDER BY id LIMIT 10');
        $st->execute([$email]);
        return array_map('intval', $st->fetchAll(\PDO::FETCH_COLUMN));
    }
}
