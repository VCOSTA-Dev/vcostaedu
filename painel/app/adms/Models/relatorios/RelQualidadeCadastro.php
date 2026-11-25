<?php

namespace App\adms\Models\relatorios;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\relatorios\AdmsRelatorioFonte as F;

/** R-SEC-04 Qualidade do cadastro (docs/RELATORIOS.md, B). */
class RelQualidadeCadastro extends AdmsRelatorioBase
{
    public const CODIGO = 'R-SEC-04';
    public const NOME = 'Qualidade do cadastro';
    public const CATEGORIA = 'B';
    public const PERGUNTA = 'Que dados faltam para emitir documentos?';
    public const SLUG = 'qualidade-cadastro';
    public const QUEM = 'administrador, secretaria';

    public const TIPOS = [
        'sexo' => 'Sem sexo definido', 'data de nascimento' => 'Sem data de nascimento', 'naturalidade' => 'Sem naturalidade',
        'nacionalidade' => 'Sem nacionalidade', 'RG' => 'Sem RG', 'CPF' => 'Sem CPF', 'filiação (mãe ou pai)' => 'Sem filiação',
        'previsao' => 'Cursando sem previsão de conclusão', 'enturmacao' => 'Cursando sem nenhuma enturmação ativa', 'polo' => 'Matrícula sem polo (nem o do curso)',
    ];

    public function filtros(): array
    {
        return ['curso', 'situacao'];
    }

    public function ficha(): array
    {
        return [
            'Uma linha por matrícula com ao menos uma falta. Os dados pessoais são os MESMOS que o histórico exige (AdmsVidaAcademica::dadosPessoaisFaltando): data de nascimento, naturalidade, nacionalidade, RG, CPF e filiação.',
            'Sem sexo: a mesma regra da pendência de sexo (sexo não é Feminino nem Masculino e não foi confirmado).',
            'Sem previsão de conclusão e sem enturmação: só matrículas em Cursando (enturmação ativa em qualquer oferta do curso).',
            'Sem polo: nem a matrícula nem o curso têm polo.',
            'A tabela "Por tipo de falta" conta as matrículas de cada tipo (uma matrícula pode ter várias faltas); cada número abre a lista.',
        ];
    }

    public function dados(array $f): array
    {
        $ent = [];
        foreach (F::ler("SELECT DISTINCT e.rm, o.curso_id FROM portal_disciplinas_alunos e INNER JOIN rm_professores_materias o ON o.id = e.rm_prof_mat_id WHERE e.situacao = 1") as $r) {
            $ent[(string) $r['rm']][(int) $r['curso_id']] = true;
        }
        $colunas = [self::col('ra', 'RA'), self::col('nome', 'Nome'), self::col('curso', 'Curso'), self::col('situacao', 'Situação'), self::col('falta', 'O que falta'), self::col('acao', '', ['classe' => 'nao-exportar nao-ordenar'])];
        $linhas = [];
        $porTipo = array_fill_keys(array_keys(self::TIPOS), []);
        foreach (F::matriculas($f) as $mc) {
            $aluno = [
                'nascimento' => $mc['nasc_data'], 'naturalidade' => trim((string) $mc['nasc_local']) === '' ? null : $mc['nasc_local'],
                'nacionalidade' => $mc['nasc_nac'], 'rg' => $mc['rg'], 'cpf' => $mc['cpf'], 'mae' => $mc['nome_mae'], 'pai' => $mc['nome_pai'],
            ];
            $faltas = \App\adms\Models\historico\AdmsVidaAcademica::dadosPessoaisFaltando($aluno);
            if (empty($mc['genero_confirmado_em']) && !in_array((int) $mc['genero_id'], [1, 2], true)) {
                array_unshift($faltas, 'sexo');
            }
            if ((int) $mc['situacao_id'] === 1 && empty($mc['previsao_conclusao'])) {
                $faltas[] = 'previsao';
            }
            if ((int) $mc['situacao_id'] === 1 && empty($ent[(string) $mc['rm']][(int) $mc['curso_id']])) {
                $faltas[] = 'enturmacao';
            }
            if (empty($mc['polo_efetivo'])) {
                $faltas[] = 'polo';
            }
            if (!$faltas) {
                continue;
            }
            $l = [
                'ra' => (string) $mc['rm'], 'nome' => self::nomeAluno($mc), 'curso' => (string) $mc['curso'], 'situacao' => (string) $mc['situacao'],
                'falta' => implode('; ', array_map(fn($t) => mb_strtolower(preg_replace('/^(Sem |Cursando sem |Matrícula sem )/u', '', self::TIPOS[$t] ?? $t)), $faltas)),
                'acao' => self::cel('Editar', ['url' => URLADM . 'academicos/editar/' . (int) $mc['usuario_id']]),
            ];
            $linhas[] = $l;
            foreach ($faltas as $t) {
                $porTipo[$t][] = $l;
            }
        }
        $det = [];
        $tipos = [];
        foreach (array_keys(self::TIPOS) as $i => $t) {
            $rot = self::TIPOS[$t];
            $k = 'tipo_' . $i;
            $det[$k] = ['titulo' => $rot, 'colunas' => $colunas, 'linhas' => $porTipo[$t]];
            $tipos[] = ['tipo' => $rot, 'n' => self::numero($det, $k)];
        }
        return [
            'cartoes' => [['rotulo' => 'Matrículas com alguma falta', 'valor' => count($linhas), 'cor' => count($linhas) ? 'bg-warning' : 'bg-success']],
            'detalhes' => $det,
            'tabelas' => [
                ['titulo' => 'Por tipo de falta', 'colunas' => [self::col('tipo', 'Falta'), self::col('n', 'Matrículas', ['num' => true])], 'linhas' => $tipos],
                ['titulo' => 'Matrículas com falta', 'colunas' => $colunas, 'linhas' => $linhas, 'rodape' => ['ra' => 'Total', 'nome' => (string) count($linhas)]],
            ],
        ];
    }
}
