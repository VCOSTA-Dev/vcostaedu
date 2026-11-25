# O site de exemplo de uma instituição que vende um curso livre

Um site de demonstração: a página de venda do curso **IA na prática para professores**, de uma instituição fictícia
(o *Instituto Ventoalto*). Quem se inscreve pelo site vira, numa requisição só, aluna ou aluno da plataforma: cadastrado,
matriculado, na turma, com usuário no ambiente de estudo (o Moodle) e inscrito nos quatro módulos. Fica **fora do
produto e do pacote**, como o outro exemplo (`exemplos/portal-matricula`). Os dados são fictícios.

## Como funciona

1. `index.php` — a página de venda, com o formulário (nome, e-mail, celular). Funciona sem JavaScript.
2. `inscrever.php` — o servidor do site chama **uma** operação da interface, `POST /inscricoes`, com o aluno e o destino
   (a turma). O número de matrícula vem de um **contador** da demonstração (em `dados/`, que a web não serve), e a chave
   de repetição (`Idempotency-Key`) vem do envio do formulário: o clique duplo manda a mesma, e a plataforma devolve a
   mesma resposta, sem segunda inscrição.
3. `confirmacao.php` — os passos se cumprindo ("Sua vaga está garantida", "Você está na turma", "Seu ambiente de estudo
   está pronto"), com o tempo que levou. A página se recarrega sozinha enquanto o ambiente é preparado; o que não coube
   na primeira requisição a plataforma termina sozinha nas seguintes.
4. O e-mail de boas-vindas: logo depois da inscrição, o servidor do site pede à plataforma o **primeiro acesso** da pessoa
   nova (`POST /alunos/{ra}/primeiro-acesso`). A plataforma manda, para o e-mail do cadastro, o mesmo link que a
   secretaria manda pela ficha do aluno: de uso único e com prazo. A confirmação avisa que o e-mail foi enviado (sem o
   e-mail configurado na plataforma, ela diz que a mensagem vai depois, e a equipe manda o link pela ficha). A pessoa
   cria a senha pelo link, entra no portal com o número de matrícula (o botão "Entrar no curso" leva à entrada) e clica
   em "Entrar no Moodle".

## O botão "Criar minha senha agora" (desligado por padrão)

Com `botao_criar_senha` ligado na configuração, a plataforma devolve ao site o mesmo link do e-mail, e a confirmação
mostra o botão **Criar minha senha agora**: a pessoa cria a senha ali mesmo, sem abrir o e-mail.

- **O que se perde:** a inscrição deixa de confirmar que o e-mail é da pessoa. Com o botão, qualquer um que preencha o
  formulário com o e-mail de outra pessoa cria a senha e entra, sem nunca ter acesso àquele e-mail. Desligado, só quem
  recebe o e-mail cria a senha.
- **Para que serve:** demonstrações ao vivo, em que o e-mail pode atrasar (ou cair na caixa de spam) e a plateia está
  esperando. Fora disso, deixe desligado.

## O aviso e o limite contra abuso

- `aviso` — uma linha de texto livre, mostrada perto do formulário (vazia por padrão: nada aparece). Na demonstração,
  diga ali que é uma demonstração e quando os dados são apagados.
- `limite_por_endereco` e `janela_minutos` — quantas inscrições novas um mesmo endereço faz na janela (o padrão,
  conservador: 10 em 10 minutos). Uma sala inteira se inscrevendo ao mesmo tempo sai pela mesma rede: suba o teto (a prova
  usa 300). O clique duplo não conta duas vezes.
- `proxies_confiaveis` — os endereços (ou faixas CIDR) dos proxies na frente do site. Só atrás deles o endereço real de
  quem acessa vem do cabeçalho `X-Forwarded-For` (lido como a plataforma lê); sem a lista, vale o endereço da conexão, e
  o cabeçalho, que qualquer um forja, é ignorado.

A chave fica só em `config.local.php`, no servidor do site; nunca vai para o navegador. A página não mostra chave,
endereço nem identificador do ambiente de estudo.

## Apontar o exemplo para uma instalação

1. Na plataforma, **Integrações → Conectar ao Moodle**: conecte e teste; escolha o modo do e-mail (na escola, o padrão
   é o e-mail da escola, com o domínio dela); escolha a categoria onde as disciplinas são criadas; envio automático.
2. **Integrações → Chaves de API**: crie uma chave com **Alunos, Matrículas e Enturmação: gravar** (e nada mais).
3. Descubra o destino. Na escola, a turma do curso: `GET /turmas?ano=2026` (com uma chave que leia "Cadastros de
   apoio") ou a tela da turma. No ensino superior, o curso, o ano e as ofertas (`GET /ofertas?curso_id=…&ano=…`).
4. Copie `config.exemplo.php` para `config.local.php` e preencha o endereço da interface, a chave, a entrada do portal e
   o destino.
5. Sirva a pasta por um servidor com PHP 8 e a extensão curl (por exemplo, um site à parte, ou
   o servidor embutido do PHP, `php -S`, dentro da pasta, só para testar). O `.htaccess` nega a configuração, o código comum e o
   contador; noutro servidor, faça o mesmo.

Na demonstração da escola, o curso livre já existe: o segmento "Curso livre: IA na prática para professores
(demonstração)", a "Turma aberta" (500 vagas) e os quatro módulos como disciplinas (na matriz, 3, 2, 3 e 2 horas, que
somam as 10 horas: ela só aceita horas inteiras). O `turma_id` é o dessa turma (`GET /turmas?ano=2026`).

A mesma pessoa duas vezes não vira duas: o mesmo envio do formulário fica com o mesmo número de matrícula e a mesma
chave de repetição; a mesma matrícula, mesmo com outra chave, só diz o que já existia.

## O que pôr à mão no Moodle

A plataforma cria no Moodle uma disciplina por módulo, vazia, e inscreve a pessoa. O conteúdo (as aulas, os textos, as
atividades) é posto no Moodle por quem cuida do curso: sem ele, a pessoa entra e vê os quatro módulos sem nada dentro.
