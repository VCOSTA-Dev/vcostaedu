# Estado do projeto — VCOSTAEDU

Atualizado em 25/11/2026.

## Onde estamos

A grande onda (modo X, `docs/DECISIONS.md` D-01) roda em 8 etapas, um commit por etapa:

| etapa | o quê | estado |
|---|---|---|
| 1 | a pasta: cópia limpa da origem 1.18.4, higiene, licença, documentos | **feita** |
| 2 | instala como o Moodle: migrations de base, runner, instalador de 7 telas, sessão conferida | **feita** |
| 3 | a camada Tabler do painel e as primeiras telas | **feita** |
| 4 | o piloto (4 telas) | **feita** |
| 5 | Ensino e Educacional | **feita** |
| 6 | o portal do aluno | **feita** |
| 7 | o resto do painel | **feita** |
| 8 | Suporte técnico e fechamento | **feita** |

## A segunda onda (produto aberto)

Roda no ramo `onda2`, num worktree ao lado da pasta principal (D-25); cada etapa provada avança para a principal por `merge --ff-only`.

| etapa | o quê | estado |
|---|---|---|
| 1 | o instalador com acabamento profissional (textos, Copiar, senha, avisos legais e Sobre, recusa de `instalar/`, páginas mortas, mensagens com ×, aviso de atualização pendente) | **feita** (1.19.0) |
| 2 | perfis de acesso do produto aberto (papéis como dado, coordenação por curso, só consulta, ações sensíveis) | **feita** (1.20.0) |
| 3 | auditoria de notas e faltas (só acréscimo, na transação; histórico da nota; relatório) | **feita** (1.21.0) |
| 4 | conhecer com a demonstração, configurar por fases, "Faça comigo" | **feita** (1.22.0) |
| 5 | importação de alunos por planilha | **feita** (1.23.0) |
| 6 | API de integração com Swagger | **feita** (1.24.0) |
| 7 | a tela de atualização | **feita** (1.25.0) |
| 8 | fechamento | **feita** (1.26.0) |

## A terceira onda (segurança, modo estrito, pente fino, interface e marca, avisos)

No ramo `onda3`, no mesmo worktree; cada etapa provada avança para a principal por `merge --ff-only`. A ordem mudou no
replanejamento depois da Etapa 3 (as telas e os textos de dia; a conferência demorada do tema à noite).

| etapa | o quê | estado |
|---|---|---|
| 1 | segurança: o professor só nas ofertas dele, a API recusa HTTP com a exigência de HTTPS, bibliotecas sem uso fora | **feita** (1.27.0) |
| 2 | o modo estrito do banco | **feita** (1.28.0) |
| 3 | o pente fino: saem as telas de programação, a permissão por ação com teto, Perfis de acesso | **feita** (1.29.0) |
| 4 | a demonstração em passos curtos e a tela final do instalador | **feita** (1.30.0) |
| 5 | marca, textos, padrão visual da tela de detalhe e a demonstração genérica | **feita** (1.31.0) |
| 6 | Sobre, Avisos do sistema, aviso de versão nova, Bibliotecas e Verificações de segurança | **feita** (1.32.0) |
| — | parada: o responsável pelo projeto olha as telas; depois dela, a **nova ordem** do que resta | — |
| 7 | ajustes de tela: as marcas do rodapé pela altura das letras, "Depois do passo N" nos Primeiros passos, Dados da instituição numa instalação atualizada, a marca formatada no Sobre | **feita** (1.33.0) |
| 8 | a integração com o Moodle (a conexão, a fila de envios, a entrada pelo portal) | **feita** (1.34.0) |
| 9 | o portal de matrícula de exemplo e o limite por chave da API | **feita** (1.35.0) |
| — | parada do dia | — |
| 10 | o tema claro e escuro | **feita** (1.36.0) |
| 11 | atualizar o sistema em passos | **feita** (1.37.0) |
| 12 | fechamento (a bateria inteira do zero) | **feita** (1.37.0) |

## A quarta onda (a escola: educação básica)

No ramo `onda4`, no mesmo worktree; cada etapa provada avança para a principal por `merge --ff-only`. O desenho e o
levantamento estão em `docs/ESCOLA.md`; a régua do ensino superior é a da 1.37.0 durante a onda inteira (D-46).

| etapa | o quê | estado |
|---|---|---|
| 0 | pendências da onda anterior: o botão "Corrigir papel e serviço" no glossário do Moodle, os nomes de origem fora dos comentários e dos documentos, o texto da marca aprovado | **feita** (no commit da 1) |
| 1 | o tipo de instituição (ensino superior ou escola), os módulos ligados pelo tipo, o vocabulário da escola | **feita** (1.38.0) |
| 2 | a escola: o ano letivo em etapas, as turmas, o aluno na turma, as três portas, o Moodle | **feita** (1.39.0) |
| 3 | a escola: a avaliação (a média por etapa, a recuperação, a média anual, o conselho, o boletim por etapa) | **feita** (1.40.0) |
| 4 | a demonstração de escola (o Colégio Horizonte, pela BNCC) | **feita** (1.41.0) |
| 5 | o motor de médias: o acréscimo e as regras prontas | **feita** (1.42.0) |
| 6 | fechamento (a bateria inteira do zero, nos dois tipos) | **feita** |

## A oitava onda (a entrada, o e-mail da escola e o material regravado)

No ramo `onda8`, no mesmo worktree; cada etapa provada avança para a principal por `merge --ff-only`. A régua do
ensino superior IDÊNTICA em todas as baterias.

