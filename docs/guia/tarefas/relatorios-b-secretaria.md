---
titulo: Relatórios B: secretaria e cadastro
resumo: Alunos por curso e situação, lista de turma (diário), previsão de conclusão e o que falta no cadastro para emitir documentos.
perfis: administrador, secretaria, professor
ordem: 62
relacionados: relatorios-visao-geral, cadastrar-aluno-e-enviar-link, vincular-e-enturmar
entrou: 1.17.0
telas: relatorios/alunos-situacao, relatorios/lista-turma, relatorios/previsao-conclusao, relatorios/qualidade-cadastro
---
## Para que serve

Responder às perguntas do dia a dia da secretaria: quem está matriculado e em que situação, quem está nesta turma, quantos devem concluir em cada mês e que dados faltam para emitir documentos.

## Antes de começar

- Uma linha por **matrícula**: o aluno com dois cursos aparece duas vezes.
- O professor vê só a **Lista de turma** das ofertas dele.

## Passo a passo

1. **Alunos por curso e situação**: filtre por curso, grade, situação, polo, coorte (semestre de ingresso) ou período de ingresso. O nome abre o desempenho do aluno.
2. **Lista de turma**: escolha a oferta. Use **PDF** ou **Imprimir** para o diário de papel (a coluna Assinatura vai em branco).
3. **Previsão de conclusão**: matrículas em Cursando por mês da previsão. A linha "sem previsão" mostra quem ainda não tem a data.
4. **Qualidade do cadastro**: o que falta em cada matrícula (sexo, CPF, nascimento, naturalidade, nacionalidade, RG, filiação, previsão, enturmação) e a contagem por tipo. "Editar" abre a ficha do aluno.

## O que pode dar errado

- O histórico avisa "dados pessoais incompletos": abra Qualidade do cadastro, filtre pelo curso e complete as fichas listadas.
- Um aluno não aparece na Lista de turma: ele não está enturmado na oferta (ou a enturmação está inativa).
