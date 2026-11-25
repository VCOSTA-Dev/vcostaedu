---
titulo: Configurar a regra de média
resumo: O catálogo de regras de média — cada regra com nome, componentes e pesos, obrigatório e limite, arredondamento e nota mínima, e o simulador —, associadas às disciplinas da matriz (uma a uma ou em lote), com o padrão da instituição e a troca na oferta.
perfis: administrador, secretaria
tipo: escola,superior
ordem: 33
relacionados: encerrar-uma-disciplina, reabrir-uma-disciplina, regras-de-nota-e-frequencia, acompanhar-notas-e-resultado
telas: regra-media/listar, regra-media/editar, regra-media/cadastrar, regra-media/aplicar, regra-media/oferta
entrou: 1.13.0
mudou: 1.42.0
---
## Para que serve

Dizer **como a média da disciplina é calculada**. Cada regra tem um **nome** (ex.: "Atividades 30% · Prova 70%") e é um conjunto de **componentes** (ex.: Questionários, Trabalho, Prova), cada um com um **peso**. Os pesos somam 100. Cada componente tem o seu **modo**. A regra também define o arredondamento, as casas e a nota mínima.

Crie quantas regras quiser no **catálogo** e associe cada uma às **disciplinas** que a usam. O catálogo já vem com duas **regras prontas**, para usar como estão ou ajustar: **"Só atividades"** (a média das atividades; para os componentes práticos) e **"Prova mensal + prova bimestral + atividades"** (30%, 40% obrigatória e 30%). Nenhuma delas vira padrão sozinha.

## Antes de começar

A regra que vale para uma disciplina é, nesta ordem:

1. a regra **trocada na própria oferta** (com justificativa, só com a oferta aberta);
2. a regra da **disciplina da matriz**;
3. o **Padrão da instituição**: exatamente uma regra do catálogo, que vale para toda disciplina sem regra.

Não há regra "do curso": para um curso, um tipo de disciplina ou um módulo, aplique a mesma regra a todas as disciplinas desse grupo (passo 7). Mudar uma regra não muda nenhuma disciplina **já encerrada**: o encerramento guarda a regra da época.

## Passo a passo

1. Abra **Configurações → Regras de média**. A lista mostra cada regra, os componentes, **em uso por** quantas disciplinas e ofertas abertas, a situação e as ações (editar, duplicar, ver onde é usada, inativar). Configurações → Instituição também traz o link.
2. **Nova regra** cria uma regra a partir do padrão; **Duplicar** cria "Cópia de …", sem associações, para montar uma variação. Dê um **nome** (obrigatório) e, se quiser, uma descrição.

[PRINT: tela da regra de média com os componentes, a soma dos pesos e o simulador]

3. **Componentes:** nome, peso e modo. Use **+ Componente** para acrescentar. A soma dos pesos aparece embaixo; com soma diferente de 100, o botão **Salvar** fica desligado.
   - **Média aritmética:** a média simples das notas.
   - **Média ponderada:** usa o **peso da atividade**, que aparece no cadastro da atividade quando o componente usa este modo.
   - **Soma (teto 10):** soma as notas, até 10.
   - **Maior nota** e **Menor nota**.
   - **Descartar as menores** e **Manter as maiores:** N atividades ou X%, na coluna **Quantos (N ou %)**, que só aparece quando algum componente usa um desses modos. Em X%, descartar arredonda para baixo e manter, para cima. Fica sempre pelo menos 1 atividade.
   - **Atividades na disciplina** (cada componente):
     - **Obrigatório:** marque no componente que toda disciplina precisa ter (a prova, por exemplo). O **Mín.** começa em 1; ponha 2 ou mais para exigir mais atividades. Sem a atividade obrigatória, a média do aluno fica **"Pendente — falta Prova"**, o peso dela **não** passa para os outros componentes e a disciplina **não encerra**.
     - **Máx.:** o número máximo de atividades do componente em cada disciplina. **Vazio = sem limite.** No cadastro de atividade, o componente cheio aparece como "(2/2)" e não pode ser escolhido.
     - Componente sem "Obrigatório" é **opcional**: pode haver disciplina sem ele, e o peso dele se divide entre os outros (o "componente sem atividade", abaixo).
     - Nenhum componente obrigatório: a tela avisa e deixa salvar.
