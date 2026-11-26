<?php if (!defined('VCOSTAEDU')) { header('Location: /'); die('Erro: Página não encontrada!'); } ?>
<h2>Para que serve</h2>
<p>Entender um aviso da integração com o Moodle — em Envios ao Moodle, no teste da conexão ou na saúde da conexão — e saber o que fazer. Com o Moodle, o problema costuma ser configuração do lado dele: os serviços web desligados, a chave trocada, uma função fora do serviço do VCOSTAEDU ou o e-mail do Moodle.</p>
<h2>Antes de começar</h2>
<p>O mesmo glossário está no fim da tela <strong>Integrações → Conectar ao Moodle</strong>.</p>
<h2>Passo a passo</h2>
<ol>
<li>Ache o aviso na lista abaixo (o texto é o mesmo da tela).</li>
<li>Faça o que a coluna &quot;o que fazer&quot; diz; depois, em <strong>Envios ao Moodle</strong>, clique em <strong>Tentar de novo</strong>.</li>
</ol>
<h2>O que pode dar errado</h2>
<ul>
<li><strong>O Moodle não reconheceu a chave.</strong> — A chave foi trocada ou apagada no Moodle. Em &quot;Conectar ao VCOSTAEDU&quot;, no Moodle, gere de novo e cole aqui.</li>
<li><strong>O Moodle recusou o acesso ao serviço.</strong> — Confira no Moodle se os serviços web estão ligados (Administração do site → Recursos avançados) e se a função está no serviço do VCOSTAEDU.</li>
<li><strong>A conta do serviço não tem a permissão para isso no Moodle.</strong> — No Moodle, confira o papel da conta do serviço do VCOSTAEDU (o plugin cria a conta com as permissões certas). Se alguém as mudou, use o botão &quot;Corrigir papel e serviço&quot; na página do plugin, no Moodle.</li>
<li><strong>O Moodle recusou os dados enviados.</strong> — Costuma ser um dado repetido (o nome de usuário ou o e-mail de outra pessoa no Moodle) ou inválido. Confira o cadastro e tente de novo.</li>
<li><strong>O Moodle não achou o registro (a disciplina ou o usuário ligado pode ter sido apagado lá).</strong> — Confira no Moodle. Se a disciplina foi apagada, ligue a disciplina de novo ou deixe o VCOSTAEDU criar outra.</li>
<li><strong>O serviço do VCOSTAEDU está desligado no Moodle.</strong> — No Moodle, ligue o serviço do VCOSTAEDU (Administração do site → Servidor → Serviços externos).</li>
<li><strong>Os serviços web estão desligados no Moodle.</strong> — No Moodle: Administração do site → Recursos avançados → ligue &quot;Serviços web&quot;.</li>
<li><strong>O aluno ainda não existe no Moodle.</strong> — Envie o aluno ao Moodle (Envios ao Moodle) e tente de novo.</li>
<li><strong>A conta do aluno foi apagada no Moodle.</strong> — Recrie a conta no Moodle, ou peça a quem cuida do Moodle para restaurá-la.</li>
<li><strong>A conta do aluno está suspensa no Moodle.</strong> — Quem cuida do Moodle reativa a conta (ou a matrícula foi trancada ou cancelada).</li>
<li><strong>A conta do aluno não está confirmada no Moodle.</strong> — Quem cuida do Moodle confirma a conta.</li>
<li><strong>A conta do aluno não pode entrar no Moodle (a autenticação dela está como &quot;sem login&quot;).</strong> — No Moodle, mude a autenticação da conta para &quot;Contas manuais&quot;.</li>
<li><strong>A conta é a de visitante do Moodle.</strong> — Ligue o aluno a uma conta própria.</li>
<li><strong>A conta é de administrador do Moodle: o link de entrada não vale para ela.</strong> — Use uma conta de aluno.</li>
<li><strong>A conta é a do próprio serviço do VCOSTAEDU.</strong> — Ligue o aluno a uma conta própria.</li>
<li><strong>A forma de entrada da conta está desligada no Moodle.</strong> — No Moodle, ligue &quot;Contas manuais&quot; em Plugins → Autenticação.</li>
<li><strong>O Moodle não respondeu (o endereço não abre, ou o servidor está fora do ar).</strong> — Confira o endereço e se o Moodle abre no navegador. O que falta fica na fila e vai depois.</li>
<li><strong>O Moodle demorou demais para responder.</strong> — Pode ser o servidor do Moodle ocupado. O que falta fica na fila; use &quot;Tentar de novo&quot; mais tarde.</li>
<li><strong>O certificado do endereço https:// do Moodle não foi aceito.</strong> — Quem cuida do Moodle confere o certificado (vencido, de outro endereço ou não reconhecido).</li>
<li><strong>O endereço levou para outro lugar (o Moodle está noutro endereço).</strong> — Confira o endereço do Moodle (com ou sem &quot;www&quot;, com https://) e salve de novo.</li>
<li><strong>O endereço respondeu, mas não como um Moodle.</strong> — Confira se o endereço é o do Moodle (a página inicial dele).</li>
<li><strong>O Moodle gravou a inscrição, mas não conseguiu mandar o e-mail de aviso (&quot;Message was not sent.&quot;).</strong> — Nada a fazer no VCOSTAEDU: a inscrição está certa. Quem cuida do Moodle confere o envio de e-mail dele.</li>
<li><strong>O aluno não tem e-mail cadastrado: o Moodle exige um.</strong> — Cadastre o e-mail na ficha do aluno; o envio vai sozinho. Ou, em Conectar ao Moodle, escolha um modo com o e-mail da escola.</li>
<li><strong>O e-mail do cadastro do aluno é o mesmo de outro aluno (irmãos com o e-mail da família, por exemplo): a conta do Moodle é de uma pessoa só.</strong> — Cadastre um e-mail só do aluno na ficha; o envio vai sozinho. Ou, em Conectar ao Moodle, escolha um modo com o e-mail da escola.</li>
<li><strong>O modo de e-mail usa o e-mail da escola, mas o domínio dele não foi informado.</strong> — Em Conectar ao Moodle, informe o domínio do e-mail da escola (por exemplo, alunos.escola.exemplo.invalid) e salve.</li>
<li><strong>O e-mail está em mais de uma conta do Moodle: o VCOSTAEDU não escolhe sozinho qual é a da pessoa.</strong> — Em Envios ao Moodle, na linha do envio, escolha a conta (o número de cada uma no Moodle aparece ali); o envio segue com ela.</li>
<li><strong>Ligado a uma conta que já existia no Moodle, mas que está SUSPENSA lá: a pessoa não entra no Moodle enquanto a conta não for reativada.</strong> — Quem cuida do Moodle reativa a conta (Administração do site → Usuários). Nada a fazer no VCOSTAEDU.</li>
<li><strong>O Moodle não está conectado.</strong> — O Administrador conecta em Integrações → Conectar ao Moodle.</li>
<li><strong>A disciplina ainda não está ligada a uma do Moodle.</strong> — Com &quot;vincular&quot;, informe na tela da disciplina qual é a disciplina do Moodle; com &quot;criar&quot;, ela vai sozinha.</li>
<li><strong>Falta escolher a categoria do Moodle onde as disciplinas são criadas.</strong> — Em Conectar ao Moodle, escolha (ou crie) a categoria.</li>
</ul>
