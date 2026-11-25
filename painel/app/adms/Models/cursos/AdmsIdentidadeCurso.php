<?php

namespace App\adms\Models\cursos;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * IDENTIDADE DO CURSO NOS DOCUMENTOS (E1a, D-340): nível, nome por extenso (corpo do documento), linha do curso no
 * cabeçalho e sigla (`abreviatura`, que já existia sem tela). Todos opcionais; usados pelas variáveis CURSO_NIVEL,
 * CURSO_NOME_EXTENSO, CURSO_CABECALHO e CURSO_SIGLA do editor de modelos. Cadastro e edição do curso passam por aqui.
 */
class AdmsIdentidadeCurso
{
    /** campo => [rótulo, tamanho máximo] */
    public const CAMPOS = [
        'nivel' => ['Nível', 60],
        'abreviatura' => ['Sigla', 20],
        'nome_extenso' => ['Nome por extenso', 255],
        'cabecalho_documento' => ['Linha do curso no cabeçalho', 255],
    ];

    /** Sugestões da lista do campo Nível (texto livre: a instituição escreve como quiser). */
    public const NIVEIS = ['Especialização', 'Aperfeiçoamento', 'MBA', 'Extensão', 'Graduação', 'Mestrado', 'Doutorado'];

    /**
     * Normaliza os quatro campos em `$dados` (espaços repetidos viram um; vazio vira NULL) e devolve os erros
     * (tamanho, quebra de linha). Com erro, nada deve ser gravado.
     */
    public static function normalizar(array &$dados): array
    {
        $erros = [];
        foreach (self::CAMPOS as $campo => [$rotulo, $max]) {
            $t = trim((string) preg_replace('/[ \t]+/u', ' ', (string) ($dados[$campo] ?? '')));
            if ($t !== '' && preg_match('/[\x00-\x1F\x7F]/u', $t)) {
                $erros[] = "{$rotulo}: use uma linha só (sem quebra de linha).";
            } elseif (mb_strlen($t) > $max) {
                $erros[] = "{$rotulo}: no máximo {$max} caracteres.";
            }
            $dados[$campo] = $t === '' ? null : $t;
        }
        /* E2 (D-360): frequência mínima do CURSO, em % (0 a 100, até 2 casas, vírgula ou ponto); vazio = a da instituição */
        $f = str_replace(',', '.', trim((string) ($dados['frequencia_minima'] ?? '')));
        if ($f === '') {
            $dados['frequencia_minima'] = null;
        } elseif (!preg_match('/^\d{1,3}(\.\d{1,2})?$/', $f) || (float) $f > 100) {
            $erros[] = 'Frequência mínima do curso: uma porcentagem de 0 a 100, com até 2 casas, ou vazio (vale a da instituição).';
        } else {
            $dados['frequencia_minima'] = number_format((float) $f, 2, '.', '');
        }
        return $erros;
    }

    public static function mensagemErro(array $erros): string
    {
        return "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button><b>Nada foi salvo:</b><ul class='mb-0'><li>"
            . implode('</li><li>', array_map(fn($e) => htmlspecialchars($e, ENT_QUOTES, 'UTF-8'), $erros)) . '</li></ul></div>';
    }
}
