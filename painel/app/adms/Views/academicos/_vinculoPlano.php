<?php
if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}
/* Onda 8, financeiro, Parte 1 (D-98): o plano de pagamento ao matricular — as parcelas nascem com a matrícula, na
   mesma transação. Só para quem pode aplicar um plano e só com plano em uso; sem plano, a matrícula fica sem parcelas
   (elas podem ser geradas depois, na ficha financeira). */
$podePlano = !empty((new \App\adms\Models\AdmsBotao())->valBotao(['p' => ['menu_controller' => 'financeiro', 'menu_metodo' => 'aplicar-plano']])['p']);
$planosAtivos = $podePlano ? \App\adms\Models\financeiro\AdmsFinTitulos::planos(true) : [];
if ($planosAtivos) : ?>
          <div class="mb-3 col-md-4">
            <label class="form-label" for="vPlano">Plano de pagamento</label>
            <select name="plano_id" id="vPlano" class="form-select">
              <option value="">Sem plano (as parcelas, depois, na ficha financeira)</option>
              <?php foreach ($planosAtivos as $pl) : ?><option value="<?= htmlspecialchars((string) (int) $pl['id'], ENT_QUOTES, 'UTF-8') ?>" data-parcelas="<?= (int) $pl['parcelas'] ?>" data-mes="<?= (int) $pl['mes_primeira'] ?>" data-dia="<?= (int) $pl['dia_vencimento'] ?>"><?= htmlspecialchars((string) $pl['nome'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?>
            </select>
          </div>
          <?php /* Correção da escola, Parte 3 (A1; D-102): a partir de qual parcela cobrar — a primeira que ainda não venceu,
             marcada; qualquer uma pode ser escolhida, inclusive todas (as vencidas nascem vencidas só assim) */ ?>
          <div class="mb-3 col-md-4" id="parcelaInicialCampo" hidden>
            <label class="form-label" for="vParcelaInicial">Cobrar a partir da parcela</label>
            <select name="parcela_inicial" id="vParcelaInicial" class="form-select" disabled></select>
            <small class="form-hint" id="vParcelaDica"></small>
          </div>
          <script>
          (function () {
            var plano = document.getElementById('vPlano'), sel = document.getElementById('vParcelaInicial'), campo = document.getElementById('parcelaInicialCampo'), dica = document.getElementById('vParcelaDica');
            var anoSel = document.getElementById('vAno');
            if (!plano || !sel) { return; }
            function dois(n) { return (n < 10 ? '0' : '') + n; }
            function montar() {
              var o = plano.options[plano.selectedIndex];
              var n = parseInt(o && o.dataset.parcelas || '0', 10);
              campo.hidden = !n; sel.disabled = !n;
              sel.innerHTML = '';
              if (!n) { return; }
              var ano = parseInt(anoSel ? anoSel.value : new Date().getFullYear(), 10), mes1 = parseInt(o.dataset.mes, 10), dia = parseInt(o.dataset.dia, 10);
              var hoje = new Date(); var h = hoje.getFullYear() + '-' + dois(hoje.getMonth() + 1) + '-' + dois(hoje.getDate());
              var primeira = 0;
              for (var i = 1; i <= n; i++) {
                var m = mes1 + i - 1, a = ano + Math.floor((m - 1) / 12), mm = ((m - 1) % 12) + 1;
                var v = a + '-' + dois(mm) + '-' + dois(dia);
                var vencida = v < h;
                if (!primeira && !vencida) { primeira = i; }
                var op = document.createElement('option');
                op.value = String(i);
                op.textContent = (i === 1 ? 'Todas, desde a 1ª' : 'A ' + i + 'ª') + ' (vence ' + dois(dia) + '/' + dois(mm) + '/' + a + (vencida ? ', já vencida' : '') + ')';
                sel.appendChild(op);
              }
              var nenhuma = document.createElement('option');
              nenhuma.value = '0'; nenhuma.textContent = 'Nenhuma parcela agora';
              sel.appendChild(nenhuma);
              sel.value = String(primeira);
              dica.textContent = primeira ? 'Marcada: a primeira que ainda não venceu. As anteriores não são cobradas.' : 'Todas as parcelas deste ano já venceram: escolha de qual cobrar, ou nenhuma.';
            }
            plano.addEventListener('change', montar);
            if (anoSel) { anoSel.addEventListener('change', montar); }
            montar();
          })();
          </script>
<?php endif;
