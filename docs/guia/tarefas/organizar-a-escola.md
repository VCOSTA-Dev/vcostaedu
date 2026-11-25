---
titulo: Organizar a escola: o ano letivo, as turmas e os alunos
resumo: Na escola, o ano letivo em bimestres ou trimestres, as turmas de cada série com as disciplinas, e os alunos na turma.
perfis: administrador, secretaria
ordem: 6
tipo: escola
relacionados: o-tipo-de-instituicao
telas: escola/index, escola-anos/index, turmas/listar, turmas/cadastrar, turmas/editar, turmas/ver, escola-regras/index
entrou: 1.39.0
mudou: 1.96.0
---
## Para que serve

Na escola, o **segmento** (o Ensino Fundamental, o Ensino Médio) tem uma matriz; cada **série** da matriz tem as suas **disciplinas**. No ano letivo, cada série tem uma ou mais **turmas**, e cada turma tem uma disciplina por linha, com o seu professor. O aluno entra na turma e fica em todas as disciplinas dela, de uma vez. Vale qualquer segmento e qualquer série, do 1º ano do Ensino Fundamental em diante (os anos iniciais, os finais, o Médio): os da demonstração são só um exemplo. A avaliação é por nota, de 0 a 10, em todas as séries.

## Antes de começar

- O segmento, a matriz e as séries, com as disciplinas e a carga horária de cada uma (Ensino → Segmentos e a matriz de cada segmento). **Ensino → Estrutura da escola** mostra tudo junto.
- O ano letivo cadastrado (Ensino → Anos letivos).
- Os alunos cadastrados e vinculados ao segmento (Alunos), ou trazidos por planilha com a coluna da turma (Educacional → Importar alunos).

## Passo a passo

1. **O ano letivo e as etapas:** em **Ensino → Ano letivo e etapas**, escolha o modelo — quatro bimestres (o padrão) ou três trimestres — e clique em **Abrir o ano**. As etapas nascem com datas sugeridas; ajuste o início, o fim e a data do conselho de classe de cada uma e clique em **Salvar as etapas**. Depois que houver nota ou falta lançada no ano, o modelo não muda (as datas, sim).
2. **A turma:** em **Ensino → Turmas**, clique em **Nova turma**, escolha a série, escreva o nome ("A", "B") e, se quiser, o turno, a sala e a capacidade. Ao criar, o sistema gera as ofertas da série: uma por disciplina. A turma aparece como a série e o nome — "6º ano A".
3. **Os professores:** na tela da turma, clique em cada disciplina e indique o professor. O professor vê só as turmas e as disciplinas dele.
4. **Os alunos na turma:** na tela da turma, o cartão **Enturmar alunos** lista os alunos matriculados na série e no ano da turma e ainda sem turma, cada um com a série e o ano. Marque os alunos (ou **Marcar todos**) e clique em **Enturmar os marcados**. A janela mostra o andamento, aluno a aluno; se a página fechar no meio, a turma oferece **Continuar**. A turma também pode ser escolhida no mesmo passo da matrícula (abaixo).
5. **O aluno novo:** em Educacional → Alunos → Cadastrar, o número do aluno já vem preenchido com o próximo da sequência (pode trocar; se outra pessoa salvar o mesmo número antes, a tela avisa e traz o seguinte). Na ficha, aba **Matrículas → Matricular num segmento**: o ano, o segmento e a série, a data da matrícula, a **turma** (opcional: só as da série e do ano) e o **plano de pagamento**, que pergunta **a partir de qual parcela cobrar** — marcada vem a primeira que ainda não venceu; as anteriores não são cobradas, a menos que você escolha. A lista dos alunos mostra a série e a turma de cada um.
6. **O aluno que entra no meio do ano:** as etapas que terminaram antes da data da matrícula ficam sem nota e fora da média anual. Se o aluno trouxe as notas da escola de origem, abra **Notas de transferência** (na aba Matrículas da ficha): para cada disciplina e etapa, a nota e as faltas, com o nome da escola e o documento apresentado. Elas contam na média, aparecem com "T" no boletim e valem mesmo na etapa já fechada; só a secretaria lança, e cada mudança fica na auditoria.
7. **O atestado de matrícula:** na escola, sai com o segmento, a série e o ano letivo, sem a previsão de conclusão e sem o CPF.
8. **Trocar de turma:** na lista de alunos da turma, escolha a turma de destino (da mesma série) e clique em **Trocar de turma**. As notas e as faltas já lançadas vão junto.
9. **Sair da turma:** **Sair da turma** tira o aluno de todas as disciplinas; o que já tinha sido lançado fica guardado.
10. **As regras da escola:** em **Configurações → Regras da escola**, a frequência mínima (75%, o padrão) e onde ela é medida — no total de horas do ano letivo (o padrão) ou em cada disciplina.

## O que pode dar errado

- **"Abra antes o ano letivo com as etapas":** a turma é criada num ano letivo já aberto na tela Ano letivo e etapas.
- **O aluno não aparece para enturmar:** ele precisa de matrícula (vínculo) Cursando no segmento e na matriz da turma, e de não estar em outra turma no mesmo ano.
- **"A turma está completa":** a capacidade da turma foi atingida; aumente-a em Editar ou use outra turma.
- **A troca é recusada:** a troca é entre turmas da mesma série, no mesmo ano.
