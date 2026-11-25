<?php
/*
 * O ATESTADO DE MATRÍCULA DA ESCOLA (correção da escola, Parte 3, A3; D-102): o modelo "Atestado de matrícula — escola",
 * marcado para a escola (rm_historico_modelos.tipo_instituicao), nasce só quando a instalação é uma escola — como as
 * aulas do dia padrão (VcHorarioPadrao): na instalação nova, o tipo é escolhido depois das migrations; na que já era
 * escola, a migration 0083 o cria. No ensino superior ele não existe (os números das tabelas dos documentos não mudam).
 * Usa o segmento, a série e o ano letivo, sem a previsão de conclusão e sem o CPF.
 */
final class VcAtestadoEscola
{
    public const NOME = 'Atestado de matrícula — escola';

    /** As seções do modelo (o formato 2 do editor de documentos). */
    public static function secoes(): array
    {
        $doc = '<table class="cab"><tbody><tr><td class="cab-logo">{{LOGO_INSTITUICAO}}</td><td class="cab-texto">{{INSTITUICAO_CABECALHO}}</td></tr></tbody></table>'
            . '<p><br></p><p><br></p><h1>ATESTADO DE MATRÍCULA</h1><p><br></p><p><br></p>'
            . '<p class="recuo" style="text-align: justify; font-size: 12pt; line-height: 1.5">Atestamos, para os devidos fins, que {{ALUNO_NOME}} está regularmente {{ALUNO_MATRICULADO}} nesta escola, no {{CURSO_NOME}} ({{ESCOLA_SERIE}}), no ano letivo de {{ESCOLA_ANO_LETIVO}}.</p>'
            . '<p><br></p><p><br></p><p style="text-align: right">{{INSTITUICAO_CIDADE}}, {{EMISSAO_DATA_EXTENSO}}.</p><p><br></p><p>{{ASSINATURAS}}</p><p>{{CODIGO_VERIFICACAO}}</p>';
        return ['assinaturas' => ['blocos' => []], 'documento' => $doc, 'formato' => 2,
            'opcoes' => ['casasNota' => 2, 'estilo' => 'oficial', 'fonte' => 'liberation', 'paginacao' => true],
            'quadro' => ['atividades' => false, 'colunas' => [], 'emCurso' => 'Em curso']];
    }

    /** Na escola, cria o modelo (e a versão 1) se ainda não existe; no ensino superior, nada. */
    public static function aplicar(\PDO $pdo, string $tipo): void
    {
        if ($tipo !== 'escola') {
            return;
        }
        try {
            $pdo->query('SELECT tipo_instituicao FROM rm_historico_modelos LIMIT 1');
        } catch (\Throwable $e) {
            return; // antes da migration 0083
        }
        $st = $pdo->prepare('SELECT id FROM rm_historico_modelos WHERE nome = ? LIMIT 1');
        $st->execute([self::NOME]);
        $id = (int) $st->fetchColumn();
        if (!$id) {
            $pdo->prepare("INSERT INTO rm_historico_modelos (tipo, nome, tipo_instituicao, situacao, versao_atual, usuario_cre, created) VALUES ('ATESTADO', ?, 'escola', 2, 1, NULL, NOW())")
                ->execute([self::NOME]);
            $id = (int) $pdo->lastInsertId();
        }
        $st = $pdo->prepare('SELECT COUNT(*) FROM rm_historico_modelo_versoes WHERE modelo_id = ?');
        $st->execute([$id]);
        if (!(int) $st->fetchColumn()) {
            $pdo->prepare('INSERT INTO rm_historico_modelo_versoes (modelo_id, versao, secoes, usuario_cre, created) VALUES (?, 1, ?, NULL, NOW())')
                ->execute([$id, json_encode(self::secoes(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
        }
    }
}
