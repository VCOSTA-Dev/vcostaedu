<?php
/*
 * O MODELO DA CONFIGURAÇÃO do portal de matrícula de exemplo. Copie para config.local.php (fora do git) e preencha.
 * A chave da API fica SÓ aqui, no servidor do exemplo: ela nunca vai para o navegador.
 */
return [
    /* o endereço da API do VCOSTAEDU (o do sistema seguido de /api/v1) */
    'api' => 'https://escola.exemplo.invalid/api/v1',
    /* a chave criada em Integrações → Chaves de API, com o limite mínimo: Alunos, Matrículas e Enturmação — consultar e
       gravar (e nada mais) */
    'chave' => '',
    /* o nome que aparece no alto da página */
    'instituicao' => 'Instituição de exemplo',
    /* os cursos que o portal oferece (o id do curso no VCOSTAEDU, o ano letivo e a forma de ingresso da matrícula) */
    'cursos' => [
        ['id' => 1, 'nome' => 'Curso de exemplo', 'ano' => 2026, 'forma_ingresso_id' => 1],
    ],
    /* a faixa dos números de matrícula (RA) que o exemplo gera: cada instituição tem a sua regra */
    'ra_inicio' => 980000,
    'ra_fim' => 989999,
];
