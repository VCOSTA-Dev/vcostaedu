<?php

/**
 * O MOODLE (Onda 3, Etapa 8): o cliente do web service, a cifra da chave e o glossário dos erros.
 *
 * O contrato (medido no Moodle 5.3 com o plugin local_vcostaedu; vale da 4.5 à 5.3): REST em
 * <endereço>/webservice/rest/server.php, com wstoken, wsfunction e moodlewsrestformat=json; as 17 funções de FUNCOES
 * (a 17ª, local_vcostaedu_find_users_by_email, desde a versão do plugin de 15/11/2026: os ids das contas de cada e-mail).
 *
 * O cliente: curl, POST, 3 segundos para conectar e 8 para responder (o envio nunca segura a tela), o certificado
 * conferido, sem seguir redirecionamento, a resposta até 2 MB. Cada chamada vai para o registro (adms_moodle_registro):
 * a função, a quem se refere, o resultado, o tempo e o código — nunca a chave, a senha, o link de entrada ou o corpo.
 */
final class VcMoodle
{
    public const FUNCOES = [
        'core_webservice_get_site_info', 'core_user_create_users', 'core_user_update_users', 'core_user_get_users_by_field',
        'core_user_get_users', 'core_course_get_categories', 'core_course_create_categories', 'core_course_get_courses_by_field',
        'core_course_create_courses', 'core_course_update_courses', 'enrol_manual_enrol_users', 'enrol_manual_unenrol_users',
        'core_enrol_get_enrolled_users', 'core_enrol_get_users_courses', 'gradereport_user_get_grade_items', 'local_vcostaedu_request_login_url',
        'local_vcostaedu_find_users_by_email',
    ];
    private const CONECTAR = 3;
    private const RESPONDER = 8;
    private const LIMITE = 2097152;

    /** @var callable|null quem registra cada chamada: fn(funcao, referencia, resultado, codigo, ms) */
    private $registrar;

    public function __construct(private string $url, private string $chave, ?callable $registrar = null)
    {
        $this->url = rtrim($url, '/');
        $this->registrar = $registrar;
    }

    /**
     * O endereço serve? Devolve '' (serve) ou o motivo. http só em endereço local (a mesma regra do resto do sistema).
     */
    public static function enderecoInvalido(string $url): string
    {
        $p = parse_url(trim($url));
        if (!$p || empty($p['scheme']) || empty($p['host']) || !in_array(strtolower($p['scheme']), ['http', 'https'], true)) {
            return 'O endereço precisa começar com https:// (por exemplo, https://moodle.instituicao.exemplo.invalid).';
        }
        if (isset($p['query']) || isset($p['fragment']) || isset($p['user']) || isset($p['pass'])) {
            return 'Informe só o endereço do Moodle, sem "?", "#" nem usuário.';
        }
        if (strtolower($p['scheme']) === 'http' && !self::local($p['host'])) {
            return 'Endereço sem https:// só é aceito numa instalação local. Use o endereço https:// do Moodle.';
        }
        return '';
    }

    public static function local(string $host): bool
    {
        $h = strtolower(trim($host, '[]'));
        /* a regra do resto do sistema (core/Verificacoes.php): só esta máquina; rede interna não conta como local */
        return in_array($h, ['localhost', '::1'], true) || str_starts_with($h, '127.') || str_ends_with($h, '.localhost');
    }

