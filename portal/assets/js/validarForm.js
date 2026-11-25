$(() => {

    $('form').on('submit', e => {
        const form = e.target;
        if(form.checkValidity() === false) {
            e.preventDefault();
            e.stopPropagation();
        }else{
        $('.botaoEnvia').addClass('disabled');
        $('.botaoEnvia').val('Processando...');
        }
        $(form).addClass('was-validated');

        });

        $(".campocep").inputmask("99999-999");
        $(".camporg").inputmask("99.999.999-9");
        $(".campocpf").inputmask("999.999.999-99");
        $(".campocnpj").inputmask("99.999.999/9999-99");
        $(".campotelefone").inputmask("(99) 99999-9999");
        $(".campocelular").inputmask("(99) 99999-9999");
        $('.select2').select2({width: '100%'});
        $(".campomoney").inputmask( 'currency',{"autoUnmask": true,
            radixPoint:",",
            groupSeparator: ".",
            allowMinus: false,    
            digits: 2,
            digitsOptional: false,
            rightAlign: false,
            unmaskAsNumber: true
        });

        $('.select2').each(function() { 
            $(this).select2({width: '100%', dropdownParent: $(this).parent()});
        })
        
});
    