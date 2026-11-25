---
titulo: Atualizar o sistema
resumo: Aplicar as etapas do banco de dados de uma versão nova, pela tela ou pelo comando, e ver a versão instalada.
perfis: administrador
ordem: 5
relacionados: integrar-outro-sistema-pela-api
telas: atualizar/index, versao/index
entrou: 1.25.0
---
## Para que serve

Uma versão nova chega em duas partes: os arquivos (que você copia para o servidor) e as etapas do banco de dados (que mudam as tabelas). Enquanto as etapas não são aplicadas, o sistema continua funcionando, e as telas novas mostram o aviso de "atualização pendente". Esta tela aplica as etapas.

## Antes de começar

- **Faça uma cópia (backup) do banco de dados** e guarde-a fora do servidor. Algumas etapas mudam a estrutura do banco e não têm volta pela tela: se algo der errado no meio, é o backup que traz o sistema de volta. Por exemplo: `mysqldump -u USUARIO -p NOME_DO_BANCO > backup.sql`.
- Copie antes os arquivos da versão nova para a pasta do sistema (sem apagar as duas `Config.php`).
- Só o Administrador atualiza.

## Passo a passo

1. Depois de copiar os arquivos, entre no painel: a faixa amarela **Atualização do banco de dados pendente** aparece. Clique em **Atualizar o sistema** (ou abra **Manutenção → Atualizar o sistema**).
2. Confira a versão do banco, a dos arquivos e a lista das etapas por aplicar.
3. Marque **Tenho uma cópia do banco de dados feita agora** e clique em **Atualizar agora**. A janela de andamento aplica **uma etapa de cada vez** e mostra qual está sendo aplicada. No fim, **Ver o resultado** diz quantas etapas foram aplicadas e a versão nova.
4. **Se a página fechar no meio** (ou a conexão cair), abra a tela de novo: ela diz que a atualização não terminou e oferece **Continuar a atualização**, que segue de onde parou — o que já passou não é aplicado de novo.
5. A qualquer momento, **Ajuda → Versão** mostra a versão instalada e se há etapa pendente (cada perfil vê a sua; só o Administrador vê o caminho para atualizar).

Pelo comando, na pasta do sistema, o efeito é o mesmo, com todas as etapas em sequência — a tela e o comando aplicam pelo mesmo caminho, e um recusa enquanto o outro estiver atualizando:

```
php bin/migrar.php status
php bin/migrar.php aplicar
```

## O que pode dar errado

- **"Outra atualização está em andamento"** — alguém está atualizando agora (pela tela, em outro computador, ou pelo comando). Espere terminar e abra a tela de novo. Se a outra pessoa fechou a página e não voltou, depois de um minuto e meio sem andamento a tela deixa você continuar a atualização dela.
- **"A atualização parou na etapa …"** — uma etapa falhou. As que passaram antes continuam valendo e aparecem na lista; a que falhou e as seguintes ficam pendentes. Não tente de novo antes de entender a causa: o detalhe está no log do servidor, e o backup traz o banco de volta. Resolvida a causa, **Continuar a atualização** recomeça pela etapa que falhou.
- **"Há etapas já aplicadas que foram alteradas nos arquivos"** — os arquivos não são os da versão instalada. Nada é aplicado assim: restaure os arquivos e procure o suporte.
- **Uma etapa "exige o privilégio TRIGGER"** — sem esse privilégio do usuário do banco, ela fica adiada de propósito, e o sistema funciona sem ela.
