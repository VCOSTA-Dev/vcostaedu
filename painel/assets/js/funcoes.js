function previewImagem() {
  var imagem = document.querySelector('input[name=imagem_nova').files[0];
  var preview = document.querySelector('#preview-user');

  var reader = new FileReader();
  reader.onloadend = function () {
    preview.src = reader.result;
  };
  if (imagem) {
    reader.readAsDataURL(imagem);
  } else {
    preview.src = "";
  }
}

$(document).on('select2:open', () => {
  document.querySelector('.select2-search__field').focus();
});

$(function () {
  if ($.fn.tooltip) { $('[data-ttip="tooltip"]').tooltip(); } // VCOSTAEDU: no Tabler, a ponte jQuery do Bootstrap 5
})

/* VCOSTAEDU: abre um modal por JS — window.tabler.Modal (o bundle do Tabler não publica window.bootstrap); na falta
   dele, o plugin jQuery (a ponte do Bootstrap 5). */
function abrirModal(id) {
  var el = document.getElementById(id);
  if (!el) { return; }
  if (window.tabler && typeof window.tabler.Modal === 'function') {
    window.tabler.Modal.getOrCreateInstance(el).show();
  } else if (window.jQuery && window.jQuery.fn.modal) {
    window.jQuery(el).modal('show');
  }
}

function limpa_formulário_cep(n) {
  document.getElementById('rua' + n).value = ("");
  document.getElementById('bairro' + n).value = ("");
  document.getElementById('cidade' + n).value = ("");
  document.getElementById('uf' + n).value = ("");
}

async function pesquisacep(valor, n) {

  var cep = valor.replace(/\D/g, '');

  if (cep != "") {

    var validacep = /^[0-9]{8}$/;

    if (validacep.test(cep)) {

      document.getElementById('rua' + n).value = "...";
      document.getElementById('bairro' + n).value = "...";
      document.getElementById('cidade' + n).value = "...";
      document.getElementById('uf' + n).value = "...";

      const corpos = await fetch('https://viacep.com.br/ws/' + cep + '/json/');
      conteudo = await corpos.json();
      if (!("erro" in conteudo)) {
        document.getElementById('rua' + n).value = (conteudo.logradouro);
        document.getElementById('bairro' + n).value = (conteudo.bairro);
        document.getElementById('cidade' + n).value = (conteudo.localidade);
        document.getElementById('uf' + n).value = (conteudo.uf);
      } else {
        limpa_formulário_cep(n);
        alert("CEP não encontrado.");
      }

    } else {
      limpa_formulário_cep(n);
      alert("Formato de CEP inválido.");
    }
  } else {
    limpa_formulário_cep(n);
  }
};

if ($.fn.bootstrapDualListbox) { $('.duallistbox').bootstrapDualListbox(); } // VCOSTAEDU (D-05): o plugin saiu do layout Tabler

$("#apagarItem").on("show.bs.modal", function (event) {
  var button = $(event.relatedTarget);
  var identidade = button.data("identidade");
  var titulo = button.data("titulo");
  var confirm = button.data("confirm");
  var modal = $(this);
  modal.find(".modal-title").text(titulo);
  modal.find(".corpoItem").html(confirm);
  modal.find('.modal-body input[name="id"]').val(identidade);
});

$('#ordenacaoItem').on('show.bs.modal', function (event) {
  var button = $(event.relatedTarget);
  var identidade = button.data('identidade');
  var ordem = button.data('ordem');
  var ordenacao = button.data('ordenacao');
  var titulo = button.data('titulo');
  var confirm = button.data('confirm');
  var modal = $(this);
  modal.find('.modal-title').text(titulo);
  modal.find('.corpoItem').text(confirm);
  modal.find('.modal-body input[name="id"]').val(identidade);
  modal.find('.modal-body input[name="ordenacao"]').val(ordenacao);
  modal.find('.modal-body input[name="ordem"]').val(ordem);
});

