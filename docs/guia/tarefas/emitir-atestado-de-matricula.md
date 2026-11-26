---
titulo: Emitir atestado de matrícula
resumo: O atestado de quem está cursando: pela ficha ou pela lista de alunos (secretaria) e pelo portal (o próprio aluno), com código de verificação.
perfis: secretaria, administrador, aluno
tipo: escola,superior
ordem: 52
relacionados: emitir-historico-parcial, conferir-um-documento, cancelar-uma-emissao, editar-modelo-de-historico
telas: atestado/index
entrou: 1.12.0
mudou: 1.74.0
---
## Para que serve

Comprovar que o aluno está matriculado e cursando: curso, carga horária, mês de início e previsão de término. O atestado tem **código de verificação**, como o histórico, e qualquer pessoa confere a autenticidade no endereço impresso.

## Antes de começar

- A matrícula precisa estar em **Cursando**. Em outra situação (Trancado, Concluído…), o atestado não sai.
- A matrícula precisa ter a **previsão de conclusão** (ficha do aluno, aba **Matrículas**, editar a matrícula). Sem ela, a frase do atestado ficaria quebrada, e a emissão é bloqueada.
- Precisa haver um modelo **ativo** do tipo **Atestado de matrícula** que sirva ao curso (**Configurações → Modelos de histórico**, do administrador).

## Passo a passo

### Secretaria

1. Ficha do aluno, aba **Históricos**, **Emitir atestado** na matrícula. Ou, na **lista de alunos**, o botão com o ícone de documento assinado (abre a matrícula em Cursando mais recente).

[PRINT: aba Históricos com o botão Emitir atestado]

2. Confira o modelo e, se quiser, a **Prévia em PDF** (sai com a tarja PRÉVIA; nada é gravado).
3. **Emitir atestado de matrícula**. A emissão guarda a cópia fiel dos dados do dia, o PDF e o código.

> Com a matrícula fora de Cursando, a ficha mostra o botão desligado e a razão: "atestado só com a matrícula em Cursando".

> **Na escola:** a aba da ficha se chama **Documentos** e o painel emite o atestado de matrícula; o histórico escolar da educação básica sai pela vida escolar do aluno (veja [Emitir o histórico escolar](guia:emitir-historico-escolar)). É o mesmo modelo e o mesmo código de verificação do atestado que o aluno e o responsável emitem pelo portal.

### Aluno (portal)

1. Menu **Acadêmico → Atestado de matrícula**.
2. Na matrícula em curso, **Emitir atestado**.
3. Abra o **PDF** na lista **Atestados emitidos**. Ali ficam também os atestados que a secretaria emitiu para você.

[PRINT: portal, tela Atestado de matrícula com o botão e a lista]

> O atestado emitido pelo portal não passa pela assinatura da diretoria: no lugar de "(assinatura digital)", ele traz "Documento emitido eletronicamente em … Confira a autenticidade em … com o código …".

## O que pode dar errado

- **"O atestado de matrícula só é emitido com a matrícula em "Cursando" (situação atual: …)."** — a situação da matrícula não permite.
- **"Falta a previsão de conclusão da matrícula …"** — preencha a previsão na aba Matrículas (secretaria). No portal, procure a secretaria.
- **"O documento tem dado sem valor: …"** — um dado que o modelo usa está vazio no cadastro. Complete e emita de novo.
- **"O atestado ainda não está disponível para o seu curso."** (portal) — não há modelo ativo de atestado para o curso; o administrador ativa um.
- **"Matrícula não encontrada."** (portal) — o aluno só emite o próprio atestado.
