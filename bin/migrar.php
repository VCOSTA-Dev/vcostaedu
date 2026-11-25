<?php

/**
 * Aplica e inspeciona as migrations da base.
 *
 *     php bin/migrar.php status            lista aplicadas, pendentes, divergentes
 *     php bin/migrar.php aplicar           aplica o que falta, em ordem
 *     php bin/migrar.php aplicar --sim     idem, sem perguntar (para script)
 *     php bin/migrar.php simular           diz o que faria, sem executar
 *
 * Mesmo desenho de outro projeto da casa. O comando `adotar` do runner do
 * sistema escolar em Tabler não veio: ele existe para ambiente onde migrations foram
 * rodadas à mão antes de o runner existir, o que não é o caso desta base — aqui
 * o runner nasce junto com a primeira migration.
 *
 * ======================= É UMA DAS DUAS PORTAS (D25) ========================
 *
 * Instalar é pelo NAVEGADOR — a tela 5 do instalador aplica as migrations à
 * vista. Atualizar tem DUAS portas: a tela de atualização e este comando.
 *
 * As duas aplicam pelo MESMO `AdmsMigrations`, inclusive a trava contra duas
 * atualizações simultâneas. Se cada uma aplicasse do seu jeito, o dia em que a
 * pessoa recorre a este comando — que é o dia em que a tela já deu problema —
 * seria o dia em que os dois discordam.
 *
 * ===================== QUANDO ESTE É O CAMINHO CERTO ========================
 *
 * Não é plano B. É o caminho certo em dois casos:
 *
 *   1. **atualização grande.** O navegador tem limite de tempo por página, e a
 *      tela recusa acima do que cabe nele em vez de morrer no meio. Aqui não há
 *      limite;
 *   2. **quando não dá para entrar no sistema.** Se a atualização é justamente o
 *      que falta para o sistema subir, a tela não está disponível.
 *
 * Código de saída: 0 tudo certo, 1 falhou ou há divergência. Serve em pipeline.
 */

declare(strict_types=1);

require __DIR__ . '/bootstrap-cli.php';
/* Onda 2, Etapa 7: a decisão da atualização (AdmsAtualizacao) é a mesma da tela "Atualizar o sistema"; ela lê o banco
   pelos helpers do painel, que vêm do autoload. */
require dirname(__DIR__) . '/painel/vendor/autoload.php';
$_SESSION = $_SESSION ?? [];

use App\adms\Models\banco\AdmsMigrations;
use App\adms\Models\helper\AdmsAtualizacao;

$comando = $argv[1] ?? 'status';
$diretorio = dirname(__DIR__) . '/database/migrations';

$runner = new AdmsMigrations($diretorio);

function imprimirEstado(array $estado): int
{
    $marcas = [
        'APLICADA' => '  ',
        'PENDENTE' => '->',
        'DIVERGENTE' => '!!',
        'ORFA' => '??',
    ];

    echo "\n";
    printf("  %-2s %-8s %-46s %-12s %s\n", '', 'VERSAO', 'ARQUIVO', 'SITUACAO', 'APLICADA EM');
    echo str_repeat('-', 98) . "\n";

    $contagem = ['APLICADA' => 0, 'PENDENTE' => 0, 'DIVERGENTE' => 0, 'ORFA' => 0];

    foreach ($estado as $m) {
        $contagem[$m['situacao']]++;

        printf(
            "  %-2s %-8s %-46s %-12s %s\n",
            $marcas[$m['situacao']] ?? '  ',
            $m['versao'],
            $m['arquivo'],
            $m['situacao'],
            $m['aplicada_em'] ?? '-'
        );
    }

    echo str_repeat('-', 98) . "\n";
    printf(
        "  %d aplicadas, %d pendentes, %d divergentes, %d orfas\n",
        $contagem['APLICADA'],
        $contagem['PENDENTE'],
        $contagem['DIVERGENTE'],
        $contagem['ORFA']
    );

    if ($contagem['DIVERGENTE'] > 0) {
        echo "\n";
        echo "  !! DIVERGENTE significa que o arquivo mudou DEPOIS de aplicado.\n";
        echo "     Migration aplicada e imutavel. O runner recusa aplicar qualquer\n";
        echo "     coisa enquanto houver divergencia - a correcao e uma migration NOVA,\n";
        echo "     nunca a edicao da antiga.\n";
    }

    if ($contagem['ORFA'] > 0) {
        echo "\n";
        echo "  ?? ORFA significa registrada no banco e ausente do disco.\n";
        echo "     Costuma ser arquivo renomeado. Nao impede aplicar, mas confira.\n";
    }

    return $contagem['DIVERGENTE'] > 0 ? 1 : 0;
}

