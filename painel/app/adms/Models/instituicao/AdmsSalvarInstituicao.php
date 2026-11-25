<?php

namespace App\adms\Models\instituicao;

use App\adms\Models\helper\AdmsInstituicao as I;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * Salva os DADOS DA INSTITUIÇÃO (E6a) na linha de adms_empresas marcada `instituicao = 1`
 * (cria na primeira vez). Nome e mantenedora obrigatórios, CNPJ validado, código e-MEC
 * opcional (só dígitos), endereço, logo.
 *
 * E1a (D-339): telefone, e-mail e as até 5 LINHAS DO CABEÇALHO institucional dos documentos. As linhas chegam
 * em cabecalho_1 … _5 na ordem da tela (as setas trocam a ordem) e são gravadas COMPACTADAS: as vazias do meio
 * saem e as preenchidas sobem, sem mudar a ordem. Tudo numa linha de adms_empresas: uma tabela, um UPDATE.
 *
 * LOGO: PNG ou JPEG de até 1 MB e 2000×2000 px, conferido pelo conteúdo (finfo + getimagesize,
 * nunca pela extensão) e RECODIFICADO em PNG pelo GD — o arquivo gravado é uma imagem nova,
 * sem nada do que foi enviado além dos pixels. Vai para assets/instituicao/, onde o .htaccess
 * não deixa nada executar. Com qualquer campo inválido, nada é salvo.
 */
class AdmsSalvarInstituicao
{
    private $resultado;
    private $erros = [];

    public function getResultado()
    {
        return $this->resultado;
    }

