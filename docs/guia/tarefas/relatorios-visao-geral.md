---
titulo: Relatórios: visão geral
resumo: O menu Relatórios — cada relatório responde a uma pergunta, mostra como é calculado e abre a lista de cada número.
perfis: administrador, secretaria, professor
ordem: 60
relacionados: relatorios-a-painel, relatorios-b-secretaria, relatorios-c-desempenho, relatorios-d-frequencia, relatorios-e-risco-conclusao, exportar-e-imprimir-listas
entrou: 1.17.0
mudou: 1.84.0
telas: relatorios/listar, relatorios/parametros
---
## Para que serve

Responder perguntas de decisão sem montar planilha: "como está a instituição agora?", "quem vai reprovar por falta?", "quem já pode receber o histórico final?". Cada relatório existe para uma pergunta, escrita no topo da tela, logo abaixo do nome do relatório (e também na lista dos relatórios e em **Como é calculado**).

## Antes de começar

- Cada perfil vê só os relatórios a que tem acesso (Configurações → níveis de acesso). O professor vê os das ofertas dele.
- Os números saem das mesmas contas das telas de notas, do boletim e do histórico: nada é recalculado à parte. Oferta **aberta** = valores **provisórios** (o encerramento é que grava).

## Passo a passo

1. Abra **Relatórios → Todos os relatórios**. Eles estão por categoria: A. Painel gerencial, B. Secretaria e cadastro, C. Desempenho acadêmico, D. Frequência, E. Evasão, risco e conclusão, F. TCC, G. Documentos, H. Auditoria.
2. Escolha os **filtros** e clique em **Filtrar**. Os filtros ficam no endereço da página: copie o link e quem abrir verá o mesmo relatório (com os dados que o perfil dele permite).
3. **Como é calculado**: o botão mostra a fórmula de cada número, o que entra e o que fica de fora.
4. **Números clicáveis**: clique num número (ou em "Ver a lista") para ver quem compõe aquele número. A lista sempre tem o mesmo tamanho do número.
5. **Colunas**: o botão Colunas esconde ou mostra colunas. A exportação leva só as visíveis.
6. **Exportar**: Excel, CSV, PDF ou Imprimir. O PDF, a impressão e o Excel trazem no topo a data e hora de geração e os filtros. Toda exportação fica registrada (quem, quando, relatório, filtros, linhas).
7. **Salvar**: guarda os filtros e as colunas com um nome. Escolha um perfil para compartilhar: quem é do perfil abre, mas não altera nem apaga. Os salvos aparecem em Todos os relatórios e no botão Salvos de cada relatório.
8. **Alerta de risco** (administrador): os limites dos sinais do relatório Alunos em risco.

## O que pode dar errado

- O CPF aparece como 123.xxx.xxx-45: o seu perfil não tem a permissão "ver CPF completo em relatórios" (por padrão, só administrador e secretaria). Vale na tela e nos arquivos.
- "O filtro recebeu um valor que não está disponível para o seu perfil": o link veio de outro perfil (ex.: uma oferta que não é sua). O filtro foi ignorado.
- "A exportação não pôde ser registrada": a sessão expirou. Entre de novo; sem o registro o arquivo não é gerado.
- Um relatório não aparece: o seu perfil não tem acesso a ele.