4. **Conta e aprovação:**
   - casas (1 ou 2);
   - **arredondamento:** matemático, sempre para cima, sempre para baixo ou para o meio ponto;
   - **quando arredondar:** só na média, ou em cada componente e na média;
   - **atividade sem nota no encerramento:** conta zero ou fica de fora. Num componente **obrigatório**, o aluno sem **nenhuma** nota nele fica com **zero** no componente mesmo em "fica de fora" (senão passaria sem a prova); com pelo menos uma nota, "fica de fora" vale dentro do componente;
   - **componente OPCIONAL sem atividade:** o peso dele se divide entre os outros, ou ele conta zero. Vale também para o opcional com atividade em que o aluno não tem nota (com "Fica de fora"). Componente **obrigatório** sem atividade deixa a disciplina **pendente**;
   - **nota mínima.** A aprovação pede também frequência suficiente (FS), pelo mínimo do curso;
   - **Permitir acréscimo** (padrão: desmarcado): o professor lança um **acréscimo**, que **soma à média fechada** — fechou com 5,0, acréscimo 1,0 → 6,0. O acréscimo é uma atividade própria (o tipo **Acréscimo**, que só aparece nas disciplinas cuja regra o permite), fora dos componentes e sem peso. Ele entra depois da conta dos componentes e antes do arredondamento, e a média não passa de 10. **Limite do acréscimo:** o máximo que somam todos os acréscimos do aluno na disciplina (na escola, em cada etapa); vazio = sem limite. A memória de cálculo mostra a linha "Acréscimo: + …".
5. **Simulador**, à direita: digite notas de teste, escolha o componente de cada atividade e veja a **média** e a **memória de cálculo** na hora. A conta é a do próprio sistema. Nada é gravado. Com **Permitir acréscimo** marcado, o campo **Acréscimo** soma o acréscimo de teste.
   - Ele segue as **mesmas travas do cadastro de atividade**: o componente que já chegou ao máximo aparece "(1/1)" e não pode ser escolhido; tirar uma atividade ou mudar o Máx. libera na hora. Se o máximo baixar com atividades a mais, as que sobram ficam em vermelho.
   - **Pendência manda no resultado:** faltando a atividade obrigatória ou passando do máximo, o resultado é **"Pendente — <motivo>"** (ex.: "Pendente — Atividades acima do máximo de 1", "Pendente — falta Prova"), nunca Aprovado ou Reprovado; quando dá para calcular, a média aparece como **média provisória**. A tela de notas e o encerramento mostram o mesmo.
   - A coluna **Peso** do simulador só aparece quando algum componente usa média ponderada, e só vale nas atividades desses componentes.
6. **Salvar a regra.** O topo mostra a última alteração e em quantos encerramentos ela foi usada (eles guardam a regra da época e não mudam). Se a regra **está em uso**, salvar mostra antes as disciplinas e as ofertas **abertas** afetadas e as que ficariam fora dos limites (faltando atividade obrigatória ou acima do máximo); confira e use **Confirmar e salvar**. Disciplina já encerrada não muda.
7. **Associar às disciplinas:**
   - **uma a uma:** em **Ensino → Matriz**, coluna **Regra de média** de cada disciplina (vazio = Padrão da instituição), e o botão ao lado para salvar;
   - **em lote:** na tela da regra, **Aplicar a disciplinas…**: filtre por curso e grade, marque as disciplinas, clique em **Ver o que muda** (a lista mostra a regra de hoje → a nova) e **Confirmar e aplicar**.