    public function salvar(array $dados, ?array $logo): bool
    {
        $nome = trim((string) ($dados['nome'] ?? ''));
        $mantenedora = trim((string) ($dados['razao_social'] ?? ''));
        $cnpj = preg_replace('/\D/', '', (string) ($dados['cnpj'] ?? ''));
        $emec = trim((string) ($dados['codigo_emec'] ?? ''));
        $cep = trim((string) ($dados['cep'] ?? ''));
        $uf = strtoupper(trim((string) ($dados['uf'] ?? '')));
        if ($nome === '') {
            $this->erros[] = 'Informe o nome da instituição.';
        }
        if ($mantenedora === '') {
            $this->erros[] = 'Informe a mantenedora (razão social).';
        }
        if (!I::cnpjValido($cnpj)) {
            $this->erros[] = 'CNPJ inválido (confira os 14 dígitos).';
        }
        if ($emec !== '' && !preg_match('/^\d{1,10}$/', $emec)) {
            $this->erros[] = 'O código e-MEC tem só dígitos (até 10), ou fica vazio.';
        }
        if ($cep !== '' && !preg_match('/^\d{5}-?\d{3}$/', $cep)) {
            $this->erros[] = 'CEP inválido (00000-000).';
        }
        if ($uf !== '' && !in_array($uf, \App\adms\Models\polos\AdmsListarPolos::ufs(), true)) {
            $this->erros[] = 'UF inválida.';
        }
        $telefone = trim((string) ($dados['telefone'] ?? ''));
        if ($telefone !== '' && (!preg_match('/^[0-9()+\-.\/ ]{8,30}$/', $telefone) || strlen(preg_replace('/\D/', '', $telefone)) < 8)) {
            $this->erros[] = 'Telefone inválido: use só números, espaço, parênteses, + e - (ao menos 8 dígitos; até 30 caracteres).';
        }
        $email = trim((string) ($dados['email'] ?? ''));
        if ($email !== '' && (mb_strlen($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL))) {
            $this->erros[] = 'E-mail institucional inválido.';
        }
        $cabecalho = $this->linhasCabecalho($dados);
        $novoLogo = null;
        if ($logo && (int) ($logo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $novoLogo = $this->validarLogo($logo);
        }
        /* Onda 3, Etapa 5 (0017): o ícone da aba e o que vai no topo do menu */
        $comMarca = \App\adms\Models\helper\AdmsAtualizacaoPendente::exige('0017');
        $iconeAba = (string) ($dados['icone_aba'] ?? 'vcostaedu');
        if (!in_array($iconeAba, ['vcostaedu', 'instituicao'], true)) {
            $this->erros[] = 'Escolha o ícone da aba.';
        }
        $topoMenu = (string) ($dados['topo_menu'] ?? 'nome');
        if (!in_array($topoMenu, ['nome', 'logo'], true)) {
            $this->erros[] = 'Escolha o que vai no topo do menu.';
        }
        if ($this->erros) {
            $_SESSION['msg'] = "<div class='alert alert-danger'><b>Nada foi salvo:</b><ul class='mb-0'><li>" . implode('</li><li>', array_map('htmlspecialchars', $this->erros)) . '</li></ul></div>';
            return $this->resultado = false;
        }
        $linha = [
            'nome' => $nome, 'razao_social' => $mantenedora, 'cnpj' => I::mascaraCnpj($cnpj),
            'codigo_emec' => $emec !== '' ? $emec : null, 'site' => $this->nulo($dados['site'] ?? ''),
            'cep' => $cep !== '' ? substr(preg_replace('/\D/', '', $cep), 0, 5) . '-' . substr(preg_replace('/\D/', '', $cep), 5) : null,
            'logradouro' => $this->nulo($dados['logradouro'] ?? ''), 'numero' => $this->nulo($dados['numero'] ?? ''),
            'complemento' => $this->nulo($dados['complemento'] ?? ''), 'bairro' => $this->nulo($dados['bairro'] ?? ''),
            'cidade' => $this->nulo($dados['cidade'] ?? ''), 'uf' => $uf !== '' ? $uf : null,
            'telefone' => $telefone !== '' ? $telefone : null, 'email' => $email !== '' ? $email : null,
        ];
        for ($n = 1; $n <= I::CABECALHO_LINHAS; $n++) {
            $linha['cabecalho_' . $n] = $cabecalho[$n - 1] ?? null;
        }
        $atual = I::dados();
        if ($comMarca) {
            $linha['icone_aba'] = $iconeAba;
            $linha['topo_menu'] = $topoMenu;
        }
        if ($novoLogo !== null) {
            $linha['logo'] = $this->gravarLogo($novoLogo, $atual['logo'] ?? null);
            if ($linha['logo'] === null) {
                $_SESSION['msg'] = "<div class='alert alert-danger'>Nada foi salvo: não foi possível gravar o logo.</div>";
                return $this->resultado = false;
            }
        }
        if ($atual) {
            $linha['usuario_mod'] = $_SESSION['colUsuario_id'];
            $up = new \App\adms\Models\helper\AdmsUpdate();
            $up->exeUpdate('adms_empresas', $linha, 'WHERE id = :id', "id={$atual['id']}");
            $ok = $up->getResultado() !== null;
        } else {
            $linha += ['instituicao' => 1, 'inscricao_estadual' => '', 'situacao' => 1, 'usuario_cre' => $_SESSION['colUsuario_id']];
            $add = new \App\adms\Models\helper\AdmsCreate();
            $add->exeCreate('adms_empresas', $linha);
            $ok = (int) $add->getResultado() > 0;
        }
        if (!$ok) {
            $_SESSION['msg'] = "<div class='alert alert-danger'>Não foi possível salvar os dados da instituição.</div>";
            return $this->resultado = false;
        }
        (new \App\adms\Models\helper\AdmsSalvarLog())->salvarLogs(385, 200, 3, 'Dados da instituição salvos' . ($novoLogo !== null ? ' (logo novo)' : ''));
        $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Dados da instituição salvos.</div>";
        return $this->resultado = true;
    }

    /** @return \GdImage|null a imagem decodificada (só PNG/JPEG válidos, dentro dos limites) */
    private function validarLogo(array $f)
    {
        if ((int) $f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file((string) $f['tmp_name'])) {
            $this->erros[] = 'O envio do logo falhou.';
            return null;
        }
        if ((int) $f['size'] <= 0 || (int) $f['size'] > I::LOGO_MAX_BYTES) {
            $this->erros[] = 'O logo deve ter até 1 MB.';
            return null;
        }
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file((string) $f['tmp_name']);
        $info = @getimagesize((string) $f['tmp_name']);
        if (!in_array($mime, ['image/png', 'image/jpeg'], true) || !$info || !in_array($info[2], [IMAGETYPE_PNG, IMAGETYPE_JPEG], true)) {
            $this->erros[] = 'O logo deve ser uma imagem PNG ou JPEG.';
            return null;
        }
        if ($info[0] < 16 || $info[1] < 16 || $info[0] > 2000 || $info[1] > 2000) {
            $this->erros[] = 'O logo deve ter entre 16×16 e 2000×2000 pixels.';
            return null;
        }
        $img = $info[2] === IMAGETYPE_PNG ? @imagecreatefrompng((string) $f['tmp_name']) : @imagecreatefromjpeg((string) $f['tmp_name']);
        if (!$img) {
            $this->erros[] = 'O logo não pôde ser lido como imagem.';
            return null;
        }
        return $img;
    }

    /** Grava a imagem recodificada em PNG com nome novo e apaga o logo anterior. */
    private function gravarLogo($img, ?string $anterior): ?string
    {
        $pasta = dirname(__DIR__, 5) . '/' . I::PASTA_LOGO;
        imagesavealpha($img, true);
        $nome = 'logo_' . bin2hex(random_bytes(8)) . '.png';
        if (!is_dir($pasta) || !imagepng($img, $pasta . $nome)) {
            return null;
        }
        imagedestroy($img);
        if ($anterior && preg_match('/^logo_[a-f0-9]{16}\.png$/', $anterior) && is_file($pasta . $anterior)) {
            @unlink($pasta . $anterior);
        }
        return $nome;
    }

    /**
     * E1a: as linhas do cabeçalho na ordem da tela, sem as vazias. Linha longa demais ou com caractere de controle
     * vira erro (nada é salvo); espaços repetidos viram um só.
     */
    private function linhasCabecalho(array $dados): array
    {
        $linhas = [];
        for ($n = 1; $n <= I::CABECALHO_LINHAS; $n++) {
            $t = trim((string) preg_replace('/[ \t]+/u', ' ', (string) ($dados['cabecalho_' . $n] ?? '')));
            if ($t === '') {
                continue;
            }
            if (preg_match('/[\x00-\x1F\x7F]/u', $t)) {
                $this->erros[] = "A linha {$n} do cabeçalho tem um caractere inválido (quebra de linha ou tabulação): cada linha é uma linha só.";
            } elseif (mb_strlen($t) > I::CABECALHO_MAX) {
                $this->erros[] = "A linha {$n} do cabeçalho passa de " . I::CABECALHO_MAX . ' caracteres.';
            }
            $linhas[] = $t;
        }
        return $linhas;
    }

    private function nulo($v)
    {
        $v = trim((string) $v);
        return $v === '' ? null : $v;
    }
}
