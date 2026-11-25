<?php

/**
 * AS VERIFICAÇÕES DE SEGURANÇA (Onda 3, Etapa 6) — uma implementação só, para a tela (Manutenção → Verificações de
 * segurança, só o Administrador) e para a linha de comando (database/ferramentas/implantacao/verificar_seguranca.php).
 *
 * Nada aqui executa programa: lê a configuração (as constantes já carregadas), o PHP (ini_get), o sistema de arquivos e
 * o banco; e, na tela, pede pela web um arquivo de cada pasta protegida (o item "Pastas protegidas"). Cada item diz OK,
 * ALERTA ou FALHA e, quando não é OK, o que fazer — em português de uso.
 *
 * Os itens: o HTTPS; a pasta dos arquivos privados (fora da pasta publicada, com a proteção dela); o instalador
 * recusando (uma instalação pronta não volta a instalar); os erros do PHP fora da tela; o modo estrito do banco; a
 * proteção do histórico (os gatilhos, que pedem o privilégio TRIGGER); o e-mail de envio; as permissões acima do limite
 * de cada ação (core/Teto.php); na escola, o portal do responsável passando pela barreira (core/BarreiraFamilia.php);
 * as pastas protegidas recusando pela web (Onda 8, 2.10: num servidor que não lê o .htaccess, como o Nginx sem a
 * configuração de docs/nginx/, elas responderiam).
 */
require_once __DIR__ . '/Banco.php';
require_once __DIR__ . '/Teto.php';

final class VcVerificacoes
{
    public const GATILHOS = ['rm_historico_emissoes_imutavel', 'rm_historico_emissoes_sem_apagar', 'rm_historico_emissao_vias_imutavel', 'rm_historico_emissao_vias_sem_apagar',
        'rm_media_fechamento_resultados_imutavel', 'rm_media_fechamento_resultados_sem_apagar'];

