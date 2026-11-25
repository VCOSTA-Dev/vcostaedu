<?php if (!defined('VCOSTAEDU')) { header('Location: /'); die('Erro: Página não encontrada!'); } ?>
<h2>Para que serve</h2>
<p>Os dados que saem no cabeçalho do boletim e do histórico, e o <strong>ato legal</strong> que o histórico cita.</p>
<h2>Antes de começar</h2>
<p>Tenha o CNPJ, o código e-MEC (se houver), o telefone e o e-mail da instituição, o texto do cabeçalho dos documentos (Estado, órgão, diretoria, programa…), o logo (PNG ou JPEG) e os atos de credenciamento com número, data, publicação e vigência.</p>
<h2>Passo a passo</h2>
<ol>
<li><strong>Configurações → Dados da instituição</strong>: nome, mantenedora, <strong>CNPJ</strong>, e-MEC, site, <strong>telefone</strong>, <strong>e-mail</strong>, endereço e <strong>logo</strong>.</li>
<li>Escolha o que vai <strong>no topo do menu, ao lado do selo do VCOSTAEDU</strong>: o <strong>nome da instituição</strong>, escrito (em até duas linhas), ou o <strong>logo</strong>. E o <strong>ícone da aba do navegador</strong>: o do VCOSTAEDU (o padrão) ou o logo da instituição. Antes de os dados serem salvos, o topo mostra &quot;VCOSTAEDU&quot;.</li>
<li>Em <strong>Cabeçalho dos documentos</strong>, escreva até 5 linhas, uma por campo, na ordem em que saem no topo do histórico. As <strong>setas</strong> trocam uma linha de lugar; a borracha apaga o texto. A <strong>prévia</strong> ao lado mostra o logo e as linhas como no documento; a linha do <strong>curso</strong> vem do cadastro de cada curso e sai por último. Salve.</li>
<li><strong>Configurações → Atos legais → Novo ato</strong>: órgão, tipo, número, data, veículo e data de publicação, <strong>vigência</strong> e a <strong>modalidade</strong> a que se aplica. Salve.</li>
<li>O topo da lista de atos mostra qual ato vale hoje em cada modalidade.</li>
</ol>
<figure><img src="{{IMG}}administrador_17_atos_legais.png" alt="Lista de atos legais com o quadro do ato de cada modalidade"><figcaption>Lista de atos legais com o quadro do ato de cada modalidade</figcaption></figure>
<blockquote>O histórico usa o ato <strong>ativo e vigente</strong> da <strong>modalidade da matrícula</strong>; sem nenhum, ou com mais de um, ele avisa &quot;ato legal não definido&quot; em vez de escolher.</blockquote>
<h2>O que pode dar errado</h2>
<ul>
<li><strong>&quot;CNPJ inválido (confira os 14 dígitos).&quot;</strong></li>
<li><strong>&quot;Telefone inválido…&quot;</strong> ou <strong>&quot;E-mail institucional inválido.&quot;</strong> — confira o que foi digitado; os dois podem ficar vazios.</li>
<li><strong>&quot;A linha N do cabeçalho passa de 200 caracteres.&quot;</strong> — divida o texto em duas linhas.</li>
<li><strong>&quot;O logo deve ser uma imagem PNG ou JPEG.&quot;</strong> (até 1 MB, conferido pelo conteúdo).</li>
<li><strong>&quot;O fim da vigência não pode ser antes do início.&quot;</strong></li>
<li>Dois atos vigentes para a mesma modalidade: o cadastro avisa, e o histórico fica com &quot;ato legal não definido&quot; até você inativar um deles.</li>
</ul>
