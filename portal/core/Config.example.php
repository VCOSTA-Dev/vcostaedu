<?php

namespace Core;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/*
 * Configuração da VCOSTAEDU — portal.
 *
 * ⚠️ Este arquivo tem credencial e NÃO vai para o git. O modelo versionado é
 * core/Config.example.php. Na subida ao servidor, só os campos marcados com
 * [SERVIDOR] mudam (ver README.md, "Subir no servidor").
 */
abstract class Config
{

    protected function configAdm()
    {
        /* [SERVIDOR] 1.9.1: o ENDEREÇO (protocolo, host e caminho; constantes URL, URLACAD, URLADM e HTTPS_ATIVO)
           é montado por core/Requisicao.php, na raiz do projeto, com os parâmetros abaixo. Nada de endereço aqui.
           EXIGIR_HTTPS = true redireciona todo acesso HTTP para HTTPS e liga o HSTS — no servidor, deixe true. */
        define('EXIGIR_HTTPS', true);
        /* Onda 6, Etapa 6: a sessão sem uso por mais destes minutos pede para entrar de novo (0 desliga). Padrão: 120. */
        define('SESSAO_INATIVA_MINUTOS', 120);
        /* [SERVIDOR] E8 (D-236) e 1.9.1: IPs ou redes (CIDR) dos proxies/balanceadores/túneis CONFIÁVEIS na frente do
           servidor, separados por espaço. Só quando o pedido vem de um deles (REMOTE_ADDR) valem o X-Forwarded-For
           (IP do cliente: o primeiro não confiável, da direita para a esquerda) e o X-Forwarded-Proto (se o usuário
           está em HTTPS; é o que evita o laço de redirecionamento quando o proxy termina o HTTPS). Vazio = nenhum
           cabeçalho de proxy é aceito. Ex.: '10.0.0.5 10.0.1.0/24'. */
        define('PROXIES_CONFIAVEIS', '');
        /* [SERVIDOR] 1.9.1: hosts aceitos no endereço do sistema, separados por espaço; '*.dominio' aceita qualquer
           subdomínio. Um Host fora da lista vira o PRIMEIRO host da lista (sem curinga): links de e-mail (senha), QR
           e endereço de verificação do histórico nunca saem com um host forjado. Entrada sem porta aceita qualquer
           porta; para fixar a porta, escreva host:porta. Vazio = aceita o Host da requisição: só desenvolvimento,
           NÃO recomendado em produção. Ex.: 'escola.exemplo.invalid'. */
        define('HOSTS_PERMITIDOS', 'escola.exemplo.invalid');
        /* [SERVIDOR] 1.9.1: caminho do sistema no domínio, com barras ('/' na raiz, '/vcostaedu/' em subpasta). Vazio =
           descoberto sozinho pela localização da pasta (funciona em subpasta, na raiz de um vhost e por Alias). */
        define('CAMINHO_BASE', '');
        /* [SERVIDOR] E7: origens (https://host[:porta]) que podem mostrar o PORTAL em iframe — o Moodle. Separe por
           espaço. Vazio = o portal só pode ser embutido por ele mesmo. Com origem e HTTPS, o cookie de sessão
           vira SameSite=None (e Partitioned, se COOKIE_PARTICIONADO). Ex.: 'https://moodle.exemplo.invalid'. */
        define('ORIGENS_PERMITIDAS_IFRAME', '');
        /* [SERVIDOR] E8 (D-238): com origem de iframe e HTTPS, o cookie da sessão leva Partitioned (CHIPS): a sessão
           dentro do Moodle sobrevive ao bloqueio de cookies de terceiros, mas fica separada da sessão numa aba
           própria. Portal num SUBDOMÍNIO do domínio do Moodle (mesmo site): pode desligar (false). Sem origem de
           iframe, não tem efeito. */
        define('COOKIE_PARTICIONADO', true);

        define('CONTROLLER', 'Login');
        define('METODO', 'acesso');
        define('CONTROLLERERRO', 'Erro');
        define('VERSAODEV', '1.18.4');

        /* [SERVIDOR] Banco. */
        define('HOST', 'localhost');
        define('USER', 'usuario_do_banco');
        define('PASS', 'senha_do_banco');
        define('DBNAME', 'vcostaedu');
        /* D-131 (1.103.0, C6.1): a porta do banco (3306, a padrão do MySQL e do MariaDB) */
        define('PORTA', 3306);

        /* [SERVIDOR] Nome da instituição nos documentos e cabeçalhos (boletim,
           rodapés). Os dados completos da IES (mantenedora, CNPJ, atos) são da E6. */
        define('NOME_INSTITUICAO', 'VCOSTAEDU');

        /* [SERVIDOR] E-mail de suporte exibido pelo sistema. */
        define('EMAILADM', 'suporte@exemplo.invalid');

        /* [SERVIDOR] E6 (D-376): o portal emite o ATESTADO do aluno com o motor do painel. Estas duas constantes
           têm de ser IGUAIS às do painel/core/Config.php: a pasta dos arquivos privados (os PDFs emitidos) e o
           ambiente (vai na emissão e na tarja do PDF). */
        define('PASTA_STORAGE', dirname(__DIR__, 2) . '/storage/');
        define('AMBIENTE', 'producao');
        /* [SERVIDOR] Onda 2: o endereço público do código-fonte desta instalação (a AGPL-3.0 pede que quem oferece o
           sistema pela rede ofereça também o código). Aparece no rodapé e na página Sobre como "Código-fonte";
           vazio = o link não aparece. Ex.: 'https://exemplo.invalid/vcostaedu'. */
        define('URL_CODIGO_FONTE', '');


        setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'pt_BR.utf-8', 'portuguese');
        date_default_timezone_set('America/Sao_Paulo');
    }
}