/**
 * O AVISO DE BACKUP, e a confirmação.
 *
 * A tela de atualização exige uma caixa marcada antes de aplicar; aqui a
 * exigência é a mesma, pelo mesmo motivo: mudança de estrutura não tem volta, e
 * a regra 14 é explícita. As duas portas cobram o mesmo — só perguntam de jeito
 * diferente.
 *
 * Interativo, pergunta e espera "sim". Não interativo — que é como um script de
 * implantação roda —, exige `--sim`. Sem isso, um `aplicar` dentro de um script
 * rodaria sem ninguém ter parado para pensar no backup, que é exatamente o que
 * este aviso existe para impedir.
 */
function confirmarBackup(array $argv): bool
{
    if (in_array('--sim', $argv, true)) {
        return true;
    }

    echo "\n";
    echo "  ATENCAO: FACA UM BACKUP DO BANCO ANTES DE CONTINUAR.\n\n";
    echo "  Algumas etapas mudam a estrutura do banco e NAO TEM VOLTA. Se algo\n";
    echo "  der errado no meio, o backup e o que traz o sistema de volta.\n\n";
    echo "      mysqldump -u SEU_USUARIO -p NOME_DO_BANCO > backup.sql\n\n";
    echo "  Guarde o arquivo fora do servidor.\n\n";

    /* `posix_isatty` não existe em toda instalação. Onde não dá para saber se há
       terminal, EXIGE a bandeira: a dúvida não pode resolver a favor de aplicar
       sem confirmação. */
    $interativo = function_exists('posix_isatty') ? @posix_isatty(STDIN) : false;

    if (!$interativo) {
        fwrite(STDERR, "  Sem confirmacao possivel neste terminal.\n");
        fwrite(STDERR, "  Rode de novo com --sim depois de ter feito o backup:\n\n");
        fwrite(STDERR, "      php bin/migrar.php aplicar --sim\n\n");

        return false;
    }

    echo "  Digite 'sim' para aplicar as etapas pendentes: ";
    $resposta = trim((string) fgets(STDIN));

    if (mb_strtolower($resposta) !== 'sim') {
        echo "\n  Nada foi aplicado.\n\n";

        return false;
    }

    return true;
}

try {
    switch ($comando) {
        case 'status':
            echo "\nESTADO DAS MIGRATIONS";
            exit(imprimirEstado($runner->estado()));

        case 'simular':
            echo "\nSIMULACAO - nada sera executado\n";
            $quantas = $runner->aplicar(true);
            foreach ($runner->mensagens() as $mensagem) {
                echo "  {$mensagem}\n";
            }
            echo "\n{$quantas} migration(s) seriam aplicadas.\n";
            exit(0);

        case 'aplicar':
            if (!confirmarBackup($argv)) {
                exit(1);
            }

            echo "\nAPLICANDO MIGRATIONS\n";
            /* a MESMA política da tela (Onda 2, Etapa 7): o mesmo runner, a mesma trava, o mesmo desfecho na falha */
            $resultado = (new AdmsAtualizacao())->aplicar();
            foreach ($resultado['aplicadas'] as $a) {
                echo "  aplicada {$a['arquivo']} ({$a['duracao_ms']} ms)\n";
            }
            if (!$resultado['ok']) {
                fwrite(STDERR, "\nA ATUALIZACAO PAROU: {$resultado['falhou']}\n");
                fwrite(STDERR, count($resultado['aplicadas']) . " migration(s) aplicada(s) antes da falha continuam valendo.\n");
                if ($resultado['pendentesDepois']) {
                    fwrite(STDERR, 'Ainda pendentes: ' . implode(', ', $resultado['pendentesDepois']) . "\n");
                }
                fwrite(STDERR, "O detalhe esta no log do servidor; o backup traz o banco de volta.\n\n");
                exit(1);
            }
            if ($resultado['quantas'] === 0) {
                echo "  Nada a aplicar: o banco esta em dia.\n";
            }
            echo "\n{$resultado['quantas']} migration(s) aplicada(s).\n";
            exit(0);

        default:
            fwrite(STDERR, "Comando desconhecido: {$comando}\n");
            fwrite(STDERR, "Use: status | aplicar | simular\n");
            exit(1);
    }
} catch (Throwable $e) {
    /* O que já rodou antes da falha não pode se perder da vista de quem depura. */
    foreach ($runner->mensagens() as $mensagem) {
        echo "  {$mensagem}\n";
    }

    fwrite(STDERR, "\nERRO: " . $e->getMessage() . "\n\n");
    fwrite(STDERR, "Rode `php bin/migrar.php status` para ver o detalhe.\n");
    exit(1);
}
