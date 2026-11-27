# Mudanças do VCOSTAEDU

O histórico completo de cada versão, com o texto para quem usa, está no próprio sistema: **Ajuda → Novidades** (as
versões e as novidades vêm das migrations, em `database/migrations/`). As decisões de cada mudança estão em
`docs/DECISIONS.md`. Este arquivo resume as versões a partir da 1.103.0.

## 1.103.1

- **O número de cada disciplina nas listas (D-138).** A coluna **#** em Disciplinas e em Minhas Disciplinas, também no
  alto da tela da disciplina, na busca e na exportação; a coluna Código continua.
- **O filtro por ano de Minhas Disciplinas (D-137)** com a regra de Disciplinas: o ano da lista sempre no filtro.
- **O financeiro desligado diz o motivo (D-134)** no endereço direto, e onde ligar, para quem pode.
- **A verificação pública com o vocabulário do tipo (D-139):** "Segmento" na escola.
- **O instalador** não deixa o navegador preencher o usuário e a senha do banco (D-135); **o CPF de demonstração**
  igual nos três tipos (D-136); o README com o financeiro nos três tipos.

## 1.103.0 — a versão do lançamento

- **Financeiro de ligar e desligar nos três tipos (D-129).** Em Configurações → Recursos da instituição e na
  instalação. Ligado de partida na escola e no ensino superior, desligado no curso livre. Desligado, some do menu, das
  telas, dos relatórios, dos cartões e do portal; nada é apagado.
- **Compensação de falta liberável ao professor (D-130).** A ação própria "Lançar compensação de ausência" (112), em
  Perfis de acesso; de fábrica, só a secretaria e o administrador. Ligada, o professor compensa só nas disciplinas dele.
- **Porta do banco no instalador (D-131).** O campo Porta (3306 de partida), a constante `PORTA` nas duas
  `Config.php` e em todas as conexões, nas ferramentas, no backup e na restauração.
- **Duas migrations com o mesmo número param a atualização (D-132).** O executor confere a pasta antes de aplicar
  qualquer coisa e diz quais são.
- **Aviso no histórico do Ensino Médio (D-133).** Sem o registro da conclusão do Ensino Fundamental, a prévia avisa e
  diz onde registrar; não impede a emissão e o documento não muda.
- **Histórico escolar: emitir e cancelar como permissões separadas (D-119).** A ação 131 emite (e a 2ª via); a 133,
  nova, cancela.

## 1.102.1

Os acertos antes de o código ir a público (D-123 a D-128): a higiene dos textos, as Novidades do zero numa instalação
nova, os textos da escola, o professor no Moodle nos três tipos, a instalação no Linux e o nome fictício do
administrador da demonstração.

## 1.102.0

A correção do ensaio no Linux (D-120 a D-122): a ficha financeira fora da escola, o modelo da planilha para o
professor, o registro da instalação, o curso livre com o professor e o aluno no Moodle e os ajustes pequenos.
