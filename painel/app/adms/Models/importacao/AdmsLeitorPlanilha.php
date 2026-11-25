<?php

namespace App\adms\Models\importacao;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * Lê um CSV enviado (Onda 2, Etapa 5 — o leitor do importador do vcostaedu, adaptado).
 *
 * - O BOM do UTF-8 sai.
 * - A codificação: UTF-8 se o arquivo inteiro for UTF-8 válido; senão Windows-1252 (o "CSV" do Excel em português,
 *   que cobre o ISO-8859-1), convertido para UTF-8.
 * - O separador: ';' (o do Excel em português) se a primeira linha tiver tantos ou mais ';' do que ',', senão ','.
 * - Linhas inteiramente vazias saem; as demais ficam com o número que têm no arquivo (o cabeçalho é a linha 1).
 */
class AdmsLeitorPlanilha
{
    public const TAMANHO_MAXIMO = 2 * 1024 * 1024;
    public const LINHAS_MAXIMO = 5000;

    private string $erro = '';

    public function getErro(): string
    {
        return $this->erro;
    }

    /**
     * @return array{codificacao: string, separador: string, cabecalho: string[], linhas: array<int, string[]>}|null
     */
    public function ler(string $conteudo): ?array
    {
        if (str_starts_with($conteudo, "\xEF\xBB\xBF")) {
            $conteudo = substr($conteudo, 3);
        }
        if (trim($conteudo) === '') {
            $this->erro = 'O arquivo está vazio.';
            return null;
        }
        $codificacao = 'UTF-8';
        if (!preg_match('//u', $conteudo)) {
            $codificacao = 'Windows-1252';
            $conteudo = mb_convert_encoding($conteudo, 'UTF-8', 'Windows-1252');
        }
        $conteudo = str_replace(["\r\n", "\r"], "\n", $conteudo);
        $primeira = strtok($conteudo, "\n");
        $separador = substr_count((string) $primeira, ';') >= substr_count((string) $primeira, ',') ? ';' : ',';

        $linhas = [];
        $numero = 0;
        $h = fopen('php://memory', 'r+');
        fwrite($h, $conteudo);
        rewind($h);
        while (($campos = fgetcsv($h, 0, $separador, '"', '')) !== false) {
            $numero++;
            if ($campos === [null] || implode('', array_map('trim', array_map('strval', $campos))) === '') {
                continue;
            }
            $linhas[$numero] = array_map(fn($v) => trim((string) $v), $campos);
            if (count($linhas) > self::LINHAS_MAXIMO + 1) {
                fclose($h);
                $this->erro = 'O arquivo tem mais de ' . self::LINHAS_MAXIMO . ' linhas. Divida-o em arquivos menores.';
                return null;
            }
        }
        fclose($h);
        if (!$linhas) {
            $this->erro = 'O arquivo está vazio.';
            return null;
        }
        $primeiro = array_key_first($linhas); // sem array_shift: ele renumera as chaves, e a chave é o número da linha no arquivo
        $cabecalho = $linhas[$primeiro];
        unset($linhas[$primeiro]);
        if (count($linhas) === 0) {
            $this->erro = 'O arquivo só tem o cabeçalho: nenhuma linha de aluno.';
            return null;
        }
        return ['codificacao' => $codificacao, 'separador' => $separador, 'cabecalho' => $cabecalho, 'linhas' => $linhas];
    }

    /** A forma achatada: minúsculas, sem acento, só letras e números separados por um espaço. */
    public static function achatar(string $texto): string
    {
        $t = mb_strtolower(trim($texto), 'UTF-8');
        $t = strtr($t, ['á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i', 'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
            'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ç' => 'c', 'ñ' => 'n', 'º' => 'o', 'ª' => 'a']);
        $t = preg_replace('/[^a-z0-9]+/', ' ', $t);
        return trim((string) $t);
    }
}
