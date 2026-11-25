---
titulo: Quando algo não vai com o Moodle
resumo: O glossário dos avisos da integração com o Moodle: o que aconteceu e o que fazer, aviso por aviso.
perfis: administrador, secretaria
ordem: 69
relacionados: conectar-ao-moodle, envios-ao-moodle
entrou: 1.34.0
mudou: 1.58.0
---
## Para que serve

Entender um aviso da integração com o Moodle — em Envios ao Moodle, no teste da conexão ou na saúde da conexão — e saber o que fazer. Com o Moodle, o problema costuma ser configuração do lado dele: os serviços web desligados, a chave trocada, uma função fora do serviço do VCOSTAEDU ou o e-mail do Moodle.

## Antes de começar

O mesmo glossário está no fim da tela **Integrações → Conectar ao Moodle**.

## Passo a passo

1. Ache o aviso na lista abaixo (o texto é o mesmo da tela).
2. Faça o que a coluna "o que fazer" diz; depois, em **Envios ao Moodle**, clique em **Tentar de novo**.

## O que pode dar errado

- **O Moodle não reconheceu a chave.** — A chave foi trocada ou apagada no Moodle. Em "Conectar ao VCOSTAEDU", no Moodle, gere de novo e cole aqui.
- **O Moodle recusou o acesso ao serviço.** — Confira no Moodle se os serviços web estão ligados (Administração do site → Recursos avançados) e se a função está no serviço do VCOSTAEDU.
- **A conta do serviço não tem a permissão para isso no Moodle.** — No Moodle, confira o papel da conta do serviço do VCOSTAEDU (o plugin cria a conta com as permissões certas). Se alguém as mudou, use o botão "Corrigir papel e serviço" na página do plugin, no Moodle.
- **O Moodle recusou os dados enviados.** — Costuma ser um dado repetido (o nome de usuário ou o e-mail de outra pessoa no Moodle) ou inválido. Confira o cadastro e tente de novo.
- **O Moodle não achou o registro (a disciplina ou o usuário ligado pode ter sido apagado lá).** — Confira no Moodle. Se a disciplina foi apagada, ligue a disciplina de novo ou deixe o VCOSTAEDU criar outra.
- **O serviço do VCOSTAEDU está desligado no Moodle.** — No Moodle, ligue o serviço do VCOSTAEDU (Administração do site → Servidor → Serviços externos).
- **Os serviços web estão desligados no Moodle.** — No Moodle: Administração do site → Recursos avançados → ligue "Serviços web".
- **O aluno ainda não existe no Moodle.** — Envie o aluno ao Moodle (Envios ao Moodle) e tente de novo.
- **A conta do aluno foi apagada no Moodle.** — Recrie a conta no Moodle, ou peça a quem cuida do Moodle para restaurá-la.
- **A conta do aluno está suspensa no Moodle.** — Quem cuida do Moodle reativa a conta (ou a matrícula foi trancada ou cancelada).
- **A conta do aluno não está confirmada no Moodle.** — Quem cuida do Moodle confirma a conta.
- **A conta do aluno não pode entrar no Moodle (a autenticação dela está como "sem login").** — No Moodle, mude a autenticação da conta para "Contas manuais".
- **A conta é a de visitante do Moodle.** — Ligue o aluno a uma conta própria.
- **A conta é de administrador do Moodle: o link de entrada não vale para ela.** — Use uma conta de aluno.
- **A conta é a do próprio serviço do VCOSTAEDU.** — Ligue o aluno a uma conta própria.
- **A forma de entrada da conta está desligada no Moodle.** — No Moodle, ligue "Contas manuais" em Plugins → Autenticação.
- **O Moodle não respondeu (o endereço não abre, ou o servidor está fora do ar).** — Confira o endereço e se o Moodle abre no navegador. O que falta fica na fila e vai depois.
- **O Moodle demorou demais para responder.** — Pode ser o servidor do Moodle ocupado. O que falta fica na fila; use "Tentar de novo" mais tarde.
- **O certificado do endereço https:// do Moodle não foi aceito.** — Quem cuida do Moodle confere o certificado (vencido, de outro endereço ou não reconhecido).
- **O endereço levou para outro lugar (o Moodle está noutro endereço).** — Confira o endereço do Moodle (com ou sem "www", com https://) e salve de novo.
- **O endereço respondeu, mas não como um Moodle.** — Confira se o endereço é o do Moodle (a página inicial dele).
- **O Moodle gravou a inscrição, mas não conseguiu mandar o e-mail de aviso ("Message was not sent.").** — Nada a fazer no VCOSTAEDU: a inscrição está certa. Quem cuida do Moodle confere o envio de e-mail dele.
- **O aluno não tem e-mail cadastrado: o Moodle exige um.** — Cadastre o e-mail na ficha do aluno; o envio vai sozinho. Ou, em Conectar ao Moodle, escolha um modo com o e-mail da escola.
- **O e-mail do cadastro do aluno é o mesmo de outro aluno (irmãos com o e-mail da família, por exemplo): a conta do Moodle é de uma pessoa só.** — Cadastre um e-mail só do aluno na ficha; o envio vai sozinho. Ou, em Conectar ao Moodle, escolha um modo com o e-mail da escola.
- **O modo de e-mail usa o e-mail da escola, mas o domínio dele não foi informado.** — Em Conectar ao Moodle, informe o domínio do e-mail da escola (por exemplo, alunos.suaescola.edu.br) e salve.
- **O e-mail está em mais de uma conta do Moodle: o VCOSTAEDU não escolhe sozinho qual é a da pessoa.** — Em Envios ao Moodle, na linha do envio, escolha a conta (o número de cada uma no Moodle aparece ali); o envio segue com ela.
- **Ligado a uma conta que já existia no Moodle, mas que está SUSPENSA lá: a pessoa não entra no Moodle enquanto a conta não for reativada.** — Quem cuida do Moodle reativa a conta (Administração do site → Usuários). Nada a fazer no VCOSTAEDU.
- **O Moodle não está conectado.** — O Administrador conecta em Integrações → Conectar ao Moodle.
- **A disciplina ainda não está ligada a uma do Moodle.** — Com "vincular", informe na tela da disciplina qual é a disciplina do Moodle; com "criar", ela vai sozinha.
- **Falta escolher a categoria do Moodle onde as disciplinas são criadas.** — Em Conectar ao Moodle, escolha (ou crie) a categoria.
