<?php

namespace App\adms\Models\relatorios;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\relatorios\AdmsRelatorioFonte as F;

/** R-DOC-01 Documentos emitidos (docs/RELATORIOS.md, G; onda 2). */
class RelDocumentos extends AdmsRelatorioBase
{
    public const CODIGO = 'R-DOC-01';
    public const NOME = 'Documentos emitidos';
    public const CATEGORIA = 'G';
    public const PERGUNTA = 'Que documentos saíram, de quem, e algum ficou desatualizado?';
    public const SLUG = 'documentos-emitidos';
    public const QUEM = 'administrador, secretaria';

    public function filtros(): array
    {
        return ['curso', 'de' => ['rotulo' => 'Emitidos de', 'tipo' => 'data'], 'ate' => ['rotulo' => 'até', 'tipo' => 'data'],
            'tipo' => ['rotulo' => 'Tipo', 'tipo' => 'select', 'opcoes' => \App\adms\Models\historico\AdmsHistoricoModelos::TIPOS],
            'origem' => ['rotulo' => 'Origem', 'tipo' => 'select', 'opcoes' => ['secretaria' => 'Secretaria', 'portal' => 'Portal do aluno']],
            'doc' => ['rotulo' => 'Situação', 'tipo' => 'select', 'opcoes' => \App\adms\Models\historico\AdmsHistoricoEmissao::SITUACOES]];
    }

    public function ficha(): array
    {
        return [
            'Cada emissão gravada (rm_historico_emissoes): histórico parcial, final e atestado; o período filtra a data de emissão.',
            'Origem: secretaria (painel) ou portal (o próprio aluno). Situação: válida ou cancelada (com o motivo).',
            '"Emitido antes de reabertura": documento válido de uma matrícula em que alguma disciplina do aluno foi REABERTA depois da emissão (AdmsHistoricoEmissao::emitidasAntesDeReabertura) — o documento pode estar desatualizado.',
            'O atestado não lista disciplinas e nunca é marcado. As verificações públicas não são registradas pelo sistema (fase futura).',
        ];
    }

    public function dados(array $f): array
    {
        $w = ['1 = 1'];
        $p = [];
        foreach (['tipo' => 'e.tipo', 'origem' => 'e.origem', 'doc' => 'e.situacao', 'curso' => 'mc.curso_id'] as $k => $col) {
            if (!empty($f[$k])) {
                $w[] = "{$col} = :{$k}";
                $p[$k] = $f[$k];
            }
        }
        if (!empty($f['de'])) {
            $w[] = 'e.emitido_em >= :de';
            $p['de'] = $f['de'] . ' 00:00:00';
        }
        if (!empty($f['ate'])) {
            $w[] = 'e.emitido_em <= :ate';
            $p['ate'] = $f['ate'] . ' 23:59:59';
        }
        $docs = F::ler(
            "SELECT e.id, e.codigo, e.tipo, e.origem, e.situacao, e.emitido_em, e.cancelado_em, e.cancelamento_motivo, e.matricula_curso_id, e.emitido_por,
                    u.id AS usuario_id, u.rm, u.nome, i.cpf, c.nome AS curso, ad.nome AS emissor
               FROM rm_historico_emissoes e
               INNER JOIN rm_matriculas_curso mc ON mc.id = e.matricula_curso_id INNER JOIN rm_cursos c ON c.id = mc.curso_id
               INNER JOIN col_usuarios u ON u.id = e.aluno_id LEFT JOIN col_usuarios_informacoes i ON i.col_user_id = u.id
               LEFT JOIN adms_usuarios ad ON ad.id = e.emitido_por
              WHERE " . implode(' AND ', $w) . " ORDER BY e.emitido_em DESC, e.id DESC",
            $p
        );
        $antes = [];
        foreach (\App\adms\Models\historico\AdmsHistoricoEmissao::emitidasAntesDeReaberturaLote(array_column($docs, 'matricula_curso_id')) as $lista) { // 1.17.2 (D-464): em lote
            foreach ($lista as $x) {
                $antes[(int) $x['id']] = (int) $x['reaberturas'];
            }
        }
        $colunas = self::colunasAluno([self::col('codigo', 'Código'), self::col('tipo', 'Tipo'), self::col('emitido', 'Emitido em'), self::col('origem', 'Origem'),
            self::col('emissor', 'Emitido por'), self::col('situacao', 'Situação'), self::col('alerta', 'Atenção')]);
        $det = [];
        foreach (array_merge(\App\adms\Models\historico\AdmsHistoricoModelos::TIPOS, ['cancelada' => 'Canceladas', 'antes' => 'Emitidos antes de reabertura']) as $k => $t) {
            $det["d_" . strtolower($k)] = ['titulo' => $t, 'colunas' => $colunas, 'linhas' => []];
        }
        $linhas = [];
        $tipos = \App\adms\Models\historico\AdmsHistoricoModelos::TIPOS;
        foreach ($docs as $d) {
            $l = self::linhaAluno($d, [
                'codigo' => (string) $d['codigo'], 'tipo' => $tipos[$d['tipo']] ?? $d['tipo'], 'emitido' => self::cel(date('d/m/Y H:i', strtotime($d['emitido_em'])), ['o' => $d['emitido_em']]),
                'origem' => $d['origem'] === 'portal' ? 'Portal do aluno' : 'Secretaria', 'emissor' => $d['origem'] === 'portal' ? 'o aluno' : (string) $d['emissor'],
                'situacao' => (int) $d['situacao'] === 1 ? 'Válida' : 'Cancelada' . ($d['cancelamento_motivo'] ? ': ' . $d['cancelamento_motivo'] : ''),
                'alerta' => isset($antes[(int) $d['id']]) ? "emitido antes de reabertura ({$antes[(int) $d['id']]} disciplina(s))" : '',
                '_classe' => isset($antes[(int) $d['id']]) ? 'table-warning' : ((int) $d['situacao'] !== 1 ? 'text-muted' : ''),
            ]);
            $l['codigo'] = self::cel((string) $d['codigo'], ['url' => URLADM . 'historico-emissoes/ver/' . (int) $d['id']]);
            $linhas[] = $l;
            $det['d_' . strtolower($d['tipo'])]['linhas'][] = $l;
            if ((int) $d['situacao'] !== 1) {
                $det['d_cancelada']['linhas'][] = $l;
            }
            if (isset($antes[(int) $d['id']])) {
                $det['d_antes']['linhas'][] = $l;
            }
        }
        $cartoes = [];
        foreach ($det as $k => $d) {
            $cartoes[] = ['rotulo' => $d['titulo'], 'valor' => self::contar($det, $k), 'detalhe' => $k, 'cor' => $k === 'd_antes' && self::contar($det, $k) ? 'bg-warning' : 'bg-light'];
        }
        return ['cartoes' => $cartoes, 'detalhes' => $det, 'tabela' => ['colunas' => $colunas, 'linhas' => $linhas, 'rodape' => ['ra' => 'Total', 'nome' => (string) count($linhas)]]];
    }
}
