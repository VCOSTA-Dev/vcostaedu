---
titulo: Usuários da equipe e titulação
resumo: Cadastrar secretaria e professores, com a titulação que sai no histórico; quem pode alterar quem.
perfis: administrador
ordem: 63
comece: administrador=7
relacionados: perfis-de-acesso, entrar-no-painel, completar-a-oferta
telas: usuarios/listar, usuarios/cadastrar, usuarios/editar, usuarios/visualizar
entrou: 1.0.0
mudou: 1.29.0
---
## Para que serve

Dar acesso ao painel a quem trabalha na secretaria e aos professores.

## Antes de começar

Tenha nome, usuário, e-mail e o perfil de cada pessoa; de cada professor, a **titulação** (Especialista, Mestre, Doutor).

## Passo a passo

1. **Equipe → Usuários → Cadastrar**: nome, usuário, e-mail, senha inicial e o **nível de acesso** (o perfil: veja [Perfis de acesso](guia:perfis-de-acesso)). Para professor, a **titulação**. A lista de níveis oferece só os níveis **abaixo do seu**.

![Cadastro de usuário com a titulação](img/administrador_09_usuario_cadastrar.png)

2. Entregue a senha por um canal seguro e peça a troca no primeiro acesso ([Entrar no painel e trocar a senha](guia:entrar-no-painel)).
   - **Situação.** **Ativo** entra normalmente. **Atualizar Dados** entra e, antes de usar o sistema, vai para a tela **"Atualize seus dados"**, onde confere e salva os dados pessoais; ao salvar, a situação volta sozinha para Ativo. **Inativo** e **Bloqueado** não entram.
3. **Quem altera quem.** Você só cadastra, edita, troca a senha, desativa ou apaga quem é de nível **abaixo do seu**.
   - Na lista, quem é do seu nível ou de um nível acima aparece como **Protegido**, sem os botões de editar e apagar.
   - Você não aparece na lista: o seu próprio cadastro e a sua senha ficam no **Perfil** (clique no seu nome, embaixo do menu).
   - A ordem dos níveis, de cima para baixo: **Administrador**, Direção acadêmica, Supervisão de secretaria, Secretaria, Coordenação de curso, Professor; os níveis criados numa versão anterior do sistema vêm depois e continuam valendo.
4. **Perfis de acesso** (o que cada perfil pode fazer, só para consultar: [Perfis de acesso](guia:perfis-de-acesso)) e o grupo **Manutenção** são só do Administrador.

## O que pode dar errado

- **"Erro: titulação inválida."**
- **"Erro:Não foi possível cadastrar o usuário!"**: o usuário ou o e-mail já foram usados, ou falta um campo obrigatório. A tela volta com os dados preenchidos, **menos a senha**: por segurança, ela nunca volta. Digite de novo.
- **"Erro: Você só pode alterar usuários e níveis de acesso abaixo do seu."**: você tentou alterar alguém do seu nível ou de um nível acima. Quem está acima precisa fazer a alteração.
- Professor sem titulação: a prévia do histórico avisa "docente … sem titulação".
