---
titulo: Conhecer com a demonstração
resumo: Explorar o sistema com uma instituição fictícia completa e, depois, trocar pelos dados da sua instituição.
perfis: administrador
ordem: 1
relacionados: primeiros-passos, perfis-de-acesso
telas: demonstracao/comecar, demonstracao/instituicao
entrou: 1.22.0
mudou: 1.102.1
---
## Para que serve

Ver o sistema funcionando antes de cadastrar qualquer coisa: uma instituição fictícia com cursos, disciplinas, professores, alunos, notas, faltas e documentos, e um usuário de cada perfil para experimentar.

## Antes de começar

A demonstração só é montada numa instalação ainda vazia (sem curso nem aluno da instituição). Tudo o que ela cria é fictício; os documentos saem com a tarja "DEMONSTRAÇÃO — documento fictício, sem validade".

## Passo a passo

1. Na última tela da instalação, ou na Página Inicial, escolha **Conhecer com dados de demonstração**.
2. Clique em **Conhecer com dados de demonstração**. Logo depois de instalar, não é preciso confirmar nada; se a instalação já tem algum dado além da sua conta, confirme a sua senha.
   - Uma janela mostra a montagem, passo a passo: preparando a instalação, a instituição e a equipe, os cursos e a matriz, os alunos e as matrículas, as notas e as faltas, a turma de 2025 já concluída e os documentos. Cada parte ganha um ✓ ao terminar, e a barra mostra quanto falta.
   - Se a página for fechada no meio, nada se perde: ao voltar a **Como você quer começar**, a tela mostra onde a montagem parou e o botão **Continuar**.
   - No fim, clique em **Entrar na demonstração**.
3. A demonstração é uma faculdade fictícia, a **Faculdade Horizonte**, com dois cursos de pós-graduação (Gestão de Projetos e Docência no Ensino Superior); enquanto ela está ligada, a sua conta aparece com um nome fictício (**Administração da demonstração**), que volta ao seu quando a demonstração sai. A faixa azul, no alto de toda tela, lembra que os dados são fictícios. Na Página Inicial, o cartão **Conheça o sistema** mostra o usuário e a senha de cada perfil e o que experimentar com cada um. As senhas ficam escondidas: **Mostrar** revela, e **Copiar** copia a senha verdadeira (mesmo escondida) para você colar no campo da senha. Entre com eles numa janela anônima, para não sair da sua conta.
4. Quando quiser começar de verdade, clique em **Começar com os dados da minha instituição** (na faixa, no menu ou no cartão), escreva **COMEÇAR** e confirme. A mesma janela mostra a remoção; os dados fictícios saem, sem reinstalar — também os planos de pagamento, os títulos e os recibos da demonstração —, e o botão do fim abre os [Primeiros passos](guia:primeiros-passos). A Página Inicial volta a perguntar **Como você quer começar?**, e os Primeiros passos levam a essa pergunta.

**No curso livre:** a demonstração monta o **Instituto Ventoalto (fictício)**: o curso **IA na prática para professores**, a matriz com os quatro módulos (10 horas), a turma aberta do ano com 500 vagas e o professor em cada módulo, e dois alunos fictícios na turma, que entram no portal pelo e-mail ou pela matrícula. É o destino das inscrições do site de vendas de exemplo, pela interface de integração.

**Na escola:** a demonstração monta o **Colégio Horizonte (fictício)**: os segmentos Ensino Fundamental (anos finais) e Ensino Médio, com a matriz de exemplo pela Base Nacional Comum Curricular (cada escola ajusta a sua), as turmas do ano (6º ano A e B, 8º ano A, 9º ano A e 1ª série A) com o 1º e o 2º bimestre fechados e o 3º aberto, com as notas (a etapa marcada como atual segue o calendário), e uma turma do ano anterior concluída. No 8º ano A, um aluno entrou no meio do ano, com as notas da escola de origem lançadas (o "T" no boletim); a Matemática das turmas do professor de demonstração está em chamada por aula, com aulas lançadas; o boletim no portal mostra as etapas fechadas, com a média final e a situação final ainda ocultas. Experimente: a turma e as etapas de uma disciplina (a recuperação, o fechamento), a chamada de uma aula (em "O meu dia", pelo professor), as notas de transferência, o conselho de classe da turma do ano anterior, o boletim por etapa no painel e no portal, a troca de turma e o atestado de matrícula, pelo aluno e pelo responsável no portal (os alunos fictícios têm um CPF de demonstração, que começa com 000, e a previsão de conclusão). O **histórico escolar** do Fundamental está pronto para emitir pela tela (pela secretaria, em **Histórico escolar**): os textos e o logo de exemplo, as escolas de origem fictícias, as matrizes de cada ano e a vida escolar de três alunos — o da casa, retido no 9º ano (o histórico só declara a série que ele deve cursar), o concluinte do 9º ano (o certificado; o histórico dele já sai emitido, para a lista de documentos emitidos e a conferência pública pelo código) e o que veio de outra escola no 8º ano A (os anos de antes com a escola de origem e as notas trazidas no bloco da transferência); veja [Emitir o histórico escolar](guia:emitir-historico-escolar). A demonstração também tem um curso livre, **IA na prática para professores**, com uma turma aberta e os quatro módulos como disciplinas: é o destino das inscrições de um site de vendas, pela interface de integração (veja [Integrar outro sistema pela API](guia:integrar-outro-sistema-pela-api)).

## O que pode dar errado

- **"Esta instalação já tem dados da instituição…"** — a demonstração não se mistura a dados reais: ela só é montada numa instalação vazia.
- **"A senha não confere. Nada foi alterado."** — digite a senha da sua conta.
- **"Parou no passo …"**, com o motivo — um passo não fechou (por exemplo, o servidor demorou a responder). O que já foi feito fica; clique em **Tentar de novo**: o passo recomeça sem duplicar nada.
- **"Outro passo da demonstração está rodando agora"** — a montagem está aberta em outra aba, ou o passo de antes ainda termina no servidor. A janela espera e segue sozinha.
- Na remoção, **"a limpeza da demonstração foi recusada…"** ou **"há emissão de histórico que não é da demonstração"** — há algo que não é da demonstração e que seria perdido: um documento emitido de verdade. Resolva e tente de novo.
