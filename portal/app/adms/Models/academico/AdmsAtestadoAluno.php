<?php

namespace App\adms\Models\academico;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\helper\AdmsPainelPonte;
use App\adms\Models\historico\AdmsHistoricoEmissao as Emissao;
use App\adms\Models\historico\AdmsHistoricoModelos as Modelos;

/**
 * E6 (D-376): o ATESTADO DE MATRÍCULA emitido pelo PRÓPRIO aluno no portal. Tudo é filtrado pelo aluno da sessão
 * (`$alunoId` = col_usuarios.id): uma matrícula ou emissão de outro aluno é recusada como "não encontrada". A emissão
 * é a mesma do painel (AdmsHistoricoEmissao, origem "portal"): snapshot, hashes, código e verificação pública.
 */
class AdmsAtestadoAluno
{
    private array $erros = [];
    private int $id = 0;

    public function getErros(): array
    {
        return $this->erros;
    }

    public function getId(): int
    {
        return $this->id;
    }

    /** As matrículas do aluno; `podeEmitir` só para vínculo em "Cursando" (a regra fina — previsão, dados — é a da emissão). */
    public static function matriculas(int $alunoId): array
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead(
            "SELECT mc.id, mc.curso_id, mc.situacao_id, s.nome AS situacao, c.nome AS curso
               FROM rm_matriculas_curso mc
               INNER JOIN rm_cursos c ON c.id = mc.curso_id
               LEFT JOIN rm_matriculas_situacao s ON s.id = mc.situacao_id
              WHERE mc.usuario_id = :u
              ORDER BY mc.data_ingresso DESC, mc.id DESC",
            "u={$alunoId}"
        );
        $lista = $ler->getResultado() ?: [];
        foreach ($lista as &$m) {
            $m['podeEmitir'] = (int) $m['situacao_id'] === 1;
        }
        return $lista;
    }

    /** Os atestados do aluno (de qualquer origem), os mais novos primeiro. */
    public static function emissoes(int $alunoId): array
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead(
            "SELECT e.id, e.codigo, e.situacao, e.emitido_em, e.origem, c.nome AS curso
               FROM rm_historico_emissoes e
               INNER JOIN rm_matriculas_curso mc ON mc.id = e.matricula_curso_id
               INNER JOIN rm_cursos c ON c.id = mc.curso_id
              WHERE e.aluno_id = :u AND e.tipo = 'ATESTADO'
              ORDER BY e.emitido_em DESC, e.id DESC LIMIT :limit",
            "u={$alunoId}&limit=30"
        );
        return $ler->getResultado() ?: [];
    }

    /** Emite o atestado da matrícula `$mcId` — só se ela for do aluno da sessão. */
    public function emitir(int $alunoId, string $nomeAluno, int $mcId): bool
    {
        $m = array_values(array_filter(self::matriculas($alunoId), fn($x) => (int) $x['id'] === $mcId))[0] ?? null;
        if (!$alunoId || !$m) {
            $this->erros = ['Matrícula não encontrada.'];
            return false;
        }
        if (!$m['podeEmitir']) {
            $this->erros = ['O atestado de matrícula só é emitido com a matrícula em curso.'];
            return false;
        }
        AdmsPainelPonte::carregar();
        $modelo = Modelos::aplicaveis('ATESTADO', (int) $m['curso_id'])[0] ?? null;
        if (!$modelo) {
            $this->erros = ['O atestado ainda não está disponível para o seu curso. Procure a secretaria.'];
            return false;
        }
        $e = new Emissao();
        if (!$e->emitir($mcId, 'ATESTADO', (int) $modelo['id'], false, 0, $nomeAluno, 'portal', $alunoId)) {
            $this->erros = $e->getErros();
            return false;
        }
        $this->id = $e->getId();
        return true;
    }

    /** O PDF gravado de um atestado DO aluno (via 1) — ou null. */
    public static function pdf(int $alunoId, int $emissaoId): ?array
    {
        AdmsPainelPonte::carregar();
        $e = Emissao::ver($emissaoId);
        if (!$e || (int) $e['aluno_id'] !== $alunoId || $e['tipo'] !== 'ATESTADO') {
            return null;
        }
        $arq = Emissao::arquivoPdf($e);
        return $arq ? ['arquivo' => $arq, 'nome' => 'atestado_' . $e['codigo'] . '.pdf'] : null;
    }
}
