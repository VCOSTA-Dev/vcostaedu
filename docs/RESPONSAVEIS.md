# Os responsáveis e o portal da família

Só na instalação do tipo escola. No ensino superior, nada aparece e nada muda (as tabelas ficam vazias; as telas, o
menu e a rota da interface não existem).

## O desenho

| tabela | o quê |
|---|---|
| `esc_responsaveis` | a pessoa responsável: nome, e-mail, celular, CPF (opcional), o login do portal (o CPF ou o e-mail, escolhido no convite), a senha do portal (o resumo), o bloqueio |
| `esc_responsavel_alunos` | o parentesco da pessoa com cada aluno |
| `esc_responsavel_designacoes` | o papel da pessoa num aluno, num ano letivo: financeiro, titular ou adicional; início, fim, motivo, referência, origem, quem |
| `esc_responsavel_contatos` | os responsáveis para contato (na ficha do aluno; não entram no portal) |
| `esc_responsavel_auditoria` | quem designou, liberou ou encerrou, quando, o antes, o depois e o motivo |

## As regras

- **O responsável financeiro:** exatamente um por aluno e por ano, sem parâmetro. O banco recusa o segundo em vigor
  (índice único sobre a coluna calculada `vigente_unica`), também pelo INSERT direto.
- **O acadêmico titular:** um por aluno e por ano, o do contrato; pode ser a mesma pessoa do financeiro.
- **O acesso acadêmico adicional:** quantos a escola permitir (Regras da escola: permitido ou não; o limite por aluno,
  0 = sem limite). Cada liberação tem o motivo (obrigatório), a referência do termo, quem liberou e quando. Só pela
  tela. Nunca dá alcance ao financeiro.
- **A troca:** encerra a designação anterior na data da troca, com o motivo, e abre a nova na mesma data. Nunca há dois
  em vigor. O histórico de quem foi fica.
- **Os anos:** os papéis de um ano não valem para outro. "Copiar do ano anterior" traz o financeiro e o titular para
  conferir antes de gravar; os adicionais não vêm.
- **A mesma pessoa:** o sistema procura pelo e-mail e pelo CPF antes de criar; a que já existe é usada.
- **As três portas:** a tela, a planilha própria (tudo ou nada) e a interface de integração (`/alunos/{ra}/responsaveis`),
  todas pela mesma função.

## O convite e o primeiro acesso (Etapa 3; D-63)

| tabela | o quê |
|---|---|
| `esc_responsavel_convites` | o convite (e o link de senha nova): o resumo do código, o tipo, a validade, o envio, o uso, o cancelamento |

- **O convite:** gerado na ficha do responsável ou na lista; um link de uso único com prazo (Regras da escola, padrão de
  7 dias). Pede e-mail ou CPF (um deles será o login) e um papel em algum aluno. Gerar de novo invalida o anterior.
- **O código:** vai no fragmento do link (`…/convite/abrir#c=…`); no banco, só o resumo (sha256). A tela mostra o link
  uma vez, para quem gerou.
- **A entrega:** por e-mail (com o envio configurado), copiando o link, ou pelo WhatsApp de quem está na secretaria
  (a mensagem das Regras da escola, com {nome}, {alunos}, {validade} e {link}).
- **O primeiro acesso:** a pessoa confere o nome, vê o primeiro nome dos alunos, cria a senha e entra. O link que não
  vale mostra sempre a mesma página.
- **A entrada:** na mesma tela do portal, pelo login da conta e a senha (desde a Onda 8, a seção "O login do portal").
- **Cancelar e bloquear:** cancelar faz o link deixar de valer; bloquear (com o motivo) impede a entrada e derruba a
  sessão aberta na próxima página; desbloquear devolve o acesso com a mesma senha.
- **Esqueci a senha:** para quem tem e-mail e já criou a senha; um link de 2 horas, pelo mesmo registro.
- **Os convites de uma turma:** em passos (a janela de progresso); quem não recebeu por e-mail fica na lista do envio
  manual, com o link (só naquela tela).
- **O registro:** cada convite gerado, enviado, usado e cancelado, cada bloqueio e cada senha trocada, na auditoria dos
  responsáveis. O limite de tentativas vale na entrada e no uso do convite.

## O portal do responsável (Etapa 4; D-64)

- **Os filhos:** depois de entrar, a lista dos alunos de que o responsável tem acesso acadêmico em algum ano; com um
  filho só, ele entra direto nele.
- **O cabeçalho:** em toda tela, o nome do filho, a turma e o ano; a troca de filho e a de ano (só os anos em que ele
  tem papel naquele aluno).
- **As telas, só de leitura:** o boletim (a mesma regra de liberação do aluno), as notas das atividades, a frequência,
  o horário e a turma (a série, o turno, a sala, os componentes e os professores).
- **O atestado de matrícula:** o responsável emite (as mesmas condições do aluno: a matrícula em curso e os dados do
  documento completos) e abre o PDF; a emissão fica na auditoria dos responsáveis.
- **A barreira:** toda tela leva o aluno e o ano no endereço e pergunta ao ponto único a cada requisição; a recusa é a
  mesma página (403) para o aluno de outra família, o ano sem papel e o aluno que não existe.
- **O que não aparece:** o financeiro, o ambiente de estudo, a senha do aluno e os outros responsáveis.

## A barreira de acesso (Etapa 5; D-65)

