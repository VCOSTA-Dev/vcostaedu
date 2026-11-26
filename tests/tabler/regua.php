<?php
/*
 * A RÉGUA DA ONDA: o retrato dos dados da demonstração, para provar que a conversão de interface não mudou nada
 * na regra acadêmica.
 *
 *   php tests/tabler/regua.php gravar   <arquivo.json>   tira o retrato
 *   php tests/tabler/regua.php comparar <arquivo.json>   tira de novo e compara; sai com 1 se houver diferença
 *
 * O retrato (a origem não versiona a ferramenta do "checksum canônico" que o estado do projeto cita, então ela foi
 * refeita aqui, pelo mesmo critério):
 *   - por tabela: o número de linhas e um sha256 canônico das linhas, sem as colunas que mudam a cada montagem
 *     (horários, hashes, senhas, códigos e tokens aleatórios, nome aleatório de arquivo) e sem as tabelas de
 *     registro de uso (logs, tentativas, controle do runner, vistos de novidade);
 *   - por aluno: o boletim (AdmsBoletimCurso::doAluno) — notas, médias, frequência, resultado;
 *   - por matrícula: a vida acadêmica (AdmsVidaAcademica::montar) — a fonte única da prévia e da emissão do
 *     histórico, com as mesmas colunas voláteis fora.
 * Só linha de comando, contra o banco da Config do painel.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

[$_, $modo, $arquivo] = $argv + [null, '', ''];
/* Onda 4 (docs/ESCOLA.md, "A régua do superior"): --config=t1,t2 nomeia as tabelas de CONFIGURAÇÃO que a etapa muda de
   propósito (versões, novidades, páginas…); a diferença nelas é listada, mas não reprova. Boletins, vidas acadêmicas e
   as outras tabelas continuam tendo de ser idênticos; uma tabela nova só passa se vier vazia. */
$config = [];
foreach (array_slice($argv, 3) as $o) {
    if (str_starts_with((string) $o, '--config=')) {
        $config = array_values(array_filter(explode(',', substr((string) $o, 9))));
    }
}
if (!in_array($modo, ['gravar', 'comparar'], true) || $arquivo === '') {
    fwrite(STDERR, "Uso: php tests/tabler/regua.php gravar|comparar <arquivo.json>\n");
    exit(2);
}

$raiz = dirname(__DIR__, 2);
/* O caminho do arquivo é resolvido ANTES do chdir para painel/ (os models do painel esperam rodar de lá). */
if (!preg_match('#^([A-Za-z]:[\\\\/]|/)#', $arquivo)) { // "C:\…" e "C:/…" são absolutos (antes, só a barra normal)
    $arquivo = getcwd() . DIRECTORY_SEPARATOR . $arquivo;
}
define('VCOSTAEDU', true);
foreach (['Un1Mv' . 'C05tA'] as $g) { defined($g) || define($g, true); } // a guarda de antes da 1.45.0, só para a Config.php já instalada (DECISIONS D-53)
$_SERVER += ['SERVER_NAME' => 'localhost', 'HTTP_HOST' => 'localhost', 'REQUEST_URI' => '/', 'SCRIPT_NAME' => '/painel/index.php'];
chdir($raiz . '/painel');
require $raiz . '/painel/vendor/autoload.php';
(new class extends \Core\Config {
    public function __construct()
    {
        $this->configAdm();
    }
});
require_once $raiz . '/core/Requisicao.php';
\Core\Requisicao::definir();
$_SESSION = ['colUsuario_id' => 1, 'colUsuario_nivel' => 1, 'colUsuario_ordem' => 1];

$pdo = new PDO('mysql:host=' . HOST . ';dbname=' . DBNAME . ';charset=utf8mb4', USER, PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);

/* O que muda a cada montagem e não é regra: medido montando a demonstração duas vezes e comparando. */
/* Onda 2, Etapa 3: a auditoria de notas e faltas é registro de uso (hora, IP e navegador mudam a cada montagem); o conteúdo
   dela é conferido pela prova tests/tabler/onda2/etapa3.js. */
