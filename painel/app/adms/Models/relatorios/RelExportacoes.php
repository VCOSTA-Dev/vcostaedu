<?php

namespace App\adms\Models\relatorios;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\relatorios\AdmsRelatorioFonte as F;

/** R-AUD-03 Exportações de relatórios (docs/RELATORIOS.md, H; onda 2): a tabela de auditoria da LGPD (D-457). */
class RelExportacoes extends AdmsRelatorioBase
{
    public const CODIGO = 'R-AUD-03';
    public const NOME = 'Exportações de relatórios';
    public const CATEGORIA = 'H';
    public const PERGUNTA = 'Quem levou dados para fora do sistema, de qual relatório e com quais filtros?';
    public const SLUG = 'exportacoes';
    public const QUEM = 'administrador';

    public function filtros(): array
    {
        $rel = [];
        foreach (AdmsRelatorios::DEFINICOES as $c => $cl) {
            $rel[$c] = $c . ' ' . $cl::NOME;
        }
        $us = [];
        foreach (F::ler("SELECT DISTINCT u.id, u.nome FROM rm_relatorios_exportacoes e INNER JOIN adms_usuarios u ON u.id = e.usuario_id ORDER BY u.nome") as $u) {
            $us[(int) $u['id']] = $u['nome'];
        }
        return ['de' => ['rotulo' => 'De', 'tipo' => 'data'], 'ate' => ['rotulo' => 'até', 'tipo' => 'data'],
            'relatorio' => ['rotulo' => 'Relatório', 'tipo' => 'select', 'opcoes' => $rel], 'usuario' => ['rotulo' => 'Quem', 'tipo' => 'select', 'opcoes' => $us]];
    }

    public function ficha(): array
    {
        return [
            'Cada exportação (Excel, CSV, PDF ou impressão) de qualquer relatório é gravada ANTES de o arquivo ser gerado: quem, quando, relatório, formato, filtros e quantas linhas. Sem o registro, o arquivo não sai.',
            'O período filtra a data da exportação.',
        ];
    }

    public function dados(array $f): array
    {
        $w = ['1 = 1'];
        $p = [];
        if (!empty($f['relatorio'])) {
            $w[] = 'e.relatorio = :r';
            $p['r'] = $f['relatorio'];
        }
        if (!empty($f['usuario'])) {
            $w[] = 'e.usuario_id = :u';
            $p['u'] = (int) $f['usuario'];
        }
        foreach (['de' => '>=', 'ate' => '<='] as $k => $op) {
            if (!empty($f[$k])) {
                $w[] = "e.created {$op} :{$k}";
                $p[$k] = $f[$k] . ($k === 'de' ? ' 00:00:00' : ' 23:59:59');
            }
        }
        $linhas = [];
        $porFormato = [];
        foreach (F::ler("SELECT e.*, u.nome AS usuario, n.nome AS perfil FROM rm_relatorios_exportacoes e LEFT JOIN adms_usuarios u ON u.id = e.usuario_id
                          LEFT JOIN adms_niveis_acessos n ON n.id = u.adms_niveis_acesso_id WHERE " . implode(' AND ', $w) . " ORDER BY e.created DESC, e.id DESC", $p) as $e) {
            $c = AdmsRelatorios::DEFINICOES[$e['relatorio']] ?? null;
            $l = ['quando' => self::cel(date('d/m/Y H:i:s', strtotime($e['created'])), ['o' => $e['created']]), 'usuario' => (string) $e['usuario'], 'perfil' => (string) $e['perfil'],
                'relatorio' => $e['relatorio'] . ($c ? ' ' . $c::NOME : ''), 'formato' => (string) $e['formato'],
                'filtros' => $c && $e['filtros'] !== '' ? self::cel(urldecode(str_replace('&', '; ', (string) $e['filtros'])), ['url' => URLADM . 'relatorios/' . $c::SLUG . '?' . $e['filtros']]) : (string) $e['filtros'],
                'linhas' => (string) $e['linhas']];
            $linhas[] = $l;
            $porFormato[$e['formato']][] = $l;
        }
        $cols = [self::col('quando', 'Quando'), self::col('usuario', 'Quem'), self::col('perfil', 'Perfil'), self::col('relatorio', 'Relatório'), self::col('formato', 'Formato'),
            self::col('filtros', 'Filtros'), self::col('linhas', 'Linhas', ['num' => true])];
        $det = [];
        $cartoes = [['rotulo' => 'Exportações', 'valor' => count($linhas)]];
        foreach (AdmsRelatorios::FORMATOS as $fm) {
            $det["f_{$fm}"] = ['titulo' => 'Formato ' . $fm, 'colunas' => $cols, 'linhas' => $porFormato[$fm] ?? []];
            $cartoes[] = ['rotulo' => ucfirst($fm), 'valor' => self::contar($det, "f_{$fm}"), 'detalhe' => "f_{$fm}"];
        }
        return ['cartoes' => $cartoes, 'detalhes' => $det, 'tabela' => ['colunas' => $cols, 'linhas' => $linhas, 'ordem' => [[0, 'desc']], 'rodape' => ['quando' => 'Total', 'usuario' => (string) count($linhas)]]];
    }
}
