/*
 * VCOSTAEDU — INICIALIZAÇÃO ÚNICA DAS LISTAS (E8c, R2). Painel e portal carregam este arquivo no rodapé.
 *
 * Marque a tabela com class="lista-dados" e, se precisar:
 *   data-titulo="…"        título dos arquivos exportados (padrão: o título da tela)
 *   data-ordem='[[1,"asc"]]'  ordenação inicial (padrão: a ordem do servidor)
 *   data-exportar="nao"    sem os botões Excel/CSV/PDF/Imprimir
 *   data-linhas="50"       linhas por página (padrão 25); data-paginar="nao" mostra tudo
 * Nas colunas (th): class="nao-exportar" (fica fora dos arquivos, ex.: Ações), "nao-ordenar", "num" (número à direita).
 * Nada de CDN: DataTables, Buttons, JSZip e pdfmake vêm de assets/plugins.
 */
(function ($) {
  if (!$ || !$.fn || !$.fn.DataTable) { return; }
  var PT = {
    decimal: ',', thousands: '.', emptyTable: 'Nenhum registro.', info: 'Mostrando _START_ a _END_ de _TOTAL_',
    infoEmpty: 'Nenhum registro', infoFiltered: '(filtrado de _MAX_)', lengthMenu: 'Mostrar _MENU_', loadingRecords: 'Carregando…',
    processing: 'Processando…', search: 'Pesquisar:', zeroRecords: 'Nada encontrado para a pesquisa.',
    paginate: { first: 'Primeira', last: 'Última', next: 'Próxima', previous: 'Anterior' },
    aria: { sortAscending: ': ordenar crescente', sortDescending: ': ordenar decrescente' },
    buttons: { copy: 'Copiar', print: 'Imprimir' }
  };
  /* 1.18.1 (D-482): ordenação de texto em português — sem diferença de acento nem de maiúscula ("Ética" junto do "E",
     não depois do "Z"), em TODAS as listas, como a collation do banco faz no servidor. */
  var semAcento = function (s) {
    s = (s === null || s === undefined) ? '' : String(s);
    return (s.normalize ? s.normalize('NFD').replace(/[\u0300-\u036f]/g, '') : s).toLowerCase();
  };
  var ordemTexto = $.fn.dataTable.ext.type.order;
  ordemTexto['string-pre'] = function (d) { return semAcento(d); };
  ordemTexto['html-pre'] = function (d) { return semAcento(String(d === null || d === undefined ? '' : d).replace(/<[^>]*>/g, '')); };
  var titulo = function ($t) {
    /* VCOSTAEDU: o título da tela é o .page-title do Tabler */
    return $t.data('titulo') || ($('.page-title').first().text() || document.title).trim();
  };
  var colunas = ':visible:not(.nao-exportar)';
  $('table.lista-dados').each(function () {
    var $t = $(this);
    if ($.fn.DataTable.isDataTable(this)) { return; }
    var exportar = String($t.data('exportar')) !== 'nao';
    var paginar = String($t.data('paginar')) !== 'nao';
    var nome = titulo($t);
    var botoes = !exportar ? [] : [
      { extend: 'excelHtml5', text: '<i class="far fa-file-excel"></i> <span class="rotulo">Excel</span>', titleAttr: 'Exportar para Excel', className: 'btn-sm btn-outline-success', title: nome, exportOptions: { columns: colunas } },
      { extend: 'csvHtml5', text: '<i class="fas fa-file-csv"></i> <span class="rotulo">CSV</span>', titleAttr: 'Exportar para CSV', className: 'btn-sm btn-outline-warning', title: nome, fieldSeparator: ';', bom: true, exportOptions: { columns: colunas } },
      { extend: 'pdfHtml5', text: '<i class="far fa-file-pdf"></i> <span class="rotulo">PDF</span>', titleAttr: 'Exportar para PDF', className: 'btn-sm btn-outline-danger', title: nome, orientation: $t.find('thead th').length > 6 ? 'landscape' : 'portrait', pageSize: 'A4', exportOptions: { columns: colunas } },
      { extend: 'print', text: '<i class="fas fa-print"></i> <span class="rotulo">Imprimir</span>', titleAttr: 'Imprimir a lista', className: 'btn-sm btn-outline-dark', title: nome, exportOptions: { columns: colunas } }
    ];
    var relatorio = $t.data('relatorio');
    if (relatorio && exportar) { botoes = relatorioBotoes($t, botoes); }
    var ordem = $t.data('ordem');
    $t.DataTable({
      dom: "<'row'<'col-sm-12 col-md-7'B><'col-sm-12 col-md-5'f>>" + "<'row'<'col-12'tr>>" + "<'row'<'col-sm-12 col-md-4'l><'col-sm-12 col-md-4'i><'col-sm-12 col-md-4'p>>",
      /* complemento do cliente: OUTLINE, fundo branco, pequenos, com ícone e dica (como no sistema escolar de origem). O buttons.bootstrap4
         põe "btn-secondary" (fundo escuro) em todo botão; aqui a classe base passa a ser só "btn" e o grupo não cola os botões. */
      buttons: { buttons: botoes, dom: { container: { className: 'dt-buttons' }, button: { className: 'btn' } } },
      language: PT,
      paging: paginar,
      pageLength: parseInt($t.data('linhas'), 10) || 25,
      lengthMenu: [[10, 25, 50, 100, -1], ['10', '25', '50', '100', 'Todos']],
      order: Array.isArray(ordem) ? ordem : [],
      autoWidth: false,
      responsive: false,
      columnDefs: [
        { orderable: false, targets: 'nao-ordenar' },
        { className: 'num', targets: 'num' },
        { visible: false, targets: 'oculta' }
      ]
    });
  });

  /*
   * 1.17.0 (D-454, D-457) — TABELAS DE RELATÓRIO (data-relatorio="R-…"): o mesmo inicializador, e mais:
   *   - no PDF, na impressão e no Excel, a data e hora de geração e os filtros (data-cabecalho) no topo; o rodapé (totais);
   *   - o botão Colunas (colVis; as colunas com class="oculta" começam escondidas e não vão para os arquivos);
   *   - AUDITORIA: antes de gerar o arquivo, a exportação é registrada no servidor (data-auditoria). Sem o registro,
   *     o arquivo não é gerado. A chamada é síncrona para que a impressão não seja bloqueada como pop-up.
   */
  function relatorioBotoes($t, botoes) {
    var formatos = { excelHtml5: 'excel', csvHtml5: 'csv', pdfHtml5: 'pdf', print: 'imprimir' };
    var topo = function () {
      var d = new Date(), p = function (n) { return (n < 10 ? '0' : '') + n; };
      return 'Gerado em ' + p(d.getDate()) + '/' + p(d.getMonth() + 1) + '/' + d.getFullYear() + ' ' + p(d.getHours()) + ':' + p(d.getMinutes())
        + '. ' + ($t.data('cabecalho') || '');
    };
    var registrar = function (formato, dt) {
      var x = new XMLHttpRequest();
      var corpo = 'relatorio=' + encodeURIComponent($t.data('relatorio')) + '&formato=' + formato
        + '&filtros=' + encodeURIComponent($t.attr('data-filtros') !== undefined ? $t.attr('data-filtros') : window.location.search.replace(/^\?/, '')) /* 1.17.4: os filtros efetivos, com os padrões */ + '&linhas=' + dt.rows({ search: 'applied' }).count();
      try {
        x.open('POST', $t.data('auditoria'), false);
        x.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        x.send(corpo);
        if (x.status === 200 && JSON.parse(x.responseText).ok === true) { return true; }
      } catch (e) { /* cai no aviso */ }
      window.alert('A exportação não pôde ser registrada (a sessão pode ter expirado). O arquivo não foi gerado.');
      return false;
    };
    var saida = botoes.map(function (b) {
      var formato = formatos[b.extend];
      var original = $.fn.dataTable.ext.buttons[b.extend] && $.fn.dataTable.ext.buttons[b.extend].action;
      var n = $.extend(true, {}, b, { footer: true });
      if (b.extend !== 'csvHtml5') { n.messageTop = topo; }
      n.action = function (e, dt, node, config) {
        if (original && registrar(formato, dt)) { original.call(this, e, dt, node, config); }
      };
      return n;
    });
    if ($.fn.dataTable.ext.buttons.colvis) {
      saida.push({ extend: 'colvis', text: '<i class="fas fa-columns"></i> <span class="rotulo">Colunas</span>', titleAttr: 'Escolher as colunas visíveis', className: 'btn-sm btn-outline-secondary', columns: ':not(.nao-exportar)' });
    }
    return saida;
  }
})(window.jQuery);
