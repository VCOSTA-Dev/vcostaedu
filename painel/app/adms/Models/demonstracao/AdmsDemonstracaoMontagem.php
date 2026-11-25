<?php

namespace App\adms\Models\demonstracao;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

require_once dirname(__DIR__, 5) . '/database/ferramentas/demonstracao_passos.php';

/**
 * MONTAR E REMOVER A DEMONSTRAÇÃO PELA TELA, EM PASSOS CURTOS (Onda 3, Etapa 4).
 *
 * Cada passo roda num chamado próprio do navegador (Controllers/Demonstracao::passo), dentro do PHP da requisição: sem
 * executar programa nenhum e sem o cliente mysql. Os passos são os de database/ferramentas/demonstracao_passos.php — os
 * mesmos que a linha de comando roda em sequência —, mais o preparo (ligar a demonstração e limpar a anterior) e o fim
 * (as senhas fictícias para o cartão "Conheça o sistema").
 *
 * O ESTADO fica no banco (adms_instalacao_estado, "demonstracao.montagem"): o que está sendo feito (montar ou remover),
 * o passo, se falhou e por quê, e o tempo de cada passo. Fechar a página no meio não perde nada: a tela mostra
 * "interrompida no passo X" e o botão Continuar. Um passo repetido não duplica nada (cada um pula o que já existe).
 * A TRAVA: um passo só roda com a trava do banco (GET_LOCK); um segundo pedido ao mesmo tempo é recusado.
 * No fim, o estado sai do banco (a instalação fica igual à montada pela linha de comando).
 */
class AdmsDemonstracaoMontagem extends \App\adms\Models\helper\AdmsConn
{
    public const ESTADO = 'demonstracao.montagem';
    private const PREPARO = ['preparar' => 'Preparando a instalação'];
    private const FIM = ['concluir' => 'Concluindo a demonstração']; // D-103 (C7): antes, "Documentos" depois de "Os documentos"
    private const REMOVER = ['remover_dados' => 'Apagando os dados fictícios', 'remover_concluir' => 'Deixando a instalação pronta'];

    private \PDO $pdo;

    public function __construct()
    {
        $this->pdo = $this->connect();
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $this->pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
    }

    /** Os passos de cada ação, na ordem: chave => o grupo que a janela mostra. */
    public static function passos(string $acao): array
    {
        if ($acao === 'remover') {
            return self::REMOVER;
        }
        $p = self::PREPARO;
        $tipoDemo = \App\adms\Models\helper\AdmsTipoInstituicao::atual(); // Onda 4, Etapa 4; D-106: e o curso livre
        $cenario = $tipoDemo === 'escola' ? \VcDemoEscola::class : ($tipoDemo === 'livre' ? \VcDemoLivre::class : \VcDemoPassos::class);
        foreach ($cenario::PASSOS as $chave => [$grupo]) {
            $p[$chave] = $grupo;
        }
        return $p + self::FIM;
    }

    /** Os grupos (o que a janela lista), na ordem, com quantos passos cada um tem. */
    public static function grupos(string $acao): array
    {
        $g = [];
        foreach (self::passos($acao) as $grupo) {
            $g[$grupo] = ($g[$grupo] ?? 0) + 1;
        }
        return $g;
    }

    public function estado(): ?array
    {
        $st = $this->pdo->prepare('SELECT valor FROM adms_instalacao_estado WHERE chave = ?');
        $st->execute([self::ESTADO]);
        $v = $st->fetchColumn();
        $e = $v === false ? null : json_decode((string) $v, true);
        return is_array($e) ? $e : null;
    }

    private function gravar(?array $e): void
    {
        $this->gravarChave(self::ESTADO, $e === null ? null : json_encode($e, JSON_UNESCAPED_UNICODE));
    }

    private function gravarChave(string $chave, ?string $valor): void
    {
        if ($valor === null) {
            $this->pdo->prepare('DELETE FROM adms_instalacao_estado WHERE chave = ?')->execute([$chave]);
            return;
        }
        $this->pdo->prepare('INSERT INTO adms_instalacao_estado (chave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)')->execute([$chave, $valor]);
    }