| etapa | o quê | estado |
|---|---|---|
| 0 | a lista do material a regravar (do retorno da Onda 7) como pendências do quadro do material | **feita** |
| 1 | a entrada pelo campo de login (a matrícula do aluno; o CPF ou o e-mail do responsável; logins únicos) | **feita** (1.57.0; D-67) |
| 2 | o e-mail do aluno no envio ao Moodle, em três modos (o da escola nunca no cadastro; a conta que já existia não muda) | **feita** (1.58.0; D-68) |
| 3 | o atestado na demonstração da escola (o CPF fictício e a previsão de conclusão) | **feita** (1.59.0; D-69) |
| 3b | do site da instituição ao Moodle numa requisição só (a operação única; o reenvio sozinho; a página de venda de exemplo) | **feita** (1.60.0; D-70) |
| C1.1 | correção: o horário de aulas completo (as aulas do dia editáveis, o horário da turma na semana, a semana no portal, a demonstração com horário) | **feita** (1.61.0; D-71) |
| C1.2 | correção: o caminho do professor até as etapas (a aba Etapas do componente, a ação em Minhas disciplinas) | **feita** (1.62.0; D-72) |
| C1.3 | correção: o cartão das senhas da demonstração (todas escondidas, com Mostrar e Copiar) | **feita** (1.63.0; D-73) |
| C2.1 | correção: a nota com Enter (grava e vai ao próximo aluno; a recuperação também) | **feita** (1.64.0; D-74) |
| C2.2 | correção: a chamada a cada clique, e o encontro que começa com todos presentes ou todos ausentes (lembrado) | **feita** (1.65.0; D-75) |
| C3.1 | correção: a página sem acesso (403, com o caminho de volta; a sessão continua) | **feita** (1.66.0; D-76) |
| C3.2 | correção: o painel acadêmico da escola (o resultado anual nos relatórios; os relatórios abrindo de uma vez) | **feita** (1.67.0; D-77) |
| C3.3 | correção: as faltas na demonstração do colégio (0 a 2 por bimestre, ninguém reprovado por falta) | **feita** (1.68.0; D-78) |
| CA-C | correção, acréscimos: A (os relatórios na escola: o vocabulário, o atestado da montagem, a tabela dos 24), B (as atividades na tela da etapa) e C (os boletins da turma num PDF só) | **feita** (1.69.0 a 1.71.0; D-79 a D-81) |
| C4 | correção, Parte 4: o dia a dia do professor (O meu dia), o portal do responsável (o Resumo de verdade, a turma nele, o horário em cartões com filtro), um professor por disciplina na demonstração e, na escola, "disciplina" (sem "componente curricular") | **feita** (1.72.0; D-82 a D-85) |
| C5 | correção, fechamento: o horário padrão só na escola; a régua com as decisões registradas (a grafia "Vespertino"); a nota com Enter conferida no banco e na tela | **feita** (1.73.0; D-86) |
| C6 | correção, segunda leva, 2.1: na escola, a secretaria emite o atestado de matrícula pelo painel (lista de alunos e aba Documentos); nenhum botão leva à página sem acesso | **feita** (1.74.0; D-87) |
| C7 | correção, segunda leva, 2.2: a ficha do aluno na escola (o segmento, a série e a turma do ano; sem polo, modalidade, forma de ingresso, grade e TCC) | **feita** (1.75.0; D-88) |
| C8 | correção, segunda leva, 2.3: a coordenadora da demonstração indicada nos anos finais; a turma de outro segmento dá a recusa do recorte | **feita** (1.76.0; D-89) |
| C9 | correção, segunda leva, 2.4: a Página Inicial de cada perfil na escola (os atalhos da direção, da secretaria e da supervisão, da coordenação) | **feita** (1.77.0; D-90) |
| C10 | correção, segunda leva, 2.5: o vocabulário que sobrou na escola (os filtros dos relatórios, a lista de disciplinas, a aba do portal) | **feita** (1.78.0; D-91) |
| C11 | correção, segunda leva, 2.6: a inscrição que não duplica (o mesmo e-mail na mesma turma é a mesma inscrição) | **feita** (1.79.0; D-92) |
| C12 | correção, segunda leva, 2.7: o limite de chamadas por minuto da API em Parâmetros, com quando aumentar | **feita** (1.80.0; D-93) |
| C13 | correção, segunda leva, 2.8: "disciplina" nos guias e na documentação da API, sem mudar os nomes dos campos e das rotas | **feita** (1.81.0; D-94) |
| C14 | correção, segunda leva, 2.9: renomear a disciplina vale também nas ofertas que já existem (o portal mostra o nome novo) | **feita** (1.82.0; D-95) |
| C15 | correção, segunda leva, 2.10: o exemplo de configuração do Nginx, o guia curto e as Verificações de segurança acusando a pasta protegida que responde | **feita** (1.83.0; D-96) |
| C16 | correção, segunda leva, 2.11: a pergunta de cada relatório no topo do relatório aberto, como o guia diz | **feita** (1.84.0; D-97) |
| F1 | financeiro, Parte 1: a base — as regras, os planos, os títulos e a ficha financeira (aberta da ficha do aluno; o plano ao matricular) | **feita** (1.85.0; D-98) |
| F2 | financeiro, Parte 2: Receber e Desfazer recebimento (os encargos na data, o servidor recalcula ao gravar) | **feita** (1.86.0; D-98) |
| F3 | financeiro, Parte 3: a ficha financeira no portal do aluno e no do responsável financeiro, só de leitura, e o cartão | **feita** (1.87.0; D-98) |
| F4 | financeiro, Parte 4: Baixar, Cancelar, Desfazer baixa e Desfazer cancelamento, com o motivo | **feita** (1.88.0; D-98) |
| F5 | financeiro, Parte 5: a ficha do caixa (R-FIN-01) e os recebimentos por período (R-FIN-02); o atalho do caixa na Página Inicial da escola | **feita** (1.89.0; D-98) |
| F6 | financeiro, Parte 6: Renegociar e Desfazer renegociação | **feita** (1.90.0; D-98) |
| F7 | financeiro, Parte 7: o histórico das ações e o atendimento | **feita** (1.91.0; D-98) |
| F8 | financeiro, Parte 8: o financeiro na demonstração do colégio (até três atendimentos) | **feita** (1.92.0; D-98) |
| R1 | financeiro refeito, Parte 1: a ficha financeira no desenho do sistema de origem, Receber (um ou vários títulos), o recibo e o Desfazer recebimento | **feita** (1.93.0; D-99) |
| R2 | financeiro refeito, Parte 2: Renegociar na tela própria — a data e os quadros, as condições parcelada (com entrada) e à vista, a proposta salva e efetivada depois, o acordo com número próprio e as parcelas sem multa, os três impressos em PDF, a aba Renegociações e o desfazer com o motivo da lista | **feita** (1.99.0; D-105) |
| E1 | correção da escola (o teste pelo navegador de 22/11), Parte 1: a etapa (o professor fecha, só a supervisão, a direção e o administrador reabrem, a trava visível, o Gravar único), a Liberação do boletim por ano letivo (etapas, média final, situação final) com o portal obedecendo, a recuperação nas Notas do portal, o relatório de reaberturas com a etapa, o conselho com a correção e a retirada; a janela de confirmação do sistema em tudo | **feita** (1.94.0; D-100) |
| E2 | correção da escola, Parte 2: o Enter que desce na hora e grava em segundo plano, a nota fora da escala que segura o cursor, a chamada sem beco sem saída (a troca num passo; "Faltas" no atalho), a trava da troca com a falta por etapa, o aviso do fim de semana, a janela de andamento que termina | **feita** (1.95.0; D-101) |
| E3 | correção da escola, Parte 3: o número proposto do aluno, a etapa sem nota antes da matrícula e as notas de transferência, a parcela a partir da qual cobrar, a turma na matrícula e a lista com a série e a turma, o atestado da escola, os candidatos da série e do ano, o endereço na planilha | **feita** (1.96.0; D-102) |
| E4 | correção da escola, Parte 4: "RM" na escola por um rótulo só, as palavras do ensino superior fora das telas da escola (a aula na chamada), sem "(s)" e sem texto de sistema, as inconsistências, os Parâmetros que mostram o gravado e não mudam o que não foi mexido, a demonstração removida com os planos e a volta ao "Como você quer começar?", o 403 do portal da família | **feita** (1.97.0; D-103) |
| E5 | correção da escola, Parte 5: a demonstração da escola com o 8º ano, o responsável com o sobrenome do filho, a Matemática do professor em chamada, o aluno que entrou no meio do ano com as notas de transferência, a liberação do boletim e o guia com as etapas da demonstração | **feita** (1.98.0; D-104) |
| L1 | fechamento, Parte 1: o curso livre, o terceiro tipo de instituição na instalação (o financeiro e a matrícula de ligar e desligar, o portal com a Página Inicial, o Moodle e a Ajuda, a demonstração do Instituto Ventoalto) e a entrada do aluno por tipo (o e-mail ou a matrícula no superior e no curso livre; o RM na escola) | **feita** (1.100.0; D-106, D-107) |
| L2 | fechamento, Parte 2: o repositório do produto (um commit por versão, sem o histórico de trabalho: `repositorio_atualizar.php`, docs/REPOSITORIO.md), a pasta limpa sem as notas de trabalho e a higiene da exportação | **feita** (1.100.0; D-108) |
| H1 | o histórico escolar da educação básica: os cadastros e os textos, a matriz curricular por ano, a vida escolar, os modelos do Fundamental e do Médio, a prévia, a emissão, a 2ª via e o cancelamento, a demonstração com o histórico, a saída no meio do ano e o ano letivo fechado (docs/HISTORICO_ESCOLAR.md) | **feita** (1.101.0; D-110 a D-117) |
| H2 | o histórico escolar, o que ficou: a chamada da turma para as disciplinas cobertas (D-118, reservada) e emitir e cancelar como ações próprias (D-119, reservada) | a fazer |
| 4 | o material regravado | a fazer |
| 5 | o fechamento | a fazer |

