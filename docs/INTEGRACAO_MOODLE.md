# A integração com o Moodle

Onda 3, Etapa 8. Este documento começa pelo **levantamento** (o que a plataforma já tinha sobre o Moodle e por onde o
envio passa) e segue com o **desenho** do que foi construído.

## 1. O levantamento (antes de escrever código)

### O que já existia

- **O "Id no Moodle" da disciplina ofertada:** `rm_professores_materias.idMoodle` (migration `0001`), digitado à mão no
  formulário da oferta (`Views/disciplinas/camposMatriz.php`), validado em `AdmsOfertaMatriz::idMoodle()` (inteiro
  positivo ou vazio), mostrado na tela da disciplina e lido em `AdmsOfertaInfo`. A geração da oferta em lote o grava
  vazio; a demonstração o preenche com um número fictício.
- **O aluno não tinha campo do Moodle.** (`col_usuarios.userid` é uma coluna herdada que nenhum código lê.)
- **O portal dentro do Moodle, não o contrário:** o portal do aluno pode ser aberto num iframe do Moodle
  (`ORIGENS_PERMITIDAS_IFRAME`, `COOKIE_PARTICIONADO` na Config do portal; o guia "O portal dentro do Moodle"). O portal
  não tinha nenhum link para o Moodle, nem endereço do Moodle guardado.
- **Notas e faltas vindas do Moodle** entram só por planilha, casadas pelo RA no "número de identificação" do Moodle.
- **O grupo Integrações** (menu 11, migration `0010`): as chaves da API, a documentação e as chamadas; só o
  Administrador; as ações 80 (consultar) e 81 (gerenciar as chaves).
- **Nenhum segredo cifrado no banco** até aqui: a senha do e-mail fica em texto (a tela só não a mostra) e as chaves da
  API guardam só o hash. A Config não tinha constante de chave de cifra.
- **Clientes HTTP:** o da versão nova (`core/VersaoNova.php`, o mais próximo do que se quer: tempo curto, certificado
  conferido, sem redirecionamento); os das ferramentas de linha de comando. Nenhum serve como está.
- **A janela de progresso** (`assets/js/vcostaedu-progresso.js` e `include_tabler/_progresso.php`) e o padrão do estado
  em `adms_instalacao_estado` com `GET_LOCK` (a demonstração em passos) — reaproveitados no "Enviar tudo o que falta".

### Os pontos onde o acadêmico é gravado (por onde o envio passa)

| O quê | A função que grava | Quem chama | Transação |
|---|---|---|---|
| aluno, criar | `AdmsCadastrarUsuarioAcademico::cadUserAcademico()` | o painel, a importação por planilha, a API | sim (`Transacao::executar`; dentro da importação, a transação de fora) |
| aluno, editar | `AdmsEditarUsuarioAcademico::editUsuarios()` | o painel, a API | sim |
| aluno, o próprio perfil no portal (nome, e-mail) | `portal/.../AdmsEditarPerfil` | o portal | não |
| matrícula, criar | `AdmsVincularAluno::vincular()` | o painel, a importação, a API | sim |
| matrícula, a situação (trancar 3, cancelar 4, reativar 1, concluir 2) | `AdmsEditarVinculo::editar()` | o painel (o formulário do vínculo), a API (`matriculasTrancar`, `Reativar`, `Cancelar`) | sim |
| enturmação, pôr e tirar | `AdmsConfirmarEnturmacao::confirmar()` (aplica a lista inteira) | o painel, a API | sim |
| professor da oferta | `AdmsAdicionarProfessor::cadProfessor()` (desliga todos e liga os marcados) e, na criação da oferta, `AdmsCadastrarDisciplina` | o painel | não |
| oferta, criar / gerar em lote / editar | `AdmsCadastrarDisciplina`, `AdmsGerarOferta`, `AdmsEditarDisciplinas` | o painel | não |

A importação não muda situação nem enturma; a API não grava professor nem oferta. Como a importação grava tudo numa
transação de fora, **o envio é anotado depois que a gravação terminou** (nunca dentro da transação, e nunca derrubando a
gravação).

### Como cada coisa se identifica

- **Aluno:** `col_usuarios` — RA (`rm`, único), `email` (pode faltar), `nome` (um campo só: o nome e o sobrenome do
  Moodle saem da divisão no último espaço), situação `col_sits_usuario_id` (1 ativo, 2 inativo, 5 apagado).
- **Professor:** `adms_usuarios` — `nome`, `email` (obrigatório).
- **Oferta:** `rm_professores_materias.id` (o `rm_prof_mat_id` das outras tabelas); o nome completo sai da matéria
  (`materia`), do curso, da turma (`codtur`) e do ano.

## 2. O desenho

### As peças

