<?php

namespace App\adms\Models\academicos;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * Edição do ALUNO (E2b). Mesmas regras do cadastro (AdmsValidaAluno): RA
 * obrigatório e único, CPF válido e único, e-mail único. O login legado
 * col_usuarios.usuario continua espelhando o RA. O nível de acesso é fixo
 * (Estudante) e não é editado aqui.
 */
class AdmsEditarUsuarioAcademico
{
    private $dadosId;
    private array $dados = [];
    private $foto;
    private $imgAntiga;
    private $novaImagem;
    private $logCodigo;
    private $logDescricao;
    private $resultado;

    private array $erros = [];

    /** Os motivos da recusa (Onda 2, Etapa 6: a API os devolve). */
    function getErros(): array
    {
        return $this->erros;
    }

    function getResultado()
    {
        return $this->resultado;
    }

    public function verUsuario($dadosId)
    {
        $this->dadosId = (int) $dadosId;
        $visUser = new \App\adms\Models\helper\AdmsRead();
        $visUser->fullRead("SELECT a.id, a.rm, a.nome, a.nome_social, a.genero_id, a.genero_confirmado_em, a.apelido, a.usuario, a.email, a.telefone, a.celular, a.imagem, a.col_niveis_acesso_id, a.col_sits_usuario_id, b.nasc_data, b.nasc_local, b.nasc_uf, b.nasc_nac, b.rg, b.rg_org, b.rg_uf, b.cpf, b.nome_mae, b.nome_pai, c.endereco, c.numero, c.complemento, c.bairro, c.cidade, c.uf, c.cep FROM col_usuarios a LEFT JOIN col_usuarios_informacoes b ON b.col_user_id=a.id LEFT JOIN col_usuarios_enderecos c ON c.usuario_id=a.id AND c.situacao =:sit WHERE a.id=:id LIMIT :limit", "sit=1&id={$this->dadosId}&limit=1");
        $this->resultado = $visUser->getResultado();

        return $this->resultado;
    }

    public function editUsuarios(array $dados)
    {
        $this->foto = $dados['imagem_nova'] ?? null;
        $this->imgAntiga = $dados['imagem_antiga'] ?? null;
        unset($dados['imagem_nova'], $dados['imagem_antiga']);
        $this->dados = array_map(fn($v) => is_string($v) ? trim($v) : $v, $dados);
        $this->dadosId = (int) ($this->dados['id'] ?? 0);

        $valida = new AdmsValidaAluno();
        if ($this->dadosId <= 0 || !$valida->validar($this->dados, $this->dadosId)) {
            $this->erros = $this->dadosId <= 0 ? ['Aluno não encontrado.'] : $valida->getErros(); // a API mostra o motivo
            $this->resultado = false;
            return;
        }
        $this->valFoto();
        /* E1b (D-345): usuário, dados pessoais e endereço numa transação só */
        $ok = \Core\Transacao::executar(function () {
            $this->altUsuario();
            return $this->resultado === true;
        });
        if ($ok) {
            \App\adms\Models\moodle\AdmsMoodle::anotar('aluno', $this->dadosId); // Onda 3, Etapa 8: o nome e o e-mail novos vão ao Moodle
        }
        if (!$ok) {
            $this->resultado = false;
            $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button><b>Nada foi salvo:</b> não foi possível atualizar o aluno. Tente de novo.</div>";
        }
    }

    private function nulo($v)
    {
        return ($v === null || $v === '') ? null : $v;
    }

    private function valFoto()
    {
        if (empty($this->foto['name'])) {
            return;
        }
        $slugImg = new \App\adms\Models\helper\AdmsSlug();
        $nome = $slugImg->nomeSlug($this->foto['name']);
        $uploadImg = new \App\adms\Models\helper\AdmsUploadImgRed();
        $uploadImg->uploadImagem($this->foto, '../portal/assets/img/academicos/' . $this->dadosId . '/', $nome, 150, 150);
        if ($uploadImg->getResultado()) {
            $this->novaImagem = $nome;
            if (!empty($this->imgAntiga)) {
                $apagarImg = new \App\adms\Models\helper\AdmsApagarImg();
                $apagarImg->apagarImg('../portal/assets/img/academicos/' . $this->dadosId . '/' . $this->imgAntiga);
            }
        }
    }