## A sétima onda (responsáveis e portal da família)

No ramo `onda7`, no mesmo worktree; cada etapa provada avança para a principal por `merge --ff-only`. O cadastro dos
responsáveis, a designação por aluno e por ano (o financeiro, o titular, o acesso acadêmico adicional), o convite, o
portal do responsável e a barreira de acesso, só na escola; o ensino superior não muda (a régua dele IDÊNTICA).

| etapa | o quê | estado |
|---|---|---|
| 1 | os acertos da demonstração da escola (a recuperação, o professor, o nome do curso sem a grade, a turma completa) | **feita** (1.51.0; D-61) |
| 2 | o cadastro do responsável e a designação por ano | **feita** (1.52.0; D-62) |
| 3 | o convite e o primeiro acesso | **feita** (1.53.0; D-63) |
| 4 | o portal do responsável | **feita** (1.54.0; D-64) |
| 5 | a barreira de acesso | **feita** (1.55.0; D-65) |
| 6 | a demonstração, os primeiros passos e a ajuda | **feita** (1.56.0; D-66) |
| 7 | o fechamento (a bateria inteira nos dois tipos, a atualização da 1.50.0 pela tela, a pasta limpa, os documentos) | **feita** |

O desenho, as regras e a barreira estão em `docs/RESPONSAVEIS.md`; o resumo, em `docs/ESCOLA.md`, seção 6. **A Onda 7 está fechada** (1.56.0).

