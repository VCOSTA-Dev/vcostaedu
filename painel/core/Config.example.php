<?php

namespace Core;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/*
 * Configuração da VCOSTAEDU — painel.
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

        define('CONTROLLER', 'Login');
        define('METODO', 'acesso');
        define('CONTROLLERERRO', 'Erro');
        define('VERSAODEV', '1.18.4');

        /* [SERVIDOR] Banco. */
        define('HOST', 'localhost');
        define('USER', 'usuario_do_banco');
        define('PASS', 'senha_do_banco');
        define('DBNAME', 'vcostaedu');

        /* [SERVIDOR] Nome da instituição nos documentos e cabeçalhos (boletim,
           rodapés). Os dados completos da IES (mantenedora, CNPJ, atos) são da E6. */
        define('NOME_INSTITUICAO', 'VCOSTAEDU');

        /* [SERVIDOR] E6c (D-202): pasta dos arquivos PRIVADOS (PDFs do histórico emitido e imagens de
           assinatura), com barra no fim. Padrão: storage/ do projeto, negado à web por storage/.htaccess.
           No servidor, aponte para FORA da raiz pública (ex.: '/home/conta/vcostaedu_storage/') e crie
           nela as subpastas historico/emissoes/ e historico/assinaturas/ (ver README, "Arquivos privados"). */
        define('PASTA_STORAGE', dirname(__DIR__, 2) . '/storage/');

        /* [SERVIDOR] E7 (D-220): 'producao' | 'homologacao' | 'demonstracao'. Só 'demonstracao' deixa limpar e
           montar a demonstração (limpar_demo.php, seed_demo_processar.php). Emissões guardam o ambiente e,
           fora de 'producao', saem com a tarja do ambiente. */
        define('AMBIENTE', 'producao');
        /* [SERVIDOR] Onda 2: o endereço público do código-fonte desta instalação (a AGPL-3.0 pede que quem oferece o
           sistema pela rede ofereça também o código). Aparece no rodapé e na página Sobre como "Código-fonte";
           vazio = o link não aparece. Ex.: 'https://exemplo.invalid/vcostaedu'. */
        define('URL_CODIGO_FONTE', '');

        /* [LOCAL] 1.18.0 (D-471): só num banco de DEMONSTRAÇÃO separado (ex.: o dos vídeos dos guias), com AMBIENTE =
           'demonstracao': o MESMO nome do DBNAME. Declarado, o seed da demonstração aceita esse banco além do oficial.
           Em produção, não defina. */
        // define('BANCO_DEMONSTRACAO', 'vcostaedu_demo');

        /* [SERVIDOR] E-mail de suporte exibido pelo sistema. */
        define('EMAILADM', 'suporte@exemplo.invalid');

        /* Financeiro fora do escopo da VCOSTAEDU: o token do VCOSTA Pay fica vazio.
           Só a ponte financeira (removida na E2a) o lê. */
        define('VCOSTAPAY_TOKEN', '');

        /* Ano letivo corrente (D-06: ano int). */
        define('ANO_ATUAL', '2026');

        setlocale(LC_TIME, 'pt_BR', 'pt_BR.utf-8', 'pt_BR.utf-8', 'portuguese');
        date_default_timezone_set('America/Sao_Paulo');
    }
}