async function liberacao(pg, id) {
  const botaoDrops = document.getElementById("btn" + pg + id);
  const btnOriginal = botaoDrops.innerHTML;
  botaoDrops.style.pointerEvents = 'none';
  botaoDrops.textContent = 'Processando...';
  const dadosDrop = await fetch("../../liberar/" + pg + "/" + id);
  const respostaDrop = await dadosDrop.json();
  if (respostaDrop['status']) {
    if (respostaDrop['situacao'] == 1) {
      botaoDrops.style.pointerEvents = '';
      botaoDrops.innerHTML = respostaDrop['botao'];
      document.getElementById("msgErroAlertaCaixa").innerHTML = respostaDrop['msg'];
    } else if (respostaDrop['situacao'] == 2) {
      botaoDrops.style.pointerEvents = '';
      botaoDrops.innerHTML = respostaDrop['botao'];
      document.getElementById("msgErroAlertaCaixa").innerHTML = respostaDrop['msg'];
    } else {
      botaoDrops.style.pointerEvents = '';
      botaoDrops.innerHTML = btnOriginal;
      document.getElementById("msgErroAlerta").innerHTML = respostaDrop['msg'];
      document.getElementById("msgErroAlertaCaixa").innerHTML = respostaDrop['msg'];
      abrirModal("errorAlertaMsg");
    }
  } else {
    botaoDrops.style.pointerEvents = '';
    botaoDrops.innerHTML = btnOriginal;
    document.getElementById("msgErroAlerta").innerHTML = respostaDrop['msg'];
    document.getElementById("msgErroAlertaCaixa").innerHTML = respostaDrop['msg'];
    abrirModal("errorAlertaMsg");
  }

}

async function liberacaoAcad(pg, id) {
  const botaoDrops = document.getElementById("btn" + pg + id);
  const btnOriginal = botaoDrops.innerHTML;
  botaoDrops.style.pointerEvents = 'none';
  botaoDrops.textContent = 'Processando...';
  const dadosDrop = await fetch("../../liberar-academico/" + pg + "/" + id);
  const respostaDrop = await dadosDrop.json();
  if (respostaDrop['status']) {
    if (respostaDrop['situacao'] == 1) {
      botaoDrops.style.pointerEvents = '';
      botaoDrops.innerHTML = respostaDrop['botao'];
      document.getElementById("msgErroAlertaCaixa").innerHTML = respostaDrop['msg'];
    } else if (respostaDrop['situacao'] == 2) {
      botaoDrops.style.pointerEvents = '';
      botaoDrops.innerHTML = respostaDrop['botao'];
      document.getElementById("msgErroAlertaCaixa").innerHTML = respostaDrop['msg'];
    } else {
      botaoDrops.style.pointerEvents = '';
      botaoDrops.innerHTML = btnOriginal;
      document.getElementById("msgErroAlerta").innerHTML = respostaDrop['msg'];
      document.getElementById("msgErroAlertaCaixa").innerHTML = respostaDrop['msg'];
      abrirModal("errorAlertaMsg");
    }
  } else {
    botaoDrops.style.pointerEvents = '';
    botaoDrops.innerHTML = btnOriginal;
    document.getElementById("msgErroAlerta").innerHTML = respostaDrop['msg'];
    document.getElementById("msgErroAlertaCaixa").innerHTML = respostaDrop['msg'];
    abrirModal("errorAlertaMsg");
  }

}

async function editLinhaMenu(id) {
  const dadosMenu = await fetch("../../nivel-acesso/busca-menu/" + id);
  const resposta = await dadosMenu.json();
  if (resposta['status']) {
    abrirModal("editarMenuPgNivel");
    $('#adms_menu_id').val(resposta['dados'][0]['adms_menu_id']).trigger('change');
    document.getElementById("idLinhaMenu").value = id;
    // document.getElementById("adms_menu_id").value.trigger = resposta['dados'][0]['adms_menu_id'];
    document.getElementById("permissao").value = resposta['dados'][0]['permissao'];
    document.getElementById("lib_menu").value = resposta['dados'][0]['lib_menu'];
    document.getElementById("dropdown").value = resposta['dados'][0]['dropdown'];
  } else {
    abrirModal("errorAlertaMsg");
  }
}

