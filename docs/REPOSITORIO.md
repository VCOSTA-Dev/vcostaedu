# O repositório do produto e as atualizações

O VCOSTAEDU tem dois repositórios:

- **o de trabalho** — esta pasta, com todo o histórico de commits das ondas, as provas, os retornos e as notas de
  sessão. Ele não é publicado;
- **o do produto** — uma pasta à parte (por exemplo, `vcostaedu-repositorio`), sem o histórico de trabalho: **um commit
  por versão entregue**, com o número da versão. É o que vai para o repositório remoto (privado por enquanto; público um
  dia).

O repositório do produto nunca recebe commit à mão: tudo nasce no de trabalho, entra no `main` e é exportado.

## O que vai para o repositório do produto

Os arquivos versionados do último commit do `main` (`git archive`), menos:

- o `retorno.md` e as notas de trabalho das sessões (`docs/SESSION_HANDOFF.md`, `docs/NEXT_TASK.md`,
  `docs/guia/material-pendencias.md`) — a lista `FORA_DO_PACOTE` de `database/ferramentas/implantacao/pasta_limpa.php`;
- a pasta `provas/` (as provas e as retomadas de cada onda).

O que o `.gitignore` já deixa fora também não vai: as `Config.php`, a pasta `storage/`, os logos e os vídeos gerados, os
envios de pessoa, as saídas das provas, as dependências de teste, os `.env`, os dumps e os logs.

Antes de cada commit, a **varredura de higiene** da pasta exportada tem de dar zero achado: chave privada, senha escrita
no código, chave de API ou token, endereço IP, porta local, caminho de disco ou de servidor, o nome de código da pasta de
trabalho e, informados pelo ambiente (nunca escritos em arquivo versionado), os nomes reais (`VCOSTAEDU_VARRER_NOMES`) e
os segredos — os nomes de banco, o endereço e a chave de um Moodle, os nomes de clientes e de instituições reais
(`VCOSTAEDU_VARRER_SEGREDOS`, listas separadas por vírgula). O sistema de onde vieram as regras da escola é sempre "o
sistema de origem".

## Criar (uma vez)

Na pasta de trabalho, no `main`, com o ambiente da varredura carregado:

```
php database/ferramentas/implantacao/repositorio_atualizar.php ../vcostaedu-repositorio
```

A ferramenta exporta pela pasta limpa, exige a varredura sem achado, cria a pasta com `git init` (o ramo `main`), copia
a exportação e faz o commit `VCOSTAEDU <versão>` com a tag `v<versão>`. O autor do commit é neutro
(`VCOSTAEDU <noreply@vcostaedu.invalid>`); para outro, defina `VCOSTAEDU_REPO_AUTOR="Nome <e-mail>"`.

Depois, o responsável liga o repositório remoto e envia (o remoto é criado por ele, privado):

```
cd ../vcostaedu-repositorio
git remote add origin <endereço do repositório remoto>
git push -u origin main
git push origin --tags
```

## Atualizar (a cada entrega)

1. A entrega nova entra no `main` do repositório de trabalho, como sempre (as provas verdes, a versão nova).
2. Na pasta de trabalho, no `main`:

   ```
   php database/ferramentas/implantacao/repositorio_atualizar.php ../vcostaedu-repositorio
   ```

   A ferramenta exporta de novo, confere a varredura, **apaga** da pasta do repositório tudo o que não é `.git` e copia a
   exportação (o que saiu do produto sai também do repositório), faz `git add -A` e **um** commit
   `VCOSTAEDU <versão>`, com a tag. Se nada mudou, não faz commit. Com `--sem-commit`, só copia, para conferir antes
   (`git -C ../vcostaedu-repositorio status`).
3. O responsável envia: `git push` e `git push origin --tags`.

Se a varredura achar algo, a ferramenta para antes de tocar no repositório e diz onde está o relatório: corrija no
repositório de trabalho (um commit normal no `main`) e rode de novo.

## Conferir que a exportação instala

A pasta do repositório é um sistema completo: servida pelo Apache, abre o instalador. A prova da criação (fechamento,
Parte 2) instalou a pasta exportada do zero, num banco de prova novo, nos três tipos de instituição. Depois de
conferir, apague as `Config.php`, a pasta `storage/` e os arquivos que o instalador criou (o `.gitignore` já os deixa
fora do commit, mas eles têm a senha do banco de prova).
