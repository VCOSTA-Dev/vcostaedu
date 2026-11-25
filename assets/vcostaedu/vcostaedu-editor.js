/*
 * EDITOR PADRÃO da VCOSTAEDU (E8d, A24; regra R10 do CHECKLIST_ENTREGA): todo texto que o usuário formata usa
 * este editor — nunca HTML digitado à mão.
 *
 * - Summernote 0.8.20 (MIT), LOCAL (assets/plugins/summernote), com a configuração do editor de contratos do
 *   projeto de referência: pt-BR, altura 520, estilos, negrito/itálico/sublinhado/limpar, listas, parágrafo, tabela, tela cheia.
 *   Diferenças registradas: sem o botão de link (o documento não leva endereço externo), "Ver código" só
 *   para o administrador, e COLAR entra sempre como texto simples.
 * - Painel de variáveis: um clique insere no ponto do cursor ({{VARIAVEL}}), com busca.
 * - BLOCOS do sistema: marcadores não editáveis (span contenteditable=false data-bloco) que voltam a
 *   {{BLOCO}} ao enviar. O servidor limpa tudo de novo (lista branca) e recusa o que for perigoso.
 */
(function ($) {
    'use strict';
    var $campoEditor = $('.editor-padrao').first();
    if (!$campoEditor.length || !$.fn.summernote) {
        return;
    }
    var cfg = {};
    try { cfg = JSON.parse($('#configEditor').text() || '{}'); } catch (e) { cfg = {}; }
    var blocos = cfg.blocos || {};
    var $campo = $('#' + $campoEditor.data('campo'));
    var $form = $campo.closest('form');

    function esc(t) {
        return String(t).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
    function marcador(nome) {
        return '<span class="bloco-sistema" contenteditable="false" data-bloco="' + esc(nome) + '">[' + esc(blocos[nome]) + ']</span>';
    }
    /* {{BLOCO}} → marcador (as variáveis comuns ficam como texto) */
    function paraEditor(html) {
        return String(html || '').replace(/\{\{\s*([A-Z_]+)\s*\}\}/g, function (m, nome) {
            return blocos[nome] ? marcador(nome) : m;
        });
    }
    /* marcador → {{BLOCO}}, lido num documento inerte (nada do conteúdo executa ou carrega) */
    function paraServidor(html) {
        var doc = document.implementation.createHTMLDocument('');
        var raiz = doc.createElement('div');
        raiz.innerHTML = html;
        Array.prototype.forEach.call(raiz.querySelectorAll('span[data-bloco]'), function (s) {
            s.parentNode.replaceChild(doc.createTextNode('{{' + s.getAttribute('data-bloco') + '}}'), s);
        });
        return raiz.innerHTML;
    }
    /* texto colado: escapado, linhas em <br>, e o texto de um marcador recortado volta a ser marcador */
    function textoColado(t) {
        var html = esc(t).replace(/\r?\n/g, '<br>');
        Object.keys(blocos).forEach(function (nome) {
            html = html.split('[' + esc(blocos[nome]) + ']').join(marcador(nome));
        });
        return html;
    }

    var visao = [['style', ['style']], ['font', ['bold', 'italic', 'underline', 'clear']], ['para', ['ul', 'ol', 'paragraph']], ['table', ['table']]];
    visao.push(['view', ['fullscreen']]); // Onda 3, Etapa 3: o "Ver código" saiu (o modelo se edita pelos blocos, não pelo HTML)
    $campoEditor.summernote({
        lang: 'pt-BR',
        /* VCOSTAEDU (Etapa 4): sem as dicas da barra. O Summernote as cria com o $.fn.tooltip do jQuery na hora de
           montar a barra — antes de a ponte jQuery do Bootstrap 5 do Tabler existir —, e a barra ficava vazia
           (achado do projeto de referência, que fez o mesmo: tooltip:false, sem shim). O title de cada botão continua. */
        tooltip: false,
        height: 520,
        styleWithCSS: false,
        toolbar: visao,
        styleTags: ['p', 'h1', 'h2', 'h3', 'h4'],
        codeviewFilter: true,
        codeviewIframeFilter: true,
        disableDragAndDrop: true,
        callbacks: {
            onPaste: function (e) {
                var dados = (e.originalEvent || e).clipboardData || window.clipboardData;
                if (!dados) {
                    return;
                }
                e.preventDefault();
                $campoEditor.summernote('pasteHTML', textoColado(dados.getData('text/plain') || dados.getData('Text') || ''));
            }
        }
    });
    $campoEditor.summernote('code', paraEditor($campo.val()));
    /* VCOSTAEDU (Etapa 4): o build bs5 do Summernote 0.8.20 ainda monta os menus da barra (Estilo, Cor) com
       data-toggle="dropdown", que o Bootstrap 5 não reconhece, e os diálogos com o form-group do 4. Traduz na hora,
       só dentro do editor e dos diálogos dele. */
    var $notaEditor = $campoEditor.next('.note-editor');
    $notaEditor.add($('.note-modal')).find('[data-toggle]').each(function () {
        this.setAttribute('data-bs-toggle', this.getAttribute('data-toggle'));
        this.removeAttribute('data-toggle');
    });
    $notaEditor.add($('.note-modal')).find('.form-group').removeClass('form-group').addClass('mb-3');
    $notaEditor.add($('.note-modal')).find('.form-control-file').removeClass('form-control-file').addClass('form-control'); /* o arquivo da imagem (Etapa 7) */
    var $editavel = $notaEditor.find('.note-editable');
    $editavel.on('keyup mouseup focusout', function () { $campoEditor.summernote('editor.saveRange'); });

    /* painel de variáveis: insere no cursor */
    $(document).on('click', '.btn-var', function () {
        var nome = String($(this).data('var'));
        $campoEditor.summernote('editor.restoreRange');
        $campoEditor.summernote('editor.focus');
        if (blocos[nome]) {
            $campoEditor.summernote('pasteHTML', marcador(nome));
        } else {
            $campoEditor.summernote('editor.insertText', '{{' + nome + '}}');
        }
        $campoEditor.summernote('editor.saveRange');
    });
    /* E5 (D-366): início/fim de um trecho condicional da variável escolhida (marcador, como o do TCC) */
    $(document).on('click', '.btn-cond', function () {
        var nome = String($(this).data('lado')) + String($('#varCondicional').val() || '');
        if (!blocos[nome]) {
            return;
        }
        $campoEditor.summernote('editor.restoreRange');
        $campoEditor.summernote('editor.focus');
        $campoEditor.summernote('pasteHTML', marcador(nome));
        $campoEditor.summernote('editor.saveRange');
    });
    $('#buscaVariavel').on('input', function () {
        var q = $(this).val().toLowerCase().trim();
        var algum = false;
        $('#catalogoVariaveis .grupo-var').each(function () {
            var visiveis = 0;
            $(this).find('.btn-var').each(function () {
                var ok = q === '' || String($(this).data('busca')).indexOf(q) !== -1;
                this.hidden = !ok;
                visiveis += ok ? 1 : 0;
            });
            this.hidden = visiveis === 0;
            algum = algum || visiveis > 0;
        });
        $('#semVariavel').prop('hidden', algum);
    });

    /* aba Quadro: ordem pelas setas */
    $(document).on('click', '.btn-subir, .btn-descer', function () {
        var $linha = $(this).closest('.coluna-quadro');
        if ($(this).hasClass('btn-subir')) {
            $linha.prev('.coluna-quadro').before($linha);
        } else {
            $linha.next('.coluna-quadro').after($linha);
        }
    });

    /* envio (salvar e prévia): o documento volta com {{BLOCOS}}; a ordem das colunas sai da posição na aba */
    $form.on('submit', function () {
        if ($campoEditor.summernote('codeview.isActivated')) {
            $campoEditor.summernote('codeview.deactivate');
        }
        $campo.val(paraServidor($campoEditor.summernote('code')));
        $('#colunasQuadro .coluna-quadro').each(function (i) {
            $(this).find('.ordem-coluna').val(i + 1);
        });
    });
})(jQuery);