    /** O que a janela precisa: a ação, o passo, o total, os grupos e a situação. Sem as senhas. */
    public static function publico(?array $e): ?array
    {
        if ($e === null) {
            return null;
        }
        $passos = self::passos((string) $e['acao']);
        $chaves = array_keys($passos);
        $i = (int) $e['passo'];
        return [
            'acao' => $e['acao'], 'passo' => $i, 'total' => count($chaves),
            'atual' => $chaves[$i] ?? null, 'grupoAtual' => $passos[$chaves[$i] ?? ''] ?? null,
            'grupos' => self::grupos((string) $e['acao']), 'falhou' => $e['falhou'] ?? null,
            'concluida' => !empty($e['concluida']), 'tempos' => $e['tempos'] ?? [],
        ];
    }

    /** Os dados que impedem a demonstração (a mesma conta da tela "Como você quer começar"). */
    public function dadosDaInstituicao(): array
    {
        $lista = implode(', ', array_map(fn($n) => $this->pdo->quote($n), \VcDemo::cursosDemo()));
        return [
            'cursos' => (int) $this->pdo->query("SELECT COUNT(*) FROM rm_cursos WHERE nome NOT IN ({$lista})")->fetchColumn(),
            'alunos' => (int) $this->pdo->query("SELECT COUNT(*) FROM col_usuarios WHERE col_niveis_acesso_id = 1 AND rm NOT BETWEEN 250000 AND 269999")->fetchColumn(),
        ];
    }

    /**
     * A instalação ainda não tem nenhum dado próprio (nada além do administrador)? Então a demonstração não pede a senha
     * de novo: não há o que perder. Com qualquer outro usuário, curso, aluno, matéria ou polo, a senha é pedida.
     */
    public function semDadoProprio(): bool
    {
        $adm = (int) $this->pdo->query("SELECT COUNT(*) FROM adms_usuarios u JOIN adms_niveis_acessos n ON n.id = u.adms_niveis_acesso_id WHERE n.papel <> 'administrador' OR n.papel IS NULL")->fetchColumn();
        $outros = (int) $this->pdo->query('SELECT (SELECT COUNT(*) FROM rm_cursos) + (SELECT COUNT(*) FROM col_usuarios) + (SELECT COUNT(*) FROM rm_materias) + (SELECT COUNT(*) FROM rm_polos WHERE nome <> ' . "'Sede'" . ')')->fetchColumn();
        return $adm === 0 && $outros === 0;
    }

    /**
     * A TRAVA DA INSTITUIÇÃO CONFIGURADA (a mesma das ferramentas, vcostaedu_instituicao_configurada): a demonstração grava a
     * instituição fictícia por cima da que estiver na base e devolve os modelos de histórico à versão 1. Se a instituição
     * não é a fictícia (foi configurada pela tela), montar e remover recusam e dizem o que seria perdido.
     * @return string[] o que seria perdido ([] = nada)
     */
    public function seriaPerdido(): array
    {
        $i = $this->pdo->query('SELECT nome, logo, cabecalho_1, telefone, email FROM adms_empresas WHERE instituicao = 1 ORDER BY id LIMIT 1')->fetch();
        if (!$i || in_array($i['nome'], \VcDemo::instituicoesDemo(), true)) {
            return [];
        }
        $perde = ['os dados da instituição (nome, mantenedora, CNPJ, e-MEC, endereço, site' . ($i['telefone'] ? ', telefone' : '') . ($i['email'] ? ', e-mail' : '') . ')'];
        if ($i['cabecalho_1']) {
            $perde[] = 'as linhas do cabeçalho dos documentos';
        }
        if ($i['logo']) {
            $perde[] = 'o logo enviado pela tela';
        }
        $versoes = (int) $this->pdo->query('SELECT COUNT(*) FROM rm_historico_modelo_versoes WHERE modelo_id IN (1, 2) AND versao > 1')->fetchColumn();
        if ($versoes > 0) {
            $perde[] = "as {$versoes} versão(ões) dos modelos de histórico depois da 1ª (inclusive nome e cargo de quem assina)";
        }
        return $perde;
    }

    private function recusaDaInstituicao(): string
    {
        $perde = $this->seriaPerdido();
        return $perde ? 'A instituição desta instalação foi configurada pela tela (não é a da demonstração). Seguir apagaria: ' . implode('; ', $perde) . '. Nada foi alterado.' : '';
    }

