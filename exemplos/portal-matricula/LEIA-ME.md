# Portal de matrícula de exemplo

Um portal de inscrição **mínimo**, para mostrar como o site de matrículas de uma instituição conversa com a API do
VCOSTAEDU. **Cada instituição tem o seu** — este é só um ponto de partida, fora do produto: o instalador, a verificação
do ambiente e o pacote do VCOSTAEDU não dependem desta pasta (o pacote nem a leva).

## O que ele faz

1. A pessoa preenche nome, e-mail, CPF, sexo e o curso.
2. O servidor do exemplo, pela API do VCOSTAEDU: **cadastra o aluno** (`POST /alunos`, com um número de matrícula da
   faixa configurada), **matricula no curso** (`POST /matriculas`) e **enturma** nas disciplinas abertas do ano
   (`GET` e `POST /matriculas/{id}/enturmacao`).
3. A página de andamento mostra, em linguagem de uso: cadastro feito, matrícula, disciplinas, enviado ao Moodle e
   acesso pronto (`GET /alunos/{ra}`, o campo `moodle`). O envio ao Moodle é o VCOSTAEDU que faz, sozinho, quando a
   instituição conectou o Moodle: o exemplo **nunca fala com o Moodle nem com o banco**.

## Como pôr para rodar

1. Num servidor com PHP 8 e a extensão curl, copie esta pasta.
2. Copie `config.exemplo.php` para `config.local.php` e preencha: o endereço da API (`https://…/api/v1`), a chave, o
   nome da instituição, os cursos (o id, o ano e a forma de ingresso de cada um) e a faixa dos números de matrícula.
3. A chave: em **Integrações → Chaves de API**, crie uma chave só para o portal, com o limite mínimo — **Alunos,
   Matrículas e Enturmação: consultar e gravar**, e nada mais (Cadastros de apoio fica em "nada": os cursos estão na
   configuração). A chave fica só no `config.local.php`, no servidor; nunca vai para o navegador. Proteja o arquivo.
4. Use HTTPS.

## Como adaptar a outro portal

- **A regra do número de matrícula** é da instituição: troque a faixa aleatória pela sua (um contador, o número do
  processo seletivo, o que for).
- **Os campos**: a API aceita mais dados do aluno (nascimento, telefone, celular, nome social — veja Integrações →
  Documentação da API). Peça só o que a sua instituição precisa.
- **Os cursos** podem vir da API (`GET /cursos`, `GET /anos-letivos`), se a chave tiver "Cadastros de apoio:
  consultar".
- **A enturmação**: o exemplo coloca o aluno em todas as disciplinas abertas do ano; o seu portal pode deixar a
  secretaria decidir (é só não chamar a enturmação).
- **Os erros**: a API devolve sempre `{"erro": {"codigo", "mensagem", "campo"}}`, com a mensagem em português — o
  exemplo mostra a mensagem como veio.
- **Repetir com segurança**: as gravações levam `Idempotency-Key`; repetir a mesma chamada não grava duas vezes.
