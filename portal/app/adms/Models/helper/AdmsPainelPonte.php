<?php

namespace App\adms\Models\helper;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * E6 (D-376): a PONTE do portal para o motor de documentos do painel (histórico/atestado: modelos, vida acadêmica,
 * snapshot, Dompdf). Um motor só — o portal não copia as classes. Carrega, DEPOIS do autoload do portal:
 *  - as bibliotecas do painel/vendor (Dompdf, QR code…), menos os namespaces App\ e Core\ de lá;
 *  - só estas pastas/classes do painel: historico/*, tcc/AdmsTcc, atosLegais/AdmsAtosLegais,
 *    modalidades/AdmsModalidades, vinculo/AdmsVinculoRegras e helper/AdmsModuloInterno (a guarda delas aceita o portal).
 * As classes que o portal já tem (AdmsRead, AdmsCreate, AdmsParametro, AdmsInstituicao, AdmsCalculoNotas…) continuam
 * as do portal: o autoload dele vem primeiro.
 */
class AdmsPainelPonte
{
    private const PERMITIDAS = ['historico\\', 'tcc\\AdmsTcc', 'atosLegais\\AdmsAtosLegais', 'modalidades\\AdmsModalidades',
        'vinculo\\AdmsVinculoRegras', 'helper\\AdmsModuloInterno', 'notas\\AdmsRegraMedia']; // 1.13.0 (E3): a regra de média

    private static bool $carregada = false;

    public static function carregar(): void
    {
        if (self::$carregada) {
            return;
        }
        self::$carregada = true;
        $raiz = dirname(__DIR__, 5);
        $vendor = $raiz . '/painel/vendor';
        $l = new \Composer\Autoload\ClassLoader($vendor);
        foreach (require $vendor . '/composer/autoload_psr4.php' as $ns => $dirs) {
            if ($ns !== 'App\\' && $ns !== 'Core\\') {
                $l->setPsr4($ns, $dirs);
            }
        }
        $l->addClassMap(array_filter(require $vendor . '/composer/autoload_classmap.php', fn($c) => !str_starts_with($c, 'App\\') && !str_starts_with($c, 'Core\\'), ARRAY_FILTER_USE_KEY));
        $l->register(false);
        $modelos = $raiz . '/painel/app/adms/Models/';
        spl_autoload_register(function (string $classe) use ($modelos): void {
            $pre = 'App\\adms\\Models\\';
            if (!str_starts_with($classe, $pre)) {
                return;
            }
            $rel = substr($classe, strlen($pre));
            foreach (self::PERMITIDAS as $p) {
                if ($rel === $p || (str_ends_with($p, '\\') && str_starts_with($rel, $p))) {
                    $arq = $modelos . str_replace('\\', '/', $rel) . '.php';
                    if (is_file($arq)) {
                        require $arq;
                    }
                    return;
                }
            }
        });
    }
}
