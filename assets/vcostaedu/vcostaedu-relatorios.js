/*
 * VCOSTAEDU — RELATÓRIOS (1.17.0, D-454). Carregado só nas telas de relatório (carregarRelatorio), depois
 * do vcostaedu-listas.js. O gráfico (Chart.js 2.9.4 local, MIT: assets/plugins/chart.js/LICENSE) e as colunas visíveis
 * do "Salvar".
 */
(function ($) {
  var cv = document.getElementById('graficoRelatorio');
  if (cv && window.Chart) {
    var g = JSON.parse(cv.getAttribute('data-grafico'));
    var cores = ['#dc3545', '#fd7e14', '#ffc107', '#17a2b8', '#6c757d', '#28a745'];
    var suf = g.sufixo || '';
    /* Onda 3, Etapa 10: o texto e as linhas do gráfico seguem o tema (o canvas não lê o CSS) */
    var escuro = document.documentElement.getAttribute('data-bs-theme') === 'dark';
    window.Chart.defaults.global.defaultFontColor = escuro ? '#C3CAD5' : '#3D3D3D';
    var grade = { color: escuro ? 'rgba(255, 255, 255, .12)' : 'rgba(0, 0, 0, .1)', zeroLineColor: escuro ? 'rgba(255, 255, 255, .3)' : 'rgba(0, 0, 0, .25)' };
    new window.Chart(cv.getContext('2d'), {
      type: g.tipo || 'bar',
      data: {
        labels: g.rotulos,
        datasets: g.series.map(function (s, i) {
          return { label: s.rotulo, data: s.dados, backgroundColor: s.cor || cores[i % cores.length] };
        })
      },
      options: {
        maintainAspectRatio: false,
        legend: { display: g.series.length > 1 },
        scales: {
          xAxes: [{ stacked: !!g.empilhado, gridLines: grade, ticks: { autoSkip: false } }],
          yAxes: [{ stacked: !!g.empilhado, gridLines: grade, ticks: { beginAtZero: true, max: g.maximo || undefined, callback: function (v) { return v + suf; } } }]
        },
        tooltips: { callbacks: { label: function (t, d) { return d.datasets[t.datasetIndex].label + ': ' + String(t.yLabel).replace('.', ',') + suf; } } }
      }
    });
  }
  /* Salvar: as colunas visíveis da tabela do relatório (vazio = todas) */
  $('#modalSalvar').on('show.bs.modal', function () {
    var $t = $('table.lista-dados[data-relatorio]').first();
    if (!$t.length || !$.fn.DataTable.isDataTable($t[0])) { return; }
    var dt = $t.DataTable(), vis = [], todas = true;
    dt.columns().every(function () {
      var ch = $(this.header()).data('chave');
      if (this.visible()) { vis.push(ch); } else { todas = false; }
    });
    $('#salvarColunas').val(todas ? '' : vis.join(','));
  });
})(window.jQuery);
