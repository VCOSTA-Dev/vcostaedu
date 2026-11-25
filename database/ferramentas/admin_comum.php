<?php
/*
 * A criação do administrador do painel (nível 1 — o papel Administrador), num lugar só.
 *
 * Usada pelo database/ferramentas/criar_admin.php (linha de comando, senha gerada e mostrada uma vez) e pelo
 * instalador (instalar/, senha escolhida na tela). As duas portas gravam pela MESMA função: as mesmas recusas,
 * o mesmo INSERT, o mesmo nível. Só define funções; não lê configuração nem imprime nada.
 */

if (!function_exists('vcostaedu_criar_admin')) {
    /**
     * Cria o administrador. Nada é alterado se o login ou o e-mail já existir.
     *
     * @return array{ok:bool, id:int, erro:string}
     */
    function vcostaedu_criar_admin(PDO $pdo, string $usuario, string $email, string $nome, string $senha): array
    {
        $usuario = trim($usuario);
        $email = trim($email);
        $nome = trim($nome) !== '' ? trim($nome) : 'Administrador VCOSTAEDU';

        if ($usuario === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['ok' => false, 'id' => 0, 'erro' => 'Informe o nome de usuário e um e-mail válido.'];
        }
        if ($senha === '') {
            return ['ok' => false, 'id' => 0, 'erro' => 'A senha não pode ficar vazia.'];
        }

        $existe = $pdo->prepare('SELECT id FROM adms_usuarios WHERE usuario = :u OR email = :e LIMIT 1');
        $existe->execute(['u' => $usuario, 'e' => $email]);
        if ($existe->fetch()) {
            return ['ok' => false, 'id' => 0, 'erro' => 'Já existe um usuário com esse login ou e-mail. Nada foi alterado.'];
        }

        $ins = $pdo->prepare(
            'INSERT INTO adms_usuarios (nome, genero_id, usuario, senha, email, adms_niveis_acesso_id, adms_sits_usuario_id, usuario_cre, created)
             VALUES (:nome, 3, :usuario, :hash, :email, 1, 1, 0, NOW())'
        );
        $ins->execute([
            'nome'    => $nome,
            'usuario' => $usuario,
            'hash'    => password_hash($senha, PASSWORD_DEFAULT),
            'email'   => $email,
        ]);

        return ['ok' => true, 'id' => (int) $pdo->lastInsertId(), 'erro' => ''];
    }
}
