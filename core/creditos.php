<?php

/**
 * OS CRÉDITOS DAS BIBLIOTECAS DE INTERFACE (assets/plugins/), para a página "Sobre".
 *
 * As bibliotecas PHP (painel/vendor e portal/vendor) não estão aqui: a página as lê do installed.json do Composer,
 * que é a fonte delas. A licença de cada pasta está levantada em docs/ORIGEM.md; ao acrescentar ou tirar uma
 * biblioteca de assets/plugins/, mude esta lista e aquele documento juntos.
 *
 * Onda 3, Etapa 1: a versão não é escrita aqui — é LIDA do próprio arquivo da biblioteca ('arquivo', relativo à raiz),
 * pela expressão 'padrao' (o primeiro grupo é a versão), por VcLegal::versaoDaBiblioteca(). Trocar a biblioteca troca a
 * versão mostrada, sem esquecer esta lista.
 */

declare(strict_types=1);

return [
    ['nome' => 'Tabler', 'licenca' => 'MIT', 'pasta' => 'assets/plugins/tabler', 'arquivo' => 'assets/plugins/tabler/css/tabler.min.css', 'padrao' => '/Tabler v(\d+\.\d+\.\d+)/'],
    ['nome' => 'Font Awesome Free', 'licenca' => 'ícones CC BY 4.0, fontes SIL OFL 1.1, código MIT', 'pasta' => 'assets/plugins/fontawesome-free', 'arquivo' => 'assets/plugins/fontawesome-free/css/all.css', 'padrao' => '/Font Awesome Free (\d+\.\d+\.\d+)/'],
    ['nome' => 'jQuery', 'licenca' => 'MIT', 'pasta' => 'assets/plugins/jquery', 'arquivo' => 'assets/plugins/jquery/jquery.js', 'padrao' => '/jQuery JavaScript Library v(\d+\.\d+\.\d+)/'],
    ['nome' => 'jQuery UI', 'licenca' => 'MIT', 'pasta' => 'assets/plugins/jquery-ui', 'arquivo' => 'assets/plugins/jquery-ui/jquery-ui.css', 'padrao' => '/jQuery UI - v(\d+\.\d+\.\d+)/'],
    ['nome' => 'DataTables (com Buttons e a integração Bootstrap 5)', 'licenca' => 'MIT', 'pasta' => 'assets/plugins/datatables', 'arquivo' => 'assets/plugins/datatables/jquery.dataTables.js', 'padrao' => '/DataTables (\d+\.\d+\.\d+)/'],
    ['nome' => 'JSZip', 'licenca' => 'MIT ou GPLv3 (usada sob a MIT)', 'pasta' => 'assets/plugins/jszip', 'arquivo' => 'assets/plugins/jszip/jszip.js', 'padrao' => '/JSZip v(\d+\.\d+\.\d+)/'],
    ['nome' => 'pdfmake', 'licenca' => 'MIT', 'pasta' => 'assets/plugins/pdfmake', 'arquivo' => 'assets/plugins/pdfmake/pdfmake.js', 'padrao' => '/pdfmake v(\d+\.\d+\.\d+)/'],
    ['nome' => 'Select2', 'licenca' => 'MIT', 'pasta' => 'assets/plugins/select2', 'arquivo' => 'assets/plugins/select2/js/select2.full.js', 'padrao' => '/Select2 (\d+\.\d+\.\d+)/'],
    ['nome' => 'Inputmask', 'licenca' => 'MIT', 'pasta' => 'assets/plugins/inputmask', 'arquivo' => 'assets/plugins/inputmask/inputmask.js', 'padrao' => '/Version: (\d+\.\d+\.\d+)/'],
    ['nome' => 'jquery-tabledit', 'licenca' => 'MIT', 'pasta' => 'assets/plugins/jquery-tabledit_notas', 'arquivo' => 'assets/plugins/jquery-tabledit_notas/jquery.tabledit.js', 'padrao' => '/Tabledit v(\d+\.\d+\.\d+)/'],
    ['nome' => 'Chart.js', 'licenca' => 'MIT', 'pasta' => 'assets/plugins/chart.js', 'arquivo' => 'assets/plugins/chart.js/Chart.bundle.js', 'padrao' => '/Chart\.js v(\d+\.\d+\.\d+)/'],
    ['nome' => 'Summernote', 'licenca' => 'MIT', 'pasta' => 'assets/plugins/summernote', 'arquivo' => 'assets/plugins/summernote/summernote-bs5.min.js', 'padrao' => '/Summernote v(\d+\.\d+\.\d+)/'],
    ['nome' => 'Swagger UI', 'licenca' => 'Apache 2.0 (com o NOTICE da SmartBear)', 'pasta' => 'assets/plugins/swagger-ui', 'arquivo' => 'assets/plugins/swagger-ui/package.json', 'padrao' => '/"version":\s*"(\d+\.\d+\.\d+)"/'],
];
