---
titulo: Conectar ao Moodle
resumo: O endereço e a chave do Moodle, o teste da conexão e o que acontece com as disciplinas, o trancamento e o professor.
perfis: administrador
ordem: 67
relacionados: envios-ao-moodle, erros-do-moodle
telas: moodle/conexao
entrou: 1.34.0
mudou: 1.102.1
---
## Para que serve

Ligar o VCOSTAEDU ao Moodle da instituição. Conectado, o aluno, a disciplina, a enturmação, o trancamento e o professor seguem sozinhos para o Moodle, e o aluno entra no Moodle pelo portal, sem senha.

## Antes de começar

- O Moodle precisa do plugin **local_vcostaedu** instalado (por quem cuida do Moodle, como administrador do site). O plugin cria no Moodle a página **Conectar ao VCOSTAEDU**, com o endereço e a chave.
- A tela fica em **Integrações → Conectar ao Moodle** e é só do Administrador.

## Passo a passo

1. Em **Integrações → Conectar ao Moodle**, cole o **endereço** do Moodle (com https://) e a **chave** que o Moodle mostrou.
2. Confira **as escolhas da instituição** — cada uma diz o que acontece:
   - **Disciplina no Moodle:** *criar pelo VCOSTAEDU* (cada disciplina ofertada vira uma disciplina nova no Moodle, na categoria escolhida — você escolhe uma das categorias do Moodle ou cria uma nova) ou *vincular a uma que já existe* (na tela de cada disciplina, você informa qual é a do Moodle).
   - **Trancamento ou cancelamento da matrícula:** *suspender a inscrição* (o padrão: o aluno não entra, e tudo volta na reativação), *remover a inscrição* ou *não fazer nada*.
   - **Professor da disciplina:** inscrever no Moodle (com edição, o padrão, ou sem edição) ou não. O coordenador do curso não é enviado.
   - **Envio:** *automático*, a cada mudança (o padrão), ou *só pelo botão*.
   - **O e-mail do aluno no Moodle:** o Moodle não aceita conta sem e-mail, e cada conta é de uma pessoa só. O **e-mail da escola** é feito da matrícula no domínio que você informar (por exemplo, 260101@alunos.escola.exemplo.invalid). Três modos:
     - *sempre o e-mail da escola* (o padrão na escola): todo aluno vai com o endereço da escola, tenha ou não e-mail no cadastro;
     - *o e-mail do aluno, ou o da escola*: o do cadastro do aluno; sem ele, ou se outro aluno tem o mesmo (irmãos com o e-mail da família), o da escola;
     - *só o e-mail do aluno* (o padrão no ensino superior): sem e-mail no cadastro, ou com o mesmo de outro aluno, o aluno fica fora do Moodle, com o motivo em Envios ao Moodle.

     O endereço da escola **não recebe mensagens**: o Moodle não consegue mandar nada a ele, e o "Esqueci a senha" do Moodle não funciona para ele. O aluno entra no Moodle **pelo portal** (o botão "Entrar no Moodle"), sem senha do Moodle. O endereço não é gravado no cadastro do aluno. Se o modo ou o domínio mudar, os alunos que o sistema criou no Moodle vão com o endereço novo; a conta que já existia no Moodle, e que o sistema só ligou ao aluno, fica com o e-mail dela.
3. Clique em **Salvar** e depois em **Testar conexão**. Dando certo, a tela mostra o nome e a versão do Moodle e que as 17 funções do serviço estão lá; os envios começam.
4. A **saúde da conexão**, na mesma tela (e num cartão em Manutenção → Avisos do sistema): a última chamada que deu certo, quantos envios estão pendentes e quantos falharam.
5. **Desconectar** apaga a chave e para os envios; o que já está ligado ao Moodle continua ligado.

## O que pode dar errado

- **"O Moodle não reconheceu a chave."** — a chave foi trocada ou apagada no Moodle: gere de novo em "Conectar ao VCOSTAEDU", no Moodle, e cole aqui.
- **"O Moodle recusou o acesso ao serviço."** — os serviços web estão desligados no Moodle, ou falta uma função no serviço do VCOSTAEDU.
- **"Endereço sem https:// só é aceito numa instalação local."** — use o endereço https:// do Moodle.
- Os outros avisos estão em **Quando algo não vai com o Moodle** (o glossário), na mesma tela e na Ajuda.