/* Onda 4, Etapa 4: na escola, a hora de entrar e sair da turma e o "em" das tabelas esc_* (nenhuma tabela do superior tem essas colunas) */
const TABELAS_FORA = ['adms_logs', 'col_logs', 'seg_tentativas', 'adms_migrations', 'adms_novidades_vistas', 'col_novidades_vistas', 'col_usuarios_tokens', 'rm_relatorios_exportacoes', 'rm_auditoria_lancamentos', 'adms_api_chamadas', 'adms_api_idempotencia'];
const COLUNAS_VOLATEIS = '/^(created|modified|data_alt|rg|cpf|.*_em|em|quando|entrada|saida|.*data_hora.*|ultimo_acesso|senha|senhas|recuperar_senha|chave_descadastro|token.*|hash.*|codigo|qr.*|pdf_arquivo|logo|arquivo|imagem|.*assinatura.*|html|snapshot|alertas_aceitos|ip)$/i';

function canonico(mixed $v): mixed
{
    if (is_array($v)) {
        $saida = [];
        foreach ($v as $k => $x) {
            if (is_string($k) && (preg_match(COLUNAS_VOLATEIS, $k) || preg_match('/[a-z]Em$/', $k))) {
                continue;
            }
            $saida[$k] = canonico($x);
        }
        return $saida;
    }
    return $v;
}

$retrato = ['tabelas' => [], 'boletins' => [], 'vidas' => []];

foreach ($pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN) as $tabela) {
    if (in_array($tabela, TABELAS_FORA, true)) {
        continue;
    }
    $colunas = array_column($pdo->query("SHOW COLUMNS FROM `{$tabela}`")->fetchAll(), 'Field');
    $usar = array_values(array_filter($colunas, fn($c) => !preg_match(COLUNAS_VOLATEIS, $c)));
    $linhas = $pdo->query('SELECT ' . implode(', ', array_map(fn($c) => "`{$c}`", $usar)) . " FROM `{$tabela}`")->fetchAll();
    /* Onda 3, Etapa 4: as senhas fictícias que a montagem da demonstração guarda para o cartão "Conheça o sistema" são
       sorteadas a cada montagem (como as outras senhas, fora da comparação); a chave fica */
    if ($tabela === 'adms_instalacao_estado') {
        foreach ($linhas as &$l) {
            if (($l['chave'] ?? '') === 'demonstracao.credenciais') {
                $l['valor'] = '(senhas sorteadas)';
            }
        }
        unset($l);
    }
    /* Onda 7, Etapa 6: o resumo do convite é o sha256 de um código sorteado a cada convite (como os tokens, fora da
       comparação); a linha fica */
    if ($tabela === 'esc_responsavel_convites') {
        foreach ($linhas as &$l) {
            if (array_key_exists('resumo', $l)) {
                $l['resumo'] = '(código sorteado)';
            }
        }
        unset($l);
    }
    /* a validade do convite, na auditoria, é a hora da montagem mais o prazo (como as outras horas, fora); o resto fica */
    if ($tabela === 'esc_responsavel_auditoria') {
        foreach ($linhas as &$l) {
            $d = json_decode((string) ($l['depois'] ?? ''), true);
            if (is_array($d) && array_key_exists('expira', $d)) {
                $d['expira'] = '(a hora da montagem)';
                $l['depois'] = json_encode($d, JSON_UNESCAPED_UNICODE);
            }
        }
        unset($l);
    }
    $textos = array_map(fn($l) => json_encode($l, JSON_UNESCAPED_UNICODE), $linhas);
    sort($textos);
    $retrato['tabelas'][$tabela] = ['linhas' => count($linhas), 'sha' => hash('sha256', implode("\n", $textos))];
    $retrato['conteudo'][$tabela] = $textos;
}

