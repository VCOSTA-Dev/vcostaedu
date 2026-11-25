# A auditoria de notas e faltas

A partir da versão 1.21.0, toda mudança de nota e de falta fica registrada na tabela `rm_auditoria_lancamentos` (migration `0006`). Este documento diz o que é gravado, por quê, quem vê e por quanto tempo.

## O que é registrado

Uma linha para cada mudança, gravada **no mesmo ponto do código que salva o valor e na mesma transação**: se o registro falhar, a gravação toda é desfeita (`painel/app/adms/Models/helper/AdmsAuditoria.php`). Salvar sem mudar o valor não gera linha.

| o quê | onde nasce | o que fica |
|---|---|---|
| nota de uma avaliação | a nota na célula (tela das notas), a importação de notas por planilha | o aluno, a avaliação, a nota anterior e a nova |
| faltas do aluno na disciplina | a tela de faltas, a importação de faltas, a chamada (as faltas consolidadas) | o total anterior e o novo |
| presença na chamada | salvar ou editar um encontro, excluir um encontro | o aluno, a data da aula, presente ou ausente antes e depois |
| faltas compensadas | lançar e remover uma compensação | o total compensado anterior e o novo |
| encerramento e reabertura | encerrar a disciplina, reabrir (com a justificativa) | a situação, a justificativa |

Em cada linha:
- **a ação:** lançou (de vazio ou de zero para um valor), alterou, apagou (de um valor para vazio), encerrou, reabriu;
- **"depois do fechamento":** a disciplina já tinha sido encerrada antes (a mudança veio depois de uma reabertura);
- **quem:** o usuário e o perfil que ele tinha no momento (ou a chave de API, quando a mudança veio pela integração);
- **quando:** data e hora, com milésimos;
- **de onde:** a origem (tela do painel, tela do professor, importação, API), o **IP** e o **navegador resumido** (o nome e a versão principal do navegador e o sistema; a assinatura completa do navegador não é guardada);
- **o ambiente** da instalação (produção, homologação, demonstração).

## Por que

Nota e falta decidem a aprovação e saem nos documentos oficiais. Quando um resultado é contestado, a instituição precisa responder quem lançou, quando, de onde, e o que havia antes — sem depender da memória de ninguém.

## O IP é dado pessoal

O endereço IP, ligado ao usuário, é **dado pessoal** (LGPD). Ele é guardado com uma finalidade só — apurar quem fez uma mudança de nota ou de falta e de onde — e:
- vem de um ponto único (`\Core\Seguranca::ipCliente()`): o cabeçalho de proxy (`X-Forwarded-For`) só é aceito quando a instalação declara o proxy na constante `PROXIES_CONFIAVEIS`; sem isso, vale o endereço que chegou ao servidor, e ninguém consegue forjar o IP mandando um cabeçalho;
- só aparece no relatório de auditoria, que só o Administrador, a Direção acadêmica, a Supervisão de secretaria e a Coordenação de curso (dos cursos dela) abrem; o histórico de cada nota não mostra o IP;
- não sai em documento, exportação automática nem integração.

A instituição deve citar este registro na sua política de privacidade e definir por quanto tempo o guarda.

## Ninguém altera nem apaga

- Não existe tela nem rota que altere ou apague uma linha da auditoria — nem para o Administrador. As duas rotas da auditoria são de leitura (`auditoria/listar`, `auditoria/nota`).
- No banco, a migration `0007` cria dois gatilhos (precisa do privilégio `TRIGGER`; sem ele, fica adiada e a proteção é só a do código): alterar uma linha é recusado; apagar também — a única exceção é a limpeza da demonstração, que apaga só as linhas do ambiente "demonstração".

## Quem vê

- **Histórico desta nota** (o ícone do relógio ao lado de cada nota): quem vê a nota — o professor, a coordenação do curso, a direção, a secretaria e o administrador. O aluno não vê.
- **Relatório "Auditoria de notas e faltas"** (Relatórios): Administrador, Direção acadêmica, Supervisão de secretaria; a Coordenação de curso só dos cursos que coordena. Filtros: curso, disciplina, aluno, quem lançou, período, o tipo, "só alterações e exclusões"; exportação para Excel, CSV e PDF.

## Até a migration ser aplicada

Numa instalação que recebeu os arquivos novos e ainda não aplicou a `0006`, nada é registrado e as gravações seguem como antes (D-28).