    private function altUsuario()
    {
        $d = $this->dados;
        $ra = (string) (int) $d['rm'];
        $usuario = [
            'rm' => $ra,
            'usuario' => $ra,
            'nome' => $d['nome'],
            'nome_social' => $this->nulo($d['nome_social'] ?? ''),
            'email' => $this->nulo($d['email'] ?? ''),
            'genero_id' => (int) $d['genero_id'], // E1b (D-356): obrigatório (AdmsValidaAluno)
            'genero_confirmado_em' => date('Y-m-d H:i:s'), // escolhido na tela: sai da lista de pendência
            'col_sits_usuario_id' => (int) ($d['col_sits_usuario_id'] ?? 1) ?: 1,
            'telefone' => $this->nulo(preg_replace('/\D/', '', (string) ($d['telefone'] ?? ''))),
            'celular' => $this->nulo(preg_replace('/\D/', '', (string) ($d['celular'] ?? ''))),
            'usuario_mod' => $_SESSION['colUsuario_id'],
            'modified' => date("Y-m-d H:i:s"),
        ];
        if (!empty($this->novaImagem)) {
            $usuario['imagem'] = $this->novaImagem;
        }
        $altUser = new \App\adms\Models\helper\AdmsUpdate();
        $altUser->exeUpdate("col_usuarios", $usuario, "WHERE id=:id", "id={$this->dadosId}");
        // AdmsUpdate: false = nenhuma linha mudou (ok, ex.: só o CPF mudou); null = erro de banco.
        if ($altUser->getResultado() === null) {
            $this->falha("Não foi possível atualizar o aluno.");
            return;
        }

        $informacoes = [
            'nasc_data' => $this->nulo($d['nasc_data'] ?? ''),
            'nasc_local' => $this->nulo($d['nasc_local'] ?? ''),
            'nasc_uf' => $this->nulo($d['nasc_uf'] ?? ''),
            'nasc_nac' => $this->nulo($d['nasc_nac'] ?? ''),
            'rg' => $this->nulo(preg_replace('/[^a-zA-Z0-9]/', '', (string) ($d['rg'] ?? ''))),
            'rg_org' => $this->nulo($d['rg_org'] ?? ''),
            'rg_uf' => $this->nulo($d['rg_uf'] ?? ''),
            'cpf' => $this->nulo(\App\adms\Models\helper\AdmsCpf::digitos($d['cpf'] ?? '')),
            'nome_mae' => $this->nulo($d['nome_mae'] ?? ''),
            'nome_pai' => $this->nulo($d['nome_pai'] ?? ''),
        ];
        $okInfo = $this->gravaComplemento("col_usuarios_informacoes", "col_user_id", $informacoes);

        $endereco = [
            'cep' => $this->nulo(preg_replace('/\D/', '', (string) ($d['cep'] ?? ''))),
            'endereco' => $this->nulo($d['endereco'] ?? ''),
            'numero' => $this->nulo($d['numero'] ?? ''),
            'complemento' => $this->nulo($d['complemento'] ?? ''),
            'bairro' => $this->nulo($d['bairro'] ?? ''),
            'cidade' => $this->nulo($d['cidade'] ?? ''),
            'uf' => $this->nulo($d['uf'] ?? ''),
        ];
        $okEnd = $this->gravaComplemento("col_usuarios_enderecos", "usuario_id", $endereco, ['situacao' => 1]);

        if (!$okInfo || !$okEnd) {
            \Core\Transacao::falhar('dados pessoais ou endereço'); // E1b: desfaz a edição inteira
        }
        $this->logCodigo = 200;
        $this->logDescricao = "Aluno atualizado com sucesso! id={$this->dadosId}&ra={$ra}";
        $this->salvarLog();
        $_SESSION['msg'] = "<div class='alert alert-success alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Dados do aluno atualizados com sucesso!</div>";
        $this->resultado = true;
    }

    /** Atualiza a linha complementar do aluno ou a cria, se ainda não existir. */
    private function gravaComplemento(string $tabela, string $chave, array $valores, array $naCriacao = []): bool
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT id FROM {$tabela} WHERE {$chave} = :u LIMIT 1", "u={$this->dadosId}");
        $linha = $ler->getResultado();
        if (!empty($linha[0]['id'])) {
            $valores['usuario_mod'] = $_SESSION['colUsuario_id'];
            $valores['modified'] = date("Y-m-d H:i:s");
            $up = new \App\adms\Models\helper\AdmsUpdate();
            $up->exeUpdate($tabela, $valores, "WHERE id=:id", "id={$linha[0]['id']}");
            return $up->getResultado() !== null;
        }
        $valores = array_merge($valores, $naCriacao, [$chave => $this->dadosId, 'usuario_cre' => $_SESSION['colUsuario_id']]);
        $cria = new \App\adms\Models\helper\AdmsCreate();
        $cria->exeCreate($tabela, $valores);
        return (bool) $cria->getResultado();
    }

    private function falha(string $msg)
    {
        $this->logCodigo = 400;
        $this->logDescricao = "Erro ao editar aluno id={$this->dadosId}: {$msg}";
        $this->salvarLog();
        $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: {$msg}</div>";
        $this->resultado = false;
    }

    private function salvarLog()
    {
        $valLogsalvar = new \App\adms\Models\helper\AdmsSalvarLog();
        $valLogsalvar->salvarLogs(48, $this->logCodigo, 3, $this->logDescricao);
    }

}