## A sexta onda (zerar as pendências das ondas 3, 4 e 5)

No ramo `onda6`, no mesmo worktree; cada etapa provada (a bateria reduzida e a régua dos dois tipos) avança para a
principal por `merge --ff-only`. Nenhum módulo novo: cada pendência dos retornos das ondas 3, 4 e 5 é feita, decidida ou
fica fora com o motivo (o quadro no `retorno.md`).

| etapa | o quê | estado |
|---|---|---|
| 1 | uma conexão com o banco por requisição (D-51) | **feita** (1.43.0) |
| 2 | o tempo da demonstração da escola (D-52) | **feita** (1.44.0) |
| 3 | o banco e a base (ONLY_FULL_GROUP_BY, a etapa só de consulta, os nomes de origem, a constante de guarda `VCOSTAEDU`) | **feita** (1.45.0; D-53) |
| 4 | as outras operações longas, em passos (a importação de alunos, os relatórios, a enturmação com o Moodle; o desfazer e o encerramento por conjunto) | **feita** (1.46.0; D-54) |
| 5 | a escola: a chamada por aula, os relatórios, o e-mail no Moodle, as séries livres | **feita** (1.47.0; D-55 a D-57) |
| 6 | segurança | **feita** (1.48.0; D-58) |
| 7 | o registro de instalação e a distribuição | **feita** (1.49.0; D-59) |
| 8 | a preparação para abrir o código | **feita** (1.50.0; D-60) |
| 9 | o material | **fora desta onda** (a pedido do responsável, 17/11/2026: será feita uma vez, com os módulos novos que mudam as telas) |
| 10 | o fechamento (a bateria inteira do zero nos dois tipos, a 1.42.0 atualizada pela tela nos dois tipos, o Moodle de teste, a varredura do repositório inteiro) | **feita** |

## A quinta onda (o material: vídeos, prints e "Faça comigo")

No ramo `onda5`, no mesmo worktree; cada bloco provado avança para a principal por `merge --ff-only`. A onda só produz
material: o sistema não muda (só textos de tela errados, pequenos e evidentes, achados na gravação). O quadro do que
existe e do que falta, guia por guia, é `docs/MATERIAL.md`: 64 dos 70 guias com vídeo atual (os outros 6 estão no
percurso da palestra ou não pedem vídeo), os 36 guias com print com o print atual, 15 com "Faça comigo".

| bloco | o quê | estado |
|---|---|---|
| 1 | o percurso da palestra (um vídeo contínuo e um por capítulo, no ensino superior e na escola) e o gravador no padrão do "Faça comigo" | **feito** |
| 2 | os vídeos do que é novo, no geral | **feito** |
| 3 | os vídeos da escola | **feito** |
| 4 | regravar o que ficou velho: os 51 vídeos da 1.18, a palestra (as senhas da demonstração mascaradas) e os 64 prints dos guias | **feito** |
| 5 | "Faça comigo" nos guias que não tinham (quatro roteiros novos, com a prova) | **feito** |
| 6 | os documentos (o quadro do material, a Ajuda e os PDFs regerados, o retorno) e as provas do fechamento | **feito** |

## O que existe

