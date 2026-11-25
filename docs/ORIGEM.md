# Origem do código

Esta pasta começa com o código do sistema de origem na versão **1.18.4** (painel + portal, AdminLTE 3.2), copiado em 14/11/2026 **por lista**: só o que é versionado lá (`git ls-files`, 4.936 caminhos), menos o que a lista de exclusão abaixo tira. O `vendor/` do painel e do portal é versionado na origem e entrou junto. A cópia é o commit `bc647f9`.

## O que entrou (4.678 arquivos)

| caminho | arquivos |
|---|---|
| `.htaccess` | 1 |
| `assets/escola1` | 7 |
| `assets/fontes` | 6 |
| `assets/imgs` | 1 |
| `assets/marca` | 1 |
| `assets/pdf` | 26 |
| `assets/plugins` | 2090 |
| `core/.htaccess` | 1 |
| `core/MotorMedia.php` | 1 |
| `core/Requisicao.php` | 1 |
| `core/Transacao.php` | 1 |
| `database/.htaccess` | 1 |
| `database/conteudo.sql` | 1 |
| `database/ferramentas` | 20 |
| `database/schema.sql` | 1 |
| `database/seed_demo.sql` | 1 |
| `docs/guia` | 124 |
| `index.php` | 1 |
| `painel/.htaccess` | 1 |
| `painel/app` | 443 |
| `painel/assets` | 87 |
| `painel/composer.json` | 1 |
| `painel/composer.lock` | 1 |
| `painel/core` | 5 |
| `painel/index.php` | 1 |
| `painel/vendor` | 1535 |
| `portal/.htaccess` | 1 |
| `portal/app` | 85 |
| `portal/assets` | 68 |
| `portal/composer.json` | 1 |
| `portal/composer.lock` | 1 |
| `portal/core` | 4 |
| `portal/index.php` | 1 |
| `portal/vendor` | 77 |
| `tests/.htaccess` | 1 |
| `tests/comum` | 1 |
| `tests/jornada` | 16 |
| `tests/videos` | 63 |

## O que ficou de fora (258 arquivos)