| Peça | Onde | O que faz |
|---|---|---|
| O cliente | `core/Moodle.php` (`VcMoodle`) | REST com curl: 3 s para conectar, 8 s para responder, o certificado conferido, sem seguir redirecionamento, até 2 MB; os códigos de erro do contrato e os da conexão (`sem_resposta`, `tempo`, `certificado`, `redirecionamento`, `http_<n>`, `resposta_invalida`); a cifra da chave (AES-256-GCM); o glossário |
| A conexão e a fila | `painel/app/adms/Models/moodle/AdmsMoodle.php` | a conexão (a chave decifrada só por dentro), `anotar()` (os pontos de gravação), `enfileirar()`, `enviar()`, a saúde, o estado da oferta |
| O envio de cada item | `AdmsMoodleEnvio.php` | o aluno, o professor, a oferta, a enturmação, a saída, a situação da matrícula, o professor da disciplina |
| "Enviar tudo o que falta" | `AdmsMoodleLote.php` | em passos, com a janela de progresso, o estado em `adms_instalacao_estado` ('moodle.lote') e a trava |
| As telas | `Controllers/Moodle.php` (Conectar ao Moodle), `Controllers/MoodleEnvios.php` (Envios ao Moodle, Tentar de novo, Enviar tudo, Ligar a disciplina) | |
| O portal | `portal/app/adms/Controllers/Moodle.php`, `Models/moodle/AdmsMoodlePortal.php`, `Views/home/_moodle.php` | o cartão na Página Inicial, o pedido do link e o redirecionamento |
| A linha de comando | `database/ferramentas/moodle_enviar.php` | o mesmo lote, para agendar (opcional) |
| O banco | migrations `0020` e `0023` | `adms_moodle_conexao`, `adms_moodle_vinculos`, `adms_moodle_envios` (com `contas`, 1.36.1), `adms_moodle_registro`; as ações 96 e 97; as páginas 497 a 504 e 506 (escolher a conta) e as do portal 26 e 27; os parâmetros |

### As regras

- **Nunca trava o acadêmico.** Os pontos de gravação chamam `AdmsMoodle::anotar()` depois de gravar; ela só anota a
  intenção. No FIM da requisição (depois que a transação, inclusive a de fora da importação por planilha, terminou),
  o que existe entra na fila e, com o envio automático, vai na hora, dentro de um orçamento de 6 segundos por
  requisição. Qualquer erro vai para o registro do PHP; a gravação acadêmica nunca é desfeita por causa do Moodle.
- **A chave** fica cifrada no banco com um segredo guardado num arquivo da pasta dos arquivos privados que a Config
  indica (`PASTA_STORAGE/segredos/cifra.key`, criado uma vez, negado à web); o painel e o portal apontam para a mesma
  pasta. A tela nunca devolve a chave (só os 4 últimos caracteres); ela não vai para registro nenhum.
- **O registro** (`adms_moodle_registro`) guarda a função, a quem se refere ("aluno 12", "oferta 5"), o resultado, o
  código e o tempo — nunca a chave, a senha, o link de entrada nem o dado pessoal.
- **Os papéis do Moodle** (estudante 5, professor com edição 3, sem edição 4) são parâmetros avançados: o serviço não
  tem função para listar os papéis.
- **O prefixo** (`moodle.prefixo`, vazio por padrão) vai no começo do idnumber e do nome de usuário criados no Moodle.
  As provas usam "prova-".
- **O link de entrada** é pedido pelo servidor e devolvido como redirecionamento (303, sem cache, sem Referer); o
  portal não o mostra e não o guarda. Não é pedido para aluno inativo, com matrícula trancada ou cancelada, nem para
  disciplina não enviada. Um limite por aluno por hora (`moodle.entradas_por_hora`).
- **A demonstração** não põe mais "Id no Moodle" fictício; a migration tira os que uma demonstração antiga pôs.
- **A procura antes de criar** (1.36.1): o idnumber, o nome de usuário e o e-mail. O e-mail vai pela função do plugin
  `local_vcostaedu_find_users_by_email` (a 17ª do serviço: de 1 a 100 e-mails por chamada; para cada um, os ids das
  contas que o têm, as suspensas também). No envio em lote, os e-mails dos alunos ainda sem conta ligada vão em grupos
  de até 100 por chamada (nunca um por chamada). **Um id:** liga. **Mais de um:** não liga sozinho — o envio fica
  "falhou" com `email_varias_contas` e os ids na coluna `contas` de `adms_moodle_envios`; o operador escolhe a conta em
  Envios ao Moodle (a página 506, a mesma permissão do "Tentar de novo"). **A conta suspensa no Moodle:** liga e o envio
  fica "enviado com aviso" (`conta_suspensa`): a pessoa não entra até a reativação lá. O e-mail malformado não vai ao
  Moodle (o plugin o recusaria com `invalidparameter`).
