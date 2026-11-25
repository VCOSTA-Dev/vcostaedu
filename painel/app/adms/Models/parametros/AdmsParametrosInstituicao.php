<?php

namespace App\adms\Models\parametros;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * Configurações da instituição (adms_parametros), E2b.
 *
 * portal.login.campo (ra | cpf | email): TROCAR o modo exige que todo aluno
 * ATIVO tenha o campo escolhido preenchido e sem repetição. Se não tiver, a
 * troca é RECUSADA com a lista do que resolver.
 * portal.senha.link_horas: inteiro de 1 a 720.
 */
class AdmsParametrosInstituicao
{
    private $resultado;
    private array $pendencias = [];

    public function getResultado()
    {
        return $this->resultado;
    }

    public function getPendencias(): array
    {
        return $this->pendencias;
    }

    /** E1b (D-355): as situações da matrícula com o rótulo no documento (a tela de parâmetros edita o rótulo). */
    public static function situacoesMatricula(): array
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT id, nome, rotulo_documento FROM rm_matriculas_situacao ORDER BY id");
        return $ler->getResultado() ?: [];
    }

    public function listar(): array
    {
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead("SELECT id, chave, valor, descricao, grupo, editavel, modified FROM adms_parametros ORDER BY grupo, id");
        return $ler->getResultado() ?: [];
    }

    public function salvar(array $dados): void
    {
        $usuario = (int) ($_SESSION['colUsuario_id'] ?? 0);
        $erros = [];

        $campo = (string) ($dados['portal_login_campo'] ?? '');
        /* Onda 8 (D-67): na escola o aluno entra sempre com a matrícula; o formulário não traz o campo, e o parâmetro
           guardado fica como está (vale de novo só se a instalação deixar de ser escola) */
        $naEscola = \App\adms\Models\helper\AdmsParametro::naEscola();
        if ($naEscola) {
            $campo = (string) (\App\adms\Models\helper\AdmsParametro::ler('portal.login.campo', 'ra') ?? 'ra');
        }
        $horas = (string) ($dados['portal_senha_link_horas'] ?? '');
        /* E4: notas e frequência. Vírgula ou ponto decimal. */
        /* 1.13.0 (D-398): casas e média mínima são editadas na Regra de média (que espelha os dois parâmetros); sem os
           campos no formulário, ficam como estão */
        $atuais = \App\adms\Models\helper\AdmsParametro::regrasNotas();
        $casas = (string) ($dados['nota_casas'] ?? $atuais['casas']);
        $mediaMin = str_replace(',', '.', trim((string) ($dados['nota_media_minima'] ?? $atuais['mediaMinima'])));
        $mediaMinVeio = array_key_exists('nota_media_minima', $dados);
        $freqMin = str_replace(',', '.', trim((string) ($dados['frequencia_minima'] ?? '')));
        /* E5: média parcial no portal (1 = mostra, 0 = esconde). Sem o campo, fica como está. */
        $parcial = (string) ($dados['portal_media_parcial'] ?? (\App\adms\Models\helper\AdmsParametro::mostrarMediaParcial() ? '1' : '0'));
        /* E8c (D-268): organizar a matriz em módulos (1 = sim, 0 = não). Sem o campo, fica como está. */
        /* Correção da escola, Parte 4 (B7; D-103): sem o campo (na escola ele não aparece: as séries são sempre os
           módulos), fica o valor GRAVADO — antes, o "usa módulos" forçado da escola virava "Sim" no banco */
        $modulos = (string) ($dados['matriz_modulos'] ?? (\App\adms\Models\helper\AdmsParametro::ler('matriz.modulos', '0') ?? '0'));

        if (!in_array($campo, ['ra', 'cpf', 'email'], true)) {
            $erros[] = 'Escolha RA, CPF ou e-mail como campo de login do portal.';
        }
        if (!preg_match('/^\d{1,3}$/', $horas) || (int) $horas < 1 || (int) $horas > 720) {
            $erros[] = 'A validade do link deve ser um número de horas entre 1 e 720.';
        }
        if (!in_array($casas, ['1', '2'], true)) {
            $erros[] = 'As casas decimais da média devem ser 1 ou 2.';
        }
        if (!preg_match('/^\d{1,2}(\.\d{1,2})?$/', $mediaMin) || (float) $mediaMin > 10) {
            $erros[] = 'A média mínima deve ser um número de 0 a 10, com até 2 casas.';
        }
        if (!preg_match('/^\d{1,3}(\.\d{1,2})?$/', $freqMin) || (float) $freqMin > 100) {
            $erros[] = 'A frequência mínima deve ser uma porcentagem de 0 a 100, com até 2 casas.';
        }
        if (!in_array($parcial, ['0', '1'], true)) {
            $erros[] = 'Escolha Sim ou Não em "Mostrar média parcial ao aluno".';
        }
        if (!in_array($modulos, ['0', '1'], true)) {
            $erros[] = 'Escolha Sim ou Não em "Organizar a matriz em módulos".';
        }
        /* E8 (D-237): limites por IP do limite de tentativas. Sem o campo, fica como está. */
        $limAtual = \App\adms\Models\helper\AdmsParametro::limitesIp();
        $lim = [];
        $rotLim = ['login' => 'Login: falhas por hora', 'esqueci' => 'Esqueci a senha: pedidos por hora', 'verificacao' => 'Verificação pública: consultas a cada 10 minutos'];
        foreach (\App\adms\Models\helper\AdmsParametro::LIMITES_IP as $k => $d) {
            $v = trim((string) ($dados['limite_ip_' . $k] ?? $limAtual[$k]));
            if (!preg_match('/^\d{1,4}$/', $v) || (int) $v < $d['min'] || (int) $v > $d['max']) {
                $erros[] = "O limite por IP \"{$rotLim[$k]}\" deve ser um número inteiro de {$d['min']} a {$d['max']}.";
            }
            $lim[$k] = (string) (int) $v;
        }
        /* Onda 8, correção, segunda leva, 2.7 (D-93): o limite de chamadas por minuto de cada chave da API. Sem o campo,
           fica como está; o valor que já estava guardado passa mesmo fora da faixa (só o valor novo é conferido) */
        $apiAtual = (string) \App\adms\Models\helper\AdmsParametro::apiLimitePorMinuto();
        $fxApi = \App\adms\Models\helper\AdmsParametro::API_LIMITE;
        $apiLimite = trim((string) ($dados['api_limite_por_minuto'] ?? $apiAtual));
        if ($apiLimite !== $apiAtual && (!preg_match('/^\d{1,5}$/', $apiLimite) || (int) $apiLimite < $fxApi['min'] || (int) $apiLimite > $fxApi['max'])) {
            $erros[] = "O limite de chamadas por minuto de cada chave da API deve ser um número inteiro de {$fxApi['min']} a {$fxApi['max']}.";
        }
        $apiLimite = (string) (int) $apiLimite;
        /* E1b (D-355, D-357) e E2 (D-361): textos dos documentos. Sem o campo, fica como está. */
        $tf = \App\adms\Models\helper\AdmsParametro::textosFrequencia();
        $tn = \App\adms\Models\helper\AdmsParametro::formasNeutras();
        $textos = [];
        foreach ([
            'frequencia.sigla_suficiente' => ['frequencia_sigla_suficiente', $tf['siglaSuficiente'], 10, 'A sigla da frequência suficiente'],
            'frequencia.rotulo_suficiente' => ['frequencia_rotulo_suficiente', $tf['rotuloSuficiente'], 60, 'O rótulo da frequência suficiente'],
            'frequencia.sigla_insuficiente' => ['frequencia_sigla_insuficiente', $tf['siglaInsuficiente'], 10, 'A sigla da frequência insuficiente'],
            'frequencia.rotulo_insuficiente' => ['frequencia_rotulo_insuficiente', $tf['rotuloInsuficiente'], 60, 'O rótulo da frequência insuficiente'],
            'aluno.rotulo_neutro' => ['aluno_rotulo_neutro', $tn['rotulo'], 60, 'A forma neutra de ALUNA/ALUNO'],
            'aluno.matriculado_neutro' => ['aluno_matriculado_neutro', $tn['matriculado'], 60, 'A forma neutra de matriculada/matriculado'],
            'aluno.artigo_neutro' => ['aluno_artigo_neutro', $tn['artigo'], 20, 'A forma neutra de a/o'],
        ] as $chave => [$campoTexto, $valorAtual, $max, $rotulo]) {
            /* E5 (D-379): nomes próprios — antes, $campo (o campo de LOGIN do portal) era reaproveitado aqui e gravado errado */
            $t = trim((string) preg_replace('/\s+/u', ' ', (string) ($dados[$campoTexto] ?? $valorAtual)));
            if ($t === '' || mb_strlen($t) > $max) {
                $erros[] = "{$rotulo} deve ter de 1 a {$max} caracteres.";
            }
            $textos[$chave] = $t;
        }
        /* E5 (D-314): como a média final do aluno é calculada nos documentos. Sem o campo, fica como está. */
        $mediaFinal = (string) ($dados['documento_media_final'] ?? \App\adms\Models\helper\AdmsParametro::mediaFinalModo());
        if (!isset(\App\adms\Models\helper\AdmsParametro::MEDIA_FINAL_MODOS[$mediaFinal])) {
            $erros[] = 'Escolha como a média final do aluno é calculada (aritmética ou ponderada pela carga horária).';
        }
        $rotulosSituacao = [];
        foreach (self::situacoesMatricula() as $sm) {
            $campoSit = 'situacao_rotulo_' . (int) $sm['id'];
            if (!array_key_exists($campoSit, $dados)) {
                continue;
            }
            $t = trim((string) preg_replace('/\s+/u', ' ', (string) $dados[$campoSit]));
            if ($t === '' || mb_strlen($t) > 60) {
                $erros[] = "O rótulo da situação \"{$sm['nome']}\" no documento deve ter de 1 a 60 caracteres.";
            }
            $rotulosSituacao[(int) $sm['id']] = $t;
        }
        if ($erros) {
            $this->aviso('danger', '<b>Nada foi salvo:</b><ul class="mb-0"><li>' . implode('</li><li>', array_map(fn($e) => htmlspecialchars($e, ENT_QUOTES, 'UTF-8'), $erros)) . '</li></ul>');
            $this->resultado = false;
            return;
        }

        $atual = \App\adms\Models\helper\AdmsParametro::campoLoginPortal();
        if (!$naEscola && $campo !== $atual) {
            $this->pendencias = $this->pendenciasDoCampo($campo);
            if ($this->pendencias) {
                $rot = \App\adms\Models\helper\AdmsParametro::rotuloCampoLogin($campo);
                $this->aviso('danger', "<b>A troca do login do portal para {$rot} foi RECUSADA.</b> "
                    . count($this->pendencias) . " aluno(s) ativo(s) não podem entrar com {$rot}. Resolva a lista abaixo (editando os alunos) e tente de novo. Nada foi salvo.");
                $this->resultado = false;
                return;
            }
        }

        /* E1b (D-345): parâmetros e rótulos das situações (duas tabelas) numa transação só: ou tudo, ou nada */
        $T = \Core\Transacao::class;
        $ok = $T::executar(function () use ($T, $usuario, $campo, $horas, $casas, $mediaMin, $mediaMinVeio, $freqMin, $parcial, $modulos, $lim, $textos, $rotulosSituacao, $mediaFinal, $apiLimite) {
            $P = \App\adms\Models\helper\AdmsParametro::class;
            /* D-103 (B7): só grava o que mudou — o valor igual ao gravado não é regravado (nem com outro formato) */
            $grava = function (string $chave, string $valor) use ($T, $P, $usuario) {
                if ((string) $P::ler($chave, '') === $valor) {
                    $P::confirmar($chave, (int) $usuario); // Fechamento: o padrão confirmado conta para a fase "As regras"
                    return;
                }
                if (!$P::gravar($chave, $valor, $usuario)) {
                    $T::falhar("parâmetro {$chave}");
                }
            };
            $grava('portal.login.campo', $campo);
            $grava('portal.senha.link_horas', (string) (int) $horas);
            $grava('nota.casas', $casas);
            /* D-103 (B7): a média mínima só muda se veio no formulário (ela é editada na Regra de média): "7.0" não vira "7.00" */
            if ($mediaMinVeio) {
                $grava('nota.media_minima', number_format((float) $mediaMin, 2, '.', ''));
            }
            $grava('frequencia.minima', rtrim(rtrim(number_format((float) $freqMin, 2, '.', ''), '0'), '.'));
            $grava('portal.media_parcial', $parcial);
            $grava('matriz.modulos', $modulos);
            foreach ($P::LIMITES_IP as $k => $d) {
                $grava($d['chave'], $lim[$k]);
            }
            foreach ($textos as $chave => $valor) {
                $grava($chave, $valor);
            }
            $grava('documento.media_final', $mediaFinal);
            $grava('api.limite_por_minuto', $apiLimite); // 2.7 (D-93)
            foreach ($rotulosSituacao as $sid => $rot) {
                $up = new \App\adms\Models\helper\AdmsUpdate();
                $up->exeUpdate('rm_matriculas_situacao', ['rotulo_documento' => $rot], 'WHERE id = :id', "id={$sid}");
                if ($up->getResultado() === null) {
                    $T::falhar('rótulo da situação');
                }
            }
            return true;
        });
        if (!$ok) {
            $this->aviso('danger', '<b>Nada foi salvo:</b> não foi possível gravar as configurações (' . htmlspecialchars(\Core\Transacao::ultimoErro(), ENT_QUOTES, 'UTF-8') . '). Tente de novo.');
            $this->resultado = false;
            return;
        }
        $log = new \App\adms\Models\helper\AdmsSalvarLog();
        $log->salvarLogs(365, 200, 3, "Configurações da instituição salvas: portal.login.campo={$campo}; portal.senha.link_horas=" . (int) $horas . "; nota.casas={$casas}; nota.media_minima={$mediaMin}; frequencia.minima={$freqMin}; portal.media_parcial={$parcial}; matriz.modulos={$modulos}; limites por IP login={$lim['login']}, esqueci={$lim['esqueci']}, verificacao={$lim['verificacao']}; api.limite_por_minuto={$apiLimite}");
        $this->aviso('success', 'Configurações salvas. O login do portal agora usa <b>' . \App\adms\Models\helper\AdmsParametro::rotuloCampoLogin($campo) . '</b>.');
        $this->resultado = true;
    }

    /**
     * Alunos ATIVOS que não conseguiriam entrar com o campo escolhido:
     * campo vazio, ou valor repetido entre alunos ativos.
     */
    public function pendenciasDoCampo(string $campo): array
    {
        $expr = [
            'ra' => "CAST(u.rm AS CHAR)",
            'cpf' => "i.cpf",
            'email' => "u.email",
        ][$campo];
        $ler = new \App\adms\Models\helper\AdmsRead();
        $ler->fullRead(
            "SELECT u.id, u.rm, u.nome, {$expr} AS valor,
                    CASE WHEN {$expr} IS NULL OR {$expr} = '' THEN 'sem o campo'
                         ELSE 'valor repetido' END AS problema
               FROM col_usuarios u
               LEFT JOIN col_usuarios_informacoes i ON i.col_user_id = u.id
              WHERE u.col_niveis_acesso_id = 1 AND u.col_sits_usuario_id = 1
                AND ( {$expr} IS NULL OR {$expr} = ''
                      OR {$expr} IN (SELECT v FROM (
                            SELECT {$expr} AS v FROM col_usuarios u
                              LEFT JOIN col_usuarios_informacoes i ON i.col_user_id = u.id
                             WHERE u.col_niveis_acesso_id = 1 AND u.col_sits_usuario_id = 1
                               AND {$expr} IS NOT NULL AND {$expr} <> ''
                             GROUP BY {$expr} HAVING COUNT(*) > 1) d) )
              ORDER BY u.nome LIMIT :limit",
            "limit=500"
        );
        return $ler->getResultado() ?: [];
    }

    private function aviso(string $tipo, string $html): void
    {
        $_SESSION['msg'] = "<div class='alert alert-{$tipo} alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>{$html}</div>";
    }
}
