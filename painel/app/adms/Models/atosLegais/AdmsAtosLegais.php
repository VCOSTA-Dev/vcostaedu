<?php

namespace App\adms\Models\atosLegais;

if (!defined('VCOSTAEDU')) { // E6 (D-376): o portal também carrega (atestado do aluno)
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * ATOS LEGAIS da instituição (E6a, D-36): credenciamento etc., cada um para UMA modalidade.
 * O histórico usa o ato da MODALIDADE DA MATRÍCULA do aluno (não da oferta): vale o ato
 * ATIVO e VIGENTE na data de referência. Nenhum ou mais de um → o histórico SINALIZA
 * "ato legal não definido"; nunca escolhe nem inventa.
 */
class AdmsAtosLegais
{
    public const TIPOS = ['Parecer', 'Resolução', 'Portaria', 'Decreto', 'Deliberação', 'Outro'];

    private $resultado;

    public function getResultado()
    {
        return $this->resultado;
    }

    public function listar(): array
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT a.*, m.nome AS modalidade FROM rm_atos_legais a LEFT JOIN uni_rh_cursos_modalidade m ON m.id = a.modalidade_id ORDER BY a.modalidade_id, a.vigencia_inicio DESC, a.id DESC");
        return $ler->getResultado() ?: [];
    }

    public function ver(int $id): ?array
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT * FROM rm_atos_legais WHERE id = :id LIMIT :limit", "id={$id}&limit=1");
        return $ler->getResultado()[0] ?? null;
    }

    /**
     * O ato aplicável a uma modalidade numa data (AAAA-MM-DD).
     * @return array{ato: ?array, alerta: ?string, candidatos: int}
     */
    public static function vigente(int $modalidadeId, string $data): array
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead(
            "SELECT a.*, m.nome AS modalidade FROM rm_atos_legais a LEFT JOIN uni_rh_cursos_modalidade m ON m.id = a.modalidade_id
              WHERE a.modalidade_id = :m AND a.situacao = :s AND a.vigencia_inicio <= :d1 AND (a.vigencia_fim IS NULL OR a.vigencia_fim >= :d2)
              ORDER BY a.vigencia_inicio DESC, a.id DESC",
            "m={$modalidadeId}&s=1&d1={$data}&d2={$data}"
        );
        $atos = $ler->getResultado() ?: [];
        $nomeMod = \App\adms\Models\modalidades\AdmsModalidades::nome($modalidadeId) ?? "modalidade {$modalidadeId}";
        $dataBr = date('d/m/Y', strtotime($data));
        if (count($atos) === 1) {
            return ['ato' => $atos[0], 'alerta' => null, 'candidatos' => 1];
        }
        $motivo = $atos ? count($atos) . " atos ativos e vigentes para {$nomeMod} em {$dataBr} (deve haver um só)" : "nenhum ato ativo e vigente para {$nomeMod} em {$dataBr}";
        return ['ato' => null, 'alerta' => "Ato legal não definido: {$motivo}.", 'candidatos' => count($atos)];
    }

    /** Linha do ato para o documento: "Resolução nº 12/2024 do Conselho …, publicada em …". */
    public static function descrever(array $a): string
    {
        $t = "{$a['tipo']} nº {$a['numero']}, de " . date('d/m/Y', strtotime($a['data_ato'])) . ", {$a['orgao']}";
        if (!empty($a['veiculo_publicacao']) || !empty($a['data_publicacao'])) {
            $t .= ' — publicado' . (!empty($a['veiculo_publicacao']) ? " em {$a['veiculo_publicacao']}" : '') . (!empty($a['data_publicacao']) ? ' de ' . date('d/m/Y', strtotime($a['data_publicacao'])) : '');
        }
        return $t;
    }

    public function salvar(array $d, int $id = 0)
    {
        $erros = [];
        $txt = fn($k) => trim((string) ($d[$k] ?? ''));
        $data = function ($k, bool $obrigatoria) use ($d) {
            $v = trim((string) ($d[$k] ?? ''));
            if ($v === '') {
                return $obrigatoria ? false : null;
            }
            $o = \DateTime::createFromFormat('Y-m-d', $v);
            return ($o && $o->format('Y-m-d') === $v) ? $v : false;
        };
        $linha = [
            'orgao' => $txt('orgao'), 'tipo' => $txt('tipo'), 'numero' => $txt('numero'),
            'data_ato' => $data('data_ato', true), 'veiculo_publicacao' => $txt('veiculo_publicacao') ?: null,
            'data_publicacao' => $data('data_publicacao', false), 'vigencia_inicio' => $data('vigencia_inicio', true),
            'vigencia_fim' => $data('vigencia_fim', false), 'modalidade_id' => (int) ($d['modalidade_id'] ?? 0),
            'situacao' => (int) ($d['situacao'] ?? 1) === 2 ? 2 : 1, 'observacao' => $txt('observacao') ?: null,
        ];
        if ($linha['orgao'] === '') {
            $erros[] = 'Informe o órgão (ex.: Conselho Estadual de Educação, Ministério da Educação).';
        }
        if (!in_array($linha['tipo'], self::TIPOS, true)) {
            $erros[] = 'Escolha o tipo do ato.';
        }
        if ($linha['numero'] === '') {
            $erros[] = 'Informe o número do ato.';
        }
        if ($linha['data_ato'] === false) {
            $erros[] = 'Informe a data do ato.';
        }
        if ($linha['data_publicacao'] === false) {
            $erros[] = 'Data de publicação inválida.';
        }
        if ($linha['vigencia_inicio'] === false) {
            $erros[] = 'Informe o início da vigência.';
        }
        if ($linha['vigencia_fim'] === false) {
            $erros[] = 'Fim da vigência inválido.';
        } elseif ($linha['vigencia_fim'] !== null && $linha['vigencia_inicio'] && $linha['vigencia_fim'] < $linha['vigencia_inicio']) {
            $erros[] = 'O fim da vigência não pode ser antes do início.';
        }
        if (!\App\adms\Models\modalidades\AdmsModalidades::existe($linha['modalidade_id'])) {
            $erros[] = 'Escolha a modalidade a que o ato se aplica.';
        }
        if ($erros) {
            $_SESSION['msg'] = "<div class='alert alert-danger'><b>Nada foi salvo:</b><ul class='mb-0'><li>" . implode('</li><li>', array_map('htmlspecialchars', $erros)) . '</li></ul></div>';
            return $this->resultado = false;
        }
        if ($id > 0) {
            if (!$this->ver($id)) {
                return $this->resultado = false;
            }
            $linha['usuario_mod'] = $_SESSION['colUsuario_id'];
            $up = new \App\adms\Models\helper\AdmsUpdate();
            $up->exeUpdate('rm_atos_legais', $linha, 'WHERE id = :id', "id={$id}");
            $ok = $up->getResultado() !== null;
        } else {
            $linha['usuario_cre'] = $_SESSION['colUsuario_id'];
            $add = new \App\adms\Models\helper\AdmsCreate();
            $add->exeCreate('rm_atos_legais', $linha);
            $id = (int) $add->getResultado();
            $ok = $id > 0;
        }
        if (!$ok) {
            $_SESSION['msg'] = "<div class='alert alert-danger'>Não foi possível salvar o ato legal.</div>";
            return $this->resultado = false;
        }
        (new \App\adms\Models\helper\AdmsSalvarLog())->salvarLogs(387, 200, 3, "Ato legal salvo: id={$id}; modalidade={$linha['modalidade_id']}");
        $aviso = '';
        $v = self::vigente($linha['modalidade_id'], date('Y-m-d'));
        if ($v['candidatos'] > 1) {
            $aviso = ' <b>Atenção:</b> há ' . $v['candidatos'] . ' atos ativos e vigentes hoje para esta modalidade; o histórico vai sinalizar "ato legal não definido" até sobrar um.';
        }
        $_SESSION['msg'] = "<div class='alert alert-" . ($aviso ? 'warning' : 'success') . " alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Ato legal salvo." . $aviso . '</div>';
        return $this->resultado = $id;
    }
}
