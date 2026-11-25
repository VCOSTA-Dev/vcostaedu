<?php if (!defined('VCOSTAEDU')) { header('Location: /'); die('Erro: Página não encontrada!'); } ?>
<h2>Para que serve</h2>
<p>Ver, num lugar só, se a instalação está em dia e segura: a versão dos arquivos e a do banco, as atualizações por aplicar, se há uma versão nova do sistema e o que a conferência de segurança encontrou.</p>
<h2>Antes de começar</h2>
<p>As três telas ficam no grupo <strong>Manutenção</strong> do menu e são só do Administrador. Nada nelas muda dado do sistema.</p>
<h2>Passo a passo</h2>
<ol>
<li><strong>Manutenção → Avisos do sistema</strong>: o cartão <strong>Versões</strong> mostra a versão dos arquivos e a do banco e, se houver, quantas atualizações do banco estão por aplicar (com o caminho para <strong>Atualizar o sistema</strong>).</li>
<li>No cartão <strong>Versão nova</strong>, o sistema diz se existe uma versão mais nova. Ele consulta isso <strong>no máximo uma vez por dia</strong>, sem enviar nenhum dado da instalação, e <strong>nunca baixa nada</strong>: quem cuida da instalação traz os arquivos novos e usa Atualizar o sistema. A consulta pode ser desligada no parâmetro &quot;Consultar se há uma versão nova&quot;.</li>
<li>O cartão <strong>Segurança</strong> resume a conferência: quantos itens estão OK, quantos pedem atenção e quantos é preciso resolver. Clique em <strong>Ver as verificações de segurança</strong>.</li>
<li><strong>Manutenção → Verificações de segurança</strong>: cada item — o HTTPS, a pasta dos arquivos privados, o instalador recusando uma segunda instalação, os erros do PHP fora da tela, o modo do banco, a proteção do histórico, o e-mail de envio, as permissões e, na escola, o portal do responsável (toda tela dele conferindo, a cada requisição, se a pessoa pode ver aquele aluno naquele ano) e as <strong>pastas protegidas</strong> (cada pasta que não pode responder pela web é pedida pelo endereço do sistema e tem de recusar; num servidor Nginx, que não lê os arquivos .htaccess, use o exemplo de configuração do pacote) — com a situação e, quando não está OK, <strong>o que fazer</strong>.</li>
<li><strong>Manutenção → Bibliotecas</strong>: as bibliotecas de terceiros que o sistema usa, com a versão e a licença de cada uma.</li>
</ol>
<h2>O que pode dar errado</h2>
<ul>
<li><strong>&quot;Não foi possível consultar a versão mais nova agora&quot;</strong> — o endereço de consulta não respondeu (ou o servidor não sai para a internet). Nada muda no sistema; a próxima tentativa é no dia seguinte.</li>
<li><strong>&quot;A consulta de versão nova está desligada&quot;</strong> — o parâmetro está desligado, ou esta instalação não tem o endereço de consulta.</li>
<li>Um item <strong>a resolver</strong> em Verificações de segurança, numa instalação local (no seu computador), costuma ser o HTTPS e a pasta dos arquivos: numa instalação local eles aparecem como <strong>atenção</strong>; resolva antes de publicar o sistema.</li>
</ul>
<h2>Registrar a instalação</h2>
<p>Em <strong>Manutenção → Registrar a instalação</strong> você pode contar ao projeto que usa o sistema. É voluntário. A tela mostra exatamente o que vai antes de enviar: um identificador aleatório (que não diz quem é), a versão, o tipo de instituição, a UF, os módulos e as contagens de alunos, cursos e turmas. O nome da instituição, o site, um e-mail para avisos de segurança e aparecer na lista vão só se você marcar. Nunca vai dado de aluno, professor ou responsável. Se o endereço do projeto não responder, o registro fica pendente e o sistema tenta de novo sozinho. <strong>Cancelar o registro</strong> para os envios.</p>
