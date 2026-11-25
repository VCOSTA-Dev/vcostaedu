---
titulo: Integrar outro sistema pela API
resumo: Deixar outro sistema consultar e cadastrar alunos, matrículas e enturmações, pelas mesmas regras das telas.
perfis: administrador
ordem: 4
relacionados: importar-alunos-de-planilha, cadastrar-aluno-e-enviar-link, vincular-e-enturmar
telas: integracoes/chaves, integracoes/documentacao, integracoes/chamadas
entrou: 1.24.0
mudou: 1.80.0
---
## Para que serve

Quando o aluno nasce em outro sistema (o de matrículas, o financeiro, o site de inscrições), a API deixa esse sistema trazer o aluno para cá sem digitar de novo: consultar cursos, grades, anos e ofertas; cadastrar e atualizar alunos; vincular ao curso; trancar, reativar e cancelar a matrícula; e enturmar. Tudo pelas mesmas regras das telas: o que a tela recusa, a API recusa, com a mesma mensagem.

## Antes de começar

- Só o Administrador cria chaves e vê o registro das chamadas.
- Crie **uma chave por sistema**: assim dá para revogar uma sem parar as outras. Dê a cada chave **só o que o sistema precisa**, assunto por assunto (alunos, matrículas, enturmação, cadastros de apoio), para consultar ou para gravar — gravar inclui consultar o mesmo assunto. Uma chave nova começa sem nada marcado.
- A chave aparece **uma vez**, ao ser criada. O sistema guarda só um resumo dela; se ela se perder, revogue-a e crie outra.
- O endereço da API é o do sistema seguido de `/api/v1` (por exemplo, `https://escola.exemplo.invalid/api/v1`). Use sempre **HTTPS** fora do computador de testes.

## Passo a passo

1. Abra **Integrações → Chaves de API**, escreva o nome de quem vai usar, marque **o que a chave pode fazer** em cada assunto e, se quiser, a validade. Clique em **Criar** e copie a chave.
2. Entregue a chave ao responsável pelo outro sistema, por um canal seguro. Ele a envia em toda chamada, no cabeçalho `Authorization: Bearer <chave>`.
3. Em **Integrações → Documentação da API** estão todas as operações, com os campos e as respostas. Para testar ali mesmo, clique em **Authorize**, cole a chave e use **Try it out** (com uma chave de escrita, o teste grava de verdade).
4. Do outro sistema, faça as chamadas como nos exemplos abaixo (dados fictícios: troque o endereço e a chave).
5. Acompanhe em **Integrações → Chamadas da API**: cada chamada, com a chave, a operação, o resultado, o IP e a hora. Na lista das chaves, veja o último uso de cada uma.
6. Quando um sistema deixar de ser usado (ou a chave vazar), clique em **Revogar**: a partir daí ela é recusada.

Cadastrar um aluno:

```
curl -X POST https://escola.exemplo.invalid/api/v1/alunos \
  -H "Authorization: Bearer vce_0000000000000000000000000000000000000000" \
  -H "Content-Type: application/json" \
  -H "Idempotency-Key: inscricao-2026-0001" \
  -d '{"ra": "900101", "nome": "Aluna Exemplo da Silva", "sexo": "feminino", "email": "aluna@exemplo.invalid", "nascimento": "2000-03-15"}'
```

E vinculá-la ao curso 1, no ano de 2026:

```
curl -X POST https://escola.exemplo.invalid/api/v1/matriculas \
  -H "Authorization: Bearer vce_0000000000000000000000000000000000000000" \
  -H "Content-Type: application/json" \
  -d '{"ra": "900101", "curso_id": 1, "ano": 2026, "forma_ingresso_id": 1, "data_ingresso": "2026-02-02"}'
```

**Na escola:** `GET /turmas` (nos cadastros de apoio) lista as turmas do ano, com a série, o nome e as ofertas de cada uma; `POST /turmas/{id}/alunos` com a RA põe o aluno na turma (ele entra em todas as disciplinas dela, pela mesma regra da tela), e `GET /turmas/{id}/alunos` lista quem está nela. Pôr na turma pede o assunto **enturmação** na chave. Numa instalação de ensino superior, essas rotas não existem.

## Inscrever numa chamada só (o site da instituição)

Para o site que vende um curso, ou o formulário de inscrição, a operação **POST /inscricoes** faz tudo de uma vez: o cadastro da pessoa, a matrícula no curso e a turma (na escola) ou as ofertas (no ensino superior). É **tudo ou nada**: se uma parte é recusada (a turma completa, a turma que não existe, a pessoa noutra turma), nada fica gravado, e a resposta diz a razão, começando por "Nada foi gravado:". A chave precisa gravar **Alunos, Matrículas e Enturmação**. As operações separadas continuam como sempre.

