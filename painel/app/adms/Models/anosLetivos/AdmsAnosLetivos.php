<?php

namespace App\adms\Models\anosLetivos;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * =============================================================================
 * O ANO LETIVO — a linha que sete telas leem e nenhuma escrevia.
 * =============================================================================
 *
 * `adms_periodos_letivos` e' a fonte do "ano corrente" e do filtro de ano de
 * matriculas, disciplinas, minhas disciplinas e horario de aula (o conselho de
 * classe saiu na E2b).
 *
 * ⛔ **E ate' 06/09/2026 nenhuma tela a escrevia.** As rotas `Periodos/*`
 * existem, mas `AdmsCadastrarPeriodos` grava `adms_periodos_bimestres`, que e' o
 * calendario de bimestres — outra tabela. A linha do ano letivo era inserida a
 * mao no banco.
 *
 * A consequencia, medida: o filtro de ano da lista de matriculas oferecia 2026 a
 * 2018. **2027 nao estava la'**, e a matricula de 2027 so' abria por URL
 * digitada. (P-33.)
 *
 * ================== AS TRES BANDEIRAS, E O QUE CADA UMA LIGA ================
 *
 * ⚠️ Ninguem tinha documentado, e a **primeira versao deste arquivo errou**: eu
 * escrevi que `situacaodisciplina` governava "disciplinas, horario e conselho" e
 * que `situacao` fazia o ano "aparecer nas listas". Fui ler as cinco consultas,
 * uma a uma, e o mapa real e' este:
 *
 *   tela                   ano PADRAO            LISTA do filtro
 *   ---------------------  --------------------  --------------------
 *   Matriculas             situacaomodelo        **todos** (sem filtro)
 *   Disciplinas            situacaodisciplina    situacaodisciplina
 *   Minhas Disciplinas     situacaodisciplina    situacao
 *   Horario de Aula        situacaodisciplina    situacao
 *
 * ⚠️ **DOIS ACHADOS QUE SAIRAM DESSA LEITURA:**
 *
 * 1. `situacaodisciplina` faz DOIS trabalhos — e' o ano corrente E a lista do
 *    filtro de Disciplinas e do Conselho. Com um ano so' marcado, essas duas
 *    telas oferecem **um unico ano** no filtro; marcar um segundo para poder
 *    escolhe-lo tambem mexe em qual e' o corrente.
 *
 * 2. Minhas Disciplinas e Horario de Aula tiram o PADRAO de uma bandeira e a
 *    LISTA de outra. O ano padrao pode nem estar na lista.
 *
 * ⓘ Nada disso foi mexido: e' comportamento de cinco telas que funcionam. Fica
 *    registrado, e a tela de Anos Letivos diz a verdade medida.
 * =============================================================================
 */
class AdmsAnosLetivos
{
    /** As tres bandeiras, com o nome que a tela mostra e o que cada uma liga. */
    const BANDEIRAS = [
        'situacaomodelo' => [
            'rotulo' => 'Ano corrente das matrículas',
            'ajuda'  => 'É o ano que a tela de Matrículas abre por padrão. Só isso — '
                . 'o filtro de ano das Matrículas mostra TODOS os anos cadastrados, marcados ou não.',
        ],
        'situacaodisciplina' => [
            'rotulo' => 'Ano corrente do acadêmico',
            'ajuda'  => 'É o ano padrão de Disciplinas, Minhas Disciplinas e Horário de Aula. '
                . '⚠️ E é TAMBÉM o que faz o ano aparecer no filtro de Disciplinas — as duas '
                . 'coisas na mesma bandeira.',
        ],
        'situacao' => [
            'rotulo' => 'Ano ativo',
            'ajuda'  => 'Faz o ano aparecer no filtro de Minhas Disciplinas e do Horário de Aula.',
        ],
    ];

    private $resultado;

    public function getResultado()
    {
        return $this->resultado;
    }

    /** Todos os anos, com quantas matriculas cada um tem. */
    public function listar(): array
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead(
            "SELECT a.id, a.ano, a.situacaomodelo, a.situacaodisciplina, a.situacao,
                    a.created, a.modified,
                    (SELECT COUNT(*) FROM rm_matriculas_ensino m
                      WHERE m.ano = a.ano AND m.situacao = 1) AS matriculas
               FROM adms_periodos_letivos a
              ORDER BY a.ano DESC"
        );

