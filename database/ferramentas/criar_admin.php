<?php
/*
 * Administrador do painel (nível 1 — o papel Administrador).
 *
 *   Criar:     php database/ferramentas/criar_admin.php <usuario> <email> ["Nome"]
 *   Redefinir: php database/ferramentas/criar_admin.php --redefinir <usuario>
 *
 * A senha é gerada aqui e mostrada UMA vez, no terminal. Ela não é gravada em
 * arquivo nenhum: só o hash vai para o banco.
 * - Criar: se o login ou o e-mail já existir, nada é alterado.
 * - Redefinir: só para um usuário que existe E é nível 1; qualquer outro caso é
 *   recusado sem alterar nada. A senha antiga deixa de valer na hora.
 */

require __DIR__ . '/comum.php';
require __DIR__ . '/admin_comum.php'; // a mesma criação que o instalador usa

$pdo = vcostaedu_pdo();

if (($argv[1] ?? '') === '--redefinir') {
    $usuario = $argv[2] ?? '';
    if ($usuario === '') {
        fwrite(STDERR, "Uso: php database/ferramentas/criar_admin.php --redefinir <usuario>\n");
        exit(1);
    }

    $ler = $pdo->prepare('SELECT id, adms_niveis_acesso_id FROM adms_usuarios WHERE usuario = :u LIMIT 1');
    $ler->execute(['u' => $usuario]);
    $conta = $ler->fetch();
    if (!$conta) {
        fwrite(STDERR, "Usuário \"{$usuario}\" não existe. Nada foi alterado.\n");
        exit(1);
    }
    if ((int) $conta['adms_niveis_acesso_id'] !== 1) {
        fwrite(STDERR, "Usuário \"{$usuario}\" não é administrador (nível 1). Nada foi alterado.\n");
        exit(1);
    }

    $senha = vcostaedu_senha_aleatoria();
    $up = $pdo->prepare('UPDATE adms_usuarios SET senha = :h, recuperar_senha = NULL, modified = NOW() WHERE id = :id');
    $up->execute(['h' => password_hash($senha, PASSWORD_DEFAULT), 'id' => $conta['id']]);

    echo "Senha do administrador redefinida (id {$conta['id']}).\n";
    echo "  usuário: {$usuario}\n";
    echo "  senha:   {$senha}\n";
    echo "Anote agora: a senha não é mostrada de novo nem gravada em lugar nenhum. A antiga já não vale.\n";
    exit(0);
}

$usuario = $argv[1] ?? '';
$email   = $argv[2] ?? '';
$nome    = $argv[3] ?? 'Administrador VCOSTAEDU';

if ($usuario === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Uso: php database/ferramentas/criar_admin.php <usuario> <email> [\"Nome\"]\n");
    fwrite(STDERR, "     php database/ferramentas/criar_admin.php --redefinir <usuario>\n");
    exit(1);
}

$senha = vcostaedu_senha_aleatoria();
$criado = vcostaedu_criar_admin($pdo, $usuario, $email, $nome, $senha);
if (!$criado['ok']) {
    fwrite(STDERR, $criado['erro'] . "\n");
    exit(1);
}

echo "Administrador criado (id " . $criado['id'] . ").\n";
echo "  usuário: {$usuario}\n";
echo "  senha:   {$senha}\n";
echo "Anote agora: a senha não é mostrada de novo nem gravada em lugar nenhum.\n";
