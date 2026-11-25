<?php
/*
 * 1.18.3 (D-500) — Põe um usuário do painel no nível 1, o Administrador. Onda 2: o nome era promover_administrador.php;
 * no produto aberto não há administrador do fornecedor — o 1 é o Administrador da instalação.
 *
 *   php database/ferramentas/promover_administrador.php --usuario=LOGIN          (pergunta antes: digite SIM)
 *   php database/ferramentas/promover_administrador.php --usuario=LOGIN --sim    (sem a pergunta)
 *
 * É o ÚNICO jeito previsto de mudar alguém para o nível 1: a atualização 1.18.3 não mexe em usuário nenhum, e a tela
 * não deixa ninguém atribuir um nível igual ou acima do seu (AdmsHierarquia).
 * - Login que não existe: recusa, nada alterado.
 * - Já é do nível 1: avisa, nada alterado.
 * - Só muda o nível (não muda a senha nem a situação). Grava em adms_logs quem era e o que virou.
 */

require __DIR__ . '/comum.php';

$opcoes = getopt('', ['usuario:', 'sim']);
$login = trim((string) ($opcoes['usuario'] ?? ''));
if ($login === '') {
    fwrite(STDERR, "Uso: php database/ferramentas/promover_administrador.php --usuario=LOGIN [--sim]\n");
    exit(1);
}

$pdo = vcostaedu_pdo();

$ler = $pdo->prepare('SELECT u.id, u.nome, u.adms_niveis_acesso_id AS nivel, u.adms_sits_usuario_id AS situacao, n.nome AS nivel_nome
                        FROM adms_usuarios u LEFT JOIN adms_niveis_acessos n ON n.id = u.adms_niveis_acesso_id
                       WHERE u.usuario = :u LIMIT 1');
$ler->execute(['u' => $login]);
$conta = $ler->fetch();
if (!$conta) {
    fwrite(STDERR, "Usuário \"{$login}\" não existe no painel. Nada foi alterado.\n");
    exit(1);
}

$super = $pdo->query('SELECT nome FROM adms_niveis_acessos WHERE id = 1')->fetchColumn();
if ($super === false) {
    fwrite(STDERR, "O nível 1 não existe nesta base (instale pelo instalador ou rode php bin/migrar.php aplicar). Nada foi alterado.\n");
    exit(1);
}

if ((int) $conta['nivel'] === 1) {
    echo "\"{$login}\" (id {$conta['id']}) já é do nível 1, {$super}. Nada foi alterado.\n";
    exit(0);
}

echo "Usuário: {$login} (id {$conta['id']}, {$conta['nome']})\n";
echo "Nível atual: {$conta['nivel']} — " . ($conta['nivel_nome'] ?? '?') . "\n";
echo "Novo nível:  1 — {$super} (o Administrador: tudo, inclusive Níveis de acesso e a manutenção)\n";
if ((int) $conta['situacao'] !== 1) {
    echo "Atenção: a situação do usuário não é Ativo ({$conta['situacao']}); ela NÃO é alterada aqui.\n";
}

if (!isset($opcoes['sim'])) {
    echo "Confirma? Digite SIM: ";
    $resposta = trim((string) fgets(STDIN));
    if ($resposta !== 'SIM') {
        echo "Cancelado. Nada foi alterado.\n";
        exit(1);
    }
}

$pdo->beginTransaction();
try {
    $up = $pdo->prepare('UPDATE adms_usuarios SET adms_niveis_acesso_id = 1, usuario_mod = 0, modified = NOW() WHERE id = :id AND adms_niveis_acesso_id = :antes');
    $up->execute(['id' => $conta['id'], 'antes' => $conta['nivel']]);
    if ($up->rowCount() !== 1) {
        throw new RuntimeException('o usuário mudou durante a operação');
    }
    $log = $pdo->prepare("INSERT INTO adms_logs (usuario_id, usuario_ip, codigo, pagina_id, descricao, acao, created)
                          VALUES (0, 'linha de comando', 200, 11, :d, 3, NOW())");
    $log->execute(['d' => "promover_administrador: usuário id={$conta['id']} do nível {$conta['nivel']} ({$conta['nivel_nome']}) para o nível 1 ({$super})"]);
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, "Erro: {$e->getMessage()}. Nada foi alterado.\n");
    exit(1);
}

echo "Feito: \"{$login}\" agora é do nível 1, {$super}. Registrado em adms_logs. Peça para ele sair e entrar de novo.\n";
exit(0);
