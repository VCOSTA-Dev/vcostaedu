<?php
/* GERADO por database/ferramentas/guias/gerar_guias.php a partir de docs/guia/tarefas/ — não editar. */
if (!defined('VCOSTAEDU')) { header('Location: /'); die('Erro: Página não encontrada!'); }
return array (
  'versao' => '1.102.0',
  'gerado' => '25/11/2026',
  'guias' => 
  array (
    'acompanhar-notas-e-resultado' => 
    array (
      'titulo' => 'Acompanhar notas, frequência e resultado',
      'resumo' => 'A média parcial da disciplina aberta e o que conta no encerramento.',
      'perfis' => 
      array (
        0 => 'professor',
        1 => 'secretaria',
        2 => 'administrador',
      ),
      'ordem' => 33,
      'entrou' => '1.2.0',
      'mudou' => '1.8.0',
      'comece' => 
      array (
        'professor' => 6,
        'secretaria' => 11,
      ),
      'relacionados' => 
      array (
        0 => 'lancar-notas',
        1 => 'lancar-faltas',
        2 => 'encerrar-uma-disciplina',
      ),
      'telas' => 
      array (
        0 => 'disciplinas/visualizar',
        1 => 'medias/final',
      ),
      'imagens' => 
      array (
        0 => 'professor_10_notas_e_resultado_parcial.png',
      ),
      'tipo' => 'escola,superior',
      'video' => 
      array (
        'arquivo' => 'acompanhar-notas-e-resultado.mp4',
        'poster' => 'acompanhar-notas-e-resultado.jpg',
        'legenda' => 'acompanhar-notas-e-resultado.vtt',
        'capitulos_vtt' => 'acompanhar-notas-e-resultado.capitulos.vtt',
        'duracao' => 54.8,
        'versao' => '1.42.0',
        'capitulos' => 
        array (
          0 => 
          array (
            'inicio' => 2.0,
            'titulo' => 'A média parcial',
          ),
          1 => 
          array (
            'inicio' => 21.1,
            'titulo' => 'A conta e as regras',
          ),
          2 => 
          array (
            'inicio' => 33.4,
            'titulo' => 'No encerramento, sem nota conta 0',
          ),
        ),
        'transcricao' => 'Na disciplina, clique em "Notas e resultado", na barra do topo.
Disciplina aberta: a média é PARCIAL — só as atividades que já têm nota entram na conta.
Em cada linha: uma coluna por atividade (— é sem nota), a média com a etiqueta "parcial", faltas e frequência.
Depois, o resultado ("Em curso" enquanto a disciplina está aberta) e o botão Boletim. Abaixo do mínimo, em vermelho.
"conta", ao lado da média, abre a memória de cálculo do aluno. As regras de cálculo estão no pé da tela.
"Atualizar lista": um aluno enturmado depois das atividades ganha as linhas de nota. A enturmação já faz isso.
Antes de encerrar, confira se todas as notas foram lançadas. Veja a prévia do encerramento:
O Trabalho final sem nota entra com 0: a média parcial 9,0 vira 4,5, "Reprovado por nota".
Aprovado exige média E frequência mínimas; senão, reprovado por nota, por frequência ou pelas duas.',
      ),
    ),
    'ano-letivo-passo-a-passo' => 
    array (
      'titulo' => 'O ano letivo, do começo ao fim',
      'resumo' => 'O guia mestre: a sequência de uso no ano, quem faz cada passo e o guia de cada um.',
      'perfis' => 
      array (
        0 => 'administrador',
        1 => 'secretaria',
      ),
      'ordem' => 3,
      'entrou' => '1.6.0',
      'mudou' => '1.8.0',
      'comece' => 
      array (
        'administrador' => 3,
        'secretaria' => 3,
      ),
      'relacionados' => 
      array (
        0 => 'montar-a-matriz',
        1 => 'gerar-a-oferta-do-ano',
        2 => 'cadastrar-aluno-e-enviar-link',
        3 => 'vincular-e-enturmar',
        4 => 'encerrar-uma-disciplina',
        5 => 'emitir-historico-final',
      ),
      'telas' => 
      array (
      ),
      'imagens' => 
      array (
      ),
      'tipo' => '',
      'video' => 
      array (
        'arquivo' => 'ano-letivo-passo-a-passo.mp4',
        'poster' => 'ano-letivo-passo-a-passo.jpg',
        'legenda' => 'ano-letivo-passo-a-passo.vtt',
        'capitulos_vtt' => 'ano-letivo-passo-a-passo.capitulos.vtt',
        'duracao' => 73.3,
        'versao' => '1.42.0',
        'capitulos' => 
        array (
          0 => 
          array (
            'inicio' => 2.0,
            'titulo' => 'O caminho na Ajuda',
          ),
          1 => 
          array (
            'inicio' => 10.7,
            'titulo' => '1 a 3 — Matérias e matriz',
          ),
          2 => 
          array (
            'inicio' => 22.9,
            'titulo' => '4 — A oferta do ano',
          ),
          3 => 
          array (
            'inicio' => 29.2,
            'titulo' => '5 e 6 — Alunos e enturmação',
          ),
          4 => 
          array (
            'inicio' => 39.1,
            'titulo' => '7 — Notas e faltas',
          ),
          5 => 
          array (
            'inicio' => 44.3,
            'titulo' => '8 — Encerrar: quem pula um passo é recusado',
          ),
          6 => 
          array (
            'inicio' => 55.0,
            'titulo' => '9 e 10 — Boletim e histórico',
          ),
        ),
        'transcricao' => 'Ajuda → Central de ajuda: o "Comece por aqui" é o caminho do ano, na ordem em que as coisas acontecem.
O guia mestre: cada passo, quem faz e o guia de cada um. Siga a ordem: cada passo depende do anterior.
1. Secretaria: as matérias do catálogo, com o código que sai no histórico (Ensino → Matérias).
2 e 3. A matriz: a grade e as disciplinas (secretaria). Quem finaliza a grade é o administrador.
4. Sem matriz não há oferta: gere a oferta do ano e complete cada uma (período, professor).
5. Cadastre os alunos e envie o link de acesso (Educacional → Usuários Acadêmico).
6. Na ficha, aba Matrículas: o vínculo ao curso e o botão Enturmação de cada ano.
7. Sem enturmação não há nota: o professor lança atividades, notas e faltas; a secretaria compensa faltas.
8. Encerrar cada disciplina. Pulou um passo? A tela diz o motivo: esta oferta não tem aluno enturmado.
"A oferta não tem aluno enturmado." Volte ao passo que faltou (aqui, a enturmação).
9. O boletim do aluno, para ver e imprimir.
10. Conclusão, TCC e histórico: a prévia avisa o que falta antes de emitir.
Regra que parece errada (média, frequência, casas decimais)? É do administrador: Regras de nota e frequência.',
      ),
    ),
    'atualizar-o-sistema' => 
    array (
      'titulo' => 'Atualizar o sistema',
      'resumo' => 'Aplicar as etapas do banco de dados de uma versão nova, pela tela ou pelo comando, e ver a versão instalada.',
      'perfis' => 
      array (
        0 => 'administrador',
      ),
      'ordem' => 5,
      'entrou' => '1.25.0',
      'mudou' => '',
      'comece' => 
      array (
      ),
      'relacionados' => 
      array (
        0 => 'integrar-outro-sistema-pela-api',
      ),
      'telas' => 
      array (
        0 => 'atualizar/index',
        1 => 'versao/index',
      ),
      'imagens' => 
      array (
      ),
      'tipo' => '',
    ),
    'auditoria-de-notas-e-faltas' => 
    array (
      'titulo' => 'Auditoria de notas e faltas',
      'resumo' => 'Ver quem lançou, alterou ou apagou uma nota ou falta, quando e de onde.',
      'perfis' => 
      array (
        0 => 'administrador',
        1 => 'professor',
      ),
      'ordem' => 64,
      'entrou' => '1.21.0',
      'mudou' => '',
      'comece' => 
      array (
      ),
      'relacionados' => 
      array (
        0 => 'lancar-notas',
        1 => 'lancar-faltas',
        2 => 'reabrir-uma-disciplina',
        3 => 'perfis-de-acesso',
      ),
      'telas' => 
      array (
        0 => 'auditoria/listar',
        1 => 'auditoria/nota',
      ),
      'imagens' => 
      array (
      ),
      'tipo' => 'escola,superior',
      'video' => 
      array (
        'arquivo' => 'auditoria-de-notas-e-faltas.mp4',
        'poster' => 'auditoria-de-notas-e-faltas.jpg',
        'legenda' => 'auditoria-de-notas-e-faltas.vtt',
        'capitulos_vtt' => 'auditoria-de-notas-e-faltas.capitulos.vtt',
        'duracao' => 34.1,
        'versao' => '1.42.0',
        'capitulos' => 
        array (
          0 => 
          array (
            'inicio' => 2.0,
            'titulo' => 'O histórico de uma nota',
          ),
          1 => 
          array (
            'inicio' => 11.2,
            'titulo' => 'O relatório',
          ),
        ),
        'transcricao' => 'Histórico da nota. Na avaliação, a coluna Histórico: o relógio na linha do aluno abre cada mudança daquela nota.
Cada mudança. Quando, a ação, o valor antes e depois, quem fez e a origem. Ninguém apaga este registro.
Auditoria de notas e faltas. Para tudo: Relatórios → Auditoria de notas e faltas.
Os filtros. Filtre por curso, disciplina, aluno, quem lançou, o quê e o período. "Só alterações e exclusões" deixa só o que mudou depois de lançado.
Exportar. Para guardar o resultado, Excel, CSV ou PDF, acima da tabela.',
      ),
    ),
    'avaliar-na-escola' => 
    array (
      'titulo' => 'A avaliação na escola: as etapas, a recuperação, a média anual e o conselho',
      'resumo' => 'Na escola, a nota de cada etapa (com a recuperação), a média anual, a frequência, a situação final, o conselho de classe e o boletim por etapa.',
      'perfis' => 
      array (
        0 => 'administrador',
        1 => 'secretaria',
        2 => 'professor',
      ),
      'ordem' => 7,
      'entrou' => '1.40.0',
      'mudou' => '1.94.0',
      'comece' => 
      array (
      ),
      'relacionados' => 
      array (
        0 => 'organizar-a-escola',
        1 => 'liberar-o-boletim',
      ),
      'telas' => 
      array (
        0 => 'escola-notas/etapas',
        1 => 'escola-conselho/turma',
      ),
      'imagens' => 
      array (
      ),
      'tipo' => 'escola',
      'video' => 
      array (
        'arquivo' => 'avaliar-na-escola.mp4',
        'poster' => 'avaliar-na-escola.jpg',
        'legenda' => 'avaliar-na-escola.vtt',
        'capitulos_vtt' => 'avaliar-na-escola.capitulos.vtt',
        'duracao' => 70.2,
        'versao' => '1.42.0',
        'capitulos' => 
        array (
          0 => 
          array (
            'inicio' => 2.0,
            'titulo' => 'As etapas do componente',
          ),
          1 => 
          array (
            'inicio' => 22.7,
            'titulo' => 'O conselho de classe',
          ),
          2 => 
          array (
            'inicio' => 30.8,
            'titulo' => 'Liberar o boletim',
          ),
          3 => 
          array (
            'inicio' => 39.8,
            'titulo' => 'O boletim',
          ),
        ),
        'transcricao' => 'A turma. Na tela da turma, cada componente tem "Etapas e notas": a média de cada aluno em cada etapa.
As etapas. Uma aba por etapa. Na etapa: a média de cada aluno pela regra do componente, a recuperação (para quem ficou abaixo da média) e as faltas.
A etapa aberta. Numa etapa aberta, os campos da recuperação e das faltas. "Fechar a etapa" guarda as notas da etapa de cada aluno, com a regra copiada.
Conselho de classe. O conselho de classe: cada aluno com a média anual e a situação de cada componente. No fim do ano, o conselho pode aprovar, com a observação obrigatória; a nota não muda.
Liberar no portal. Ano letivo e etapas: a coluna do portal libera o boletim de cada etapa para a escola inteira. A média anual e a situação aparecem com todas liberadas.
O boletim na tela. O boletim do aluno: as etapas em colunas (com "R" quando houve recuperação), a média anual, as faltas, a frequência e a situação.
O boletim em PDF. O mesmo boletim em PDF, com o mesmo cálculo.
O boletim no portal. No portal, o aluno vê só as etapas liberadas.',
      ),
    ),
    'avisos-e-seguranca' => 
    array (
      'titulo' => 'Avisos do sistema e segurança',
      'resumo' => 'As versões, as atualizações por aplicar, a versão nova, as verificações de segurança e as bibliotecas, em Manutenção.',
      'perfis' => 
      array (
        0 => 'administrador',
      ),
      'ordem' => 66,
      'entrou' => '1.32.0',
      'mudou' => '1.83.0',
      'comece' => 
      array (
      ),
      'relacionados' => 
      array (
        0 => 'atualizar-o-sistema',
        1 => 'perfis-de-acesso',
      ),
      'telas' => 
      array (
        0 => 'avisos-sistema/index',
        1 => 'verificacoes-seguranca/index',
        2 => 'bibliotecas/index',
      ),
      'imagens' => 
      array (
      ),
      'tipo' => '',
      'video' => 
      array (
        'arquivo' => 'avisos-e-seguranca.mp4',
        'poster' => 'avisos-e-seguranca.jpg',
        'legenda' => 'avisos-e-seguranca.vtt',
        'capitulos_vtt' => 'avisos-e-seguranca.capitulos.vtt',
        'duracao' => 52.3,
        'versao' => '1.42.0',
        'capitulos' => 
        array (
          0 => 
          array (
            'inicio' => 2.0,
            'titulo' => 'Avisos do sistema e segurança',
          ),
        ),
        'transcricao' => 'As versões. A versão dos arquivos e a do banco. Se houver atualização por aplicar, o cartão leva a Atualizar o sistema. Agora: Confira e clique em Avançar.
A versão nova. Se existe uma versão mais nova: no máximo uma consulta por dia, sem enviar dado da instalação e sem baixar nada. Agora: Clique em Avançar.
A segurança. O resumo da conferência de segurança: o que está certo, o que pede atenção e o que é preciso resolver. Agora: Clique em Ver as verificações de segurança.
Verificações de segurança. Cada item, com o que foi conferido e o que fazer. Numa instalação no próprio computador, o HTTPS e a pasta dos arquivos aparecem como atenção. Agora: Leia os itens e clique em Avançar.
Bibliotecas. As bibliotecas de terceiros que o sistema usa, com a versão e a licença de cada uma. Nada nestas telas muda dado do sistema.',
      ),
    ),
    'bloquear-o-acesso-de-um-responsavel' => 
    array (
      'titulo' => 'Cancelar o convite ou bloquear o acesso de um responsável',
      'resumo' => 'Cancelar faz o link do convite deixar de valer. Bloquear impede a pessoa de entrar no portal, mesmo com a senha certa.',
      'perfis' => 
      array (
        0 => 'administrador',
        1 => 'secretaria',
      ),
      'ordem' => 17,
      'entrou' => '1.53.0',
      'mudou' => '',
      'comece' => 
      array (
      ),
      'relacionados' => 
      array (
        0 => 'convidar-um-responsavel',
      ),
      'telas' => 
      array (
      ),
      'imagens' => 
      array (
      ),
      'tipo' => 'escola',
    ),
    'cadastrar-aluno-e-enviar-link' => 
    array (
      'titulo' => 'Cadastrar aluno e enviar o link',
      'resumo' => 'Cadastro com RA único e o link para o próprio aluno definir a senha.',
      'perfis' => 
      array (
        0 => 'administrador',
        1 => 'secretaria',
      ),
      'ordem' => 20,
      'entrou' => '1.0.0',
      'mudou' => '1.11.0',
      'comece' => 
      array (
        'secretaria' => 8,
      ),
      'relacionados' => 
      array (
        0 => 'vincular-e-enturmar',
        1 => 'primeiro-acesso-do-aluno',
        2 => 'email-smtp',
      ),
      'telas' => 
      array (
        0 => 'academicos/listar',
        1 => 'academicos/cadastrar',
        2 => 'academicos/editar',
        3 => 'academicos/enviar-link',
      ),
      'imagens' => 
      array (
        0 => 'secretaria_08_alunos.png',
        1 => 'secretaria_09_aluno_cadastrar.png',
        2 => 'secretaria_11_link_de_acesso.png',
      ),
      'tipo' => '',
      'video' => 
      array (
        'arquivo' => 'cadastrar-aluno-e-enviar-link.mp4',
        'poster' => 'cadastrar-aluno-e-enviar-link.jpg',
        'legenda' => 'cadastrar-aluno-e-enviar-link.vtt',
        'capitulos_vtt' => 'cadastrar-aluno-e-enviar-link.capitulos.vtt',
        'duracao' => 44.5,
        'versao' => '1.42.0',
        'capitulos' => 
        array (
          0 => 
          array (
            'inicio' => 2.0,
            'titulo' => 'Abrir o cadastro',
          ),
          1 => 
          array (
            'inicio' => 5.9,
            'titulo' => 'O sexo é obrigatório',
          ),
          2 => 
          array (
            'inicio' => 13.6,
            'titulo' => 'Cadastro certo',
          ),
          3 => 
          array (
            'inicio' => 22.6,
            'titulo' => 'Enviar o link de acesso',
          ),
        ),
        'transcricao' => 'Educacional → Usuários Acadêmico: a lista de alunos. Clique em "Cadastrar aluno".
Preencha o RA (só números, único) e o nome. Vamos tentar salvar sem escolher o sexo.
Recusado: o campo Sexo é obrigatório — os documentos concordam com ele (ALUNA/ALUNO).
Escolha o sexo. CPF e e-mail são opcionais, mas precisam ser válidos e únicos.
Aluno cadastrado. Ele nasce SEM senha: quem define a senha é o próprio aluno, pelo link.
Na ficha do aluno, clique em "Enviar link de acesso".
Sem e-mail configurado, a tela avisa "O e-mail NÃO foi enviado" e mostra o link para você entregar por um canal seguro.
O link vale por 48 horas e uma vez só; um link novo invalida o anterior.',
      ),
    ),
    'cadastrar-materias' => 
    array (
      'titulo' => 'Cadastrar matérias',
      'resumo' => 'O catálogo de disciplinas da instituição, com o código que sai no histórico.',
      'perfis' => 
      array (
        0 => 'administrador',
        1 => 'secretaria',
      ),
      'ordem' => 10,
      'entrou' => '1.0.0',
      'mudou' => '1.18.1',
      'comece' => 
      array (
        'secretaria' => 4,
      ),
      'relacionados' => 
      array (
        0 => 'inativar-curso-ou-materia',
        1 => 'montar-a-matriz',
        2 => 'ano-letivo-passo-a-passo',
      ),
      'telas' => 
      array (
        0 => 'materias/listar',
        1 => 'materias/cadastrar',
        2 => 'materias/editar',
        3 => 'materias/visualizar',
      ),
      'imagens' => 
      array (
        0 => 'secretaria_02_materias.png',
        1 => 'secretaria_03_materia_cadastrar.png',
      ),
      'tipo' => '',
      'video' => 
      array (
        'arquivo' => 'cadastrar-materias.mp4',
        'poster' => 'cadastrar-materias.jpg',
        'legenda' => 'cadastrar-materias.vtt',
        'capitulos_vtt' => 'cadastrar-materias.capitulos.vtt',
        'duracao' => 49.4,
        'versao' => '1.42.0',
        'capitulos' => 
        array (
          0 => 
          array (
            'inicio' => 2.0,
            'titulo' => 'O catálogo de matérias',
          ),
          1 => 
          array (
            'inicio' => 9.9,
            'titulo' => 'Cadastrar',
          ),
          2 => 
          array (
            'inicio' => 38.7,
            'titulo' => 'A matéria na lista',
          ),
        ),
        'transcricao' => 'Ensino → Matérias: o catálogo de disciplinas. A matriz de cada curso é montada com elas.
Cada matéria tem o seu código (a coluna "Código / abreviação"): é ele que sai no histórico.
Código / abreviação: até 20 caracteres. Em branco, o histórico mostra o número interno da matéria.
Nome e carga horária são obrigatórios: sem eles, a tela não deixa salvar.
Recusado: "Preencha este campo." — e, se o pedido chegasse assim mesmo, o sistema recusaria sem gravar nada.
Agora o nome, a carga horária padrão (horas, de 1 a 9999) e uma descrição curta.
Salvo: "Matéria criada!". A matéria nova aparece na lista, com o código ESP101.
Apagar matéria é do administrador; a secretaria cadastra e edita (lápis). Matéria que não será oferecida: inative-a.',
      ),
    ),
    'cadastrar-um-responsavel' => 
    array (
      'titulo' => 'Cadastrar um responsável',
      'resumo' => 'Na escola, a pessoa responsável tem cadastro próprio e pode ser responsável por vários alunos. O sistema não cria a mesma pessoa duas vezes.',
      'perfis' => 
      array (
        0 => 'administrador',
        1 => 'secretaria',
      ),
      'ordem' => 10,
      'entrou' => '1.52.0',
      'mudou' => '',
      'comece' => 
      array (
      ),
      'relacionados' => 
      array (
        0 => 'designar-o-financeiro-e-o-titular',
        1 => 'importar-responsaveis',
      ),
      'telas' => 
      array (
        0 => 'responsaveis/listar',
        1 => 'responsaveis/cadastrar',
        2 => 'responsaveis/editar',
        3 => 'responsaveis/ver',
      ),
      'imagens' => 
      array (
      ),
      'tipo' => 'escola',
    ),
    'cancelar-uma-emissao' => 
    array (
      'titulo' => 'Cancelar uma emissão',
      'resumo' => 'Tirar a validade de um histórico emitido, com motivo; a verificação passa a mostrar "cancelado".',
      'perfis' => 
      array (
        0 => 'administrador',
      ),
      'ordem' => 53,
      'entrou' => '1.5.0',
      'mudou' => '1.18.2',
      'comece' => 
      array (
        'administrador' => 11,
      ),
      'relacionados' => 
      array (
        0 => 'emitir-historico-final',
        1 => 'conferir-um-documento',
      ),
      'telas' => 
      array (
      ),
      'imagens' => 
      array (
        0 => 'administrador_16_emissao_cancelar.png',
      ),
      'tipo' => 'escola,superior',
      'video' => 
      array (
        'arquivo' => 'cancelar-uma-emissao.mp4',
        'poster' => 'cancelar-uma-emissao.jpg',
        'legenda' => 'cancelar-uma-emissao.vtt',
        'capitulos_vtt' => 'cancelar-uma-emissao.capitulos.vtt',
        'duracao' => 51.6,
        'versao' => '1.42.0',
        'capitulos' => 
        array (
          0 => 
          array (
            'inicio' => 2.0,
            'titulo' => 'Abrir a emissão',
          ),
          1 => 
          array (
            'inicio' => 11.0,
            'titulo' => 'O motivo é obrigatório',
          ),
          2 => 
          array (
            'inicio' => 20.5,
            'titulo' => 'Cancelar com motivo',
          ),
          3 => 
          array (
            'inicio' => 36.9,
            'titulo' => 'A verificação pública',
          ),
        ),
        'transcricao' => 'Educacional → Históricos emitidos: a lista de todos os documentos emitidos, com a situação de cada um.
Vamos cancelar o histórico PARCIAL 5JJS-ZAME-HUGY. Clique em "Ver" na linha da emissão.
À direita, o quadro "Cancelar esta emissão" (só o administrador o vê). Sem motivo, o cancelamento é recusado.
Recusado: "Informe o motivo do cancelamento (de 5 a 500 caracteres)". Nada mudou: a emissão continua válida.
Escreva o motivo (ele fica gravado) e clique em "Cancelar emissão". O sistema pede confirmação.
Cancelada. O documento NÃO é apagado: a situação mostra a data, quem cancelou e o motivo.
O quadro de cancelamento e a 2ª via somem: cancelada não volta a valer. Para corrigir, emita de novo (novo código).
Abra o link da verificação pública desta emissão.
Quem conferir o código agora vê "Documento cancelado", com a data do cancelamento.',
      ),
    ),
    'como-usar-os-videos-da-ajuda' => 
    array (
      'titulo' => 'Como usar os vídeos da Ajuda',
      'resumo' => 'O vídeo no topo de cada guia, a legenda, os capítulos, a transcrição e a página Vídeos.',
      'perfis' => 
      array (
        0 => 'administrador',
        1 => 'secretaria',
        2 => 'professor',
      ),
      'ordem' => 2,
      'entrou' => '1.18.0',
      'mudou' => '',
      'comece' => 
      array (
      ),
      'relacionados' => 
      array (
        0 => 'usar-a-ajuda',
      ),
      'telas' => 
      array (
        0 => 'ajuda/videos',
        1 => 'home/videos',
      ),
      'imagens' => 
      array (
      ),
      'tipo' => '',
      'video' => 
      array (
        'arquivo' => 'como-usar-os-videos-da-ajuda.mp4',
        'poster' => 'como-usar-os-videos-da-ajuda.jpg',
        'legenda' => 'como-usar-os-videos-da-ajuda.vtt',
        'capitulos_vtt' => 'como-usar-os-videos-da-ajuda.capitulos.vtt',
        'duracao' => 48.7,
        'versao' => '1.42.0',
        'capitulos' => 
        array (
          0 => 
          array (
            'inicio' => 2.0,
            'titulo' => 'A página Vídeos',
          ),
          1 => 
          array (
            'inicio' => 16.6,
            'titulo' => 'O player no guia',
          ),
          2 => 
          array (
            'inicio' => 29.3,
            'titulo' => 'Capítulos e transcrição',
          ),
        ),
        'transcricao' => 'Na Central de ajuda, o botão "Vídeos" abre todos os vídeos do seu perfil.
Cada vídeo com o poster e a duração. Busque por uma palavra do título ou do que é dito no vídeo.
A busca encontra o guia "Cadastrar aluno e enviar o link". Clique para abrir o guia com o vídeo.
O vídeo fica no topo do guia, antes do texto. Ele só carrega quando você manda tocar.
A legenda já vem ligada: é a narração de cada passo. Velocidade e tela cheia ficam nos controles do vídeo.
Ao lado, os capítulos: clique em um para pular direto para aquele trecho. O capítulo atual fica marcado.
Abaixo do vídeo, "Ler a transcrição" mostra todo o texto narrado.
Guia sem vídeo no servidor? O texto continua valendo — o player simplesmente não aparece.',
      ),
    ),
    'compensar-faltas' => 
    array (
      'titulo' => 'Compensar faltas (atestado, licença)',
      'resumo' => 'Lançar falta compensada com tipo, justificativa e período; o aluno vê só o número.',
      'perfis' => 
      array (
        0 => 'secretaria',
        1 => 'administrador',
      ),
      'ordem' => 33,
      'entrou' => '1.8.0',
      'mudou' => '',
      'comece' => 
      array (
      ),
      'relacionados' => 
      array (
        0 => 'lancar-faltas',
        1 => 'acompanhar-notas-e-resultado',
      ),
      'telas' => 
      array (
        0 => 'faltas/compensar',
      ),
      'imagens' => 
      array (
      ),
      'tipo' => 'escola,superior',
      'video' => 
      array (
        'arquivo' => 'compensar-faltas.mp4',
        'poster' => 'compensar-faltas.jpg',
        'legenda' => 'compensar-faltas.vtt',
        'capitulos_vtt' => 'compensar-faltas.capitulos.vtt',
        'duracao' => 57.5,
        'versao' => '1.42.0',
        'capitulos' => 
        array (
          0 => 
          array (
            'inicio' => 2.0,
            'titulo' => 'Abrir as faltas',
          ),
          1 => 
          array (
            'inicio' => 9.6,
            'titulo' => 'O que é recusado',
          ),
          2 => 
          array (
            'inicio' => 27.5,
            'titulo' => 'A compensação certa',
          ),
        ),
        'transcricao' => 'Na disciplina, clique em Faltas. As faltas já foram lançadas pelo professor.
Na linha do aluno, o botão Compensar. Fábio tem 2 faltas: 86,67% de frequência.
A tela diz até quanto dá para compensar. Mais que as faltas (3) não passa.
Quantidade 2, tipo Atestado médico e a justificativa. Vamos pôr o fim do período antes do início.
Recusado: "O fim do período não pode ser antes do início." Nada foi salvo.
De novo, com o período certo. Clique em "Lançar compensação".
"Compensação lançada." Compensadas: 2. A frequência é recalculada na hora: 100%.
O histórico registra quem lançou, quando, o tipo, o período e o motivo. Um lançamento errado sai pela lixeira.
O aluno (e o professor) vê só a quantidade compensada, nunca o motivo.',
      ),
    ),
    'completar-a-oferta' => 
    array (
      'titulo' => 'Completar a oferta: período e professor',
      'resumo' => 'Em cada disciplina ofertada: total de encontros, período de realização e o professor.',
      'perfis' => 
      array (
        0 => 'administrador',
        1 => 'secretaria',
      ),
      'ordem' => 14,
      'entrou' => '1.1.0',
      'mudou' => '1.34.0',
      'comece' => 
      array (
        'secretaria' => 7,
      ),
      'relacionados' => 
      array (
        0 => 'gerar-a-oferta-do-ano',
        1 => 'previa-do-historico',
      ),
      'telas' => 
      array (
        0 => 'disciplinas/editar',
      ),
      'imagens' => 
      array (
        0 => 'secretaria_18_oferta_editar.png',
        1 => 'administrador_13_ofertas_professor.png',
      ),
      'tipo' => '',
      'video' => 
      array (
        'arquivo' => 'completar-a-oferta.mp4',
        'poster' => 'completar-a-oferta.jpg',
        'legenda' => 'completar-a-oferta.vtt',
        'capitulos_vtt' => 'completar-a-oferta.capitulos.vtt',
        'duracao' => 47.7,
        'versao' => '1.42.0',
        'capitulos' => 
        array (
          0 => 
          array (
            'inicio' => 2.0,
            'titulo' => 'Editar a oferta',
          ),
          1 => 
          array (
            'inicio' => 12.1,
            'titulo' => 'Período: o fim não vem antes do início',
          ),
          2 => 
          array (
            'inicio' => 29.2,
            'titulo' => 'O professor',
          ),
        ),
        'transcricao' => 'Ensino → Todas as Disciplinas. A oferta recém-gerada do TCC (turma PGP-2026-B) ainda está "sem professor".
Clique em Editar (lápis) na linha da disciplina.
Preencha o total de encontros e o período de realização (início e fim: saem no histórico).
Datas trocadas por engano: o fim ficou antes do início. Salve para ver a recusa.
Recusado: "O fim do período da oferta não pode ser antes do início." Corrija as datas e salve.
Oferta salva, com os encontros e o período.
Na mesma lista, o botão verde com o ícone de pessoa: escolha o professor e salve.
Pronto: a professora passa a ver a oferta, a frequência tem base e o histórico tem o período e o docente.',
      ),
    ),
    'concluir-o-curso-e-tcc' => 
    array (
      'titulo' => 'Concluir o curso e registrar o TCC',
      'resumo' => 'TCC da matrícula e a conclusão, que só é aceita com tudo aprovado.',
      'perfis' => 
      array (
        0 => 'secretaria',
        1 => 'administrador',
      ),
      'ordem' => 40,
      'entrou' => '1.4.0',
      'mudou' => '',
      'comece' => 
      array (
        'secretaria' => 14,
      ),
      'relacionados' => 
      array (
        0 => 'previa-do-historico',
        1 => 'emitir-historico-final',
        2 => 'vincular-e-enturmar',
      ),
      'telas' => 
      array (
      ),
      'imagens' => 
      array (
        0 => 'secretaria_19_tcc_conclusao.png',
      ),
      'tipo' => 'escola,superior',
      'video' => 
      array (
        'arquivo' => 'concluir-o-curso-e-tcc.mp4',
        'poster' => 'concluir-o-curso-e-tcc.jpg',
        'legenda' => 'concluir-o-curso-e-tcc.vtt',
        'capitulos_vtt' => 'concluir-o-curso-e-tcc.capitulos.vtt',
        'duracao' => 49.5,
        'versao' => '1.42.0',
        'capitulos' => 
        array (
          0 => 
          array (
            'inicio' => 2.0,
            'titulo' => 'A conclusão sem o TCC aprovado',
          ),
          1 => 
          array (
            'inicio' => 14.7,
            'titulo' => 'Registrar o TCC',
          ),
          2 => 
          array (
            'inicio' => 30.8,
            'titulo' => 'Concluir o curso',
          ),
        ),
        'transcricao' => 'Ficha do aluno, aba Matrículas. João cursou todas as disciplinas; o curso exige TCC, ainda "Em andamento".
Vamos tentar concluir assim: em Situação, escolha "Concluído" e informe a data de conclusão.
"Conclusão RECUSADA — falta: …" A lista diz o que falta (aqui, o TCC aprovado). Nada foi salvo.
No bloco TCC: título, orientador (um professor), nota, data da defesa ou entrega e a situação.
"Aprovado" exige orientador, data e nota a partir da mínima (7,0). Clique em "Salvar TCC".
TCC salvo como Aprovado.
Agora, de novo: Situação "Concluído", a data de conclusão e "Salvar matrícula".
Aceita: "curso CONCLUÍDO em 15/12/2025". A matrícula fica Concluída — isso libera o histórico FINAL.
A data de conclusão não pode ser antes do ingresso; com disciplina em curso, reprovada ou não cursada, também é recusada.',
      ),
    ),
    'conectar-ao-moodle' => 
    array (
      'titulo' => 'Conectar ao Moodle',
      'resumo' => 'O endereço e a chave do Moodle, o teste da conexão e o que acontece com as disciplinas, o trancamento e o professor.',
      'perfis' => 
      array (
        0 => 'administrador',
      ),
      'ordem' => 67,
      'entrou' => '1.34.0',
      'mudou' => '1.58.0',
      'comece' => 
      array (
      ),
      'relacionados' => 
      array (
        0 => 'envios-ao-moodle',
        1 => 'erros-do-moodle',
      ),
      'telas' => 
      array (
        0 => 'moodle/conexao',
      ),
      'imagens' => 
      array (
      ),
      'tipo' => '',
    ),
    'conferir-um-documento' => 
    array (
      'titulo' => 'Conferir um documento',
      'resumo' => 'A verificação pública pelo código: resultado, dados mínimos, o hash, o comprovante e o PDF recebido.',
      'perfis' => 
      array (
        0 => 'secretaria',
        1 => 'administrador',
      ),
      'ordem' => 54,
      'entrou' => '1.5.0',
      'mudou' => '1.8.0',
      'comece' => 
      array (
      ),
      'relacionados' => 
      array (
        0 => 'emitir-historico-final',
        1 => 'emitir-2a-via',
        2 => 'cancelar-uma-emissao',
      ),
      'telas' => 
      array (
      ),
      'imagens' => 
      array (
        0 => 'secretaria_20_verificacao.png',
      ),
      'tipo' => 'escola,superior',
      'video' => 
      array (
        'arquivo' => 'conferir-um-documento.mp4',
        'poster' => 'conferir-um-documento.jpg',
        'legenda' => 'conferir-um-documento.vtt',
        'capitulos_vtt' => 'conferir-um-documento.capitulos.vtt',
        'duracao' => 56.9,
        'versao' => '1.42.0',
        'capitulos' => 
        array (
          0 => 
          array (
            'inicio' => 2.2,
            'titulo' => 'Digitar o código',
          ),
          1 => 
          array (
            'inicio' => 6.4,
            'titulo' => 'O resultado',
          ),
          2 => 
          array (
            'inicio' => 24.0,
            'titulo' => 'Conferir o PDF recebido',
          ),
          3 => 
          array (
            'inicio' => 42.0,
            'titulo' => 'Código não encontrado',
          ),
        ),
        'transcricao' => 'Abra o endereço do sistema seguido de /verificar. Não precisa de login. Digite o código impresso no documento.
A página do código (o QR code e o endereço curto /verificar/CÓDIGO levam a ela). No topo, a instituição e o resultado.
Os dados mínimos: tipo, data de emissão, instituição, curso, nome do aluno e o CPF mascarado.
A impressão digital do documento (o hash), com o botão Copiar. "Imprimir comprovante" guarda a prova da conferência.
Recebeu o arquivo? Em "Conferir o arquivo recebido", escolha o PDF e clique em "Conferir PDF". Ele não é guardado.
O arquivo é idêntico ao documento emitido com este código.
Agora um arquivo alterado depois da emissão (mesmo que só um pouco).
"O arquivo enviado NÃO é idêntico a nenhum documento emitido com este código: ele pode ter sido alterado."
Um código que não existe (digitado errado, por exemplo):
"Código não encontrado": confira o código impresso no documento. Cancelado ou "Não confere": não aceite o documento.',
      ),
    ),
    'configurar-a-regra-de-media' => 
    array (
      'titulo' => 'Configurar a regra de média',
      'resumo' => 'O catálogo de regras de média — cada regra com nome, componentes e pesos, obrigatório e limite, arredondamento e nota mínima, e o simulador —, associadas às disciplinas da matriz (uma a uma ou em lote), com o padrão da instituição e a troca na oferta.',
      'perfis' => 
      array (
        0 => 'administrador',
        1 => 'secretaria',
      ),
      'ordem' => 33,
      'entrou' => '1.13.0',
      'mudou' => '1.42.0',
      'comece' => 
      array (
      ),
      'relacionados' => 
      array (
        0 => 'encerrar-uma-disciplina',
        1 => 'reabrir-uma-disciplina',
        2 => 'regras-de-nota-e-frequencia',
        3 => 'acompanhar-notas-e-resultado',
      ),
      'telas' => 
      array (
        0 => 'regra-media/listar',
        1 => 'regra-media/editar',
        2 => 'regra-media/cadastrar',
        3 => 'regra-media/aplicar',
        4 => 'regra-media/oferta',
      ),
      'imagens' => 
      array (
      ),
      'tipo' => 'escola,superior',
      'video' => 
      array (
        'arquivo' => 'configurar-a-regra-de-media.mp4',
        'poster' => 'configurar-a-regra-de-media.jpg',
        'legenda' => 'configurar-a-regra-de-media.vtt',
        'capitulos_vtt' => 'configurar-a-regra-de-media.capitulos.vtt',
        'duracao' => 74.2,
        'versao' => '1.42.0',
        'capitulos' => 
        array (
          0 => 
          array (
            'inicio' => 2.0,
            'titulo' => 'O catálogo e as regras prontas',
          ),
          1 => 
          array (
            'inicio' => 13.4,
            'titulo' => 'Componentes e pesos',
          ),
          2 => 
          array (
            'inicio' => 20.3,
            'titulo' => 'O acréscimo',
          ),
          3 => 
          array (
            'inicio' => 30.0,
            'titulo' => 'O simulador',
          ),
          4 => 
          array (
            'inicio' => 66.1,
            'titulo' => 'Salvar',
          ),
        ),
        'transcricao' => 'Regras de média. Configurações → Regras de média: cada regra, os componentes, em uso por quantas disciplinas, e as ações.
As regras prontas. Duas regras vêm prontas: "Só atividades" e "Prova mensal + prova bimestral + atividades". Nenhuma muda a regra de uma disciplina sozinha.
Os componentes. Os componentes, cada um com o peso e o modo. A soma dos pesos fica embaixo: fora de 100, o Salvar desliga.
Permitir acréscimo. Permitir acréscimo (o padrão é não): o professor lança um acréscimo, que soma à média fechada, até o limite.
✓ Acréscimo permitido, até 0,5 ponto.
O simulador. O simulador, ao lado: notas de teste, a média e a memória de cálculo na hora, pela conta do próprio sistema. Nada é gravado.
A média sem o acréscimo. Com as notas, a média fica 7,1. Agora, um acréscimo de 1,0 — maior que o limite.
A média com o acréscimo. A média vai a 7,6: o acréscimo soma antes do arredondamento, até o limite de 0,5, e nunca passa de 10.
✓ A memória de cálculo mostra a linha do acréscimo.
Salvar. Salvar a regra. Se ela está em uso, a tela mostra antes as disciplinas abertas que mudam. No lançamento, o tipo Acréscimo só aparece nas disciplinas com esta regra.
✓ A regra foi salva, com o acréscimo.',
      ),
    ),
    'conhecer-com-a-demonstracao' => 
    array (
      'titulo' => 'Conhecer com a demonstração',
      'resumo' => 'Explorar o sistema com uma instituição fictícia completa e, depois, trocar pelos dados da sua instituição.',
      'perfis' => 
      array (
        0 => 'administrador',
      ),
      'ordem' => 1,
      'entrou' => '1.22.0',
      'mudou' => '1.101.0',
      'comece' => 
      array (
      ),
      'relacionados' => 
      array (
        0 => 'primeiros-passos',
        1 => 'perfis-de-acesso',
      ),
      'telas' => 
      array (
        0 => 'demonstracao/comecar',
        1 => 'demonstracao/instituicao',
      ),
      'imagens' => 
      array (
      ),
      'tipo' => '',
    ),
    'convidar-os-responsaveis-de-uma-turma' => 
    array (
      'titulo' => 'Convidar os responsáveis de uma turma',
      'resumo' => 'Gera o convite de todos os responsáveis da turma que ainda não entraram no portal, envia por e-mail em passos e lista quem fica para o envio manual.',
      'perfis' => 
      array (
        0 => 'administrador',
        1 => 'secretaria',
      ),
      'ordem' => 16,
      'entrou' => '1.53.0',
      'mudou' => '',
      'comece' => 
      array (
      ),
      'relacionados' => 
      array (
        0 => 'convidar-um-responsavel',
      ),
      'telas' => 
      array (
        0 => 'responsaveis/convidar-turma',
      ),
      'imagens' => 
      array (
      ),
      'tipo' => 'escola',
    ),
    'convidar-um-responsavel' => 
    array (
      'titulo' => 'Convidar um responsável para o portal',
      'resumo' => 'O convite é um link de uso único, com prazo. Por ele, a pessoa confere o nome, cria a senha e entra no portal da escola.',
      'perfis' => 
      array (
        0 => 'administrador',
        1 => 'secretaria',
      ),
      'ordem' => 15,
      'entrou' => '1.53.0',
      'mudou' => '1.57.0',
      'comece' => 
      array (
      ),
      'relacionados' => 
      array (
        0 => 'cadastrar-um-responsavel',
        1 => 'designar-o-financeiro-e-o-titular',
        2 => 'convidar-os-responsaveis-de-uma-turma',
        3 => 'bloquear-o-acesso-de-um-responsavel',
      ),
      'telas' => 
      array (
      ),
      'imagens' => 
      array (
      ),
      'tipo' => 'escola',
    ),
    'copiar-do-ano-anterior' => 
    array (
      'titulo' => 'Copiar os responsáveis do ano anterior',
      'resumo' => 'Traz o responsável financeiro e o titular do ano anterior para o ano novo, para conferir antes de gravar. Os acessos adicionais não são copiados.',
      'perfis' => 
      array (
        0 => 'administrador',
        1 => 'secretaria',
      ),
      'ordem' => 14,
      'entrou' => '1.52.0',
      'mudou' => '',
      'comece' => 
      array (
      ),
      'relacionados' => 
      array (
        0 => 'designar-o-financeiro-e-o-titular',
      ),
      'telas' => 
      array (
      ),
      'imagens' => 
      array (
      ),
      'tipo' => 'escola',
    ),
    'dados-da-instituicao-e-atos-legais' => 
    array (
      'titulo' => 'Dados da instituição e atos legais',
      'resumo' => 'Nome, mantenedora, CNPJ, e-MEC, endereço, telefone, e-mail, as linhas do cabeçalho dos documentos, logo e os atos de credenciamento por modalidade.',
      'perfis' => 
      array (
        0 => 'administrador',
      ),
      'ordem' => 60,
      'entrou' => '1.4.0',
      'mudou' => '1.31.0',
      'comece' => 
      array (
        'administrador' => 6,
      ),
      'relacionados' => 
      array (
        0 => 'editar-modelo-de-historico',
        1 => 'regras-de-nota-e-frequencia',
        2 => 'previa-do-historico',
      ),
      'telas' => 
      array (
        0 => 'dados-instituicao/editar',
        1 => 'atos-legais/listar',
        2 => 'atos-legais/cadastrar',
        3 => 'atos-legais/editar',
      ),
      'imagens' => 
      array (
        0 => 'administrador_17_atos_legais.png',
      ),
      'tipo' => '',
      'video' => 
      array (
        'arquivo' => 'dados-da-instituicao-e-atos-legais.mp4',
        'poster' => 'dados-da-instituicao-e-atos-legais.jpg',
        'legenda' => 'dados-da-instituicao-e-atos-legais.vtt',
        'capitulos_vtt' => 'dados-da-instituicao-e-atos-legais.capitulos.vtt',
        'duracao' => 59.7,
        'versao' => '1.42.0',
        'capitulos' => 
        array (
          0 => 
          array (
            'inicio' => 2.0,
            'titulo' => 'Os dados da instituição',
          ),
          1 => 
          array (
            'inicio' => 54.0,
            'titulo' => 'O topo do menu',
          ),
        ),
        'transcricao' => 'Dados da instituição. Configurações → Instituição: os dados que aparecem no topo do menu, nos documentos e no ícone da aba do navegador.
O nome da instituição. É o nome que sai no menu, na tela de entrada e nos documentos. Agora: Escreva o nome da instituição.
Contato e endereço. O telefone, o e-mail, o site e o endereço completam o cabeçalho dos documentos. Nenhum deles é obrigatório. Agora: Preencha o que tiver e clique em Avançar.
O logo. Um arquivo PNG ou JPG, de até 1 MB. Ele aparece no menu, na tela de entrada e no cabeçalho dos documentos. Agora: Escolha o arquivo do logo.
Salvar. Os dados são gravados e o logo passa a aparecer no menu. Agora: Clique em Salvar.
Pronto. A instituição está configurada. Nos Primeiros passos, a fase aparece concluída, com a prévia do logo no menu, na entrada e no documento.
O topo da instituição. No alto do menu, ao lado do selo do sistema, o logo ou o nome da instituição. O mesmo logo vira o ícone da aba do navegador.
✓ A instituição aparece no topo de toda tela.',
      ),
    ),
    'designar-o-financeiro-e-o-titular' => 
    array (
      'titulo' => 'Designar o responsável financeiro e o titular do ano',
      'resumo' => 'Para cada aluno, em cada ano letivo, a escola designa o responsável financeiro (um só) e o responsável acadêmico titular, que pode ser a mesma pessoa.',
      'perfis' => 
      array (
        0 => 'administrador',
        1 => 'secretaria',
      ),
      'ordem' => 11,
      'entrou' => '1.52.0',
      'mudou' => '',
      'comece' => 
      array (
      ),
      'relacionados' => 
      array (
        0 => 'cadastrar-um-responsavel',
        1 => 'trocar-o-responsavel-financeiro',
        2 => 'copiar-do-ano-anterior',
        3 => 'liberar-um-acesso-academico-adicional',
      ),
      'telas' => 
      array (
        0 => 'responsaveis/aluno',
      ),
      'imagens' => 
      array (
      ),
      'tipo' => 'escola',
    ),
    'editar-modelo-de-historico' => 
    array (
      'titulo' => 'Editar um modelo de histórico',
      'resumo' => 'O documento no editor visual, as variáveis e os blocos, os trechos condicionais, o quadro de disciplinas, as assinaturas, a prévia e as versões dos modelos PARCIAL, FINAL e do atestado de matrícula.',
      'perfis' => 
      array (
        0 => 'administrador',
      ),
      'ordem' => 55,
      'entrou' => '1.5.0',
      'mudou' => '1.12.0',
      'comece' => 
      array (
        'administrador' => 9,
      ),
      'relacionados' => 
      array (
        0 => 'emitir-historico-final',
        1 => 'emitir-historico-parcial',
        2 => 'emitir-atestado-de-matricula',
        3 => 'dados-da-instituicao-e-atos-legais',
      ),
      'telas' => 
      array (
        0 => 'historico-modelos/listar',
        1 => 'historico-modelos/editar',
        2 => 'historico-modelos/cadastrar',
      ),
      'imagens' => 
      array (
        0 => 'administrador_18_modelos.png',
        1 => 'administrador_19_modelo_editor.png',
      ),
      'tipo' => 'escola,superior',
      'video' => 
      array (
        'arquivo' => 'editar-modelo-de-historico.mp4',
        'poster' => 'editar-modelo-de-historico.jpg',
        'legenda' => 'editar-modelo-de-historico.vtt',
        'capitulos_vtt' => 'editar-modelo-de-historico.capitulos.vtt',
        'duracao' => 84.9,
        'versao' => '1.42.0',
        'capitulos' => 
        array (
          0 => 
          array (
            'inicio' => 2.0,
            'titulo' => 'A lista de modelos',
          ),
          1 => 
          array (
            'inicio' => 11.8,
            'titulo' => 'Nome, cursos e situação',
          ),
          2 => 
          array (
            'inicio' => 15.6,
            'titulo' => 'O documento e as variáveis',
          ),
          3 => 
          array (
            'inicio' => 42.9,
            'titulo' => 'Quadro, assinaturas e opções',
          ),
          4 => 
          array (
            'inicio' => 60.1,
            'titulo' => 'Prévia e salvar',
          ),
        ),
        'transcricao' => 'Configurações → Modelos de histórico: o tipo, os cursos a que se aplica, a versão e a situação de cada modelo.
Clique em "Editar" no modelo (ou em "Novo modelo").
Nome, cursos a que se aplica (nenhum = padrão da instituição) e situação: só "Ativo" aparece para emitir.
O documento: escreva e formate como num editor de texto. Vamos digitar uma variável à mão, errada.
"Nada foi salvo: … variável desconhecida — {{CONFERIDO_POR}}." Variável se põe pelo painel, não à mão.
O texto volta para corrigir. Reescreva a linha e, na aba Variáveis, busque e clique na variável certa.
Ela entra no ponto do cursor, como {{ALUNO_NOME}}; na emissão, vira o nome do aluno.
No alto do painel, os blocos do sistema (quadro de disciplinas, assinaturas, logo, QR code…) e o trecho condicional.
Aba Quadro: as colunas do quadro de disciplinas, o rótulo, a largura, o alinhamento e a frequência.
Aba Assinaturas: nome, cargo e, se quiser, a imagem (PNG ou JPEG até 1 MB).
Aba Opções: o estilo do documento, a fonte e as casas decimais das notas.
Aba Prévia: escolha um aluno e "Gerar a prévia em PDF" — o documento como está, sem salvar nada.
Depois, "Salvar modelo". Modelo ativo: salvar cria uma NOVA versão; as emissões antigas ficam com a delas.
A prévia em PDF do modelo salvo, com a tarja PRÉVIA: a linha nova traz o nome do aluno.',
      ),
    ),
    'email-smtp' => 
    array (
      'titulo' => 'E-mail (SMTP) dos links de acesso',
      'resumo' => 'Configurar o e-mail que envia os links de senha aos alunos.',
      'perfis' => 
      array (
        0 => 'administrador',
      ),
      'ordem' => 64,
      'entrou' => '1.0.0',
      'mudou' => '1.18.2',
      'comece' => 
      array (
      ),
      'relacionados' => 
      array (
        0 => 'cadastrar-aluno-e-enviar-link',
        1 => 'regras-de-nota-e-frequencia',
      ),
      'telas' => 
      array (
        0 => 'configuracao-email/listar',
      ),
      'imagens' => 
      array (
        0 => 'administrador_15_email.png',
      ),
      'tipo' => '',
      'video' => 
      array (
        'arquivo' => 'email-smtp.mp4',
        'poster' => 'email-smtp.jpg',
        'legenda' => 'email-smtp.vtt',
        'capitulos_vtt' => 'email-smtp.capitulos.vtt',
        'duracao' => 73.6,
        'versao' => '1.42.0',
        'capitulos' => 
        array (
          0 => 
          array (
            'inicio' => 2.0,
            'titulo' => 'Abrir a configuração',
          ),
          1 => 
          array (
            'inicio' => 10.4,
            'titulo' => 'Preencher os dados do servidor',
          ),
          2 => 
          array (
            'inicio' => 39.8,
            'titulo' => 'Mandar um link de teste',
          ),
        ),
        'transcricao' => 'Configurações → E-mail (SMTP): o servidor que envia os links de senha aos alunos.
Nenhuma configuração ainda: sem SMTP, nenhum link sai por e-mail. Clique em Cadastrar.
Tenha em mãos servidor, porta, usuário e senha da conta de e-mail da instituição. Aqui, dados inventados.
Senha da conta de e-mail, Smtp Secure (tls ou ssl) e a porta (587 com tls; 465 com ssl).
Salvo. A senha do e-mail fica só no banco da instituição: não aparece na lista, em relatório nem em guia.
Ao editar, o campo Senha vem em branco: a senha gravada não aparece. Em branco, ela é mantida; preencha só para trocar.
Agora, um link de teste: a secretaria (ou você) abre a ficha de um aluno e clica em "Enviar link de acesso".
O servidor inventado não responde: a tela avisa "O e-mail NÃO foi enviado" e diz o motivo.
Sem SMTP (ou sem e-mail no cadastro), a secretaria copia o link e entrega por um canal seguro.
Com o servidor certo, o aviso é "Link de acesso enviado para …" e o link vai direto ao e-mail do aluno.',
      ),
    ),
    'emitir-2a-via' => 
    array (
      'titulo' => 'Emitir a 2ª via',
      'resumo' => 'Reimprimir o mesmo documento, com o mesmo código e a marca "2ª VIA".',
      'perfis' => 
      array (
        0 => 'secretaria',
        1 => 'administrador',
      ),
      'ordem' => 52,
      'entrou' => '1.5.0',
      'mudou' => '',
      'comece' => 
      array (
      ),
      'relacionados' => 
      array (
        0 => 'emitir-historico-final',
        1 => 'emitir-historico-parcial',
        2 => 'conferir-um-documento',
      ),
      'telas' => 
      array (
        0 => 'historico-emissoes/ver',
      ),
      'imagens' => 
      array (
      ),
      'tipo' => 'escola,superior',
      'video' => 
      array (
        'arquivo' => 'emitir-2a-via.mp4',
        'poster' => 'emitir-2a-via.jpg',
        'legenda' => 'emitir-2a-via.vtt',
        'capitulos_vtt' => 'emitir-2a-via.capitulos.vtt',
        'duracao' => 45.5,
        'versao' => '1.42.0',
        'capitulos' => 
        array (
          0 => 
          array (
            'inicio' => 2.0,
            'titulo' => 'Abrir a emissão',
          ),
          1 => 
          array (
            'inicio' => 8.9,
            'titulo' => 'Gerar a 2ª via',
          ),
          2 => 
          array (
            'inicio' => 23.6,
            'titulo' => 'O PDF da via',
          ),
        ),
        'transcricao' => 'Educacional → Históricos emitidos. A coluna "Vias" conta o original e as reimpressões.
O histórico FINAL UM9W-LZ5D-B4JQ está válido. Clique em "Ver" na linha da emissão.
No quadro Documentos, clique em "Gerar 2ª via": reimprime o MESMO conteúdo gravado, com o mesmo código.
"2ª via gerada, com o mesmo código e o mesmo conteúdo." Fica registrado quem gerou e quando.
A via entra na lista Documentos, ao lado do PDF original. Abra o PDF da 2ª via.
No topo de cada página, a marca "2ª VIA — reimpressão de …". O código continua UM9W-LZ5D-B4JQ.
Emissão cancelada não tem 2ª via. Precisa de um documento com dados novos? Não é 2ª via: emita de novo.',
      ),
    ),
    'emitir-atestado-de-matricula' => 
    array (
      'titulo' => 'Emitir atestado de matrícula',
      'resumo' => 'O atestado de quem está cursando: pela ficha ou pela lista de alunos (secretaria) e pelo portal (o próprio aluno), com código de verificação.',
      'perfis' => 
      array (
        0 => 'secretaria',
        1 => 'administrador',
      ),
      'ordem' => 52,
      'entrou' => '1.12.0',
      'mudou' => '1.74.0',
      'comece' => 
      array (
      ),
      'relacionados' => 
      array (
        0 => 'emitir-historico-parcial',
        1 => 'conferir-um-documento',
        2 => 'cancelar-uma-emissao',
        3 => 'editar-modelo-de-historico',
      ),
      'telas' => 
      array (
        0 => 'atestado/index',
      ),
      'imagens' => 
      array (
      ),
      'tipo' => 'escola,superior',
      'video' => 
      array (
        'arquivo' => 'emitir-atestado-de-matricula.mp4',
        'poster' => 'emitir-atestado-de-matricula.jpg',
        'legenda' => 'emitir-atestado-de-matricula.vtt',
        'capitulos_vtt' => 'emitir-atestado-de-matricula.capitulos.vtt',
        'duracao' => 78.0,
        'versao' => '1.42.0',
        'capitulos' => 
        array (
          0 => 
          array (
            'inicio' => 2.0,
            'titulo' => 'Sem a previsão de conclusão',
          ),
          1 => 
          array (
            'inicio' => 8.6,
            'titulo' => 'Preencher a previsão',
          ),
          2 => 
          array (
            'inicio' => 20.1,
            'titulo' => 'Emitir pela ficha (secretaria)',
          ),
          3 => 
          array (
            'inicio' => 52.1,
            'titulo' => 'Pelo portal (aluno)',
          ),
        ),
        'transcricao' => 'Ficha do aluno, aba Históricos. A matrícula está "Cursando": clique em "Emitir atestado".
Bloqueado: "Falta a previsão de conclusão da matrícula". Sem ela, a frase do atestado ficaria quebrada.
Aba Matrículas: em "Previsão de conclusão", use a sugestão (o fim das ofertas da grade) ou digite a data.
Salvo. A previsão aparece na matrícula como mês e ano — é assim que sai no atestado.
De volta a "Emitir atestado": confira o modelo. "Prévia em PDF" sai com a tarja PRÉVIA; nada é gravado.
Clique em "Emitir atestado de matrícula". A emissão guarda a cópia fiel dos dados do dia, o PDF e o código.
Emitido, com o código LYG2-39MT-VG57. O PDF:
O próprio aluno também emite: ele entra no portal com o RA e a senha.
Menu Acadêmico → Atestado de matrícula.
Na matrícula em curso, "Emitir atestado".
Em "Atestados emitidos" ficam os do aluno e também os que a secretaria emitiu para ele.
No PDF do portal, no lugar da assinatura: "Documento emitido eletronicamente em … Confira a autenticidade em …".',
      ),
    ),
    'emitir-historico-escolar' => 
    array (
      'titulo' => 'Emitir o histórico escolar',
      'resumo' => 'Na escola, o histórico do Ensino Fundamental, pela vida escolar do aluno: a prévia, a 1ª via com o código de verificação, a 2ª via e o cancelamento.',
      'perfis' => 
      array (
        0 => 'secretaria',
        1 => 'administrador',
      ),
      'ordem' => 50,
      'entrou' => '1.101.0',
      'mudou' => '',
      'comece' => 
      array (
      ),
      'relacionados' => 
      array (
        0 => 'conferir-um-documento',
        1 => 'emitir-2a-via',
        2 => 'cancelar-uma-emissao',
        3 => 'conhecer-com-a-demonstracao',
      ),
      'telas' => 
      array (
        0 => 'historico-escola-vida/buscar',
        1 => 'historico-escola-emissao/previa',
      ),
      'imagens' => 
      array (
      ),
      'tipo' => 'escola',
    ),
    'emitir-historico-final' => 
    array (
      'titulo' => 'Emitir o histórico final',
      'resumo' => 'O histórico de quem concluiu: só com a matrícula Concluída e nenhum alerta.',
      'perfis' => 
      array (
        0 => 'secretaria',
        1 => 'administrador',
      ),
      'ordem' => 51,
      'entrou' => '1.5.0',
      'mudou' => '1.7.0',
      'comece' => 
      array (
        'secretaria' => 16,
      ),
      'relacionados' => 
      array (
        0 => 'concluir-o-curso-e-tcc',
        1 => 'previa-do-historico',
        2 => 'emitir-2a-via',
        3 => 'conferir-um-documento',
      ),
      'telas' => 
      array (
        0 => 'historico-emissoes/emitir?tipo=FINAL',
      ),
      'imagens' => 
      array (
        0 => 'secretaria_21_emissao_final.png',
      ),
      'tipo' => 'escola,superior',
      'video' => 
      array (
        'arquivo' => 'emitir-historico-final.mp4',
        'poster' => 'emitir-historico-final.jpg',
        'legenda' => 'emitir-historico-final.vtt',
        'capitulos_vtt' => 'emitir-historico-final.capitulos.vtt',
        'duracao' => 60.1,
        'versao' => '1.42.0',
        'capitulos' => 
        array (
          0 => 
          array (
            'inicio' => 2.0,
            'titulo' => 'Só com a matrícula Concluída',
          ),
          1 => 
          array (
            'inicio' => 11.0,
            'titulo' => 'Prévia de quem concluiu',
          ),
          2 => 
          array (
            'inicio' => 32.2,
            'titulo' => 'Emitir',
          ),
          3 => 
          array (
            'inicio' => 50.7,
            'titulo' => 'A verificação pública',
          ),
        ),
        'transcricao' => 'Ficha do aluno, aba Históricos. João ainda está "Cursando" (o TCC não foi aprovado). Clique em "Emitir final".
Bloqueado: "O histórico FINAL só é emitido com a matrícula CONCLUÍDA". O botão fica desligado.
Irene concluiu o curso. Na aba Históricos dela, "Emitir final".
Situação Concluído e o modelo FINAL. "Nenhum alerta": o FINAL só sai sem nenhum alerta.
"Prévia em PDF" mostra o documento com a tarja PRÉVIA. Nada é gravado.
Sem bloqueios, clique em "Emitir histórico FINAL".
Emitido. A tela da emissão mostra o código L9UM-2FH7-YJXU, o modelo e a versão usados e os hashes.
O PDF emitido, no desenho do modelo escolhido: as disciplinas com a nota final e a frequência, a média, o TCC e a situação.
Por fim, o link da verificação pública: qualquer pessoa confere o documento pelo código.
"Documento válido", com o tipo, o curso e o nome do aluno.',
      ),
    ),
    'emitir-historico-parcial' => 
    array (
      'titulo' => 'Emitir o histórico parcial',
      'resumo' => 'O histórico de quem ainda cursa: atividades por disciplina e o que está em curso; com alertas, só confirmando.',
      'perfis' => 
      array (
        0 => 'secretaria',
        1 => 'administrador',
      ),
      'ordem' => 50,
      'entrou' => '1.5.0',
      'mudou' => '',
      'comece' => 
      array (
        'secretaria' => 15,
      ),
      'relacionados' => 
      array (
        0 => 'previa-do-historico',
        1 => 'emitir-historico-final',
        2 => 'emitir-2a-via',
        3 => 'conferir-um-documento',
      ),
      'telas' => 
      array (
        0 => 'historico-emissoes/listar',
        1 => 'historico-emissoes/emitir',
      ),
      'imagens' => 
      array (
        0 => 'secretaria_22_aba_historicos.png',
        1 => 'secretaria_23_emitir_parcial.png',
      ),
      'tipo' => 'escola,superior',
      'video' => 
      array (
        'arquivo' => 'emitir-historico-parcial.mp4',
        'poster' => 'emitir-historico-parcial.jpg',
        'legenda' => 'emitir-historico-parcial.vtt',
        'capitulos_vtt' => 'emitir-historico-parcial.capitulos.vtt',
        'duracao' => 60.6,
        'versao' => '1.42.0',
        'capitulos' => 
        array (
          0 => 
          array (
            'inicio' => 2.0,
            'titulo' => 'Abrir a emissão',
          ),
          1 => 
          array (
            'inicio' => 6.0,
            'titulo' => 'Modelo e prévia',
          ),
          2 => 
          array (
            'inicio' => 25.6,
            'titulo' => 'Os alertas',
          ),
          3 => 
          array (
            'inicio' => 36.1,
            'titulo' => 'Emitir',
          ),
        ),
        'transcricao' => 'Ficha do aluno, aba Históricos: cada matrícula tem os botões de emissão. Clique em "Emitir parcial".
Confira o tipo (Parcial) e o modelo: só aparecem os modelos ATIVOS que servem a este curso.
"Prévia em PDF": o documento com a tarja PRÉVIA, nada é gravado. Neste modelo, a disciplina em andamento sai em branco.
Leia os alertas (aqui, falta a naturalidade da aluna). No PARCIAL, dá para emitir assim mesmo, confirmando.
Sem marcar a confirmação, a emissão é recusada:
Marque "Li os alertas acima e emito o documento assim mesmo": os alertas ficam gravados na emissão.
Clique em "Emitir histórico PARCIAL".
Emitido: a emissão guarda a cópia fiel dos dados de hoje, o PDF e o código VDZX-LU3D-NBKW (impresso no rodapé).
O PDF emitido. Corrigiu uma nota depois? O documento emitido não muda: emita de novo.',
      ),
    ),
    'encerrar-uma-disciplina' => 
    array (
      'titulo' => 'Encerrar uma disciplina',
      'resumo' => 'Prévia com a conta de cada aluno e o encerramento que grava o resultado, a regra de média usada e a memória de cálculo, e trava notas e faltas.',
      'perfis' => 
      array (
        0 => 'secretaria',
        1 => 'administrador',
      ),
      'ordem' => 34,
      'entrou' => '1.2.0',
      'mudou' => '1.16.0',
      'comece' => 
      array (
        'secretaria' => 12,
      ),
      'relacionados' => 
      array (
        0 => 'acompanhar-notas-e-resultado',
        1 => 'reabrir-uma-disciplina',
        2 => 'configurar-a-regra-de-media',
        3 => 'ver-e-imprimir-o-boletim',
      ),
      'telas' => 
      array (
        0 => 'disciplinas/encerrar',
      ),
      'imagens' => 
      array (
        0 => 'secretaria_14_oferta_aberta.png',
        1 => 'secretaria_15_previa_encerramento.png',
        2 => 'secretaria_16_oferta_encerrada.png',
      ),
      'tipo' => 'escola,superior',
      'video' => 
      array (
        'arquivo' => 'encerrar-uma-disciplina.mp4',
        'poster' => 'encerrar-uma-disciplina.jpg',
        'legenda' => 'encerrar-uma-disciplina.vtt',
        'capitulos_vtt' => 'encerrar-uma-disciplina.capitulos.vtt',
        'duracao' => 53.1,
        'versao' => '1.42.0',
        'capitulos' => 
        array (
          0 => 
          array (
            'inicio' => 2.0,
            'titulo' => 'Com pendência, não encerra',
          ),
          1 => 
          array (
            'inicio' => 10.3,
            'titulo' => 'A prévia',
          ),
          2 => 
          array (
            'inicio' => 32.6,
            'titulo' => 'Confirmar',
          ),
        ),
        'transcricao' => 'Encerramento de "Trabalho de Conclusão de Curso": a regra da disciplina exige uma Prova, e ela não foi cadastrada.
Recusado: "Falta cadastrar: Prova (mínimo 1)". Na prévia, cada aluno fica "Pendente — falta Prova", sem botão de encerrar.
Outra disciplina, com todas as notas e faltas lançadas: Todas as Disciplinas → ver.
Na barra do topo da disciplina, clique em Encerramento.
A prévia diz qual regra de média vai valer, pelo nome e de onde vem (aqui, o padrão da instituição).
Para cada aluno: a conta, a média final, as faltas, a frequência e o resultado. "Imprimir a prévia" imprime a conferência.
Conferido, clique em "Confirmar encerramento".
Encerrada: abre Notas e resultado com os valores GRAVADOS (a regra usada e a memória de cálculo); notas e faltas travam.
Em Encerramento, o quadro Fechamentos lista o vigente e os anteriores. Encerrou com erro? Só o administrador reabre.',
      ),
    ),
    'entrar-no-painel' => 
    array (
      'titulo' => 'Entrar no painel e trocar a senha',
      'resumo' => 'O login da equipe, o menu de cada perfil e como trocar a própria senha.',
      'perfis' => 
      array (
        0 => 'administrador',
        1 => 'secretaria',
        2 => 'professor',
      ),
      'ordem' => 2,
      'entrou' => '1.0.0',
      'mudou' => '1.18.4',
      'comece' => 
      array (
        'administrador' => 2,
        'secretaria' => 2,
        'professor' => 2,
      ),
      'relacionados' => 
      array (
        0 => 'usar-a-ajuda',
        1 => 'usuarios-e-niveis',
      ),
      'telas' => 
      array (
        0 => 'home/index',
      ),
      'imagens' => 
      array (
        0 => 'administrador_01_login.png',
        1 => 'administrador_02_inicio.png',
      ),
      'tipo' => '',
      'video' => 
      array (
        'arquivo' => 'entrar-no-painel.mp4',
        'poster' => 'entrar-no-painel.jpg',
        'legenda' => 'entrar-no-painel.vtt',
        'capitulos_vtt' => 'entrar-no-painel.capitulos.vtt',
        'duracao' => 58.8,
        'versao' => '1.42.0',
        'capitulos' => 
        array (
          0 => 
          array (
            'inicio' => 2.0,
            'titulo' => 'A tela de entrada',
          ),
          1 => 
          array (
            'inicio' => 9.8,
            'titulo' => 'Entrar',
          ),
          2 => 
          array (
            'inicio' => 25.7,
            'titulo' => 'O menu do seu perfil',
          ),
          3 => 
          array (
            'inicio' => 27.8,
            'titulo' => 'O tema',
          ),
          4 => 
          array (
            'inicio' => 47.8,
            'titulo' => 'O aviso da página inicial',
          ),
        ),
        'transcricao' => 'Entrar no painel. Abra o endereço do painel e informe o usuário (ou o e-mail) e a senha.
A senha errada. Senha errada: "Usuário ou senha incorretos." A mensagem é a mesma se o usuário não existir. Depois de várias erradas, a entrada pede uma espera.
A senha certa. Agora, com a senha certa.
✓ Entrou, na página inicial.
O menu. O menu da esquerda mostra só o que o seu perfil pode abrir, em grupos. A tela em que você está fica destacada.
As suas opções. Embaixo do menu, o seu nome abre as suas opções: o tema, o seu perfil e sair.
Claro, escuro ou automático. O tema é de cada pessoa: claro, escuro ou automático (segue o computador). Fica guardado para as próximas vezes.
✓ O tema escuro, nesta conta.
O seu perfil. Para trocar a senha ou os seus dados: "Meu perfil", no mesmo lugar.
O aviso. O aviso de boas-vindas (ou de novidades) fica aqui até você clicar em Entendi. As novidades continuam em Ajuda → Novidades.
✓ Lido: o aviso não volta.',
      ),
    ),
    'entrar-no-portal-como-responsavel' => 
    array (
      'titulo' => 'Entrar no portal como responsável',
      'resumo' => 'O responsável entra na mesma tela do portal, com o login que escolheu no convite (o CPF ou o e-mail) e a senha. Se esquecer a senha, pede um link novo pelo e-mail.',
      'perfis' => 
      array (
        0 => 'administrador',
        1 => 'secretaria',
      ),
      'ordem' => 18,
      'entrou' => '1.53.0',
      'mudou' => '1.57.0',
      'comece' => 
      array (
      ),
      'relacionados' => 
      array (
        0 => 'convidar-um-responsavel',
      ),
      'telas' => 
      array (
      ),
      'imagens' => 
      array (
      ),
      'tipo' => 'escola',
    ),
    'envios-ao-moodle' => 
    array (
      'titulo' => 'Envios ao Moodle',
      'resumo' => 'A fila do que vai para o Moodle: o que foi, o que falta, o motivo de cada falha, o "Tentar de novo" e o "Enviar tudo o que falta".',
      'perfis' => 
      array (
        0 => 'administrador',
        1 => 'secretaria',
      ),
      'ordem' => 68,
      'entrou' => '1.34.0',
      'mudou' => '1.60.0',
      'comece' => 
      array (
      ),
      'relacionados' => 
      array (
        0 => 'conectar-ao-moodle',
        1 => 'erros-do-moodle',
      ),
      'telas' => 
      array (
        0 => 'moodle-envios/listar',
      ),
      'imagens' => 
      array (
      ),
      'tipo' => '',
      'video' => 
      array (
        'arquivo' => 'envios-ao-moodle.mp4',
        'poster' => 'envios-ao-moodle.jpg',
        'legenda' => 'envios-ao-moodle.vtt',
        'capitulos_vtt' => 'envios-ao-moodle.capitulos.vtt',
        'duracao' => 41.5,
        'versao' => '1.42.0',
        'capitulos' => 
        array (
          0 => 
          array (
            'inicio' => 2.0,
            'titulo' => 'Envios ao Moodle',
          ),
          1 => 
          array (
            'inicio' => 36.1,
            'titulo' => 'Enviar tudo o que falta',
          ),
        ),
        'transcricao' => 'Os contadores. Quantos envios estão pendentes, quantos falharam e quando foi a última chamada que deu certo. Agora: Clique em Avançar.
Filtrar. Pendente, enviado, enviado com aviso ou falhou. Cada falha diz o que aconteceu e o que fazer. Agora: Clique em Avançar.
Tentar de novo. Resolvido o motivo (por exemplo, o e-mail do aluno), Tentar de novo na linha. Quando o e-mail está em mais de uma conta do Moodle, a linha lista as contas para escolher a da pessoa. Agora: Clique em Avançar.
Enviar tudo o que falta. Com pendência, Enviar tudo o que falta manda a fila inteira em passos curtos, com a janela de progresso.
Enviar tudo. "Enviar tudo o que falta" manda a fila inteira em passos curtos, com a janela de progresso.
✓ A fila foi enviada.',
      ),
    ),
    'erros-do-moodle' => 
    array (
      'titulo' => 'Quando algo não vai com o Moodle',
      'resumo' => 'O glossário dos avisos da integração com o Moodle: o que aconteceu e o que fazer, aviso por aviso.',
      'perfis' => 
      array (
        0 => 'administrador',
        1 => 'secretaria',
      ),
      'ordem' => 69,
      'entrou' => '1.34.0',
      'mudou' => '1.58.0',
      'comece' => 
      array (
      ),
      'relacionados' => 
      array (
        0 => 'conectar-ao-moodle',
        1 => 'envios-ao-moodle',
      ),
      'telas' => 
      array (
      ),
      'imagens' => 
      array (
      ),
      'tipo' => '',
    ),
    'escolher-uma-senha' => 
    array (
      'titulo' => 'Escolher uma senha',
      'resumo' => 'Como o sistema avalia a senha e o que faz uma senha ser recusada.',
      'perfis' => 
      array (
        0 => 'professor',
        1 => 'secretaria',
        2 => 'administrador',
      ),
      'ordem' => 71,
      'entrou' => '1.19.0',
      'mudou' => '',
      'comece' => 
      array (
      ),
      'relacionados' => 
      array (
        0 => 'esqueci-a-senha',
        1 => 'primeiro-acesso-do-aluno',
        2 => 'usuarios-e-niveis',
      ),
      'telas' => 
      array (
      ),
      'imagens' => 
      array (
      ),
      'tipo' => '',
      'video' => 
      array (
        'arquivo' => 'escolher-uma-senha.mp4',
        'poster' => 'escolher-uma-senha.jpg',
        'legenda' => 'escolher-uma-senha.vtt',
        'capitulos_vtt' => 'escolher-uma-senha.capitulos.vtt',
        'duracao' => 40.4,
        'versao' => '1.42.0',
        'capitulos' => 
        array (
          0 => 
          array (
            'inicio' => 2.0,
            'titulo' => 'A força da senha',
          ),
          1 => 
          array (
            'inicio' => 24.2,
            'titulo' => 'Mostrar e ocultar',
          ),
          2 => 
          array (
            'inicio' => 26.5,
            'titulo' => 'Salvar',
          ),
        ),
        'transcricao' => 'Editar senha. Em Meu perfil, "Editar senha". O mesmo campo aparece em toda tela que pede uma senha nova.
A barra de força. Digite a senha. Abaixo do campo, a barra mostra a força; uma senha curta é recusada já aqui, antes de enviar.
Uma frase longa. Quanto mais longa, mais forte: uma frase com várias palavras é fácil de lembrar e difícil de adivinhar.
Mostrar e ocultar. Para conferir o que digitou, clique em Mostrar; Ocultar esconde de novo. Colar e o gerenciador de senhas do navegador funcionam normalmente.
Salvar. Clique em Atualizar. As mesmas regras valem no servidor, que é quem decide.
✓ Senha nova salva.',
      ),
    ),
    'esqueci-a-senha' => 
    array (
      'titulo' => 'Esqueci a senha',
      'resumo' => 'Pedir um link novo para definir outra senha.',
      'perfis' => 
      array (
        0 => 'secretaria',
        1 => 'administrador',
      ),
      'ordem' => 72,
      'entrou' => '1.0.0',
      'mudou' => '1.7.0',
      'comece' => 
      array (
      ),
      'relacionados' => 
      array (
        0 => 'primeiro-acesso-do-aluno',
        1 => 'cadastrar-aluno-e-enviar-link',
      ),
      'telas' => 
      array (
      ),
      'imagens' => 
      array (
        0 => 'aluno_03_esqueci_a_senha.png',
      ),
      'tipo' => '',
      'video' => 
      array (
        'arquivo' => 'esqueci-a-senha.mp4',
        'poster' => 'esqueci-a-senha.jpg',
        'legenda' => 'esqueci-a-senha.vtt',
        'capitulos_vtt' => 'esqueci-a-senha.capitulos.vtt',
        'duracao' => 85.2,
        'versao' => '1.42.0',
        'capitulos' => 
        array (
          0 => 
          array (
            'inicio' => 2.0,
            'titulo' => 'Pedir o link',
          ),
          1 => 
          array (
            'inicio' => 31.9,
            'titulo' => 'A resposta é sempre a mesma',
          ),
          2 => 
          array (
            'inicio' => 44.1,
            'titulo' => 'Muitos pedidos seguidos',
          ),
          3 => 
          array (
            'inicio' => 63.2,
            'titulo' => 'O link',
          ),
        ),
        'transcricao' => 'Na tela de entrada do portal, clique em "Esqueci a senha".
Informe o seu usuário (aqui, o RA) e clique em Recuperar.
Se houver e-mail no cadastro, chega um link para definir a nova senha. Ele vale por 48 horas.
Por segurança, a resposta é a mesma exista ou não o cadastro. Veja com um RA que não existe:
A mesma mensagem: a tela não revela quem tem cadastro.
Pedindo de novo e de novo para o mesmo RA…
…a tela pede uma espera: começa em 30 segundos e dobra a cada novo pedido, até 15 minutos.
Espere e peça de novo, ou procure a secretaria.
Com o link do e-mail, você define a nova senha como no primeiro acesso. Esta demonstração não envia e-mail.
Link velho, já usado ou incompleto: "Este link é inválido, já foi usado ou expirou." Peça outro.
O e-mail não chegou? Confira o spam. Sem e-mail no cadastro, a secretaria envia o link pela sua ficha.',
      ),
    ),
    'exportar-e-imprimir-listas' => 
    array (
      'titulo' => 'Pesquisar, exportar e imprimir listas',
      'resumo' => 'Toda lista tem pesquisa, ordenação e os botões Excel, CSV, PDF e Imprimir.',
      'perfis' => 
      array (
        0 => 'administrador',
        1 => 'secretaria',
        2 => 'professor',
      ),
      'ordem' => 5,
      'entrou' => '1.8.0',
      'mudou' => '',
      'comece' => 
      array (
      ),
      'relacionados' => 
      array (
        0 => 'usar-a-ajuda',
      ),
      'telas' => 
      array (
      ),
      'imagens' => 
      array (
      ),
      'tipo' => '',
      'video' => 
      array (
        'arquivo' => 'exportar-e-imprimir-listas.mp4',
        'poster' => 'exportar-e-imprimir-listas.jpg',
        'legenda' => 'exportar-e-imprimir-listas.vtt',
        'capitulos_vtt' => 'exportar-e-imprimir-listas.capitulos.vtt',
        'duracao' => 57.8,
        'versao' => '1.42.0',
        'capitulos' => 
        array (
          0 => 
          array (
            'inicio' => 2.0,
            'titulo' => 'Pesquisar',
          ),
          1 => 
          array (
            'inicio' => 10.6,
            'titulo' => 'Ordenar',
          ),
          2 => 
          array (
            'inicio' => 15.0,
            'titulo' => 'Exportar',
          ),
          3 => 
          array (
            'inicio' => 28.3,
            'titulo' => 'Mostrar',
          ),
          4 => 
          array (
            'inicio' => 39.1,
            'titulo' => 'Ações',
          ),
        ),
        'transcricao' => 'Todas as listas do painel funcionam do mesmo jeito. Aqui, a lista de matérias.
Pesquisar: digite no campo à direita. A lista filtra enquanto você digita.
Ordenar: clique no título da coluna; clique de novo para inverter.
Exportar: Excel, CSV (separado por ponto e vírgula, abre direto no Excel), PDF e Imprimir, no topo da lista.
A exportação leva o que está filtrado na tela e deixa de fora a coluna Ações.
O CSV saiu com as 2 matérias filtradas. Lista curta? Há texto no Pesquisar: limpe e exporte de novo.
Mostrar: no pé da lista, escolha 10, 25, 50, 100 ou Todos por página.
Agora o Excel leva a lista inteira. O PDF sai em paisagem quando a lista tem muitas colunas.
Ações: os botões de cada linha (ver, editar, apagar…). Passe o mouse para ver a dica.
Um botão apagado diz na dica por que não pode ser usado: "Em uso: 1 grade(s) na matriz, 1 oferta(s). Não pode ser apagado."',
      ),
    ),
    'fazer-a-chamada' => 
    array (
      'titulo' => 'Fazer a chamada',
      'resumo' => 'A frequência pela chamada de cada encontro: escolher a fonte, registrar o encontro com a data e marcar quem faltou.',
      'perfis' => 
      array (
        0 => 'professor',
        1 => 'secretaria',
        2 => 'administrador',
      ),
      'ordem' => 33,
      'entrou' => '1.14.0',
      'mudou' => '1.97.0',
      'comece' => 
      array (
      ),
      'relacionados' => 
      array (
        0 => 'lancar-faltas',
        1 => 'compensar-faltas',
        2 => 'encerrar-uma-disciplina',
        3 => 'acompanhar-notas-e-resultado',
      ),
      'telas' => 
      array (
        0 => 'chamada/listar',
        1 => 'chamada/encontro',
      ),
      'imagens' => 
      array (
      ),
      'tipo' => 'escola,superior',
      'video' => 
      array (
        'arquivo' => 'fazer-a-chamada.mp4',
        'poster' => 'fazer-a-chamada.jpg',
        'legenda' => 'fazer-a-chamada.vtt',
        'capitulos_vtt' => 'fazer-a-chamada.capitulos.vtt',
        'duracao' => 57.7,
        'versao' => '1.42.0',
        'capitulos' => 
        array (
          0 => 
          array (
            'inicio' => 2.0,
            'titulo' => 'A fonte da frequência',
          ),
          1 => 
          array (
            'inicio' => 12.0,
            'titulo' => 'Zerar as faltas lançadas',
          ),
          2 => 
          array (
            'inicio' => 30.1,
            'titulo' => 'Registrar um encontro',
          ),
          3 => 
          array (
            'inicio' => 43.1,
            'titulo' => 'A lista de encontros',
          ),
        ),
        'transcricao' => 'Na barra da disciplina, clique em Chamada.
A fonte é "Lançamento de faltas". Para fazer chamada, clique em "Trocar para Chamada por encontro".
Recusado: há falta lançada para 3 alunos. As duas fontes não se misturam, para a falta não contar duas vezes.
Na tela de Faltas, zere as faltas lançadas e salve.
De volta à Chamada, a troca agora passa (o sistema pede confirmação).
"Novo encontro": a data é obrigatória; horário, título e conteúdo são opcionais.
Todos começam PRESENTES. Clique em quem faltou: o botão vira AUSENTE (clique de novo para desfazer).
"Salvar a chamada": o encontro, as presenças e as faltas são gravados de uma vez.
O encontro na lista, com presentes e ausentes. O lápis abre para corrigir; a lixeira apaga e recalcula as faltas.
As faltas agora vêm da chamada; a compensação continua na tela de Faltas.',
      ),
    ),
    'gerar-a-oferta-do-ano' => 
    array (
      'titulo' => 'Gerar a oferta do ano',
      'resumo' => 'Uma oferta por disciplina da grade, com turma, polo, modalidade e total de encontros.',
      'perfis' => 
      array (
        0 => 'administrador',
        1 => 'secretaria',
      ),
      'ordem' => 13,
      'entrou' => '1.1.0',
      'mudou' => '1.8.0',
      'comece' => 
      array (
        'secretaria' => 6,
      ),
      'relacionados' => 
      array (
        0 => 'montar-a-matriz',
        1 => 'completar-a-oferta',
        2 => 'vincular-e-enturmar',
      ),
      'telas' => 
      array (
        0 => 'disciplinas/gerar-oferta',
        1 => 'disciplinas/listar',
      ),
      'imagens' => 
      array (
        0 => 'secretaria_06_gerar_oferta.png',
        1 => 'secretaria_07_ofertas.png',
      ),
      'tipo' => 'escola,superior',
      'video' => 
      array (
        'arquivo' => 'gerar-a-oferta-do-ano.mp4',
        'poster' => 'gerar-a-oferta-do-ano.jpg',
        'legenda' => 'gerar-a-oferta-do-ano.vtt',
        'capitulos_vtt' => 'gerar-a-oferta-do-ano.capitulos.vtt',
        'duracao' => 71.0,
        'versao' => '1.42.0',
        'capitulos' => 
        array (
          0 => 
          array (
            'inicio' => 2.0,
            'titulo' => 'Escolher a grade',
          ),
          1 => 
          array (
            'inicio' => 15.0,
            'titulo' => 'As recusas',
          ),
          2 => 
          array (
            'inicio' => 32.0,
            'titulo' => 'Gerar',
          ),
          3 => 
          array (
            'inicio' => 48.4,
            'titulo' => 'Repetir não duplica',
          ),
          4 => 
          array (
            'inicio' => 61.1,
            'titulo' => 'Conferir a lista',
          ),
        ),
        'transcricao' => 'Ensino → Todas as Disciplinas: clique em "Gerar oferta do ano" (o mesmo botão está na tela da grade).
Escolha o curso e a grade. Grade Desativada não aparece: ela não gera oferta.
As disciplinas da grade vêm todas marcadas: desmarque as que não serão oferecidas agora.
Se todas ficarem desmarcadas, a geração é recusada.
"Escolha ao menos uma disciplina." Agora com as disciplinas, mas 0 encontros:
"O total de encontros deve ser um número inteiro de 1 a 999 (ou vazio)." Ele é a base da frequência.
Desmarque o Trabalho de Conclusão de Curso (será ofertado depois). Ano, turma, polo, modalidade (vazio = a do curso) e encontros.
Sai uma oferta por disciplina marcada: 5 criadas. Os professores vêm depois, em cada oferta.
Gerar de novo com a mesma turma (o TCC de novo desmarcado) não duplica: a oferta que já existe é pulada.
"0 criada(s), 5 já existia(m)": nada foi duplicado.
Em Todas as Disciplinas, as 5 ofertas da turma nova, Abertas e sem professor. Agora, complete cada oferta.',
      ),
    ),
    'importar-alunos-de-planilha' => 
    array (
      'titulo' => 'Importar alunos de uma planilha',
      'resumo' => 'Trazer muitos alunos de uma vez por um CSV, conferido linha a linha antes de gravar, e desfazer se preciso.',
      'perfis' => 
      array (
        0 => 'administrador',
        1 => 'secretaria',
      ),
      'ordem' => 3,
      'entrou' => '1.23.0',
      'mudou' => '1.39.0',
      'comece' => 
      array (
      ),
      'relacionados' => 
      array (
        0 => 'cadastrar-aluno-e-enviar-link',
        1 => 'vincular-e-enturmar',
        2 => 'primeiros-passos',
      ),
      'telas' => 
      array (
        0 => 'importacao-alunos/listar',
        1 => 'importacao-alunos/conferir',
      ),
      'imagens' => 
      array (
      ),
      'tipo' => '',
      'video' => 
      array (
        'arquivo' => 'importar-alunos-de-planilha.mp4',
        'poster' => 'importar-alunos-de-planilha.jpg',
        'legenda' => 'importar-alunos-de-planilha.vtt',
        'capitulos_vtt' => 'importar-alunos-de-planilha.capitulos.vtt',
        'duracao' => 52.9,
        'versao' => '1.42.0',
        'capitulos' => 
        array (
          0 => 
          array (
            'inicio' => 2.0,
            'titulo' => 'Enviar a planilha',
          ),
          1 => 
          array (
            'inicio' => 15.6,
            'titulo' => 'Conferir',
          ),
          2 => 
          array (
            'inicio' => 34.1,
            'titulo' => 'Aplicar',
          ),
        ),
        'transcricao' => 'Importar alunos. Educacional → Importar alunos. "Baixar o modelo" traz as colunas; a planilha pode ter outros nomes de coluna.
Enviar e conferir. Escolha o arquivo (CSV ou planilha) e clique em "Enviar e conferir": nada é gravado ainda.
As colunas. As colunas: em que campo cada coluna entra. O que não for reconhecido fica "(não importar)" até você escolher.
Os valores. Os valores: cada valor diferente de sexo, situação e modalidade e o que ele virou. Valor não reconhecido não é adivinhado.
As linhas. As linhas: Pronta, Com erro (com o motivo) ou Pulada (o RA já está cadastrado). "Corrigir" acerta uma linha aqui mesmo.
Aplicar. Com linhas ainda com erro, marque "Aplicar sem as linhas com erro" e clique em "Aplicar a importação".
✓ Importado. Os alunos novos recebem o primeiro acesso pelo "Enviar link de acesso", como os cadastrados na tela.',
      ),
    ),
    'importar-responsaveis' => 
    array (
      'titulo' => 'Importar responsáveis de planilha',
      'resumo' => 'Uma planilha com uma linha por papel (o RA do aluno, o ano, financeiro ou titular, e a pessoa). O sistema confere todas as linhas antes de gravar; uma com problema, nada é gravado.',
      'perfis' => 
      array (
        0 => 'administrador',
        1 => 'secretaria',
      ),
      'ordem' => 15,
      'entrou' => '1.52.0',
      'mudou' => '',
      'comece' => 
      array (
      ),
      'relacionados' => 
      array (
        0 => 'cadastrar-um-responsavel',
        1 => 'designar-o-financeiro-e-o-titular',
      ),
      'telas' => 
      array (
        0 => 'responsaveis/importar',
      ),
      'imagens' => 
      array (
      ),
      'tipo' => 'escola',
    ),
    'inativar-curso-ou-materia' => 
    array (
      'titulo' => 'Inativar curso ou matéria',
      'resumo' => 'Tirar das listas de escolha um curso ou uma matéria que não vai mais ser oferecido, sem mexer no que já foi feito.',
      'perfis' => 
      array (
        0 => 'administrador',
        1 => 'secretaria',
      ),
      'ordem' => 12,
      'entrou' => '1.9.0',
      'mudou' => '',
      'comece' => 
      array (
      ),
      'relacionados' => 
      array (
        0 => 'cadastrar-materias',
        1 => 'polos-e-cursos',
        2 => 'montar-a-matriz',
      ),
      'telas' => 
      array (
        0 => 'cursos/situacao',
        1 => 'materias/situacao',
      ),
      'imagens' => 
      array (
        0 => 'administrador_05_cursos.png',
        1 => 'secretaria_02_materias.png',
      ),
      'tipo' => '',
      'video' => 
      array (
        'arquivo' => 'inativar-curso-ou-materia.mp4',
        'poster' => 'inativar-curso-ou-materia.jpg',
        'legenda' => 'inativar-curso-ou-materia.vtt',
        'capitulos_vtt' => 'inativar-curso-ou-materia.capitulos.vtt',
        'duracao' => 62.3,
        'versao' => '1.42.0',
        'capitulos' => 
        array (
          0 => 
          array (
            'inicio' => 2.0,
            'titulo' => 'Curso em uso não se apaga',
          ),
          1 => 
          array (
            'inicio' => 9.3,
            'titulo' => 'Inativar o curso',
          ),
          2 => 
          array (
            'inicio' => 20.1,
            'titulo' => 'Fora das listas de escolha',
          ),
          3 => 
          array (
            'inicio' => 40.8,
            'titulo' => 'Reativar',
          ),
          4 => 
          array (
            'inicio' => 48.1,
            'titulo' => 'Matéria: o mesmo caminho',
          ),
        ),
        'transcricao' => 'Ensino → Cursos. O curso de Docência no Ensino Superior já tem grade e matrícula: o Apagar fica desabilitado.
A dica diz: "Em uso: 1 grade(s), 2 matrícula(s), 4 oferta(s). Não pode ser apagado.". É o caso de INATIVAR.
Na linha, clique em Inativar (ícone de proibido) e confirme.
A situação passa a Inativo e, no lugar do botão, aparece Reativar. Ofertas, notas e históricos não mudam.
Em Ensino → Matriz, o curso inativo aparece com a etiqueta Inativo, e "Nova grade" fica desabilitado.
A dica explica: "Curso inativo: reative-o em Cursos para criar uma grade". O mesmo vale para nova oferta e vínculo do aluno.
E na tela "Nova grade", o seletor de curso só traz cursos ativos: o de Docência no Ensino Superior, inativo, não aparece.
Para voltar a oferecer, clique em Reativar: o curso volta às listas de escolha.
Matéria: Ensino → Matérias (a secretaria também pode). Inativar e confirmar.
Inativa, ela sai da matriz e da nova oferta, mas continua aqui, com a etiqueta Inativo.
Reativar devolve a matéria às listas de escolha.',
      ),
    ),
    'instalar-como-curso-livre' => 
    array (
      'titulo' => 'Instalar como curso livre',
      'resumo' => 'O curso livre (curso on-line) na instalação: o que vem ligado, o financeiro e a matrícula de ligar e desligar, o curso, a turma aberta e o portal do aluno.',
      'perfis' => 
      array (
        0 => 'administrador',
      ),
      'ordem' => 3,
      'entrou' => '1.100.0',
      'mudou' => '',
      'comece' => 
      array (
      ),
      'relacionados' => 
      array (
        0 => 'o-tipo-de-instituicao',
        1 => 'conhecer-com-a-demonstracao',
        2 => 'integrar-outro-sistema-pela-api',
        3 => 'conectar-ao-moodle',
      ),
      'telas' => 
      array (
        0 => 'recursos-livre/index',
      ),
      'imagens' => 
      array (
      ),
      'tipo' => '',
    ),
    'integrar-outro-sistema-pela-api' => 
    array (
      'titulo' => 'Integrar outro sistema pela API',
      'resumo' => 'Deixar outro sistema consultar e cadastrar alunos, matrículas e enturmações, pelas mesmas regras das telas.',
      'perfis' => 
      array (
        0 => 'administrador',
      ),
      'ordem' => 4,
      'entrou' => '1.24.0',
      'mudou' => '1.80.0',
      'comece' => 
      array (
      ),
      'relacionados' => 
      array (
        0 => 'importar-alunos-de-planilha',
        1 => 'cadastrar-aluno-e-enviar-link',
        2 => 'vincular-e-enturmar',
      ),
      'telas' => 
      array (
        0 => 'integracoes/chaves',
        1 => 'integracoes/documentacao',
        2 => 'integracoes/chamadas',
      ),
      'imagens' => 
      array (
      ),
      'tipo' => '',
      'video' => 
      array (
        'arquivo' => 'integrar-outro-sistema-pela-api.mp4',
        'poster' => 'integrar-outro-sistema-pela-api.jpg',
        'legenda' => 'integrar-outro-sistema-pela-api.vtt',
        'capitulos_vtt' => 'integrar-outro-sistema-pela-api.capitulos.vtt',
        'duracao' => 57.2,
        'versao' => '1.42.0',
        'capitulos' => 
        array (
          0 => 
          array (
            'inicio' => 2.0,
            'titulo' => 'A chave, a documentação e as chamadas',
          ),
        ),
        'transcricao' => 'Quem vai usar. Uma chave por sistema que se integra: assim dá para revogar uma sem parar as outras. Agora: Escreva o nome do sistema.
O que a chave pode fazer. Para cada assunto: nada, consultar, ou consultar e gravar. Marque só o que o outro sistema precisa. Agora: Marque Alunos: consultar e gravar e clique em Avançar.
Criar. A chave é criada e aparece uma vez só. Agora: Clique em Criar.
A chave nova. Copie a chave e entregue ao outro sistema por um canal seguro: ele a envia em toda chamada. Ela não aparece de novo. Agora: Copie e clique em Avançar.
A documentação. Todas as operações, com os campos, as respostas e o limite que cada uma pede. Para testar: Authorize, cole a chave e use Try it out. Agora: Clique em Avançar.
As chamadas. Cada chamada, com a chave, a operação, o resultado e a hora. Uma chave que vazou: Revogar, na lista das chaves.',
      ),
    ),
    'lancar-faltas' => 
    array (
      'titulo' => 'Lançar faltas',
      'resumo' => 'Faltas por aluno, digitadas ou por planilha rm;faltas; a frequência sai do total de encontros e das compensadas.',
      'perfis' => 
      array (
        0 => 'professor',
        1 => 'secretaria',
        2 => 'administrador',
      ),
      'ordem' => 32,
      'entrou' => '1.2.0',
      'mudou' => '1.14.0',
      'comece' => 
      array (
        'professor' => 5,
        'secretaria' => 10,
      ),
      'relacionados' => 
      array (
        0 => 'compensar-faltas',
        1 => 'fazer-a-chamada',
        2 => 'lancar-notas',
        3 => 'acompanhar-notas-e-resultado',
        4 => 'encerrar-uma-disciplina',
      ),
      'telas' => 
      array (
        0 => 'faltas/visualizar',
        1 => 'faltas/editar',
      ),
      'imagens' => 
      array (
        0 => 'professor_09_faltas.png',
      ),
      'tipo' => 'escola,superior',
      'video' => 
      array (
        'arquivo' => 'lancar-faltas.mp4',
        'poster' => 'lancar-faltas.jpg',
        'legenda' => 'lancar-faltas.vtt',
        'capitulos_vtt' => 'lancar-faltas.capitulos.vtt',
        'duracao' => 53.2,
        'versao' => '1.42.0',
        'capitulos' => 
        array (
          0 => 
          array (
            'inicio' => 2.0,
            'titulo' => 'Abrir as faltas',
          ),
          1 => 
          array (
            'inicio' => 9.2,
            'titulo' => 'Digitar e salvar',
          ),
          2 => 
          array (
            'inicio' => 19.7,
            'titulo' => 'Por planilha: tudo ou nada',
          ),
        ),
        'transcricao' => 'Ensino → Minhas Disciplinas: o atalho Faltas de cada disciplina (ou Faltas, na barra da disciplina).
Frequência = 1 − (faltas − compensadas) ÷ total de encontros. Aqui são 15 encontros; o mínimo é 75%.
Digite as faltas de cada aluno e clique em "Salvar faltas".
Salvo. 5 faltas em 15 encontros: 66,67%, em vermelho, "Abaixo do mínimo". A coluna Compensadas é da secretaria.
Por planilha: "Importar faltas por planilha", um CSV rm;faltas. "Baixar o modelo" traz o cabeçalho.
Um arquivo com uma linha errada: 20 faltas, mais que os 15 encontros.
Recusado: nada é gravado, e a tela mostra a linha com o erro ("… é mais que o total de 15 encontros").
Corrigido o arquivo, envie de novo.
"Faltas importadas: 4 aluno(s). Tudo foi gravado." A frequência de cada um é recalculada.',
      ),
    ),
    'lancar-notas' => 
    array (
      'titulo' => 'Lançar notas',
      'resumo' => 'Nota por aluno na tela da atividade ou por planilha CSV (inclusive exportada do Moodle); vírgula ou ponto, de 0 a 10; a planilha grava tudo ou nada.',
      'perfis' => 
      array (
        0 => 'professor',
        1 => 'administrador',
      ),
      'ordem' => 31,
      'entrou' => '1.0.0',
      'mudou' => '1.95.0',
      'comece' => 
      array (
        'professor' => 4,
      ),
      'relacionados' => 
      array (
        0 => 'minhas-disciplinas-e-atividades',
        1 => 'acompanhar-notas-e-resultado',
        2 => 'lancar-faltas',
      ),
      'telas' => 
      array (
        0 => 'atividades/visualizar',
        1 => 'atividades/upload-notas',
      ),
      'imagens' => 
      array (
        0 => 'professor_06_notas.png',
        1 => 'professor_08_importar_csv.png',
        2 => 'professor_07_nota_invalida.png',
      ),
      'tipo' => 'escola,superior',
      'video' => 
      array (
        'arquivo' => 'lancar-notas.mp4',
        'poster' => 'lancar-notas.jpg',
        'legenda' => 'lancar-notas.vtt',
        'capitulos_vtt' => 'lancar-notas.capitulos.vtt',
        'duracao' => 54.6,
        'versao' => '1.42.0',
        'capitulos' => 
        array (
          0 => 
          array (
            'inicio' => 2.0,
            'titulo' => 'Abrir a atividade',
          ),
          1 => 
          array (
            'inicio' => 5.0,
            'titulo' => 'Nota pela tela',
          ),
          2 => 
          array (
            'inicio' => 21.6,
            'titulo' => 'Por planilha: tudo ou nada',
          ),
        ),
        'transcricao' => 'Na disciplina, Atividades: abra a atividade em que vai lançar as notas.
Clique na célula Nota do aluno, digite e aperte Enter: grava na hora. De 0 a 10, até 2 casas.
Uma nota fora da escala (12) é recusada: nada é gravado.
A linha fica marcada em vermelho claro, legível, e a célula volta ao valor gravado (aqui, vazio): o 12 não foi salvo.
Por planilha: "Atualizar via Planilha", com um CSV rm;nota separado por ponto e vírgula.
Primeiro, um arquivo com duas linhas erradas: uma nota 11 e um RA que não existe.
Recusado inteiro: "Nenhuma nota foi gravada", com o número da linha de cada erro. Corrija e envie de novo.
Agora o arquivo corrigido (vírgula ou ponto na nota; colunas a mais são ignoradas).
"Notas importadas: 6 aluno(s). Tudo foi gravado." As notas aparecem na atividade.',
      ),
    ),
    'liberar-o-boletim' => 
    array (
      'titulo' => 'Liberar o boletim no portal',
      'resumo' => 'Por ano letivo, o que a família e o aluno veem do boletim no portal — cada etapa, a média final e a situação final —, já ou a partir de uma data e hora.',
      'perfis' => 
      array (
        0 => 'administrador',
        1 => 'secretaria',
      ),
      'ordem' => 8,
      'entrou' => '1.94.0',
      'mudou' => '',
      'comece' => 
      array (
      ),
      'relacionados' => 
      array (
        0 => 'avaliar-na-escola',
        1 => 'o-que-o-responsavel-ve-no-portal',
      ),
      'telas' => 
      array (
        0 => 'escola-liberacao/index',
      ),
      'imagens' => 
      array (
      ),
      'tipo' => 'escola',
    ),
    'liberar-um-acesso-academico-adicional' => 
    array (
      'titulo' => 'Liberar um acesso acadêmico adicional',
      'resumo' => 'A escola pode liberar o acompanhamento acadêmico do aluno, num ano, a outras pessoas que não estão no contrato, com o motivo e a referência do termo.',
      'perfis' => 
      array (
        0 => 'administrador',
        1 => 'secretaria',
      ),
      'ordem' => 13,
      'entrou' => '1.52.0',
      'mudou' => '',
      'comece' => 
      array (
      ),
      'relacionados' => 
      array (
        0 => 'designar-o-financeiro-e-o-titular',
      ),
      'telas' => 
      array (
      ),
      'imagens' => 
      array (
      ),
      'tipo' => 'escola',
    ),
    'minhas-disciplinas-e-atividades' => 
    array (
      'titulo' => 'Minhas disciplinas e atividades',
      'resumo' => 'Achar as suas disciplinas do ano e criar as atividades que compõem a média.',
      'perfis' => 
      array (
        0 => 'professor',
        1 => 'administrador',
      ),
      'ordem' => 30,
      'entrou' => '1.0.0',
      'mudou' => '1.72.0',
      'comece' => 
      array (
        'professor' => 3,
      ),
      'relacionados' => 
      array (
        0 => 'lancar-notas',
        1 => 'lancar-faltas',
        2 => 'acompanhar-notas-e-resultado',
      ),
      'telas' => 
      array (
        0 => 'minhas-disciplinas/listar',
        1 => 'atividades/listar',
        2 => 'atividades/cadastrar',
      ),
      'imagens' => 
      array (
        0 => 'professor_02_minhas_disciplinas.png',
        1 => 'professor_03_disciplina.png',
        2 => 'professor_05_atividade_cadastrar.png',
      ),
      'tipo' => 'escola,superior',
      'video' => 
      array (
        'arquivo' => 'minhas-disciplinas-e-atividades.mp4',
        'poster' => 'minhas-disciplinas-e-atividades.jpg',
        'legenda' => 'minhas-disciplinas-e-atividades.vtt',
        'capitulos_vtt' => 'minhas-disciplinas-e-atividades.capitulos.vtt',
        'duracao' => 49.1,
        'versao' => '1.42.0',
        'capitulos' => 
        array (
          0 => 
          array (
            'inicio' => 2.0,
            'titulo' => 'Minhas Disciplinas',
          ),
          1 => 
          array (
            'inicio' => 9.4,
            'titulo' => 'Dentro da disciplina',
          ),
          2 => 
          array (
            'inicio' => 20.0,
            'titulo' => 'Cadastrar atividade',
          ),
        ),
        'transcricao' => 'Ensino → Minhas Disciplinas: as disciplinas do ano em que você é o professor.
Em cada linha: a quantidade de alunos, a situação (Aberta ou Encerrada) e os atalhos Atividades, Notas e Faltas.
Clique em Atividades. A barra do topo leva a Atividades, Notas e resultado, Faltas e Encerramento.
O cartão "Dados da disciplina" mostra curso, turma, polo, modalidade, datas e encontros.
"Cadastrar atividade": escolha o tipo. Os tipos desativados (Acréscimo, Recuperação…) não aparecem.
Nome, conteúdo e as datas. Salve.
A atividade está na lista. Ao salvar, cada aluno ganhou a linha de nota.
Os 8 alunos da turma, prontos para a nota. Aluno enturmado depois? "Atualizar lista", em Notas e resultado.',
      ),
    ),
    'montar-a-matriz' => 
    array (
      'titulo' => 'Montar a matriz',
      'resumo' => 'A grade do curso e a lista das disciplinas, com a carga horária somada.',
      'perfis' => 
      array (
        0 => 'administrador',
        1 => 'secretaria',
      ),
      'ordem' => 11,
      'entrou' => '1.0.0',
      'mudou' => '1.18.1',
      'comece' => 
      array (
        'secretaria' => 5,
      ),
      'relacionados' => 
      array (
        0 => 'cadastrar-materias',
        1 => 'situacao-da-grade',
        2 => 'gerar-a-oferta-do-ano',
      ),
      'telas' => 
      array (
        0 => 'matriz/listar',
        1 => 'matriz/visualizar',
        2 => 'matriz/cadastrar-grade',
      ),
      'imagens' => 
      array (
        0 => 'secretaria_04_matriz.png',
        1 => 'secretaria_05_grade.png',
      ),
      'tipo' => '',
      'video' => 
      array (
        'arquivo' => 'montar-a-matriz.mp4',
        'poster' => 'montar-a-matriz.jpg',
        'legenda' => 'montar-a-matriz.vtt',
        'capitulos_vtt' => 'montar-a-matriz.capitulos.vtt',
        'duracao' => 83.2,
        'versao' => '1.42.0',
        'capitulos' => 
        array (
          0 => 
          array (
            'inicio' => 2.0,
            'titulo' => 'Nova grade',
          ),
          1 => 
          array (
            'inicio' => 20.2,
            'titulo' => 'Incluir disciplinas',
          ),
          2 => 
          array (
            'inicio' => 42.6,
            'titulo' => 'Carga horária',
          ),
          3 => 
          array (
            'inicio' => 53.1,
            'titulo' => 'A ordem do histórico',
          ),
          4 => 
          array (
            'inicio' => 66.9,
            'titulo' => 'Mandar para Em análise',
          ),
        ),
        'transcricao' => 'Ensino → Matriz: cada curso com as suas grades. A grade 1 do curso de Gestão de Projetos já está Finalizada.
Clique em "Nova grade" no curso. O número é inteiro e não se repete no mesmo curso.
Recusado: "A grade 1 já existe no curso…". Use outro número: 2.
Grade 2 criada, Aberta. Agora as disciplinas.
Em "Incluir disciplina", escolha a matéria do catálogo e clique em Incluir. CH vazia = a do catálogo.
Cada disciplina nova entra no fim da lista. A CH da grade é a soma, feita pelo sistema.
A mesma matéria não entra duas vezes: vamos tentar incluir Liderança de Equipes de novo.
Recusado: "«Liderança de Equipes» já está nesta grade."
Para mudar a CH de uma disciplina: digite o número na linha e clique no disquete. Aqui, Planejamento e Cronograma passa a 90 h.
A CH da grade foi recalculada: 60 + 90 + 60 = 210 h.
Ordem do currículo: arraste a linha pela alça (a 1ª coluna). Vamos levar Liderança de Equipes para o topo.
Depois de arrastar, clique em "Salvar a ordem". É a ordem em que as disciplinas saem no histórico.
Pronta, mande a grade para "Em análise" em Situação da grade. Quem finaliza é o administrador.
Em análise, a grade trava: a faixa diz o motivo e o caminho para destravar, e os botões ficam desabilitados.',
      ),
    ),
    'montar-o-horario-da-turma' => 
    array (
      'titulo' => 'Montar o horário da turma',
      'resumo' => 'As aulas do dia de cada turno e, na tela da turma, a disciplina de cada aula da semana. O aluno e o responsável veem a semana no portal.',
      'perfis' => 
      array (
        0 => 'administrador',
        1 => 'secretaria',
      ),
      'ordem' => 9,
      'entrou' => '1.61.0',
      'mudou' => '',
      'comece' => 
      array (
      ),
      'relacionados' => 
      array (
        0 => 'organizar-a-escola',
        1 => 'completar-a-oferta',
        2 => 'o-que-o-responsavel-ve-no-portal',
      ),
      'telas' => 
      array (
      ),
      'imagens' => 
      array (
      ),
      'tipo' => 'escola',
    ),
    'o-financeiro' => 
    array (
      'titulo' => 'O financeiro: a ficha financeira, receber, o recibo e os desfazer',
      'resumo' => 'A ficha financeira do aluno (os responsáveis do ano, os totais, as abas, os filtros e o Em lote); Receber um ou vários títulos, com o recibo; Baixar, Cancelar, Renegociar e os desfazer; o caixa do dia e os recebimentos por período.',
      'perfis' => 
      array (
        0 => 'administrador',
        1 => 'secretaria',
      ),
      'ordem' => 40,
      'entrou' => '1.85.0',
      'mudou' => '1.99.0',
      'comece' => 
      array (
      ),
      'relacionados' => 
      array (
        0 => 'vincular-e-enturmar',
        1 => 'o-que-o-responsavel-ve-no-portal',
      ),
      'telas' => 
      array (
        0 => 'fin-regras/index',
        1 => 'fin-planos/listar',
        2 => 'fin-motivos/listar',
        3 => 'fin-bandeiras/listar',
        4 => 'financeiro/ficha',
        5 => 'financeiro/receber-pagamento',
        6 => 'financeiro/desfazer-recebimento',
        7 => 'financeiro/titulo',
        8 => 'financeiro/renegociar-titulos',
      ),
      'imagens' => 
      array (
      ),
      'tipo' => '',
    ),
    'o-que-cada-papel-ve' => 
    array (
      'titulo' => 'O que cada papel vê',
      'resumo' => 'O responsável financeiro, o acadêmico titular e o acesso acadêmico adicional veem o mesmo acompanhamento escolar, só do aluno e do ano em que têm o papel.',
      'perfis' => 
      array (
        0 => 'administrador',
        1 => 'secretaria',
      ),
      'ordem' => 20,
      'entrou' => '1.56.0',
      'mudou' => '',
      'comece' => 
      array (
      ),
      'relacionados' => 
      array (
        0 => 'designar-o-financeiro-e-o-titular',
        1 => 'liberar-um-acesso-academico-adicional',
        2 => 'o-que-o-responsavel-ve-no-portal',
      ),
      'telas' => 
      array (
      ),
      'imagens' => 
      array (
      ),
      'tipo' => 'escola',
    ),
    'o-que-o-responsavel-ve-no-portal' => 
    array (
      'titulo' => 'O que o responsável vê no portal',
      'resumo' => 'Depois de entrar, o responsável escolhe o filho e o ano e vê, só para leitura, o boletim, as notas das atividades, a frequência, o horário e a turma; e emite o atestado de matrícula.',
      'perfis' => 
      array (
        0 => 'administrador',
        1 => 'secretaria',
      ),
      'ordem' => 19,
      'entrou' => '1.54.0',
      'mudou' => '1.57.0',
      'comece' => 
      array (
      ),
      'relacionados' => 
      array (
        0 => 'entrar-no-portal-como-responsavel',
        1 => 'convidar-um-responsavel',
      ),
      'telas' => 
      array (
      ),
      'imagens' => 
      array (
      ),
      'tipo' => 'escola',
    ),
    'o-tipo-de-instituicao' => 
    array (
      'titulo' => 'O tipo de instituição',
      'resumo' => 'Ensino superior, escola (educação básica) ou curso livre: o que cada tipo traz, onde se escolhe e quando ele não muda mais.',
      'perfis' => 
      array (
        0 => 'administrador',
      ),
      'ordem' => 2,
      'entrou' => '1.38.0',
      'mudou' => '1.102.0',
      'comece' => 
      array (
      ),
      'relacionados' => 
      array (
        0 => 'conhecer-com-a-demonstracao',
        1 => 'instalar-como-curso-livre',
        2 => 'atualizar-o-sistema',
      ),
      'telas' => 
      array (
      ),
      'imagens' => 
      array (
      ),
      'tipo' => '',
    ),
    'organizar-a-escola' => 
    array (
      'titulo' => 'Organizar a escola: o ano letivo, as turmas e os alunos',
      'resumo' => 'Na escola, o ano letivo em bimestres ou trimestres, as turmas de cada série com as disciplinas, e os alunos na turma.',
      'perfis' => 
      array (
        0 => 'administrador',
        1 => 'secretaria',
      ),
      'ordem' => 6,
      'entrou' => '1.39.0',
      'mudou' => '1.96.0',
      'comece' => 
      array (
      ),
      'relacionados' => 
      array (
        0 => 'o-tipo-de-instituicao',
      ),
      'telas' => 
      array (
        0 => 'escola/index',
        1 => 'escola-anos/index',
        2 => 'turmas/listar',
        3 => 'turmas/cadastrar',
        4 => 'turmas/editar',
        5 => 'turmas/ver',
        6 => 'escola-regras/index',
      ),
      'imagens' => 
      array (
      ),
      'tipo' => 'escola',
      'video' => 
      array (
        'arquivo' => 'organizar-a-escola.mp4',
        'poster' => 'organizar-a-escola.jpg',
        'legenda' => 'organizar-a-escola.vtt',
        'capitulos_vtt' => 'organizar-a-escola.capitulos.vtt',
        'duracao' => 62.3,
        'versao' => '1.42.0',
        'capitulos' => 
        array (
          0 => 
          array (
            'inicio' => 2.0,
            'titulo' => 'A estrutura',
          ),
          1 => 
          array (
            'inicio' => 5.8,
            'titulo' => 'O ano letivo e as etapas',
          ),
          2 => 
          array (
            'inicio' => 19.7,
            'titulo' => 'A turma',
          ),
          3 => 
          array (
            'inicio' => 41.5,
            'titulo' => 'Trocar de turma',
          ),
        ),
        'transcricao' => 'Estrutura da escola. Ensino → Estrutura da escola: os segmentos, as séries de cada um e os componentes curriculares de cada série.
Ano letivo e etapas. Ensino → Ano letivo e etapas: o ano em quatro bimestres (o padrão) ou três trimestres, com as datas e o conselho de cada etapa.
As etapas. Com nota lançada, o modelo não muda mais. As datas se ajustam na própria tela.
Turmas. Ensino → Turmas: cada turma do ano, com a série, o turno, a sala e quantos alunos tem.
As ofertas da turma. A turma gera uma oferta por componente da série, cada uma com o seu professor. O professor vê só as turmas e os componentes dele.
Os alunos. Os alunos da turma. O cartão "Enturmar alunos" lista os do segmento ainda sem turma: marcar e enturmar põe o aluno em todos os componentes de uma vez.
Trocar de turma. Para trocar um aluno de turma: escolha a turma de destino, da mesma série, e clique em Trocar de turma. As notas e as faltas vão junto.
✓ O aluno mudou de turma, com o que já tinha sido lançado.',
      ),
    ),
    'perfis-de-acesso' => 
    array (
      'titulo' => 'Perfis de acesso',
      'resumo' => 'O que cada perfil vê e altera, a tela Perfis de acesso, a coordenação por curso e as ações sensíveis.',
      'perfis' => 
      array (
        0 => 'administrador',
      ),
      'ordem' => 62,
      'entrou' => '1.20.0',
      'mudou' => '1.66.0',
      'comece' => 
      array (
      ),
      'relacionados' => 
      array (
        0 => 'usuarios-e-niveis',
        1 => 'reabrir-uma-disciplina',
        2 => 'polos-e-cursos',
      ),
      'telas' => 
      array (
        0 => 'perfis-acesso/index',
        1 => 'cursos/visualizar',
      ),
      'imagens' => 
      array (
      ),
      'tipo' => '',
      'video' => 
      array (
        'arquivo' => 'perfis-de-acesso.mp4',
        'poster' => 'perfis-de-acesso.jpg',
        'legenda' => 'perfis-de-acesso.vtt',
        'capitulos_vtt' => 'perfis-de-acesso.capitulos.vtt',
        'duracao' => 38.2,
        'versao' => '1.42.0',
        'capitulos' => 
        array (
          0 => 
          array (
            'inicio' => 2.0,
            'titulo' => 'O que cada perfil pode',
          ),
          1 => 
          array (
            'inicio' => 21.3,
            'titulo' => 'A coordenação do curso',
          ),
        ),
        'transcricao' => 'O que cada perfil pode. As ações, agrupadas, com o limite de cada uma e um ✓ em cada perfil que pode. Agora: Percorra a tabela e clique em Avançar.
O limite. O limite é o perfil mais baixo que pode receber a ação. Os perfis vêm prontos e mudam com a atualização do sistema. Quem coordena cada curso é indicado na tela do curso (Adicionar coordenador).
Quem coordena o curso. A Coordenação de curso vê só os cursos em que é coordenadora. Quem indica é o Administrador ou a Direção acadêmica, na tela do curso.
A pessoa e a posição. Escolha a pessoa e a posição, e salve. A pessoa passa a ver, em Minhas disciplinas e em Relatórios, só os dados deste curso.
✓ A coordenação foi indicada.',
      ),
    ),
    'polos-e-cursos' => 
    array (
      'titulo' => 'Polos e cursos',
      'resumo' => 'O local da turma e o curso, com polo e modalidade padrão e a exigência de TCC.',
      'perfis' => 
      array (
        0 => 'administrador',
      ),
      'ordem' => 62,
      'entrou' => '1.1.0',
      'mudou' => '1.31.0',
      'comece' => 
      array (
        'administrador' => 4,
      ),
      'relacionados' => 
      array (
        0 => 'inativar-curso-ou-materia',
        1 => 'montar-a-matriz',
        2 => 'usuarios-e-niveis',
      ),
      'telas' => 
      array (
        0 => 'polos/listar',
        1 => 'polos/cadastrar',
        2 => 'polos/editar',
        3 => 'cursos/listar',
      ),
      'imagens' => 
      array (
        0 => 'administrador_04_polo_cadastrar.png',
        1 => 'administrador_06_curso_cadastrar.png',
      ),
      'tipo' => 'escola,superior',
      'video' => 
      array (
        'arquivo' => 'polos-e-cursos.mp4',
        'poster' => 'polos-e-cursos.jpg',
        'legenda' => 'polos-e-cursos.vtt',
        'capitulos_vtt' => 'polos-e-cursos.capitulos.vtt',
        'duracao' => 78.1,
        'versao' => '1.42.0',
        'capitulos' => 
        array (
          0 => 
          array (
            'inicio' => 2.0,
            'titulo' => 'Novo polo',
          ),
          1 => 
          array (
            'inicio' => 16.9,
            'titulo' => 'Novo curso',
          ),
          2 => 
          array (
            'inicio' => 45.5,
            'titulo' => 'Nos documentos',
          ),
          3 => 
          array (
            'inicio' => 63.9,
            'titulo' => 'Curso em uso não se apaga',
          ),
        ),
        'transcricao' => 'Configurações → Polos: onde as turmas acontecem. Clique em "Novo polo".
Nome, endereço, cidade e UF.
Polo cadastrado. Ele passa a aparecer na escolha do polo do curso, da oferta e da matrícula.
Ensino → Cursos → Cadastrar. A secretaria não cadastra polos nem cursos: só os escolhe.
O polo padrão e a modalidade padrão: a oferta e a matrícula herdam os dois (e podem trocar).
Com a modalidade EaD, o Turno some: não se aplica. Em Presencial ou Híbrido, ele é escolhido na lista.
Exige TCC = Sim: a conclusão do curso passa a exigir o TCC aprovado.
Frequência mínima do curso: opcional. Vazia, vale a da instituição (Configurações → Instituição).
Nos documentos (opcionais): nível, sigla, nome por extenso e a linha do cabeçalho, escrita como deve sair.
Curso criado. Abra o Visualizar para conferir.
O Visualizar mostra o polo, a modalidade, a frequência (a da instituição) e os quatro campos dos documentos.
Curso em uso: Apagar desabilitado — "Em uso: 1 grade(s), 8 matrícula(s), 6 oferta(s). Não pode ser apagado.". Se não vai mais ser oferecido, inative.',
      ),
    ),
    'portal-dentro-do-moodle' => 
    array (
      'titulo' => 'Portal dentro do Moodle',
      'resumo' => 'Mostrar o portal do aluno numa página do Moodle (iframe) e o que fazer se o navegador bloquear.',
      'perfis' => 
      array (
        0 => 'administrador',
      ),
      'ordem' => 65,
      'entrou' => '1.7.0',
      'mudou' => '1.7.1',
      'comece' => 
      array (
      ),
      'relacionados' => 
      array (
        0 => 'email-smtp',
        1 => 'usuarios-e-niveis',
      ),
      'telas' => 
      array (
      ),
      'imagens' => 
      array (
        0 => 'administrador_20_portal_no_moodle.png',
        1 => 'administrador_21_plano_b.png',
      ),
      'tipo' => '',
      'video' => 
      array (
        'arquivo' => 'portal-dentro-do-moodle.mp4',
        'poster' => 'portal-dentro-do-moodle.jpg',
        'legenda' => 'portal-dentro-do-moodle.vtt',
        'capitulos_vtt' => 'portal-dentro-do-moodle.capitulos.vtt',
        'duracao' => 68.2,
        'versao' => '1.42.0',
        'capitulos' => 
        array (
          0 => 
          array (
            'inicio' => 2.0,
            'titulo' => 'O que o guia pede',
          ),
          1 => 
          array (
            'inicio' => 20.6,
            'titulo' => 'O portal dentro da página do curso',
          ),
          2 => 
          array (
            'inicio' => 40.7,
            'titulo' => 'O painel nunca aparece num iframe',
          ),
        ),
        'transcricao' => 'Ajuda → "Portal dentro do Moodle". Antes: portal e Moodle em HTTPS; o recomendado é o portal num subdomínio do Moodle.
1) No servidor do portal, em portal/core/Config.php: o endereço do Moodle em ORIGENS_PERMITIDAS_IFRAME.
2) No Moodle, no curso: um recurso URL com "Embutir", ou um bloco HTML com este iframe.
Quem instala o sistema edita o arquivo no servidor. A demonstração não tem Moodle: a seguir, uma simulação.
3) Abra o curso como um aluno de teste: o portal aparece dentro da página. O aluno entra com o RA.
Entrou sem sair do curso. Agora o Boletim: se ele aparece sem pedir o login de novo, está pronto.
O boletim abriu dentro da página, sem novo login. O aluno vê boletim, notas e horário sem sair do curso.
Só o portal do aluno pode aparecer dentro de outro site. O painel da equipe, por segurança, nunca.
O quadro do painel fica recusado ("recusou a conexão"). O mesmo acontece com o portal se o endereço do Moodle faltar no passo 1.
Se o navegador do aluno bloquear o acesso dentro do Moodle, o portal mostra o botão "Abrir o portal em nova aba".',
      ),
    ),
    'previa-do-historico' => 
    array (
      'titulo' => 'Conferir a prévia do histórico',
      'resumo' => 'Os dados que o histórico vai usar, com as tentativas de cada disciplina, os alertas e a prévia em PDF.',
      'perfis' => 
      array (
        0 => 'secretaria',
        1 => 'administrador',
      ),
      'ordem' => 41,
      'entrou' => '1.4.0',
      'mudou' => '1.8.0',
      'comece' => 
      array (
      ),
      'relacionados' => 
      array (
        0 => 'emitir-historico-parcial',
        1 => 'emitir-historico-final',
        2 => 'completar-a-oferta',
        3 => 'dados-da-instituicao-e-atos-legais',
      ),
      'telas' => 
      array (
        0 => 'historico/previa',
      ),
      'imagens' => 
      array (
        0 => 'secretaria_24_previa.png',
      ),
      'tipo' => 'escola,superior',
      'video' => 
      array (
        'arquivo' => 'previa-do-historico.mp4',
        'poster' => 'previa-do-historico.jpg',
        'legenda' => 'previa-do-historico.vtt',
        'capitulos_vtt' => 'previa-do-historico.capitulos.vtt',
        'duracao' => 61.2,
        'versao' => '1.42.0',
        'capitulos' => 
        array (
          0 => 
          array (
            'inicio' => 2.0,
            'titulo' => 'Buscar o aluno',
          ),
          1 => 
          array (
            'inicio' => 9.7,
            'titulo' => 'Os alertas',
          ),
          2 => 
          array (
            'inicio' => 17.3,
            'titulo' => 'Conferir os dados',
          ),
          3 => 
          array (
            'inicio' => 37.9,
            'titulo' => 'A barra do topo',
          ),
        ),
        'transcricao' => 'Educacional → Prévia do histórico. Busque o aluno pelo RA ou pelo nome.
Escolha a matrícula e clique em "Ver prévia".
No topo, os alertas: o que falta para o histórico. Aqui, o curso exige TCC e o dele ainda não foi aprovado.
O histórico FINAL só sai sem nenhum alerta. Resolva cada um onde o alerta indica.
Os dados do aluno e a matrícula: polo, modalidade, ingresso e conclusão.
A instituição e o ato legal da modalidade da matrícula, na data de referência.
As disciplinas com todas as tentativas (a vigente em destaque): docente, titulação, datas, média, frequência, resultado.
Por fim, o TCC e os totais.
Na barra do topo: a prévia em PDF (parcial ou final), imprimir a conferência, a ficha do aluno e "Emitir histórico".
"Prévia em PDF — parcial": o documento com a tarja PRÉVIA. Nada é gravado.
Para corrigir dados pessoais, a matrícula ou o TCC: "Ir para a ficha do aluno".',
      ),
    ),
    'primeiro-acesso-do-aluno' => 
    array (
      'titulo' => 'Primeiro acesso do aluno',
      'resumo' => 'Definir a própria senha pelo link recebido da secretaria.',
      'perfis' => 
      array (
        0 => 'secretaria',
        1 => 'administrador',
      ),
      'ordem' => 70,
      'entrou' => '1.0.0',
      'mudou' => '1.19.0',
      'comece' => 
      array (
      ),
      'relacionados' => 
      array (
        0 => 'esqueci-a-senha',
        1 => 'cadastrar-aluno-e-enviar-link',
      ),
      'telas' => 
      array (
      ),
      'imagens' => 
      array (
        0 => 'aluno_02_primeiro_acesso.png',
      ),
      'tipo' => '',
      'video' => 
      array (
        'arquivo' => 'primeiro-acesso-do-aluno.mp4',
        'poster' => 'primeiro-acesso-do-aluno.jpg',
        'legenda' => 'primeiro-acesso-do-aluno.vtt',
        'capitulos_vtt' => 'primeiro-acesso-do-aluno.capitulos.vtt',
        'duracao' => 78.9,
        'versao' => '1.42.0',
        'capitulos' => 
        array (
          0 => 
          array (
            'inicio' => 2.0,
            'titulo' => 'O link de acesso',
          ),
          1 => 
          array (
            'inicio' => 6.7,
            'titulo' => 'Abrir o link',
          ),
          2 => 
          array (
            'inicio' => 33.8,
            'titulo' => 'As recusas',
          ),
          3 => 
          array (
            'inicio' => 48.3,
            'titulo' => 'Definir a senha',
          ),
          4 => 
          array (
            'inicio' => 58.6,
            'titulo' => 'O link vale uma vez só',
          ),
          5 => 
          array (
            'inicio' => 63.5,
            'titulo' => 'Entrar no portal',
          ),
        ),
        'transcricao' => 'Antes: a secretaria cadastrou o aluno e enviou o link de acesso (por e-mail ou entregue pessoalmente).
Agora o aluno, no computador dele, abre o link recebido.
O link abre "Definir a senha". O código do link sai da barra de endereço e não fica gravado no servidor.
A nova senha, duas vezes: pelo menos 12 caracteres. Primeiro, com as duas diferentes…
Recusado na hora: "Os campos de senha estão diferentes!" — nada é enviado.
…e com uma senha curta demais.
Recusada: a senha precisa ter pelo menos 12 caracteres. Senhas muito comuns também são recusadas.
Agora certo: a mesma senha nas duas caixas. Clique em Atualizar Senha.
"Senha definida. Entre com ela no portal." Ninguém da instituição fica sabendo dela.
Abrir o mesmo link de novo não funciona: ele vale uma vez só (e por 48 horas, se a instituição não mudou).
Pronto: o aluno entra no portal com o RA e a senha que acabou de definir.
Dentro do portal: boletim, notas das atividades, horário e ajuda.',
      ),
    ),
    'primeiros-passos' => 
    array (
      'titulo' => 'Primeiros passos',
      'resumo' => 'Configurar a instituição uma fase de cada vez, com o Faça comigo em cada fase.',
      'perfis' => 
      array (
        0 => 'administrador',
      ),
      'ordem' => 2,
      'entrou' => '1.22.0',
      'mudou' => '1.33.0',
      'comece' => 
      array (
      ),
      'relacionados' => 
      array (
        0 => 'conhecer-com-a-demonstracao',
        1 => 'dados-da-instituicao-e-atos-legais',
        2 => 'montar-a-matriz',
        3 => 'cadastrar-aluno-e-enviar-link',
      ),
      'telas' => 
      array (
        0 => 'primeiros-passos/index',
      ),
      'imagens' => 
      array (
      ),
      'tipo' => '',
      'video' => 
      array (
        'arquivo' => 'primeiros-passos.mp4',
        'poster' => 'primeiros-passos.jpg',
        'legenda' => 'primeiros-passos.vtt',
        'capitulos_vtt' => 'primeiros-passos.capitulos.vtt',
        'duracao' => 97.3,
        'versao' => '1.42.0',
        'capitulos' => 
        array (
          0 => 
          array (
            'inicio' => 2.0,
            'titulo' => 'Começar com os dados da instituição',
          ),
          1 => 
          array (
            'inicio' => 11.1,
            'titulo' => 'As fases',
          ),
          2 => 
          array (
            'inicio' => 19.7,
            'titulo' => 'Faça comigo',
          ),
        ),
        'transcricao' => 'Começar com os dados da minha instituição. Terminada a visita, a demonstração sai sem reinstalar: os dados fictícios são apagados e fica só a conta do administrador.
✓ A demonstração saiu, em passos.
Primeiros passos. Os Primeiros passos: as fases na ordem em que a instituição se monta. A próxima fica em destaque.
As fases travadas. A fase que depende de outra fica travada e diz qual falta, até a anterior ser feita.
Faça comigo. Cada fase tem o "Faça comigo": uma janela diz o que fazer, destaca o campo e confere cada passo.
O nome da instituição. É o nome que sai no menu, na tela de entrada e nos documentos. Agora: Escreva o nome da instituição.
A mantenedora. A razão social de quem mantém a instituição, como no CNPJ. Agora: Escreva a mantenedora.
O CNPJ. O CNPJ da mantenedora, com os dígitos verificadores; ele vai nos documentos. Agora: Escreva o CNPJ.
Contato e endereço. O telefone, o e-mail, o site e o endereço completam o cabeçalho dos documentos. Nenhum deles é obrigatório. Agora: Preencha o que tiver e clique em Avançar.
O logo. Um arquivo PNG ou JPG, de até 1 MB. Ele aparece no menu, na tela de entrada e no cabeçalho dos documentos. Agora: Escolha o arquivo do logo.
Salvar. Os dados são gravados e o logo passa a aparecer no menu. Agora: Clique em Salvar.
Pronto. A instituição está configurada. Nos Primeiros passos, a fase aparece concluída, com a prévia do logo no menu, na entrada e no documento.
Fase concluída. A fase concluída é marcada sozinha, e a próxima passa a ser a da vez.',
      ),
    ),
    'reabrir-uma-disciplina' => 
    array (
      'titulo' => 'Reabrir uma disciplina encerrada',
      'resumo' => 'Desfazer o encerramento para corrigir nota ou falta, com justificativa registrada.',
      'perfis' => 
      array (
        0 => 'administrador',
      ),
      'ordem' => 35,
      'entrou' => '1.2.0',
      'mudou' => '1.20.0',
      'comece' => 
      array (
        'administrador' => 10,
      ),
      'relacionados' => 
      array (
        0 => 'encerrar-uma-disciplina',
        1 => 'configurar-a-regra-de-media',
        2 => 'emitir-historico-final',
      ),
      'telas' => 
      array (
      ),
      'imagens' => 
      array (
        0 => 'administrador_14_reabrir_oferta.png',
      ),
      'tipo' => 'escola,superior',
      'video' => 
      array (
        'arquivo' => 'reabrir-uma-disciplina.mp4',
        'poster' => 'reabrir-uma-disciplina.jpg',
        'legenda' => 'reabrir-uma-disciplina.vtt',
        'capitulos_vtt' => 'reabrir-uma-disciplina.capitulos.vtt',
        'duracao' => 48.8,
        'versao' => '1.42.0',
        'capitulos' => 
        array (
          0 => 
          array (
            'inicio' => 2.0,
            'titulo' => 'Abrir o encerramento',
          ),
          1 => 
          array (
            'inicio' => 12.6,
            'titulo' => 'Sem justificativa: recusado',
          ),
          2 => 
          array (
            'inicio' => 21.8,
            'titulo' => 'Com justificativa',
          ),
        ),
        'transcricao' => 'Abra a disciplina encerrada e clique em Encerramento, na barra do topo.
A faixa diz que a disciplina está encerrada. Embaixo, o quadro "Reabrir a disciplina".
A dica do quadro: reabrir exige a justificativa e NÃO apaga o resultado — o fechamento fica guardado como substituído.
Sem a justificativa, clique em Reabrir.
Recusado: "Escreva a justificativa da reabertura (ela fica no registro da disciplina)."
Escreva o motivo em Justificativa e clique em Reabrir.
Reaberta: notas e faltas voltam a ser lançadas. A prévia do encerramento aparece de novo.
Nada se apaga: o fechamento nº 1 fica "Substituído", com quem reabriu, quando e a justificativa.
O professor corrige; a secretaria encerra de novo (o fechamento nº 2). Históricos já emitidos não mudam.',
      ),
    ),
    'regras-de-nota-e-frequencia' => 
    array (
      'titulo' => 'Regras de nota e frequência',
      'resumo' => 'Média e frequência mínimas, casas decimais, média parcial no portal, módulos na matriz, login do aluno, validade do link e limites por IP.',
      'perfis' => 
      array (
        0 => 'administrador',
      ),
      'ordem' => 61,
      'entrou' => '1.2.0',
      'mudou' => '1.13.0',
      'comece' => 
      array (
        'administrador' => 5,
      ),
      'relacionados' => 
      array (
        0 => 'configurar-a-regra-de-media',
        1 => 'acompanhar-notas-e-resultado',
        2 => 'encerrar-uma-disciplina',
        3 => 'dados-da-instituicao-e-atos-legais',
        4 => 'tentativas-de-acesso',
      ),
      'telas' => 
      array (
        0 => 'parametros/listar',
      ),
      'imagens' => 
      array (
        0 => 'administrador_07_instituicao.png',
      ),
      'tipo' => 'escola,superior',
      'video' => 
      array (
        'arquivo' => 'regras-de-nota-e-frequencia.mp4',
        'poster' => 'regras-de-nota-e-frequencia.jpg',
        'legenda' => 'regras-de-nota-e-frequencia.vtt',
        'capitulos_vtt' => 'regras-de-nota-e-frequencia.capitulos.vtt',
        'duracao' => 59.0,
        'versao' => '1.42.0',
        'capitulos' => 
        array (
          0 => 
          array (
            'inicio' => 2.0,
            'titulo' => 'A tela',
          ),
          1 => 
          array (
            'inicio' => 33.6,
            'titulo' => 'Fora da faixa: recusado',
          ),
          2 => 
          array (
            'inicio' => 41.2,
            'titulo' => 'Salvar',
          ),
        ),
        'transcricao' => 'Configurações → Instituição: as regras que valem para todo o sistema.
Portal do aluno: o aluno entra com RA, CPF ou e-mail, e a validade do link de senha (1 a 720 horas).
Casas decimais e média mínima ficam na regra de média: a tela mostra os valores e o link.
Frequência mínima (padrão 75%) e "Mostrar média parcial ao aluno". O curso pode ter a sua própria frequência.
"Organizar a matriz em módulos" (Sim ou Não), os textos dos documentos e a média final do histórico.
Os limites de tentativas por IP: login, "Esqueci a senha" e a verificação pública.
Uma validade de link de 1000 horas, fora da faixa. Salvar:
Recusado: a validade do link vai de 1 a 720 horas. Nada foi salvo; a tela aponta o campo.
Corrigido para 72 horas, e a frequência mínima em 80%. Salvar.
Salvo. Vale para as disciplinas abertas e os próximos encerramentos; a encerrada guarda as regras do dia.',
      ),
    ),
    'relatorios-a-painel' => 
    array (
      'titulo' => 'Relatórios A: painel acadêmico',
      'resumo' => 'Os números da instituição numa tela — alunos, situações, ofertas, aprovação, reprovação, frequência, risco e pendências.',
      'perfis' => 
      array (
        0 => 'administrador',
        1 => 'secretaria',
      ),
      'ordem' => 61,
      'entrou' => '1.17.0',
      'mudou' => '1.67.0',
      'comece' => 
      array (
      ),
      'relacionados' => 
      array (
        0 => 'relatorios-visao-geral',
        1 => 'relatorios-c-desempenho',
        2 => 'relatorios-e-risco-conclusao',
      ),
      'telas' => 
      array (
        0 => 'relatorios/painel-academico',
      ),
      'imagens' => 
      array (
      ),
      'tipo' => 'escola,superior',
      'video' => 
      array (
        'arquivo' => 'relatorios-a-painel.mp4',
        'poster' => 'relatorios-a-painel.jpg',
        'legenda' => 'relatorios-a-painel.vtt',
        'capitulos_vtt' => 'relatorios-a-painel.capitulos.vtt',
        'duracao' => 51.9,
        'versao' => '1.42.0',
        'capitulos' => 
        array (
          0 => 
          array (
            'inicio' => 2.0,
            'titulo' => 'Abrir e filtrar',
          ),
          1 => 
          array (
            'inicio' => 9.1,
            'titulo' => 'Os cartões',
          ),
          2 => 
          array (
            'inicio' => 23.2,
            'titulo' => 'A lista de cada número',
          ),
          3 => 
          array (
            'inicio' => 39.0,
            'titulo' => 'Período sem encerramento',
          ),
        ),
        'transcricao' => 'Relatórios → Painel acadêmico: como está a instituição agora, num relance.
Filtre pelo curso e, se quiser, pelo período de encerramento. Clique em Filtrar.
Alunos ativos (matrícula em Cursando), matrículas por situação e as ofertas abertas e encerradas.
Aprovação e reprovação (por nota, por frequência) e a frequência média usam só resultados gravados de ofertas encerradas.
Alunos em risco, pendentes, ofertas com pendência e atrasadas olham as ofertas abertas.
Clique no número para ver quem compõe o número.
A lista dos 8 alunos ativos. "Voltar ao relatório" volta aos cartões.
Nos percentuais, clique em "Ver a lista (n)": os aprovados que formam a taxa.
Cada linha mostra a oferta, a média, a frequência e o resultado gravado.
O período filtra a data do encerramento. Num período sem oferta encerrada, os percentuais aparecem como "—".
Para ver de novo tudo, limpe o período (ou clique em Limpar).',
      ),
    ),
    'relatorios-b-secretaria' => 
    array (
      'titulo' => 'Relatórios B: secretaria e cadastro',
      'resumo' => 'Alunos por curso e situação, lista de turma (diário), previsão de conclusão e o que falta no cadastro para emitir documentos.',
      'perfis' => 
      array (
        0 => 'administrador',
        1 => 'secretaria',
        2 => 'professor',
      ),
      'ordem' => 62,
      'entrou' => '1.17.0',
      'mudou' => '',
      'comece' => 
      array (
      ),
      'relacionados' => 
      array (
        0 => 'relatorios-visao-geral',
        1 => 'cadastrar-aluno-e-enviar-link',
        2 => 'vincular-e-enturmar',
      ),
      'telas' => 
      array (
        0 => 'relatorios/alunos-situacao',
        1 => 'relatorios/lista-turma',
        2 => 'relatorios/previsao-conclusao',
        3 => 'relatorios/qualidade-cadastro',
      ),
      'imagens' => 
      array (
      ),
      'tipo' => '',
      'video' => 
      array (
        'arquivo' => 'relatorios-b-secretaria.mp4',
        'poster' => 'relatorios-b-secretaria.jpg',
        'legenda' => 'relatorios-b-secretaria.vtt',
        'capitulos_vtt' => 'relatorios-b-secretaria.capitulos.vtt',
        'duracao' => 53.0,
        'versao' => '1.42.0',
        'capitulos' => 
        array (
          0 => 
          array (
            'inicio' => 2.0,
            'titulo' => 'Alunos por curso e situação',
          ),
          1 => 
          array (
            'inicio' => 9.6,
            'titulo' => 'Lista de turma',
          ),
          2 => 
          array (
            'inicio' => 22.9,
            'titulo' => 'Previsão de conclusão',
          ),
          3 => 
          array (
            'inicio' => 35.7,
            'titulo' => 'Qualidade do cadastro',
          ),
        ),
        'transcricao' => 'Alunos por curso e situação: uma linha por matrícula. Filtre por curso, grade, situação, polo ou ingresso.
Os cartões contam cada situação. O nome do aluno abre o desempenho dele.
Lista de turma: escolha a oferta e clique em Filtrar.
Os enturmados da oferta, numerados. A coluna Assinatura vai em branco, para o diário de papel.
Use PDF ou Imprimir para tirar o diário. O PDF é baixado (e a exportação fica registrada).
Previsão de conclusão: as matrículas em Cursando por mês da previsão, em gráfico e em tabela.
A linha "sem previsão" mostra quem ainda não tem a data. Clique no número para ver quem é.
Qualidade do cadastro: o que falta em cada matrícula para emitir documentos.
A contagem por tipo: sexo, CPF, nascimento, naturalidade, RG, filiação, previsão, enturmação…
Em "Matrículas com falta", o que falta a cada uma; "Editar" abre a ficha do aluno para completar.',
      ),
    ),
    'relatorios-c-desempenho' => 
    array (
      'titulo' => 'Relatórios C: desempenho acadêmico',
      'resumo' => 'Mapa de notas, resultado por disciplina, distribuição de notas, pendências de lançamento e o desempenho de cada aluno.',
      'perfis' => 
      array (
        0 => 'administrador',
        1 => 'secretaria',
        2 => 'professor',
      ),
      'ordem' => 63,
      'entrou' => '1.17.0',
      'mudou' => '1.69.0',
      'comece' => 
      array (
      ),
      'relacionados' => 
      array (
        0 => 'relatorios-visao-geral',
        1 => 'acompanhar-notas-e-resultado',
        2 => 'encerrar-uma-disciplina',
      ),
      'telas' => 
      array (
        0 => 'relatorios/mapa-notas',
        1 => 'relatorios/resultado-disciplina',
        2 => 'relatorios/distribuicao-notas',
        3 => 'relatorios/pendencias-lancamento',
        4 => 'relatorios/desempenho-aluno',
      ),
      'imagens' => 
      array (
      ),
      'tipo' => 'escola,superior',
      'video' => 
      array (
        'arquivo' => 'relatorios-c-desempenho.mp4',
        'poster' => 'relatorios-c-desempenho.jpg',
        'legenda' => 'relatorios-c-desempenho.vtt',
        'capitulos_vtt' => 'relatorios-c-desempenho.capitulos.vtt',
        'duracao' => 63.4,
        'versao' => '1.42.0',
        'capitulos' => 
        array (
          0 => 
          array (
            'inicio' => 2.0,
            'titulo' => 'Mapa de notas da oferta',
          ),
          1 => 
          array (
            'inicio' => 17.5,
            'titulo' => 'Resultado por disciplina',
          ),
          2 => 
          array (
            'inicio' => 32.5,
            'titulo' => 'Distribuição de notas',
          ),
          3 => 
          array (
            'inicio' => 40.3,
            'titulo' => 'Pendências de lançamento',
          ),
          4 => 
          array (
            'inicio' => 47.6,
            'titulo' => 'Desempenho do aluno',
          ),
        ),
        'transcricao' => 'Mapa de notas: escolha a oferta. Aqui, uma oferta ainda aberta.
Uma coluna por atividade, a média, a frequência, FS/FI e o resultado (ou "Pendente — motivo").
Oferta aberta: a média parcial (a mesma do boletim) e, à parte, a "se encerrasse hoje", provisória.
Resultado por disciplina: só as ofertas encerradas — aprovados, reprovados por nota e por frequência, média e desvio.
O gráfico mostra a taxa de reprovação de cada disciplina. Agrupe por oferta no filtro "Agrupar por".
Distribuição de notas: o histograma das médias em faixas de 1 ponto. Com uma oferta aberta, as médias provisórias.
Pendências de lançamento: por oferta aberta, o que falta antes de encerrar (nota, chamada, encontros, atraso).
O desempenho do aluno abre também pelo botão Desempenho, na ficha do aluno (aba Matrículas).
A grade inteira da matrícula: situação, média e frequência de cada disciplina, CH cumprida e a média final do documento.',
      ),
    ),
    'relatorios-d-frequencia' => 
    array (
      'titulo' => 'Relatórios D: frequência',
      'resumo' => 'A frequência de cada aluno na oferta e quem vai reprovar por falta se faltar de novo.',
      'perfis' => 
      array (
        0 => 'administrador',
        1 => 'secretaria',
        2 => 'professor',
      ),
      'ordem' => 64,
      'entrou' => '1.17.0',
      'mudou' => '1.17.4',
      'comece' => 
      array (
      ),
      'relacionados' => 
      array (
        0 => 'relatorios-visao-geral',
        1 => 'fazer-a-chamada',
        2 => 'lancar-faltas',
        3 => 'compensar-faltas',
      ),
      'telas' => 
      array (
        0 => 'relatorios/frequencia-oferta',
        1 => 'relatorios/frequencia-minimo',
      ),
      'imagens' => 
      array (
      ),
      'tipo' => 'escola,superior',
      'video' => 
      array (
        'arquivo' => 'relatorios-d-frequencia.mp4',
        'poster' => 'relatorios-d-frequencia.jpg',
        'legenda' => 'relatorios-d-frequencia.vtt',
        'capitulos_vtt' => 'relatorios-d-frequencia.capitulos.vtt',
        'duracao' => 50.6,
        'versao' => '1.42.0',
        'capitulos' => 
        array (
          0 => 
          array (
            'inicio' => 2.0,
            'titulo' => 'Frequência da oferta',
          ),
          1 => 
          array (
            'inicio' => 9.4,
            'titulo' => 'Abaixo ou perto do mínimo',
          ),
          2 => 
          array (
            'inicio' => 32.0,
            'titulo' => 'Mostrar todos',
          ),
        ),
        'transcricao' => 'Frequência da oferta: escolha a oferta. Com lançamento, as faltas lançadas; com chamada, uma coluna por encontro.
Faltas, compensadas, a frequência e FS/FI: 1 − (faltas − compensadas) ÷ encontros, a mesma conta do boletim.
Todas as ofertas abertas. Abre em "Só os alertas": abaixo do mínimo, no limite e perto.
Os cartões mostram sempre as quatro contagens, qualquer que seja o filtro.
Abaixo: já passou do mínimo. No limite: não pode faltar mais. Perto: tem falta e pode faltar até a margem.
Clique num cartão para ver a lista daquele grupo.
Para ver também os "Sem alerta", escolha Mostrar todos e clique em Filtrar. O filtro fica no link.
Ordene por "Faltas que ainda pode ter" para ver primeiro quem está pior.
A exportação (Excel, CSV, PDF, Imprimir) leva só o que a tabela mostra, com o filtro no cabeçalho.',
      ),
    ),
    'relatorios-dos-responsaveis' => 
    array (
      'titulo' => 'Relatórios dos responsáveis',
      'resumo' => 'Os alunos sem o responsável financeiro ou sem o titular no ano, por turma, e os acessos acadêmicos adicionais em vigor, com o motivo e quem liberou.',
      'perfis' => 
      array (
        0 => 'administrador',
        1 => 'secretaria',
      ),
      'ordem' => 21,
      'entrou' => '1.56.0',
      'mudou' => '',
      'comece' => 
      array (
      ),
      'relacionados' => 
      array (
        0 => 'designar-o-financeiro-e-o-titular',
        1 => 'liberar-um-acesso-academico-adicional',
        2 => 'o-que-cada-papel-ve',
      ),
      'telas' => 
      array (
        0 => 'relatorios/responsaveis-faltando',
        1 => 'relatorios/acessos-adicionais',
      ),
      'imagens' => 
      array (
      ),
      'tipo' => 'escola',
    ),
    'relatorios-e-risco-conclusao' => 
    array (
      'titulo' => 'Relatórios E: risco, evasão e conclusão',
      'resumo' => 'Alunos em risco (com o porquê), coorte de ingresso, aptos a concluir e o tempo até a conclusão.',
      'perfis' => 
      array (
        0 => 'administrador',
        1 => 'secretaria',
      ),
      'ordem' => 65,
      'entrou' => '1.17.0',
      'mudou' => '1.17.3',
      'comece' => 
      array (
      ),
      'relacionados' => 
      array (
        0 => 'relatorios-visao-geral',
        1 => 'concluir-o-curso-e-tcc',
        2 => 'emitir-historico-final',
      ),
      'telas' => 
      array (
        0 => 'relatorios/alunos-risco',
        1 => 'relatorios/aptos-concluir',
        2 => 'relatorios/coorte-ingresso',
        3 => 'relatorios/concluintes',
      ),
      'imagens' => 
      array (
      ),
      'tipo' => 'escola,superior',
      'video' => 
      array (
        'arquivo' => 'relatorios-e-risco-conclusao.mp4',
        'poster' => 'relatorios-e-risco-conclusao.jpg',
        'legenda' => 'relatorios-e-risco-conclusao.vtt',
        'capitulos_vtt' => 'relatorios-e-risco-conclusao.capitulos.vtt',
        'duracao' => 69.0,
        'versao' => '1.42.0',
        'capitulos' => 
        array (
          0 => 
          array (
            'inicio' => 2.0,
            'titulo' => 'Alunos em risco',
          ),
          1 => 
          array (
            'inicio' => 30.4,
            'titulo' => 'Aptos a concluir',
          ),
          2 => 
          array (
            'inicio' => 48.3,
            'titulo' => 'Coorte de ingresso',
          ),
          3 => 
          array (
            'inicio' => 57.5,
            'titulo' => 'Concluintes',
          ),
        ),
        'transcricao' => 'Alunos em risco: matrículas em Cursando com ao menos um sinal. O alerta não dá nota: mostra o sinal e o porquê.
Os sinais: frequência perto do mínimo, média parcial abaixo da mínima, atividades sem nota, reprovação anterior, sem enturmação.
Frequência: abaixo do mínimo, ou ao menos 1 falta não compensada e só até a margem (padrão 2). Sem falta, não entra.
Filtre por sinal para agir em grupo. Aqui, só a frequência.
Aptos a concluir: todas as disciplinas da grade aprovadas e o TCC aprovado, quando o curso exige. A mesma regra do Concluído.
O atalho abre a emissão do histórico final. Antes, mude a matrícula para Concluído, com a data (o aviso no topo lembra).
"Cursando com pendência" mostra o que falta aos outros: disciplina não aprovada na tentativa vigente ou TCC.
Coorte de ingresso (administrador e diretoria): por semestre de ingresso, quantos concluíram, seguem, trancaram ou saíram.
Evasão = cancelados + transferidos. Retenção (atraso no fluxo) = trancados + cursando com a previsão vencida.
Concluintes e tempo até a conclusão: por semestre de conclusão, o tempo médio, mediano e máximo, em meses.',
      ),
    ),
    'relatorios-f-tcc' => 
    array (
      'titulo' => 'Relatórios F: situação do TCC',
      'resumo' => 'O TCC de cada matrícula — sem TCC, em andamento, aprovado ou reprovado — com a nota e a nota mínima do curso.',
      'perfis' => 
      array (
        0 => 'administrador',
        1 => 'secretaria',
      ),
      'ordem' => 66,
      'entrou' => '1.17.0',
      'mudou' => '',
      'comece' => 
      array (
      ),
      'relacionados' => 
      array (
        0 => 'relatorios-visao-geral',
        1 => 'concluir-o-curso-e-tcc',
      ),
      'telas' => 
      array (
        0 => 'relatorios/situacao-tcc',
      ),
      'imagens' => 
      array (
      ),
      'tipo' => 'escola,superior',
      'video' => 
      array (
        'arquivo' => 'relatorios-f-tcc.mp4',
        'poster' => 'relatorios-f-tcc.jpg',
        'legenda' => 'relatorios-f-tcc.vtt',
        'capitulos_vtt' => 'relatorios-f-tcc.capitulos.vtt',
        'duracao' => 37.8,
        'versao' => '1.42.0',
        'capitulos' => 
        array (
          0 => 
          array (
            'inicio' => 2.0,
            'titulo' => 'Abrir o relatório',
          ),
          1 => 
          array (
            'inicio' => 17.3,
            'titulo' => 'A tabela',
          ),
          2 => 
          array (
            'inicio' => 20.0,
            'titulo' => 'Cobrar quem não tem TCC',
          ),
        ),
        'transcricao' => 'Relatórios → Situação do TCC: como estão os TCC de quem vai concluir.
Entram as matrículas de cursos que exigem TCC e as que têm TCC lançado. Filtre por curso, situação ou previsão.
Os cartões contam Sem TCC, Em andamento, Aprovado e Reprovado.
A tabela traz a situação do TCC, a nota, a nota mínima da regra do curso, o orientador e a data da defesa.
Para cobrar quem ainda não tem TCC: clique no cartão Sem TCC, ou filtre "Situação do TCC: Sem TCC".
Já defendeu e aparece "Sem TCC"? Lance o TCC na ficha do aluno (aba Matrícula).',
      ),
    ),
    'relatorios-g-documentos' => 
    array (
      'titulo' => 'Relatórios G: documentos emitidos',
      'resumo' => 'Históricos e atestados emitidos, por quem e de onde, cancelados e os emitidos antes de uma reabertura.',
      'perfis' => 
      array (
        0 => 'administrador',
        1 => 'secretaria',
      ),
      'ordem' => 67,
      'entrou' => '1.17.1',
      'mudou' => '1.18.2',
      'comece' => 
      array (
      ),
      'relacionados' => 
      array (
        0 => 'relatorios-visao-geral',
        1 => 'emitir-historico-parcial',
        2 => 'cancelar-uma-emissao',
      ),
      'telas' => 
      array (
        0 => 'relatorios/documentos-emitidos',
      ),
      'imagens' => 
      array (
      ),
      'tipo' => 'escola,superior',
      'video' => 
      array (
        'arquivo' => 'relatorios-g-documentos.mp4',
        'poster' => 'relatorios-g-documentos.jpg',
        'legenda' => 'relatorios-g-documentos.vtt',
        'capitulos_vtt' => 'relatorios-g-documentos.capitulos.vtt',
        'duracao' => 48.2,
        'versao' => '1.42.0',
        'capitulos' => 
        array (
          0 => 
          array (
            'inicio' => 2.0,
            'titulo' => 'Abrir e filtrar',
          ),
          1 => 
          array (
            'inicio' => 12.7,
            'titulo' => 'Os cartões',
          ),
          2 => 
          array (
            'inicio' => 27.2,
            'titulo' => 'O código abre a emissão',
          ),
        ),
        'transcricao' => 'Relatórios → Documentos emitidos: que documentos saíram e qual pode estar desatualizado.
Cada emissão gravada: histórico parcial, final e atestado, pela secretaria ou pelo portal do aluno.
Filtre por curso, período de emissão, tipo, origem ou situação (válida ou cancelada).
Os cartões contam cada tipo, as Canceladas e os Emitidos antes de reabertura.
"Emitido antes de reabertura": uma disciplina do aluno foi reaberta depois da emissão. O documento pode estar desatualizado.
Confira se a reabertura mudou a nota. Se mudou, emita de novo; o antigo, só o administrador cancela.
O código abre a emissão, com o PDF. O quadro de cancelamento aparece só para o administrador (aqui, a conta é a dele).
O administrador informa o motivo (obrigatório) e clica em "Cancelar emissão". A secretaria vê a emissão e o PDF e pede a ele.',
      ),
    ),
    'relatorios-h-auditoria' => 
    array (
      'titulo' => 'Relatórios H: auditoria e governança',
      'resumo' => 'Reaberturas de oferta, trocas e edições de regra de média, exportações de relatórios e o último acesso de cada usuário.',
      'perfis' => 
      array (
        0 => 'administrador',
      ),
      'ordem' => 68,
      'entrou' => '1.17.1',
      'mudou' => '',
      'comece' => 
      array (
      ),
      'relacionados' => 
      array (
        0 => 'relatorios-visao-geral',
        1 => 'reabrir-uma-disciplina',
        2 => 'configurar-a-regra-de-media',
        3 => 'usuarios-e-niveis',
      ),
      'telas' => 
      array (
        0 => 'relatorios/reaberturas',
        1 => 'relatorios/trocas-regra',
        2 => 'relatorios/exportacoes',
        3 => 'relatorios/usuarios-acesso',
      ),
      'imagens' => 
      array (
      ),
      'tipo' => '',
      'video' => 
      array (
        'arquivo' => 'relatorios-h-auditoria.mp4',
        'poster' => 'relatorios-h-auditoria.jpg',
        'legenda' => 'relatorios-h-auditoria.vtt',
        'capitulos_vtt' => 'relatorios-h-auditoria.capitulos.vtt',
        'duracao' => 46.3,
        'versao' => '1.42.0',
        'capitulos' => 
        array (
          0 => 
          array (
            'inicio' => 2.0,
            'titulo' => 'Reaberturas de oferta',
          ),
          1 => 
          array (
            'inicio' => 14.2,
            'titulo' => 'Trocas e edições de regra',
          ),
          2 => 
          array (
            'inicio' => 18.3,
            'titulo' => 'Exportações',
          ),
          3 => 
          array (
            'inicio' => 32.1,
            'titulo' => 'Usuários e último acesso',
          ),
        ),
        'transcricao' => 'Relatórios → H. Auditoria: quem mudou o quê, quando e por quê. Só o administrador vê estes relatórios.
Reaberturas: cada fechamento substituído, com quem encerrou, quem reabriu, quando e a justificativa.
Toda tabela de relatório exporta. Exporto esta em CSV: a exportação fica registrada.
Trocas de regra por oferta (anterior, nova, justificativa, quem) e as criações e edições de regra no catálogo.
Exportações: cada Excel, CSV, PDF ou impressão, com quem, quando, o relatório, os filtros e quantas linhas.
O link dos filtros abre o relatório como foi exportado.
Usuários e último acesso: usuários por perfil, o último login e quem, ativo, não entra há mais de N dias.
O padrão é 90 dias; mude em "Sem acesso há mais de (dias)". Use para inativar acessos parados.',
      ),
    ),
    'relatorios-visao-geral' => 
    array (
      'titulo' => 'Relatórios: visão geral',
      'resumo' => 'O menu Relatórios — cada relatório responde a uma pergunta, mostra como é calculado e abre a lista de cada número.',
      'perfis' => 
      array (
        0 => 'administrador',
        1 => 'secretaria',
        2 => 'professor',
      ),
      'ordem' => 60,
      'entrou' => '1.17.0',
      'mudou' => '1.84.0',
      'comece' => 
      array (
      ),
      'relacionados' => 
      array (
        0 => 'relatorios-a-painel',
        1 => 'relatorios-b-secretaria',
        2 => 'relatorios-c-desempenho',
        3 => 'relatorios-d-frequencia',
        4 => 'relatorios-e-risco-conclusao',
        5 => 'exportar-e-imprimir-listas',
      ),
      'telas' => 
      array (
        0 => 'relatorios/listar',
        1 => 'relatorios/parametros',
      ),
      'imagens' => 
      array (
      ),
      'tipo' => '',
      'video' => 
      array (
        'arquivo' => 'relatorios-visao-geral.mp4',
        'poster' => 'relatorios-visao-geral.jpg',
        'legenda' => 'relatorios-visao-geral.vtt',
        'capitulos_vtt' => 'relatorios-visao-geral.capitulos.vtt',
        'duracao' => 87.3,
        'versao' => '1.42.0',
        'capitulos' => 
        array (
          0 => 
          array (
            'inicio' => 2.0,
            'titulo' => 'Todos os relatórios',
          ),
          1 => 
          array (
            'inicio' => 11.3,
            'titulo' => 'Filtros no endereço',
          ),
          2 => 
          array (
            'inicio' => 20.1,
            'titulo' => 'Como é calculado',
          ),
          3 => 
          array (
            'inicio' => 31.1,
            'titulo' => 'A lista de cada número',
          ),
          4 => 
          array (
            'inicio' => 41.3,
            'titulo' => 'Colunas',
          ),
          5 => 
          array (
            'inicio' => 48.4,
            'titulo' => 'Exportar',
          ),
          6 => 
          array (
            'inicio' => 63.5,
            'titulo' => 'Salvar',
          ),
          7 => 
          array (
            'inicio' => 77.4,
            'titulo' => 'Alerta de risco',
          ),
        ),
        'transcricao' => 'Relatórios → Todos os relatórios: por categoria, de A (Painel gerencial) a H (Auditoria). Cada um responde a uma pergunta.
Cada perfil vê só os relatórios a que tem acesso. Vamos abrir "Alunos em risco".
A pergunta fica no topo. Escolha os filtros — aqui, o curso — e clique em Filtrar.
Os filtros ficam no endereço: copie o link e quem abrir verá o mesmo relatório (com o que o perfil dele permite).
"Como é calculado": a fórmula de cada número, o que entra e o que fica de fora.
Os números são clicáveis: clique no número, ou em "Ver a lista", para ver quem compõe o número.
A lista tem sempre o mesmo tamanho do número: 8 matrícula(s) em risco, com os sinais de cada uma.
O botão Colunas esconde ou mostra colunas. A exportação leva só as visíveis.
Exportar: Excel, CSV, PDF ou Imprimir. O PDF, a impressão e o Excel trazem no topo a data, a hora e os filtros.
Antes de gerar o arquivo, o sistema registra a exportação. Sem o registro, o arquivo não sai.
Em Relatórios → Exportações: quem, quando, o relatório, os filtros e quantas linhas.
Salvar guarda os filtros e as colunas com um nome.
Escolha um perfil para compartilhar: quem é do perfil abre, mas não altera nem apaga.
Os salvos aparecem em Todos os relatórios (e no botão Salvos de cada relatório), com o perfil compartilhado.
Alerta de risco (administrador): os limites dos sinais do relatório Alunos em risco.',
      ),
    ),
    'situacao-da-grade' => 
    array (
      'titulo' => 'Situação da grade (finalizar, desativar, reabrir)',
      'resumo' => 'Tornar a grade oficial, parar de aceitar aluno novo ou reabri-la, com justificativa.',
      'perfis' => 
      array (
        0 => 'administrador',
      ),
      'ordem' => 12,
      'entrou' => '1.0.0',
      'mudou' => '1.18.1',
      'comece' => 
      array (
        'administrador' => 8,
      ),
      'relacionados' => 
      array (
        0 => 'montar-a-matriz',
        1 => 'gerar-a-oferta-do-ano',
      ),
      'telas' => 
      array (
      ),
      'imagens' => 
      array (
        0 => 'administrador_11_grade_situacao.png',
      ),
      'tipo' => '',
      'video' => 
      array (
        'arquivo' => 'situacao-da-grade.mp4',
        'poster' => 'situacao-da-grade.jpg',
        'legenda' => 'situacao-da-grade.vtt',
        'capitulos_vtt' => 'situacao-da-grade.capitulos.vtt',
        'duracao' => 63.1,
        'versao' => '1.42.0',
        'capitulos' => 
        array (
          0 => 
          array (
            'inicio' => 2.0,
            'titulo' => 'A grade em análise',
          ),
          1 => 
          array (
            'inicio' => 11.2,
            'titulo' => 'Finalizar',
          ),
          2 => 
          array (
            'inicio' => 26.4,
            'titulo' => 'Reabrir pede justificativa',
          ),
          3 => 
          array (
            'inicio' => 47.3,
            'titulo' => 'Desativada',
          ),
        ),
        'transcricao' => 'Ensino → Matriz: a secretaria montou a grade 2 e a mandou para "Em análise". Abra a grade.
Travada, a grade mostra uma faixa com o motivo e o botão "Ir para a situação da grade".
Em Situação da grade, escolha "Finalizada". A justificativa é opcional aqui. Salve.
Finalizada: é a matriz oficial da turma. A troca fica no Registro de alterações, com quem mudou e quando.
Para mudar a grade de novo, reabra (Aberta). Vamos tentar sem a justificativa.
Recusado: "Para reabrir uma grade «Finalizada», escreva a justificativa (ao menos 5 caracteres)."
Reaberta. O registro guarda a justificativa de quem reabriu.
Desativada: a grade não aceita aluno nem oferta nova.
O "Gerar oferta do ano" fica desabilitado: "Grade Desativada: não gera oferta". Para usar de novo, reabra.',
      ),
    ),
    'tentativas-de-acesso' => 
    array (
      'titulo' => 'Tentativas de acesso e limites',
      'resumo' => 'Como funciona a espera depois de muitas tentativas, como ajustar os limites por IP e como liberar alguém antes.',
      'perfis' => 
      array (
        0 => 'administrador',
      ),
      'ordem' => 66,
      'entrou' => '1.7.0',
      'mudou' => '1.7.1',
      'comece' => 
      array (
      ),
      'relacionados' => 
      array (
        0 => 'entrar-no-painel',
        1 => 'esqueci-a-senha',
        2 => 'conferir-um-documento',
        3 => 'regras-de-nota-e-frequencia',
      ),
      'telas' => 
      array (
        0 => 'tentativas-acesso/listar',
      ),
      'imagens' => 
      array (
        0 => 'administrador_22_tentativas.png',
      ),
      'tipo' => '',
      'video' => 
      array (
        'arquivo' => 'tentativas-de-acesso.mp4',
        'poster' => 'tentativas-de-acesso.jpg',
        'legenda' => 'tentativas-de-acesso.vtt',
        'capitulos_vtt' => 'tentativas-de-acesso.capitulos.vtt',
        'duracao' => 106.8,
        'versao' => '1.42.0',
        'capitulos' => 
        array (
          0 => 
          array (
            'inicio' => 2.0,
            'titulo' => 'Cinco senhas erradas',
          ),
          1 => 
          array (
            'inicio' => 22.2,
            'titulo' => 'Quem está em espera',
          ),
          2 => 
          array (
            'inicio' => 73.0,
            'titulo' => 'Liberar antes do fim',
          ),
          3 => 
          array (
            'inicio' => 80.9,
            'titulo' => 'Os limites por IP',
          ),
        ),
        'transcricao' => 'Alguém tenta entrar como "carlos.inventado" (um usuário inventado) e erra a senha cinco vezes.
No 5º erro: "Muitas tentativas. Aguarde…". A espera começa em 30 s e dobra a cada nova falha, até 15 min.
O administrador entra no painel…
…e abre Configurações → Tentativas de acesso. O quadro azul resume os limites em vigor.
A linha amarela está em espera agora: 5 falhas, por usuário, com a hora em que a espera acaba.
A tabela guarda só uma dica do usuário (as 2 primeiras letras e o tamanho), nunca a senha.
Para liberar: digite o usuário como a pessoa digitou na entrada e clique em Liberar (ou use o botão da linha).
"Contagem apagada": a pessoa pode tentar de novo. A linha do IP fica — na dúvida, não libere o IP.
Os limites por IP ficam em Configurações → Instituição, no quadro "Limite de tentativas por IP".
Um valor fora da faixa é recusado. Ex.: 5 falhas por hora no login — a faixa é de 10 a 1000.
Recusado, e nada foi salvo: o campo fora da faixa fica marcado. Use um número dentro dela e salve de novo.',
      ),
    ),
    'trocar-o-responsavel-financeiro' => 
    array (
      'titulo' => 'Trocar o responsável financeiro (ou o titular) durante o ano',
      'resumo' => 'A troca encerra o responsável anterior na data da troca, com o motivo, e começa o novo na mesma data. Nunca ficam dois ao mesmo tempo.',
      'perfis' => 
      array (
        0 => 'administrador',
        1 => 'secretaria',
      ),
      'ordem' => 12,
      'entrou' => '1.52.0',
      'mudou' => '',
      'comece' => 
      array (
      ),
      'relacionados' => 
      array (
        0 => 'designar-o-financeiro-e-o-titular',
      ),
      'telas' => 
      array (
      ),
      'imagens' => 
      array (
      ),
      'tipo' => 'escola',
    ),
    'usar-a-ajuda' => 
    array (
      'titulo' => 'Como usar a ajuda e as novidades',
      'resumo' => 'Onde ficam os guias, o "Comece por aqui", o ícone de ajuda de cada tela e o aviso de novidades.',
      'perfis' => 
      array (
        0 => 'administrador',
        1 => 'secretaria',
        2 => 'professor',
      ),
      'ordem' => 1,
      'entrou' => '1.6.0',
      'mudou' => '1.18.0',
      'comece' => 
      array (
        'administrador' => 1,
        'secretaria' => 1,
        'professor' => 1,
      ),
      'relacionados' => 
      array (
        0 => 'como-usar-os-videos-da-ajuda',
        1 => 'entrar-no-painel',
      ),
      'telas' => 
      array (
        0 => 'ajuda/index',
        1 => 'novidades/listar',
        2 => 'home/ajuda',
        3 => 'home/novidades',
      ),
      'imagens' => 
      array (
        0 => 'secretaria_25_central_ajuda.png',
      ),
      'tipo' => '',
      'video' => 
      array (
        'arquivo' => 'usar-a-ajuda.mp4',
        'poster' => 'usar-a-ajuda.jpg',
        'legenda' => 'usar-a-ajuda.vtt',
        'capitulos_vtt' => 'usar-a-ajuda.capitulos.vtt',
        'duracao' => 100.4,
        'versao' => '1.42.0',
        'capitulos' => 
        array (
          0 => 
          array (
            'inicio' => 2.0,
            'titulo' => 'O aviso da página inicial',
          ),
          1 => 
          array (
            'inicio' => 9.5,
            'titulo' => 'A Central de ajuda',
          ),
          2 => 
          array (
            'inicio' => 41.0,
            'titulo' => 'Achar um guia',
          ),
          3 => 
          array (
            'inicio' => 45.5,
            'titulo' => 'O ícone ? de cada tela',
          ),
          4 => 
          array (
            'inicio' => 54.7,
            'titulo' => 'Vídeos e Novidades',
          ),
          5 => 
          array (
            'inicio' => 74.3,
            'titulo' => 'Guia de outro perfil',
          ),
          6 => 
          array (
            'inicio' => 83.1,
            'titulo' => 'Marcar o aviso como lido',
          ),
        ),
        'transcricao' => 'No primeiro acesso, a página inicial mostra o aviso de boas-vindas, com o link para o "Comece por aqui".
Ele fica aqui, toda vez que a página abre, até você clicar em Entendi (ou no ×). Vamos deixá-lo para depois.
No menu da esquerda, abra o grupo Ajuda e clique em Central de ajuda.
No topo, "Comece por aqui": o caminho do seu perfil, na ordem em que as coisas acontecem no ano.
Cada guia: para que serve, antes de começar, o passo a passo e o que pode dar errado.
No topo do guia, o vídeo da tarefa, com legenda e capítulos.
No fim de cada guia, o botão "Próximo passo" leva ao seguinte do caminho.
Abaixo, todos os guias do seu perfil. Digite parte do título no campo Filtrar.
Em várias telas, o ícone ? no alto da página abre direto o guia daquela tela. Ex.: a lista de alunos.
Abriu o guia "Cadastrar aluno e enviar o link".
Ajuda → Vídeos (no menu, ou o botão Vídeos na Central) reúne os vídeos dos guias do seu perfil.
Clique num vídeo para abrir o guia com ele; a busca procura no título e no que é dito no vídeo.
Novidades (o botão aqui ou Ajuda → Novidades): o que mudou em cada versão, com o link para o guia.
Um link para um guia de outro perfil (ex.: o do e-mail SMTP, que é do administrador)…
…é recusado: "Este guia não é do seu perfil (ou não existe)." A Central mostra os que você pode abrir.
De volta à página inicial: o aviso continua lá. Clique em Entendi.
Pronto: o aviso não volta mais. Sair e entrar de novo não conta como lido; só o Entendi (ou o ×).',
      ),
    ),
    'usuarios-e-niveis' => 
    array (
      'titulo' => 'Usuários da equipe e titulação',
      'resumo' => 'Cadastrar secretaria e professores, com a titulação que sai no histórico; quem pode alterar quem.',
      'perfis' => 
      array (
        0 => 'administrador',
      ),
      'ordem' => 63,
      'entrou' => '1.0.0',
      'mudou' => '1.29.0',
      'comece' => 
      array (
        'administrador' => 7,
      ),
      'relacionados' => 
      array (
        0 => 'perfis-de-acesso',
        1 => 'entrar-no-painel',
        2 => 'completar-a-oferta',
      ),
      'telas' => 
      array (
        0 => 'usuarios/listar',
        1 => 'usuarios/cadastrar',
        2 => 'usuarios/editar',
        3 => 'usuarios/visualizar',
      ),
      'imagens' => 
      array (
        0 => 'administrador_09_usuario_cadastrar.png',
      ),
      'tipo' => '',
      'video' => 
      array (
        'arquivo' => 'usuarios-e-niveis.mp4',
        'poster' => 'usuarios-e-niveis.jpg',
        'legenda' => 'usuarios-e-niveis.vtt',
        'capitulos_vtt' => 'usuarios-e-niveis.capitulos.vtt',
        'duracao' => 83.5,
        'versao' => '1.42.0',
        'capitulos' => 
        array (
          0 => 
          array (
            'inicio' => 2.0,
            'titulo' => 'A lista da equipe',
          ),
          1 => 
          array (
            'inicio' => 10.6,
            'titulo' => 'Cadastrar uma professora',
          ),
          2 => 
          array (
            'inicio' => 32.9,
            'titulo' => 'Cadastro certo',
          ),
          3 => 
          array (
            'inicio' => 46.4,
            'titulo' => 'Pedir a atualização dos dados',
          ),
          4 => 
          array (
            'inicio' => 64.2,
            'titulo' => 'Quem altera quem',
          ),
        ),
        'transcricao' => 'Usuários, no menu: quem tem acesso ao painel (secretaria e professores).
Nome, e-mail, sexo, usuário e a senha inicial. Vamos usar, por engano, um e-mail que já existe.
O nível de acesso: a lista só oferece níveis ABAIXO do seu. Para professor, a titulação: ela sai no histórico.
Recusado: o e-mail já é de outra pessoa. Usuário e e-mail não podem se repetir.
Os dados voltam preenchidos, menos a senha: por segurança, ela nunca volta. Digite de novo.
Corrija o e-mail e salve de novo.
Cadastrada, como Professor. Entregue a senha inicial por um canal seguro e peça a troca no 1º acesso.
Para pedir que a pessoa confira o cadastro, abra Editar e escolha a situação "Atualizar Dados".
Ela entra e é levada a atualizar os dados antes de usar o sistema. Inativo e Bloqueado não entram.
Gravado. Ao salvar os dados, a situação volta sozinha para Ativo.
Você só altera quem é de nível ABAIXO do seu. Quem é do seu nível ou de um nível acima aparece como "Protegido", sem editar nem apagar.
Você não aparece na lista: o seu cadastro e a sua senha ficam no Perfil (clique no seu nome, embaixo do menu).',
      ),
    ),
    'ver-e-imprimir-o-boletim' => 
    array (
      'titulo' => 'Ver e imprimir o boletim',
      'resumo' => 'Boletim por curso no painel, para imprimir ou em PDF, com filtro de curso e ano.',
      'perfis' => 
      array (
        0 => 'secretaria',
        1 => 'administrador',
      ),
      'ordem' => 36,
      'entrou' => '1.2.0',
      'mudou' => '1.71.0',
      'comece' => 
      array (
        'secretaria' => 13,
      ),
      'relacionados' => 
      array (
        0 => 'encerrar-uma-disciplina',
        1 => 'previa-do-historico',
        2 => 'acompanhar-notas-e-resultado',
      ),
      'telas' => 
      array (
        0 => 'academicos/boletim',
      ),
      'imagens' => 
      array (
        0 => 'secretaria_17_boletim.png',
      ),
      'tipo' => 'escola,superior',
      'video' => 
      array (
        'arquivo' => 'ver-e-imprimir-o-boletim.mp4',
        'poster' => 'ver-e-imprimir-o-boletim.jpg',
        'legenda' => 'ver-e-imprimir-o-boletim.vtt',
        'capitulos_vtt' => 'ver-e-imprimir-o-boletim.capitulos.vtt',
        'duracao' => 54.9,
        'versao' => '1.42.0',
        'capitulos' => 
        array (
          0 => 
          array (
            'inicio' => 2.0,
            'titulo' => 'Abrir o boletim',
          ),
          1 => 
          array (
            'inicio' => 6.2,
            'titulo' => 'Ler o boletim',
          ),
          2 => 
          array (
            'inicio' => 21.1,
            'titulo' => 'Filtros, Imprimir e PDF',
          ),
        ),
        'transcricao' => 'Educacional → Alunos. Na linha do aluno, o botão "Boletim" (também está em Notas e resultado da disciplina).
Uma faixa com o aluno e o curso; depois, cada disciplina: média, faltas e compensadas, frequência e resultado.
A etiqueta "parcial": a disciplina está aberta e a média ainda pode mudar até o encerramento.
Em vermelho, o que está abaixo do mínimo. Abaixo da tabela, "Como ler": a média e a frequência mínimas.
No topo, Curso e Ano filtram o boletim. Vamos escolher um ano.
O boletim de 2026. Sem nada com o filtro, a tela avisa: volte o filtro para "Todos".
"Imprimir" imprime a página sem o menu. "PDF" gera o mesmo boletim, com o mesmo filtro:
Aluno em dois cursos tem dois boletins, um abaixo do outro, cada um com a sua faixa.',
      ),
    ),
    'vincular-e-enturmar' => 
    array (
      'titulo' => 'Vincular e enturmar',
      'resumo' => 'Matrícula no curso (grade, polo, modalidade) e a enturmação sugerida pela grade.',
      'perfis' => 
      array (
        0 => 'administrador',
        1 => 'secretaria',
      ),
      'ordem' => 21,
      'entrou' => '1.1.0',
      'mudou' => '1.11.0',
      'comece' => 
      array (
        'secretaria' => 9,
      ),
      'relacionados' => 
      array (
        0 => 'cadastrar-aluno-e-enviar-link',
        1 => 'gerar-a-oferta-do-ano',
        2 => 'concluir-o-curso-e-tcc',
      ),
      'telas' => 
      array (
        0 => 'academicos/visualizar',
        1 => 'portal-alunos/visualizar',
      ),
      'imagens' => 
      array (
        0 => 'secretaria_12_vinculo.png',
        1 => 'secretaria_13_enturmacao.png',
      ),
      'tipo' => 'escola,superior',
      'video' => 
      array (
        'arquivo' => 'vincular-e-enturmar.mp4',
        'poster' => 'vincular-e-enturmar.jpg',
        'legenda' => 'vincular-e-enturmar.vtt',
        'capitulos_vtt' => 'vincular-e-enturmar.capitulos.vtt',
        'duracao' => 62.7,
        'versao' => '1.42.0',
        'capitulos' => 
        array (
          0 => 
          array (
            'inicio' => 2.0,
            'titulo' => 'Vincular ao curso',
          ),
          1 => 
          array (
            'inicio' => 30.2,
            'titulo' => 'Previsão de conclusão',
          ),
          2 => 
          array (
            'inicio' => 39.4,
            'titulo' => 'Enturmação',
          ),
        ),
        'transcricao' => 'Ficha da aluna, aba Matrículas: ela ainda não tem vínculo. Use "Vincular a um curso".
Ano, curso — grade, forma e data de ingresso, polo e modalidade (vazio = os do curso). Sem a data, veja:
Recusado: "Informe a data de ingresso." Preencha e vincule de novo.
Vinculada. A modalidade da matrícula escolhe o ato legal do histórico.
Na edição da matrícula, "Usar a sugestão" preenche a previsão com a maior data de fim das ofertas da grade.
Ela sai nos documentos como "novembro de 2026".
Clique no botão Enturmação do ano 2026.
As ofertas abertas da grade vêm sugeridas e marcadas. As já encerradas aparecem bloqueadas: o administrador reabre.
Desmarque o que ela cursará depois (aqui, o TCC) e clique em "Confirmar enturmação". Nada grava antes.
Confirmada: agora ela aparece para o professor de Liderança de Equipes. Num ano seguinte, use "Registrar no ano".',
      ),
    ),
  ),
);
