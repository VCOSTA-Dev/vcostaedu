<?php if (!defined('VCOSTAEDU')) { header('Location: /'); die('Erro: Página não encontrada!'); } ?>
<h2>Para que serve</h2>
<p>Uma versão nova chega em duas partes: os arquivos (que você copia para o servidor) e as etapas do banco de dados (que mudam as tabelas). Enquanto as etapas não são aplicadas, o sistema continua funcionando, e as telas novas mostram o aviso de &quot;atualização pendente&quot;. Esta tela aplica as etapas.</p>
<h2>Antes de começar</h2>
<ul>
<li><strong>Faça uma cópia (backup) do banco de dados</strong> e guarde-a fora do servidor. Algumas etapas mudam a estrutura do banco e não têm volta pela tela: se algo der errado no meio, é o backup que traz o sistema de volta. Por exemplo: <code>mysqldump -u USUARIO -p NOME_DO_BANCO &gt; backup.sql</code>.</li>
<li>Copie antes os arquivos da versão nova para a pasta do sistema (sem apagar as duas <code>Config.php</code>).</li>
<li>Só o Administrador atualiza.</li>
</ul>
<h2>Passo a passo</h2>
<ol>
<li>Depois de copiar os arquivos, entre no painel: a faixa amarela <strong>Atualização do banco de dados pendente</strong> aparece. Clique em <strong>Atualizar o sistema</strong> (ou abra <strong>Manutenção → Atualizar o sistema</strong>).</li>
<li>Confira a versão do banco, a dos arquivos e a lista das etapas por aplicar.</li>
<li>Marque <strong>Tenho uma cópia do banco de dados feita agora</strong> e clique em <strong>Atualizar agora</strong>. A janela de andamento aplica <strong>uma etapa de cada vez</strong> e mostra qual está sendo aplicada. No fim, <strong>Ver o resultado</strong> diz quantas etapas foram aplicadas e a versão nova.</li>
<li><strong>Se a página fechar no meio</strong> (ou a conexão cair), abra a tela de novo: ela diz que a atualização não terminou e oferece <strong>Continuar a atualização</strong>, que segue de onde parou — o que já passou não é aplicado de novo.</li>
<li>A qualquer momento, <strong>Ajuda → Versão</strong> mostra a versão instalada e se há etapa pendente (cada perfil vê a sua; só o Administrador vê o caminho para atualizar).</li>
</ol>
<p>Pelo comando, na pasta do sistema, o efeito é o mesmo, com todas as etapas em sequência — a tela e o comando aplicam pelo mesmo caminho, e um recusa enquanto o outro estiver atualizando:</p>
<pre><code>php bin/migrar.php status
php bin/migrar.php aplicar</code></pre>
<h2>O que pode dar errado</h2>
<ul>
<li><strong>&quot;Outra atualização está em andamento&quot;</strong> — alguém está atualizando agora (pela tela, em outro computador, ou pelo comando). Espere terminar e abra a tela de novo. Se a outra pessoa fechou a página e não voltou, depois de um minuto e meio sem andamento a tela deixa você continuar a atualização dela.</li>
<li><strong>&quot;A atualização parou na etapa …&quot;</strong> — uma etapa falhou. As que passaram antes continuam valendo e aparecem na lista; a que falhou e as seguintes ficam pendentes. Não tente de novo antes de entender a causa: o detalhe está no log do servidor, e o backup traz o banco de volta. Resolvida a causa, <strong>Continuar a atualização</strong> recomeça pela etapa que falhou.</li>
<li><strong>&quot;Há etapas já aplicadas que foram alteradas nos arquivos&quot;</strong> — os arquivos não são os da versão instalada. Nada é aplicado assim: restaure os arquivos e procure o suporte.</li>
<li><strong>Uma etapa &quot;exige o privilégio TRIGGER&quot;</strong> — sem esse privilégio do usuário do banco, ela fica adiada de propósito, e o sistema funciona sem ela.</li>
</ul>
