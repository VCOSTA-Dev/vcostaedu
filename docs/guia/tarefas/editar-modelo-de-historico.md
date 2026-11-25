---
titulo: Editar um modelo de histórico
resumo: O documento no editor visual, as variáveis e os blocos, os trechos condicionais, o quadro de disciplinas, as assinaturas, a prévia e as versões dos modelos PARCIAL, FINAL e do atestado de matrícula.
perfis: administrador
tipo: escola,superior
ordem: 55
comece: administrador=9
relacionados: emitir-historico-final, emitir-historico-parcial, emitir-atestado-de-matricula, dados-da-instituicao-e-atos-legais
telas: historico-modelos/listar, historico-modelos/editar, historico-modelos/cadastrar
entrou: 1.5.0
mudou: 1.12.0
---
## Para que serve

Deixar o histórico com a cara da instituição. O modelo é **um documento só**, escrito como num editor de texto. A secretaria só escolhe o modelo na hora de emitir.

## Antes de começar

O sistema vem com dois modelos neutros: **Histórico parcial — padrão** e **Histórico final — padrão**. O tipo **Atestado de matrícula** nasce de um modelo neutro ao escolher **Novo modelo** com esse tipo. Cadastre antes os [dados da instituição e os atos legais](guia:dados-da-instituicao-e-atos-legais).

## Passo a passo

1. **Configurações → Modelos de histórico**, **Editar** (ou **Novo modelo**).

![Lista de modelos, com tipo, cursos, versão e situação](img/administrador_18_modelos.png)

2. Nome, **cursos a que se aplica** (nenhum = padrão da instituição) e **situação** (só **Ativo** aparece para emitir).
3. **Documento**: escreva e formate como num editor de texto (negrito, listas, alinhamento, tabela, títulos). Colar sempre entra como texto simples.
4. **Variáveis** (aba à direita): clique no ponto do texto e depois na variável (ex.: *Nome civil*). Ela entra ali, como `{{ALUNO_NOME}}`, e na emissão vira o dado do aluno. Use a **busca** para achar pelo nome. Da instituição há também cidade, site, telefone, e-mail e cada linha do cabeçalho; do curso, nível, nome por extenso, linha do cabeçalho e sigla. Do aluno, a concordância (ALUNA/ALUNO, matriculada/matriculado, a/o) e o CPF mascarado (123.xxx.xxx-45); da matrícula, a situação no documento (EM CURSO) e o ingresso e a previsão de conclusão como "novembro de 2025"; para a legenda, FS/FI, os rótulos, a frequência mínima (75%) e a nota mínima (7,0).
5. **Blocos do sistema** (no alto da aba Variáveis): **Quadro de disciplinas**, **Assinaturas**, **Logo da instituição**, **Cabeçalho institucional** (as linhas de *Dados da instituição* e, por último, a linha do curso), **QR code da verificação** e **Código de verificação**. No documento aparecem como um marcador em destaque (ex.: **[Quadro de disciplinas]**), que o sistema monta na emissão. Para tirar, apague o marcador inteiro; para mudar de lugar, recorte e cole.
6. **Só se houver TCC**: o que ficar entre **[Início: só se houver TCC]** e **[Fim: só se houver TCC]** (grupo TCC) só sai para quem tem TCC.
   **Trecho que só sai se houver o dado** (no alto da aba Variáveis): escolha a variável e clique em **Início** e em **Fim** nos pontos do texto. O que ficar entre os dois só sai quando o dado está preenchido, e o dado vazio **não vira alerta**. Ex.: "Telefone: …" só com o telefone cadastrado; o e-MEC, o e-mail, o site e a linha 5 do cabeçalho. Para o atestado, as variáveis **Emitido pelo aluno no portal** e **Emitido pela secretaria** separam o texto de cada origem.
7. Aba **Quadro**: marque as colunas, o rótulo, a largura (%) e o alinhamento; as setas mudam a ordem. No PARCIAL, escolha também a linha das atividades e o texto de disciplina aberta ("Em curso"). Para o desenho do modelo Word: **Carga horária dentro do nome** ("Nome (**18** horas aula)", com o texto "horas aula" editável), **Uma linha por disciplina** (só a tentativa vigente, sem atividades), **Disciplina em andamento em branco** e **Linhas finais** (média final do aluno, TCC e situação, com fundo cinza; a média só sai no FINAL). Em **Frequência**, escolha como ela sai: **Percentual** (o padrão), **Sigla** (FS/FI, só com a disciplina encerrada, pelo mínimo gravado no encerramento) ou **Os dois**.
8. Aba **Assinaturas**: nome, cargo e, se quiser, a imagem (PNG ou JPEG até 1 MB).
   Aba **Opções**: **Estilo do documento** ("Documento oficial": margens, cabeçalho sem filete e quadro de bordas pretas, como no modelo Word), **Fonte** (Liberation Sans, com a métrica da Arial) e **Casas decimais das notas no documento** (1 ou 2; só a forma de escrever — o cálculo continua com as casas de Configurações → Instituição). Um modelo que não usa essas opções sai como antes.
9. Aba **Prévia**: escolha um aluno e clique em **Gerar a prévia em PDF** (nada é salvo). O PDF aparece ali mesmo, ao lado do documento. Depois, **Salvar modelo**.

![Editor com o documento, os marcadores dos blocos e a aba de variáveis](img/administrador_19_modelo_editor.png)

> Salvar um modelo **ativo** cria uma **nova versão**; as emissões antigas continuam com a versão delas. A coluna "Módulo" do quadro só aparece com *Organizar a matriz em módulos* em **Sim**.

## O que pode dar errado

- **"Nada foi salvo: Documento: conteúdo não permitido — …"** — o documento não aceita script, imagem, quadro de outro site (`iframe`), endereço externo nem código. Tire esse conteúdo e salve de novo.
- **"Nada foi salvo: Documento: variável desconhecida — {{…}}."** — a variável foi digitada à mão ou está errada; use o painel.
- **"Formatação que o documento não usa foi retirada: …"** — o modelo foi salvo; só a formatação que o PDF não usa (fonte, cor) saiu.
- **"… só se houver TCC precisa de um início e de um fim"** — ponha os dois marcadores, o início antes do fim.
- **Variável sem valor no documento** na hora de emitir: o dado está vazio no cadastro do aluno. É um **alerta**: complete o cadastro (no FINAL, a emissão só sai sem alertas).
- Assinatura: **"a imagem deve ser PNG ou JPEG."** e **"a imagem deve ter até 1 MB."**
