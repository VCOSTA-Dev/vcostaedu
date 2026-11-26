<?php

namespace App\adms\Models\historicoEscola;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\historico\AdmsHistoricoEmissao as Emissao;
use App\adms\Models\historico\AdmsJsonCanonico as Json;
use App\adms\Models\historicoEscola\AdmsHistBase as B;
use App\adms\Models\historicoEscola\AdmsHistCadastros as C;

/**
 * A EMISSÃO DO HISTÓRICO ESCOLAR (Parte E; D-114): a prévia (o mesmo documento, marcado PRÉVIA, nada gravado), a
 * emissão pela emissão guardada de sempre (rm_historico_emissoes: o código de verificação aleatório, a cópia do conteúdo
 * em JSON canônico, o HTML, o PDF fora do acesso web, o sha256 do conteúdo e o do PDF, a conferência pública; a decisão
 * 5), com o tipo próprio da escola (ESC_EF) — o do superior não muda; a 2ª via (a reimpressão do mesmo HTML, com o
 * número da via no rodapé; a decisão 16); o cancelamento com motivo (a via cancelada fica guardada).
 * Só "o quadro não cabe" e "sem série" impedem a emissão; os outros avisos ficam com quem emite, que confirma que os leu
 * (a cópia guarda os avisos aceitos).
 */
class AdmsHistEmissao
{
    public const TIPOS = ['EF' => 'ESC_EF', 'EM' => 'ESC_EM'];
    private array $erros = [];
    private int $id = 0;
    private int $via = 0;

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

    /** A opção do formulário: a data, a publicação, os dias letivos e as aulas dadas (o bloco de transferência). */
    public static function opcoes(array $src): array
    {
        return ['data' => (string) ($src['data'] ?? ''), 'publicacao' => mb_substr(trim((string) ($src['publicacao'] ?? '')), 0, 60),
            'dias_letivos' => (string) ($src['dias_letivos'] ?? ''), 'aulas' => array_map('strval', (array) ($src['aulas'] ?? []))];
    }

    /**
     * Monta o documento e o renderiza (os degraus). `$codigo` vazio = a prévia.
     * @return array{dados: array, avisos: list<string>, bloqueios: list<string>, pdf: ?string, html: string, degrau: int}
     */
    public function preparar(int $alunoId, string $nivel, array $op, string $codigo = '', string $via = ''): array
    {
        $m = AdmsHistDocumento::montar($alunoId, $nivel, $op);
        $r = $m + ['pdf' => null, 'html' => '', 'degrau' => -1];
        if ($m['bloqueios']) {
            return $r;
        }
        $previa = $codigo === '';
        $verif = ['codigo' => $previa ? 'PRÉVIA' : $codigo, 'url' => Emissao::urlVerificacao($previa ? '' : $codigo)];
        $rod = ['codigo' => $previa ? '' : $codigo, 'via' => $via, 'paginacao' => true,
            'tarja' => $previa ? AdmsHistDocumento::TARJA_PREVIA : Emissao::tarjaAmbiente(Emissao::ambiente())];
        $x = AdmsHistDocumento::renderizar($m['dados'], $verif, $rod);
        if ($x['pdf'] === null) {
            $r['bloqueios'][] = AdmsHistPdf::erro() !== '' ? AdmsHistPdf::erro() : 'O quadro não cabe: nenhum tamanho de letra deu frente e verso em duas páginas (no menor: ' . $x['paginas'] . ' páginas, até ' . number_format((float) ($x['largura'] ?? 0), 1, ',', '') . ' pt de largura, o limite é ' . number_format(AdmsHistDocumento::LIMITE_X, 0) . ').';
            $r['html'] = $x['html'];
            return $r;
        }
        return array_merge($r, ['pdf' => $x['pdf'], 'html' => $x['html'], 'degrau' => $x['degrau']]);
    }