| papel naquele aluno e ano | o acadêmico | o financeiro |
|---|---|---|
| o responsável financeiro | sim | sim |
| o acadêmico titular | sim | não |
| o acesso acadêmico adicional | sim, com a regra da escola ligada | não, nunca |
| nenhum papel em vigor (ou o responsável bloqueado) | não | não |

- **O ponto único:** `VcAcessoFamilia::pode(responsável, aluno, ano, tipo, data)`, em `core/AcessoFamilia.php`. Lê o
  banco a cada pergunta; nada vem da sessão. "Pode ver o financeiro?" é `podeVerFinanceiro`, pronta para o módulo
  financeiro.
- **A cada requisição:** toda tela do portal do responsável começa por `Familia::exigir` (o aluno e o ano do
  endereço). O papel retirado, o financeiro trocado, a liberação encerrada e a regra desligada valem na tela seguinte.
- **A troca de financeiro:** fecha a designação anterior na data da troca e abre a nova na mesma data: nunca há dois;
  o anterior tem o alcance até a véspera, e o histórico dele fica (DUVIDAS 307: quem verá as cobranças anteriores à
  troca é decisão do módulo financeiro).
- **A conferência:** em Manutenção → Verificações de segurança (só na escola), o item "Portal do responsável"
  (`core/BarreiraFamilia.php`).
- **As provas:** `tests/tabler/onda7/etapa5.js` (cada caso com a sabotagem) e, para os dois financeiros pela tela,
  pela planilha, pela interface e no banco, `tests/tabler/onda7/etapa2.js`.

## A demonstração, os primeiros passos, a ajuda e os relatórios (Etapa 6; D-66)

- **A demonstração do colégio:** os responsáveis fictícios (`VcDemoEscola::RESPONSAVEIS` e `PAPEIS`, em
  `database/ferramentas/demonstracao_escola.php`), montados pelas telas; os que entram criam a senha pelo convite. A
  Página Inicial lista os usuários de responsável, com a senha só ao clicar em "Mostrar".
- **Remover a demonstração:** `database/seed_demo.sql` tira os papéis, os vínculos, os contatos, o registro e os
  convites dos alunos fictícios, e as pessoas que só eram responsáveis deles.
- **Os Primeiros passos:** a fase "Os responsáveis e o convite" (`esc_responsaveis`), marcada sozinha com um papel e um
  convite, com o "Faça comigo" (`painel/app/adms/Roteiros/fase-escola-responsaveis.json`).
- **A ajuda:** cadastrar, designar o financeiro e o titular, liberar o adicional, trocar o financeiro, copiar do ano
  anterior, importar, convidar (um e a turma), bloquear, entrar no portal, o que o responsável vê, o que cada papel vê
  e os relatórios dos responsáveis.
- **Os relatórios:** R-SEC-05, os alunos sem o financeiro ou sem o titular no ano, por turma; R-AUD-05, os acessos
  adicionais em vigor, com o motivo e quem liberou.
- **O registro da instalação:** nada de responsável (nem contagem, nem dado de pessoa).

## O fechamento (Etapa 7)

- **A atualização:** uma instalação da 1.50.0 vai à 1.56.0 pela tela (as etapas do banco 0038 a 0043, uma por vez),
  nos dois tipos; as seis tabelas acima nascem vazias, e os dados acadêmicos ficam idênticos
  (`tests/tabler/onda7/etapa7.js superior|escola`).
- **As provas:** `tests/tabler/onda7/etapa1.js` a `etapa7.js`, cada uma com a sabotagem; na bateria inteira, nos dois
  tipos.

## O login do portal (Onda 8, Etapa 1; D-67)

- **Um login por conta, separado do cadastro.** A entrada procura só nele (`core/LoginPortal.php`).
  - **O aluno:** o número de matrícula. O e-mail e o CPF do cadastro do aluno são opcionais e não servem para entrar;
    o campo de login das Configurações não vale na escola (a tela mostra "o número de matrícula").
  - **O responsável:** o CPF ou o e-mail do cadastro, escolhido por ele no convite, quando cria a senha
    (`esc_responsaveis.login`; o CPF só com os dígitos). Quem já tinha a senha antes desta versão ficou com o e-mail
    como login (ou o CPF, sem e-mail).
- **Na entrada:** o que foi digitado e é um CPF válido (com ou sem a pontuação), ou tem "@", é o login de um
  responsável; o resto é a matrícula do aluno, só com os números. Um caminho só: nunca o aluno e, se a senha não
  confere, o responsável. A mensagem é a mesma para o login que não existe e para a senha errada.
- **Dois logins iguais não existem**, entre os alunos e os responsáveis juntos:
  - a matrícula tem até 9 dígitos (nunca é um CPF de 11), e na escola a matrícula que é um CPF válido é recusada;
  - o login do responsável é único (índice no banco);
  - o cadastro de uma pessoa (pela tela, pela planilha e pela interface) recusa o e-mail ou o CPF que já é o login de
    outra conta;
  - o convite não sai quando os dois identificadores da pessoa já são o login de outra conta.
- **Trocar o login:** na ficha do responsável, em "Acesso ao portal" (o e-mail ou o CPF do cadastro; a mesma permissão
  de bloquear). Editar o e-mail ou o CPF do cadastro não troca o login.
- **O "esqueci a senha":** pelo mesmo login; o link vai para o e-mail do cadastro.
- **No ensino superior** a entrada do aluno não mudou: o campo escolhido pela instituição (o RA, o CPF ou o e-mail).
