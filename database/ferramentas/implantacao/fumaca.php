<?php
/*
 * TESTE DE FUMAÇA POR PERFIL (E8). Pela web, contra qualquer URL: para cada perfil, entra, abre as telas do
 * menu (só GET, nada que altere dados), confere que não há erro de PHP, recusa nem HTTP ≥ 400, e sai.
 *
 * Uso: php database/ferramentas/implantacao/fumaca.php <URL base> [--inseguro] [--limite=40]
 *
 * Pede usuário e senha de cada perfil (Onda 2: os 7 — administrador, direção, supervisão, secretaria, coordenação,
 * professor, aluno) no terminal; a senha é
 * lida sem eco e nunca é gravada. Enter no usuário pula o perfil (ALERTA). O que muda no banco: só o que o
 * próprio login registra (log de acesso, último acesso), como num uso normal.
 */
require __DIR__ . '/_base.php';

$o = vcostaedu_args($argv);
$B = vcostaedu_url_base($o['_'][0] ?? null);
$limite = max(5, (int) ($o['limite'] ?? 40));
$evitar = '#/(logout|sair|apagar|excluir|deletar|desenturmar|enturmar|processa|gerar|alterar|alt-|ordem|ordenacao|enviar-link|imprimir|salvar|cadastrar-|sit-|editar-senha|atualizar-senha|esqueceu|mover|remover|adicionar|situacao|confirmar|vincular|posicionar|editar-vinculo|reabrir|edit-notas|upload|segunda-via|cancelar|liberar|visto|emitir|encerrar)#i';
$perfis = ['administrador' => 'painel', 'direcao' => 'painel', 'supervisao' => 'painel', 'secretaria' => 'painel', 'coordenacao' => 'painel', 'professor' => 'painel', 'aluno' => 'portal'];

foreach ($perfis as $perfil => $app) {
    Saida::secao("Perfil {$perfil} ({$app})");
    $u = vcostaedu_perguntar("Usuário do perfil {$perfil} (Enter para pular): ");
    if ($u === '') {
        Saida::alerta("{$perfil}: pulado (sem usuário)");
        continue;
    }
    $s = vcostaedu_perguntar('Senha (não aparece): ', true);
    $h = new Http(!empty($o['inseguro']));
    [$c0, , $html0] = $h->req("{$B}{$app}/login/acesso");
    if ($c0 !== 200) {
        Saida::falha("{$perfil}: a tela de login respondeu HTTP {$c0}" . ($c0 === 0 ? " ({$html0}; em teste, --inseguro)" : ''));
        continue;
    }
    [$c, , $html, $final] = $h->req("{$B}{$app}/login/acesso", ['usuario' => $u, 'senha' => $s, 'SendLogin' => 'Acessar']);
    $s = str_repeat("\0", strlen($s));
    unset($s);
    if (vcostaedu_eh_login($html)) {
        $msg = preg_match("/id='loginFalha'[^>]*>.*?<\/button>(.*?)<\/div>/s", $html, $m) ? trim(strip_tags($m[1])) : 'voltou à tela de login';
        Saida::falha("{$perfil}: não entrou — {$msg}");
        continue;
    }
    $err = vcostaedu_erros_php($html);
    $err === '' ? Saida::ok("{$perfil}: entrou (" . substr($final, strlen($B)) . ')') : Saida::falha("{$perfil}: página inicial com erro de PHP: {$err}");

    /* telas do menu lateral (e do alto) */
    $d = new DOMDocument();
    @$d->loadHTML('<?xml encoding="utf-8"?>' . $html);
    $links = [];
    foreach ((new DOMXPath($d))->query('//aside//a[@href] | //nav//a[@href]') as $a) {
        $href = strtok($a->getAttribute('href'), '#');
        if (str_starts_with($href, "{$B}{$app}/") && !preg_match($evitar, $href)) {
            $links[$href] = true;
        }
    }
    $links = array_slice(array_keys($links), 0, $limite);
    $prob = [];
    foreach ($links as $l) {
        [$cl, , $hl] = $h->req($l);
        $e = vcostaedu_erros_php($hl);
        if ($cl >= 400 || $cl === 0 || $e !== '' || vcostaedu_eh_login($hl)) {
            $prob[] = substr($l, strlen($B)) . " → HTTP {$cl}" . (vcostaedu_eh_login($hl) ? ' (voltou ao login)' : '') . ($e !== '' ? " {$e}" : '');
        }
    }
    if ($prob) {
        Saida::falha("{$perfil}: " . count($prob) . ' de ' . count($links) . ' telas com problema');
        foreach (array_slice($prob, 0, 15) as $p) {
            Saida::info($p);
        }
    } else {
        Saida::ok("{$perfil}: " . count($links) . ' telas do menu abertas, sem erro de PHP, recusa ou HTTP ≥ 400');
    }
    [$cs, , $hs] = $h->req("{$B}{$app}/login/logout");
    vcostaedu_eh_login($hs) ? Saida::ok("{$perfil}: saiu (logout)") : Saida::alerta("{$perfil}: o logout não voltou à tela de login (HTTP {$cs})");
}
Saida::fim();
