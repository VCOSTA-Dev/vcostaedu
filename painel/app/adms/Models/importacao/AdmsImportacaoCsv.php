<?php

namespace App\adms\Models\importacao;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * IMPORTAÇÃO POR PLANILHA (E8, 1.14.0, D-410 e D-411) — o leitor e a validação comuns de notas (`rm;nota`) e faltas
 * (`rm;faltas`), no padrão que já existia (D-323): CSV com `;` (o que o Excel brasileiro exporta), uma linha por aluno,
 * chave pelo RA; colunas a mais (ex.: nome) são ignoradas.
 * - Arquivo em UTF-8 (com ou sem BOM) ou em Windows-1252 (o Excel antigo): convertido para UTF-8, sem quebrar acento.
 * - Valida TODAS as linhas antes de gravar. Erros, com o número da linha do arquivo: RA inexistente, aluno não
 *   enturmado na oferta, RA repetido no arquivo e o valor (a regra de cada tipo). Linha com o valor vazio é pulada
 *   (como antes).
 * - Quem grava (AdmsUploadNotas, AdmsImportarFaltas) grava numa transação: tudo ou nada.
 */
class AdmsImportacaoCsv
{
    /** Os modelos para baixar: o cabeçalho e uma linha de exemplo FICTÍCIA. */
    public const MODELOS = [
        'notas' => ['rm;nota', '999999;8,5', 'modelo_notas.csv'],
        'faltas' => ['rm;faltas', '999999;2', 'modelo_faltas.csv'],
    ];

    /**
     * Lê o arquivo enviado: ['linhas' => [['linha' => nº no arquivo, 'rm' => …, $coluna => …]], 'erros' => [...]].
     * `$coluna` é a do valor (nota ou faltas).
     */
    public static function ler(?array $arquivo, string $coluna): array
    {
        if (empty($arquivo['tmp_name']) || !is_uploaded_file($arquivo['tmp_name']) && !is_file($arquivo['tmp_name']) || (int) ($arquivo['size'] ?? 0) <= 0) {
            return ['linhas' => [], 'erros' => ['Envie o arquivo CSV (veja o modelo para baixar).']];
        }
        if ((int) $arquivo['size'] > 2 * 1024 * 1024) {
            return ['linhas' => [], 'erros' => ['Arquivo grande demais (até 2 MB).']];
        }
        $txt = (string) file_get_contents($arquivo['tmp_name']);
        if (str_starts_with($txt, "\xEF\xBB\xBF")) {
            $txt = substr($txt, 3);
        } elseif (!mb_check_encoding($txt, 'UTF-8')) {
            $txt = mb_convert_encoding($txt, 'UTF-8', 'Windows-1252');
        }
        $txt = str_replace(["\r\n", "\r"], "\n", $txt);
        $cab = null;
        $linhas = [];
        foreach (explode("\n", $txt) as $i => $l) {
            if (trim($l) === '') {
                continue;
            }
            $campos = array_map('trim', str_getcsv($l, ';'));
            if ($cab === null) {
                $cab = array_map(fn($c) => mb_strtolower($c), $campos);
                if (!in_array('rm', $cab, true) || !in_array($coluna, $cab, true)) {
                    return ['linhas' => [], 'erros' => ["linha " . ($i + 1) . ": o cabeçalho deve ter as colunas rm e {$coluna}, separadas por ponto e vírgula (;) — veja o modelo."]];
                }
                continue;
            }
            $item = ['linha' => $i + 1];
            foreach ($cab as $k => $nome) {
                $item[$nome] = $campos[$k] ?? '';
            }
            $linhas[] = $item;
        }
        if ($cab === null) {
            return ['linhas' => [], 'erros' => ['O arquivo está vazio.']];
        }
        return ['linhas' => $linhas, 'erros' => []];
    }

    /**
     * Confere os alunos das linhas (com valor) contra a oferta. `$valida(string $valor): ?string` devolve o erro do
     * valor ou null. Devolve [linhas válidas (rm => valor normalizado pela $valida via referência), erros].
     */
    public static function conferir(int $ofertaId, array $linhas, string $coluna, callable $valida): array
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT rm FROM portal_disciplinas_alunos WHERE rm_prof_mat_id = :o AND situacao = :s", "o={$ofertaId}&s=1");
        $enturmados = array_flip(array_map('strval', array_column($ler->getResultado() ?: [], 'rm')));
        $vistos = [];
        $validas = [];
        $erros = [];
        $h = fn($s) => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
        foreach ($linhas as $l) {
            $rm = trim((string) ($l['rm'] ?? ''));
            $valor = trim((string) ($l[$coluna] ?? ''));
            if ($valor === '' && $rm === '') {
                continue;
            }
            if ($valor === '') {
                continue; /* como antes: linha sem valor é pulada */
            }
            $n = $l['linha'];
            $extra = isset($l['nome']) && $l['nome'] !== '' ? ' (' . $h(mb_substr($l['nome'], 0, 60)) . ')' : '';
            if (!preg_match('/^\d{1,12}$/', $rm) || !self::raExiste($rm)) {
                $erros[] = "linha {$n}: RA " . $h(mb_substr($rm, 0, 20)) . "{$extra} inexistente";
                continue;
            }
            if (isset($vistos[$rm])) {
                $erros[] = "linha {$n}: RA {$rm}{$extra} repetido no arquivo (já na linha {$vistos[$rm]})";
                continue;
            }
            $vistos[$rm] = $n;
            if (!isset($enturmados[$rm])) {
                $erros[] = "linha {$n}: aluno {$rm}{$extra} não está enturmado nesta oferta";
                continue;
            }
            $erro = $valida($valor, $rm);
            if ($erro !== null) {
                $erros[] = "linha {$n}: RA {$rm}{$extra}: " . $h($erro);
                continue;
            }
            $validas[$rm] = $valor;
        }
        return [$validas, $erros];
    }

    private static function raExiste(string $rm): bool
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT id FROM col_usuarios WHERE rm = :rm AND col_niveis_acesso_id = :n LIMIT :limit", "rm={$rm}&n=1&limit=1");
        return !empty($ler->getResultado());
    }

    /** Nota: 0 a 10, até 2 casas, vírgula ou ponto. Distingue "fora da escala" de "formato inválido". */
    public static function erroNota(string $v): ?string
    {
        $n = str_replace(',', '.', $v);
        if (!preg_match('/^\d+(\.\d+)?$/', $n)) {
            return "nota \"" . mb_substr($v, 0, 20) . "\" em formato inválido (use 8,5 ou 8.5)";
        }
        if (\App\adms\Models\notas\AdmsCalculoNotas::centesimos($n) === null) {
            return (float) $n > 10 ? "nota {$v} fora da escala (0 a 10)" : "nota {$v} em formato inválido (até 2 casas decimais)";
        }
        return null;
    }

    /** A caixa de erro da tela (uma linha por erro, no máximo 30). */
    public static function caixaErros(string $titulo, array $erros): string
    {
        $mais = count($erros) > 30 ? '<br>… e mais ' . (count($erros) - 30) . ' erro(s).' : '';
        return "<div class='alert alert-danger' id='importacaoRecusada'><b>" . htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8') . '</b><br>' . implode('<br>', array_slice($erros, 0, 30)) . $mais . '</div>';
    }
}
