<?php
/*
 * UM MOODLE FALSO, para a prova da Etapa 8 (php -S, só na máquina da prova). Responde como o CONTRATO medido do web
 * service do Moodle com o plugin local_vcostaedu (webservice/rest/server.php, wstoken, wsfunction, json; as 17
 * funções; os erros invalidtoken, accessexception, invalidparameter, invalidrecord e os vcostaedu_*), com estado:
 * usuários, categorias, disciplinas e inscrições, num arquivo JSON (VC_MOODLE_FALSO). Serve quando o Moodle de teste
 * não responde (o prompt: "prove com um servidor falso local que responde como o contrato").
 *
 * Os modos, pelo começo do caminho:
 *   /contrato/...  o Moodle inteiro (a chave certa é VC_MOODLE_FALSO_CHAVE); /contrato/vcostaedu/login?key=… é o link
 *                  de entrada (uso único: abre a sessão e leva ao returnurl); /contrato/course/view.php?id=… é a
 *                  disciplina (com o menu do usuário quando a sessão está aberta);
 *   /lento/...     espera 10 segundos antes de responder (o tempo curto do cliente);
 *   /aviso/...     enrol_manual_enrol_users responde "Message was not sent." e GRAVA (o e-mail do Moodle parado);
 *   /contas/...    o Moodle inteiro, sem conferir a chave e sem recusar e-mail repetido (o Moodle que deixa duas contas
 *                  com o mesmo e-mail): a pendência de várias contas e a busca em grupo (o registro "buscas_email").
 */
$arq = getenv('VC_MOODLE_FALSO') ?: sys_get_temp_dir() . '/vc_moodle_falso.json';
$e = is_file($arq) ? (json_decode((string) file_get_contents($arq), true) ?: []) : [];
$e += ['seq' => 100, 'usuarios' => [], 'categorias' => [], 'cursos' => [], 'inscricoes' => [], 'chaves' => [], 'inscritos' => []];
$gravar = static function () use (&$e, $arq) { file_put_contents($arq, json_encode($e)); };
$caminho = (string) parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$partes = explode('/', trim($caminho, '/'));
$modo = $partes[0] ?? '';
$resto = '/' . implode('/', array_slice($partes, 1));
$base = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/' . $modo;
$erro = static fn(string $cod, string $msg = '') => ['exception' => 'moodle_exception', 'errorcode' => $cod, 'message' => $msg !== '' ? $msg : $cod];

if ($modo === 'lento') {
    sleep(10);
}

/* ---------------------------------------------------------------- as páginas (o navegador do aluno) -------------- */
if ($modo === 'contrato' && $resto === '/vcostaedu/login') {
    $k = (string) ($_GET['key'] ?? '');
    $c = $e['chaves'][$k] ?? null;
    unset($e['chaves'][$k]); // uso único
    $gravar();
    if (!$c || $c['ate'] < time()) {
        http_response_code(403);
        echo '<!doctype html><title>Link inválido</title><p>O link de entrada expirou ou já foi usado.</p>';
        return true;
    }
    setcookie('MoodleFalso', (string) $c['usuario'], ['path' => '/', 'httponly' => true]);
    header('Location: ' . $c['volta'], true, 303);
    return true;
}
if ($modo === 'contrato' && ($resto === '/course/view.php' || $resto === '/my' || $resto === '/my/')) {
    $u = $_COOKIE['MoodleFalso'] ?? '';
    header('Content-Type: text/html; charset=utf-8');
    if ($u === '') {
        echo '<!doctype html><title>Acesso</title><form id="login"><input name="username"><input name="password" type="password"></form>';
        return true;
    }
    $curso = $e['cursos'][(string) ($_GET['id'] ?? '')] ?? null;
    echo '<!doctype html><title>' . htmlspecialchars($curso ? $curso['fullname'] : 'Painel', ENT_QUOTES) . ' | Moodle falso</title>'
        . '<div class="usermenu"><span class="userbutton">' . htmlspecialchars($u, ENT_QUOTES) . '</span></div><h1>' . htmlspecialchars($curso ? $curso['fullname'] : 'Painel', ENT_QUOTES) . '</h1>';
    return true;
}

/* ---------------------------------------------------------------- o web service ---------------------------------- */
header('Content-Type: application/json');
$f = (string) ($_POST['wsfunction'] ?? '');
if ($modo === 'contrato' && (string) ($_POST['wstoken'] ?? '') !== (string) getenv('VC_MOODLE_FALSO_CHAVE')) {
    echo json_encode($erro('invalidtoken', 'Invalid token - token not found'));
    return true;
}
$FUNCOES = ['core_webservice_get_site_info', 'core_user_create_users', 'core_user_update_users', 'core_user_get_users_by_field', 'core_user_get_users',
    'core_course_get_categories', 'core_course_create_categories', 'core_course_get_courses_by_field', 'core_course_create_courses',
    'core_course_update_courses', 'enrol_manual_enrol_users', 'enrol_manual_unenrol_users', 'core_enrol_get_enrolled_users',
    'core_enrol_get_users_courses', 'gradereport_user_get_grade_items', 'local_vcostaedu_request_login_url', 'local_vcostaedu_find_users_by_email'];