8. **Na oferta:** a aba **Regra de média** da disciplina mostra "Regra: <nome> (da disciplina | padrão da instituição | própria desta oferta)". Com a oferta **aberta**, dá para trocar só nela, com **justificativa**; as trocas ficam registradas. Encerrada, não muda.
9. **No curso — a regra do TCC e dos documentos:** na tela do curso, **Regra de média do TCC e dos documentos** escolhe a regra cuja **nota mínima** vale para a aprovação do TCC e para a legenda dos documentos do curso ("Nota mínima por disciplina"). Vazio = o Padrão da instituição. A média de cada disciplina continua seguindo a regra dela. O TCC e o documento já emitidos guardam o que usaram.
   - **A legenda tem de dizer a verdade:** se alguma disciplina do curso exige uma nota mínima diferente da que a legenda dos documentos vai dizer, a tela do curso **avisa** ao lado do campo ("3 disciplinas exigem 7,0, e a legenda dos documentos dirá 6,0"), com a lista e o link para a regra de cada disciplina; ao salvar o campo, o mesmo aviso aparece antes de confirmar.
   - No **histórico** (parcial e final), a mesma divergência vira um **alerta** que cita as disciplinas; no **FINAL**, bloqueia, como os outros alertas. Disciplina encerrada vale pela nota mínima **gravada** no encerramento. Alinhe a regra das disciplinas ou a do curso.
10. **Padrão da instituição:** só o administrador troca, no botão da estrela da lista. Antes de confirmar, a tela conta as disciplinas sem regra própria e as ofertas abertas que mudam; as que têm regra própria não mudam.
11. **Inativar:** só uma regra **sem uso** (nenhuma disciplina da matriz, nenhuma oferta aberta e nenhum curso no campo do TCC e dos documentos). Em uso, a tela diz onde. Regra inativa não aparece para escolha; **Reativar** a traz de volta. O padrão não pode ser inativado.

## O que pode dar errado

- **"Os pesos dos componentes devem somar 100 (soma atual: 90)."** — ajuste os pesos.
- **"Componente …: N deve ser uma quantidade inteira de 1 a 99."** ou **"o percentual deve ser um inteiro de 1 a 99."** — nos modos descartar/manter.
- **"Nota mínima: um número de 0 a 10, com até 2 casas."**
- **"O limite do acréscimo: uma nota de 0 a 10 (ou vazio, sem limite)."**
- **O tipo "Acréscimo" não aparece no cadastro de atividade** — a regra da disciplina não permite o acréscimo. Marque **Permitir acréscimo** na regra (ou use uma regra que permita).
- **"Componente inválido: escolha um dos componentes da regra de média desta oferta."** — no cadastro de atividade, o componente veio de outra regra. Escolha um da lista.
- **"Componente …: o máximo de atividades (2) não pode ser menor que o mínimo (3)."** — ajuste o Mín. ou o Máx. Vazio no Máx. é "sem limite".
- **"O componente Prova aceita no máximo 2 atividades nesta disciplina."** — no cadastro de atividade: o componente já está cheio. Use outro componente ou aumente o Máx. na regra.
- **"Falta cadastrar: Prova (mínimo 1)."** — nas telas da disciplina e no encerramento: cadastre a atividade que falta no componente obrigatório.
- **"Nome da regra: obrigatório"** ou **"Já existe uma regra com o nome …"** — dê um nome único.
- **"A regra está em uso e não pode ser inativada."** — a lista abaixo diz as disciplinas e as ofertas: troque a regra delas antes.
- **"A oferta está ENCERRADA: a regra não muda."** — o encerramento guardou a regra que valeu; só o administrador reabre.
- **"Justificativa: escreva o motivo da troca."** — na troca da regra da oferta.
- A secretaria não muda o Padrão da instituição (nem o troca): é do administrador.
