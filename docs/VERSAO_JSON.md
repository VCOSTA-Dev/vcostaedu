# O `versao.json` que o site do projeto publica

O sistema pergunta, uma vez por dia, qual é a versão mais nova (`core/VersaoNova.php`). O endereço fica fixo no código:
`https://vcostaedu.com/versao.json` (`VcLegal::URL_VERSAO_NOVA`, ou a constante `URL_VERSAO_NOVA` da Config). A
consulta é um GET sem parâmetro, sem cookie, sem a versão instalada e sem o endereço da instalação. O sistema lê no
máximo 16 KB e nunca baixa o pacote. O parâmetro `sistema.verificar_versao_nova` = 0 desliga a consulta.

Enquanto o endereço não responder, tudo funciona como se ele estivesse vazio:
- nada aparece como versão nova;
- os links "Apoie" e "Serviços" não aparecem.

## O formato

Um objeto JSON, em UTF-8:

```json
{
  "versao": "1.49.0",
  "data": "2026-11-18",
  "notas": "https://vcostaedu.com/versoes/1.49.0",
  "pacote": "https://vcostaedu.com/pacotes/vcostaedu-1.49.0-20261118.zip",
  "sha256": "64 caracteres hexadecimais: o código de conferência do zip",
  "seguranca": false
}
```

| Campo | Obrigatório | O que é |
|---|---|---|
| `versao` | sim | a versão mais nova, `MAIOR.MENOR.CORREÇÃO` (por exemplo, `1.49.0`). A instalação compara com a dela |
| `data` | não | a data da versão (`AAAA-MM-DD`) |
| `notas` | não | o endereço das notas da versão (https://). A tela dos Avisos do sistema mostra o link |
| `pacote` | não | o endereço do pacote da versão (o zip do gerador). A tela não baixa: só mostra |
| `sha256` | não | o código de conferência do pacote (o `.sha256` que o gerador escreve ao lado do zip), para quem baixa conferir |
| `seguranca` | não | `true` quando a versão corrige uma falha de segurança. A instalação mais antiga mostra o aviso com destaque, "corrige uma falha de segurança: atualize assim que puder" |

Campo a mais é ignorado. Um `versao` fora do formato, ou um JSON inválido, conta como "sem resposta": nada é mostrado.

## De onde vêm os valores

O gerador do pacote escreve, ao lado do zip, o `versao.json` pronto para publicar:

```
php database/ferramentas/implantacao/gerar_pacote.php [--saida=PASTA] [--seguranca]
```

- `vcostaedu-<versão>-<data>.zip` e o `.sha256` dele, como antes;
- `vcostaedu-<versão>-<data>.versao.json`: o formato acima, com `versao`, `data`, `sha256` e `seguranca` (o
  `--seguranca` marca a versão que corrige falha de segurança). Os endereços `notas` e `pacote` seguem o padrão do
  site e podem ser ajustados antes de publicar;
- `NOTAS-<versão>.md`: as notas da versão, tiradas das novidades da etapa do banco daquela versão (o título e o texto
  de cada uma), com o código de conferência no fim. Dentro do zip também, ao lado do `PACOTE.txt`.

O site publica o `versao.json` e as notas. O lado que recebe o registro das instalações
(`https://vcostaedu.com/registro`) não é construído neste repositório (DECISIONS D-59).
