<?php
/*
 * Monta a DEMONSTRAÇÃO CONSOLIDADA (E5) PELO CAMINHO DO SISTEMA, via HTTP, depois de
 * database/seed_demo.sql (que só apaga a anterior; rode-o por database/ferramentas/limpar_demo.php, E7).
 * Tudo inventado. Só roda com AMBIENTE = 'demonstracao' (D-220).
 *
 *   ADMINISTRADOR  polo, curso (presencial), usuários da equipe (1 secretaria e 2
 *                  professores), finaliza a grade;
 *   SECRETARIA     ano letivo, 6 matérias, grade com as 6 disciplinas (sem módulo, D-268), oferta
 *                  2026 (15 encontros), 8 alunos, link de acesso de 7 (o 8º fica sem senha),
 *                  vínculo, enturmação sugerida (1 aluna com ajuste) e o encerramento de 4
 *                  disciplinas (FAP, MPA, GPS, OFP);
 *   ALUNOS         definem a senha pelo link recebido (portal);
 *   E6a            dados da instituição fictícia e logo fictício (gerado aqui), 2 atos legais
 *                  fictícios (presencial e EaD), titulação dos professores, período das
 *                  ofertas, Heitor com matrícula EaD, e uma TURMA 2025 CONCLUÍDA num segundo
 *                  curso (exige TCC): Irene reprovada e depois aprovada em nova oferta, TCC
 *                  aprovado e conclusão aceita; João com TCC pendente e conclusão recusada;
 *   PROFESSORES    atividades, notas (com vírgula) e faltas (1 com falta compensada);
 *   E6b            código (abreviação) de cada matéria, dados pessoais fictícios de 4 alunos,
 *                  os 2 modelos padrão personalizados pelo administrador (cabeçalho em linhas,
 *                  polo, 2 assinaturas fictícias, uma com imagem gerada aqui) e as emissões:
 *                  PARCIAL do Diego, FINAL da Irene, FINAL do João RECUSADO e PARCIAL do Heitor.
 *
 * Liderança de Equipes e Trabalho de Conclusão de Curso ficam ABERTAS (média parcial). As senhas são geradas aqui e mostradas SÓ no
 * terminal, no fim; nenhuma vai para arquivo.
 *
 * Onda 3, Etapa 4: o cenário e a montagem moram em database/ferramentas/demonstracao_passos.php, dividida em passos
 * curtos — os mesmos que a tela roda, um por chamado do navegador. Aqui, os passos rodam em sequência.
 *
 * Uso (o sistema precisa estar no ar):
 *   php database/ferramentas/seed_demo_processar.php http://localhost/vcostaedu/ [--sobrescrever-instituicao-configurada]
 * (E1b, D-352: sem a confirmação, recusa quando a instituição da base foi configurada pela tela.)
 * e digite, em duas linhas, o usuário e a senha do administrador.
 */

require __DIR__ . '/comum.php';

/* E7 (D-220): TRAVA DE AMBIENTE — só monta a demonstração com AMBIENTE = 'demonstracao' e sem emissão real. */
$ambienteDemo = defined('AMBIENTE') ? (string) AMBIENTE : 'producao';
if ($ambienteDemo !== 'demonstracao' && !vcostaedu_demonstracao_ativa(vcostaedu_pdo())) { // Onda 2, Etapa 4: ou ligada pela tela
    fwrite(STDERR, "RECUSADO: AMBIENTE = '{$ambienteDemo}' em painel/core/Config.php. A demonstração só é montada com AMBIENTE = 'demonstracao'. Nada foi alterado.\n");
    exit(2);
}
$reaisDemo = (int) vcostaedu_pdo()->query("SELECT COUNT(*) FROM rm_historico_emissoes WHERE ambiente <> 'demonstracao'")->fetchColumn();
if ($reaisDemo > 0) {
    fwrite(STDERR, "RECUSADO: há {$reaisDemo} emissão(ões) de histórico que NÃO são da demonstração. Nada foi alterado.\n");
    exit(3);
}
vcostaedu_trava_instituicao(vcostaedu_pdo(), $argv, 'seed_demo_processar.php'); // E1b (D-352)

$base = rtrim($argv[1] ?? '', '/') . '/';
if (!preg_match('#^https?://#', $base)) {
    fwrite(STDERR, "Uso: php database/ferramentas/seed_demo_processar.php <URL base, ex. http://localhost/vcostaedu/>\n");
    exit(1);
}
$painel = $base . 'painel/';
$portal = $base . 'portal/';

fwrite(STDERR, "Usuário do administrador: ");
$adminUsuario = trim((string) fgets(STDIN));
fwrite(STDERR, "Senha do administrador: ");
$adminSenha = trim((string) fgets(STDIN));
fwrite(STDERR, "\n");
if ($adminUsuario === '' || $adminSenha === '') {
    fwrite(STDERR, "Usuário e senha do administrador são obrigatórios.\n");
    exit(1);
}

require_once __DIR__ . '/demonstracao_passos.php';

$pdo = vcostaedu_pdo();
$adminNav = function () use ($painel, $adminUsuario, $adminSenha): VcDemoNavegador {
    $n = new VcDemoNavegador();
    $n->req($painel . 'login/acesso', ['usuario' => $adminUsuario, 'senha' => $adminSenha, 'SendLogin' => 'Acessar']);
    if ($n->code !== 200 || str_contains($n->html, 'name="SendLogin"')) {
        VcDemoPassos::falha("login de {$adminUsuario} no painel não entrou.");
    }
    echo "Administrador entrou no painel.\n";
    return $n;
};
$cenario = VcDemo::cenario($pdo); // Onda 4, Etapa 4: a faculdade fictícia ou o colégio fictício
$senhas = $cenario::novasSenhas();
$c = new VcDemoContexto($pdo, $base, $adminNav, $senhas, static function (string $t): void {
    echo $t . "\n";
});
try {
    foreach (array_keys($cenario::PASSOS) as $passo) {
        $t0 = microtime(true);
        $cenario::rodar($passo, $c);
        if (getenv('VCOSTAEDU_TEMPOS')) {
            fwrite(STDERR, sprintf("  [tempo] %-16s %.1f s\n", $passo, microtime(true) - $t0));
        }
    }
} catch (VcDemoFalha $e) {
    fwrite(STDERR, 'FALHA: ' . $e->getMessage() . "\n");
    exit(1);
}
echo "\n";

