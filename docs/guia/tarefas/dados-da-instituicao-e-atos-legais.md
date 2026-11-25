---
titulo: Dados da instituição e atos legais
resumo: Nome, mantenedora, CNPJ, e-MEC, endereço, telefone, e-mail, as linhas do cabeçalho dos documentos, logo e os atos de credenciamento por modalidade.
perfis: administrador
ordem: 60
comece: administrador=6
relacionados: editar-modelo-de-historico, regras-de-nota-e-frequencia, previa-do-historico
telas: dados-instituicao/editar, atos-legais/listar, atos-legais/cadastrar, atos-legais/editar
entrou: 1.4.0
mudou: 1.31.0
---
## Para que serve

Os dados que saem no cabeçalho do boletim e do histórico, e o **ato legal** que o histórico cita.

## Antes de começar

Tenha o CNPJ, o código e-MEC (se houver), o telefone e o e-mail da instituição, o texto do cabeçalho dos documentos (Estado, órgão, diretoria, programa…), o logo (PNG ou JPEG) e os atos de credenciamento com número, data, publicação e vigência.

## Passo a passo

1. **Configurações → Dados da instituição**: nome, mantenedora, **CNPJ**, e-MEC, site, **telefone**, **e-mail**, endereço e **logo**.
2. Escolha o que vai **no topo do menu, ao lado do selo do VCOSTAEDU**: o **nome da instituição**, escrito (em até duas linhas), ou o **logo**. E o **ícone da aba do navegador**: o do VCOSTAEDU (o padrão) ou o logo da instituição. Antes de os dados serem salvos, o topo mostra "VCOSTAEDU".
3. Em **Cabeçalho dos documentos**, escreva até 5 linhas, uma por campo, na ordem em que saem no topo do histórico. As **setas** trocam uma linha de lugar; a borracha apaga o texto. A **prévia** ao lado mostra o logo e as linhas como no documento; a linha do **curso** vem do cadastro de cada curso e sai por último. Salve.
4. **Configurações → Atos legais → Novo ato**: órgão, tipo, número, data, veículo e data de publicação, **vigência** e a **modalidade** a que se aplica. Salve.
5. O topo da lista de atos mostra qual ato vale hoje em cada modalidade.

![Lista de atos legais com o quadro do ato de cada modalidade](img/administrador_17_atos_legais.png)

> O histórico usa o ato **ativo e vigente** da **modalidade da matrícula**; sem nenhum, ou com mais de um, ele avisa "ato legal não definido" em vez de escolher.

## O que pode dar errado

- **"CNPJ inválido (confira os 14 dígitos)."**
- **"Telefone inválido…"** ou **"E-mail institucional inválido."** — confira o que foi digitado; os dois podem ficar vazios.
- **"A linha N do cabeçalho passa de 200 caracteres."** — divida o texto em duas linhas.
- **"O logo deve ser uma imagem PNG ou JPEG."** (até 1 MB, conferido pelo conteúdo).
- **"O fim da vigência não pode ser antes do início."**
- Dois atos vigentes para a mesma modalidade: o cadastro avisa, e o histórico fica com "ato legal não definido" até você inativar um deles.