    /**
     * @param PDO    $pdo   a conexão do sistema
     * @param string $raiz  a pasta do sistema
     * @param bool   $https a requisição atual chegou por HTTPS (na linha de comando, o que a configuração diz)
     * @return array<int, array{codigo:string, titulo:string, situacao:string, detalhe:string, fazer:string}>
     */
    public static function todas(PDO $pdo, string $raiz, bool $https): array
    {
        $raiz = rtrim(str_replace('\\', '/', $raiz), '/');
        $host = strtolower(trim((string) parse_url(defined('URL') ? (string) URL : '', PHP_URL_HOST), '[]'));
        $local = in_array($host, ['localhost', '::1'], true) || str_starts_with($host, '127.') || str_ends_with($host, '.localhost');
        $i = [];

        /* 1. o HTTPS */
        $exige = defined('EXIGIR_HTTPS') && EXIGIR_HTTPS;
        if ($exige && $https) {
            $i[] = self::item('https', 'HTTPS', 'OK', 'O sistema exige HTTPS, e esta conexão é segura.');
        } elseif ($exige) {
            $i[] = self::item('https', 'HTTPS', 'ALERTA', 'O sistema exige HTTPS, mas esta conexão não chegou por HTTPS (um proxy na frente?).', 'Confira com quem cuida do servidor se o endereço público abre em https://.');
        } else {
            $i[] = self::item('https', 'HTTPS', $local ? 'ALERTA' : 'FALHA', 'O sistema não exige HTTPS: as senhas e os dados podem passar sem proteção.' . ($local ? ' Normal em instalação local.' : ''),
                'Num servidor publicado, peça o certificado (HTTPS) ao provedor e ligue a exigência de HTTPS na configuração do painel e do portal.');
        }

        /* 2. a pasta dos arquivos privados */
        $st = defined('PASTA_STORAGE') ? rtrim(str_replace('\\', '/', (string) PASTA_STORAGE), '/') : $raiz . '/storage';
        $stReal = is_dir($st) ? rtrim(str_replace('\\', '/', (string) realpath($st)), '/') : '';
        if ($stReal === '') {
            $i[] = self::item('arquivos', 'Arquivos privados', 'FALHA', 'A pasta dos arquivos privados (os PDFs do histórico e as assinaturas) não existe.', 'Crie a pasta indicada na configuração e dê ao servidor permissão de escrita nela.');
        } elseif (str_starts_with($stReal . '/', $raiz . '/')) {
            $ht = is_file($stReal . '/.htaccess') ? (string) @file_get_contents($stReal . '/.htaccess') : '';
            $protegida = stripos($ht, 'deny') !== false || stripos($ht, 'denied') !== false;
            $i[] = self::item('arquivos', 'Arquivos privados', $local ? 'ALERTA' : ($protegida ? 'ALERTA' : 'FALHA'),
                'A pasta dos arquivos privados está dentro da pasta publicada pelo servidor' . ($protegida ? ': protegida só pelo .htaccess (num servidor que não lê o .htaccess, os PDFs ficariam abertos).' : ', sem a proteção do .htaccess.') . ($local ? ' Normal em instalação local.' : ''),
                'Num servidor publicado, mova a pasta para fora da pasta publicada e indique o caminho novo na configuração do painel e do portal.');
        } else {
            $i[] = self::item('arquivos', 'Arquivos privados', 'OK', 'A pasta dos arquivos privados fica fora da pasta publicada pelo servidor.');
        }

        /* 3. o instalador recusando */
        $configs = is_file($raiz . '/painel/core/Config.php') && is_file($raiz . '/portal/core/Config.php');
        $trava = is_file($raiz . '/instalar/.htaccess');
        if (!is_dir($raiz . '/instalar')) {
            $i[] = self::item('instalador', 'Instalador', 'OK', 'A pasta do instalador foi apagada.');
        } elseif ($configs && $trava) {
            $i[] = self::item('instalador', 'Instalador', 'OK', 'O instalador recusa: a instalação está pronta, e uma segunda instalação não começa por cima dela.');
        } else {
            $i[] = self::item('instalador', 'Instalador', 'FALHA', 'O instalador pode não estar recusando (falta ' . ($configs ? 'a proteção da pasta dele' : 'a configuração') . ').', 'Restaure o arquivo instalar/.htaccess do pacote, ou apague a pasta instalar/ (ela não é mais necessária).');
        }

        /* 4. os erros do PHP fora da tela */
        $mostra = (string) ini_get('display_errors');
        if (in_array(strtolower($mostra), ['1', 'on', 'stdout', 'true'], true)) {
            $i[] = self::item('erros', 'Erros do PHP', $local ? 'ALERTA' : 'FALHA', 'O PHP mostra os erros na tela (eles podem revelar caminhos e detalhes do servidor).' . ($local ? ' Normal em instalação local.' : ''),
                'Desligue display_errors no php.ini do servidor (os erros continuam no registro do servidor).');
        } else {
            $i[] = self::item('erros', 'Erros do PHP', 'OK', 'Os erros do PHP não aparecem na tela (ficam no registro do servidor).');
        }

        /* 5. o modo do banco */
        $modo = VcBanco::modoEmUso($pdo);
        $i[] = VcBanco::modoCompleto($modo)
            ? self::item('banco', 'Modo do banco', 'OK', 'O banco recusa dado inválido em vez de gravar pela metade (o modo estrito, fixado pelo sistema).')
            : self::item('banco', 'Modo do banco', 'FALHA', 'O banco não ficou no modo estrito que o sistema fixa.', 'Confira com o provedor se o usuário do banco pode mudar o modo da sessão (SET SESSION sql_mode).');

        /* 6. a proteção do histórico (os gatilhos) */
        $ha = $pdo->query("SELECT TRIGGER_NAME FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = DATABASE()")->fetchAll(PDO::FETCH_COLUMN);
        $faltam = array_values(array_diff(self::GATILHOS, $ha));
        $i[] = $faltam === []
            ? self::item('gatilhos', 'Proteção do histórico', 'OK', 'Os documentos emitidos e os fechamentos guardados não mudam nem são apagados: a proteção (os 6 gatilhos) está no banco.')
            : self::item('gatilhos', 'Proteção do histórico', 'ALERTA', 'Faltam ' . count($faltam) . ' dos 6 gatilhos: o histórico emitido fica protegido só pelo sistema.',
                'Peça ao provedor o privilégio TRIGGER para o usuário do banco e use Manutenção → Atualizar o sistema (a etapa dos gatilhos é aplicada).');

        /* 7. o e-mail de envio */
        $smtp = $pdo->query("SELECT host, email FROM adms_confs_emails ORDER BY id LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        $i[] = $smtp && trim((string) $smtp['host']) !== ''
            ? self::item('email', 'E-mail de envio', 'OK', 'O e-mail de envio está configurado (os links de acesso e de nova senha saem por e-mail).')
            : self::item('email', 'E-mail de envio', 'ALERTA', 'O e-mail de envio não está configurado: os links de acesso e de nova senha não saem por e-mail.', 'Configure em Configurações → E-mail (o servidor de envio da instituição).');

        /* 8. as permissões acima do limite de cada ação */
        if (VcTeto::temAcoes($pdo)) {
            $acima = VcTeto::acimaDoTeto($pdo);
            $i[] = $acima === []
                ? self::item('permissoes', 'Permissões', 'OK', 'Nenhum perfil recebe uma ação acima do que pode (Equipe → Perfis de acesso).')
                : self::item('permissoes', 'Permissões', 'FALHA', count($acima) . ' permissão(ões) acima do perfil: ' . implode('; ', array_slice($acima, 0, 3)) . '. O sistema já as recusa.',
                    'Peça a quem cuida do sistema para corrigir a permissão (a atualização do sistema não as dá).');
        }

        /* 9. Onda 6, Etapa 6: o perfil criado pela tela numa instalação anterior (sem papel): ele segue só a matriz dele,
           sem o limite das ações (DUVIDAS 146) — fica como atenção, para alguém conferir as permissões dele */
        try {
            $semPapel = $pdo->query("SELECT nome FROM adms_niveis_acessos WHERE papel IS NULL OR papel = '' ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);
        } catch (\Throwable $e) {
            $semPapel = [];
        }
        $i[] = $semPapel === []
            ? self::item('perfis', 'Perfis criados pela tela', 'OK', 'Todo perfil de acesso é um dos perfis do sistema, com o limite das ações de cada um.')
            : self::item('perfis', 'Perfis criados pela tela', 'ALERTA', count($semPapel) . ' perfil(is) criado(s) pela tela numa instalação anterior: ' . implode(', ', array_slice($semPapel, 0, 5))
                . '. Ele(s) segue(m) só as permissões marcadas para ele(s), sem o limite das ações de um perfil do sistema.',
                'Em Equipe → Perfis de acesso, confira as permissões de cada um; se ele faz o papel de um perfil do sistema, passe as pessoas para esse perfil.');

        /* 10. Onda 7, Etapa 5 (só na escola): toda rota do portal do responsável passa pelo ponto único da barreira */
        try {
            $tipo = (string) $pdo->query("SELECT valor FROM adms_instalacao_estado WHERE chave = 'instituicao.tipo' LIMIT 1")->fetchColumn();
        } catch (\Throwable $e) {
            $tipo = '';
        }
        if ($tipo === 'escola') {
            require_once __DIR__ . '/BarreiraFamilia.php';
            $fora = VcBarreiraFamilia::problemas($pdo, $raiz);
            $i[] = $fora === []
                ? self::item('familia', 'Portal do responsável', 'OK', 'Toda tela do portal do responsável confere, a cada requisição, se aquela pessoa pode ver aquele aluno naquele ano (o ponto único da barreira).')
                : self::item('familia', 'Portal do responsável', 'FALHA', 'O portal do responsável tem ' . count($fora) . ' ponto(s) fora da barreira: ' . implode('; ', array_slice($fora, 0, 3)) . '.',
                    'Não libere o portal para as famílias até corrigir: restaure os arquivos do portal do pacote da versão instalada.');
        }

        /* 11. Onda 8, correção, segunda leva, 2.10 (D-96): as pastas protegidas, pedidas pela web */
        $i[] = self::pastasProtegidas($pdo, $raiz, $local);
        return $i;
    }

    /**
     * Onda 8, correção, segunda leva, 2.10 (D-96): a pasta (como o .htaccess dela a protege) => um arquivo dela, pedido
     * pela web. Entra só o arquivo que existe nesta instalação (o pacote não leva tests/ nem docs/api/).
     */
    public const PASTAS_PROTEGIDAS = [
        'core/' => 'core/Banco.php',
        'bin/' => 'bin/migrar.php',
        'database/' => 'database/migrations/0001_estrutura.sql',
        'docs/api/' => 'docs/api/openapi.json',
        'tests/' => 'tests/tabler/lib.js',
        'painel/core/' => 'painel/core/Config.php',
        'painel/app/' => 'painel/app/adms/Models/AdmsBotao.php',
        'portal/core/' => 'portal/core/Config.php',
        'portal/app/' => 'portal/app/adms/Controllers/Home.php',
        'storage/' => 'storage/.htaccess',
        '.git/' => '.git/HEAD',
        'os arquivos .md' => 'README.md',
    ];
    public const ESTADO_PASTAS = 'seguranca.pastas_protegidas';

    /**
     * Na tela (o endereço da instalação conhecido): pede cada arquivo pela web; o que responde (2xx) é pasta aberta. O
     * resultado fica guardado, e a linha de comando (que não conhece o endereço) mostra a última conferência da tela.
     */
    private static function pastasProtegidas(PDO $pdo, string $raiz, bool $local): array
    {
        $titulo = 'Pastas protegidas';
        if (PHP_SAPI === 'cli' || !defined('URL')) {
            $g = null;
            try {
                $st = $pdo->prepare('SELECT valor FROM adms_instalacao_estado WHERE chave = ?');
                $st->execute([self::ESTADO_PASTAS]);
                $g = json_decode((string) $st->fetchColumn(), true);
            } catch (\Throwable $e) {
                $g = null;
            }
            if (!is_array($g) || !isset($g['situacao'], $g['detalhe'], $g['quando'])) {
                return self::item('pastas', $titulo, 'ALERTA', 'As pastas protegidas ainda não foram pedidas pela web (a conferência é feita pela tela, que conhece o endereço do sistema).',
                    'Abra Manutenção → Verificações de segurança no painel, ou rode database/ferramentas/implantacao/varredura_exposicao.php com o endereço do sistema.');
            }
            return self::item('pastas', $titulo, (string) $g['situacao'], 'Na última conferência pela tela (' . date('d/m/Y H:i', strtotime((string) $g['quando'])) . '): ' . $g['detalhe'], (string) ($g['fazer'] ?? ''));
        }
        $base = rtrim((string) URL, '/') . '/';
        $abertas = [];
        $recusaram = 0;
        $semResposta = [];
        foreach (self::PASTAS_PROTEGIDAS as $pasta => $arquivo) {
            if (!is_file($raiz . '/' . $arquivo)) {
                continue;
            }
            $codigo = self::codigoHttp($base . $arquivo, $local);
            if ($codigo >= 200 && $codigo < 300) {
                $abertas[] = $pasta;
            } elseif ($codigo === 0) {
                $semResposta[] = $pasta;
            } else {
                $recusaram++;
            }
        }
        $fazerServidor = 'No Apache, ligue o mod_rewrite e deixe a pasta do sistema ler os .htaccess (AllowOverride All); no Nginx, use a configuração de exemplo docs/nginx/vcostaedu.conf (o guia: docs/IMPLANTACAO.md). Não abra o sistema para a instituição até corrigir.';
        if ($abertas) {
            $item = self::item('pastas', $titulo, 'FALHA', count($abertas) . ' pasta(s) protegida(s) respondem pela web: ' . implode(', ', $abertas) . ' — o servidor não está aplicando a proteção (o Nginx, por exemplo, não lê o .htaccess).', $fazerServidor);
        } elseif ($recusaram === 0) {
            $item = self::item('pastas', $titulo, 'ALERTA', 'Não deu para pedir as pastas protegidas pela web daqui (o servidor não alcançou o próprio endereço).',
                'Rode database/ferramentas/implantacao/varredura_exposicao.php com o endereço do sistema, de outra máquina.');
        } else {
            $item = self::item('pastas', $titulo, 'OK', "As {$recusaram} pastas protegidas pedidas pela web recusam (o código, a configuração, as etapas do banco e os arquivos privados)."
                . ($semResposta ? ' Sem resposta: ' . implode(', ', $semResposta) . '.' : ''));
        }
        try {
            $pdo->prepare('INSERT INTO adms_instalacao_estado (chave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)')
                ->execute([self::ESTADO_PASTAS, json_encode(['quando' => date('Y-m-d H:i:s'), 'situacao' => $item['situacao'], 'detalhe' => $item['detalhe'], 'fazer' => $item['fazer']], JSON_UNESCAPED_UNICODE)]);
        } catch (\Throwable $e) {
            /* sem guardar: a linha de comando dirá que ainda não foi conferido */
        }
        return $item;
    }

    /** O código HTTP de um GET, sem seguir redirecionamento (0: sem resposta). Na instalação local, aceita o certificado próprio. */
    private static function codigoHttp(string $url, bool $local): int
    {
        if (function_exists('curl_init')) {
            $c = curl_init($url);
            curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_NOBODY => false, CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => 4, CURLOPT_CONNECTTIMEOUT => 3,
                CURLOPT_USERAGENT => 'VCOSTAEDU-verificacoes', CURLOPT_RANGE => '0-0', CURLOPT_SSL_VERIFYPEER => !$local, CURLOPT_SSL_VERIFYHOST => $local ? 0 : 2]);
            curl_exec($c);
            $codigo = (int) curl_getinfo($c, CURLINFO_HTTP_CODE);
            curl_close($c);
            return $codigo;
        }
        $ctx = stream_context_create(['http' => ['method' => 'GET', 'timeout' => 4, 'follow_location' => 0, 'ignore_errors' => true, 'user_agent' => 'VCOSTAEDU-verificacoes'],
            'ssl' => ['verify_peer' => !$local, 'verify_peer_name' => !$local]]);
        $r = @file_get_contents($url, false, $ctx, 0, 1);
        if ($r === false && empty($http_response_header)) {
            return 0;
        }
        return preg_match('#^HTTP/\S+\s+(\d{3})#', (string) ($http_response_header[0] ?? ''), $m) ? (int) $m[1] : 0;
    }

    /** As contagens: [OK, ALERTA, FALHA]. */
    public static function resumo(array $itens): array
    {
        $c = ['OK' => 0, 'ALERTA' => 0, 'FALHA' => 0];
        foreach ($itens as $x) {
            $c[$x['situacao']]++;
        }
        return $c;
    }

    private static function item(string $codigo, string $titulo, string $situacao, string $detalhe, string $fazer = ''): array
    {
        return ['codigo' => $codigo, 'titulo' => $titulo, 'situacao' => $situacao, 'detalhe' => $detalhe, 'fazer' => $fazer];
    }
}
