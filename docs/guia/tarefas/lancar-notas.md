---
titulo: Lançar notas
resumo: Nota por aluno na tela da atividade ou por planilha CSV (inclusive exportada do Moodle); vírgula ou ponto, de 0 a 10; a planilha grava tudo ou nada.
perfis: professor, administrador
tipo: escola,superior
ordem: 31
comece: professor=4
relacionados: minhas-disciplinas-e-atividades, acompanhar-notas-e-resultado, lancar-faltas
telas: atividades/visualizar, atividades/upload-notas
entrou: 1.0.0
mudou: 1.95.0
---
## Para que serve

Registrar as notas de cada atividade. A nota fica como você lançou; o arredondamento é só na média final.

## Antes de começar

A atividade existe ([Minhas disciplinas e atividades](guia:minhas-disciplinas-e-atividades)) e a disciplina está aberta.

## Passo a passo

1. Abra a atividade, clique no campo **Nota** do primeiro aluno, digite e aperte **Enter**: o cursor vai na hora para o campo do próximo aluno, pronto para digitar, e a nota grava em segundo plano — pode digitar a próxima sem esperar. Sair do campo com outro valor (Tab ou um clique fora) também grava; **Esc** volta ao que estava gravado.
   - Gravou: um sinal pequeno de confirmado aparece ao lado do campo e some sozinho (passe o mouse: "Gravado").
   - Não gravou: um sinal de alerta fica ao lado do campo de onde você saiu, com o motivo (por exemplo, a etapa fechada). O que você digitou continua lá: volte, corrija e aperte Enter de novo.
   - A nota fora da escala (acima de 10, ou com mais de 2 casas) nem sai do campo: o cursor fica nele, com o motivo, para corrigir.

![Lançamento de notas](img/professor_06_notas.png)

2. De **0 a 10**, até **2 casas**; **vírgula ou ponto** (7,5 = 7.5).
3. Por planilha: na atividade, **Atualizar via Planilha**, com um CSV de colunas `rm;nota` separadas por **ponto e vírgula** (o que o Excel brasileiro salva). Baixe o **modelo** na própria tela.

```
rm;nota
999999;8,5
```

   - Vírgula ou ponto na nota (8,5 = 8.5). Colunas a mais (ex.: o nome) são ignoradas.
   - O arquivo pode vir do Excel em UTF-8 ou no formato antigo (Windows): o acento não quebra.
   - **Tudo ou nada:** se alguma linha estiver errada, nenhuma nota é gravada, e a tela lista cada erro com o **número da linha** do arquivo.
   - Linha com a nota vazia é pulada.

### Do Moodle para o sistema

1. No Moodle, o **número de identificação** de cada aluno (perfil do usuário) deve ser o **RA**.
2. No curso do Moodle, **Notas → Exportar** em planilha (Excel ou CSV), com o "Número de identificação" e a coluna da atividade.
3. Na planilha, deixe só duas colunas: **rm** (o número de identificação) e **nota** (a da atividade), com o cabeçalho `rm;nota`. Salve como **CSV (separado por ponto e vírgula)**.
4. No sistema, abra a atividade correspondente e use **Atualizar via Planilha** com esse arquivo. Repita para cada atividade.

![Importação de notas por CSV](img/professor_08_importar_csv.png)

## O que pode dar errado

- **"Nota inválida: use um número de 0 a 10 com até 2 casas decimais (vírgula ou ponto)."** — nada é gravado.

![Nota inválida recusada](img/professor_07_nota_invalida.png)

- **"Nenhuma nota foi gravada. Corrija as linhas abaixo e envie de novo:"**, com uma linha por erro: **RA … inexistente**, **aluno … não está enturmado nesta oferta**, **nota … fora da escala (0 a 10)**, **nota … em formato inválido** ou **RA … repetido no arquivo**. Corrija as linhas indicadas: nada foi gravado.
- **"o cabeçalho deve ter as colunas rm e nota…"** — use o modelo; o separador é o ponto e vírgula.
- **"Oferta encerrada: a nota não pode mais ser alterada."** — peça a reabertura à secretaria (quem reabre é o administrador).
