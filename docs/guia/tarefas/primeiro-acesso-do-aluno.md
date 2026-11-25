---
titulo: Primeiro acesso do aluno
resumo: Definir a própria senha pelo link recebido da secretaria.
perfis: aluno, secretaria, administrador
ordem: 70
comece: aluno=2
relacionados: entrar-no-portal, esqueci-a-senha, cadastrar-aluno-e-enviar-link
entrou: 1.0.0
mudou: 1.19.0
---
## Para que serve

Criar a sua senha do portal. Ninguém da instituição fica sabendo dela.

## Antes de começar

A secretaria cadastrou você e enviou um **link de acesso** (por e-mail ou entregue pessoalmente).

## Passo a passo

1. Abra o link. Ele leva à página **Definir a senha**, que abre em seguida o formulário (o navegador precisa estar com o JavaScript ligado, como já é o normal).
2. Digite a nova senha duas vezes: pelo menos 12 caracteres; espaços são aceitos ([Escolher uma senha](guia:escolher-uma-senha)).
3. Clique em **Atualizar Senha**. Pronto: entre no portal ([Entrar no portal](guia:entrar-no-portal)).

![Definição da senha pelo link](img/aluno_02_primeiro_acesso.png)

> O link vale por tempo limitado (48 horas, se a instituição não mudou) e uma vez só. O código do link não aparece na barra de endereço depois que a página abre, e não fica gravado no servidor.

## O que pode dar errado

- **"Este link é inválido, já foi usado ou expirou. Peça um novo em Esqueci a senha ou procure a secretaria."**
- **"Para definir a senha, o navegador precisa do JavaScript ligado. …"** — ligue o JavaScript e abra o link de novo, ou abra-o em outro navegador.
- **"Este link está num formato antigo e não vale mais. Peça um novo em Esqueci a senha ou procure a secretaria."** — links emitidos antes da versão 1.7.0 não valem mais: peça outro.
- **"As duas senhas não conferem."**
- **"A senha precisa ter pelo menos 12 caracteres."**, **"Esta senha é muito comum e fácil de adivinhar. Escolha outra."** ou **"A senha não pode conter o seu nome, o seu usuário ou o seu e-mail."** — veja [Escolher uma senha](guia:escolher-uma-senha).
