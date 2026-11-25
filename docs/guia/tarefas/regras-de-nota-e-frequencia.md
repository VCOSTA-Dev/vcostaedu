---
titulo: Regras de nota e frequência
resumo: Média e frequência mínimas, casas decimais, média parcial no portal, módulos na matriz, login do aluno, validade do link e limites por IP.
perfis: administrador
tipo: escola,superior
ordem: 61
comece: administrador=5
relacionados: configurar-a-regra-de-media, acompanhar-notas-e-resultado, encerrar-uma-disciplina, dados-da-instituicao-e-atos-legais, tentativas-de-acesso
telas: parametros/listar
entrou: 1.2.0
mudou: 1.13.0
---
## Para que serve

Definir as regras da instituição para todo o sistema.

## Antes de começar

As regras valem para as disciplinas abertas e para os próximos encerramentos. Uma disciplina já encerrada guarda o resultado com as regras do dia.

## Passo a passo

1. **Configurações → Instituição**.

![Configurações da instituição](img/administrador_07_instituicao.png)

2. Ajuste e salve:
  - **O aluno entra no portal com**: RA (padrão), CPF ou e-mail;
  - **Validade do link** de senha: 1 a 720 horas (padrão 48);
  - **Casas decimais** e **média mínima**: desde a 1.13.0 ficam na [regra de média](guia:configurar-a-regra-de-media) (a tela mostra os valores e o link);
  - **Frequência mínima** (padrão 75%). Um curso pode ter a sua própria frequência mínima (no cadastro do curso); vazio = esta. A disciplina encerrada guarda o mínimo que valia no encerramento;
  - **Mostrar média parcial ao aluno** (padrão: Sim);
  - **Média final do aluno (histórico FINAL)**: **aritmética simples** das disciplinas (padrão) ou **ponderada pela carga horária**. Entram as disciplinas encerradas (a tentativa vigente); o TCC fica fora; no PARCIAL, a linha sai em branco. A frase "Como ler" do boletim mostra a frequência mínima do curso do aluno;
  - **Organizar a matriz em módulos** (padrão: **Não**). Com Não, o curso é grade e lista de disciplinas, e módulo não aparece em nenhuma tela nem documento. Com Sim, as disciplinas ficam agrupadas em módulos, e aparecem a tela Módulos, o agrupamento no boletim e no histórico e a coluna "Módulo" no modelo do histórico. Pode trocar nos dois sentidos: ao passar para Sim, as grades que já existem mostram um módulo **Geral** com todas as disciplinas, que você renomeia ou reorganiza; nada se perde nem se duplica;
  - **Textos dos documentos**: as siglas e os rótulos da frequência suficiente e insuficiente (FS/FI), as formas neutras da concordância (para o sexo "Outros": ESTUDANTE, com matrícula regular, o(a)) e o **rótulo de cada situação da matrícula** no documento (Cursando → EM CURSO);
  - **Limite de tentativas por IP**: falhas de login por hora (padrão 50), pedidos de Esqueci a senha por hora (padrão 20) e consultas da verificação a cada 10 minutos (padrão 60). Veja [Tentativas de acesso e limites](guia:tentativas-de-acesso).

## O que pode dar errado

- **"A validade do link deve ser um número de horas entre 1 e 720."**
- **"O rótulo da situação … no documento deve ter de 1 a 60 caracteres."** — nenhum texto dos documentos fica vazio.
- **"Escolha Sim ou Não em «Organizar a matriz em módulos»."** — escolha uma das duas opções.
- **"O limite por IP … deve ser um número inteiro de … a …."** — cada limite tem a sua faixa, escrita embaixo do campo.
- **"A frequência mínima deve ser uma porcentagem de 0 a 100, com até 2 casas."**
- **"A troca do login do portal para … foi RECUSADA."** — a tela lista os alunos ativos sem o campo escolhido (ou com valor repetido); corrija cada um e salve de novo.