| caminho | arquivos | motivo |
|---|---|---|
| `.gitignore` | 1 | da instância de origem; aqui há um próprio |
| `README.md` | 1 | da instância de origem; aqui há um próprio |
| `assets/instituicao/.gitkeep` | 1 | dado de execução: o instalador cria a pasta |
| `assets/instituicao/.htaccess` | 1 | dado de execução: o instalador cria a pasta |
| `assets/videos/guias/.htaccess` | 1 | dado de execução: o instalador cria a pasta |
| `database/atualizacoes/1.10.0_identidade.sql` | 1 | SQL de atualização por versão: esta pasta nasce na 1.18.4 (D-17) |
| `database/atualizacoes/1.11.0_matricula_frequencia.sql` | 1 | SQL de atualização por versão: esta pasta nasce na 1.18.4 (D-17) |
| `database/atualizacoes/1.12.0_historico_atestado.sql` | 1 | SQL de atualização por versão: esta pasta nasce na 1.18.4 (D-17) |
| `database/atualizacoes/1.13.0_motor_media.sql` | 1 | SQL de atualização por versão: esta pasta nasce na 1.18.4 (D-17) |
| `database/atualizacoes/1.14.0_chamada_importacao.sql` | 1 | SQL de atualização por versão: esta pasta nasce na 1.18.4 (D-17) |
| `database/atualizacoes/1.15.0_componente_obrigatorio.sql` | 1 | SQL de atualização por versão: esta pasta nasce na 1.18.4 (D-17) |
| `database/atualizacoes/1.15.1_herdar_memoria.sql` | 1 | SQL de atualização por versão: esta pasta nasce na 1.18.4 (D-17) |
| `database/atualizacoes/1.15.2_tela_regra.sql` | 1 | SQL de atualização por versão: esta pasta nasce na 1.18.4 (D-17) |
| `database/atualizacoes/1.16.0_catalogo_regras.sql` | 1 | SQL de atualização por versão: esta pasta nasce na 1.18.4 (D-17) |
| `database/atualizacoes/1.16.1_regra_do_curso.sql` | 1 | SQL de atualização por versão: esta pasta nasce na 1.18.4 (D-17) |
| `database/atualizacoes/1.16.2_divergencia_nota_minima.sql` | 1 | SQL de atualização por versão: esta pasta nasce na 1.18.4 (D-17) |
| `database/atualizacoes/1.17.0_relatorios.sql` | 1 | SQL de atualização por versão: esta pasta nasce na 1.18.4 (D-17) |
| `database/atualizacoes/1.17.1_relatorios_onda2.sql` | 1 | SQL de atualização por versão: esta pasta nasce na 1.18.4 (D-17) |
| `database/atualizacoes/1.17.2_encontros_previstos.sql` | 1 | SQL de atualização por versão: esta pasta nasce na 1.18.4 (D-17) |
| `database/atualizacoes/1.17.3_sinal_frequencia.sql` | 1 | SQL de atualização por versão: esta pasta nasce na 1.18.4 (D-17) |
| `database/atualizacoes/1.17.4_frequencia_filtro.sql` | 1 | SQL de atualização por versão: esta pasta nasce na 1.18.4 (D-17) |
| `database/atualizacoes/1.18.0_videos_guias.sql` | 1 | SQL de atualização por versão: esta pasta nasce na 1.18.4 (D-17) |
| `database/atualizacoes/1.18.1_correcoes_videos.sql` | 1 | SQL de atualização por versão: esta pasta nasce na 1.18.4 (D-17) |
| `database/atualizacoes/1.18.2_seguranca_senhas.sql` | 1 | SQL de atualização por versão: esta pasta nasce na 1.18.4 (D-17) |
| `database/atualizacoes/1.18.3_permissoes.sql` | 1 | SQL de atualização por versão: esta pasta nasce na 1.18.4 (D-17) |
| `database/atualizacoes/1.18.4_atualizar_dados.sql` | 1 | SQL de atualização por versão: esta pasta nasce na 1.18.4 (D-17) |
| `database/atualizacoes/1.7.2_menu.sql` | 1 | SQL de atualização por versão: esta pasta nasce na 1.18.4 (D-17) |
| `database/atualizacoes/1.8.0_usabilidade.sql` | 1 | SQL de atualização por versão: esta pasta nasce na 1.18.4 (D-17) |
| `database/atualizacoes/1.9.0_decisoes_e_editor.sql` | 1 | SQL de atualização por versão: esta pasta nasce na 1.18.4 (D-17) |
| `database/atualizacoes/1.9.1_endereco.sql` | 1 | SQL de atualização por versão: esta pasta nasce na 1.18.4 (D-17) |
| `docs/.htaccess` | 1 | documento de trabalho da origem |
| `docs/ATUALIZAR_SERVIDOR.md` | 1 | documento de trabalho da origem |
| `docs/CHECKLIST_ENTREGA.md` | 1 | documento de trabalho da origem |
| `docs/DECISIONS.md` | 1 | documento de trabalho da origem |
| `docs/ENTREGA.md` | 1 | documento de trabalho da origem |
| `docs/IMPLANTACAO.md` | 1 | documento de trabalho da origem |
| `docs/LEVANTAMENTO_DOCUMENTOS_FREQUENCIA_MEDIA.md` | 1 | documento de trabalho da origem |
| `docs/LEVANTAMENTO_RECORTE.md` | 1 | documento de trabalho da origem |
| `docs/NEXT_TASK.md` | 1 | documento de trabalho da origem |
| `docs/PEDIDO_CLIENTE.md` | 1 | documento de trabalho da origem |
| `docs/PERGUNTAS_CLIENTE.md` | 1 | documento de trabalho da origem |
| `docs/PERMISSOES.md` | 1 | documento de trabalho da origem |
| `docs/PROJECT_STATE.md` | 1 | documento de trabalho da origem |
| `docs/RELATORIOS.md` | 1 | documento de trabalho da origem |
| `docs/ROTEIRO_DEMO.md` | 1 | documento de trabalho da origem |
| `docs/SESSION_HANDOFF.md` | 1 | documento de trabalho da origem |
| `docs/TABELAS_RECORTE.md` | 1 | documento de trabalho da origem |
| `docs/USABILIDADE_COMPONENTES.md` | 1 | documento de trabalho da origem |
| `docs/USABILIDADE_INVENTARIO.md` | 1 | documento de trabalho da origem |
| `docs/USABILIDADE_JORNADAS.md` | 1 | documento de trabalho da origem |
| `docs/USABILIDADE_PROPOSTA.md` | 1 | documento de trabalho da origem |
| `docs/provas/` | 198 | documento de trabalho da origem |
| `docs/retornos/` | 5 | documento de trabalho da origem |
| `retorno.md` | 1 | da instância de origem; aqui há um próprio |
| `storage/.htaccess` | 1 | dado de execução: o instalador cria a pasta |
| `storage/historico/assinaturas/.gitkeep` | 1 | dado de execução: o instalador cria a pasta |
| `storage/historico/emissoes/.gitkeep` | 1 | dado de execução: o instalador cria a pasta |

