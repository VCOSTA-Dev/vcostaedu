<?php

namespace App\adms\Models\academicos;

if(!defined('VCOSTAEDU')){
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * Lista e busca de ALUNOS (nível acadêmico 1) por nome, RA, CPF ou e-mail.
 * Sem termo, lista os mais recentes (limite de 200).
 */
class AdmsListarAcademico
{
    private $resultado;

    public function listarAlunos(string $termo)
    {
        $termo = trim($termo);
        $this->resultado['termo'] = $termo;
        /* E6 (D-373): mcAtivo = a matrícula em "Cursando" mais recente (o botão do atestado na lista) */
        $base = "SELECT u.id, u.rm, u.nome, u.nome_social, u.email, i.cpf, u.col_sits_usuario_id, s.nome situacaoUsuario, s.cores as situacaoCor,
                        (SELECT mc.id FROM rm_matriculas_curso mc WHERE mc.usuario_id = u.id AND mc.situacao_id = 1 ORDER BY mc.data_ingresso DESC, mc.id DESC LIMIT 1) AS mcAtivo
                        /* Correção da escola, Parte 3 (C8; D-102): na escola, a série e a turma do ano mais novo */
                        " . (\App\adms\Models\helper\AdmsTipoInstituicao::porTurma() ? ", (SELECT CONCAT(t.ano, '|', IFNULL(ms.codnome, ''), '|', t.nome) FROM esc_turma_alunos x INNER JOIN esc_turmas t ON t.id = x.turma_id
                            LEFT JOIN rm_cursos_turma_series ms ON ms.id = t.modulo_id WHERE x.usuario_id = u.id AND x.saida IS NULL ORDER BY t.ano DESC LIMIT 1) AS turmaAtual,
                          (SELECT CONCAT(me.ano, '|', IFNULL(ms2.codnome, '')) FROM rm_matriculas_ensino me INNER JOIN rm_matriculas_curso mc2 ON mc2.id = me.matricula_curso_id
                            LEFT JOIN rm_cursos_turma_series ms2 ON ms2.id = me.modulo_id WHERE mc2.usuario_id = u.id ORDER BY me.ano DESC, me.id DESC LIMIT 1) AS serieAtual" : '') . "
                   FROM col_usuarios u
                   INNER JOIN col_situacao s ON s.id=u.col_sits_usuario_id
                   LEFT JOIN col_usuarios_informacoes i ON i.col_user_id=u.id
                  WHERE u.col_niveis_acesso_id = 1";
        $ler = new \App\adms\Models\helper\AdmsRead();
        if ($termo === '') {
            $ler->fullRead("{$base} ORDER BY u.id DESC LIMIT :limit", "limit=200");
        } else {
            $digitos = preg_replace('/\D/', '', $termo);
            $like = addcslashes($termo, '%_\\'); // D-179: % e _ digitados valem como texto
            $ler->fullRead(
                "{$base} AND (u.nome LIKE :nome OR u.nome_social LIKE :nome OR u.email LIKE :email"
                . ($digitos !== '' ? " OR u.rm = :ra OR i.cpf = :cpf" : "")
                . ") ORDER BY u.nome LIMIT :limit",
                "nome=" . urlencode("%{$like}%") . "&email=" . urlencode("%{$like}%")
                . ($digitos !== '' ? "&ra={$digitos}&cpf={$digitos}" : "") . "&limit=200"
            );
        }
        $this->resultado['lista'] = $ler->getResultado();
        return $this->resultado;
    }

}
