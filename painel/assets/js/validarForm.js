/**
 * =============================================================================
 * ⚠️ `novalidate` NÃO DESLIGA A VALIDAÇÃO. Desliga só a bolha do navegador.
 * =============================================================================
 *
 * `form.checkValidity()` **ignora** o atributo `novalidate` — ele continua
 * avaliando os `required` e continua devolvendo `false`. E é esse `false` que o
 * ouvinte abaixo usa para cancelar o envio.
 *
 * O resultado, antes de 05/09/2026: quem tentava rematricular um aluno da 3ª
 * série apertava **Salvar** e não acontecia nada. Nada era gravado, nada era
 * dito. O `.was-validated` do Bootstrap pintava a borda de vermelho, e não há
 * um único `.invalid-feedback` escrito nas telas de matrícula para dizer o quê.
 * Uma recusa muda é indistinguível de um botão quebrado — e foram 23 alunos.
 *
 * A saída **não** foi tirar o `novalidate` (isso devolveria a bolha nativa, uma
 * de cada vez, e mudaria o comportamento de todo formulário da casa). Foi dizer
 * em voz alta o que a checagem já sabia:
 *
 *   1. um aviso no topo do formulário, nomeando os campos que faltam;
 *   2. um `.invalid-feedback` criado na hora, embaixo de cada campo;
 *   3. o cursor no primeiro campo que falta.
 *
 * ⓘ O problema P-29 de um projeto de referência.
 * =============================================================================
 */
$(() => {

    /** O nome que a pessoa vê, e não o `name` do HTML. */
    function rotuloDoCampo(campo) {
        const grupo = campo.closest('.form-group, .form-row, .mb-3, .modal-body') /* VCOSTAEDU: no Tabler o grupo é .mb-3 */;
        if (campo.id) {
            const l = document.querySelector('label[for="' + CSS.escape(campo.id) + '"]');
            if (l && l.textContent.trim()) { return l.textContent.trim().replace(/[:*]\s*$/, ''); }
        }
        if (grupo) {
            const l = grupo.querySelector('label');
            if (l && l.textContent.trim()) { return l.textContent.trim().replace(/[:*]\s*$/, ''); }
        }
        return campo.placeholder || campo.name || 'um campo obrigatório';
    }

    /** ⚠️ Bootstrap 4 só mostra `.invalid-feedback` se for IRMÃO do controle. */
    function dizerNoCampo(campo) {
        const pai = campo.parentElement;
        if (!pai) { return; }
        let aviso = pai.querySelector(':scope > .invalid-feedback.vc-gerado');
        if (!aviso) {
            aviso = document.createElement('div');
            aviso.className = 'invalid-feedback vc-gerado';
            campo.insertAdjacentElement('afterend', aviso);
        }
        aviso.textContent = campo.value === '' || campo.value === null
            ? 'Preencha este campo para continuar.'
            : mensagemInvalido(campo);
    }

    /** 1.14.0 (E9, D-338): campo PREENCHIDO mas mal formado não "falta preencher": diz o que está errado. */
    function mensagemInvalido(campo) {
        if (campo.validity && campo.validity.typeMismatch && campo.type === 'email') { return 'E-mail inválido.'; }
        return campo.validationMessage || 'Valor inválido.';
    }

    /** O aviso do topo, dentro do modal quando houver modal. */
    function dizerNoTopo(form, faltando, malPreenchidos) {
        const alvo = form.querySelector('.modal-body') || form;
        let caixa = alvo.querySelector(':scope > .vc-aviso-validacao');
        if (!caixa) {
            caixa = document.createElement('div');
            caixa.className = 'alert alert-warning vc-aviso-validacao';
            caixa.setAttribute('role', 'alert');
            alvo.insertAdjacentElement('afterbegin', caixa);
        }
        const partes = [];
        if (faltando.length === 1) { partes.push('Falta preencher <b>' + faltando[0] + '</b> para salvar.'); }
        if (faltando.length > 1) { partes.push('Faltam preencher, para salvar: <b>' + faltando.join('</b>, <b>') + '</b>.'); }
        (malPreenchidos || []).forEach((m) => partes.push('<b>' + m.rotulo + '</b>: ' + m.texto));
        caixa.innerHTML = partes.join('<br>');
    }

    $('form').on('submit', e => {
        const form = e.target;
        if (form.checkValidity() === false) {
            e.preventDefault();
            e.stopPropagation();

            const invalidos = [...form.elements]
                .filter((c) => c.willValidate && !c.checkValidity());

            invalidos.forEach(dizerNoCampo);
            /* 1.14.0 (D-338): "Falta preencher" só para o campo VAZIO; o mal preenchido diz o erro (ex.: "E-mail inválido.") */
            const vazios = invalidos.filter((c) => c.validity && c.validity.valueMissing);
            const mal = invalidos.filter((c) => !(c.validity && c.validity.valueMissing));
            dizerNoTopo(form, [...new Set(vazios.map(rotuloDoCampo))], mal.map((c) => ({ rotulo: rotuloDoCampo(c), texto: mensagemInvalido(c) })));

            if (invalidos[0]) {
                invalidos[0].focus();
                /* ⚠️ `scrollIntoView` num modal rola o modal, não a página —
                   e é o que se quer nos dois casos. */
                invalidos[0].scrollIntoView({ block: 'center', behavior: 'smooth' });
            }
        } else {
            const antigo = form.querySelector('.vc-aviso-validacao');
            if (antigo) { antigo.remove(); }
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
    $('.select2').select2({ width: '100%' });
    // $(".campomoney").inputmask( 'currency',{"autoUnmask": true,
    //     radixPoint:",",
    //     groupSeparator: ".",
    //     allowMinus: false,           
    //     digits: 2,
    //     digitsOptional: false,
    //     rightAlign: false,
    //     unmaskAsNumber: true
    // });

    $(".campomoney").inputmask("currency", {
        radixPoint: ",",
        groupSeparator: ".",
        allowMinus: false,
        digits: 2,
        digitsOptional: false,
        rightAlign: false,
        autoGroup: true,

        // importante para enviar "cru" no submit
        removeMaskOnSubmit: true,

        // você já usa:
        unmaskAsNumber: true
    });


    $('.select2').each(function () {
        $(this).select2({ width: '100%', dropdownParent: $(this).parent() });
    })

});