    /**
     * Uma chamada. Devolve ['ok' => bool, 'dados' => mixed, 'codigo' => string, 'mensagem' => string, 'ms' => int].
     * 'codigo' é o errorcode do Moodle, ou um dos da conexão: sem_resposta, tempo, certificado, redirecionamento,
     * http_<n>, resposta_invalida.
     */
    public function chamar(string $funcao, array $params = [], string $referencia = ''): array
    {
        $t = microtime(true);
        $r = ['ok' => false, 'dados' => null, 'codigo' => '', 'mensagem' => '', 'ms' => 0];
        $ch = curl_init($this->url . '/webservice/rest/server.php');
        $corpo = '';
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query(['wstoken' => $this->chave, 'wsfunction' => $funcao, 'moodlewsrestformat' => 'json'] + $params, '', '&'),
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_WRITEFUNCTION => static function ($c, $d) use (&$corpo) {
                $corpo .= $d;
                return strlen($corpo) > self::LIMITE ? 0 : strlen($d);
            },
            CURLOPT_CONNECTTIMEOUT => self::CONECTAR,
            CURLOPT_TIMEOUT => self::RESPONDER,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
            CURLOPT_USERAGENT => 'VCOSTAEDU',
        ]);
        $okCurl = curl_exec($ch);
        $erro = curl_errno($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        $r['ms'] = (int) round((microtime(true) - $t) * 1000);

        if ($okCurl === false || $erro) {
            $r['codigo'] = match ($erro) {
                CURLE_OPERATION_TIMEDOUT => 'tempo',
                35, 51, 58, 60, 77, 83 => 'certificado', /* os erros de TLS e de certificado do curl (o mesmo número em todas as versões) */
                default => 'sem_resposta',
            };
        } elseif ($http >= 300 && $http < 400) {
            $r['codigo'] = 'redirecionamento';
        } elseif ($http !== 200) {
            $r['codigo'] = 'http_' . $http;
        } else {
            $j = json_decode($corpo, true);
            if ($corpo !== 'null' && $j === null) {
                $r['codigo'] = 'resposta_invalida';
            } elseif (is_array($j) && isset($j['exception'])) {
                $r['codigo'] = (string) ($j['errorcode'] ?? 'erro');
                $r['mensagem'] = (string) ($j['message'] ?? '');
            } else {
                $r['ok'] = true;
                $r['dados'] = $j;
            }
        }
        if ($this->registrar) {
            ($this->registrar)($funcao, $referencia, $r['ok'] ? 'ok' : 'erro', $r['codigo'], $r['ms']);
        }
        return $r;
    }

    /* ---------------------------------------------------------------- a cifra da chave ------------------------------ */

    /**
     * O segredo da cifra: um arquivo gerado uma vez na pasta dos arquivos privados que a Config indica (PASTA_STORAGE,
     * fora da pasta publicada num servidor; negada à web pelo .htaccess). As duas aplicações (o painel grava a chave, o
     * portal pede o link de entrada) apontam para a mesma pasta, e uma instalação que veio por atualização não precisa
     * mudar a Config. Sem o arquivo de segredo, a chave guardada não decifra: é só colar a chave de novo.
     */
    private static function segredo(): string
    {
        $pasta = rtrim(str_replace('\\', '/', defined('PASTA_STORAGE') ? (string) PASTA_STORAGE : dirname(__DIR__) . '/storage'), '/') . '/segredos';
        $arq = $pasta . '/cifra.key';
        if (!is_file($arq)) {
            if (!is_dir($pasta) && !@mkdir($pasta, 0700, true) && !is_dir($pasta)) {
                throw new RuntimeException('a pasta dos arquivos privados não aceita o arquivo de segredo');
            }
            @file_put_contents($pasta . '/.htaccess', "Require all denied\nDeny from all\n");
            if (@file_put_contents($arq, bin2hex(random_bytes(32)), LOCK_EX) === false) {
                throw new RuntimeException('não foi possível gravar o arquivo de segredo');
            }
            @chmod($arq, 0600);
        }
        $s = trim((string) @file_get_contents($arq));
        if (strlen($s) < 64) {
            throw new RuntimeException('o arquivo de segredo está vazio');
        }
        return hash('sha256', 'vcostaedu-moodle|' . $s, true);
    }

    public static function cifrar(string $texto): string
    {
        $iv = random_bytes(12);
        $tag = '';
        $c = openssl_encrypt($texto, 'aes-256-gcm', self::segredo(), OPENSSL_RAW_DATA, $iv, $tag);
        return 'v1:' . base64_encode($iv . $tag . $c);
    }

    public static function decifrar(?string $cifrado): string
    {
        if (!$cifrado || !str_starts_with($cifrado, 'v1:')) {
            return '';
        }
        $b = base64_decode(substr($cifrado, 3), true);
        if ($b === false || strlen($b) < 29) {
            return '';
        }
        $t = openssl_decrypt(substr($b, 28), 'aes-256-gcm', self::segredo(), OPENSSL_RAW_DATA, substr($b, 0, 12), substr($b, 12, 16));
        return $t === false ? '' : $t;
    }

    /* ---------------------------------------------------------------- o glossário ---------------------------------- */

    /**
     * Cada código, em linguagem de uso: [o que aconteceu, o que fazer]. Com o Moodle, o problema costuma ser
     * configuração do lado dele.
     */
    public const GLOSSARIO = [
        'invalidtoken' => ['O Moodle não reconheceu a chave.', 'A chave foi trocada ou apagada no Moodle. Em "Conectar ao VCOSTAEDU", no Moodle, gere de novo e cole aqui.'],
        'accessexception' => ['O Moodle recusou o acesso ao serviço.', 'Confira no Moodle se os serviços web estão ligados (Administração do site → Recursos avançados) e se a função está no serviço do VCOSTAEDU.'],
        'nopermissions' => ['A conta do serviço não tem a permissão para isso no Moodle.', 'No Moodle, confira o papel da conta do serviço do VCOSTAEDU (o plugin cria a conta com as permissões certas). Se alguém as mudou, use o botão "Corrigir papel e serviço" na página do plugin, no Moodle.'],
        'invalidparameter' => ['O Moodle recusou os dados enviados.', 'Costuma ser um dado repetido (o nome de usuário ou o e-mail de outra pessoa no Moodle) ou inválido. Confira o cadastro e tente de novo.'],
        'invalidrecord' => ['O Moodle não achou o registro (a disciplina ou o usuário ligado pode ter sido apagado lá).', 'Confira no Moodle. Se a disciplina foi apagada, ligue a disciplina de novo ou deixe o VCOSTAEDU criar outra.'],
        'servicenotavailable' => ['O serviço do VCOSTAEDU está desligado no Moodle.', 'No Moodle, ligue o serviço do VCOSTAEDU (Administração do site → Servidor → Serviços externos).'],
        'webservicesdisabled' => ['Os serviços web estão desligados no Moodle.', 'No Moodle: Administração do site → Recursos avançados → ligue "Serviços web".'],
        'vcostaedu_usernotfound' => ['O aluno ainda não existe no Moodle.', 'Envie o aluno ao Moodle (Envios ao Moodle) e tente de novo.'],
        'vcostaedu_userdeleted' => ['A conta do aluno foi apagada no Moodle.', 'Recrie a conta no Moodle, ou peça a quem cuida do Moodle para restaurá-la.'],
        'vcostaedu_usersuspended' => ['A conta do aluno está suspensa no Moodle.', 'Quem cuida do Moodle reativa a conta (ou a matrícula foi trancada ou cancelada).'],
        'vcostaedu_usernotconfirmed' => ['A conta do aluno não está confirmada no Moodle.', 'Quem cuida do Moodle confirma a conta.'],
        'vcostaedu_usernologin' => ['A conta do aluno não pode entrar no Moodle (a autenticação dela está como "sem login").', 'No Moodle, mude a autenticação da conta para "Contas manuais".'],
        'vcostaedu_userisguest' => ['A conta é a de visitante do Moodle.', 'Ligue o aluno a uma conta própria.'],
        'vcostaedu_userisadmin' => ['A conta é de administrador do Moodle: o link de entrada não vale para ela.', 'Use uma conta de aluno.'],
        'vcostaedu_userisservice' => ['A conta é a do próprio serviço do VCOSTAEDU.', 'Ligue o aluno a uma conta própria.'],
        'vcostaedu_authdisabled' => ['A forma de entrada da conta está desligada no Moodle.', 'No Moodle, ligue "Contas manuais" em Plugins → Autenticação.'],
        'sem_resposta' => ['O Moodle não respondeu (o endereço não abre, ou o servidor está fora do ar).', 'Confira o endereço e se o Moodle abre no navegador. O que falta fica na fila e vai depois.'],
        'tempo' => ['O Moodle demorou demais para responder.', 'Pode ser o servidor do Moodle ocupado. O que falta fica na fila; use "Tentar de novo" mais tarde.'],
        'certificado' => ['O certificado do endereço https:// do Moodle não foi aceito.', 'Quem cuida do Moodle confere o certificado (vencido, de outro endereço ou não reconhecido).'],
        'redirecionamento' => ['O endereço levou para outro lugar (o Moodle está noutro endereço).', 'Confira o endereço do Moodle (com ou sem "www", com https://) e salve de novo.'],
        'resposta_invalida' => ['O endereço respondeu, mas não como um Moodle.', 'Confira se o endereço é o do Moodle (a página inicial dele).'],
        'aviso_email' => ['O Moodle gravou a inscrição, mas não conseguiu mandar o e-mail de aviso ("Message was not sent.").', 'Nada a fazer no VCOSTAEDU: a inscrição está certa. Quem cuida do Moodle confere o envio de e-mail dele.'],
        'sem_email' => ['O aluno não tem e-mail cadastrado: o Moodle exige um.', 'Cadastre o e-mail na ficha do aluno; o envio vai sozinho. Ou, em Conectar ao Moodle, escolha um modo com o e-mail da escola.'],
        'email_repetido' => ['O e-mail do cadastro do aluno é o mesmo de outro aluno (irmãos com o e-mail da família, por exemplo): a conta do Moodle é de uma pessoa só.', 'Cadastre um e-mail só do aluno na ficha; o envio vai sozinho. Ou, em Conectar ao Moodle, escolha um modo com o e-mail da escola.'],
        'sem_dominio' => ['O modo de e-mail usa o e-mail da escola, mas o domínio dele não foi informado.', 'Em Conectar ao Moodle, informe o domínio do e-mail da escola (por exemplo, alunos.escola.exemplo.invalid) e salve.'],
        'email_varias_contas' => ['O e-mail está em mais de uma conta do Moodle: o VCOSTAEDU não escolhe sozinho qual é a da pessoa.', 'Em Envios ao Moodle, na linha do envio, escolha a conta (o número de cada uma no Moodle aparece ali); o envio segue com ela.'],
        'conta_suspensa' => ['Ligado a uma conta que já existia no Moodle, mas que está SUSPENSA lá: a pessoa não entra no Moodle enquanto a conta não for reativada.', 'Quem cuida do Moodle reativa a conta (Administração do site → Usuários). Nada a fazer no VCOSTAEDU.'],
        'nao_conectado' => ['O Moodle não está conectado.', 'O Administrador conecta em Integrações → Conectar ao Moodle.'],
        'sem_disciplina' => ['A disciplina ainda não está ligada a uma do Moodle.', 'Com "vincular", informe na tela da disciplina qual é a disciplina do Moodle; com "criar", ela vai sozinha.'],
        'sem_categoria' => ['Falta escolher a categoria do Moodle onde as disciplinas são criadas.', 'Em Conectar ao Moodle, escolha (ou crie) a categoria.'],
    ];

    public static function explicar(string $codigo): array
    {
        if (isset(self::GLOSSARIO[$codigo])) {
            return self::GLOSSARIO[$codigo];
        }
        if (str_starts_with($codigo, 'http_')) {
            return ['O endereço do Moodle respondeu com um erro (' . substr($codigo, 5) . ').', 'Confira se o endereço é o do Moodle e se ele abre no navegador.'];
        }
        return ['O Moodle recusou o pedido (' . $codigo . ').', 'Veja o código no Moodle ou peça ajuda a quem cuida dele.'];
    }
}
