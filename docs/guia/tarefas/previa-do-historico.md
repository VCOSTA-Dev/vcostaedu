---
titulo: Conferir a prévia do histórico
resumo: Os dados que o histórico vai usar, com as tentativas de cada disciplina, os alertas e a prévia em PDF.
perfis: secretaria, administrador
tipo: escola,superior
ordem: 41
relacionados: emitir-historico-parcial, emitir-historico-final, completar-a-oferta, dados-da-instituicao-e-atos-legais
telas: historico/previa
entrou: 1.4.0
mudou: 1.8.0
---
## Para que serve

Conferir tudo o que entra no histórico, sem o formato de documento, e resolver o que falta antes de emitir.

## Antes de começar

O aluno tem matrícula no curso.

## Passo a passo

1. Menu **Educacional → Prévia do histórico** (ou o botão na aba Matrículas). Busque por RA ou nome e escolha a matrícula.
2. Confira:
   - o aluno;
   - a matrícula: polo, modalidade, ingresso e conclusão;
   - a instituição e o **ato legal**;
   - as disciplinas da grade com **todas as tentativas** (a vigente em destaque): docente, titulação, datas de realização, média, frequência e resultado;
   - o TCC e os totais.
3. Resolva os **alertas** do topo. O histórico FINAL só sai sem nenhum alerta.
4. Na barra do topo:
   - **Prévia em PDF — parcial** ou **— final**: o documento com a tarja PRÉVIA; nada é gravado;
   - **Imprimir esta conferência**;
   - **Ir para a ficha do aluno**: para corrigir dados pessoais ou a matrícula;
   - **Emitir histórico**.

![Prévia do histórico com alertas](img/secretaria_24_previa.png)

## O que pode dar errado

- **"Dados pessoais do aluno incompletos (ficha do aluno → Editar): falta …"** — complete a ficha (nascimento, naturalidade, nacionalidade, RG, CPF, filiação).
- **"Ato legal não definido: …"** — nenhum ato ativo e vigente para a modalidade da matrícula (ou mais de um): [Dados da instituição e atos legais](guia:dados-da-instituicao-e-atos-legais).
- **"… disciplina sem docente."** e **"… oferta sem período de realização."** — [Completar a oferta](guia:completar-a-oferta).
- **"…: docente … sem titulação."** — o administrador informa a titulação no cadastro do professor.
- **"O curso exige TCC e …"** — [Concluir o curso e registrar o TCC](guia:concluir-o-curso-e-tcc).
- **Prévia em PDF** desabilitado: não há modelo ativo daquele tipo para o curso (Modelos de histórico).