    public function demonstracaoAtiva(): bool
    {
        return (string) $this->pdo->query("SELECT valor FROM adms_instalacao_estado WHERE chave = 'demonstracao.ativa'")->fetchColumn() === '1';
    }

    /** Começa (ou devolve a que já está em andamento). Devolve [ok, mensagem]. */
    public function iniciar(string $acao): array
    {
        $e = $this->estado();
        if ($e !== null) {
            return $e['acao'] === $acao ? [true, ''] : [false, 'Há outra operação da demonstração em andamento. Termine-a antes.'];
        }
        if ($acao === 'montar') {
            if ($this->demonstracaoAtiva()) {
                return [false, 'A demonstração já está ligada.'];
            }
            $d = $this->dadosDaInstituicao();
            if ($d['cursos'] > 0 || $d['alunos'] > 0) {
                return [false, "Esta instalação já tem dados da instituição ({$d['cursos']} curso(s) e {$d['alunos']} aluno(s)). A demonstração só é montada numa instalação ainda vazia."];
            }
            if (($r = $this->recusaDaInstituicao()) !== '') {
                return [false, $r];
            }
        } elseif ($acao === 'remover') {
            if (!$this->demonstracaoAtiva()) {
                return [false, 'A demonstração não está ligada.'];
            }
            if (($r = $this->recusaDaInstituicao()) !== '') {
                return [false, $r];
            }
            $reais = (int) $this->pdo->query("SELECT COUNT(*) FROM rm_historico_emissoes WHERE ambiente <> 'demonstracao'")->fetchColumn();
            if ($reais > 0) {
                return [false, "Há {$reais} documento(s) emitido(s) que não são da demonstração: a remoção os apagaria. Nada foi alterado."];
            }
        } else {
            return [false, 'Operação desconhecida.'];
        }
        $this->gravar(['acao' => $acao, 'passo' => 0, 'falhou' => null, 'inicio' => date('Y-m-d H:i:s'), 'tempos' => [],
            'senhas' => $acao === 'montar' ? (\VcDemo::cenario($this->pdo))::novasSenhas() : []]);
        return [true, ''];
    }

    private function trava(): string
    {
        return 'vcostaedu_demo_' . substr(sha1((string) $this->pdo->query('SELECT DATABASE()')->fetchColumn()), 0, 32);
    }

    /**
     * Roda o próximo passo (ou o que falhou, de novo). $cookieAdmin: a sessão de quem pediu, para as telas que o
     * administrador faz (a sessão já foi liberada pelo controller). Devolve o estado público.
     */
    public function executarPasso(string $base, string $cookieAdmin): array
    {
        $e = $this->estado();
        if ($e === null) {
            return ['erro' => 'Nada em andamento.'];
        }
        $ok = (int) $this->pdo->query('SELECT GET_LOCK(' . $this->pdo->quote($this->trava()) . ', 0)')->fetchColumn();
        if ($ok !== 1) {
            return ['erro' => 'Outro passo da demonstração está rodando agora (em outra aba?). Espere ele terminar.', 'ocupado' => true] + (self::publico($e) ?? []);
        }
        try {
            $e = $this->estado() ?? $e; // relido com a trava
            $passos = array_keys(self::passos((string) $e['acao']));
            $i = (int) $e['passo'];
            if ($i >= count($passos)) {
                return self::publico($e) ?? [];
            }
            $chave = $passos[$i];
            $e['falhou'] = null;
            $t0 = microtime(true);
            try {
                $this->rodar($chave, $e, $base, $cookieAdmin);
                $e['tempos'][$chave] = round(microtime(true) - $t0, 1);
                $e['passo'] = $i + 1;
                if ($e['passo'] >= count($passos)) {
                    (new \App\adms\Models\helper\AdmsSalvarLog())->salvarLogs($e['acao'] === 'montar' ? 468 : 469, 200, 2, 'Demonstração: ' . $e['acao'] . ' (em passos)');
                    $this->gravar(null); // concluída: o estado sai (a instalação fica como a montada pela linha de comando)
                    return ['concluida' => true, 'acao' => $e['acao'], 'passo' => $e['passo'], 'total' => count($passos), 'tempos' => $e['tempos'], 'grupos' => self::grupos((string) $e['acao'])];
                }
            } catch (\Throwable $ex) {
                $e['falhou'] = ['passo' => $chave, 'motivo' => self::motivo($ex)];
                error_log('[vcostaedu][demonstracao] passo ' . $chave . ': ' . $ex->getMessage());
            }
            $this->gravar($e);
            return self::publico($e) ?? [];
        } finally {
            $this->pdo->query('SELECT RELEASE_LOCK(' . $this->pdo->quote($this->trava()) . ')');
        }
    }

