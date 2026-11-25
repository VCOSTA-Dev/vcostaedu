<?php
/*
 * O MODELO DA CONFIGURAÇÃO do site de exemplo. Copie para config.local.php (fora do git) e preencha (o LEIA-ME diz
 * como). A chave da interface fica SÓ aqui, no servidor do site: ela nunca vai para o navegador.
 */
return [
    /* o endereço da interface de integração da plataforma (o endereço do sistema seguido de /api/v1) */
    'api' => 'https://plataforma.exemplo.invalid/api/v1',
    /* a chave criada em Integrações → Chaves de API, com o limite mínimo: Alunos, Matrículas e Enturmação — gravar */
    'chave' => '',
    /* a entrada do portal do aluno (para quem já criou a senha) */
    'portal' => 'https://plataforma.exemplo.invalid/portal/login/acesso',
    /* o nome da instituição fictícia */
    'instituicao' => 'Instituto Ventoalto',
    /* o destino da inscrição: na escola, a turma (o id de GET /turmas); no ensino superior, o curso, o ano e as ofertas */
    'destino' => ['turma_id' => 0],
    // 'destino' => ['curso_id' => 1, 'ano' => 2026, 'ofertas' => [10, 11, 12, 13]],
    /* o primeiro número de matrícula do contador da demonstração */
    'matricula_inicio' => 970001,
    /* um aviso opcional, mostrado perto do formulário (por exemplo, que é uma demonstração e quando os dados são
       apagados). Vazio: nada aparece. */
    'aviso' => '',
    /* o limite contra abuso: quantas inscrições novas um mesmo endereço faz na janela. Uma sala inteira atrás da mesma
       rede pede um teto maior (por exemplo, 300). */
    'limite_por_endereco' => 10,
    'janela_minutos' => 10,
    /* os proxies confiáveis (endereços ou faixas CIDR) na frente do site: só atrás deles o endereço real vem do
       X-Forwarded-For. Vazio: vale o endereço da conexão. */
    'proxies_confiaveis' => [],
    /* DESLIGADO por padrão. Ligado, a confirmação mostra o botão "Criar minha senha agora", com o link do primeiro acesso
       que a plataforma devolve (além do e-mail de boas-vindas). O que se perde: a inscrição deixa de confirmar que o
       e-mail é da pessoa. Serve para demonstrações ao vivo, em que o e-mail pode atrasar (o LEIA-ME explica). */
    'botao_criar_senha' => false,
];
