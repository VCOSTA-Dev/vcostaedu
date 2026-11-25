/*
 * E1b (D-353): ARRASTAR PARA ORDENAR as disciplinas da grade (jQuery UI sortable, local).
 * Cada <tbody class="ordenavel"> vira uma lista arrastável pela alça (.alca-ordem). Ao soltar, os números são
 * refeitos na ordem da página e o campo #ordemDisciplinas recebe os ids das linhas (data-linha) nessa ordem; o botão
 * "Salvar a ordem" fica ativo. Nada é gravado sem o clique (um POST só, com a ordem inteira).
 */
(function ($) {
    'use strict';
    $(function () {
        var $listas = $('tbody.ordenavel');
        var $campo = $('#ordemDisciplinas'), $btn = $('#btnSalvarOrdem'), $aviso = $('#avisoOrdem');
        if (!$listas.length || !$campo.length || !$.fn.sortable) {
            return;
        }
        var atualizar = function () {
            var ids = [];
            $('tbody.ordenavel tr.linhaMatriz').each(function (i) {
                ids.push($(this).data('linha'));
                $(this).find('.num-ordem').text(i + 1);
            });
            $campo.val(ids.join(','));
            return ids;
        };
        atualizar();
        var inicial = $campo.val();
        $listas.sortable({
            items: 'tr.linhaMatriz',
            handle: '.alca-ordem',
            axis: 'y',
            cursor: 'move',
            helper: function (e, tr) {
                /* mantém a largura das colunas enquanto a linha é arrastada */
                var $orig = tr.children(), $h = tr.clone();
                $h.children().each(function (i) { $(this).width($orig.eq(i).width()); });
                return $h;
            },
            update: function () {
                atualizar();
                var mudou = $campo.val() !== inicial;
                $btn.prop('disabled', !mudou);
                $aviso.text(mudou ? 'Ordem alterada: clique em "Salvar a ordem" para gravar.' : 'Arraste as disciplinas pela alça para a ordem do currículo. É a ordem do histórico.');
            }
        });
    });
})(jQuery);
