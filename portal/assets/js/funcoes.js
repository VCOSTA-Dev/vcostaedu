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