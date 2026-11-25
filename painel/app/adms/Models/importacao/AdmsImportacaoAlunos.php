<?php

namespace App\adms\Models\importacao;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use App\adms\Models\importacao\AdmsLeitorPlanilha as Leitor;

/**
 * IMPORTAÇÃO DE ALUNOS POR PLANILHA (Onda 2, Etapa 5) — o desenho do importador do vcostaedu, adaptado ao modelo daqui.
 *
 * O lote nasce "conferindo": a linha BRUTA do arquivo fica guardada, o de-para das colunas (achatado, com ajuste na tela
 * e memória para o próximo arquivo com os mesmos cabeçalhos) e as correções feitas na tela ficam à parte, e o
 * JULGAMENTO de cada linha (pronta, com aviso, com erro, pulada) é refeito a cada mudança. Nada é adivinhado: valor
 * não reconhecido é erro da linha, com o motivo.
 *
 * Aplicar grava pelas MESMAS funções da tela — AdmsCadastrarUsuarioAcademico (o aluno) e AdmsVincularAluno (o vínculo)
 * —, numa transação só: uma linha que falha desfaz o lote inteiro. Desfazer (ação sensível) tira os alunos do lote
 * enquanto nenhum tiver lançamento, documento ou uso; senão diz quais impedem.
 */
class AdmsImportacaoAlunos
{
    public const CONFERINDO = 1;
    public const APLICADA = 2;
    public const DESFEITA = 3;

    public const PRONTA = 1;
    public const AVISO = 2;
    public const ERRO = 3;
    public const PULADA = 4;
    public const APLICADA_LINHA = 5;

    /** campo => [rótulo, obrigatório, sinônimos do cabeçalho (achatados)] */
    public const CAMPOS = [
        'ra' => ['RA', true, ['ra', 'r a', 'rm', 'registro academico', 'matricula', 'n matricula', 'numero de matricula', 'no matricula', 'codigo do aluno']],
        'nome' => ['Nome', true, ['nome', 'nome completo', 'aluno', 'nome do aluno', 'aluna', 'estudante', 'nome do estudante']],
        'cpf' => ['CPF', false, ['cpf', 'cpf do aluno', 'documento cpf']],
        'email' => ['E-mail', false, ['email', 'e mail', 'correio eletronico', 'e mail do aluno', 'email do aluno']],
        'nascimento' => ['Nascimento', false, ['nascimento', 'data de nascimento', 'data nascimento', 'dt nascimento', 'dt nasc', 'data nasc', 'nasc']],
        'sexo' => ['Sexo', false, ['sexo', 'genero']],
        'telefone' => ['Telefone', false, ['telefone', 'celular', 'fone', 'whatsapp', 'telefone celular']],
        'situacao' => ['Situação', false, ['situacao', 'situacao do aluno', 'status']],
        'curso' => ['Curso', false, ['curso', 'nome do curso']],
        'grade' => ['Grade', false, ['grade', 'matriz', 'versao da grade', 'grade curricular']],
        'ano' => ['Ano', false, ['ano', 'ano letivo', 'ano de ingresso']],
        'polo' => ['Polo', false, ['polo', 'unidade', 'localidade']],
        'modalidade' => ['Modalidade', false, ['modalidade', 'modalidade de ensino']],
    ];

    /** Onda 4, Etapa 2: a coluna da turma, só numa instalação de escola (no ensino superior, os campos de sempre). */
    public const CAMPO_TURMA = ['turma' => ['Turma', false, ['turma', 'classe', 'turma do aluno', 'serie e turma']]];

    /** Os campos desta instalação: os de sempre e, na escola, a turma. */
    public static function campos(): array
    {
        return \App\adms\Models\helper\AdmsTipoInstituicao::porTurma() ? self::CAMPOS + self::CAMPO_TURMA : self::CAMPOS;
    }

    /** de-para de VALOR (achatado => valor) */
    private const SEXO = ['f' => 1, 'fem' => 1, 'feminino' => 1, 'mulher' => 1, 'm' => 2, 'masc' => 2, 'masculino' => 2, 'homem' => 2, 'o' => 3, 'outro' => 3, 'outros' => 3];
    private const SITUACAO = ['ativo' => 1, 'ativa' => 1, 'a' => 1, 'sim' => 1, '1' => 1, 'inativo' => 2, 'inativa' => 2, 'i' => 2, 'nao' => 2, '2' => 2];
    private const MODALIDADE_SINONIMOS = ['a distancia' => 'ead', 'distancia' => 'ead', 'ensino a distancia' => 'ead', 'semipresencial' => 'hibrido', 'semi presencial' => 'hibrido'];

    public const NOMES_RESULTADO = [1 => 'Pronta', 2 => 'Com aviso', 3 => 'Com erro', 4 => 'Pulada', 5 => 'Aplicada'];
    public const NOMES_SITUACAO = [1 => 'Conferindo', 2 => 'Aplicada', 3 => 'Desfeita', 4 => 'Aplicando'];

    private static ?\PDO $conexao = null;
    private string $erro = '';
    private array $impedimentos = [];

    public function getErro(): string
    {
        return $this->erro;
    }

    /** Desfazer recusado: [['rm', 'nome', 'motivos' => [...]], ...] */
    public function getImpedimentos(): array
    {
        return $this->impedimentos;
    }

    /** A conexão da transação em curso (a mesma dos helpers) ou uma própria. */
    private static function db(): \PDO
    {
        if ($pdo = \Core\Transacao::pdo()) {
            return $pdo;
        }
        if (self::$conexao === null) {
            self::$conexao = \VcBanco::daRequisicao(); // Onda 6, Etapa 1: a conexão da requisição
        }
        return self::$conexao;
    }

    private static function q(string $sql, array $p = []): \PDOStatement
    {
        $st = self::db()->prepare($sql);
        $st->execute($p);
        $st->setFetchMode(\PDO::FETCH_ASSOC);
        return $st;
    }

    // ------------------------------------------------------------------ o de-para das colunas

    /** Coluna do arquivo => campo, pela forma achatada do cabeçalho; cada campo uma vez só. */
    public static function mapearAutomatico(array $cabecalho): array
    {
        $mapa = [];
        $usados = [];
        foreach ($cabecalho as $i => $titulo) {
            $a = Leitor::achatar((string) $titulo);
            $mapa[$i] = '';
            foreach (self::campos() as $campo => [$rotulo, , $sinonimos]) {
                if (isset($usados[$campo])) {
                    continue;
                }
                if ($a === Leitor::achatar($rotulo) || in_array($a, $sinonimos, true)) {
                    $mapa[$i] = $campo;
                    $usados[$campo] = true;
                    break;
                }
            }
        }
        return $mapa;
    }

    public static function assinatura(array $cabecalho): string
    {
        return sha1(implode('|', array_map(fn($c) => Leitor::achatar((string) $c), $cabecalho)));
    }

    // ------------------------------------------------------------------ o lote

