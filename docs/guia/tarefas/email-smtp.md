---
titulo: E-mail (SMTP) dos links de acesso
resumo: Configurar o e-mail que envia os links de senha aos alunos.
perfis: administrador
ordem: 64
relacionados: cadastrar-aluno-e-enviar-link, regras-de-nota-e-frequencia
telas: configuracao-email/listar
entrou: 1.0.0
mudou: 1.18.2
---
## Para que serve

Com o SMTP configurado, o link de acesso vai direto para o e-mail do aluno.

## Antes de começar

Tenha servidor, porta, usuário e senha da conta de e-mail da instituição.

## Passo a passo

1. **Configurações → Config. E-mail** e preencha os dados do servidor.

![Configuração de e-mail](img/administrador_15_email.png)

2. Salve e peça à secretaria para enviar um link de teste a um aluno.
3. Para mudar a configuração depois, abra-a e edite: o campo **Senha** vem **sempre em branco** (a senha gravada não é mostrada). Deixe em branco para manter a atual; preencha só para trocar.

## O que pode dar errado

- Sem SMTP (ou sem e-mail no cadastro do aluno), a tela avisa **"O e-mail NÃO foi enviado"** e mostra o link para a secretaria entregar por um canal seguro.
- A senha do e-mail fica só no banco da instituição; ela não aparece em tela, relatório nem guia. Depois de um erro ao salvar, digite a senha de novo: ela não volta preenchida.