async function editLinhaMenuAcad(id) {
  const dadosMenu = await fetch("../../acesso-academico/busca-menu/" + id);
  const resposta = await dadosMenu.json();
  if (resposta['status']) {
    abrirModal("editarMenuPgAcad");
    $('#adms_menu_id').val(resposta['dados'][0]['adms_menu_id']).trigger('change');
    document.getElementById("idLinhaMenu").value = id;
    // document.getElementById("adms_menu_id").value.trigger = resposta['dados'][0]['adms_menu_id'];
    document.getElementById("permissao").value = resposta['dados'][0]['permissao'];
    document.getElementById("lib_menu").value = resposta['dados'][0]['lib_menu'];
    document.getElementById("dropdown").value = resposta['dados'][0]['dropdown'];
  } else {
    abrirModal("errorAlertaMsg");
  }
}

$('#editarFilial').on('show.bs.modal', function (event) {
  var button = $(event.relatedTarget);
  var identidade = button.data('identidade');
  var ordem = button.data('ordem');
  var titulo = button.data('titulo');
  var rua = button.data('rua');
  var cep = button.data('cep');
  var numero = button.data('numero');
  var bairro = button.data('bairro');
  var cidade = button.data('cidade');
  var estado = button.data('estado');
  var comp = button.data('comp');
  var nome = button.data('nome');
  var telefone1 = button.data('telefone1');
  var telefone2 = button.data('telefone2');
  var telefone3 = button.data('telefone3');
  var email1 = button.data('email1');
  var email2 = button.data('email2');
  var modal = $(this);
  modal.find('.modal-title').text(titulo);
  modal.find('.modal-body input[name="id"]').val(identidade);
  modal.find('.modal-body input[name="ordem"]').val(ordem);
  modal.find('.modal-body input[name="nome"]').val(nome);
  modal.find('.modal-body input[name="cep"]').val(cep);
  modal.find('.modal-body input[name="rua"]').val(rua);
  modal.find('.modal-body input[name="numero"]').val(numero);
  modal.find('.modal-body input[name="bairro"]').val(bairro);
  modal.find('.modal-body input[name="cidade"]').val(cidade);
  modal.find('.modal-body input[name="estado"]').val(estado);
  modal.find('.modal-body input[name="comp"]').val(comp);
  modal.find('.modal-body input[name="telefone1"]').val(telefone1);
  modal.find('.modal-body input[name="telefone2"]').val(telefone2);
  modal.find('.modal-body input[name="telefone3"]').val(telefone3);
  modal.find('.modal-body input[name="email1"]').val(email1);
  modal.find('.modal-body input[name="email2"]').val(email2);
});

$("#apagarFilialItem").on("show.bs.modal", function (event) {
  var button = $(event.relatedTarget);
  var identidade = button.data("identidade");
  var titulo = button.data("titulo");
  var confirm = button.data("confirm");
  var modal = $(this);
  modal.find(".modal-title").text(titulo);
  modal.find(".corpoItem").html(confirm);
  modal.find('.modal-body input[name="id"]').val(identidade);
});

$('#adicionarProfessor').on('show.bs.modal', function (event) {
  var button = $(event.relatedTarget);
  var identidade = button.data('identidade');
  var professores = button.data('professores');
  var corpo = button.data('corpo');
  var materia = button.data('materia');
  var modal = $(this);
  modal.find('.corpoItem').html(corpo);
  modal.find('.materiaItem').html(materia);
  modal.find('.modal-body input[name="rm_prof_mat_id"]').val(identidade);
  modal.find('.modal-body select[name="professores[]"]').val(professores).trigger("change");
})