    /** Cria o lote a partir do arquivo lido; o de-para vem do último lote com os mesmos cabeçalhos, ou o automático. */
    public function criar(string $arquivoNome, array $leitura): ?int
    {
        $cab = $leitura['cabecalho'];
        $assinatura = self::assinatura($cab);
        $mapa = null;
        $anterior = self::q('SELECT mapa_colunas FROM adms_importacoes WHERE assinatura = ? ORDER BY id DESC LIMIT 1', [$assinatura])->fetchColumn();
        if ($anterior !== false) {
            $m = json_decode((string) $anterior, true);
            if (is_array($m) && count($m) === count($cab)) {
                $mapa = $m;
            }
        }
        $mapa = $mapa ?? self::mapearAutomatico($cab);
        $id = (int) \Core\Transacao::executar(function () use ($arquivoNome, $leitura, $cab, $assinatura, $mapa) {
            self::q('INSERT INTO adms_importacoes (tipo, arquivo_nome, separador, codificacao, cabecalho, assinatura, mapa_colunas, situacao, total, usuario_cre)
                     VALUES (\'alunos\', ?, ?, ?, ?, ?, ?, 1, ?, ?)', [
                mb_substr($arquivoNome, 0, 255), $leitura['separador'], $leitura['codificacao'],
                json_encode($cab, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $assinatura, json_encode($mapa), count($leitura['linhas']), (int) $_SESSION['colUsuario_id'],
            ]);
            $id = (int) self::db()->lastInsertId();
            /* 250 linhas por comando (Onda 6, Etapa 4: um arquivo de 5000 linhas em segundos) */
            foreach (array_chunk($leitura['linhas'], 250, true) as $parte) {
                $p = [];
                foreach ($parte as $numero => $campos) {
                    array_push($p, $id, (int) $numero, json_encode(array_values($campos), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                }
                self::q('INSERT INTO adms_importacoes_linhas (importacao_id, numero, bruto) VALUES ' . implode(', ', array_fill(0, count($parte), '(?, ?, ?)')), $p);
            }
            return $id;
        });
        if ($id < 1) {
            $this->erro = 'Não foi possível guardar o arquivo (' . \Core\Transacao::ultimoErro() . ').';
            return null;
        }
        $this->julgar($id);
        return $id;
    }

    public static function lote(int $id): ?array
    {
        $l = self::q('SELECT i.*, u.nome AS autor, a.nome AS aplicador, d.nome AS desfez
                        FROM adms_importacoes i
                        LEFT JOIN adms_usuarios u ON u.id = i.usuario_cre
                        LEFT JOIN adms_usuarios a ON a.id = i.aplicada_por
                        LEFT JOIN adms_usuarios d ON d.id = i.desfeita_por
                       WHERE i.id = ? AND i.tipo = \'alunos\'', [$id])->fetch();
        if (!$l) {
            return null;
        }
        $l['cabecalho'] = json_decode((string) $l['cabecalho'], true) ?: [];
        $l['mapa_colunas'] = json_decode((string) $l['mapa_colunas'], true) ?: [];
        return $l;
    }

    public static function lotes(): array
    {
        return self::q('SELECT i.id, i.arquivo_nome, i.situacao, i.total, i.prontas, i.avisos, i.erros, i.puladas, i.aplicadas, i.vinculos, i.created, i.aplicada_em, i.desfeita_em, u.nome AS autor
                          FROM adms_importacoes i LEFT JOIN adms_usuarios u ON u.id = i.usuario_cre
                         WHERE i.tipo = \'alunos\' ORDER BY i.id DESC LIMIT 100')->fetchAll();
    }

    public static function linhas(int $id): array
    {
        $r = self::q('SELECT * FROM adms_importacoes_linhas WHERE importacao_id = ? ORDER BY numero', [$id])->fetchAll();
        foreach ($r as &$l) {
            $l['dados_json'] = (string) ($l['dados'] ?? '');
            $l['mensagens_json'] = (string) ($l['mensagens'] ?? '');
            $l['bruto'] = json_decode((string) $l['bruto'], true) ?: [];
            $l['correcao'] = json_decode((string) ($l['correcao'] ?? ''), true) ?: [];
            $l['dados'] = json_decode((string) ($l['dados'] ?? ''), true) ?: [];
            $l['mensagens'] = json_decode((string) ($l['mensagens'] ?? ''), true) ?: [];
        }
        return $r;
    }

    /** Os valores da linha por campo: o que o de-para tira do arquivo, com a correção da tela por cima. */
    public static function valores(array $lote, array $linha): array
    {
        $v = array_fill_keys(array_keys(self::campos()), '');
        foreach ($lote['mapa_colunas'] as $i => $campo) {
            if ($campo !== '' && isset(self::campos()[$campo])) {
                $v[$campo] = trim((string) ($linha['bruto'][$i] ?? ''));
            }
        }
        foreach ($linha['correcao'] as $campo => $valor) {
            if (isset(self::campos()[$campo])) {
                $v[$campo] = trim((string) $valor);
            }
        }
        return $v;
    }

    public function salvarMapa(int $id, array $mapa): bool
    {
        $lote = self::lote($id);
        if (!$lote || (int) $lote['situacao'] !== self::CONFERINDO) {
            $this->erro = 'Este lote não está mais em conferência.';
            return false;
        }
        $novo = [];
        $usados = [];
        foreach (array_keys($lote['cabecalho']) as $i) {
            $campo = (string) ($mapa[$i] ?? '');
            if ($campo !== '' && !isset(self::campos()[$campo])) {
                $campo = '';
            }
            if ($campo !== '' && isset($usados[$campo])) {
                $this->erro = 'O campo "' . self::campos()[$campo][0] . '" foi escolhido para duas colunas. Cada campo vem de uma coluna só.';
                return false;
            }
            if ($campo !== '') {
                $usados[$campo] = true;
            }
            $novo[$i] = $campo;
        }
        self::q('UPDATE adms_importacoes SET mapa_colunas = ? WHERE id = ? AND situacao = 1', [json_encode($novo), $id]);
        $this->julgar($id);
        return true;
    }

    public function corrigir(int $id, int $numero, array $valores): bool
    {
        $lote = self::lote($id);
        if (!$lote || (int) $lote['situacao'] !== self::CONFERINDO) {
            $this->erro = 'Este lote não está mais em conferência.';
            return false;
        }
        $linha = self::q('SELECT id, bruto, correcao FROM adms_importacoes_linhas WHERE importacao_id = ? AND numero = ?', [$id, $numero])->fetch();
        if (!$linha) {
            $this->erro = 'Linha não encontrada.';
            return false;
        }
        $linha['bruto'] = json_decode((string) $linha['bruto'], true) ?: [];
        $linha['correcao'] = [];
        $doArquivo = self::valores($lote, $linha);
        $correcao = [];
        foreach (array_keys(self::campos()) as $campo) {
            if (!array_key_exists($campo, $valores)) {
                continue;
            }
            $novo = trim((string) $valores[$campo]);
            if ($novo !== $doArquivo[$campo]) {
                $correcao[$campo] = $novo; // só o que difere do arquivo fica como correção
            }
        }
        self::q('UPDATE adms_importacoes_linhas SET correcao = ? WHERE id = ?', [$correcao ? json_encode($correcao, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null, (int) $linha['id']]);
        $this->julgar($id);
        return true;
    }

    // ------------------------------------------------------------------ o julgamento

    /** Julga todas as linhas do lote em conferência (as duplicidades dependem do lote inteiro). */
    public function julgar(int $id): void
    {
        $lote = self::lote($id);
        if (!$lote || (int) $lote['situacao'] !== self::CONFERINDO) {
            return;
        }
        $linhas = self::linhas($id);
        $ref = self::referencias();
        $colunasPresentes = array_flip(array_filter($lote['mapa_colunas']));

        /* primeira passada: os valores e as repetições no arquivo */
        $vals = [];
        $vistos = ['ra' => [], 'cpf' => [], 'email' => []];
        foreach ($linhas as $l) {
            $v = self::valores($lote, $l);
            $vals[$l['numero']] = $v;
            $chaves = ['ra' => preg_replace('/\s+/', '', $v['ra']), 'cpf' => \App\adms\Models\helper\AdmsCpf::digitos($v['cpf']), 'email' => mb_strtolower($v['email'])];
            foreach ($chaves as $k => $c) {
                if ($c !== '') {
                    $vistos[$k][$c][] = (int) $l['numero'];
                }
            }
        }
        /* o banco: quem já existe, pelos RAs, CPFs e e-mails do arquivo */
        $existentesRa = self::existentes('SELECT rm AS chave, rm, nome FROM col_usuarios WHERE rm IN (%s)', array_keys($vistos['ra']));
        $existentesCpf = self::existentes('SELECT i.cpf AS chave, u.rm, u.nome FROM col_usuarios_informacoes i JOIN col_usuarios u ON u.id = i.col_user_id WHERE i.cpf IN (%s)', array_keys($vistos['cpf']));
        $existentesEmail = self::existentes('SELECT LOWER(email) AS chave, rm, nome FROM col_usuarios WHERE LOWER(email) IN (%s)', array_keys($vistos['email']));

        $cont = [self::PRONTA => 0, self::AVISO => 0, self::ERRO => 0, self::PULADA => 0];
        $gravar = []; // as linhas que mudaram: gravadas no fim, numa transação só (Onda 6, Etapa 4: 5000 linhas em segundos)
        $naEscola = \App\adms\Models\helper\AdmsParametro::naEscola(); // Onda 8 (D-67): uma vez, fora das linhas
        if ($naEscola) {
            require_once dirname(__DIR__, 5) . '/core/LoginPortal.php';
        }
        foreach ($linhas as $l) {
            $v = $vals[$l['numero']];
            $erros = [];
            $avisos = [];
            $d = [];
            $num = (int) $l['numero'];

            if (!isset($colunasPresentes['ra']) && !isset($l['correcao']['ra'])) {
                $erros[] = 'O arquivo não tem a coluna do RA (ajuste as colunas).';
            }
            if (!isset($colunasPresentes['nome']) && !isset($l['correcao']['nome'])) {
                $erros[] = 'O arquivo não tem a coluna do nome (ajuste as colunas).';
            }

            /* RA */
            $ra = preg_replace('/\s+/', '', $v['ra']);
            $pulada = false;
            if ($ra === '') {
                $erros[] = 'Falta o RA.';
            } elseif ($naEscola && \VcLoginPortal::comoCpf($v['ra']) !== null) {
                $erros[] = "O número de matrícula ({$v['ra']}) não pode ser um CPF: na escola, o CPF é o login do responsável no portal. Use o número de matrícula da escola.";
            } elseif (!preg_match('/^\d{1,9}$/', $ra)) {
                $erros[] = "RA inválido ({$v['ra']}): só dígitos, até 9.";
            } else {
                $ra = (string) (int) $ra;
                if (count($vistos['ra'][preg_replace('/\s+/', '', $v['ra'])] ?? []) > 1) {
                    $erros[] = 'RA ' . $ra . ' repetido no arquivo (linhas ' . implode(', ', $vistos['ra'][preg_replace('/\s+/', '', $v['ra'])]) . ').';
                } elseif (isset($existentesRa[$ra])) {
                    $pulada = true;
                    $avisos[] = "Já cadastrado: RA {$ra} ({$existentesRa[$ra]['nome']}). A linha é pulada; o cadastro não muda.";
                }
            }
            $d['rm'] = $ra;

            /* nome */
            $d['nome'] = preg_replace('/\s+/u', ' ', $v['nome']);
            if ($d['nome'] === '') {
                $erros[] = 'Falta o nome.';
            }

            /* CPF */
            $cpf = \App\adms\Models\helper\AdmsCpf::digitos($v['cpf']);
            if ($v['cpf'] !== '') {
                if (!\App\adms\Models\helper\AdmsCpf::valido($cpf)) {
                    $erros[] = "CPF inválido ({$v['cpf']}): os dígitos verificadores não conferem.";
                } elseif (count($vistos['cpf'][$cpf] ?? []) > 1) {
                    $erros[] = 'CPF repetido no arquivo (linhas ' . implode(', ', $vistos['cpf'][$cpf]) . ').';
                } elseif (isset($existentesCpf[$cpf]) && (string) $existentesCpf[$cpf]['rm'] !== $ra) {
                    $erros[] = "CPF já cadastrado para outro aluno (RA {$existentesCpf[$cpf]['rm']}, {$existentesCpf[$cpf]['nome']}).";
                }
            }
            $d['cpf'] = $cpf;

            /* e-mail */
            $email = mb_strtolower($v['email']);
            if ($email !== '') {
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $erros[] = "E-mail inválido ({$v['email']}).";
                } elseif (!$naEscola && count($vistos['email'][$email] ?? []) > 1) {
                    /* na escola (Onda 8, D-67) o e-mail não é login: irmãos podem ter o mesmo, no arquivo e no cadastro */
                    $erros[] = 'E-mail repetido no arquivo (linhas ' . implode(', ', $vistos['email'][$email]) . ').';
                } elseif (!$naEscola && isset($existentesEmail[$email]) && (string) $existentesEmail[$email]['rm'] !== $ra) {
                    $erros[] = "E-mail já cadastrado para outro aluno (RA {$existentesEmail[$email]['rm']}).";
                }
            }
            $d['email'] = $email;

            /* nascimento */
            $d['nasc_data'] = '';
            if ($v['nascimento'] !== '') {
                $data = self::data($v['nascimento']);
                if ($data === null) {
                    $erros[] = "Data de nascimento impossível ou em formato desconhecido ({$v['nascimento']}): use dd/mm/aaaa.";
                } else {
                    $d['nasc_data'] = $data;
                }
            }

            /* sexo */
            $d['genero_id'] = '';
            if ($v['sexo'] !== '') {
                $s = self::SEXO[Leitor::achatar($v['sexo'])] ?? null;
                if ($s === null) {
                    $erros[] = "Sexo não reconhecido ({$v['sexo']}): use Feminino, Masculino ou Outros.";
                } else {
                    $d['genero_id'] = $s;
                }
            } else {
                $avisos[] = 'Sem o sexo: o aluno entra na lista "Alunos sem sexo informado", em Alunos (os documentos concordam com ele).';
            }

            /* telefone */
            $d['telefone'] = preg_replace('/\D/', '', $v['telefone']);
            if ($v['telefone'] !== '' && (strlen($d['telefone']) < 8 || strlen($d['telefone']) > 13)) {
                $erros[] = "Telefone inválido ({$v['telefone']}).";
            }

            /* situação */
            $d['col_sits_usuario_id'] = 1;
            if ($v['situacao'] !== '') {
                $s = self::SITUACAO[Leitor::achatar($v['situacao'])] ?? null;
                if ($s === null) {
                    $erros[] = "Situação não reconhecida ({$v['situacao']}): use Ativo ou Inativo.";
                } else {
                    $d['col_sits_usuario_id'] = $s;
                }
            }

            /* o vínculo com o curso */
            $d['vinculo'] = null;
            $temVinculo = $v['curso'] !== '';
            if (!$temVinculo) {
                foreach (array_merge(['grade', 'ano', 'polo', 'modalidade'], isset($v['turma']) ? ['turma'] : []) as $c) {
                    if ($v[$c] !== '') {
                        $erros[] = 'Sem o curso, a coluna ' . self::campos()[$c][0] . ' não serve: informe o curso para vincular, ou deixe-a vazia.';
                        break;
                    }
                }
            } else {
                $vinc = self::vinculo($v, $ref, $erros);
                /* Onda 4, Etapa 2: na escola, a turma da linha (no ano e no segmento do vínculo): o aluno entra nela */
                if ($vinc && ($v['turma'] ?? '') !== '') {
                    $turma = self::turma($v['turma'], $vinc);
                    if ($turma === null) {
                        $erros[] = "Turma não encontrada ({$v['turma']}) em {$vinc['curso']}, {$vinc['ano']}: use a série e o nome, como \"6º ano A\".";
                        $vinc = null;
                    } else {
                        $vinc['turma_id'] = (int) $turma['id'];
                        $vinc['modulo_id'] = (int) $turma['modulo_id'];
                        $vinc['turma'] = $turma['codtur'];
                    }
                }
                $d['vinculo'] = $vinc;
            }

            if ($pulada) {
                /* o aluno já existe e a linha não grava nada: os outros problemas dela não importam (sem isso, reenviar um
                   arquivo corrigido na tela acusaria de novo o que já foi resolvido) */
                $erros = [];
                $avisos = array_slice($avisos, 0, 1);
                $d['vinculo'] = null;
            }
            if ($erros) {
                $res = self::ERRO;
            } elseif ($pulada) {
                $res = self::PULADA;
            } elseif ($avisos) {
                $res = self::AVISO;
            } else {
                $res = self::PRONTA;
            }
            $cont[$res]++;
            $msgs = array_merge(array_map(fn($t) => ['tipo' => 'erro', 'texto' => $t], $erros), array_map(fn($t) => ['tipo' => 'aviso', 'texto' => $t], $avisos));
            $novo = [json_encode($d, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $res, json_encode($msgs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), (int) $l['id']];
            if ($novo[0] !== $l['dados_json'] || $res !== (int) $l['resultado'] || $novo[2] !== $l['mensagens_json']) {
                $gravar[] = $novo;
            }
        }
        \Core\Transacao::executar(function () use ($gravar, $cont, $id) {
            /* 250 linhas por comando: o "INSERT … ON DUPLICATE KEY UPDATE" só atualiza (os ids vêm desta leitura do lote) */
            foreach (array_chunk($gravar, 250) as $parte) {
                $vals = [];
                $p = [];
                foreach ($parte as [$dados, $res, $msgs, $lid]) {
                    $vals[] = '(?, ?, 0, \'\', ?, ?, ?)';
                    array_push($p, $lid, $id, $dados, $res, $msgs);
                }
                self::q('INSERT INTO adms_importacoes_linhas (id, importacao_id, numero, bruto, dados, resultado, mensagens) VALUES ' . implode(', ', $vals)
                    . ' ON DUPLICATE KEY UPDATE dados = VALUES(dados), resultado = VALUES(resultado), mensagens = VALUES(mensagens)', $p);
            }
            self::q('UPDATE adms_importacoes SET prontas = ?, avisos = ?, erros = ?, puladas = ? WHERE id = ?',
                [$cont[self::PRONTA], $cont[self::AVISO], $cont[self::ERRO], $cont[self::PULADA], $id]);
            return true;
        });
    }

    /** chave => ['rm', 'nome'] para as chaves dadas */
    private static function existentes(string $sql, array $chaves): array
    {
        $chaves = array_values(array_filter(array_map('strval', $chaves), fn($c) => $c !== ''));
        if (!$chaves) {
            return [];
        }
        $r = [];
        foreach (array_chunk($chaves, 500) as $parte) {
            $st = self::q(sprintf($sql, implode(',', array_fill(0, count($parte), '?'))), $parte);
            foreach ($st->fetchAll() as $x) {
                $r[(string) $x['chave']] = $x;
            }
        }
        return $r;
    }

    /** As tabelas de referência do vínculo, achatadas. */
    private static function referencias(): array
    {
        $ref = ['cursos' => [], 'polos' => [], 'modalidades' => [], 'anos' => []];
        foreach (self::q('SELECT id, nome, abreviatura, localidade, modalidade_id, situacao FROM rm_cursos')->fetchAll() as $c) {
            $c['grades'] = [];
            $ref['cursos'][(int) $c['id']] = $c;
        }
        foreach (self::q('SELECT id, curso_id, versao, situacao FROM rm_cursos_grades ORDER BY versao')->fetchAll() as $g) {
            if (isset($ref['cursos'][(int) $g['curso_id']])) {
                $ref['cursos'][(int) $g['curso_id']]['grades'][] = $g;
            }
        }
        foreach (self::q('SELECT id, nome FROM rm_polos WHERE situacao = 1')->fetchAll() as $p) {
            $ref['polos'][Leitor::achatar($p['nome'])] = (int) $p['id'];
        }
        foreach (self::q('SELECT id, nome FROM uni_rh_cursos_modalidade WHERE status = 1')->fetchAll() as $m) {
            $ref['modalidades'][Leitor::achatar($m['nome'])] = (int) $m['id'];
        }
        foreach (self::q('SELECT ano FROM adms_periodos_letivos')->fetchAll() as $a) {
            $ref['anos'][(int) $a['ano']] = true;
        }
        return $ref;
    }

    /** O vínculo da linha (curso, grade, módulo, ano, polo, modalidade) ou null com os erros na lista. */
    private static function vinculo(array $v, array $ref, array &$erros): ?array
    {
        $alvo = Leitor::achatar($v['curso']);
        $curso = null;
        foreach ($ref['cursos'] as $c) {
            if ($alvo === Leitor::achatar((string) $c['nome']) || ($c['abreviatura'] !== null && $c['abreviatura'] !== '' && $alvo === Leitor::achatar((string) $c['abreviatura']))
                || (ctype_digit($v['curso']) && (int) $v['curso'] === (int) $c['id'])) {
                $curso = $c;
                break;
            }
        }
        if (!$curso) {
            $erros[] = "Curso não encontrado ({$v['curso']}): use o nome ou a sigla de um curso cadastrado.";
            return null;
        }
        /* uma consulta por curso, não por linha (Onda 6, Etapa 4: 2500 linhas com curso levavam 14 s) */
        static $ativo = [];
        static $poloPadrao = [];
        if (!($ativo[(int) $curso['id']] ??= \App\adms\Models\helper\AdmsInativar::ativo('curso', (int) $curso['id']))) {
            $erros[] = "O curso {$curso['nome']} está inativo e não aceita vínculo novo.";
            return null;
        }
        $abertas = array_values(array_filter($curso['grades'], fn($g) => (int) $g['situacao'] !== 4));
        $grade = null;
        if ($v['grade'] !== '') {
            $num = preg_replace('/\D/', '', $v['grade']);
            foreach ($curso['grades'] as $g) {
                if ($num !== '' && (int) $num === (int) $g['versao']) {
                    $grade = $g;
                }
            }
            if (!$grade) {
                $erros[] = "Grade não encontrada ({$v['grade']}) em {$curso['nome']}.";
                return null;
            }
            if ((int) $grade['situacao'] === 4) {
                $erros[] = "A grade {$grade['versao']} de {$curso['nome']} está desativada e não aceita vínculo novo.";
                return null;
            }
        } elseif (count($abertas) === 1) {
            $grade = $abertas[0];
        } else {
            $erros[] = count($abertas) === 0 ? "{$curso['nome']} não tem grade que aceite vínculo." : "{$curso['nome']} tem " . count($abertas) . ' grades: informe a grade.';
            return null;
        }
        $ano = (int) preg_replace('/\D/', '', $v['ano']);
        if ($v['ano'] === '') {
            $erros[] = 'Para vincular, informe o ano.';
            return null;
        }
        if (!isset($ref['anos'][$ano])) {
            $erros[] = "Ano letivo não cadastrado ({$v['ano']}).";
            return null;
        }
        $polo = '';
        if ($v['polo'] !== '') {
            $polo = $ref['polos'][Leitor::achatar($v['polo'])] ?? null;
            if ($polo === null) {
                $erros[] = "Polo não encontrado ({$v['polo']}).";
                return null;
            }
        } elseif (!($poloPadrao[(int) $curso['localidade']] ??= \App\adms\Models\disciplinas\AdmsOfertaMatriz::poloAtivo((int) $curso['localidade']))) {
            $erros[] = "{$curso['nome']} não tem polo padrão: informe o polo.";
            return null;
        }
        $modalidade = '';
        if ($v['modalidade'] !== '') {
            $a = Leitor::achatar($v['modalidade']);
            $a = self::MODALIDADE_SINONIMOS[$a] ?? $a;
            $modalidade = $ref['modalidades'][$a] ?? null;
            if ($modalidade === null) {
                $erros[] = "Modalidade não reconhecida ({$v['modalidade']}): use " . implode(', ', array_map('ucfirst', array_keys($ref['modalidades']))) . '.';
                return null;
            }
        }
        return ['curso_id' => (int) $curso['id'], 'curso' => $curso['nome'], 'grade_id' => (int) $grade['id'], 'versao' => (int) $grade['versao'],
            'ano' => $ano, 'polo_id' => $polo, 'modalidade_id' => $modalidade];
    }

    /** A turma da linha: a série e o nome ("6º ano A"), ou só o nome se ele for único no segmento e no ano. */
    private static function turma(string $texto, array $vinc): ?array
    {
        $alvo = Leitor::achatar($texto);
        $achadas = [];
        foreach (self::q('SELECT t.id, t.nome, t.modulo_id, m.codnome FROM esc_turmas t INNER JOIN rm_cursos_turma_series m ON m.id = t.modulo_id
                           WHERE t.ano = ? AND t.curso_id = ? AND t.grade_id = ?', [(int) $vinc['ano'], (int) $vinc['curso_id'], (int) $vinc['grade_id']])->fetchAll() as $t) {
            $codtur = \App\adms\Models\escola\AdmsTurmas::codtur((string) $t['codnome'], (string) $t['nome']);
            if ($alvo === Leitor::achatar($codtur) || $alvo === Leitor::achatar((string) $t['nome'])) {
                $achadas[] = $t + ['codtur' => $codtur];
            }
        }
        return count($achadas) === 1 ? $achadas[0] : null;
    }

    /** dd/mm/aaaa, d/m/aaaa, dd-mm-aaaa, dd.mm.aaaa ou aaaa-mm-dd; a data tem de existir e caber numa vida. */
    public static function data(string $t): ?string
    {
        $t = trim($t);
        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $t, $m)) {
            [$a, $mes, $d] = [(int) $m[1], (int) $m[2], (int) $m[3]];
        } elseif (preg_match('/^(\d{1,2})[\/.\-](\d{1,2})[\/.\-](\d{4})$/', $t, $m)) {
            [$d, $mes, $a] = [(int) $m[1], (int) $m[2], (int) $m[3]];
        } else {
            return null;
        }
        if (!checkdate($mes, $d, $a) || $a < 1900 || sprintf('%04d-%02d-%02d', $a, $mes, $d) > date('Y-m-d')) {
            return null;
        }
        return sprintf('%04d-%02d-%02d', $a, $mes, $d);
    }

    /** Cada valor distinto de sexo, situação e modalidade no arquivo, e o que virou (ou "não reconhecido"). */
    public static function deParaValores(array $lote, array $linhas): array
    {
        $ref = null;
        $r = ['sexo' => [], 'situacao' => [], 'modalidade' => []];
        $nomesSexo = [1 => 'Feminino', 2 => 'Masculino', 3 => 'Outros'];
        $nomesSit = [1 => 'Ativo', 2 => 'Inativo'];
        foreach ($linhas as $l) {
            $v = self::valores($lote, $l);
            foreach (array_keys($r) as $campo) {
                $bruto = $v[$campo];
                if ($bruto === '') {
                    continue;
                }
                if (!isset($r[$campo][$bruto])) {
                    $a = Leitor::achatar($bruto);
                    if ($campo === 'sexo') {
                        $vira = isset(self::SEXO[$a]) ? $nomesSexo[self::SEXO[$a]] : null;
                    } elseif ($campo === 'situacao') {
                        $vira = isset(self::SITUACAO[$a]) ? $nomesSit[self::SITUACAO[$a]] : null;
                    } else {
                        $ref = $ref ?? self::q('SELECT id, nome FROM uni_rh_cursos_modalidade WHERE status = 1')->fetchAll();
                        $a = self::MODALIDADE_SINONIMOS[$a] ?? $a;
                        $vira = null;
                        foreach ($ref as $m) {
                            if (Leitor::achatar($m['nome']) === $a) {
                                $vira = $m['nome'];
                            }
                        }
                    }
                    $r[$campo][$bruto] = ['vira' => $vira, 'linhas' => 0];
                }
                $r[$campo][$bruto]['linhas']++;
            }
        }
        return array_filter($r);
    }

    // ------------------------------------------------------------------ aplicar

    /*
     * APLICAR EM PASSOS (Onda 6, Etapa 4; docs/OPERACOES_LONGAS.md). O lote passa a "aplicando" (4) e cada passo grava
     * algumas linhas, até o orçamento de tempo, cada uma na sua transação (o cadastro, o vínculo e, na escola, a turma,
     * pelas mesmas funções da tela) e marcada "aplicada" junto. O "tudo ou nada" continua: se uma linha falha, os passos
     * seguintes desfazem as linhas já gravadas deste lote e o lote volta a "conferindo", com o erro da linha. Só no fim,
     * com tudo gravado, o Moodle é avisado (a fila, em passos; com o envio automático, o que couber nos 6 segundos do
     * último passo). A página fechada retoma: o estado está no banco (as linhas e adms_instalacao_estado).
     */
    public const APLICANDO = 4;
    public const ORCAMENTO = 4.0;
    /** O orçamento de cada passo (a prova, pela linha de comando, aplica "de uma vez" com um orçamento sem fim, para comparar). */
    public static float $orcamento = self::ORCAMENTO;

    private static function chaveLote(int $id): string
    {
        return 'importacao.alunos.' . $id;
    }

    public static function estadoAplicacao(int $id): ?array
    {
        $v = \App\adms\Models\helper\AdmsAmbiente::estado(self::chaveLote($id));
        $e = $v ? json_decode($v, true) : null;
        return is_array($e) ? $e : null;
    }

    private static function gravarAplicacao(int $id, ?array $e): void
    {
        \App\adms\Models\helper\AdmsAmbiente::gravarEstado(self::chaveLote($id), $e === null ? null : json_encode($e, JSON_UNESCAPED_UNICODE));
    }

    /** O começo: as mesmas conferências de antes; o lote passa a "aplicando". @return array o estado público, ou ['erro' => …] */
    public function iniciarAplicacao(int $id, int $forma, string $dataIngresso, bool $aceitarErros): array
    {
        $lote = self::lote($id);
        if ($lote && (int) $lote['situacao'] === self::APLICANDO && self::estadoAplicacao($id)) {
            return self::publicoAplicacao(self::estadoAplicacao($id)); // já em andamento: continua
        }
        if (!$this->conferirAplicacao($id, $forma, $dataIngresso, $aceitarErros, $comVinculo)) {
            return ['erro' => $this->erro];
        }
        $total = (int) self::q('SELECT COUNT(*) FROM adms_importacoes_linhas WHERE importacao_id = ? AND resultado IN (1, 2)', [$id])->fetchColumn();
        /* dois cliques não aplicam duas vezes: só um muda o lote de "conferindo" para "aplicando" */
        $st = self::q('UPDATE adms_importacoes SET situacao = 4, aplicada_por = ?, forma_ingresso_id = ?, data_ingresso = ?, aplicadas = 0, vinculos = 0 WHERE id = ? AND situacao = 1',
            [(int) $_SESSION['colUsuario_id'], $comVinculo ? $forma : null, $comVinculo ? $dataIngresso : null, $id]);
        if ($st->rowCount() !== 1) {
            $this->erro = 'Este lote já foi aplicado ou desfeito.';
            return ['erro' => $this->erro];
        }
        $e = ['fase' => 'aplicando', 'total' => $total, 'feitos' => 0, 'aplicadas' => 0, 'vinculos' => 0, 'desfazer' => 0, 'desfeitas' => 0,
            'moodleAte' => 0, 'moodleIds' => [], 'falha' => null, 'forma' => $forma, 'data' => $dataIngresso, 'comVinculo' => $comVinculo];
        self::gravarAplicacao($id, $e);
        return self::publicoAplicacao($e);
    }

    /** Um passo: gravar, desfazer (depois de uma falha) ou avisar o Moodle, até o orçamento de tempo. */
    public function passoAplicacao(int $id): array
    {
        $pdo = self::db();
        if ((int) $pdo->query("SELECT GET_LOCK('vcostaedu_importacao_{$id}', 0)")->fetchColumn() !== 1) {
            return ['ocupado' => true];
        }
        $msgAntes = $_SESSION['msg'] ?? null;
        \App\adms\Models\moodle\AdmsMoodle::segurar(true); // nada vai ao Moodle linha a linha: só no fim, com tudo gravado
        try {
            $e = self::estadoAplicacao($id);
            $lote = self::lote($id);
            if (!$e || !$lote || (int) $lote['situacao'] !== self::APLICANDO) {
                return ['erro' => 'Não há aplicação em andamento neste lote.'];
            }
            $t0 = microtime(true);
            $ainda = fn() => microtime(true) - $t0 < self::$orcamento;
            if ($e['fase'] === 'aplicando') {
                /* a partir da última linha feita (o índice do lote e do número): sem isso, cada linha relia as já aplicadas */
                $prox = self::db()->prepare('SELECT * FROM adms_importacoes_linhas WHERE importacao_id = ? AND numero > ? AND resultado IN (1, 2) ORDER BY numero LIMIT 1');
                while ($ainda()) {
                    $prox->execute([$id, (int) ($e['ate'] ?? 0)]);
                    $l = $prox->fetch(\PDO::FETCH_ASSOC);
                    $prox->closeCursor();
                    if (!$l) {
                        $e['fase'] = 'moodle';
                        break;
                    }
                    $l['dados'] = json_decode((string) ($l['dados'] ?? ''), true) ?: [];
                    $r = $this->gravarLinha($l, (int) $e['forma'], (string) $e['data']);
                    if ($r !== true) {
                        $e['falha'] = ['numero' => (int) $l['numero'], 'motivo' => $r];
                        $e['fase'] = 'desfazendo';
                        $e['desfazer'] = (int) self::q('SELECT COUNT(*) FROM adms_importacoes_linhas WHERE importacao_id = ? AND resultado = 5', [$id])->fetchColumn();
                        break;
                    }
                    $e['feitos']++;
                    $e['ate'] = (int) $l['numero'];
                    $e['aplicadas']++;
                    $e['vinculos'] += !empty($l['dados']['vinculo']) ? 1 : 0;
                }
                self::gravarAplicacao($id, $e);
            }
            if ($e['fase'] === 'desfazendo') {
                /* em blocos de 200 alunos, cada bloco numa transação (a retirada e a linha de volta a "pronta") */
                $prox = self::db()->prepare('SELECT l.id, l.usuario_id, u.rm FROM adms_importacoes_linhas l LEFT JOIN col_usuarios u ON u.id = l.usuario_id
                                              WHERE l.importacao_id = ? AND l.resultado = 5 ORDER BY l.numero DESC LIMIT 200');
                while ($ainda()) {
                    $prox->execute([$id]);
                    $bloco = $prox->fetchAll(\PDO::FETCH_ASSOC);
                    if (!$bloco) {
                        return $this->terminarComFalha($id, $e);
                    }
                    \Core\Transacao::executar(function () use ($bloco) {
                        $comAluno = array_values(array_filter($bloco, fn($a) => $a['usuario_id'] && $a['rm'] !== null));
                        if ($comAluno) {
                            self::retirarAlunos(array_map(fn($a) => (int) $a['usuario_id'], $comAluno), array_map(fn($a) => (string) $a['rm'], $comAluno));
                        }
                        $ids = array_map(fn($a) => (int) $a['id'], $bloco);
                        self::q('UPDATE adms_importacoes_linhas SET resultado = 1, usuario_id = NULL, matricula_curso_id = NULL WHERE id IN (' . implode(',', $ids) . ')');
                        return true;
                    });
                    $e['desfeitas'] += count($bloco);
                    self::gravarAplicacao($id, $e);
                }
                return self::publicoAplicacao($e);
            }
            if ($e['fase'] === 'moodle') {
                if ($this->avisarMoodle($id, $e, $ainda)) {
                    return $this->terminarAplicacao($id, $e);
                }
                self::gravarAplicacao($id, $e);
            }
            return self::publicoAplicacao($e);
        } finally {
            \App\adms\Models\moodle\AdmsMoodle::segurar(false);
            if ($msgAntes === null) {
                unset($_SESSION['msg']);
            } else {
                $_SESSION['msg'] = $msgAntes;
            }
            $pdo->query("SELECT RELEASE_LOCK('vcostaedu_importacao_{$id}')");
        }
    }

    /** Uma linha, numa transação: o cadastro, o vínculo, a turma e a marca de "aplicada". @return true|string */
    private function gravarLinha(array $l, int $forma, string $dataIngresso)
    {
        $ok = \Core\Transacao::executar(function () use ($l, $forma, $dataIngresso) {
            $d = $l['dados'];
            $cad = new \App\adms\Models\academicos\AdmsCadastrarUsuarioAcademico();
            $cad->cadUserAcademico([
                'rm' => $d['rm'], 'nome' => $d['nome'], 'cpf' => $d['cpf'], 'email' => $d['email'], 'nasc_data' => $d['nasc_data'],
                'genero_id' => $d['genero_id'], 'telefone' => $d['telefone'], 'col_sits_usuario_id' => $d['col_sits_usuario_id'],
            ], ['sexo_pendente' => true, 'sem_sessao' => true]);
            if (!$cad->getResultado()) {
                \Core\Transacao::falhar("linha {$l['numero']} (RA {$d['rm']}): " . implode(' ', $cad->getErros()));
            }
            $alunoId = (int) $cad->getIdUsuario();
            $mc = null;
            if (!empty($d['vinculo'])) {
                $vin = $d['vinculo'];
                /* na escola, com a turma, o aluno fica na série dela */
                $modulo = !empty($vin['modulo_id']) ? (int) $vin['modulo_id'] : \App\adms\Models\helper\AdmsModuloInterno::garantir((int) $vin['grade_id']);
                $r = (new \App\adms\Models\vinculo\AdmsVincularAluno())->vincular($alunoId, [
                    'ano' => $vin['ano'], 'modulo_id' => $modulo, 'forma_ingresso_id' => $forma, 'data_ingresso' => $dataIngresso,
                    'polo_id' => (string) $vin['polo_id'], 'modalidade_id' => (string) $vin['modalidade_id'],
                ]);
                if (!$r) {
                    \Core\Transacao::falhar("linha {$l['numero']} (RA {$d['rm']}): " . trim(html_entity_decode(strip_tags(str_replace('Fechar', '', (string) ($_SESSION['msg'] ?? 'o vínculo não foi gravado'))))));
                }
                $mc = (int) $r;
                if (!empty($vin['turma_id'])) {
                    $t = \App\adms\Models\escola\AdmsTurmaAlunos::entrar((int) $vin['turma_id'], $alunoId);
                    if ($t !== true) {
                        \Core\Transacao::falhar("linha {$l['numero']} (RA {$d['rm']}): {$t}");
                    }
                }
            }
            self::q('UPDATE adms_importacoes_linhas SET resultado = 5, usuario_id = ?, matricula_curso_id = ? WHERE id = ? AND resultado IN (1, 2)', [$alunoId, $mc, (int) $l['id']]);
            return true;
        });
        return $ok ? true : \Core\Transacao::ultimoErro();
    }

    /** A fila do Moodle, em passos: o aluno e as disciplinas de cada linha aplicada. @return bool true quando acabou */
    private function avisarMoodle(int $id, array &$e, callable $ainda): bool
    {
        $M = \App\adms\Models\moodle\AdmsMoodle::class;
        if (!$M::disponivel() || !$M::conectado()) {
            return true;
        }
        $prox = self::db()->prepare('SELECT l.numero, l.usuario_id, u.rm FROM adms_importacoes_linhas l JOIN col_usuarios u ON u.id = l.usuario_id
                                      WHERE l.importacao_id = ? AND l.resultado = 5 AND l.numero > ? ORDER BY l.numero LIMIT 50');
        $ofertas = self::db()->prepare('SELECT DISTINCT rm_prof_mat_id FROM portal_disciplinas_alunos WHERE rm = ?');
        while ($ainda()) {
            $prox->execute([$id, (int) $e['moodleAte']]);
            $bloco = $prox->fetchAll(\PDO::FETCH_ASSOC);
            if (!$bloco) {
                return true;
            }
            foreach ($bloco as $a) {
                $ids = [$M::enfileirar('aluno', (int) $a['usuario_id'], null)];
                $ofertas->execute([(string) $a['rm']]);
                foreach ($ofertas->fetchAll(\PDO::FETCH_COLUMN) as $of) {
                    $ids[] = $M::enfileirar('enturmar', (int) $a['usuario_id'], (int) $of);
                }
                if (count($e['moodleIds']) < 300) {
                    array_push($e['moodleIds'], ...$ids);
                }
                $e['moodleAte'] = (int) $a['numero'];
            }
        }
        return false;
    }

    /** Tudo gravado: o lote "aplicado", o registro, o envio automático do que couber, e o estado sai do banco. */
    private function terminarAplicacao(int $id, array $e): array
    {
        /* as contagens pelas linhas (o estado pode ter ficado um passo atrás, se a página caiu entre a linha e o estado) */
        $c = self::q('SELECT COUNT(*) AS a, COUNT(matricula_curso_id) AS v FROM adms_importacoes_linhas WHERE importacao_id = ? AND resultado = 5', [$id])->fetch();
        $e['aplicadas'] = (int) $c['a'];
        $e['vinculos'] = (int) $c['v'];
        self::q('UPDATE adms_importacoes SET situacao = 2, aplicada_em = NOW(), aplicadas = ?, vinculos = ? WHERE id = ? AND situacao = 4', [$e['aplicadas'], $e['vinculos'], $id]);
        (new \App\adms\Models\helper\AdmsSalvarLog())->salvarLogs(480, 200, 1, "Importação de alunos: lote {$id} aplicado");
        $M = \App\adms\Models\moodle\AdmsMoodle::class;
        if ($e['moodleIds'] && (($M::conexao()['envio'] ?? 'automatico') === 'automatico')) {
            $t0 = microtime(true);
            foreach ($e['moodleIds'] as $mid) {
                if (microtime(true) - $t0 >= $M::ORCAMENTO) {
                    break; // o resto fica na fila: Envios ao Moodle → Enviar tudo o que falta
                }
                $M::enviar((int) $mid);
            }
        }
        self::gravarAplicacao($id, null);
        $e['fase'] = 'pronto';
        return self::publicoAplicacao($e);
    }

    /** A falha de uma linha, depois de desfeitas as já gravadas: o lote volta a "conferindo", com o erro da linha. */
    private function terminarComFalha(int $id, array $e): array
    {
        self::q('UPDATE adms_importacoes SET situacao = 1, aplicada_por = NULL, aplicadas = 0, vinculos = 0 WHERE id = ? AND situacao = 4', [$id]);
        self::gravarAplicacao($id, null);
        $this->julgar($id);
        $this->erro = 'Nada foi gravado: ' . ($e['falha']['motivo'] ?? 'uma linha falhou') . '.';
        return ['erro' => $this->erro, 'desfeitas' => (int) $e['desfeitas']];
    }

    /** O estado para a janela de progresso. */
    public static function publicoAplicacao(?array $e): array
    {
        if (!$e) {
            return ['nada' => true];
        }
        $total = max(1, (int) $e['total']);
        if ($e['fase'] === 'pronto') {
            return ['passo' => $total, 'total' => $total, 'concluida' => true, 'aplicadas' => (int) $e['aplicadas'], 'vinculos' => (int) $e['vinculos'], 'grupoAtual' => 'Pronto'];
        }
        if ($e['fase'] === 'desfazendo') {
            return ['passo' => (int) $e['feitos'], 'total' => $total, 'concluida' => false,
                'grupoAtual' => 'a linha ' . (int) ($e['falha']['numero'] ?? 0) . ' falhou: desfazendo as já gravadas (' . (int) $e['desfeitas'] . ' de ' . (int) $e['desfazer'] . ')'];
        }
        if ($e['fase'] === 'moodle') {
            return ['passo' => (int) $e['feitos'], 'total' => $total, 'concluida' => false, 'grupoAtual' => 'avisando o Moodle'];
        }
        return ['passo' => (int) $e['feitos'], 'total' => $total, 'concluida' => false, 'grupoAtual' => 'linha ' . min((int) $e['feitos'] + 1, $total) . ' de ' . $total];
    }

    /** As conferências de antes de aplicar (as mesmas da aplicação de uma vez). */
    private function conferirAplicacao(int $id, int $forma, string $dataIngresso, bool $aceitarErros, ?bool &$comVinculo = null): bool
    {
        $comVinculo = false;
        $lote = self::lote($id);
        if (!$lote || (int) $lote['situacao'] !== self::CONFERINDO) {
            $this->erro = 'Este lote já foi aplicado ou desfeito.';
            return false;
        }
        $this->julgar($id);
        $lote = self::lote($id);
        if ((int) $lote['prontas'] + (int) $lote['avisos'] === 0) {
            $this->erro = 'Nenhuma linha pronta para gravar.';
            return false;
        }
        if ((int) $lote['erros'] > 0 && !$aceitarErros) {
            $this->erro = "Há {$lote['erros']} linha(s) com erro. Corrija-as, ou marque \"Aplicar sem as linhas com erro\".";
            return false;
        }
        $comVinculo = (bool) self::q("SELECT COUNT(*) FROM adms_importacoes_linhas WHERE importacao_id = ? AND resultado IN (1, 2) AND dados LIKE '%\"vinculo\":{%'", [$id])->fetchColumn();
        if ($comVinculo) {
            if ((int) self::q('SELECT COUNT(*) FROM rm_matricula_tipos WHERE id = ?', [$forma])->fetchColumn() !== 1) {
                $this->erro = 'Escolha a forma de ingresso dos vínculos.';
                return false;
            }
            if (!\DateTime::createFromFormat('!Y-m-d', $dataIngresso) || \DateTime::createFromFormat('!Y-m-d', $dataIngresso)->format('Y-m-d') !== $dataIngresso) {
                $this->erro = 'Informe a data de ingresso dos vínculos.';
                return false;
            }
        }
        return true;
    }

    /**
     * Tira do sistema os alunos criados pelo lote (desfazer, e a falha no meio da aplicação), por conjunto: um comando por
     * tabela para até 500 alunos. Dentro de uma transação.
     */
    private static function retirarAlunos(array $uids, array $rms): void
    {
        $por = function (string $sql, array $chaves): void {
            foreach (array_chunk(array_values($chaves), 500) as $parte) {
                self::q(sprintf($sql, implode(',', array_fill(0, count($parte), '?'))), $parte);
            }
        };
        $por('DELETE FROM portal_disciplinas_alunos WHERE rm IN (%s)', $rms); // enturmação sem lançamento
        $por("DELETE FROM rm_disciplina_atividade_notas WHERE (nota IS NULL OR nota = '') AND rm IN (%s)", $rms);
        $por('DELETE FROM rm_disciplina_faltas WHERE faltas = 0 AND faltas_compensadas = 0 AND rm IN (%s)', $rms);
        $por('DELETE FROM esc_turma_alunos WHERE usuario_id IN (%s)', $uids); // a escola: a entrada na turma
        $por('DELETE FROM rm_matriculas_ensino WHERE usuario_id IN (%s)', $uids);
        $por('DELETE FROM rm_matriculas_curso WHERE usuario_id IN (%s)', $uids);
        $por('DELETE FROM col_usuarios_tokens WHERE col_usuario_id IN (%s)', $uids);
        $por('DELETE FROM col_novidades_vistas WHERE col_usuario_id IN (%s)', $uids);
        $por('DELETE FROM col_usuarios_enderecos WHERE usuario_id IN (%s)', $uids);
        $por('DELETE FROM col_usuarios_informacoes WHERE col_user_id IN (%s)', $uids);
        $por('DELETE FROM col_usuarios WHERE col_niveis_acesso_id = 1 AND id IN (%s)', $uids);
    }

    /** chave => o número, por partes de 500 chaves (a consulta tem %s no lugar da lista e devolve chave e n) */
    private static function contagens(string $sql, array $chaves): array
    {
        $r = [];
        foreach (array_chunk(array_values($chaves), 500) as $parte) {
            foreach (self::q(sprintf($sql, implode(',', array_fill(0, count($parte), '?'))), $parte)->fetchAll() as $x) {
                $r[(string) $x['chave']] = (int) $x['n'];
            }
        }
        return $r;
    }

    /**
     * O que impede tirar cada aluno do lote: [usuario_id => ['rm', 'nome', 'motivos' => []]]. Por conjunto (Onda 6, Etapa 4:
     * uma consulta por motivo para o lote inteiro, não uma por aluno — 5000 alunos em segundos).
     */
    public static function impedimentosDoLote(int $id): array
    {
        $alunos = self::q('SELECT l.usuario_id, l.matricula_curso_id, u.rm, u.nome, u.senha FROM adms_importacoes_linhas l JOIN col_usuarios u ON u.id = l.usuario_id
                            WHERE l.importacao_id = ? AND l.resultado = 5', [$id])->fetchAll();
        if (!$alunos) {
            return [];
        }
        $rms = array_map(fn($a) => (string) $a['rm'], $alunos);
        $uids = array_map(fn($a) => (int) $a['usuario_id'], $alunos);
        $porRm = [
            'notas' => self::contagens("SELECT rm AS chave, COUNT(*) AS n FROM rm_disciplina_atividade_notas WHERE nota IS NOT NULL AND nota <> '' AND rm IN (%s) GROUP BY rm", $rms),
            'faltas' => self::contagens('SELECT rm AS chave, COUNT(*) AS n FROM rm_disciplina_faltas WHERE (faltas > 0 OR faltas_compensadas > 0) AND rm IN (%s) GROUP BY rm', $rms),
            'chamada' => self::contagens('SELECT rm AS chave, COUNT(*) AS n FROM rm_oferta_presencas WHERE rm IN (%s) GROUP BY rm', $rms),
            'compensadas' => self::contagens('SELECT rm AS chave, COUNT(*) AS n FROM rm_faltas_compensacoes WHERE rm IN (%s) GROUP BY rm', $rms),
            'media' => self::contagens('SELECT rm AS chave, COUNT(*) AS n FROM rm_disciplina_media_final WHERE rm IN (%s) GROUP BY rm', $rms),
            'fechamento' => self::contagens('SELECT rm AS chave, COUNT(*) AS n FROM rm_media_fechamento_resultados WHERE rm IN (%s) GROUP BY rm', $rms),
            'auditoria' => self::contagens('SELECT rm AS chave, COUNT(*) AS n FROM rm_auditoria_lancamentos WHERE rm IN (%s) GROUP BY rm', $rms),
        ];
        $porUid = [
            'documentos' => self::contagens('SELECT aluno_id AS chave, COUNT(*) AS n FROM rm_historico_emissoes WHERE aluno_id IN (%s) GROUP BY aluno_id', $uids),
            'tcc' => self::contagens('SELECT mc.usuario_id AS chave, COUNT(*) AS n FROM rm_tcc t JOIN rm_matriculas_curso mc ON mc.id = t.matricula_curso_id WHERE mc.usuario_id IN (%s) GROUP BY mc.usuario_id', $uids),
            'vinculos' => self::contagens('SELECT usuario_id AS chave, COUNT(*) AS n FROM rm_matriculas_curso WHERE usuario_id IN (%s) GROUP BY usuario_id', $uids),
        ];
        $r = [];
        foreach ($alunos as $a) {
            $rm = (string) $a['rm'];
            $uid = (string) (int) $a['usuario_id'];
            $m = [];
            if ($n = $porRm['notas'][$rm] ?? 0) {
                $m[] = "{$n} nota(s) lançada(s)";
            }
            if ($n = $porRm['faltas'][$rm] ?? 0) {
                $m[] = "faltas lançadas em {$n} disciplina(s)";
            }
            if ($n = $porRm['chamada'][$rm] ?? 0) {
                $m[] = "{$n} registro(s) de chamada";
            }
            if ($porRm['compensadas'][$rm] ?? 0) {
                $m[] = 'faltas compensadas';
            }
            if (($porRm['media'][$rm] ?? 0) || ($porRm['fechamento'][$rm] ?? 0)) {
                $m[] = 'média final calculada';
            }
            if ($n = $porUid['documentos'][$uid] ?? 0) {
                $m[] = "{$n} documento(s) emitido(s)";
            }
            if ($porUid['tcc'][$uid] ?? 0) {
                $m[] = 'TCC registrado';
            }
            if ($porRm['auditoria'][$rm] ?? 0) {
                $m[] = 'lançamentos na auditoria';
            }
            if ((string) $a['senha'] !== '-inexistente') {
                $m[] = 'já criou a senha do portal';
            }
            /* um vínculo além do que o lote criou (antes: COUNT(*) dos vínculos com id diferente do do lote) */
            if (($porUid['vinculos'][$uid] ?? 0) > ($a['matricula_curso_id'] ? 1 : 0)) {
                $m[] = 'tem vínculo feito depois da importação';
            }
            if ($m) {
                $r[(int) $uid] = ['rm' => $rm, 'nome' => $a['nome'], 'motivos' => $m];
            }
        }
        return $r;
    }

    public function desfazer(int $id, string $motivo): bool
    {
        $this->impedimentos = [];
        if (!\App\adms\Models\helper\AdmsNivel::podeSensivel()) { // a rota já confere (ação sensível); aqui, a regra
            $this->erro = 'Desfazer uma importação é ação sensível: só o Administrador e a Supervisão de secretaria.';
            return false;
        }
        $lote = self::lote($id);
        if (!$lote || (int) $lote['situacao'] !== self::APLICADA) {
            $this->erro = 'Só um lote aplicado pode ser desfeito.';
            return false;
        }
        $motivo = trim($motivo);
        if (mb_strlen($motivo) < 10) {
            $this->erro = 'Escreva o motivo (pelo menos 10 caracteres): ele fica registrado no lote.';
            return false;
        }
        $imp = self::impedimentosDoLote($id);
        if ($imp) {
            $this->impedimentos = array_values($imp);
            $this->erro = count($imp) . ' aluno(s) do lote já têm uso no sistema; nada foi desfeito.';
            return false;
        }
        $ok = \Core\Transacao::executar(function () use ($id, $motivo) {
            $st = self::q('UPDATE adms_importacoes SET situacao = 3, desfeita_por = ?, desfeita_em = NOW(), desfeita_motivo = ? WHERE id = ? AND situacao = 2',
                [(int) $_SESSION['colUsuario_id'], mb_substr($motivo, 0, 500), $id]);
            if ($st->rowCount() !== 1) {
                \Core\Transacao::falhar('o lote já foi desfeito');
            }
            if (self::impedimentosDoLote($id)) { // de novo, dentro da transação
                \Core\Transacao::falhar('um aluno do lote passou a ter uso no sistema');
            }
            $alunos = self::q('SELECT l.usuario_id, u.rm FROM adms_importacoes_linhas l JOIN col_usuarios u ON u.id = l.usuario_id WHERE l.importacao_id = ? AND l.resultado = 5', [$id])->fetchAll();
            self::retirarAlunos(array_map(fn($a) => (int) $a['usuario_id'], $alunos), array_map(fn($a) => (string) $a['rm'], $alunos));
            return true;
        });
        if (!$ok) {
            $this->erro = 'Nada foi desfeito: ' . \Core\Transacao::ultimoErro() . '.';
            return false;
        }
        (new \App\adms\Models\helper\AdmsSalvarLog())->salvarLogs(481, 200, 1, "Importação de alunos: lote {$id} desfeito ({$motivo})");
        return true;
    }

    /** O CSV de exemplo: as colunas na ordem, com duas linhas fictícias. */
    public static function modelo(): string
    {
        $linhas = [
            array_map(fn($c) => $c[0], self::campos()),
            ['100101', 'Maria Exemplo da Silva', '', 'maria.exemplo@exemplo.invalid', '15/03/1990', 'Feminino', '(11) 90000-0001', 'Ativo', '', '', '', '', ''],
            ['100102', 'João Exemplo Souza', '', '', '02/11/1988', 'Masculino', '', 'Ativo', '', '', '', '', ''],
        ];
        $h = fopen('php://memory', 'r+');
        foreach ($linhas as $l) {
            fputcsv($h, array_values($l), ';', '"', '');
        }
        rewind($h);
        $csv = stream_get_contents($h);
        fclose($h);
        return "\xEF\xBB\xBF" . $csv;
    }
}
