<?php

namespace App\adms\Models\relatorios;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\relatorios\AdmsRelatorioFonte as F;

/** R-AUD-04 Usuários por perfil e último acesso (docs/RELATORIOS.md, H; onda 2). */
class RelUsuariosAcesso extends AdmsRelatorioBase
{
    public const CODIGO = 'R-AUD-04';
    public const NOME = 'Usuários e último acesso';
    public const CATEGORIA = 'H';
    public const PERGUNTA = 'Quem tem acesso ao painel, com que perfil, e quem não entra há muito tempo?';
    public const SLUG = 'usuarios-acesso';
    public const QUEM = 'administrador';

    public function filtros(): array
    {
        $niv = [];
        foreach (F::ler("SELECT id, nome FROM adms_niveis_acessos ORDER BY ordem, id") as $n) {
            $niv[(int) $n['id']] = $n['nome'];
        }
        return ['nivel' => ['rotulo' => 'Perfil', 'tipo' => 'select', 'opcoes' => $niv],
            'dias' => ['rotulo' => 'Sem acesso há mais de (dias)', 'tipo' => 'numero', 'padrao' => '90']];
    }

    public function ficha(): array
    {
        return [
            'Usuários do painel (adms_usuarios), com o perfil (nível de acesso) e a situação.',
            'Último acesso = o registro de login mais recente do usuário no registro do sistema (adms_logs, página de login). Não há outra coluna de último acesso.',
            'Sem acesso há mais de N dias: usuário ATIVO cujo último acesso é mais antigo que N dias, ou que nunca entrou.',
        ];
    }

    public function dados(array $f): array
    {
        $dias = (int) ($f['dias'] ?? 90);
        $w = ['1 = 1'];
        $p = [];
        if (!empty($f['nivel'])) {
            $w[] = 'u.adms_niveis_acesso_id = :n';
            $p['n'] = (int) $f['nivel'];
        }
        $usuarios = F::ler(
            "SELECT u.id, u.nome, u.usuario, u.adms_sits_usuario_id, n.nome AS perfil, s.nome AS situacao,
                    (SELECT MAX(l.created) FROM adms_logs l WHERE l.pagina_id = 1 AND l.acao = 7 AND l.usuario_id = u.id) AS ultimo
               FROM adms_usuarios u LEFT JOIN adms_niveis_acessos n ON n.id = u.adms_niveis_acesso_id LEFT JOIN adms_sits_usuarios s ON s.id = u.adms_sits_usuario_id
              WHERE " . implode(' AND ', $w) . " ORDER BY n.ordem, u.nome",
            $p
        );
        $limite = date('Y-m-d H:i:s', strtotime("-{$dias} days"));
        $cols = [self::col('nome', 'Nome'), self::col('usuario', 'Usuário'), self::col('perfil', 'Perfil'), self::col('situacao', 'Situação'), self::col('ultimo', 'Último acesso'),
            self::col('diasSem', 'Dias sem acesso', ['num' => true])];
        $det = ['parados' => ['titulo' => "Ativos sem acesso há mais de {$dias} dias (ou nunca)", 'colunas' => $cols, 'linhas' => []]];
        $linhas = [];
        $porPerfil = [];
        foreach ($usuarios as $u) {
            $d = $u['ultimo'] ? (int) floor((time() - strtotime($u['ultimo'])) / 86400) : null;
            $l = ['nome' => (string) $u['nome'], 'usuario' => (string) $u['usuario'], 'perfil' => (string) $u['perfil'], 'situacao' => (string) $u['situacao'],
                'ultimo' => self::cel($u['ultimo'] ? date('d/m/Y H:i', strtotime($u['ultimo'])) : 'nunca', ['o' => (string) $u['ultimo']]), 'diasSem' => self::cel($d === null ? '—' : (string) $d, ['o' => $d ?? 99999])];
            $linhas[] = $l;
            $porPerfil[(string) $u['perfil']][] = $l;
            if ((int) $u['adms_sits_usuario_id'] === 1 && (!$u['ultimo'] || $u['ultimo'] < $limite)) {
                $det['parados']['linhas'][] = $l;
            }
        }
        $cartoes = [['rotulo' => 'Usuários', 'valor' => count($linhas)], ['rotulo' => "Ativos sem acesso > {$dias} dias", 'valor' => self::contar($det, 'parados'), 'detalhe' => 'parados', 'cor' => 'bg-warning']];
        $i = 0;
        foreach ($porPerfil as $perfil => $ls) {
            $det['perfil_' . $i] = ['titulo' => 'Perfil: ' . $perfil, 'colunas' => $cols, 'linhas' => $ls];
            $cartoes[] = ['rotulo' => $perfil, 'valor' => count($ls), 'detalhe' => 'perfil_' . $i++];
        }
        return ['cartoes' => $cartoes, 'detalhes' => $det, 'tabela' => ['colunas' => $cols, 'linhas' => $linhas, 'rodape' => ['nome' => 'Total', 'usuario' => (string) count($linhas)]]];
    }
}