- **O e-mail do aluno no Moodle** (1.58.0, D-68): o parâmetro `moodle.email_modo` — `escola` (sempre a matrícula no
  domínio `moodle.email_dominio`; o padrão na escola), `aluno_ou_escola` (o do cadastro; sem ele, ou repetido em outro
  aluno, o da escola) ou `aluno` (só o do cadastro; sem ele ou repetido, `sem_email` ou `email_repetido`; o padrão no
  ensino superior). Vazio = o padrão do tipo. Sem domínio válido, os modos com o da escola dão `sem_dominio`. O endereço
  da escola é montado em `AdmsMoodle::emailParaMoodle` na hora do envio e não é gravado: nem no cadastro, nem na fila.
  Em `adms_moodle_vinculos`, `criado` diz se a conta foi criada pelo sistema (só nela o envio do aluno manda o e-mail;
  a conta que já existia fica com o dela) e `email_proprio` se ela foi com o e-mail do cadastro (quando um irmão chega
  com o mesmo e-mail, o primeiro, que foi com o da família, volta à fila e passa ao da escola; o que já foi com o da
  escola não volta). Trocar o modo ou o domínio em Conectar ao Moodle põe na fila os alunos de conta criada. Os envios
  de e-mail do sistema (o link de acesso, a recuperação da senha, o convite) usam sempre o e-mail do cadastro.
- **O nome da disciplina** (1.36.1): editar a oferta (a turma, a disciplina da matriz) põe a oferta na fila, se ela já
  está no Moodle; o envio confere que a disciplina de lá é a que o VCOSTAEDU criou (o idnumber com o prefixo e
  `oferta-<id>`) e atualiza o nome completo e o curto (`core_course_update_courses`). A disciplina só ligada (a escolha
  "vincular", de outro dono) não é renomeada.

### O que sobrou anda sozinho (Onda 8, 1.60.0, D-70)

- **A retomada:** no fim de toda requisição do painel e da interface (`AdmsMoodle::retomar`, registrada em `painel/index.php` e
  `api/index.php` depois do envio da própria requisição), se a requisição não mandou nada ao Moodle, ela manda um pouco da
  fila: os pendentes com mais de 8 segundos e as falhas passageiras (`sem_resposta`, `tempo`, `erro_interno`,
  `resposta_invalida`) cuja espera já passou (30 s, 1 min, 2 min… até 1 hora). No máximo 1,5 s, e no máximo uma vez a cada
  6 s em todas as requisições juntas (a marca é um arquivo temporário: a requisição comum não abre conexão por isso), uma
  de cada vez (a trava do "Enviar tudo o que falta"), só com o envio automático. A resposta da interface leva o tamanho
  (`Content-Length`): quem chama não espera a retomada. O que só uma pessoa resolve (`sem_email`, `email_repetido`,
  `sem_dominio`, várias contas, a chave recusada) não é tentado de novo. O envio na hora continua como era.
- **Um item, uma requisição:** `enviar()` pega uma trava do item (`GET_LOCK`); se outra requisição está mandando o mesmo
  item, ela o deixa (devolve `ocupado`). Assim a retomada e o envio na hora nunca mandam o mesmo item juntos.
- **As inscrições de volta:** quando o usuário do aluno chega ao Moodle (o e-mail corrigido, por exemplo), as inscrições
  dele que tinham falhado pelo mesmo motivo (`sem_email`, `email_repetido`, `sem_dominio`, `vcostaedu_usernotfound`)
  voltam a "pendente" sozinhas e, na mesma requisição, vão logo depois, se o orçamento deixar.
- **O agendamento (opcional):** para a fila andar também com o sistema parado, agende `php database/ferramentas/moodle_enviar.php`
  na pasta da instalação (o cron, no Linux: `*/15 * * * * cd /caminho/da/instalacao && php database/ferramentas/moodle_enviar.php`;
  o Agendador de Tarefas, no Windows). É o mesmo lote da tela; sai com 1 se o Moodle não responder.
- **A inscrição numa chamada só** (`POST /inscricoes`): grava o cadastro, a matrícula e a turma (ou as ofertas) numa
  transação e, depois, chama `aoTerminar()` dentro da própria chamada, para a resposta trazer o estado no Moodle; o que não
  coube termina pela retomada.

### O que fica para depois

- A leitura das notas do Moodle (`gradereport_user_get_grade_items` já está no serviço).
- Descobrir os papéis pelo próprio Moodle: pede uma função a mais no plugin (DUVIDAS).
- Apagar no Moodle o que for apagado no VCOSTAEDU: o serviço não tem a função de apagar.
