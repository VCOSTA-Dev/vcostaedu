<?php

namespace App\adms\Models\escola;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\escola\AdmsResponsaveis as M;

/**
 * A PLANILHA DE RESPONSÁVEIS (Onda 7, Etapa 2; D-62) — uma planilha própria, separada da de alunos (mais clara para
 * quem preenche: uma linha por papel). As colunas: RA do aluno; ano letivo; papel (financeiro ou titular); nome;
 * e-mail; celular; CPF; parentesco; motivo (só para trocar quem já está no papel).
 *
 * A mesma conferência linha a linha da importação de alunos, e o mesmo tudo ou nada: se uma linha não passa, nada é
 * gravado; aplicada, cada linha passa pela mesma função da tela (AdmsResponsaveis::designar), numa transação só. O
 * acesso acadêmico adicional não entra pela planilha: exige o motivo, e é dado pela tela.
 * Até 1.000 linhas por arquivo (a aplicação cabe numa requisição curta; acima disso, divida o arquivo).
 */
class AdmsResponsaveisPlanilha
{
    public const MAX_LINHAS = 1000;
    /* Correção da escola, Parte 3 (D-102): o endereço da pessoa, opcional, nas últimas colunas ("numero_endereco": o
       "numero" é o da linha da planilha) */
    public const COLUNAS = ['ra', 'ano', 'papel', 'nome', 'email', 'celular', 'cpf', 'parentesco', 'motivo', 'endereco', 'numero_endereco', 'complemento', 'bairro', 'cidade', 'uf', 'cep'];
    private const SESSAO = 'vc_planilha_responsaveis';

    public static function modelo(): string
    {
        $ano = (int) date('Y');
        return "ra;ano;papel;nome;email;celular;cpf;parentesco;motivo;endereco;numero_endereco;complemento;bairro;cidade;uf;cep\r\n"
            . "265001;{$ano};financeiro;Nome da Pessoa (exemplo);pessoa@exemplo.invalid;11999990000;;Mãe;;Rua do Exemplo;100;;Centro;Cidade Exemplo;SP;00000-000\r\n"
            . "265001;{$ano};titular;Nome da Pessoa (exemplo);pessoa@exemplo.invalid;11999990000;;Mãe;;;;;;;;\r\n";
    }

    /** Lê o CSV (ponto e vírgula ou vírgula; com ou sem a marca do UTF-8). @return array|string */
    public static function ler(string $conteudo)
    {
        $conteudo = preg_replace('/^\xEF\xBB\xBF/', '', $conteudo);
        if (!mb_check_encoding($conteudo, 'UTF-8')) {
            $conteudo = mb_convert_encoding($conteudo, 'UTF-8', 'Windows-1252');
        }
        $linhas = preg_split('/\r\n|\r|\n/', trim($conteudo));
        if (!$linhas || trim($linhas[0]) === '') {
            return 'A planilha está vazia.';
        }
        $sep = substr_count($linhas[0], ';') >= substr_count($linhas[0], ',') ? ';' : ',';
        $cab = array_map(static fn($c) => mb_strtolower(trim(str_replace(['-', ' '], '', (string) $c))), str_getcsv($linhas[0], $sep));
        $cab = array_map(static fn($c) => $c === 'emailr' ? 'email' : $c, $cab);
        foreach (['ra', 'ano', 'papel', 'nome'] as $obrig) {
            if (!in_array($obrig, $cab, true)) {
                return 'A planilha precisa das colunas ' . implode(', ', self::COLUNAS) . ' (na primeira linha). Falta: ' . $obrig . '. Baixe o modelo.';
            }
        }
        $saida = [];
        foreach (array_slice($linhas, 1) as $i => $l) {
            if (trim($l) === '') {
                continue;
            }
            $v = str_getcsv($l, $sep);
            $linha = [];
            foreach ($cab as $k => $nome) {
                $linha[$nome] = trim((string) ($v[$k] ?? ''));
            }
            $linha['numero'] = $i + 2;
            $saida[] = $linha;
        }
        if (!$saida) {
            return 'A planilha não tem nenhuma linha depois do cabeçalho.';
        }
        if (count($saida) > self::MAX_LINHAS) {
            return 'A planilha tem ' . count($saida) . ' linhas; o máximo é ' . self::MAX_LINHAS . ' por arquivo. Divida em arquivos menores.';
        }
        return $saida;
    }

