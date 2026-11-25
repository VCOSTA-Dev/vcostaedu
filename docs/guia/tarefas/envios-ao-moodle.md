---
titulo: Envios ao Moodle
resumo: A fila do que vai para o Moodle: o que foi, o que falta, o motivo de cada falha, o "Tentar de novo" e o "Enviar tudo o que falta".
perfis: administrador, secretaria
ordem: 68
relacionados: conectar-ao-moodle, erros-do-moodle
telas: moodle-envios/listar
entrou: 1.34.0
mudou: 1.60.0
---
## Para que serve

Acompanhar o que o VCOSTAEDU manda para o Moodle. Salvar um aluno, uma matrícula ou uma enturmação **nunca falha por causa do Moodle**: cada coisa a enviar vira um item numa fila; o sistema tenta enviar na hora e, se não der, o item fica pendente, com o motivo.

## Antes de começar

O Moodle precisa estar conectado (Integrações → Conectar ao Moodle, pelo Administrador). Sem conexão, nada vai para a fila.

## Passo a passo

1. Abra **Integrações → Envios ao Moodle**. No alto, quantos envios estão pendentes, quantos falharam e quando foi a última chamada que deu certo.
2. Filtre por situação: **Pendente**, **Enviado**, **Enviado com aviso** ou **Falhou**. Cada falha diz o que aconteceu e **o que fazer**.
3. Resolvido o motivo, clique em **Tentar de novo** na linha. Quando o motivo era o e-mail do aluno, basta corrigir o cadastro: salvo o aluno, o usuário dele vai ao Moodle e as inscrições dele que tinham falhado pelo mesmo motivo voltam à fila sozinhas.
   O que fica pendente (por exemplo, o que não coube no tempo de uma tela) e o que falhou por um motivo passageiro (o Moodle fora do ar, sem resposta) **andam sozinhos**: a cada poucos segundos, uma das telas ou chamadas da interface manda um pouco do que sobrou, sem clique de ninguém (a falha passageira volta com espera crescente, até uma hora). O que só uma pessoa resolve (sem e-mail, o e-mail em mais de uma conta) não é tentado de novo sozinho.
4. **O e-mail em mais de uma conta do Moodle:** o VCOSTAEDU não escolhe sozinho. Na linha do envio aparece a lista das contas (o número de cada uma no Moodle): escolha a da pessoa e clique em **Ligar a esta conta**; o envio segue com ela.
5. **Enviar tudo o que falta** envia a fila inteira em passos curtos, com a janela de progresso. Na primeira vez, ele também manda o que já existia antes da conexão (as disciplinas abertas, os alunos enturmados nelas e os professores). Se fechar a página, nada se perde: o botão **Continuar o envio** retoma.
6. Para o reenvio não depender de alguém usar o sistema (a instalação parada à noite, por exemplo), agende no servidor o comando `php database/ferramentas/moodle_enviar.php`, rodado da pasta da instalação; ele faz o mesmo que **Enviar tudo o que falta** e sai com o código 1 se o Moodle não responder (o que falta fica para a próxima vez). Não é obrigatório.
   - **Linux (cron):** a cada 15 minutos, `*/15 * * * * cd /caminho/da/instalacao && php database/ferramentas/moodle_enviar.php > /dev/null 2>&1`.
   - **Windows (Agendador de Tarefas):** uma tarefa que repete a cada 15 minutos, com o programa `php.exe`, o argumento `database/ferramentas/moodle_enviar.php` e a pasta de início na pasta da instalação.

## O que pode dar errado

- **"O aluno não tem e-mail cadastrado."** — o Moodle exige e-mail: cadastre na ficha do aluno; o envio vai sozinho. Ou escolha, em Conectar ao Moodle, um modo com o e-mail da escola.
- **"O e-mail do cadastro do aluno é o mesmo de outro aluno."** — irmãos com o e-mail da família: cada conta do Moodle é de uma pessoa só. Cadastre um e-mail só do aluno, ou escolha um modo com o e-mail da escola.
- **"O Moodle não respondeu"** ou **"demorou demais"** — o que falta fica na fila; use **Tentar de novo** mais tarde, ou **Enviar tudo o que falta**.
- **"Enviado com aviso"** — o Moodle gravou a inscrição, mas não mandou o e-mail de aviso dele ("Message was not sent."): a inscrição está certa; quem cuida do Moodle confere o e-mail dele.
- **"Enviado com aviso: a conta está SUSPENSA no Moodle"** — o aluno já tinha conta no Moodle (achada pelo e-mail) e ela foi ligada, mas está suspensa lá: a pessoa não entra no Moodle até quem cuida do Moodle reativar a conta.
- A pessoa que **já tem conta no Moodle** (com o mesmo e-mail) é ligada a ela, sem criar outra.
- Mudou o **nome ou a turma da disciplina** no VCOSTAEDU: a disciplina que o VCOSTAEDU criou no Moodle muda de nome junto. A que foi só **ligada** a uma disciplina que já existia no Moodle não é renomeada.
- O nome ou o e-mail que o aluno muda no próprio perfil do portal entra na fila e vai no próximo **Enviar tudo o que falta**.