$('#addCoordenadorCurso').on('show.bs.modal', function (event) {
  var button = $(event.relatedTarget);
  var curso = button.data('curso');
  var corpo = button.data('corpo');
  var modal = $(this);
  modal.find('.corpoItem').html(corpo);
  modal.find('.modal-body input[name="curso_id"]').val(curso);
})

$('#horarioSala').on('show.bs.modal', function (event) {
  var button = $(event.relatedTarget);
  var identidade = button.data('identidade');
  var sala = button.data('sala');
  var alunos = button.data('alunos');
  var corpos = button.data('corpos');
  var modal = $(this);
  modal.find('.corpoItem').text(corpos);
  modal.find('.modal-body input[name="rm_prof_mat_id"]').val(identidade);
  modal.find('.modal-body input[name="alunos"]').val(alunos);
  modal.find('.modal-body select[name="sala[]"]').val(sala).trigger("change");
})

async function btnHorarioAula(id, dia, campo) {
  const inputDia = document.getElementById("ipt" + dia + campo);
  if (inputDia.checked) {
    const dados = await fetch("../../horario-aula/editar/" + id + "?dia=" + dia + "&campo=" + campo + "&sit=1");
    const resposta = await dados.json();
    if (resposta['status']) {
      inputDia.checked = true;
    } else {
      inputDia.checked = false;
    }
    document.getElementById("msgAlertaCaixa").innerHTML = resposta['msg'];
  } else {
    const dados = await fetch("../../horario-aula/editar/" + id + "?dia=" + dia + "&campo=" + campo + "&sit=2");
    const resposta = await dados.json();
    if (resposta['status']) {
      inputDia.checked = false;
    } else {
      inputDia.checked = true;
    }
    document.getElementById("msgAlertaCaixa").innerHTML = resposta['msg'];
  }

}

$('#editCoordCurso').on('show.bs.modal', function (event) {
  var button = $(event.relatedTarget);
  var identidade = button.data('identidade');
  var curso = button.data('curso');
  var posicao = button.data('posicao');
  var titulo = button.data('titulo');
  var modal = $(this);
  modal.find('.modal-title').text(titulo);
  modal.find('.modal-body input[name="id"]').val(identidade);
  modal.find('.modal-body input[name="curso_id"]').val(curso);
  modal.find('.modal-body select[name="posicao_id"]').val(posicao).trigger("change");
})

$('#apagarCoordCurso').on('show.bs.modal', function (event) {
  var button = $(event.relatedTarget);
  var identidade = button.data('identidade');
  var corpo = button.data('corpo');
  var titulo = button.data('titulo');
  var modal = $(this);
  modal.find('.modal-title').text(titulo);
  modal.find(".corpoItem").html(corpo);
  modal.find('.modal-body input[name="id"]').val(identidade);
})

$('.bloqEnter').keypress(function (e) {
  if (e.which == 13) {
    e.preventDefault();
  }
});

$('#ausenciaCompensa').on('show.bs.modal', function (event) {
  var button = $(event.relatedTarget);
  var identidade = button.data('identidade');
  var aluno = button.data('aluno');
  var comp1 = button.data('comp1');
  var comp2 = button.data('comp2');
  var comp3 = button.data('comp3');
  var comp4 = button.data('comp4');
  var obs1 = button.data('obs1');
  var obs2 = button.data('obs2');
  var obs3 = button.data('obs3');
  var obs4 = button.data('obs4');
  var corpo = button.data('corpo');
  var modal = $(this);
  modal.find(".corpoItem").html(corpo);
  modal.find('.modal-body input[name="id"]').val(identidade);
  modal.find('.modal-body input[name="rm"]').val(aluno);
  modal.find('.modal-body input[name="c1"]').val(comp1);
  modal.find('.modal-body input[name="c2"]').val(comp2);
  modal.find('.modal-body input[name="c3"]').val(comp3);
  modal.find('.modal-body input[name="c4"]').val(comp4);
  modal.find('.modal-body textarea[name="obs1"]').val(obs1);
  modal.find('.modal-body textarea[name="obs2"]').val(obs2);
  modal.find('.modal-body textarea[name="obs3"]').val(obs3);
  modal.find('.modal-body textarea[name="obs4"]').val(obs4);
})