        return $ler->getResultado() ?: [];
    }

    public function ver(int $id): ?array
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead(
            "SELECT * FROM adms_periodos_letivos WHERE id = :id LIMIT :limit",
            "id={$id}&limit=1"
        );
        $linhas = $ler->getResultado();

        return !empty($linhas[0]['id']) ? $linhas[0] : null;
    }

    /**
     * Cria ou atualiza. Devolve o id, ou `false`.
     *
     * ⚠️ **Ano duplicado e' recusado.** Duas linhas para o mesmo ano fariam as
     * sete telas escolherem uma delas por `ORDER BY id DESC LIMIT 1`, e a outra
     * viraria dado morto que ninguem entende.
     */
    public function salvar(array $dados)
    {
        $id = (int) ($dados['id'] ?? 0);
        $ano = (int) ($dados['ano'] ?? 0);

        if ($ano < 2000 || $ano > 2999) {
            return $this->recusar('Informe um ano entre 2000 e 2999.');
        }

        if ($this->anoJaExiste($ano, $id)) {
            return $this->recusar("O ano {$ano} já está cadastrado.");
        }

        $linha = ['ano' => $ano];

        foreach (array_keys(self::BANDEIRAS) as $bandeira) {
            $linha[$bandeira] = !empty($dados[$bandeira]) ? 1 : 2;
        }

        if ($id > 0) {
            $linha['usuario_mod'] = $_SESSION['colUsuario_id'];
            $alt = new \App\adms\Models\helper\AdmsUpdate();
            $alt->exeUpdate('adms_periodos_letivos', $linha, 'WHERE id=:id', "id={$id}");
            $this->desmarcarOutros($linha, $id);
            $this->resultado = $id;
            $_SESSION['msg'] = $this->aviso('success', "Ano letivo {$ano} atualizado com sucesso!");

            return $id;
        }

        $linha['usuario_cre'] = $_SESSION['colUsuario_id'];
        $add = new \App\adms\Models\helper\AdmsCreate();
        $add->exeCreate('adms_periodos_letivos', $linha);
        $novo = $add->getResultado();

        if (!$novo) {
            return $this->recusar('Não foi possível salvar o ano letivo.');
        }

        $this->desmarcarOutros($linha, (int) $novo);
        $this->resultado = $novo;
        $_SESSION['msg'] = $this->aviso('success', "Ano letivo {$ano} cadastrado com sucesso!");

        return $novo;
    }

    /**
     * ⚠️ **"Ano corrente" e' UM SO', e por isso marcar um DESMARCA os outros.**
     *
     * `AdmsListarMatriculas` pega o corrente com `ORDER BY id DESC LIMIT 1`:
     * com dois anos marcados, ele escolhe o de id maior e o outro fica marcado
     * sem efeito nenhum. A tela mostraria duas verdades e o sistema obedeceria a
     * uma — que e' pior que mostrar uma.
     *
     * `situacao` (ano ativo) NAO entra aqui: varios anos podem estar ativos ao
     * mesmo tempo, e e' assim que a lista de selecao se enche.
     */
    private function desmarcarOutros(array $linha, int $id): void
    {
        foreach (['situacaomodelo', 'situacaodisciplina'] as $bandeira) {
            if ((int) ($linha[$bandeira] ?? 2) !== 1) {
                continue;
            }

            $alt = new \App\adms\Models\helper\AdmsUpdate();
            $alt->exeUpdate(
                'adms_periodos_letivos',
                [$bandeira => 2],
                "WHERE id <> :id AND {$bandeira} = :um",
                "id={$id}&um=1"
            );
        }
    }

    private function anoJaExiste(int $ano, int $exceto): bool
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead(
            "SELECT id FROM adms_periodos_letivos WHERE ano = :ano AND id <> :id LIMIT :limit",
            "ano={$ano}&id={$exceto}&limit=1"
        );

        return !empty($ler->getResultado());
    }

    /**
     * ⚠️ **APAGA de verdade, e so' quando o ano esta' VAZIO.**
     *
     * Diferente do modelo de contrato, que se inativa: aqui a linha nao guarda
     * historico nenhum — ela so' liga o ano nas telas. Mas apagar um ano que ja'
     * tem matricula esconderia as matriculas do filtro, e ninguem ligaria uma
     * coisa a outra.
     */
    public function apagar(int $id): bool
    {
        $ano = $this->ver($id);

        if ($ano === null) {
            return $this->recusar('Ano letivo não encontrado.') !== false;
        }

        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead(
            "SELECT COUNT(*) AS qtd FROM rm_matriculas_ensino WHERE ano = :ano",
            "ano={$ano['ano']}"
        );
        $qtd = (int) ($ler->getResultado()[0]['qtd'] ?? 0);

        if ($qtd > 0) {
            $this->recusar("O ano {$ano['ano']} tem {$qtd} matrícula(s) e não pode ser apagado. "
                . 'Desmarque "Ano ativo" para tirá-lo das listas.');

            return false;
        }

        $del = new \App\adms\Models\helper\AdmsDeletar();
        $del->exeDelete('adms_periodos_letivos', 'WHERE id=:id', "id={$id}");
        $_SESSION['msg'] = $this->aviso('success', "Ano letivo {$ano['ano']} apagado.");
        $this->resultado = true;

        return true;
    }

    private function recusar(string $texto)
    {
        $_SESSION['msg'] = $this->aviso('danger', 'Erro: ' . $texto);
        $this->resultado = false;

        return false;
    }

    private function aviso(string $cor, string $texto): string
    {
        return "<div class='alert alert-{$cor} alert-dismissible'>"
            . "<button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>"
            . htmlspecialchars($texto, ENT_QUOTES, 'UTF-8') . '</div>';
    }
}