foreach ($pdo->query('SELECT rm FROM col_usuarios WHERE rm IS NOT NULL ORDER BY rm')->fetchAll(PDO::FETCH_COLUMN) as $rm) {
    $retrato['boletins'][(string) $rm] = canonico(\App\adms\Models\notas\AdmsBoletimCurso::doAluno((string) $rm));
}
foreach ($pdo->query('SELECT id FROM rm_matriculas_curso ORDER BY id')->fetchAll(PDO::FETCH_COLUMN) as $mc) {
    $vida = \App\adms\Models\historico\AdmsVidaAcademica::montar((int) $mc);
    /* Onda 2, Etapa 5: sem a conclusão, a data de referência do ato legal é HOJE (AdmsVidaAcademica) — muda com o
       calendário, não com o dado; sai do retrato (o ato escolhido continua comparado) */
    if (is_array($vida) && ($vida['atoLegal']['dataReferencia'] ?? null) === date('Y-m-d')) {
        unset($vida['atoLegal']['dataReferencia']);
    }
    $retrato['vidas'][(string) $mc] = canonico($vida);
}

if ($modo === 'gravar') {
    @mkdir(dirname($arquivo), 0755, true);
    file_put_contents($arquivo, json_encode($retrato, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    printf("Retrato gravado: %d tabelas, %d boletins, %d vidas acadêmicas.\n", count($retrato['tabelas']), count($retrato['boletins']), count($retrato['vidas']));
    exit(0);
}

$antes = json_decode((string) file_get_contents($arquivo), true);
/* Onda 8, correção (D-86): as DECISÕES REGISTRADAS que mudam um dado de propósito atualizam o retrato de referência
   antes da comparação, linha a linha e só pelo valor exato (tests/tabler/regua_decisoes.json): "troca" muda os campos
   da linha que tem exatamente o "onde"; "inclui" põe as linhas listadas, só no tipo de instituição nomeado e só se a
   referência não tem nenhuma delas. Qualquer outro valor continua diferente — a prova não afrouxa. */
$decisoesAplicadas = [];
$tipoAgora = (string) ($pdo->query("SELECT valor FROM adms_instalacao_estado WHERE chave = 'instituicao.tipo'")->fetchColumn() ?: 'superior');
foreach ((array) json_decode((string) @file_get_contents(__DIR__ . '/regua_decisoes.json'), true) as $d) {
    $t = (string) ($d['tabela'] ?? '');
    /* Correção da escola, Parte 1 (D-100): "colunas" — as colunas novas de uma tabela, com o valor de nascença, em cada
       linha da referência que ainda não as tem (as outras colunas continuam comparadas como estão) */
    if (($d['acao'] ?? '') === 'colunas' && $t !== '' && !empty($antes['conteudo'][$t]) && !(isset($d['tipo']) && $d['tipo'] !== $tipoAgora)) {
        $n = 0;
        foreach ($antes['conteudo'][$t] as &$texto) {
            $l = json_decode($texto, true);
            if (is_array($l) && array_diff_key($d['valores'], $l) === $d['valores']) {
                /* "depois_de": a coluna nasceu no meio da tabela (AFTER …): entra logo depois daquele campo, na mesma ordem
                   do retrato de agora; sem ele, no fim */
                if (!empty($d['depois_de']) && array_key_exists($d['depois_de'], $l)) {
                    $novo = [];
                    foreach ($l as $k => $x) {
                        $novo[$k] = $x;
                        if ($k === $d['depois_de']) {
                            $novo += $d['valores'];
                        }
                    }
                    $l = $novo;
                } else {
                    $l += $d['valores'];
                }
                $texto = json_encode($l, JSON_UNESCAPED_UNICODE);
                $n++;
            }
        }
        unset($texto);
        if ($n) {
            sort($antes['conteudo'][$t]);
            $antes['tabelas'][$t] = ['linhas' => count($antes['conteudo'][$t]), 'sha' => hash('sha256', implode("\n", $antes['conteudo'][$t]))];
            $decisoesAplicadas[] = "{$d['decisao']} {$t} (colunas, {$n} linha" . ($n > 1 ? 's' : '') . ')';
        }
        continue;
    }
    /* D-102: "atestado_escola" — na escola, a migration 0083 (ou a escolha do tipo) cria o modelo "Atestado de matrícula —
       escola" e a versão 1 dele. Numa referência sem ele, entram exatamente as duas linhas de agora SE forem o que o código
       cria (o nome, marcado para a escola, publicado, sem autor, e as seções iguais a VcAtestadoEscola::secoes()); qualquer
       outro conteúdo continua diferente. No ensino superior o modelo não existe e nada muda. */
    if (($d['acao'] ?? '') === 'atestado_escola') {
        require_once __DIR__ . '/../../core/AtestadoEscola.php';
        $dec = static fn(array $l) => array_map(static fn($x) => json_decode($x, true), $l);
        $temAntes = array_filter($dec($antes['conteudo']['rm_historico_modelos'] ?? []), static fn($m) => ($m['nome'] ?? '') === \VcAtestadoEscola::NOME);
        $agoraM = array_values(array_filter($dec($retrato['conteudo']['rm_historico_modelos'] ?? []), static fn($m) => ($m['nome'] ?? '') === \VcAtestadoEscola::NOME));
        if ($tipoAgora === 'escola' && !$temAntes && isset($antes['conteudo']['rm_historico_modelos']) && count($agoraM) === 1
            && $agoraM[0]['tipo'] === 'ATESTADO' && $agoraM[0]['tipo_instituicao'] === 'escola' && (int) $agoraM[0]['situacao'] === 2
            && (int) $agoraM[0]['versao_atual'] === 1 && $agoraM[0]['usuario_cre'] === null) {
            $mid = (int) $agoraM[0]['id'];
            $versoes = array_values(array_filter($dec($retrato['conteudo']['rm_historico_modelo_versoes'] ?? []), static fn($v) => (int) $v['modelo_id'] === $mid));
            if (count($versoes) === 1 && (int) $versoes[0]['versao'] === 1 && $versoes[0]['usuario_cre'] === null
                && json_decode((string) $versoes[0]['secoes'], true) === \VcAtestadoEscola::secoes()) {
                foreach (['rm_historico_modelos' => $agoraM[0], 'rm_historico_modelo_versoes' => $versoes[0]] as $tt => $linha) {
                    $antes['conteudo'][$tt][] = json_encode($linha, JSON_UNESCAPED_UNICODE);
                    sort($antes['conteudo'][$tt]);
                    $antes['tabelas'][$tt] = ['linhas' => count($antes['conteudo'][$tt]), 'sha' => hash('sha256', implode("\n", $antes['conteudo'][$tt]))];
                }
                $decisoesAplicadas[] = "{$d['decisao']} o atestado da escola (o modelo e a versão 1, iguais ao código)";
            }
        }
        continue;
    }
    /* D-100: "deriva" — a liberação do boletim nova (esc_boletim_liberacao) na referência de antes dela: exatamente o que a
       migration 0081 tira da tabela antiga da referência (cada etapa liberada, com quem liberou; a média e a situação
       finais do ano com todas as etapas liberadas), linha a linha, menos o número da linha. Qualquer outra linha
       continua diferente. */
    if (($d['acao'] ?? '') === 'deriva' && $t === 'esc_boletim_liberacao' && !array_key_exists($t, $antes['conteudo'] ?? []) && isset($retrato['conteudo'][$t])) {
        $dec = static fn(array $l) => array_map(static fn($x) => json_decode($x, true), $l);
        $anoDa = [];
        foreach ($dec($antes['conteudo']['esc_etapas'] ?? []) as $e) {
            $anoDa[(int) $e['id']] = (int) $e['ano'];
        }
        $esperado = [];
        $porAno = [];
        foreach ($dec($antes['conteudo']['esc_boletim_liberado'] ?? []) as $b) {
            $ano = $anoDa[(int) $b['etapa_id']] ?? 0;
            $esperado[] = json_encode([$ano, 'etapa', (int) $b['etapa_id'], 1, null, $b['por'] === null ? null : (int) $b['por']]);
            $porAno[$ano] = ($porAno[$ano] ?? 0) + 1;
        }
        foreach (array_count_values($anoDa) as $ano => $total) {
            if (($porAno[$ano] ?? 0) === $total) {
                $esperado[] = json_encode([$ano, 'media_final', 0, 1, null, null]);
                $esperado[] = json_encode([$ano, 'situacao_final', 0, 1, null, null]);
            }
        }
        $agora = array_map(static fn($l) => json_encode([(int) $l['ano'], $l['item'], (int) $l['etapa_id'], (int) $l['exibir'], $l['a_partir'], $l['alterado_por'] === null ? null : (int) $l['alterado_por']]), $dec($retrato['conteudo'][$t]));
        sort($esperado);
        sort($agora);
        if ($esperado === $agora) {
            $antes['conteudo'][$t] = $retrato['conteudo'][$t];
            $antes['tabelas'][$t] = $retrato['tabelas'][$t];
            $decisoesAplicadas[] = "{$d['decisao']} {$t} (deriva da tabela antiga, " . count($agora) . ' linha' . (count($agora) > 1 ? 's' : '') . ')';
        }
        continue;
    }
    /* Onda 8, financeiro (D-98): "inclui" numa tabela que a referência não tem (uma tabela nova que nasce com linhas,
       como as regras financeiras): a referência passa a tê-la só com as linhas listadas — as outras continuam diferentes */
    if ($t !== '' && ($d['acao'] ?? '') === 'inclui' && !array_key_exists($t, $antes['conteudo'] ?? []) && !(isset($d['tipo']) && $d['tipo'] !== $tipoAgora)) {
        $antes['conteudo'][$t] = [];
    } elseif (!empty($d['so_sem_a_tabela'])) {
        continue; // a decisão vale só para a referência de antes da tabela; numa referência que já a tem, compara como está
    }
    if ($t === '' || !array_key_exists($t, $antes['conteudo'] ?? []) || (isset($d['tipo']) && $d['tipo'] !== $tipoAgora)) {
        continue;
    }
    $n = 0;
    if (($d['acao'] ?? '') === 'troca') {
        foreach ($antes['conteudo'][$t] as &$texto) {
            $l = json_decode($texto, true);
            if (is_array($l) && count(array_intersect_key($l, $d['onde'])) === count($d['onde']) && array_intersect_key($l, $d['onde']) === array_replace(array_intersect_key($l, $d['onde']), $d['onde'])) {
                $texto = json_encode(array_replace($l, $d['fica']), JSON_UNESCAPED_UNICODE);
                $n++;
            }
        }
        unset($texto);
    } elseif (($d['acao'] ?? '') === 'inclui') {
        $novas = array_map(fn($l) => json_encode($l, JSON_UNESCAPED_UNICODE), (array) $d['linhas']);
        /* Renegociar (D-105): com "chave", a linha só entra se a referência não tem nenhuma linha com o mesmo valor
           nessa coluna (a referência de uma demonstração que já usou o acordo compara como está) */
        $jaTem = false;
        if (!empty($d['chave'])) {
            $k = (string) $d['chave'];
            $valores = array_map(fn($l) => $l[$k] ?? null, (array) $d['linhas']);
            foreach ($antes['conteudo'][$t] as $texto) {
                $l = json_decode($texto, true);
                if (is_array($l) && array_key_exists($k, $l) && in_array($l[$k], $valores, true)) { $jaTem = true; break; }
            }
        }
        if (!$jaTem && !array_intersect($novas, $antes['conteudo'][$t])) {
            $antes['conteudo'][$t] = array_merge($antes['conteudo'][$t], $novas);
            $n = count($novas);
        }
    }
    if ($n) {
        sort($antes['conteudo'][$t]);
        $antes['tabelas'][$t] = ['linhas' => count($antes['conteudo'][$t]), 'sha' => hash('sha256', implode("\n", $antes['conteudo'][$t]))];
        $decisoesAplicadas[] = "{$d['decisao']} {$t} ({$d['acao']}, {$n} linha" . ($n > 1 ? 's' : '') . ')';
    }
}
$difs = [];
$deProposito = [];
$novasVazias = [];
foreach (['tabelas', 'boletins', 'vidas'] as $parte) {
    $chaves = array_unique(array_merge(array_keys($antes[$parte] ?? []), array_keys($retrato[$parte])));
    foreach ($chaves as $k) {
        $a = json_encode($antes[$parte][$k] ?? null, JSON_UNESCAPED_UNICODE);
        $b = json_encode($retrato[$parte][$k] ?? null, JSON_UNESCAPED_UNICODE);
        if ($a !== $b) {
            if ($parte === 'tabelas' && in_array($k, $config, true)) {
                $deProposito[] = "{$k} (" . ($antes['tabelas'][$k]['linhas'] ?? 0) . ' → ' . ($retrato['tabelas'][$k]['linhas'] ?? 0) . ' linhas)';
                continue;
            }
            if ($parte === 'tabelas' && !isset($antes['tabelas'][$k]) && (int) ($retrato['tabelas'][$k]['linhas'] ?? -1) === 0) {
                $novasVazias[] = $k;
                continue;
            }
            $difs[] = "{$parte}/{$k}: " . primeiraDiferenca($parte === 'tabelas' ? ($antes['conteudo'][$k] ?? []) : ($antes[$parte][$k] ?? null),
                $parte === 'tabelas' ? ($retrato['conteudo'][$k] ?? []) : ($retrato[$parte][$k] ?? null));
        }
    }
}
/** O primeiro caminho em que os dois valores divergem, com os dois valores (para achar a causa). */
function primeiraDiferenca(mixed $a, mixed $b, string $caminho = ''): string
{
    if (is_array($a) && is_array($b)) {
        foreach (array_unique(array_merge(array_keys($a), array_keys($b))) as $k) {
            if (json_encode($a[$k] ?? null) !== json_encode($b[$k] ?? null)) {
                return primeiraDiferenca($a[$k] ?? null, $b[$k] ?? null, $caminho . '/' . $k);
            }
        }
        return $caminho . ' (iguais?)';
    }
    return $caminho . ' ' . mb_substr(json_encode($a, JSON_UNESCAPED_UNICODE), 0, 160) . ' → ' . mb_substr(json_encode($b, JSON_UNESCAPED_UNICODE), 0, 160);
}

printf("Régua: %d tabelas, %d boletins, %d vidas acadêmicas comparados.\n", count($retrato['tabelas']), count($retrato['boletins']), count($retrato['vidas']));
if ($decisoesAplicadas) {
    echo '  retrato de referência atualizado pelas decisões registradas: ' . implode(', ', $decisoesAplicadas) . "\n";
}
if ($difs) {
    echo "DIFERENTE em " . count($difs) . " ponto(s):\n  " . implode("\n  ", array_slice($difs, 0, 40)) . "\n";
    exit(1);
}
if ($deProposito || $novasVazias) {
    echo "IDÊNTICA nos dados acadêmicos (boletins, vidas acadêmicas e todas as outras tabelas).\n";
    if ($deProposito) {
        echo '  configuração mudada de propósito: ' . implode(', ', $deProposito) . "\n";
    }
    if ($novasVazias) {
        echo '  tabelas novas, vazias: ' . implode(', ', $novasVazias) . "\n";
    }
    exit(0);
}
echo "IDÊNTICA.\n";
