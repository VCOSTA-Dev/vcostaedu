$(() => {

    $('form').on('submit', e => {
        const form = e.target;
        if(document.getElementById('senha_repetido')){
            verificarDiferenca(form, e);
        }else{
            if(form.checkValidity() === false) {
                e.preventDefault();
                e.stopPropagation();
            }else{
                $('.botaoEnvia').addClass('disabled');
                $('.botaoEnvia').val('Processando...');
            }
            $(form).addClass('was-validated');
        }
        
        });

});

function verificarDiferenca(form, e) {
    var val1 = document.getElementById('senha');
    var val2 = document.getElementById('senha_repetido');
    var repetir = document.getElementById('msgError');

    if (val1.value !== val2.value) {
        e.preventDefault();
        e.stopPropagation();
        form.classList.remove('was-validated');
        val1.classList.add('is-invalid');
        val2.classList.add('is-invalid');
        repetir.innerHTML = "<span class='text-danger'>Os campos de senha estão diferentes!</span>";
    }else{
        form.classList.add('was-validated')
    }

}

const campSenha = document.getElementById('senha');
const campRepeteSenha = document.getElementById('senha_repetido');
if(campSenha){
    campSenha.addEventListener('focus', () => {
        campSenha.classList.remove('is-invalid');
    });
}
if(campRepeteSenha){
    campRepeteSenha.addEventListener('focus', () => {
        campRepeteSenha.classList.remove('is-invalid');
    });
}