    /** A matrícula do aluno num segmento do nível (a mais nova): a emissão guardada é ligada a uma matrícula. */
    public static function matricula(int $alunoId, string $nivel): int
    {
        $cursos = C::cursosDoNivel($nivel);
        if (!$cursos) {
            return 0;
        }
        return (int) B::valor('SELECT id FROM rm_matriculas_curso WHERE usuario_id = ? AND curso_id IN (' . implode(',', $cursos) . ') ORDER BY data_ingresso DESC, id DESC LIMIT 1', [$alunoId]);
    }

    public function emitir(int $alunoId, string $nivel, array $op, bool $confirmou, int $usuarioId, string $emissor): bool
    {
        $this->erros = [];
        if (!isset(self::TIPOS[$nivel])) {
            $this->erros[] = 'Nível inválido.';
            return false;
        }
        $mc = self::matricula($alunoId, $nivel);
        if (!$mc) {
            $this->erros[] = 'O aluno não tem matrícula num segmento do ' . B::NIVEIS[$nivel] . ' (a emissão guardada é ligada a ela).';
            return false;
        }
        $codigo = Emissao::novoCodigo();
        $p = $this->preparar($alunoId, $nivel, $op, $codigo, '1ª via');
        if ($p['bloqueios']) {
            $this->erros = $p['bloqueios'];
            return false;
        }
        if ($p['avisos'] && !$confirmou) {
            $this->erros[] = 'Há ' . count($p['avisos']) . ' aviso(s): confirme que os leu para emitir assim mesmo.';
            return false;
        }
        $agora = date('Y-m-d H:i:s');
        $d = $p['dados'];
        $snap = [
            'vida' => ['instituicao' => ['nome' => (string) $d['texto']['escola_nome']], 'matricula' => ['id' => $mc, 'curso' => B::NIVEIS[$nivel]],
                'aluno' => ['id' => $alunoId, 'nome' => (string) $d['aluno']['nome'], 'rm' => (string) $d['aluno']['rm'], 'cpf' => null]],
            'historico' => $d,
            'emissao' => ['tipo' => self::TIPOS[$nivel], 'codigo' => $codigo, 'url' => Emissao::urlVerificacao($codigo), 'data' => $agora, 'emissor' => $emissor,
                'ambiente' => Emissao::ambiente(), 'origem' => 'secretaria', 'degrau' => $p['degrau'], 'via' => 1, 'avisos' => $p['avisos']],
            'modelo' => ['id' => 0, 'nome' => 'Histórico escolar — ' . B::NIVEIS[$nivel], 'versao' => 0, 'versaoId' => 0],
        ];
        unset($snap['historico']['logo']); // o logo vai no HTML guardado; no conteúdo, só o resto
        $snapshot = Json::codificar($snap);
        $arquivo = $codigo . '.pdf';
        $pasta = Emissao::pasta();
        if (!is_dir($pasta)) {
            @mkdir($pasta, 0775, true);
        }
        if (file_put_contents($pasta . $arquivo, $p['pdf']) === false) {
            $this->erros[] = 'Não foi possível gravar o PDF.';
            return false;
        }
        $criar = new \App\adms\Models\helper\AdmsCreate();
        $criar->exeCreate('rm_historico_emissoes', [
            'codigo' => $codigo, 'tipo' => self::TIPOS[$nivel], 'matricula_curso_id' => $mc, 'aluno_id' => $alunoId, 'modelo_id' => 0, 'modelo_versao_id' => 0,
            'snapshot' => $snapshot, 'html' => $p['html'], 'hash_conteudo' => hash('sha256', $snapshot . Emissao::SEP . $p['html']),
            'pdf_arquivo' => $arquivo, 'hash_pdf' => hash('sha256', $p['pdf']),
            'alertas_aceitos' => $p['avisos'] ? Json::codificar(array_values($p['avisos'])) : null,
            'emitido_por' => $usuarioId, 'emitido_em' => $agora, 'situacao' => 1, 'ambiente' => Emissao::ambiente(), 'origem' => 'secretaria', 'emitido_por_aluno' => null,
        ]);
        $this->id = (int) $criar->getResultado();
        if (!$this->id) {
            @unlink($pasta . $arquivo);
            $this->erros[] = 'Não foi possível gravar a emissão.';
            return false;
        }
        return true;
    }

