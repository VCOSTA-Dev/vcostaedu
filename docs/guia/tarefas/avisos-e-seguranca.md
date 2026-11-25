---
titulo: Avisos do sistema e segurança
resumo: As versões, as atualizações por aplicar, a versão nova, as verificações de segurança e as bibliotecas, em Manutenção.
perfis: administrador
ordem: 66
relacionados: atualizar-o-sistema, perfis-de-acesso
telas: avisos-sistema/index, verificacoes-seguranca/index, bibliotecas/index
mudou: 1.83.0
entrou: 1.32.0
---
## Para que serve

Ver, num lugar só, se a instalação está em dia e segura: a versão dos arquivos e a do banco, as atualizações por aplicar, se há uma versão nova do sistema e o que a conferência de segurança encontrou.

## Antes de começar

As três telas ficam no grupo **Manutenção** do menu e são só do Administrador. Nada nelas muda dado do sistema.

## Passo a passo

1. **Manutenção → Avisos do sistema**: o cartão **Versões** mostra a versão dos arquivos e a do banco e, se houver, quantas atualizações do banco estão por aplicar (com o caminho para **Atualizar o sistema**).
2. No cartão **Versão nova**, o sistema diz se existe uma versão mais nova. Ele consulta isso **no máximo uma vez por dia**, sem enviar nenhum dado da instalação, e **nunca baixa nada**: quem cuida da instalação traz os arquivos novos e usa Atualizar o sistema. A consulta pode ser desligada no parâmetro "Consultar se há uma versão nova".
3. O cartão **Segurança** resume a conferência: quantos itens estão OK, quantos pedem atenção e quantos é preciso resolver. Clique em **Ver as verificações de segurança**.
4. **Manutenção → Verificações de segurança**: cada item — o HTTPS, a pasta dos arquivos privados, o instalador recusando uma segunda instalação, os erros do PHP fora da tela, o modo do banco, a proteção do histórico, o e-mail de envio, as permissões e, na escola, o portal do responsável (toda tela dele conferindo, a cada requisição, se a pessoa pode ver aquele aluno naquele ano) e as **pastas protegidas** (cada pasta que não pode responder pela web é pedida pelo endereço do sistema e tem de recusar; num servidor Nginx, que não lê os arquivos .htaccess, use o exemplo de configuração do pacote) — com a situação e, quando não está OK, **o que fazer**.
5. **Manutenção → Bibliotecas**: as bibliotecas de terceiros que o sistema usa, com a versão e a licença de cada uma.

## O que pode dar errado

- **"Não foi possível consultar a versão mais nova agora"** — o endereço de consulta não respondeu (ou o servidor não sai para a internet). Nada muda no sistema; a próxima tentativa é no dia seguinte.
- **"A consulta de versão nova está desligada"** — o parâmetro está desligado, ou esta instalação não tem o endereço de consulta.
- Um item **a resolver** em Verificações de segurança, numa instalação local (no seu computador), costuma ser o HTTPS e a pasta dos arquivos: numa instalação local eles aparecem como **atenção**; resolva antes de publicar o sistema.

## Registrar a instalação

Em **Manutenção → Registrar a instalação** você pode contar ao projeto que usa o sistema. É voluntário. A tela mostra exatamente o que vai antes de enviar: um identificador aleatório (que não diz quem é), a versão, o tipo de instituição, a UF, os módulos e as contagens de alunos, cursos e turmas. O nome da instituição, o site, um e-mail para avisos de segurança e aparecer na lista vão só se você marcar. Nunca vai dado de aluno, professor ou responsável. Se o endereço do projeto não responder, o registro fica pendente e o sistema tenta de novo sozinho. **Cancelar o registro** para os envios.
