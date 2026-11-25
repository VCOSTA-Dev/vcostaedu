<?php

namespace App\adms\Models\historico;

if (!defined('VCOSTAEDU')) { // E6 (D-376): o portal também carrega (atestado do aluno)
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * MODELOS de histórico (E6b): cadastro, versões e cursos a que se aplicam.
 *
 * VERSÃO: salvar seções diferentes das da versão atual cria uma NOVA versão quando o modelo
 * está ATIVO ou quando a versão atual já foi usada numa emissão; só um rascunho nunca usado
 * é alterado no lugar. A versão usada numa emissão nunca muda (a emissão guarda o id dela).
 *
 * Assinaturas: imagem PNG/JPEG conferida pelo CONTEÚDO (como o logo da E6a), regravada pelo
 * GD como PNG com nome pelo hash do conteúdo, em <PASTA_STORAGE>historico/assinaturas/ (fora do git
 * e negada à web). Nunca é apagada: versões antigas podem apontar para ela.
 */
class AdmsHistoricoModelos
{
    public const SITUACOES = [1 => 'Rascunho', 2 => 'Ativo', 3 => 'Arquivado'];
    public const TIPOS = ['PARCIAL' => 'Parcial', 'FINAL' => 'Final', 'ATESTADO' => 'Atestado de matrícula']; // E6 (D-371)

    /** E6 (D-371): o nome do documento por tipo — telas, verificação pública e nome do arquivo usam este. */
    public static function nomeDocumento(string $tipo): string
    {
        return ['PARCIAL' => 'Histórico escolar parcial', 'FINAL' => 'Histórico escolar final', 'ATESTADO' => 'Atestado de matrícula'][$tipo] ?? 'Documento';
    }
    public const MAX_ASSINATURA = 1048576;

    private $erros = [];
    private $id = 0;
    private $novaVersao = false;
    private $ajustes = [];

    public function getErros(): array
    {
        return $this->erros;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function criouVersao(): bool
    {
        return $this->novaVersao;
    }

    /** E8d (A24): formatação que a lista branca tirou em silêncio ao salvar (para o aviso). */
    public function getAjustes(): array
    {
        return $this->ajustes;
    }

    public static function pastaAssinaturas(): string
    {
        return AdmsHistoricoDocumento::storage() . 'historico/assinaturas/';
    }

    /** D-102 (A3): o modelo marcado para um tipo de instituição (o atestado da escola) só existe nela. */
    private static function comTipo(): bool
    {
        return \App\adms\Models\helper\AdmsAtualizacaoPendente::exige('0083');
    }

    private static function doTipo(string $a): string
    {
        return self::comTipo() ? " AND ({$a}.tipo_instituicao IS NULL OR {$a}.tipo_instituicao = '" . (\App\adms\Models\helper\AdmsParametro::naEscola() ? 'escola' : 'superior') . "')" : '';
    }

    private static function primeiroODoTipo(string $a): string
    {
        return self::comTipo() ? "({$a}.tipo_instituicao IS NOT NULL) DESC, " : '';
    }

    public static function listar(): array
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead(
            "SELECT m.*, (SELECT GROUP_CONCAT(c.nome ORDER BY c.nome SEPARATOR ', ') FROM rm_historico_modelo_cursos mc INNER JOIN rm_cursos c ON c.id = mc.curso_id WHERE mc.modelo_id = m.id) AS cursos,
                    (SELECT COUNT(*) FROM rm_historico_emissoes e WHERE e.modelo_id = m.id) AS emissoes,
                    (SELECT COUNT(*) FROM rm_historico_modelo_versoes v WHERE v.modelo_id = m.id) AS versoes
               FROM rm_historico_modelos m WHERE 1 = 1" . self::doTipo('m') . " ORDER BY m.tipo DESC, m.situacao, m.nome"
        );
        return $ler->getResultado() ?: [];
    }

    /** O modelo com a versão atual (seções decodificadas) e os ids dos cursos. */
    public static function ver(int $id): ?array
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT * FROM rm_historico_modelos WHERE id = :id LIMIT :limit", "id={$id}&limit=1");
        $m = $ler->getResultado()[0] ?? null;
        if (!$m) {
            return null;
        }
        $v = self::versaoPorNumero($id, (int) $m['versao_atual']);
        $m['versao'] = $v;
        $m['modelo'] = $v ? json_decode($v['secoes'], true) : AdmsHistoricoEditor::padrao($m['tipo']);
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT curso_id FROM rm_historico_modelo_cursos WHERE modelo_id = :id", "id={$id}");
        $m['cursos'] = array_map('intval', array_column($ler->getResultado() ?: [], 'curso_id'));
        return $m;
    }

    public static function versaoPorNumero(int $modeloId, int $versao): ?array
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT * FROM rm_historico_modelo_versoes WHERE modelo_id = :m AND versao = :v LIMIT :limit", "m={$modeloId}&v={$versao}&limit=1");
        return $ler->getResultado()[0] ?? null;
    }

    public static function versao(int $versaoId): ?array
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT v.*, m.nome, m.tipo FROM rm_historico_modelo_versoes v INNER JOIN rm_historico_modelos m ON m.id = v.modelo_id WHERE v.id = :id LIMIT :limit", "id={$versaoId}&limit=1");
        return $ler->getResultado()[0] ?? null;
    }

    /** Modelos ATIVOS do tipo que servem ao curso: os do curso primeiro, depois os padrão (sem curso). */
    public static function aplicaveis(string $tipo, int $cursoId): array
    {
        /* D-102 (A3): na escola, o atestado da escola existe (idempotente; a instalação cujo tipo foi gravado direto no banco) */
        if ($tipo === 'ATESTADO' && self::comTipo() && \App\adms\Models\helper\AdmsParametro::naEscola()) {
            require_once dirname(__DIR__, 5) . '/core/AtestadoEscola.php';
            \VcAtestadoEscola::aplicar(\VcBanco::daRequisicao(), 'escola');
        }
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead(
            "SELECT m.id, m.nome, m.versao_atual, EXISTS(SELECT 1 FROM rm_historico_modelo_cursos x WHERE x.modelo_id = m.id AND x.curso_id = :c1) AS doCurso
               FROM rm_historico_modelos m
              WHERE m.tipo = :t AND m.situacao = :s
                AND (EXISTS(SELECT 1 FROM rm_historico_modelo_cursos x WHERE x.modelo_id = m.id AND x.curso_id = :c2)
                     OR NOT EXISTS(SELECT 1 FROM rm_historico_modelo_cursos x WHERE x.modelo_id = m.id))" . self::doTipo('m') . "
              ORDER BY " . self::primeiroODoTipo('m') . "doCurso DESC, m.nome",
            "c1={$cursoId}&t=" . urlencode($tipo) . "&s=2&c2={$cursoId}"
        );
        return $ler->getResultado() ?: [];
    }

    public static function cursos(): array
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT id, nome FROM rm_cursos WHERE situacao = :s ORDER BY nome", "s=1");
        return $ler->getResultado() ?: [];
    }

    private static function versaoUsada(int $versaoId): bool
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT id FROM rm_historico_emissoes WHERE modelo_versao_id = :v LIMIT :limit", "v={$versaoId}&limit=1");
        return (bool) $ler->getResultado();
    }

    /**
     * Cadastra (id 0) ou salva o modelo. `$post`: nome, tipo (só no cadastro), situacao, cursos[] e o
     * formulário do editor (E8d, A24: documento, quadro[...], assinaturas[...], opcoes[...]).
     * `$arquivos`: $_FILES['assinatura_img'] (por índice de bloco).
     * Todo salvamento grava no FORMATO 2; um modelo ainda no formato 1 vira o 2 na próxima versão.
     */
    public function salvar(int $id, array $post, array $arquivos, int $usuarioId): bool
    {
        $this->erros = [];
        $atual = $id > 0 ? self::ver($id) : null;
        if ($id > 0 && !$atual) {
            $this->erros[] = 'Modelo não encontrado.';
            return false;
        }
        $tipo = $atual ? $atual['tipo'] : strtoupper(trim((string) ($post['tipo'] ?? '')));
        if (!isset(self::TIPOS[$tipo])) {
            $this->erros[] = 'Escolha o tipo: Parcial, Final ou Atestado de matrícula.';
        }
        $nome = trim(strip_tags((string) ($post['nome'] ?? '')));
        if ($nome === '' || mb_strlen($nome) > 150) {
            $this->erros[] = 'Informe o nome do modelo (até 150 caracteres).';
        }
        $situacao = (int) ($post['situacao'] ?? 1);
        if (!isset(self::SITUACOES[$situacao])) {
            $this->erros[] = 'Situação inválida.';
        }
        $validos = array_map('intval', array_column(self::cursos(), 'id'));
        $cursos = array_values(array_unique(array_map('intval', (array) ($post['cursos'] ?? []))));
        foreach ($cursos as $c) {
            if (!in_array($c, $validos, true)) {
                $this->erros[] = 'Curso inválido na lista de cursos.';
                break;
            }
        }
        $imagens = [];
        foreach ((array) ($arquivos['error'] ?? []) as $i => $err) {
            if ((int) $err === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $f = ['name' => $arquivos['name'][$i] ?? '', 'tmp_name' => $arquivos['tmp_name'][$i] ?? '', 'error' => (int) $err, 'size' => (int) ($arquivos['size'][$i] ?? 0)];
            $r = self::gravarAssinatura($f);
            if (is_string($r) && str_starts_with($r, 'assin_')) {
                $imagens[$i] = $r;
            } else {
                $this->erros[] = 'Assinatura ' . ((int) $i + 1) . ': ' . $r;
            }
        }
        $secoes = AdmsHistoricoEditor::doFormulario($post, $atual['modelo'] ?? [], $imagens, isset(self::TIPOS[$tipo]) ? $tipo : 'PARCIAL', $errosSecoes, $this->ajustes);
        $this->erros = array_merge($this->erros, $errosSecoes);
        if ($this->erros) {
            return false;
        }
        $json = AdmsJsonCanonico::codificar($secoes);
        /* E1b (D-345, D-346): as 3 tabelas (versões, modelo, cursos do modelo) numa transação só. Qualquer gravação que
           falhe desfaz as outras; antes, cada helper gravava na própria conexão e uma falha no meio deixava o modelo
           pela metade. Update sem linha alterada devolve false, que não é erro; erro de SQL devolve null. */
        $T = \Core\Transacao::class;
        $ok = $T::executar(function () use ($T, $atual, $id, $tipo, $nome, $situacao, $json, $cursos, $usuarioId) {
            $this->novaVersao = false;
            if (!$atual) {
                $criar = new \App\adms\Models\helper\AdmsCreate();
                $criar->exeCreate('rm_historico_modelos', ['tipo' => $tipo, 'nome' => $nome, 'situacao' => $situacao, 'versao_atual' => 1, 'usuario_cre' => $usuarioId, 'created' => date('Y-m-d H:i:s')]);
                $this->id = (int) $T::exigir($criar->getResultado(), 'modelo');
                $v1 = new \App\adms\Models\helper\AdmsCreate();
                $v1->exeCreate('rm_historico_modelo_versoes', ['modelo_id' => $this->id, 'versao' => 1, 'secoes' => $json, 'usuario_cre' => $usuarioId, 'created' => date('Y-m-d H:i:s')]);
                $T::exigir($v1->getResultado(), 'versão do modelo');
                $this->novaVersao = true;
            } else {
                $this->id = $id;
                $v = $atual['versao'];
                $versaoAtual = (int) $atual['versao_atual'];
                if (!$v || $v['secoes'] !== $json) {
                    $imutavel = !$v || (int) $atual['situacao'] === 2 || self::versaoUsada((int) $v['id']);
                    if ($imutavel) {
                        $ler = new \App\adms\Models\helper\AdmsRead();
                        $ler->fullRead("SELECT MAX(versao) AS mx FROM rm_historico_modelo_versoes WHERE modelo_id = :m", "m={$id}");
                        $versaoAtual = (int) ($ler->getResultado()[0]['mx'] ?? 0) + 1;
                        $nv = new \App\adms\Models\helper\AdmsCreate();
                        $nv->exeCreate('rm_historico_modelo_versoes', ['modelo_id' => $id, 'versao' => $versaoAtual, 'secoes' => $json, 'usuario_cre' => $usuarioId, 'created' => date('Y-m-d H:i:s')]);
                        $T::exigir($nv->getResultado(), 'versão nova do modelo');
                        $this->novaVersao = true;
                    } else {
                        $upv = new \App\adms\Models\helper\AdmsUpdate();
                        $upv->exeUpdate('rm_historico_modelo_versoes', ['secoes' => $json], 'WHERE id = :id', "id={$v['id']}");
                        if ($upv->getResultado() === null) {
                            $T::falhar('versão do modelo');
                        }
                    }
                }
                $up = new \App\adms\Models\helper\AdmsUpdate();
                $up->exeUpdate('rm_historico_modelos', ['nome' => $nome, 'situacao' => $situacao, 'versao_atual' => $versaoAtual, 'usuario_mod' => $usuarioId, 'modified' => date('Y-m-d H:i:s')], 'WHERE id = :id', "id={$id}");
                if ($up->getResultado() === null) {
                    $T::falhar('modelo');
                }
                $del = new \App\adms\Models\helper\AdmsDeletar();
                $del->exeDelete('rm_historico_modelo_cursos', 'WHERE modelo_id = :m', "m={$id}");
                $T::exigir($del->getResultado(), 'cursos do modelo');
            }
            foreach ($cursos as $c) {
                $mc = new \App\adms\Models\helper\AdmsCreate();
                $mc->exeCreate('rm_historico_modelo_cursos', ['modelo_id' => $this->id, 'curso_id' => $c]);
                $T::exigir($mc->getResultado(), 'cursos do modelo');
            }
            return true;
        });
        if (!$ok) {
            $this->novaVersao = false;
            $this->erros[] = 'Não foi possível gravar o modelo (' . \Core\Transacao::ultimoErro() . '): nenhuma parte dele mudou. Tente de novo; se repetir, avise o suporte.';
            return false;
        }
        return true;
    }

    /**
     * Valida pelo conteúdo e regrava como PNG. Devolve o nome do arquivo ("assin_…png") ou a
     * mensagem de erro.
     */
    public static function gravarAssinatura(array $f): string
    {
        if ((int) $f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file((string) $f['tmp_name'])) {
            return (int) $f['error'] === UPLOAD_ERR_INI_SIZE || (int) $f['error'] === UPLOAD_ERR_FORM_SIZE ? 'a imagem deve ter até 1 MB.' : 'falha no envio da imagem.';
        }
        if ((int) $f['size'] > self::MAX_ASSINATURA || filesize((string) $f['tmp_name']) > self::MAX_ASSINATURA) {
            return 'a imagem deve ter até 1 MB.';
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file((string) $f['tmp_name']);
        $info = @getimagesize((string) $f['tmp_name']);
        if (!in_array($mime, ['image/png', 'image/jpeg'], true) || !$info || !in_array($info[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG], true)) {
            return 'a imagem deve ser PNG ou JPEG.';
        }
        if ($info[0] < 16 || $info[1] < 16 || $info[0] > 2000 || $info[1] > 2000) {
            return 'a imagem deve ter de 16 a 2000 pixels de lado.';
        }
        $img = $info[2] === IMAGETYPE_PNG ? @imagecreatefrompng((string) $f['tmp_name']) : @imagecreatefromjpeg((string) $f['tmp_name']);
        if (!$img) {
            return 'a imagem não pôde ser lida.';
        }
        imagesavealpha($img, true);
        ob_start();
        imagepng($img);
        $png = (string) ob_get_clean();
        imagedestroy($img);
        $nome = 'assin_' . substr(hash('sha256', $png), 0, 24) . '.png';
        $pasta = self::pastaAssinaturas();
        if (!is_file($pasta . $nome) && (!is_dir($pasta) || file_put_contents($pasta . $nome, $png) === false)) {
            return 'não foi possível gravar a imagem.';
        }
        return $nome;
    }
}