```
curl -X POST https://escola.exemplo.invalid/api/v1/inscricoes \
  -H "Authorization: Bearer vce_0000000000000000000000000000000000000000" \
  -H "Content-Type: application/json" \
  -H "Idempotency-Key: site-inscricao-8f2c51d0" \
  -d '{"aluno": {"ra": "970001", "nome": "Pessoa Exemplo da Silva", "email": "pessoa@exemplo.invalid", "celular": "61999990000"}, "destino": {"turma_id": 12}}'
```

No ensino superior, o destino é o curso, o ano e as ofertas: `"destino": {"curso_id": 1, "ano": 2026, "ofertas": [10, 11, 12, 13]}`.

A resposta diz o que foi feito (`"feito": {"aluno": "criado", "matricula": "criada", "turma": "entrou"}`) e o estado no Moodle. A mesma matrícula de novo não cria uma segunda pessoa: a resposta diz o que já existia (`ja_existia`, `ja_estava`). Gere a `Idempotency-Key` a partir do **envio do formulário** (o mesmo envio, a mesma chave): o clique duplo manda a mesma chave, e a segunda chamada devolve a mesma resposta, mesmo chegando ao mesmo tempo que a primeira.

**O que acontece no Moodle.** Com o Moodle conectado e o envio automático (**Integrações → Conectar ao Moodle**), logo depois de gravar a plataforma manda, na mesma chamada e dentro de um tempo curto: o usuário da pessoa (com o e-mail do modo escolhido em Conectar ao Moodle), a disciplina no Moodle de cada disciplina da turma, se ainda não existe, e a inscrição da pessoa em cada uma. A resposta traz `moodle`: `enviado` (o usuário existe lá), `disciplinas` (em quantas ela já está) e `disciplinas_total` (quantas são). O que não coube termina **sozinho** nas chamadas e telas seguintes, sem clique de ninguém; `GET /alunos/{ra}` mostra o andamento. O Moodle fora do ar não impede a inscrição: os dados ficam gravados, e o envio se completa quando ele volta. O que só uma pessoa resolve (a pessoa sem e-mail, no modo "só o e-mail do aluno") fica em **Integrações → Envios ao Moodle**, com o motivo; corrigido o cadastro, o usuário e as inscrições vão sozinhos.

**O primeiro acesso.** Logo depois da inscrição, `POST /alunos/{ra}/primeiro-acesso` manda à pessoa o e-mail de boas-vindas, com o link para criar a senha (o mesmo que a secretaria manda pela ficha do aluno: de uso único e com prazo). Com `"devolver_link": true`, o link também volta na resposta, para o site mostrar um botão "Criar minha senha agora"; com isso, a inscrição deixa de confirmar que o e-mail é da pessoa: use só numa demonstração ao vivo, em que o e-mail pode atrasar. O link aberto de novo, depois de criar a senha, leva à entrada do portal, com o aviso de que a senha já foi criada.

## O que pode dar errado

Toda resposta de erro vem no mesmo formato: `{"erro": {"codigo": "...", "mensagem": "...", "campo": "..."}}`.

- **401** (`nao_autenticado`, `chave_revogada`, `chave_vencida`) — a chave não veio, está errada, foi revogada ou venceu.
- **403** (`sem_permissao`) — a chave não tem o assunto, ou tem só para consultar e tentou gravar. A mensagem diz o que a chave pode; peça ao administrador uma chave com o limite certo.
- **422** (`validacao`) — o dado foi recusado pela regra da tela (CPF inválido, sexo faltando, curso inexistente…); o `campo` diz qual.
- **409** (`conflito`) — a operação não cabe no estado atual (o aluno já tem matrícula no curso; reativar uma matrícula cancelada), ou a mesma `Idempotency-Key` veio com outra requisição.
- **403** (`https_obrigatorio`) — a instalação exige HTTPS e a chamada veio por `http://`. Chame por `https://`; se a chave foi enviada por HTTP, revogue-a e crie outra.
- **429** (`limite_excedido`) — a chave passou do limite de chamadas por minuto (120 de início). Espere o tempo do cabeçalho `Retry-After`. O limite fica em **Parâmetros**, em "Integrações: chamadas por minuto de cada chave da API" (de 10 a 10000): aumente quando um sistema ligado faz muitas chamadas de uma vez (o site de inscrições num período de matrícula, uma importação grande) e o registro de chamadas mostra respostas 429.
- **Cadastrar um RA que já existe** não é erro: a API devolve o aluno existente (`"criado": false`), sem duplicar e sem mudar nada. Para mudar os dados dele, use o `PATCH`.
- **422 com "Nada foi gravado:"** (na inscrição numa chamada só) — uma parte foi recusada (a turma completa, por exemplo) e nada ficou gravado; a mensagem diz a razão.
- **Repetir uma escrita por falha de rede:** mande a mesma `Idempotency-Key`; a API devolve a mesma resposta, sem gravar duas vezes.
