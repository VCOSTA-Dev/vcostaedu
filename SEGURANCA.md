# Política de segurança

Achou uma falha de segurança? Obrigado por avisar — e por avisar do jeito certo.

## Como avisar

**Por canal privado, nunca num relato público.** Uma falha publicada antes da correção deixa todas as instituições que
usam o sistema expostas ao mesmo tempo.

Escreva para **[A DEFINIR: e-mail para avisos de segurança]** com:

1. **O que é:** a tela ou o endereço, e o que dá para fazer que não deveria.
2. **Como reproduzir,** passo a passo, de preferência na demonstração fictícia.
3. **A versão,** que fica no rodapé de qualquer tela.

Não mande dado real de aluno, professor ou responsável. Se a falha expôs algum, diga só que expôs, e o tipo de dado.

## O que acontece depois

- **A confirmação de recebimento** sai em até **[A DEFINIR: prazo, em dias úteis]**.
- Quando a falha é confirmada, combinamos com você a data da publicação: depois da versão corrigida, com tempo para as
  instituições atualizarem.
- A versão que corrige falha de segurança sai **marcada como correção de segurança**, e a tela **Avisos do sistema**
  de cada instalação mostra isso a quem administra.
- Quem avisou é citado nas notas da versão, se quiser.

## O que está no alcance

O sistema deste repositório: o painel, o portal do aluno, a API, o instalador e o plugin do Moodle. As bibliotecas de
terceiros que ele usa têm os próprios canais; uma falha numa delas que afete o sistema é bem-vinda aqui também.

Não faça teste que derrube o servidor de alguém, que mexa em dado de outra pessoa ou que use força bruta contra uma
instalação que não é sua. Para testar, instale a sua, com a demonstração fictícia.

## Como manter a sua instalação segura

- Atualize pela tela **Manutenção → Atualizar o sistema**; a versão nova aparece em **Avisos do sistema**.
- Confira **Manutenção → Verificações de segurança** depois de instalar e depois de cada mudança no servidor.
- Se quiser receber os avisos de segurança por e-mail, use o **Registrar instalação** (Manutenção), que é voluntário.
