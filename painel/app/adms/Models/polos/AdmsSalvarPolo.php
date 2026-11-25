<?php

namespace App\adms\Models\polos;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/** Cadastra (`$id = 0`) ou edita um polo. Nome obrigatório e único; UF da tabela adms_uf. */
class AdmsSalvarPolo
{
    private $resultado;

    public function getResultado()
    {
        return $this->resultado;
    }

    public function salvar(array $dados, int $id = 0)
    {
        $nome = trim((string) ($dados['nome'] ?? ''));
        $uf = strtoupper(trim((string) ($dados['uf'] ?? '')));
        if ($nome === '') {
            return $this->recusar('Informe o nome do polo.');
        }
        if ($uf !== '' && !in_array($uf, AdmsListarPolos::ufs(), true)) {
            return $this->recusar('UF inválida.');
        }
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT id FROM rm_polos WHERE nome = :n AND id <> :id LIMIT :limit", "n={$nome}&id={$id}&limit=1");
        if ($ler->getResultado()) {
            return $this->recusar("Já existe um polo chamado «{$nome}».");
        }
        $linha = [
            'nome' => $nome,
            'cidade' => $this->nulo($dados['cidade'] ?? ''),
            'uf' => $uf !== '' ? $uf : null,
            'endereco' => $this->nulo($dados['endereco'] ?? ''),
            'instituicao_parceira' => $this->nulo($dados['instituicao_parceira'] ?? ''),
            'situacao' => (int) ($dados['situacao'] ?? 1) === 2 ? 2 : 1,
        ];
        if ($id > 0) {
            if (!(new AdmsListarPolos())->ver($id)) {
                return $this->recusar('Polo não encontrado.');
            }
            $linha['usuario_mod'] = $_SESSION['colUsuario_id'];
            $up = new \App\adms\Models\helper\AdmsUpdate();
            $up->exeUpdate('rm_polos', $linha, 'WHERE id = :id', "id={$id}");
            if ($up->getResultado() === null) {
                return $this->recusar('Não foi possível salvar o polo.');
            }
            $this->log(380, 3, "Polo atualizado: id={$id}");
            $_SESSION['msg'] = $this->aviso('success', "Polo «{$nome}» atualizado.");
            return $this->resultado = $id;
        }
        $linha['usuario_cre'] = $_SESSION['colUsuario_id'];
        $add = new \App\adms\Models\helper\AdmsCreate();
        $add->exeCreate('rm_polos', $linha);
        $novo = (int) $add->getResultado();
        if ($novo < 1) {
            return $this->recusar('Não foi possível cadastrar o polo.');
        }
        $this->log(379, 1, "Polo cadastrado: id={$novo}");
        $_SESSION['msg'] = $this->aviso('success', "Polo «{$nome}» cadastrado.");
        return $this->resultado = $novo;
    }

    private function nulo($v)
    {
        $v = trim((string) $v);
        return $v === '' ? null : $v;
    }

    private function log(int $pagina, int $acao, string $texto): void
    {
        (new \App\adms\Models\helper\AdmsSalvarLog())->salvarLogs($pagina, 200, $acao, $texto);
    }

    private function aviso(string $tipo, string $texto): string
    {
        return "<div class='alert alert-{$tipo} alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>"
            . htmlspecialchars($texto, ENT_QUOTES, 'UTF-8') . '</div>';
    }

    private function recusar(string $texto)
    {
        $_SESSION['msg'] = $this->aviso('danger', 'Erro: ' . $texto);
        return $this->resultado = false;
    }
}