    /** A mensagem para a tela: a do passo, sem caminho do servidor nem erro de banco. */
    private static function motivo(\Throwable $ex): string
    {
        $m = $ex instanceof \VcDemoFalha ? $ex->getMessage() : 'erro inesperado no servidor (o detalhe ficou no registro do servidor).';
        return (string) preg_replace('#[A-Za-z]:[\\\\/][^\s]*|/(?:var|home|srv|usr|tmp)/[^\s]*#', '…', $m);
    }

    private function rodar(string $chave, array $e, string $base, string $cookieAdmin): void
    {
        @set_time_limit(120);
        if ($chave === 'preparar') {
            $this->gravarChave('demonstracao.ativa', '1'); // libera as travas da demonstração (o próprio SQL confere)
            try {
                \VcDemoLimpeza::rodar($this->pdo);
            } catch (\Throwable $ex) {
                $this->gravarChave('demonstracao.ativa', null);
                throw $ex;
            }
            $faltam = \VcDemoLimpeza::gatilhosFaltando($this->pdo);
            if ($faltam) {
                $this->gravarChave('demonstracao.ativa', null);
                \VcDemoPassos::falha('faltam gatilhos de proteção do histórico (' . implode(', ', $faltam) . '): o usuário do banco precisa do privilégio TRIGGER.');
            }
            return;
        }
        if ($chave === 'concluir') {
            $cred = ['painel' => [], 'portal' => []];
            foreach ($e['senhas'] as $quem => $senha) {
                if (str_contains((string) $quem, '@')) { // Onda 7: os responsáveis fictícios (só na escola)
                    $cred['familia'][(string) $quem] = $senha;
                } elseif (preg_match('/^\d+$/', (string) $quem)) {
                    $cred['portal'][(string) $quem] = $senha;
                } else {
                    $cred['painel'][$quem] = $senha;
                }
            }
            $this->gravarChave('demonstracao.credenciais', json_encode($cred, JSON_UNESCAPED_UNICODE));
            $this->gravarChave('comeco.escolhido', 'demonstracao');
            return;
        }
        if ($chave === 'remover_dados') {
            \VcDemoLimpeza::rodar($this->pdo);
            $faltam = \VcDemoLimpeza::gatilhosFaltando($this->pdo);
            if ($faltam) {
                \VcDemoPassos::falha('faltam gatilhos de proteção do histórico (' . implode(', ', $faltam) . '): o usuário do banco precisa do privilégio TRIGGER.');
            }
            return;
        }
        if ($chave === 'remover_concluir') {
            /* os anos letivos que a demonstração criou e que nada mais usa: sem isso, a fase "o primeiro ano letivo"
               começaria concluída pelo ano fictício */
            $this->pdo->exec('DELETE p FROM adms_periodos_letivos p
                WHERE NOT EXISTS (SELECT 1 FROM rm_professores_materias m WHERE m.ano = p.ano)
                  AND NOT EXISTS (SELECT 1 FROM portal_disciplinas_alunos d WHERE d.ano = p.ano)');
            $this->gravarChave('demonstracao.ativa', null);
            $this->gravarChave('demonstracao.credenciais', null);
            /* Correção da escola, Parte 4 (B17; D-103): sem a demonstração, a escolha do começo volta a valer — a Página
               Inicial mostra de novo o "Como você quer começar?" enquanto a instalação não tem os dados da instituição */
            $this->gravarChave('comeco.escolhido', null);
            return;
        }
        $admin = static function () use ($cookieAdmin): \VcDemoNavegador {
            return new \VcDemoNavegador($cookieAdmin);
        };
        $c = new \VcDemoContexto($this->pdo, $base, $admin, (array) $e['senhas']);
        (\VcDemo::cenario($this->pdo))::rodar($chave, $c); // Onda 4, Etapa 4: a faculdade ou o colégio
    }
}