$usuario = static fn(array $u) => ['id' => $u['id'], 'username' => $u['username'], 'idnumber' => $u['idnumber'] ?? '', 'email' => $u['email'], 'auth' => $u['auth'],
    'suspended' => !empty($u['suspended']), 'firstname' => $u['firstname'], 'lastname' => $u['lastname']];
$curso = static fn(array $c) => ['id' => $c['id'], 'fullname' => $c['fullname'], 'shortname' => $c['shortname'], 'idnumber' => $c['idnumber'] ?? '', 'categoryid' => $c['categoryid']];

$saida = null;
switch ($f) {
    case 'core_webservice_get_site_info':
        $saida = ['sitename' => 'Moodle falso', 'release' => '4.5 (contrato)', 'functions' => array_map(fn($n) => ['name' => $n], $FUNCOES)];
        break;
    case 'core_user_create_users':
        $saida = [];
        foreach ((array) ($_POST['users'] ?? []) as $n) {
            foreach ($e['usuarios'] as $u) {
                if ($u['username'] === ($n['username'] ?? '') || ($n['idnumber'] ?? '') !== '' && ($u['idnumber'] ?? '') === $n['idnumber']) {
                    $saida = $erro('invalidparameter', 'Invalid parameter value detected (Username already exists)');
                    break 3;
                }
            }
            if (empty($n['email']) || empty($n['username']) || empty($n['firstname']) || empty($n['lastname'])) {
                $saida = $erro('invalidparameter');
                break 2;
            }
            $id = ++$e['seq'];
            $e['usuarios'][(string) $id] = ['id' => $id, 'username' => $n['username'], 'idnumber' => $n['idnumber'] ?? '', 'email' => $n['email'], 'auth' => $n['auth'] ?? 'manual',
                'firstname' => $n['firstname'], 'lastname' => $n['lastname'], 'suspended' => 0];
            $saida[] = ['id' => $id, 'username' => $n['username']];
        }
        break;
    case 'core_user_update_users':
        foreach ((array) ($_POST['users'] ?? []) as $n) {
            if (!isset($e['usuarios'][(string) ($n['id'] ?? '')])) {
                $saida = $erro('invalidrecord');
                break 2;
            }
            foreach (['firstname', 'lastname', 'email', 'suspended', 'idnumber'] as $k) {
                if (isset($n[$k])) {
                    $e['usuarios'][(string) $n['id']][$k] = $n[$k];
                }
            }
        }
        break;
    case 'core_user_get_users_by_field':
        $campo = (string) ($_POST['field'] ?? '');
        $vals = array_map('strval', (array) ($_POST['values'] ?? []));
        $saida = array_values(array_map($usuario, array_filter($e['usuarios'], fn($u) => in_array((string) ($u[$campo] ?? ''), $vals, true))));
        break;
    case 'core_user_get_users':
        $saida = ['users' => [], 'warnings' => []];
        break;
    case 'core_course_get_categories':
        $saida = array_values(array_map(fn($c) => ['id' => $c['id'], 'name' => $c['name'], 'coursecount' => 0], $e['categorias']));
        break;
    case 'core_course_create_categories':
        $saida = [];
        foreach ((array) ($_POST['categories'] ?? []) as $n) {
            $id = ++$e['seq'];
            $e['categorias'][(string) $id] = ['id' => $id, 'name' => (string) $n['name']];
            $saida[] = ['id' => $id, 'name' => (string) $n['name']];
        }
        break;
    case 'core_course_get_courses_by_field':
        $campo = (string) ($_POST['field'] ?? '');
        $valor = (string) ($_POST['value'] ?? '');
        $campo = $campo === 'category' ? 'categoryid' : $campo;
        $saida = ['courses' => array_values(array_map($curso, array_filter($e['cursos'], fn($c) => (string) ($c[$campo] ?? '') === $valor))), 'warnings' => []];
        break;
    case 'core_course_create_courses':
        $saida = [];
        foreach ((array) ($_POST['courses'] ?? []) as $n) {
            foreach ($e['cursos'] as $c) {
                if ($c['shortname'] === ($n['shortname'] ?? '') || ($n['idnumber'] ?? '') !== '' && ($c['idnumber'] ?? '') === $n['idnumber']) {
                    $saida = $erro('invalidparameter', 'Short name is already used');
                    break 3;
                }
            }
            if (!isset($e['categorias'][(string) ($n['categoryid'] ?? '')])) {
                $saida = $erro('invalidrecord', 'Category not found');
                break 2;
            }
            $id = ++$e['seq'];
            $e['cursos'][(string) $id] = ['id' => $id, 'fullname' => (string) $n['fullname'], 'shortname' => (string) $n['shortname'], 'idnumber' => (string) ($n['idnumber'] ?? ''), 'categoryid' => (int) $n['categoryid']];
            $saida[] = ['id' => $id, 'shortname' => (string) $n['shortname']];
        }
        break;
    case 'core_course_update_courses':
        foreach ((array) ($_POST['courses'] ?? []) as $n) {
            foreach (['fullname', 'shortname'] as $k) {
                if (isset($n[$k], $e['cursos'][(string) ($n['id'] ?? '')])) {
                    $e['cursos'][(string) $n['id']][$k] = (string) $n[$k];
                }
            }
        }
        $saida = ['warnings' => []];
        break;
    case 'local_vcostaedu_find_users_by_email':
        /* o contrato: de 1 a 100 e-mails válidos; para cada um, na ordem, o e-mail como veio e os ids das contas (as
           suspensas também); nada mais do usuário */
        $lista = array_map('strval', (array) ($_POST['emails'] ?? []));
        if (!$lista || count($lista) > 100 || array_filter($lista, fn($x) => !filter_var($x, FILTER_VALIDATE_EMAIL))) {
            $saida = $erro('invalidparameter', 'Invalid parameter value detected');
            break;
        }
        $e['buscas_email'][] = count($lista);
        $saida = [];
        foreach ($lista as $x) {
            $ids = [];
            foreach ($e['usuarios'] as $u) {
                if (strcasecmp((string) $u['email'], $x) === 0) {
                    $ids[] = (int) $u['id'];
                }
            }
            $saida[] = ['email' => $x, 'userids' => $ids];
        }
        break;
    case 'enrol_manual_enrol_users':
        foreach ((array) ($_POST['enrolments'] ?? []) as $n) {
            $u = (string) ($n['userid'] ?? '');
            $c = (string) ($n['courseid'] ?? '');
            if ($modo === 'contrato' && (!isset($e['usuarios'][$u]) || !isset($e['cursos'][$c]))) {
                $saida = $erro('invalidparameter', 'Invalid parameter value detected');
                break 2;
            }
            $e['inscricoes'][$c . ':' . $u] = ['curso' => (int) $c, 'usuario' => (int) $u, 'papel' => (int) ($n['roleid'] ?? 5), 'suspensa' => (int) ($n['suspend'] ?? 0)];
            $e['inscritos'] = array_values(array_unique(array_merge($e['inscritos'], [(int) $u])));
        }
        if ($modo === 'aviso') {
            $saida = $erro('errorwhilesendingmessage', 'Message was not sent.');
        }
        break;
    case 'enrol_manual_unenrol_users':
        foreach ((array) ($_POST['enrolments'] ?? []) as $n) {
            unset($e['inscricoes'][(string) $n['courseid'] . ':' . (string) $n['userid']]);
        }
        break;
    case 'core_enrol_get_enrolled_users':
        $c = (int) ($_POST['courseid'] ?? 0);
        $ativos = false;
        foreach ((array) ($_POST['options'] ?? []) as $o) {
            if (($o['name'] ?? '') === 'onlyactive') {
                $ativos = (int) ($o['value'] ?? 0) === 1;
            }
        }
        if ($modo !== 'contrato') {
            $saida = array_map(fn($id) => ['id' => (int) $id], $e['inscritos']);
            break;
        }
        $saida = [];
        foreach ($e['inscricoes'] as $i) {
            $u = $e['usuarios'][(string) $i['usuario']] ?? null;
            if ($i['curso'] === $c && $u && (!$ativos || (!$i['suspensa'] && empty($u['suspended'])))) {
                $saida[] = ['id' => $i['usuario'], 'roles' => [['roleid' => $i['papel']]]];
            }
        }
        break;
    case 'core_enrol_get_users_courses':
        $u = (int) ($_POST['userid'] ?? 0);
        $saida = [];
        foreach ($e['inscricoes'] as $i) {
            if ($i['usuario'] === $u && isset($e['cursos'][(string) $i['curso']])) {
                $saida[] = $curso($e['cursos'][(string) $i['curso']]);
            }
        }
        break;
    case 'gradereport_user_get_grade_items':
        $saida = ['usergrades' => [], 'warnings' => []];
        break;
    case 'local_vcostaedu_request_login_url':
        $nome = (string) ($_POST['username'] ?? '');
        $u = null;
        foreach ($e['usuarios'] as $x) {
            if ($x['username'] === $nome) {
                $u = $x;
            }
        }
        if (!$u) {
            $saida = $erro('vcostaedu_usernotfound');
        } elseif (!empty($u['suspended'])) {
            $saida = $erro('vcostaedu_usersuspended');
        } else {
            $k = bin2hex(random_bytes(16));
            $e['chaves'][$k] = ['usuario' => $nome, 'volta' => (string) ($_POST['returnurl'] ?? $base . '/my/'), 'ate' => time() + 60];
            $saida = ['loginurl' => $base . '/vcostaedu/login?key=' . $k, 'warnings' => []];
        }
        break;
    default:
        $saida = $erro('accessexception', 'Access control exception');
}
$gravar();
echo json_encode($saida);
return true;