async function buscaSerie(es, pe) {
  const valorSelect = document.getElementById(es);
  const turmaserie = document.getElementById(pe);
  if (valorSelect.value > 0) {
    const dadosBusca = await fetch("../../buscar/turmaserie/" + valorSelect.value);
    const resposta = await dadosBusca.json();
    if (resposta['status']) {
      turmaserie.innerHTML = resposta['opcoes'];
    } else {
      turmaserie.innerHTML = resposta['opcoes'];
    }
  }
}
$('#ordemPortalAlunos').on('show.bs.modal', function (event) {
  var button = $(event.relatedTarget);
  var rm = button.data('rm');
  var curso = button.data('curso');
  var ano = button.data('ano');
  var periodo = button.data('periodo');
  var ordem = button.data('ordem');
  var corpo = button.data('corpo');
  var modal = $(this);
  modal.find(".corpoItem").html(corpo);
  modal.find('.modal-body input[name="rm"]').val(rm);
  modal.find('.modal-body input[name="ano"]').val(ano);
  modal.find('.modal-body input[name="curso_id"]').val(curso);
  modal.find('.modal-body input[name="periodo"]').val(periodo);
  modal.find('.modal-body input[name="ordem"]').val(ordem);
})

$('#cadObsPortalAlunos').on('show.bs.modal', function (event) {
  var button = $(event.relatedTarget);
  var rm = button.data('rm');
  var curso = button.data('curso');
  var ano = button.data('ano');
  var periodo = button.data('periodo');
  var obs = button.data('obs');
  var corpo = button.data('corpo');
  var modal = $(this);
  modal.find(".corpoItem").html(corpo);
  modal.find('.modal-body input[name="rm"]').val(rm);
  modal.find('.modal-body input[name="ano"]').val(ano);
  modal.find('.modal-body input[name="curso_id"]').val(curso);
  modal.find('.modal-body input[name="periodo"]').val(periodo);
  modal.find('.modal-body input[name="observacao"]').val(obs);
})

$('#enturmaPortalAluno').on('show.bs.modal', function (event) {
  var button = $(event.relatedTarget);
  var rm = button.data('rm');
  var disc = button.data('disc');
  var curso = button.data('curso');
  var ano = button.data('ano');
  var periodo = button.data('periodo');
  var ordem = button.data('ordem');
  var corpo = button.data('corpo');
  var modal = $(this);
  modal.find(".corpoItem").html(corpo);
  modal.find('.modal-body input[name="rm"]').val(rm);
  modal.find('.modal-body input[name="ano"]').val(ano);
  modal.find('.modal-body input[name="curso_id"]').val(curso);
  modal.find('.modal-body input[name="rm_prof_mat_id"]').val(disc);
  modal.find('.modal-body input[name="periodo"]').val(periodo);
  modal.find('.modal-body input[name="ordem"]').val(ordem);
})

async function horarioDisciplina(rm) {
  const pagina = document.getElementById('paginaConsulta');
  const dadosAprova = await fetch(pagina.value + rm);
  const resposta = await dadosAprova.json();
  if(resposta['status']){
    $("#cadDiaSemanaAula").modal("show");
    document.getElementById("DiscHorario").innerHTML = resposta['dados'];
  }else{
    $("#cadDiaSemanaAula").modal("show");
    document.getElementById("msgErro").innerHTML = resposta['msg'];
  }

}