Também não entraram (não são versionados na origem): os arquivos de configuração com credencial, `graphify-out/`, os `prompt-*.md`, pacotes, backups, logs, `node_modules/`, as saídas dos testes e os vídeos gerados.

## Higiene feita na cópia (antes do primeiro commit do código)

| o quê | onde | troca |
|---|---|---|
| nome do banco da instância de origem nas travas do SQL | `database/schema.sql:58-62`, `database/conteudo.sql:5-7`, `database/seed_demo.sql:20` | `'vcostaedu'` |
| idem, na troca da trava pelo `DBNAME` | `database/ferramentas/implantacao/instalar_banco.php:9,41` | idem |
| idem, nas Configs de exemplo | `painel/core/Config.example.php:51,71`, `portal/core/Config.example.php:60` | `vcostaedu`, `vcostaedu_demo` |
| banco e pasta da demonstração de origem | `tests/videos/README.md:30-37`, `tests/videos/lib.js:16` | `vcostaedu_demo` e a pasta vizinha `vcostaedu-demo` (hoje, do ambiente: `VCOSTAEDU_VIDEOS_RAIZ`) |
| endereço local da origem nos exemplos de uso | `database/ferramentas/guias/capturar.php:15`, `database/ferramentas/seed_demo_processar.php:29,51`, `tests/jornada/lib.js:12`, `tests/jornada/README.md:33` | `http://localhost/vcostaedu/` |
| e-mail em linha comentada | `painel/app/adms/Models/helper/AdmsSendPhpMailer.php:32` e o mesmo no portal | `suporte@exemplo.invalid` |

Varrido e **não encontrado** fora das bibliotecas de terceiros: CPF (só a máscara `999.999.999-99` do `validarForm.js`), e-mail de pessoa (os do código são `@exemplo.invalid` e `@prova.invalid`), IP real (só exemplos de rede privada nos comentários das Configs de exemplo), caminho de servidor, a sigla e o nome da instituição de origem. Os nomes de pessoas da demonstração são fictícios (`database/seed_demo.sql`, `database/ferramentas/seed_demo_processar.php`).

Ficou e está registrado em `docs/DUVIDAS.md`:
- o e-mail institucional da VCOSTA como autor nos `composer.json` do painel e do portal (é da empresa, não de pessoa);
- menções em comentário a outros projetos da casa que serviram de base (o sistema escolar de origem: 12 arquivos, 21 ocorrências; o sistema de origem de ensino superior: 8 e 17; o projeto de referência: 22 e 27; a "ficha financeira": 1);
- os caminhos padrão do XAMPP (`C:/xampp/mysql/bin/…`) como valor de reserva nas ferramentas de linha de comando (`database/ferramentas/implantacao/_base.php:181`, `limpar_demo.php:44`, `tests/videos/banco.php:73`, `tests/videos/lib.js:19`): são o padrão do XAMPP, não caminho de servidor.

## Cabeçalhos de copyright em `painel/`, `portal/` e `core/` (listados, sem mexer)