    /**
     * Confere linha a linha, sem gravar nada. @return array|string ['lote', 'linhas' => [..., 'erros' => [], 'acao']], ou a recusa
     */
    public static function conferir(?array $arquivo)
    {
        if (!$arquivo || ($arquivo['error'] ?? 1) !== UPLOAD_ERR_OK || !is_uploaded_file((string) $arquivo['tmp_name'])) {
            return 'Escolha o arquivo da planilha (CSV).';
        }
        if (!preg_match('/\.(csv|txt)$/i', (string) $arquivo['name'])) {
            return 'A planilha vai em CSV (no Excel ou no LibreOffice: Salvar como → CSV).';
        }
        $linhas = self::ler((string) file_get_contents((string) $arquivo['tmp_name']));
        if (is_string($linhas)) {
            return $linhas;
        }
        return self::conferirLinhas($linhas);
    }

    /** A conferência das linhas já lidas (também para a prova). */
    public static function conferirLinhas(array $linhas): array
    {
        $anos = M::anos();
        $visto = [];
        $pessoasDaPlanilha = [];
        foreach ($linhas as &$l) {
            $erros = [];
            $aluno = $l['ra'] !== '' ? M::alunoPorRa($l['ra']) : null;
            if (!$aluno) {
                $erros[] = 'aluno não encontrado pelo RA';
            }
            $ano = (int) $l['ano'];
            if (!in_array($ano, $anos, true)) {
                $erros[] = 'o ano letivo ' . ($l['ano'] ?: '(vazio)') . ' não está aberto';
            }
            $papel = mb_strtolower($l['papel']);
            if (in_array($papel, ['adicional', 'acesso adicional'], true)) {
                $erros[] = 'o acesso acadêmico adicional não entra pela planilha (exige o motivo): libere pela tela';
            } elseif (!in_array($papel, ['financeiro', 'titular'], true)) {
                $erros[] = 'o papel é "financeiro" ou "titular"';
            }
            [$c, $e] = M::limparPessoa($l);
            if (!$c) {
                $erros[] = mb_strtolower(rtrim($e, '.'));
            } elseif (!M::procurar($c['email'], $c['cpf']) && ($recusa = M::loginDeOutro($c, 0)) !== '') {
                $erros[] = rtrim($recusa, '.'); // Onda 8 (D-67): o e-mail ou o CPF que já é o login de outra conta
            }
            $chave = ($aluno['id'] ?? 0) . '|' . $ano . '|' . $papel;
            if (isset($visto[$chave])) {
                $erros[] = 'outra linha (' . $visto[$chave] . ') já dá este papel a este aluno neste ano — um só por papel';
            }
            $visto[$chave] = $l['numero'];
            /* a mesma pessoa em duas linhas: o mesmo e-mail ou CPF tem de ter o mesmo nome */
            if ($c) {
                foreach (array_filter([$c['email'], $c['cpf']]) as $id) {
                    if (isset($pessoasDaPlanilha[$id]) && $pessoasDaPlanilha[$id] !== $c['nome']) {
                        $erros[] = 'o e-mail ou o CPF aparece em outra linha com outro nome';
                    }
                    $pessoasDaPlanilha[$id] = $c['nome'];
                }
            }
            $acao = 'designar';
            if ($aluno && !$erros) {
                $existe = $c ? M::procurar($c['email'], $c['cpf']) : null;
                $atual = M::emVigor((int) $aluno['id'], $ano, $papel);
                if ($atual && $existe && (int) $atual['responsavel_id'] === (int) $existe['id']) {
                    $acao = 'igual';
                } elseif ($atual) {
                    if (trim((string) ($l['motivo'] ?? '')) === '') {
                        $erros[] = 'o aluno já tem ' . $atual['nome'] . ' neste papel; para trocar, preencha o motivo';
                    } else {
                        $acao = 'trocar';
                    }
                }
                $l['pessoaExiste'] = $existe ? $existe['nome'] : null;
            }
            $l['aluno'] = $aluno;
            $l['erros'] = $erros;
            $l['acao'] = $acao;
        }
        unset($l);
        $lote = bin2hex(random_bytes(8));
        $ok = !array_filter($linhas, static fn($l) => $l['erros']);
        if ($ok) {
            $_SESSION[self::SESSAO] = ['lote' => $lote, 'linhas' => array_map(static fn($l) => array_intersect_key($l, array_flip(array_merge(self::COLUNAS, ['numero']))), $linhas)];
        } else {
            unset($_SESSION[self::SESSAO]);
        }
        return ['lote' => $ok ? $lote : '', 'linhas' => $linhas, 'ok' => $ok];
    }

