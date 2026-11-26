<?php
/* GERADO por database/ferramentas/guias/gerar_guias.php a partir de docs/guia/tarefas/ — não editar. */
if (!defined('VCOSTAEDU')) { header('Location: /'); die('Erro: Página não encontrada!'); }
return array (
  'versao' => '1.102.1',
  'gerado' => '26/11/2026',
  'guias' => 
  array (
    'como-usar-os-videos-da-ajuda' => 
    array (
      'titulo' => 'Como usar os vídeos da Ajuda',
      'resumo' => 'O vídeo no topo de cada guia, a legenda, os capítulos, a transcrição e a página Vídeos.',
      'perfis' => 
      array (
        0 => 'aluno',
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
    'conferir-um-documento' => 
    array (
      'titulo' => 'Conferir um documento',
      'resumo' => 'A verificação pública pelo código: resultado, dados mínimos, o hash, o comprovante e o PDF recebido.',
      'perfis' => 
      array (
        0 => 'aluno',
      ),
      'ordem' => 54,
      'entrou' => '1.5.0',
      'mudou' => '1.8.0',
      'comece' => 
      array (
      ),
      'relacionados' => 
      array (
        0 => 'ler-o-boletim',
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
    'emitir-atestado-de-matricula' => 
    array (
      'titulo' => 'Emitir atestado de matrícula',
      'resumo' => 'O atestado de quem está cursando: pela ficha ou pela lista de alunos (secretaria) e pelo portal (o próprio aluno), com código de verificação.',
      'perfis' => 
      array (
        0 => 'aluno',
      ),
      'ordem' => 52,
      'entrou' => '1.12.0',
      'mudou' => '1.74.0',
      'comece' => 
      array (
      ),
      'relacionados' => 
      array (
        0 => 'conferir-um-documento',
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
    'entrar-no-moodle-pelo-portal' => 
    array (
      'titulo' => 'Entrar no Moodle pelo portal',
      'resumo' => 'O botão "Entrar no Moodle" e o "Abrir no Moodle" de cada disciplina, sem senha.',
      'perfis' => 
      array (
        0 => 'aluno',
      ),
      'ordem' => 72,
      'entrou' => '1.34.0',
      'mudou' => '',
      'comece' => 
      array (
      ),
      'relacionados' => 
      array (
        0 => 'entrar-no-portal',
      ),
      'telas' => 
      array (
      ),
      'imagens' => 
      array (
      ),
      'tipo' => '',
    ),
    'entrar-no-portal' => 
    array (
      'titulo' => 'Entrar no portal',
      'resumo' => 'O login do aluno (na escola, o número de matrícula; no ensino superior e no curso livre, o e-mail do cadastro ou o número de matrícula) e a página inicial.',
      'perfis' => 
      array (
        0 => 'aluno',
      ),
      'ordem' => 71,
      'entrou' => '1.0.0',
      'mudou' => '1.100.0',
      'comece' => 
      array (
        'aluno' => 3,
      ),
      'relacionados' => 
      array (
        0 => 'primeiro-acesso-do-aluno',
        1 => 'esqueci-a-senha',
        2 => 'ler-o-boletim',
      ),
      'telas' => 
      array (
        0 => 'home/index',
      ),
      'imagens' => 
      array (
        0 => 'aluno_01_login.png',
        1 => 'aluno_04_inicio.png',
      ),
      'tipo' => '',
      'video' => 
      array (
        'arquivo' => 'entrar-no-portal.mp4',
        'poster' => 'entrar-no-portal.jpg',
        'legenda' => 'entrar-no-portal.vtt',
        'capitulos_vtt' => 'entrar-no-portal.capitulos.vtt',
        'duracao' => 79.9,
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
            'inicio' => 11.1,
            'titulo' => 'A espera depois de 5 erros',
          ),
          2 => 
          array (
            'inicio' => 58.9,
            'titulo' => 'Entrar',
          ),
        ),
        'transcricao' => 'Na tela de entrada, o usuário é o RA (ou CPF ou e-mail, se a instituição escolheu): a própria tela diz qual.
Senha errada: "Usuário ou senha incorretos." A mensagem é a mesma se o usuário não existir.
Errando 5 vezes seguidas…
…a entrada pede uma espera: "Muitas tentativas. Aguarde…". Começa em 30 s e dobra a cada novo erro.
Durante a espera, nem a senha certa entra:
Espere o tempo indicado (aqui, 30 segundos). Esqueceu a senha? Use "Esqueci a senha", abaixo do botão.
Depois da espera, a senha certa entra e zera a contagem de erros.
Entrou. O menu traz o grupo Acadêmico (Horário, Boletim, Notas das Atividades e outros)…
…e o grupo Ajuda, com a Central de ajuda e as Novidades.
Portal dentro do Moodle com o botão "Abrir o portal em nova aba"? O navegador bloqueou; use o botão.',
      ),
    ),
    'escolher-uma-senha' => 
    array (
      'titulo' => 'Escolher uma senha',
      'resumo' => 'Como o sistema avalia a senha e o que faz uma senha ser recusada.',
      'perfis' => 
      array (
        0 => 'aluno',
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
        0 => 'aluno',
      ),
      'ordem' => 72,
      'entrou' => '1.0.0',
      'mudou' => '1.7.0',
      'comece' => 
      array (
        'aluno' => 6,
      ),
      'relacionados' => 
      array (
        0 => 'primeiro-acesso-do-aluno',
        1 => 'entrar-no-portal',
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
    'ler-o-boletim' => 
    array (
      'titulo' => 'Ler o boletim',
      'resumo' => 'Média (parcial ou final), faltas, frequência e resultado de cada disciplina do curso.',
      'perfis' => 
      array (
        0 => 'aluno',
      ),
      'ordem' => 73,
      'entrou' => '1.2.0',
      'mudou' => '1.13.0',
      'comece' => 
      array (
        'aluno' => 4,
      ),
      'relacionados' => 
      array (
        0 => 'notas-das-atividades',
        1 => 'entrar-no-portal',
        2 => 'conferir-um-documento',
      ),
      'telas' => 
      array (
        0 => 'home/boletim-aluno',
      ),
      'imagens' => 
      array (
        0 => 'aluno_05_boletim.png',
      ),
      'tipo' => 'escola,superior',
      'video' => 
      array (
        'arquivo' => 'ler-o-boletim.mp4',
        'poster' => 'ler-o-boletim.jpg',
        'legenda' => 'ler-o-boletim.vtt',
        'capitulos_vtt' => 'ler-o-boletim.capitulos.vtt',
        'duracao' => 66.2,
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
            'inicio' => 9.1,
            'titulo' => 'Como ler',
          ),
          2 => 
          array (
            'inicio' => 39.4,
            'titulo' => 'Curso, ano e imprimir',
          ),
          3 => 
          array (
            'inicio' => 50.6,
            'titulo' => 'Como foi calculado',
          ),
        ),
        'transcricao' => 'No menu, Acadêmico → Boletim.
No topo, uma faixa com o seu nome, RA, curso e situação; abaixo, as disciplinas do curso. No menu, "Boletim" fica marcado.
Média com a etiqueta "parcial": a disciplina está aberta; é a média só das atividades com nota e pode mudar.
Média sem etiqueta: a disciplina foi encerrada; é a média final.
Faltas e Comp. (faltas compensadas, como atestados): as compensadas não contam contra você.
Frequência = 1 − (faltas − compensadas) ÷ total de encontros. O que está em vermelho está abaixo do mínimo.
Resultado: Em curso, Aprovado ou Reprovado (por nota, por frequência ou pelos dois).
Para ver só um ano ou um curso, use os seletores Curso e Ano, no topo.
"Imprimir" tira o menu e imprime só o boletim. Veja como fica a página impressa:
"Como foi calculado", embaixo do curso: clique para abrir a conta de cada disciplina.
As notas de cada componente, os pesos, o arredondamento e a frequência, conforme a regra de média.
Nota ou falta errada? Fale com o professor ou a secretaria.',
      ),
    ),
    'meu-progresso' => 
    array (
      'titulo' => 'Meu progresso no curso',
      'resumo' => 'A sua grade inteira — o que já foi aprovado, o que está em curso, a carga horária cumprida e a média final.',
      'perfis' => 
      array (
        0 => 'aluno',
      ),
      'ordem' => 8,
      'entrou' => '1.17.1',
      'mudou' => '',
      'comece' => 
      array (
      ),
      'relacionados' => 
      array (
        0 => 'ler-o-boletim',
      ),
      'telas' => 
      array (
        0 => 'progresso/index',
      ),
      'imagens' => 
      array (
      ),
      'tipo' => 'escola,superior',
      'video' => 
      array (
        'arquivo' => 'meu-progresso.mp4',
        'poster' => 'meu-progresso.jpg',
        'legenda' => 'meu-progresso.vtt',
        'capitulos_vtt' => 'meu-progresso.capitulos.vtt',
        'duracao' => 41.6,
        'versao' => '1.42.0',
        'capitulos' => 
        array (
          0 => 
          array (
            'inicio' => 2.0,
            'titulo' => 'Abrir o progresso',
          ),
          1 => 
          array (
            'inicio' => 7.8,
            'titulo' => 'Os números do curso',
          ),
          2 => 
          array (
            'inicio' => 22.2,
            'titulo' => 'A grade, disciplina por disciplina',
          ),
        ),
        'transcricao' => 'No menu, Acadêmico → Meu progresso: quanto falta para concluir o curso, disciplina por disciplina.
Você vê só as suas matrículas. Com mais de um curso, um seletor no topo escolhe o curso.
A carga horária cumprida, sobre a carga horária total da grade do curso.
Quantas disciplinas você já aprovou, de quantas tem a grade.
A média final: só das disciplinas encerradas.
Na tabela, cada disciplina da grade: Aprovado, Reprovado, Em curso ou Não cursada, com a média e a frequência.
Em curso: a média é parcial (só das atividades com nota) e pode mudar até o encerramento.
"Não cursada", mas você está na turma? Fale com a secretaria: pode faltar a enturmação.',
      ),
    ),
    'notas-das-atividades' => 
    array (
      'titulo' => 'Ver as notas das atividades',
      'resumo' => 'A nota de cada atividade, disciplina por disciplina.',
      'perfis' => 
      array (
        0 => 'aluno',
      ),
      'ordem' => 74,
      'entrou' => '1.0.0',
      'mudou' => '',
      'comece' => 
      array (
        'aluno' => 5,
      ),
      'relacionados' => 
      array (
        0 => 'ler-o-boletim',
      ),
      'telas' => 
      array (
        0 => 'home/notas-atividades',
      ),
      'imagens' => 
      array (
        0 => 'aluno_06_notas_atividades.png',
      ),
      'tipo' => 'escola,superior',
      'video' => 
      array (
        'arquivo' => 'notas-das-atividades.mp4',
        'poster' => 'notas-das-atividades.jpg',
        'legenda' => 'notas-das-atividades.vtt',
        'capitulos_vtt' => 'notas-das-atividades.capitulos.vtt',
        'duracao' => 37.2,
        'versao' => '1.42.0',
        'capitulos' => 
        array (
          0 => 
          array (
            'inicio' => 2.0,
            'titulo' => 'Abrir as notas',
          ),
          1 => 
          array (
            'inicio' => 9.9,
            'titulo' => 'Escolher a disciplina',
          ),
        ),
        'transcricao' => 'No menu, Acadêmico → Notas das Atividades: cada nota como o professor lançou, antes de virar média.
Aqui, todas as disciplinas do ano, uma abaixo da outra, com as atividades e as notas. No menu, o item fica marcado.
Para ver uma só, escolha a disciplina no filtro e clique em Pesquisar.
Aparece cada atividade da disciplina, com a sua nota.
"-" é atividade sem nota: o professor ainda não lançou. No encerramento, ela conta 0.
Nota errada? Fale com o professor. A média e o resultado ficam no Boletim.',
      ),
    ),
    'primeiro-acesso-do-aluno' => 
    array (
      'titulo' => 'Primeiro acesso do aluno',
      'resumo' => 'Definir a própria senha pelo link recebido da secretaria.',
      'perfis' => 
      array (
        0 => 'aluno',
      ),
      'ordem' => 70,
      'entrou' => '1.0.0',
      'mudou' => '1.19.0',
      'comece' => 
      array (
        'aluno' => 2,
      ),
      'relacionados' => 
      array (
        0 => 'entrar-no-portal',
        1 => 'esqueci-a-senha',
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
    'usar-a-ajuda' => 
    array (
      'titulo' => 'Como usar a ajuda e as novidades',
      'resumo' => 'Onde ficam os guias, o "Comece por aqui", o ícone de ajuda de cada tela e o aviso de novidades.',
      'perfis' => 
      array (
        0 => 'aluno',
      ),
      'ordem' => 1,
      'entrou' => '1.6.0',
      'mudou' => '1.18.0',
      'comece' => 
      array (
        'aluno' => 1,
      ),
      'relacionados' => 
      array (
        0 => 'como-usar-os-videos-da-ajuda',
        1 => 'entrar-no-portal',
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
  ),
);