Fora de `vendor/`, só os da própria VCOSTA, nas telas:
- `painel/app/adms/Views/acesso/login.php:50` e `esqueceuSenha.php:43`: "Copyright © 2019-<ano> VCOSTA®. All rights reserved." — **contradiz a AGPL** do repositório; a tela de login é reescrita na Etapa 3 e a frase sai (`docs/DUVIDAS.md`);
- `painel/app/adms/Views/include/footer.php:7`, `portal/app/adms/Views/include/footer.php:8`, `portal/app/adms/Views/acesso/login.php:122`, `esqueceuSenha.php:112`, `atualizaSenha.php:112`: "Copyright © 2019-<ano> VCOSTA".

Nenhum cabeçalho de copyright de terceiro em arquivo da aplicação (`grep -rniE "copyright|\(c\) [0-9]{4}|©"` em `painel/app`, `portal/app`, `core`, `painel/core`, `portal/core`).

## Licença de cada pasta de `assets/plugins/` e `vendor/`

Lida do arquivo `LICENSE`/`COPYING` da pasta; sem ele, do `composer.json`/`package.json`; sem eles, do cabeçalho do primeiro arquivo de código ("(cabeçalho)"). Nenhuma é incompatível com a AGPL-3.0 do código; a que pede atenção é a do Font Awesome (ícones CC BY 4.0, que exige manter a atribuição no CSS). As pastas de plugin que saem na Etapa 3 deixam de ser distribuídas.