    /** Aplica o lote conferido: tudo ou nada. @return array|string a contagem, ou a recusa */
    public static function aplicar(string $lote, string $origem = 'planilha')
    {
        $s = $_SESSION[self::SESSAO] ?? null;
        if (!$s || !hash_equals((string) $s['lote'], $lote)) {
            return 'A conferência expirou ou já foi aplicada. Envie a planilha de novo.';
        }
        $linhas = $s['linhas'];
        return self::aplicarLinhas($linhas, $origem, function () {
            unset($_SESSION[self::SESSAO]);
        });
    }

    /** @return array|string */
    public static function aplicarLinhas(array $linhas, string $origem = 'planilha', ?callable $depois = null)
    {
        $T = \Core\Transacao::class;
        $antesPessoas = (int) (AdmsEscolaBanco::pdo()->query('SELECT COUNT(*) FROM esc_responsaveis')->fetchColumn());
        $erro = '';
        $n = $T::executar(function () use ($T, $linhas, $origem, &$erro) {
            $designacoes = 0;
            foreach ($linhas as $l) {
                $aluno = M::alunoPorRa((string) $l['ra']);
                if (!$aluno) {
                    $erro = "Linha {$l['numero']}: aluno não encontrado.";
                    $T::falhar($erro);
                }
                [$c] = M::limparPessoa($l);
                $existe = $c ? M::procurar($c['email'], $c['cpf']) : null;
                $pessoa = $existe ? ['id' => (int) $existe['id']] : $l;
                $pessoa['parentesco'] = (string) ($l['parentesco'] ?? '');
                $r = M::designar((int) $aluno['id'], (int) $l['ano'], mb_strtolower((string) $l['papel']), $pessoa, null, (string) ($l['motivo'] ?? ''), $origem);
                /* D-102: o endereço da planilha, quando a linha o traz (sem ele, o endereço que já existe fica) */
                if ($r === true && trim((string) ($l['endereco'] ?? '')) !== '') {
                    $idPessoa = $existe ? (int) $existe['id'] : (int) (($c ? M::procurar($c['email'], $c['cpf']) : null)['id'] ?? 0);
                    $end = ['endereco' => $l['endereco'], 'numero' => $l['numero_endereco'] ?? '', 'complemento' => $l['complemento'] ?? '', 'bairro' => $l['bairro'] ?? '',
                        'cidade' => $l['cidade'] ?? '', 'uf' => $l['uf'] ?? '', 'cep' => $l['cep'] ?? ''];
                    $e = $idPessoa ? M::gravarEndereco($idPessoa, $end) : 'a pessoa não foi encontrada para gravar o endereço';
                    if ($e !== '') {
                        $erro = "Linha {$l['numero']}: {$e}";
                        $T::falhar($erro);
                    }
                }
                if ($r !== true) {
                    $erro = "Linha {$l['numero']}: {$r}";
                    $T::falhar($erro);
                }
                $designacoes++;
            }
            return $designacoes;
        });
        if ($n === false) {
            return ($erro !== '' ? $erro : $T::ultimoErro()) . ' Nada foi gravado.';
        }
        if ($depois) {
            $depois();
        }
        $pessoas = (int) (AdmsEscolaBanco::pdo()->query('SELECT COUNT(*) FROM esc_responsaveis')->fetchColumn()) - $antesPessoas;
        return ['linhas' => count($linhas), 'pessoas' => $pessoas, 'designacoes' => (int) $n];
    }
}
