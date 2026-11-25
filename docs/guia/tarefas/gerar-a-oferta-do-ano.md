---
titulo: Gerar a oferta do ano
resumo: Uma oferta por disciplina da grade, com turma, polo, modalidade e total de encontros.
perfis: administrador, secretaria
tipo: escola,superior
ordem: 13
comece: secretaria=6
relacionados: montar-a-matriz, completar-a-oferta, vincular-e-enturmar
telas: disciplinas/gerar-oferta, disciplinas/listar
entrou: 1.1.0
mudou: 1.8.0
---
## Para que serve

Criar de uma vez as disciplinas do ano a partir da grade, com a carga horária da matriz.

## Antes de começar

A grade tem disciplinas e não está desativada.

## Passo a passo

1. Na grade (ou em **Ensino → Todas as Disciplinas**), clique em **Gerar oferta do ano** e escolha o curso e a grade.
2. As **disciplinas da grade** vêm todas marcadas: desmarque as que não serão oferecidas agora.
3. Escolha o ano, a turma, o polo, a modalidade (vazio = a do curso) e o **total de encontros**.
4. Confirme. Sai uma oferta por disciplina marcada. Repetir não duplica: a oferta que já existe é pulada.

![Gerar oferta do ano](img/secretaria_06_gerar_oferta.png)

5. Confira em **Todas as Disciplinas** (situação **Aberta**). Depois, [complete cada oferta](guia:completar-a-oferta).

![Ofertas do ano](img/secretaria_07_ofertas.png)

> O total de encontros é a base da frequência: sem ele, a disciplina não encerra.

## O que pode dar errado

- **"Escolha ao menos uma disciplina."** — todas ficaram desmarcadas.
- **"Informe a turma e o ano da oferta."**
- **"O total de encontros deve ser um número inteiro de 1 a 999 (ou vazio)."**
- **"A grade N está Desativada: não gera oferta."** — peça ao administrador para reabrir a grade.
