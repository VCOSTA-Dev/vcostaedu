<?php

namespace App\adms\Controllers;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * PRÉVIA DO HISTÓRICO (E6a, rota 390; administrador e secretaria): escolher o aluno e a
 * matrícula no curso e ver o objeto da fonte única (AdmsVidaAcademica), organizado e sem
 * layout de documento, com os alertas. Ferramenta de conferência até o documento da E6b.
 */
class Historico
{
    private $dados;

    public function previa($dadosId = null): void
    {
        $mcId = (int) $dadosId;
        $busca = trim((string) (filter_input(INPUT_GET, 'aluno') ?? ''));
        $this->dados['busca'] = $busca;
        $this->dados['matriculas'] = [];
        $this->dados['vida'] = null;
        if ($mcId > 0) {
            $this->dados['vida'] = \App\adms\Models\historico\AdmsVidaAcademica::montar($mcId);
        }
        if ($busca !== '' || $mcId > 0) {
            $ler = new \App\adms\Models\helper\AdmsRead();
            if ($mcId > 0 && $busca === '') {
                $ler->fullRead(
                    "SELECT mc.id, u.rm, u.nome, c.nome AS curso, g.versao, s.nome AS situacao FROM rm_matriculas_curso mc INNER JOIN col_usuarios u ON u.id = mc.usuario_id
                      INNER JOIN rm_cursos c ON c.id = mc.curso_id INNER JOIN rm_cursos_grades g ON g.id = mc.grade_id LEFT JOIN rm_matriculas_situacao s ON s.id = mc.situacao_id
                      WHERE mc.usuario_id = (SELECT usuario_id FROM rm_matriculas_curso WHERE id = :id) ORDER BY mc.data_ingresso",
                    "id={$mcId}"
                );
            } else {
                $like = '%' . addcslashes($busca, '%_\\') . '%'; // D-179: % e _ digitados valem como texto
                $ler->fullRead(
                    "SELECT mc.id, u.rm, u.nome, c.nome AS curso, g.versao, s.nome AS situacao FROM rm_matriculas_curso mc INNER JOIN col_usuarios u ON u.id = mc.usuario_id
                      INNER JOIN rm_cursos c ON c.id = mc.curso_id INNER JOIN rm_cursos_grades g ON g.id = mc.grade_id LEFT JOIN rm_matriculas_situacao s ON s.id = mc.situacao_id
                      WHERE CAST(u.rm AS CHAR) = :rm OR u.nome LIKE :nome ORDER BY u.nome, mc.data_ingresso LIMIT :limit",
                    "rm=" . urlencode($busca) . "&nome=" . urlencode($like) . "&limit=50"
                );
            }
            $this->dados['matriculas'] = $ler->getResultado() ?: [];
        }
        $this->dados['mcId'] = $mcId;
        $this->dados['antesDeReabertura'] = $mcId > 0 ? \App\adms\Models\historico\AdmsHistoricoEmissao::emitidasAntesDeReabertura($mcId) : []; // 1.14.0 (D-415)
        /* E8c (A16/A17): a barra da prévia — PDF parcial/final (o modelo aplicável ao curso), imprimir e ficha do aluno */
        $this->dados['botao'] = (new \App\adms\Models\AdmsBotao())->valBotao([
            'pdf' => ['menu_controller' => 'historico-emissoes', 'menu_metodo' => 'previa'],
            'ficha' => ['menu_controller' => 'academicos', 'menu_metodo' => 'visualizar'],
            'emitir' => ['menu_controller' => 'historico-emissoes', 'menu_metodo' => 'emitir'],
        ]);
        $this->dados['modelosPrevia'] = [];
        if ($this->dados['vida']) {
            foreach (['PARCIAL', 'FINAL'] as $tipo) {
                $this->dados['modelosPrevia'][$tipo] = (int) (\App\adms\Models\historico\AdmsHistoricoModelos::aplicaveis($tipo, (int) $this->dados['vida']['matricula']['curso_id'])[0]['id'] ?? 0);
            }
        }
        $this->dados['paginaAtiva'] = 390;
        $this->dados['menus'] = (new \App\adms\Models\AdmsMenus())->itemMenu();
        (new \Core\ConfigView('adms/Views/historico/previa', $this->dados))->renderizar('tabler');
    }
}
