<?php

namespace App\adms\Models\historico;

if (!defined('VCOSTAEDU')) { // E6 (D-376): o portal também carrega (atestado do aluno)
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * EMISSÃO do histórico (E6b). Regra da D-180:
 * - FINAL: só com a matrícula CONCLUÍDA e NENHUM alerta (os da fonte única + variável vazia);
 * - PARCIAL: com alerta, só com CONFIRMAÇÃO explícita, e os alertas aceitos ficam gravados.
 *
 * Cada emissão grava, IMUTÁVEL: código público aleatório, snapshot (JSON canônico do objeto
 * da E6a + dados da emissão), HTML renderizado, PDF (fora do acesso web), sha256 de
 * snapshot+HTML e sha256 do arquivo PDF. Só a SITUAÇÃO muda (cancelar: administrador, com
 * motivo). Emitir de novo gera outra emissão; a 2ª via reimprime o MESMO HTML, com a marca
 * da via, e grava o hash do arquivo novo — mesmo código.
 *
 * Como no projeto de referência: a prévia monta tudo pelo MESMO caminho e não grava nada.
 */
class AdmsHistoricoEmissao
{
    public const ALFABETO = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
    public const SITUACOES = [1 => 'Válida', 2 => 'Cancelada'];
    public const SEP = "\x1e";

    private $erros = [];
    private $id = 0;
    private $via = 0;

    public function getErros(): array
    {
        return $this->erros;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getVia(): int
    {
        return $this->via;
    }

    /** E7 (D-220): o AMBIENTE da Config (producao se não definido). Vai na linha e no snapshot da emissão.
        Onda 2, Etapa 4: o ambiente EFETIVO — "demonstracao" também quando a demonstração foi ligada pela tela. */
    public static function ambiente(): string
    {
        $a = \App\adms\Models\helper\AdmsAmbiente::atual();
        return in_array($a, ['producao', 'homologacao', 'demonstracao'], true) ? $a : 'producao';
    }

    /** Tarja de toda página do PDF fora de produção. */
    public static function tarjaAmbiente(string $ambiente): string
    {
        return ['demonstracao' => 'DEMONSTRAÇÃO — documento fictício, sem validade.', 'homologacao' => 'HOMOLOGAÇÃO — documento de teste, sem validade.'][$ambiente] ?? '';
    }

    public static function pasta(): string
    {
        return AdmsHistoricoDocumento::storage() . 'historico/emissoes/';
    }

    public static function urlVerificacao(string $codigo): string
    {
        return (defined('URL') ? URL : '/') . 'verificar/' . $codigo;
    }

    /** "abcd efgh-jklm" → "ABCD-EFGH-JKLM"; qualquer outra coisa → ''. */
    public static function normalizarCodigo(string $c): string
    {
        $c = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $c));
        if (strlen($c) !== 12 || strspn($c, self::ALFABETO) !== 12) {
            return '';
        }
        return substr($c, 0, 4) . '-' . substr($c, 4, 4) . '-' . substr($c, 8, 4);
    }

    /** 12 símbolos de 32 (60 bits), por random_int: sem sequência, sem data, sem id. */
    public static function novoCodigo(): string
    {
        do {
            $c = '';
            for ($i = 0; $i < 12; $i++) {
                $c .= self::ALFABETO[random_int(0, 31)];
            }
            $c = self::normalizarCodigo($c);
        } while (self::porCodigo($c));
        return $c;
    }

    /**
     * Monta a emissão SEM gravar. `$secoes` só na prévia do editor (modelo ainda não salvo).
     * Devolve snap, html, alertas, bloqueios, paginacao — ou null (erros em getErros()).
     */
    public function preparar(int $mcId, string $tipo, int $modeloId, string $codigo, string $emissor, ?array $secoes = null, ?array $modeloInfo = null, string $origem = 'secretaria'): ?array
    {
        $this->erros = [];
        if (!isset(AdmsHistoricoModelos::TIPOS[$tipo])) {
            $this->erros[] = 'Tipo de histórico inválido.';
            return null;
        }
        $vida = AdmsVidaAcademica::montar($mcId, $tipo !== 'FINAL' ? date('Y-m-d') : null); // PARCIAL e ATESTADO: o ato legal de hoje
        if (!$vida) {
            $this->erros[] = 'Matrícula não encontrada.';
            return null;
        }
        if ($secoes === null) {
            $m = AdmsHistoricoModelos::ver($modeloId);
            $aplicaveis = array_map('intval', array_column(AdmsHistoricoModelos::aplicaveis($tipo, (int) $vida['matricula']['curso_id']), 'id'));
            if (!$m || $m['tipo'] !== $tipo || !$m['versao'] || !in_array($modeloId, $aplicaveis, true)) {
                $this->erros[] = 'Escolha um modelo ATIVO do tipo ' . AdmsHistoricoModelos::TIPOS[$tipo] . ' que sirva a este curso (Configurações → Modelos de histórico).';
                return null;
            }
            $secoes = $m['modelo'];
            $modeloInfo = ['id' => (int) $m['id'], 'nome' => $m['nome'], 'versao' => (int) $m['versao']['versao'], 'versaoId' => (int) $m['versao']['id']];
        }
        /* Correção da escola, Parte 3 (A3; D-102): na escola, a série e o ano letivo da matrícula entram na fotografia (o
           atestado da escola os usa no lugar da previsão de conclusão) */
        if (\App\adms\Models\helper\AdmsParametro::naEscola()) {
            $l = new \App\adms\Models\helper\AdmsRead();
            $l->fullRead('SELECT me.ano, ms.codnome FROM rm_matriculas_ensino me LEFT JOIN rm_cursos_turma_series ms ON ms.id = me.modulo_id WHERE me.matricula_curso_id = :mc AND me.ano <= :a ORDER BY me.ano DESC, me.id DESC LIMIT :limit',
                'mc=' . $mcId . '&a=' . (int) date('Y') . '&limit=1');
            $pos = $l->getResultado()[0] ?? null;
            $vida['matricula']['escolaSerie'] = $pos['codnome'] ?? null;
            $vida['matricula']['escolaAno'] = isset($pos['ano']) ? (int) $pos['ano'] : null;
        }
        $snap = [
            'vida' => $vida,
            'emissao' => ['tipo' => $tipo, 'codigo' => $codigo, 'url' => self::urlVerificacao($codigo), 'data' => date('Y-m-d H:i:s'), 'emissor' => $emissor, 'ambiente' => self::ambiente(),
                'origem' => $origem === 'portal' ? 'portal' : 'secretaria'], // E6 (D-372)
            'modelo' => $modeloInfo ?? ['id' => 0, 'nome' => '(prévia do editor)', 'versao' => 0, 'versaoId' => 0],
        ];
        $doc = new AdmsHistoricoDocumento();
        $html = $doc->montarHtml($secoes, $snap, $vazias);
        /* E6 (D-377): os alertas da vida acadêmica são do HISTÓRICO (dados pessoais, ato legal, docentes, CH); o
           atestado só olha o que ele mesmo usa. */
        $alertas = $tipo === 'ATESTADO' ? [] : $vida['alertas'];
        foreach ($vazias as $v) {
            $alertas[] = 'Falta um dado que o documento usa: ' . AdmsHistoricoVariaveis::rotulo((string) $v) . '.'; // D-102: sem o nome interno
        }
        $bloqueios = [];
        if ($tipo === 'ATESTADO') {
            /* E6 (D-377): só vínculo ATIVO (Cursando); sem a previsão de conclusão não sai (a frase ficaria quebrada);
               e nenhum dado vazio fora de trecho condicional (quem emite pelo portal não tem como "confirmar"). */
            if ((int) $vida['matricula']['situacao_id'] !== 1) {
                $bloqueios[] = 'O atestado de matrícula só é emitido com a matrícula em "Cursando" (situação atual: ' . ($vida['matricula']['situacao'] ?? '—') . ').';
            }
            if (empty($vida['matricula']['previsaoConclusao']) && !\App\adms\Models\helper\AdmsParametro::naEscola()) { // D-102: na escola, o atestado não usa a previsão
                $bloqueios[] = 'Falta a previsão de conclusão da matrícula: sem ela, o atestado não sai. Quem preenche é a secretaria (ficha do aluno → Matrículas → editar a matrícula).';
            }
            if ($alertas) {
                $bloqueios[] = 'O documento tem dado sem valor: ' . implode(' ', $alertas);
            }
        }        if ($tipo === 'FINAL') {
            if ((int) $vida['matricula']['situacao_id'] !== 2) {
                $bloqueios[] = 'O histórico FINAL só é emitido com a matrícula CONCLUÍDA (situação atual: ' . ($vida['matricula']['situacao'] ?? '—') . ').';
            }
            if ($alertas) {
                $bloqueios[] = 'O histórico FINAL só é emitido sem nenhum alerta (' . count($alertas) . ' alerta(s)).';
            }
        }
        return [
            'snap' => $snap, 'html' => $html, 'alertas' => $alertas, 'bloqueios' => $bloqueios,
            'paginacao' => AdmsHistoricoDocumento::paginacao($secoes), /* E8d (A24): formato 1 ou 2 */
        ];
    }

    /** Prévia: o mesmo documento, com a tarja em toda página. Nada é gravado. */
    public function previaPdf(int $mcId, string $tipo, int $modeloId, string $emissor, ?array $secoes = null): ?string
    {
        $p = $this->preparar($mcId, $tipo, $modeloId, 'PREVIA', $emissor, $secoes);
        if (!$p) {
            return null;
        }
        $doc = new AdmsHistoricoDocumento();
        $pdf = $doc->pdf($p['html'], AdmsHistoricoDocumento::TARJA_PREVIA, '', '', $p['paginacao']);
        if ($pdf === null) {
            $this->erros[] = $doc->getErro();
        }
        return $pdf;
    }

    /**
     * E6 (D-372): `$origem` = secretaria (painel; $usuarioId = adms_usuarios.id) ou portal (o próprio aluno; $usuarioId = 0
     * e $alunoId = col_usuarios.id). Pelo portal só sai ATESTADO — quem chama (Controllers\Atestado do portal) confere o dono.
     */
    public function emitir(int $mcId, string $tipo, int $modeloId, bool $confirmou, int $usuarioId, string $emissor, string $origem = 'secretaria', ?int $alunoId = null): bool
    {
        if ($origem === 'portal' && $tipo !== 'ATESTADO') {
            $this->erros = ['Pelo portal só se emite o atestado de matrícula.'];
            return false;
        }
        /* Onda 8, correção, segunda leva, 2.1 (D-87): na escola, também pelo painel, só o atestado */
        if ($tipo !== 'ATESTADO' && \App\adms\Models\helper\AdmsTipoInstituicao::ehEscola()) {
            $this->erros = ['Na escola, o painel emite só o atestado de matrícula.'];
            return false;
        }
        $codigo = self::novoCodigo();
        $p = $this->preparar($mcId, $tipo, $modeloId, $codigo, $emissor, null, null, $origem);
        if (!$p) {
            return false;
        }
        if ($p['bloqueios']) {
            $this->erros = $p['bloqueios'];
            return false;
        }
        if ($p['alertas'] && !$confirmou) {
            $this->erros[] = 'Há ' . count($p['alertas']) . ' alerta(s): confirme que os leu para emitir o PARCIAL assim mesmo.';
            return false;
        }
        $snapshot = AdmsJsonCanonico::codificar($p['snap']);
        $doc = new AdmsHistoricoDocumento();
        $pdf = $doc->pdf($p['html'], self::tarjaAmbiente(self::ambiente()), '', $codigo, $p['paginacao']);
        if ($pdf === null) {
            $this->erros[] = $doc->getErro();
            return false;
        }
        $arquivo = $codigo . '.pdf';
        if (!is_dir(self::pasta()) || file_put_contents(self::pasta() . $arquivo, $pdf) === false) {
            $this->erros[] = 'Não foi possível gravar o PDF.';
            return false;
        }
        $criar = new \App\adms\Models\helper\AdmsCreate();
        $criar->exeCreate('rm_historico_emissoes', [
            'codigo' => $codigo, 'tipo' => $tipo, 'matricula_curso_id' => $mcId, 'aluno_id' => (int) $p['snap']['vida']['aluno']['id'],
            'modelo_id' => $p['snap']['modelo']['id'], 'modelo_versao_id' => $p['snap']['modelo']['versaoId'],
            'snapshot' => $snapshot, 'html' => $p['html'], 'hash_conteudo' => hash('sha256', $snapshot . self::SEP . $p['html']),
            'pdf_arquivo' => $arquivo, 'hash_pdf' => hash('sha256', $pdf),
            'alertas_aceitos' => $p['alertas'] ? AdmsJsonCanonico::codificar(array_values($p['alertas'])) : null,
            'emitido_por' => $usuarioId, 'emitido_em' => $p['snap']['emissao']['data'], 'situacao' => 1, 'ambiente' => self::ambiente(),
            'origem' => $p['snap']['emissao']['origem'], 'emitido_por_aluno' => $origem === 'portal' ? $alunoId : null, // E6 (D-372)
        ]);
        $this->id = (int) $criar->getResultado();
        if (!$this->id) {
            @unlink(self::pasta() . $arquivo);
            $this->erros[] = 'Não foi possível gravar a emissão.';
            return false;
        }
        return true;
    }

    /** 2ª via (e seguintes): o MESMO HTML gravado, com a marca da via; registra quem e quando. */
    public function segundaVia(int $id, int $usuarioId): bool
    {
        $this->erros = [];
        $e = self::ver($id);
        if (!$e) {
            $this->erros[] = 'Emissão não encontrada.';
            return false;
        }
        /* o histórico escolar da educação básica (D-114) tem o seu desenho e o seu rodapé: a 2ª via é a dele */
        if (str_starts_with((string) $e['tipo'], 'ESC_')) {
            $x = new \App\adms\Models\historicoEscola\AdmsHistEmissao();
            $ok = $x->segundaVia($id, $usuarioId);
            $this->erros = $x->getErros();
            $this->via = $x->getVia();
            return $ok;
        }
        if ((int) $e['situacao'] !== 1) {
            $this->erros[] = 'Emissão cancelada não tem 2ª via.';
            return false;
        }
        if (!self::integra($e)) {
            $this->erros[] = 'Os dados gravados desta emissão NÃO conferem com o hash: 2ª via recusada.';
            return false;
        }
        $this->via = count($e['vias']) + 2;
        $agora = date('Y-m-d H:i:s');
        $marca = $this->via . 'ª VIA — reimpressão de ' . date('d/m/Y H:i', strtotime($agora)) . ' do documento emitido em ' . date('d/m/Y H:i', strtotime($e['emitido_em']));
        $doc = new AdmsHistoricoDocumento();
        $pdf = $doc->pdf($e['html'], self::tarjaAmbiente((string) ($e['ambiente'] ?? 'producao')), $marca, $e['codigo'], self::paginacao((int) $e['modelo_versao_id']));
        if ($pdf === null) {
            $this->erros[] = $doc->getErro();
            return false;
        }
        $arquivo = $e['codigo'] . '_via' . $this->via . '.pdf';
        if (file_put_contents(self::pasta() . $arquivo, $pdf) === false) {
            $this->erros[] = 'Não foi possível gravar o PDF.';
            return false;
        }
        $criar = new \App\adms\Models\helper\AdmsCreate();
        $criar->exeCreate('rm_historico_emissao_vias', ['emissao_id' => $id, 'via' => $this->via, 'pdf_arquivo' => $arquivo, 'hash_pdf' => hash('sha256', $pdf), 'emitido_por' => $usuarioId, 'emitido_em' => $agora]);
        return (bool) $criar->getResultado();
    }

    public function cancelar(int $id, string $motivo, int $usuarioId): bool
    {
        $this->erros = [];
        $e = self::ver($id);
        $motivo = trim(strip_tags($motivo));
        if (!$e) {
            $this->erros[] = 'Emissão não encontrada.';
        } elseif ((int) $e['situacao'] !== 1) {
            $this->erros[] = 'A emissão já está cancelada.';
        }
        if (mb_strlen($motivo) < 5 || mb_strlen($motivo) > 500) {
            $this->erros[] = 'Informe o motivo do cancelamento (de 5 a 500 caracteres).';
        }
        if ($this->erros) {
            return false;
        }
        $up = new \App\adms\Models\helper\AdmsUpdate();
        $up->exeUpdate('rm_historico_emissoes', ['situacao' => 2, 'cancelado_por' => $usuarioId, 'cancelado_em' => date('Y-m-d H:i:s'), 'cancelamento_motivo' => $motivo], 'WHERE id = :id AND situacao = :s', "id={$id}&s=1");
        return (bool) $up->getResultado();
    }

    private static function paginacao(int $versaoId): bool
    {
        $v = AdmsHistoricoModelos::versao($versaoId);
        return AdmsHistoricoDocumento::paginacao((array) json_decode((string) ($v['secoes'] ?? ''), true));
    }

    private const CAMPOS = "e.*, u.nome AS aluno, u.rm, c.nome AS curso, m.nome AS modelo, v.versao AS modeloVersao,
                   IF(e.origem = 'portal', CONCAT(u.nome, ' (o próprio aluno, pelo portal)'), pe.nome) AS emissor, pc.nome AS cancelador";
    private const JUNCOES = "FROM rm_historico_emissoes e
               INNER JOIN col_usuarios u ON u.id = e.aluno_id
               INNER JOIN rm_matriculas_curso mc ON mc.id = e.matricula_curso_id
               INNER JOIN rm_cursos c ON c.id = mc.curso_id
               LEFT JOIN rm_historico_modelos m ON m.id = e.modelo_id
               LEFT JOIN rm_historico_modelo_versoes v ON v.id = e.modelo_versao_id
               LEFT JOIN adms_usuarios pe ON pe.id = e.emitido_por
               LEFT JOIN adms_usuarios pc ON pc.id = e.cancelado_por";

    public static function ver(int $id): ?array
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT " . self::CAMPOS . " " . self::JUNCOES . " WHERE e.id = :id LIMIT :limit", "id={$id}&limit=1");
        $e = $ler->getResultado()[0] ?? null;
        if ($e) {
            $ler = new \App\adms\Models\helper\AdmsRead();
            $ler->fullRead("SELECT x.*, u.nome AS emissor FROM rm_historico_emissao_vias x LEFT JOIN adms_usuarios u ON u.id = x.emitido_por WHERE x.emissao_id = :id ORDER BY x.via", "id={$id}");
            $e['vias'] = $ler->getResultado() ?: [];
        }
        return $e;
    }

    /** Filtros: busca (RA ou nome), tipo, situacao, aluno (id). */
    public static function listar(array $f = []): array
    {
        $onde = [];
        $par = [];
        $busca = trim((string) ($f['busca'] ?? ''));
        if ($busca !== '') {
            $onde[] = '(CAST(u.rm AS CHAR) = :rm OR u.nome LIKE :nome OR e.codigo = :cod)';
            $par[] = 'rm=' . urlencode($busca) . '&nome=' . urlencode('%' . addcslashes($busca, '%_\\') . '%') . '&cod=' . urlencode(self::normalizarCodigo($busca) ?: '-');
        }
        if (isset(AdmsHistoricoModelos::TIPOS[$f['tipo'] ?? ''])) {
            $onde[] = 'e.tipo = :tipo';
            $par[] = 'tipo=' . $f['tipo'];
        }
        if (isset(self::SITUACOES[(int) ($f['situacao'] ?? 0)])) {
            $onde[] = 'e.situacao = :sit';
            $par[] = 'sit=' . (int) $f['situacao'];
        }
        if (!empty($f['aluno'])) {
            $onde[] = 'e.aluno_id = :aluno';
            $par[] = 'aluno=' . (int) $f['aluno'];
        }
        $par[] = 'limit=200';
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT e.id, e.codigo, e.tipo, e.situacao, e.emitido_em, e.alertas_aceitos, u.nome AS aluno, u.rm, c.nome AS curso, m.nome AS modelo, v.versao AS modeloVersao, IF(e.origem = 'portal', CONCAT(u.nome, ' (portal)'), pe.nome) AS emissor,
                (SELECT COUNT(*) FROM rm_historico_emissao_vias x WHERE x.emissao_id = e.id) AS vias,
                /* 1.14.0 (E9, D-415): ofertas do aluno, no curso desta matrícula, REABERTAS depois da emissão (o atestado não
                   traz notas: fica de fora) */
                (SELECT COUNT(DISTINCT x.rm_prof_mat_id) FROM rm_professores_materias_encerramentos x INNER JOIN rm_professores_materias o ON o.id = x.rm_prof_mat_id
                    INNER JOIN portal_disciplinas_alunos pa ON pa.rm_prof_mat_id = x.rm_prof_mat_id AND pa.rm = u.rm
                    WHERE x.acao = 'reabrir' AND x.created > e.emitido_em AND o.curso_id = mc.curso_id AND e.tipo <> 'ATESTADO') AS reaberturas " . self::JUNCOES
            . ($onde ? ' WHERE ' . implode(' AND ', $onde) : '') . " ORDER BY e.emitido_em DESC, e.id DESC LIMIT :limit", implode('&', $par));
        return $ler->getResultado() ?: [];
    }

    /** O aviso padrão (D-415). */
    public const AVISO_REABERTURA = 'Há emissão anterior a uma reabertura; confira se precisa emitir de novo.';

    /**
     * 1.14.0 (E9, D-415): as emissões (histórico parcial ou final, válidas) da matrícula `$mcId` feitas ANTES da reabertura
     * de alguma disciplina do aluno no mesmo curso. A emissão continua válida na verificação; é só um aviso.
     */
    public static function emitidasAntesDeReabertura(int $mcId): array
    {
        return self::emitidasAntesDeReaberturaLote([$mcId])[$mcId] ?? [];
    }

    /** 1.17.2 (D-464): a mesma consulta para várias matrículas de uma vez (o relatório de documentos): [mcId => [emissões]]. */
    public static function emitidasAntesDeReaberturaLote(array $mcIds): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $mcIds))));
        if (!$ids) {
            return [];
        }
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT e.matricula_curso_id, e.id, e.codigo, e.tipo, e.emitido_em, (SELECT COUNT(DISTINCT x.rm_prof_mat_id) FROM rm_professores_materias_encerramentos x INNER JOIN rm_professores_materias o ON o.id = x.rm_prof_mat_id
                    INNER JOIN portal_disciplinas_alunos pa ON pa.rm_prof_mat_id = x.rm_prof_mat_id AND pa.rm = u.rm
                    WHERE x.acao = 'reabrir' AND x.created > e.emitido_em AND o.curso_id = mc.curso_id AND e.tipo <> 'ATESTADO') AS reaberturas
               FROM rm_historico_emissoes e INNER JOIN col_usuarios u ON u.id = e.aluno_id
               INNER JOIN rm_matriculas_curso mc ON mc.id = e.matricula_curso_id
              WHERE e.matricula_curso_id IN (" . implode(',', $ids) . ") AND e.situacao = :s ORDER BY e.emitido_em DESC", "s=1");
        $por = [];
        foreach ($ler->getResultado() ?: [] as $x) {
            if ((int) $x['reaberturas'] > 0) {
                $mc = (int) $x['matricula_curso_id'];
                unset($x['matricula_curso_id']);
                $por[$mc][] = $x;
            }
        }
        return $por;
    }

    public static function porCodigo(string $codigo): ?array
    {
        if ($codigo === '') {
            return null;
        }
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT id FROM rm_historico_emissoes WHERE codigo = :c LIMIT :limit", "c=" . urlencode($codigo) . "&limit=1");
        $id = (int) ($ler->getResultado()[0]['id'] ?? 0);
        return $id ? self::ver($id) : null;
    }

    /** Recalcula o sha256 de snapshot+HTML GRAVADOS e compara com o gravado. */
    public static function integra(array $e): bool
    {
        return hash_equals((string) $e['hash_conteudo'], hash('sha256', $e['snapshot'] . self::SEP . $e['html']));
    }

    /** A que arquivo emitido corresponde o sha256 (original ou via N) — ou null. */
    public static function conferirPdf(array $e, string $sha): ?string
    {
        if (hash_equals((string) $e['hash_pdf'], $sha)) {
            return 'ao documento original';
        }
        foreach ($e['vias'] as $v) {
            if (hash_equals((string) $v['hash_pdf'], $sha)) {
                return 'à ' . (int) $v['via'] . 'ª via, de ' . date('d/m/Y H:i', strtotime($v['emitido_em']));
            }
        }
        return null;
    }

    /** Caminho do PDF (via 1 = original) — só o nome gravado, nunca o que vem da URL. */
    public static function arquivoPdf(array $e, int $via = 1): ?string
    {
        $nome = $e['pdf_arquivo'];
        if ($via > 1) {
            $x = array_values(array_filter($e['vias'], fn($v) => (int) $v['via'] === $via))[0] ?? null;
            $nome = $x['pdf_arquivo'] ?? null;
        }
        $caminho = $nome ? self::pasta() . basename($nome) : null;
        return $caminho && is_file($caminho) ? $caminho : null;
    }

    /** E6c (D-201): os 4 gatilhos de imutabilidade estão no banco? (sem o privilégio TRIGGER na instalação, não) */
    public static function gatilhosAtivos(): bool
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT COUNT(*) AS n FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = DATABASE() AND TRIGGER_NAME IN ('rm_historico_emissoes_imutavel', 'rm_historico_emissoes_sem_apagar', 'rm_historico_emissao_vias_imutavel', 'rm_historico_emissao_vias_sem_apagar')");
        return (int) ($ler->getResultado()[0]['n'] ?? 0) === 4;
    }

    /**
     * CPF mascarado para a verificação pública. E1b (D-324, D-358): a MESMA máscara do documento (3 + 2 dígitos
     * visíveis, 123.xxx.xxx-45), numa função só (AdmsHistoricoVariaveis::cpfMascarado). Antes: ***.***.789-**.
     */
    public static function cpfMascarado(?string $cpf): string
    {
        $m = AdmsHistoricoVariaveis::cpfMascarado($cpf);
        return $m !== '' ? $m : '—';
    }
}