| pasta | licença |
|---|---|
| `assets/plugins/bootstrap-colorpicker/` | MIT (cabeçalho) |
| `assets/plugins/bootstrap-slider/` | MIT (cabeçalho) |
| `assets/plugins/bootstrap-switch/` | Apache (cabeçalho) |
| `assets/plugins/bootstrap/` | MIT (cabeçalho) |
| `assets/plugins/bootstrap4-duallistbox/` | Apache (cabeçalho) |
| `assets/plugins/bs-custom-file-input/` | MIT (cabeçalho) |
| `assets/plugins/bs-stepper/` | MIT (cabeçalho) |
| `assets/plugins/chart.js/` | MIT |
| `assets/plugins/codemirror/` | MIT (cabeçalho) |
| `assets/plugins/datatables-autofill/` | não identificada |
| `assets/plugins/datatables-bs4/` | não identificada |
| `assets/plugins/datatables-buttons/` | não identificada |
| `assets/plugins/datatables-colreorder/` | não identificada |
| `assets/plugins/datatables-fixedcolumns/` | não identificada |
| `assets/plugins/datatables-fixedheader/` | MIT (cabeçalho) |
| `assets/plugins/datatables-keytable/` | MIT (cabeçalho) |
| `assets/plugins/datatables-responsive/` | MIT (cabeçalho) |
| `assets/plugins/datatables-rowgroup/` | MIT (cabeçalho) |
| `assets/plugins/datatables-rowreorder/` | MIT (cabeçalho) |
| `assets/plugins/datatables-scroller/` | MIT (cabeçalho) |
| `assets/plugins/datatables-searchbuilder/` | mit (cabeçalho) |
| `assets/plugins/datatables-searchpanes/` | não identificada |
| `assets/plugins/datatables-select/` | mit (cabeçalho) |
| `assets/plugins/datatables/` | MIT (cabeçalho) |
| `assets/plugins/daterangepicker/` | MIT (cabeçalho) |
| `assets/plugins/dropzone/` | não identificada |
| `assets/plugins/ekko-lightbox/` | não identificada |
| `assets/plugins/fastclick/` | mit (cabeçalho) |
| `assets/plugins/filterizr/` | MIT (cabeçalho) |
| `assets/plugins/flag-icon-css/` | não identificada |
| `assets/plugins/flot/` | GPL (cabeçalho) |
| `assets/plugins/fontawesome-free/` | CC BY (cabeçalho) |
| `assets/plugins/fontawesome-free_old/` | CC BY (cabeçalho) |
| `assets/plugins/fullcalendar/` | MIT |
| `assets/plugins/icheck-bootstrap/` | MIT |
| `assets/plugins/inputmask/` | MIT (cabeçalho) |
| `assets/plugins/ion-rangeslider/` | MIT |
| `assets/plugins/jquery-knob/` | não identificada |
| `assets/plugins/jquery-mapael/` | MIT (cabeçalho) |
| `assets/plugins/jquery-mousewheel/` | mit |
| `assets/plugins/jquery-tabledit/` | MIT |
| `assets/plugins/jquery-tabledit_notas/` | MIT |
| `assets/plugins/jquery-ui/` | mit |
| `assets/plugins/jquery-validation/` | MIT (cabeçalho) |
| `assets/plugins/jquery/` | MIT (cabeçalho) |
| `assets/plugins/jqvmap/` | não identificada |
| `assets/plugins/jsgrid/` | não identificada |
| `assets/plugins/jszip/` | MIT (cabeçalho) |
| `assets/plugins/moment/` | não identificada |
| `assets/plugins/overlayScrollbars/` | MIT (cabeçalho) |
| `assets/plugins/pace-progress/` | iSc (cabeçalho) |
| `assets/plugins/pdfmake/` | MIT (cabeçalho) |
| `assets/plugins/popper/` | mit (cabeçalho) |
| `assets/plugins/raphael/` | MIT |
| `assets/plugins/select2-bootstrap4-theme/` | não identificada |
| `assets/plugins/select2/` | não identificada |
| `assets/plugins/swagger-ui/` | Apache 2.0 (`LICENSE` e `NOTICE` na pasta; só os arquivos usados de `swagger-ui-dist` 5.17.14: o bundle e o CSS — Onda 2, Etapa 6, a documentação da API servida localmente) |
| `assets/plugins/select2_old/` | não identificada |
| `assets/plugins/sparklines/` | não identificada |
| `assets/plugins/summernote/` | MIT (cabeçalho) |
| `assets/plugins/sweetalert2-theme-bootstrap-4/` | MIT (cabeçalho) |
| `assets/plugins/sweetalert2/` | MIT (cabeçalho) |
| `assets/plugins/tempusdominus-bootstrap-4/` | MIT (cabeçalho) |
| `assets/plugins/toastr/` | não identificada |
| `assets/plugins/uplot/` | MIT (cabeçalho) |
| `painel/vendor/chillerlan/php-qrcode/` | MIT |
| `painel/vendor/chillerlan/php-settings-container/` | MIT |
| `painel/vendor/dompdf/dompdf/` | GNU LESSER |
| `painel/vendor/firebase/php-jwt/` | mit |
| `painel/vendor/guzzlehttp/guzzle/` | mit |
| `painel/vendor/guzzlehttp/promises/` | mit |
| `painel/vendor/guzzlehttp/psr7/` | mit |
| `painel/vendor/masterminds/html5/` | PHP License |
| `painel/vendor/microsoft/azure-storage/` | MIT |
| `painel/vendor/microsoft/windowsazure/` | Apache License |
| `painel/vendor/pear/console_getopt/` | mit |
| `painel/vendor/pear/http_request2/` | Mozilla Public (cabeçalho) |
| `painel/vendor/pear/mail_mime-decode/` | "BSD-2-Clause" |
| `painel/vendor/pear/mail_mime/` | "BSD-3-clause" |
| `painel/vendor/pear/net_url2/` | "BSD-3-Clause" |
| `painel/vendor/pear/pear-core-minimal/` | "BSD-3-Clause" |
| `painel/vendor/pear/pear_exception/` | mit |
| `painel/vendor/phenx/php-font-lib/` | GNU LESSER |
| `painel/vendor/phenx/php-svg-lib/` | GNU LESSER |
| `painel/vendor/phpmailer/phpmailer/` | GNU LESSER |
| `painel/vendor/psr/http-message/` | mit |
| `painel/vendor/ralouphie/getallheaders/` | MIT |
| `painel/vendor/sabberworm/php-css-parser/` | MIT |
| `painel/vendor/symfony/polyfill-intl-idn/` | mit |
| `painel/vendor/symfony/polyfill-intl-normalizer/` | mit |
| `painel/vendor/symfony/polyfill-php72/` | mit |
| `portal/vendor/phpmailer/phpmailer/` | GNU LESSER |