    /** A 2ª via (e as seguintes): o MESMO HTML guardado, com o número da via no rodapé; o mesmo código. */
    public function segundaVia(int $id, int $usuarioId): bool
    {
        $this->erros = [];
        $e = Emissao::ver($id);
        if (!$e || !in_array($e['tipo'], self::TIPOS, true)) {
            $this->erros[] = 'Emissão não encontrada.';
            return false;
        }
        if ((int) $e['situacao'] !== 1) {
            $this->erros[] = 'O histórico cancelado não tem 2ª via.';
            return false;
        }
        if (!Emissao::integra($e)) {
            $this->erros[] = 'Os dados guardados desta emissão NÃO conferem com o hash: 2ª via recusada.';
            return false;
        }
        $this->via = count($e['vias']) + 2;
        $agora = date('Y-m-d H:i:s');
        $pdf = AdmsHistPdf::gerar((string) $e['html'], 'portrait', ['codigo' => (string) $e['codigo'], 'paginacao' => true,
            'via' => $this->via . 'ª via (reimpressão de ' . date('d/m/Y', strtotime($agora)) . ')', 'tarja' => Emissao::tarjaAmbiente((string) ($e['ambiente'] ?? 'producao'))]);
        if ($pdf === null) {
            $this->erros[] = AdmsHistPdf::erro();
            return false;
        }
        $arquivo = $e['codigo'] . '_via' . $this->via . '.pdf';
        if (file_put_contents(Emissao::pasta() . $arquivo, $pdf) === false) {
            $this->erros[] = 'Não foi possível gravar o PDF.';
            return false;
        }
        $criar = new \App\adms\Models\helper\AdmsCreate();
        $criar->exeCreate('rm_historico_emissao_vias', ['emissao_id' => $id, 'via' => $this->via, 'pdf_arquivo' => $arquivo, 'hash_pdf' => hash('sha256', $pdf), 'emitido_por' => $usuarioId, 'emitido_em' => $agora]);
        return (bool) $criar->getResultado();
    }

    public function cancelar(int $id, string $motivo, int $usuarioId): bool
    {
        $e = Emissao::ver($id);
        if (!$e || !in_array($e['tipo'], self::TIPOS, true)) {
            $this->erros = ['Emissão não encontrada.'];
            return false;
        }
        $x = new Emissao();
        $ok = $x->cancelar($id, $motivo, $usuarioId);
        $this->erros = $x->getErros();
        return $ok;
    }

    /** As emissões do histórico escolar do aluno (as mais novas primeiro), com as vias. */
    public static function doAluno(int $alunoId): array
    {
        $r = [];
        foreach (Emissao::listar(['aluno' => $alunoId]) as $l) {
            if (in_array($l['tipo'], self::TIPOS, true)) {
                $r[] = Emissao::ver((int) $l['id']);
            }
        }
        return $r;
    }

    /** O PDF guardado — só se o sha256 do arquivo confere com o gravado. @return string|null */
    public static function pdfConferido(int $id, int $via = 1): ?string
    {
        $e = Emissao::ver($id);
        if (!$e || !in_array($e['tipo'], self::TIPOS, true)) {
            return null;
        }
        $arq = Emissao::arquivoPdf($e, $via);
        if (!$arq) {
            return null;
        }
        $esperado = $via > 1 ? (array_values(array_filter($e['vias'], fn($v) => (int) $v['via'] === $via))[0]['hash_pdf'] ?? '') : (string) $e['hash_pdf'];
        $bin = (string) file_get_contents($arq);
        return hash_equals($esperado, hash('sha256', $bin)) ? $bin : null;
    }
}