- O código acadêmico da origem (`docs/ORIGEM.md`): painel, portal, `core/`, ferramentas, testes e guias.
- **Interface:** Tabler 1.4 / Bootstrap 5 em tudo (a convivência das Etapas 3 a 7, D-23, terminou). Em Tabler: a entrada da raiz, o login e as telas de senha do painel, a Página Inicial, Usuários, Perfil o piloto e as áreas Ensino e Educacional inteiras (anos letivos, matérias, cursos, matriz, módulos, disciplinas e oferta, atividades e notas, médias, chamada, regra da oferta, salas e horário, alunos, vínculo, boletim, emissões, enturmação). E o **portal do aluno inteiro** (login com o plano B do iframe, senhas, Página Inicial, horário, boletim, notas das atividades, atestado, progresso, perfil, ajuda, guias, vídeos, novidades). E o **resto do painel**: Configurações (e-mail, parâmetros, regras de média com o simulador, polos, dados da instituição, atos legais, modelos de histórico, tentativas de acesso), Equipe (usuários e, desde a 1.29.0, Perfis de acesso, só leitura), Relatórios (o hub, os 22 relatórios com cartões, gráfico e exportação, os parâmetros), a prévia do histórico, Ajuda, Novidades e a verificação pública. As telas de programação (Páginas, Menu, Acesso acadêmico, Páginas acadêmicas, Níveis de acesso) saíram na 1.29.0 (`docs/PENTE_FINO.md`). **A interface é toda Tabler:** o layout AdminLTE, o Bootstrap 4 e as integrações bs4 saíram (Etapa 8).
- **Layout Tabler:** `painel/app/adms/Views/include_tabler/` e `portal/app/adms/Views/include_tabler/` (head, rail, libraries_js, _page_header, login); CSS `assets/css/vcostaedu-tabler.css`; JS `assets/vcostaedu/vcostaedu-tabler.js`.
- **De-para:** `database/ferramentas/tabler/converter_views.php` e `converter_mensagens.php` (D-24); como foi aplicado: `docs/DEPARA_APLICADO.md`.
- **Instalação pelo navegador** (`instalar/`, D-21): 7 telas em Tabler com a marca; grava as duas configurações a partir dos moldes, cria as pastas de execução, confere a exposição por HTTP, aplica as migrations, cria o primeiro Administrador e roda a conferência do servidor (`verificar_ambiente.php`). A raiz, o painel e o portal sem configuração levam ao instalador.
- **Migrations** (`database/migrations/`, D-19): `0001_estrutura` (88 tabelas), `0002_conteudo`, `0003_gatilhos` (exige TRIGGER; sem ele fica adiada). `php bin/migrar.php status | simular | aplicar`.
- **Sessão** (D-22): identificador novo no login (painel e portal); situação e nível relidos a cada requisição no painel.
- **Onda 2, Etapa 1:** a regra única da senha (`core/Senha.php`, D-26) e o componente do navegador (`assets/js/vcostaedu-componentes.js`, também o Copiar dos blocos de comando); os avisos legais (`core/Legal.php`, D-27) no instalador, nas telas de acesso, no painel e no portal, e a página `sobre.php`; `instalar/` recusa todo endereço depois de instalado (`instalar/.htaccess`); o aviso de "atualização pendente" (`AdmsAtualizacaoPendente`, D-28); a migration `0004` (páginas mortas fora, versão 1.19.0).
- **Onda 2, Etapa 2:** os perfis do produto aberto (`docs/NIVEIS_PRODUTO_ABERTO.md`, D-29, D-30): migration `0005` (papéis e marcas; os níveis do fornecedor e o 2 saíram), `AdmsNivel` (o papel), `AdmsEscopo` (o recorte do coordenador), as travas da rota e do botão (`AdmsPaginas::nivelPode`).
- **Onda 2, Etapa 3:** a auditoria de notas e faltas (`docs/AUDITORIA.md`, D-31): migrations `0006` (a tabela, as 2 páginas) e `0007` (os gatilhos), `AdmsAuditoria` nos 7 pontos que gravam, o "Histórico desta nota" e o relatório (`Controllers/Auditoria.php`).
- **Onda 2, Etapa 4:** conhecer primeiro, configurar por fases (`docs/FACA_COMIGO.md`, D-32): migration `0008` (`adms_instalacao_estado`, `adms_fases`, `adms_fases_estado`, as 8 páginas); a demonstração montada e removida pela tela (`Controllers/Demonstracao.php` + `database/ferramentas/demonstracao.php`, a marca `demonstracao.ativa` no banco, a faixa nos dois apps, o cartão "Conheça o sistema"); os Primeiros passos (`AdmsPrimeirosPassos`, a fase concluída medida pela condição SQL); o "Faça comigo" (`assets/js/vcostaedu-faca-comigo.js`, os roteiros em `painel/app/adms/Roteiros/`), também no guia; a prova da jornada do zero `tests/tabler/onda2/jornada4.js`.
- **Onda 2, Etapa 5:** a importação de alunos por planilha (D-33): migration `0009` (`adms_importacoes`, `adms_importacoes_linhas`, as 8 páginas, o menu Educacional → Importar alunos); `Models/importacao/` (o leitor do CSV e o lote: o de-para das colunas com memória, o de-para dos valores, o julgamento linha a linha, a correção na tela, aplicar pelas funções da tela numa transação, desfazer como ação sensível); a prova `tests/tabler/onda2/etapa5.js` com o arquivo fictício de `onda2/planilha_alunos.js`.
- **Onda 2, Etapa 6:** a API de integração (D-34): `api/index.php` + `api/.htaccess` → `Models/api/AdmsApiServidor.php` (as 20 rotas, a chave, o limite, a Idempotency-Key, o registro, o erro num formato só) e `AdmsApiRecursos.php` (as operações, pelas funções das telas); `AdmsApiChaves.php` (só o hash); migration `0010` (as 3 tabelas, o parâmetro do limite, o menu Integrações, 6 páginas); `Controllers/Integracoes.php` (chaves, documentação com o Swagger UI local em `assets/plugins/swagger-ui`, chamadas, especificação); `docs/api/openapi.json` (gerada a partir de uma lista de esquemas; a prova `tests/tabler/onda2/etapa6.js` confere que ela e o servidor têm as mesmas rotas).
- **Onda 2, Etapa 7:** a tela de atualização e a tela Versão (D-35): `Models/helper/AdmsAtualizacao.php` (a política das duas portas: o estado, o "cabe no tempo da tela", aplicar e a falha com o que passou), `Controllers/Atualizar.php` e `Versao.php`, `bin/migrar.php aplicar` pela mesma classe, a dependência recursiva em `AdmsPaginas::telaDeAtualizacao`, o aviso de atualização pendente levando à tela; migration `0011`; a prova `tests/tabler/onda2/etapa7.js` sobre o banco da versão anterior (`onda2/banco_antigo.php`).
- **Onda 2, Etapa 8:** o fechamento: a versão 1.26.0 (migration `0012`, só dado); a prova final do zero `tests/tabler/onda2/jornada8.js` (a jornada da Etapa 4, a planilha, a API e a atualização, em sequência); a galeria `provas/tabler/index.html` com a segunda onda (`tests/tabler/galeria.js`). **A segunda onda está completa.**
- **Onda 3, Etapa 1:** segurança: o professor só alcança as ofertas a que está ligado, na leitura e na gravação (`AdmsEscopo::podeOferta($oferta, $gravar)`, a marca "grava" da página levada pelo roteador; migration `0013` com o escopo das rotas de nota, falta, chamada e importação); a API recusa HTTP com `EXIGIR_HTTPS`; as bibliotecas PHP sem uso saíram de `painel/vendor`; a versão de cada biblioteca de interface lida do arquivo dela (`core/creditos.php`); a prova `tests/tabler/onda3/etapa1.js`.
- **Onda 3, Etapa 2:** o modo estrito do banco (D-36): `core/Banco.php` (`VcBanco`) fixa o modo em toda conexão e traduz a recusa em motivo; o runner aplica sem o modo só as duas migrations antigas que gravam texto maior que a coluna; migration `0014` (a coluna alargada, os resumos inteiros, o usuário legado dos alunos reparado); as correções que o modo revelou (o perfil do portal, o perfil do painel, o registro da API); ONLY_FULL_GROUP_BY medido e fora; do complemento, o boletim do professor pelo escopo e as disciplinas do curso coordenado como "só consulta"; a prova `tests/tabler/onda3/etapa2.js`.
- **Onda 3, Etapa 3:** o pente fino (D-37, `docs/PENTE_FINO.md`: toda página como USO, CONFIGURAÇÃO ou PROGRAMAÇÃO): saem as telas de programação e as sobras do fornecedor; migration `0015` (`adms_acoes` — 91 ações com nome, grupo e teto —, `adms_paginas.acao_id`, a página Perfis de acesso, a saída das páginas de programação, os nomes do menu); o teto no motor e no menu (`AdmsPaginas::nivelPode`, `AdmsNivel::abaixoDoTeto`) e na conferência (`core/Teto.php`, no instalador e na verificação do ambiente); a tela `Controllers/PerfisAcesso.php` (a matriz por ação, só leitura); a varredura do jargão na tela (`tests/tabler/lib.js`, `jargao()`, em toda prova que chama `semErros`); "Ensino" no sentido de curso virou curso; o grupo "Dashboard" virou "Início"; um caminho de volta só nas telas de cadastro; a documentação da API por assunto; os assets herdados renomeados (`assets/vcostaedu/`); a prova `tests/tabler/onda3/etapa3.js`.
- **Onda 3, Etapa 4:** a demonstração em passos curtos (D-38): `database/ferramentas/demonstracao_passos.php` (o cenário e os 22 passos; a limpeza do `seed_demo.sql` pelo PDO), `Models/demonstracao/AdmsDemonstracaoMontagem.php` (o estado no banco, a trava, a retomada), a rota `demonstracao/montar` em JSON, a janela de progresso (`include_tabler/_progresso.php`, `assets/js/vcostaedu-progresso.js`); a linha de comando roda os mesmos passos (`seed_demo_processar.php`); a verificação do servidor com `--tela` (português de uso, endereço local, escrita testada, vídeos), o resumo no topo da tela final do instalador; migration `0016` (1.30.0); a prova `tests/tabler/onda3/etapa4.js`.
- **Onda 3, Etapa 5:** a marca e os textos (D-39): o topo do menu numa linha (o selo do VCOSTAEDU e, ao lado, o nome ou o logo da instituição — `AdmsInstituicao::topoMenu`), o ícone da aba, o rodapé com as marcas VCOSTAEDU e VCOSTA em imagem (`assets/marca/vcosta-wordmark*.png`), o × em toda mensagem (`core/Mensagem.php`, nos dois `ConfigView`), uma faixa só no topo, o polo Sede; o padrão da tela de detalhe (`AdmsUi::barraDisciplina` em abas, o voltar discreto no CSS, os atalhos em cartões, rótulo sem valor não aparece, o contraste); a demonstração genérica (a Faculdade Horizonte, com os mesmos números — `tests/tabler/onda3/so_texto.php`) e o administrador com nome fictício; as ações com nome próprio e sem o limite no nome; migration `0017` (1.31.0); a prova `tests/tabler/onda3/etapa5.js`.
- **Onda 3, Etapa 6:** o que a instalação mostra de si (D-40): o Sobre sem a versão e sem as bibliotecas para quem não entrou (`sobre.php` lê a sessão do painel só para ler), com a marca (TRADEMARK); em Manutenção, só para o Administrador, Avisos do sistema (`Controllers/AvisosSistema.php`: as versões, as atualizações por aplicar, a versão nova, o resumo da segurança, o copyright, os links das constantes de `core/Legal.php`), Verificações de segurança (`core/Verificacoes.php`, a mesma da linha de comando `database/ferramentas/implantacao/verificar_seguranca.php`) e Bibliotecas (`VcLegal::bibliotecas`, a mesma lista do Sobre); o aviso de versão nova (`core/VersaoNova.php`: uma vez por dia, 3 s, sem identificação, até 16 KB, nunca baixa, o parâmetro `sistema.verificar_versao_nova`); migration `0018` (1.32.0); a prova `tests/tabler/onda3/etapa6.js` (com o endereço falso `onda3/versao_falsa.php` e `onda3/versao_nova.php`).
- **Onda 3, Etapa 7:** os ajustes de tela: as marcas do rodapé com as letras da altura de maiúscula da fonte (`assets/marca/vcostaedu-rodape*.svg`, `VcLegal::marcaVcostaedu`, a unidade "cap" no CSS; a medida em `tests/tabler/onda3/medir_rodape.js`), sem o nome escrito ao lado; "Depois do passo N" e os botões travados nos Primeiros passos (`AdmsPrimeirosPassos` dá o número dos passos); a marca formatada no Sobre (`core/Markdown.php` sobre o `TRADEMARK.md`, com o texto novo); a prova da instalação atualizada da 1.28.0 com e sem a demonstração antiga (`tests/tabler/onda3/instalacao_antiga.php`); migration `0019` (1.33.0).
- **Onda 3, Etapa 8:** a integração com o Moodle (D-41; `docs/INTEGRACAO_MOODLE.md`, com o levantamento): o cliente `core/Moodle.php` (curl, tempo curto, certificado, sem redirecionamento; a cifra AES-256-GCM da chave; o glossário); `Models/moodle/` (a conexão e a fila `AdmsMoodle`, o envio de cada item `AdmsMoodleEnvio`, o lote em passos `AdmsMoodleLote`); as telas Conectar ao Moodle e Envios ao Moodle (Integrações); os ganchos nos pontos de gravação (o aluno, a enturmação, a situação da matrícula, o professor), no fim da requisição; o "Id no Moodle" vira o estado real (e a demonstração não põe mais número fictício); a entrada pelo portal (`portal/.../Moodle.php`, o link de uso único em redirecionamento); a saúde em Avisos do sistema; o comando `database/ferramentas/moodle_enviar.php`; migration `0020` (1.34.0); a prova `tests/tabler/onda3/etapa8.js` (contra o Moodle de teste, ou o servidor falso `moodle_falso.php` com VCOSTAEDU_MOODLE_FALSO=1).
- **Onda 3, Etapa 9:** o limite por chave da API (D-42): `AdmsApiChaves::ASSUNTOS` e `pode()` (alunos, matrículas, enturmação, cadastros de apoio; consultar ou gravar), a coluna `permissoes`, o assunto em cada rota de `AdmsApiServidor::ROTAS`, a recusa 403 com o limite, a tela das chaves e a documentação (`x-assunto`); `GET /alunos/{ra}` com o campo `moodle`; o portal de matrícula de exemplo em `exemplos/portal-matricula/` (fora do produto e do pacote); migration `0021` (1.35.0); a prova `tests/tabler/onda3/etapa9.js`.
- **Onda 3, Etapa 10:** o tema claro e escuro (D-43): `core/Tema.php` (o script no `<head>`, a escolha no menu do usuário), `AdmsTema`/`AdmsTemaPortal` e os controllers `Tema` (gravar a escolha), a coluna `tema` nas duas contas, o cookie `vc_tema` para as telas de acesso; as cores medidas nos dois temas (`vcostable-tabler.css`); a varredura do contraste `tests/tabler/onda3/varrer_contraste.js` (171 telas, dois temas); migration `0022` (1.36.0); a prova `tests/tabler/onda3/etapa10.js`.
- **Onda 3, o ajuste do Moodle (1.36.1):** o plugin com 17 funções — a busca por e-mail por `local_vcostaedu_find_users_by_email` (em grupos de até 100 no lote; uma conta liga; mais de uma vira a escolha do operador em Envios ao Moodle, coluna `contas`, página 506; a suspensa liga com aviso `conta_suspensa`), a renomeação da disciplina criada pelo VCOSTAEDU (`AdmsMoodle::anotarNome`, `AdmsMoodleEnvio::atualizarNome`); migration `0023`.
- **Onda 3, Etapa 11:** atualizar o sistema em passos (D-44): `AdmsMigrations::aplicarProxima` (uma etapa por chamada), o lote em `AdmsAtualizacao` (a tabela `adms_atualizacao_lote`, criada e apagada pela tela; a retomada; a trava entre as chamadas: o dono vivo há menos de 90 s), a tela com a janela de progresso; o comando segue em sequência e recusa durante a tela; `docs/OPERACOES_LONGAS.md`; migration `0024` (1.37.0); a prova `tests/tabler/onda3/etapa11.js`.
- **Onda 3, Etapa 12:** o fechamento — a bateria inteira do zero na 1.37.0; as 6 provas que tinham ficado para trás de etapas posteriores, atualizadas (o sistema não mudou) (DUVIDAS 239). **A Onda 3 está fechada.**
- **Onda 4, Etapa 0:** o glossário do Moodle indica o botão "Corrigir papel e serviço" do plugin; os nomes das instituições e dos projetos de origem saíram dos comentários e dos documentos (ficaram só nas migrations 0001 e 0002, que não podem mudar — DUVIDAS 240).
- **Onda 4, Etapa 1:** o tipo de instituição (D-45): `core/Vocabulario.php` (os dois tipos, a frase de cada um, o vocabulário da escola: `traduzir` no menu e no título das telas), `AdmsTipoInstituicao` (o tipo em `adms_instalacao_estado`, os módulos de `adms_paginas_modulo`, a troca só sem dado próprio), o filtro do módulo no roteador (`AdmsPaginas`), no menu (`AdmsMenus`) e nos relatórios; a tela do tipo no instalador (depois da conta); o cartão do tipo em "Começar com os dados da minha instituição"; o tipo em Avisos do sistema, no Sobre e na verificação do ambiente; a Estrutura da escola (`Controllers/Escola.php`); na escola, a demonstração ainda é recusada; a régua com `--config` (D-46); migration `0025` (1.38.0); a prova `tests/tabler/onda4/etapa1.js`.
- **Onda 4, Etapa 2:** a estrutura da escola (D-47): o ano letivo em etapas (`esc_anos`, `esc_etapas`: bimestres ou trimestres, as datas, o conselho; com lançamento, o modelo não muda — `Controllers/EscolaAnos.php`); as turmas (`esc_turmas`, `esc_turma_ofertas`: a turma gera as ofertas da série por `AdmsGerarOferta`, com a turma "<série> <nome>"; renomear leva o nome ao Moodle — `Controllers/Turmas.php`, `Models/escola/AdmsTurmas.php`); o aluno na turma (`esc_turma_alunos`: entrar em todas as ofertas pela confirmação de sempre, sair, trocar levando o lançado em `esc_lancamentos_trazidos`; em lote, em passos — `AdmsTurmaAlunos`, `AdmsTurmaLote`); as três portas (a tela; a coluna "Turma" na planilha, só na escola; `GET /turmas`, `GET|POST /turmas/{id}/alunos`, só na escola); as regras da escola (`esc_configuracao`, o padrão no código; a frequência); os Primeiros passos da escola (as fases do módulo `escola`; as do superior com o módulo `superior`); na escola, os módulos da matriz valem sempre; o guia e a novidade de um tipo só; migration `0026` (1.39.0); a prova `tests/tabler/onda4/etapa2.js` (com o Moodle de teste).
- **Onda 4, Etapa 3:** a avaliação da escola (D-48): `core/MediaEscola.php` (pura: o motor uma vez por etapa, a recuperação com as três regras, a média anual simples ou com pesos, a frequência no ano ou no componente, a situação, o conselho); a fachada `AdmsCalculoNotas` (painel e portal, iguais) entrega a oferta de uma turma por ela; as etapas do componente (`Controllers/EscolaNotas.php`, `Models/escola/AdmsEtapaNotas.php`: a recuperação, as faltas da etapa, fechar e reabrir), o conselho de classe (`EscolaConselho`, `AdmsConselho`), as travas da etapa fechada (a nota, a atividade, a falta, a recuperação), o ano que só fecha com as etapas fechadas, a auditoria com a etapa, o boletim por etapa (`AdmsBoletimCurso::tabelaEscola`, na tela, no PDF e no portal, com a liberação por etapa), as Regras da escola completas; migration `0027` (1.40.0); as provas `tests/tabler/onda4/media_escola.php` e `etapa3.js`.
- **Onda 4, Etapa 4:** a demonstração da escola (D-49): "Colégio Horizonte (fictício)", pela BNCC e pela LDB (as fontes em `docs/ESCOLA.md`, seção 3), num cenário da mesma montagem (`VcDemo::cenario`; `database/ferramentas/demonstracao_escola.php`): a estrutura do EF anos finais e do EM, dois anos letivos, cinco turmas, a enturmação, as notas por etapa, o fechamento, a troca de turma, o conselho e o boletim, em passos curtos pela tela ou pela linha de comando, e o Remover de sempre; na escola, a oferta gerada pela turma leva nos encontros as horas do componente; a régua da escola (`regua-escola-onda4-etapa4.json`); duas correções achadas na montagem: a frequência no total de horas do ano (a comparação do RA entre tabelas de collation diferente — DUVIDAS 265) e a conexão com o banco que não abria por falta de porta local, tentada de novo em `VcBanco::abrir` (DUVIDAS 270); a régua ignora a hora de entrada e saída da turma; migration `0028` (1.41.0); a prova `tests/tabler/onda4/etapa4.js` (a tela com um passo forçado a falhar, a linha de comando do produto com a régua idêntica, a sabotagem).
- **Onda 4, Etapa 5:** o motor de médias (D-50): o acréscimo em `core/MotorMedia.php` (a atividade do tipo Acréscimo fora dos componentes; a soma, até o limite da regra, depois da composição e antes do arredondamento, com teto 10,00; a linha na memória; na escola, por etapa), a opção por versão da regra (`rm_media_regra_acrescimo`; "Permitir acréscimo" e o limite na tela da regra, o campo no simulador), o tipo Acréscimo só onde a regra permite (a lista, o cadastro e o envio direto); as regras prontas no catálogo ("Só atividades"; "Prova mensal + prova bimestral + atividades"); a fase "As regras de média" nos Primeiros passos da escola; migration `0029` (1.42.0); as provas `tests/tabler/onda4/acrescimo.php` e `etapa5.js` (o cálculo real pela fachada = o simulador; a sabotagem do limite).
- **Onda 4, Etapa 6:** o fechamento — a bateria inteira do zero no ensino superior (com o ciclo antes de cada prova da onda 4 e do iframe) e na escola (`tests/tabler/onda4/escola_do_zero.php`: a instalação de escola pela tela, a demonstração do colégio pelo comando, a régua da escola e a fumaça com os perfis do colégio); uma instalação da 1.37.0 com a demonstração antiga atualizada pela tela, uma etapa por passo (`tests/tabler/onda4/etapa6.js`: continua de ensino superior, a régua idêntica); a prova do menu da Onda 2 tira da matriz as páginas do outro tipo. O sistema não mudou. **A Onda 4 está fechada.**
- **Provas** em `tests/tabler/` (README lá): instalador (5 cenários), régua, fumaça dos 4 perfis, sessão, telas (`telas.js` + `telas/<etapa>.json`), menu, comportamento (`comportamento.js` + `comportamento/<etapa>.json`), o portal dentro de um iframe HTTPS de outra origem (`iframe.js`), a galeria (`galeria.js` → `provas/tabler/index.html`). Prints em `provas/tabler/<etapa>/{antes,depois}/` (fora do git); o "antes" saiu da cópia AdminLTE (git worktree do commit da Etapa 2, removido no fim da onda).

## A régua

`php tests/tabler/ciclo.php comparar tests/tabler/saida/regua-etapa2.json` monta do zero (instalador + demonstração) e compara com o retrato da Etapa 2: 81 tabelas, 10 boletins, 10 vidas acadêmicas. Na onda 2, a diferença só pode estar nas tabelas de acesso e de configuração que a etapa muda de propósito; cada etapa grava um retrato novo (`regua-onda2-etapaN.json`), e a etapa seguinte compara com ele. O retrato fica em `tests/tabler/saida/` (fora do git): se ele se perder, grave de novo a partir de um commit da Etapa 2.

## Referências

- `docs/LEVANTAMENTO_DEPARA_TABLER.md` — o mapa da onda.
- `docs/DECISIONS.md` — as decisões (D-01 a D-39).
- `docs/DUVIDAS.md` — as escolhas feitas sem o responsável pelo projeto.