if ($cenario === VcDemoEscola::class) {
    echo "O colégio fictício está montado: " . count(VcDemoEscola::todas()) . " turmas e " . count(VcDemoEscola::ras()) . " alunos.\n";
    echo "\nSenhas da demonstração (mostradas só agora; não ficam gravadas em lugar nenhum):\n";
    foreach (array_merge(VcDemoPassos::EQUIPE, VcDemoEscola::EQUIPE_ESCOLA) as $login => [$nome]) { // Onda 8 (D-84): os professores por componente
        echo '  painel  ' . str_pad($login, 18) . $senhas[$login] . "\n";
    }
    foreach (VcDemoEscola::todas() as [, , , , $alunosT]) {
        foreach ($alunosT as $ra => [$nome]) {
            echo '  portal  RM ' . $ra . '  ' . str_pad($nome, 25) . $senhas[(string) $ra] . "\n";
        }
    }
    /* Onda 7: os responsáveis que criaram a senha pelo convite (entram no portal com o e-mail) */
    foreach (VcDemoEscola::RESPONSAVEIS as $email => [$nome, $acesso]) {
        if ($acesso === 'senha') {
            echo '  familia ' . str_pad($email, 34) . $senhas[$email] . "\n";
        }
    }
    exit(0);
}
if ($cenario === VcDemoLivre::class) { // Fechamento (D-106): o curso livre fictício
    echo "O curso livre fictício está montado: o curso, a turma aberta com os quatro módulos e " . count(VcDemoLivre::ALUNOS) . " alunos.\n";
    echo "\nSenhas da demonstração (mostradas só agora; não ficam gravadas em lugar nenhum):\n";
    foreach (VcDemoPassos::EQUIPE as $login => [$nome]) {
        echo '  painel  ' . str_pad($login, 18) . $senhas[$login] . "\n";
    }
    foreach (VcDemoLivre::ALUNOS as $ra => [$nome]) {
        echo '  portal  RA ' . $ra . '  ' . str_pad($nome, 25) . $senhas[(string) $ra] . "\n";
    }
    exit(0);
}
/* ------------------------------------------------ o resultado */
$um = fn(string $sql, array $p = []) => $c->um($sql, $p);
$pad = fn(string $t, int $n) => $t . str_repeat(' ', max(1, $n - mb_strlen($t)));
$curso = (int) $um('SELECT id FROM rm_cursos WHERE nome = ?', [VcDemoPassos::CURSO]);
$oferta = [];
foreach (VcDemoPassos::DISC as $k => $nome) {
    $oferta[$k] = (int) $um('SELECT o.id FROM rm_professores_materias o JOIN rm_materias m ON m.id = o.codmat WHERE o.curso_id = ? AND o.ano = ? AND m.nome = ?', [$curso, VcDemoPassos::ANO, $nome]);
}
echo str_pad('Aluno', 26) . implode('', array_map(fn($k) => str_pad($k, 22), array_keys(VcDemoPassos::DISC))) . "\n";
foreach (VcDemoPassos::ALUNOS as $ra => [$nome]) {
    $linha = $pad(explode(' ', $nome)[0] . " ({$ra})", 26);
    foreach ($oferta as $k => $of) {
        $g = $pdo->query("SELECT media_final, casas, frequencia, resultado, motivo FROM rm_disciplina_media_final WHERE rm = '{$ra}' AND rm_prof_mat_id = {$of}")->fetch(PDO::FETCH_ASSOC);
        if ($g) {
            $r = (int) $g['resultado'] === 1 ? 'Apr' : ['nota' => 'Rep.nota', 'frequencia' => 'Rep.freq', 'nota_frequencia' => 'Rep.n+f'][$g['motivo']];
            $cel = number_format((float) $g['media_final'], (int) $g['casas'], ',', '') . ' ' . rtrim(rtrim(number_format((float) $g['frequencia'], 2, ',', ''), '0'), ',') . '% ' . $r;
        } elseif ((int) $um('SELECT COUNT(*) FROM portal_disciplinas_alunos WHERE rm = ? AND rm_prof_mat_id = ?', [(string) $ra, $of]) === 0) {
            $cel = '(não enturmado)';
        } else {
            $cel = 'aberta';
        }
        $linha .= $pad($cel, 22);
    }
    echo rtrim($linha) . "\n";
}

echo "\nSenhas da demonstração (mostradas só agora; não ficam gravadas em lugar nenhum):\n";
foreach (VcDemoPassos::EQUIPE as $login => [$nome]) {
    echo '  painel  ' . str_pad($login, 18) . $senhas[$login] . "\n";
}
$modo = $um("SELECT valor FROM adms_parametros WHERE chave = 'portal.login.campo'") ?: 'ra';
foreach (VcDemoPassos::ALUNOS as $ra => [$nome]) {
    echo '  portal  RA ' . $ra . '  ' . $pad($nome, 25) . ($senhas[(string) $ra] ?? '(sem senha: use "Enviar link de acesso")') . "\n";
}
echo "  (modo de login do portal: {$modo})\n";
