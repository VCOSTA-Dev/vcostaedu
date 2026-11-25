if (typeof tabelaNome == "undefined") {
  var tabelaNome = "tabelaLancaNotas"
}
if (typeof campos == "undefined") {
  var campos = [];
}
if (typeof envio == "undefined") {
  var envio = "";
}
$("#" + tabelaNome).DataTable({
  dom: "Bfltip",
  buttons: [
    {
      extend: "excelHtml5",
      text: '<i class="far fa-file-excel"></i>',
      titleAttr: "Excel",
      className: "btn btn-outline-success",
    },
    {
      extend: "csvHtml5",
      text: '<i class="fas fa-file-csv"></i>',
      titleAttr: "CSV",
      className: "btn btn-outline-warning",
    },
    {
      extend: "pdfHtml5",
      text: '<i class="far fa-file-pdf"></i>',
      titleAttr: "PDF",
      className: "btn btn-outline-danger",
    },
  ],
  "paging":   false,
  "ordering": false,
  "info":     false,
  autoWidth: false,
  responsive: true,
});

/* VCOSTAEDU (Etapa 5): sem a tabela na tela (atividade sem alunos para lançar), o plugin lançava "Tabledit only works
   when applied to a table." — erro herdado da origem. Só liga quando a tabela existe. */
if (document.getElementById(tabelaNome)) {
$("#"+tabelaNome).Tabledit({
  url: "../../"+envio,
  editButton: false,
  deleteButton: false,
  hideIdentifier: true,
  columns: {
    identifier: [0, "id"],
    editable: campos,
  },
  /* 1.18.2 (D-490): a linha recusada fica LEGÍVEL — "table-danger" (fundo claro, texto escuro, como na tela de Regra de
     média), não "bg-danger" (texto branco que a listra da tabela deixava ilegível) — e a célula VOLTA ao valor gravado
     (antes, ficava mostrando o valor recusado, que não foi salvo). */
  onSuccess: function (data, textStatus, jqXHR) {
    var $linha = $("#" + data["valor"]["id"]);
    if (data["erro"] == false) {
      if (data["msg"]) {
        $("#ValorErradoMsg").text(data["msg"]);
      }
      $("#ValorErrado").modal("show");
      $linha.removeClass("bg-danger").addClass("table-danger linha-recusada").attr("title", data["msg"] || "Valor recusado: nada foi gravado.");
      if (data["anterior"] !== undefined) {
        $.each(campos, function (i, c) {
          var $inp = $linha.find('.tabledit-input[name="' + c[1] + '"]');
          $inp.val(data["anterior"]);
          $inp.siblings(".tabledit-span").text(data["anterior"]);
        });
      }
    } else {
      $linha.removeClass("bg-danger table-danger linha-recusada").removeAttr("title");
    }
  },
});
}
