<?php

namespace App\adms\Models\usuarios;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

class AdmsListarUsuarios
{
    private $resultado;

    public function listUsuarios()
    {
        $listAlunos = new \App\adms\Models\helper\AdmsRead();
        $listAlunos->fullRead("SELECT u.id, u.nome, u.email, u.adms_niveis_acesso_id, (SELECT a.nome FROM adms_niveis_acessos a WHERE a.id=u.adms_niveis_acesso_id) as nivelAcesso, (SELECT a.ordem FROM adms_niveis_acessos a WHERE a.id=u.adms_niveis_acesso_id) as nivelOrdem, u.adms_sits_usuario_id, s.nome situacaoUsuario, (SELECT c.cor FROM adms_cors c WHERE c.id=s.adms_cor_id) as situacaoCor FROM adms_usuarios u INNER JOIN adms_sits_usuarios s ON s.id=u.adms_sits_usuario_id WHERE u.id <> 1 AND u.adms_sits_usuario_id <:sit", "sit=5");
        $this->resultado = $listAlunos->getResultado();
        /* 1.18.3 (D-497): a lista mostra TODOS os usuários (antes escondia o id 1). Os de nível igual ou acima do seu
           aparecem sem as ações de alterar ("Protegido"): a recusa vale também por URL e por POST (AdmsHierarquia). */
        $meu = \App\adms\Models\helper\AdmsHierarquia::ordem(\App\adms\Models\helper\AdmsNivel::nivel());
        foreach (($this->resultado ?: []) as $i => $u) {
            $this->resultado[$i]['podeAlterar'] = $meu !== null && $u['nivelOrdem'] !== null && (int) $u['nivelOrdem'] > $meu
                && (int) $u['id'] !== (int) ($_SESSION['colUsuario_id'] ?? 0);
        }
        return $this->resultado;
    }

}

