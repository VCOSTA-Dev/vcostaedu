<?php

namespace App\adms\Models\banco;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

use PDO;
use RuntimeException;
use Throwable;

/**
 * RUNNER DE MIGRATIONS — controle de versão aplicada.
 *
 * Herdado de outro projeto da casa (`app/adms/Models/banco/AdmsMigrations.php`), que
 * por sua vez veio do sistema escolar em Tabler. O levantamento mediu esse runner inteiro
 * (§1.1 do `docs/LEVANTAMENTO_INSTALADOR.md`) e a conclusão foi que ele
 * transfere quase sem mudança. O que mudou está listado no fim deste bloco, com
 * o motivo de cada item — herdar sem dizer o que se mexeu é o começo de duas
 * bases que divergem sem ninguém perceber.
 *
 * ============================ O QUE ELE IMPEDE ==============================
 *
 * Migrations aplicadas à mão funcionam enquanto há um ambiente e uma pessoa.
 * Com dois ambientes surgem três problemas, e o terceiro é o caro:
 *
 *   1. não se sabe o que já foi aplicado onde;
 *   2. reaplicar uma migration de ALTER falha e assusta;
 *   3. alguém EDITA uma migration já aplicada.
 *
 * O terceiro é a origem clássica do "funciona aqui e não lá". Por isso o hash:
 * migration aplicada é imutável. Mudou o conteúdo, o runner PARA e não aplica
 * mais nada. A correção é sempre uma migration NOVA.
 *
 * =========================== SOBRE TRANSAÇÃO ================================
 *
 * As migrations NÃO rodam em transação, e isso é limite do MySQL, não escolha:
 * DDL causa commit implícito. Uma migration que falha na terceira instrução
 * deixa as duas primeiras aplicadas e NÃO é registrada como aplicada. Por isso
 * as migrations desta base usam `CREATE TABLE IF NOT EXISTS` e
 * `INSERT ... WHERE NOT EXISTS` onde dá — para serem reexecutáveis.
 *
 * Isto está documentado aqui, e não escondido, porque quem for depurar uma
 * migration parcial precisa saber que o estado parcial é possível. E é essa a
 * razão de a tela do instalador, quando uma migration falha, mandar apagar o
 * banco e recomeçar em vez de "tentar de novo".
 *
 * ========================= SOBRE A CONEXÃO ==================================
 *
 * Não usa a camada de conexão da aplicação, e a D11 explica: aquela conexão é
 * `static` por requisição, o que é certo para transação e errado para quem
 * precisa reconectar. O runner monta o próprio PDO a partir das MESMAS
 * constantes — a fonte continua sendo o `Config.php`, que é o único lugar onde
 * a credencial mora.
 *
 * ==================== O QUE MUDOU EM RELAÇÃO À ORIGEM =======================
 *
 * 1. **Namespace e guarda** — os deste projeto.
 *
 * 2. **A porta entra no DSN.** O `Config.php` gerado pelo nosso instalador
 *    define `PORTA`, porque a tela 2 pergunta. A origem não tinha o campo e o
 *    DSN dela não tinha a porta. Sem esta mudança, uma instalação em porta não
 *    padrão passaria pelo instalador e falharia no runner — com um erro de
 *    conexão que não apontaria para a causa. A constante é lida com queda para
 *    3306 quando não existe, para que um `Config.php` antigo continue servindo.
 *
 * 3. **`resultados()`, ao lado de `mensagens()`.** A origem só devolve frases
 *    prontas ("aplicada X (N ms)"), que servem ao terminal. A tela 5 do
 *    instalador precisa dos mesmos dados em colunas. Fazer a tela recortar a
 *    frase seria transformar texto de log em contrato — e o primeiro ajuste de
 *    redação quebraria a tela. `mensagens()` continua idêntica, para o CLI.
 *
 * 4. **`aplicar()` devolve o que aplicou mesmo quando estoura.** Na origem, uma
 *    falha no meio lança e o que já rodou se perde para quem chamou. Aqui a
 *    exceção continua sendo lançada — a tela precisa parar —, mas os resultados
 *    já acumulados ficam disponíveis, porque a mensagem da §4.7 do levantamento
 *    promete dizer **quais** foram aplicadas antes da que falhou.
 *
 * ========= O QUE MUDOU AO VIR PARA ESTA PASTA (VCOSTAEDU, modo X) ===========
 *
 * 5. **A guarda é a do painel** (`VCOSTAEDU`): o runner mora em `painel/`, ao
 *    lado dos outros models, e é carregado pelo instalador e pelo `bin/`.
 *
 * 6. **Migration que exige privilégio.** Um arquivo que começa com
 *    `-- vcostaedu:exige-privilegio TRIGGER` e falha por falta desse privilégio
 *    (erros 1142 e 1419 do MySQL) fica ADIADA: não é registrada, o runner avisa
 *    e segue com as próximas. É o caso dos gatilhos de imutabilidade da origem
 *    em hospedagem compartilhada: o sistema funciona sem eles, protegido pelo
 *    código. Rodar `aplicar` de novo depois de obter o privilégio a aplica.
 *
 * O que NÃO mudou, de propósito: o `sha256` do arquivo, as quatro situações, a
 * recusa por divergência, a ordenação natural pelo prefixo numérico, e a
 * criação da tabela de controle pelo próprio runner.
 */
final class AdmsMigrations
{
    private PDO $conexao;
    private string $diretorio;

    /** @var string[] mensagens do último processamento (o CLI lê daqui) */
    private array $mensagens = [];

    /** @var array<int, array{arquivo:string, versao:string, duracao_ms:int, situacao:string}> */
    private array $resultados = [];

    /** Onda 3, Etapa 2: as que gravam texto maior que a coluna e só passam sem o modo estrito (medido: medir_estrito.php). */
    private const ANTERIORES_AO_MODO_ESTRITO = ['0002_conteudo.sql', '0005_perfis_produto_aberto.sql'];

    public function __construct(string $diretorio, ?PDO $conexao = null)
    {
        if (!is_dir($diretorio)) {
            throw new RuntimeException("Diretório de migrations não encontrado: {$diretorio}");
        }

        $this->diretorio = rtrim($diretorio, '/\\');
        $this->conexao = $conexao ?? self::conexaoPadrao();
    }

    private static function conexaoPadrao(): PDO
    {
        foreach (['HOST', 'USER', 'PASS', 'DBNAME'] as $constante) {
            if (!defined($constante)) {
                throw new RuntimeException(
                    "Constante {$constante} não definida. O bootstrap precisa carregar o Config.php."
                );
            }
        }

        /* Ver a mudança 2 do cabeçalho. A queda para 3306 mantém compatível um
           Config.php que não tenha a constante. */
        $porta = defined('PORTA') ? (int) constant('PORTA') : 3306;

        require_once dirname(__DIR__, 5) . '/core/Banco.php'; // Onda 3, Etapa 2: o modo de SQL do sistema
        return \VcBanco::abrir(
            'mysql:host=' . HOST . ';port=' . $porta . ';dbname=' . DBNAME . ';charset=utf8mb4',
            USER,
            PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    }

    // ------------------------------------------------------------------
    // Estado
    // ------------------------------------------------------------------

    /**
     * Onda 6, Etapa 3 (DUVIDAS 240): o resumo (sha256) de antes de os comentários das duas primeiras etapas perderem os
     * nomes de origem. Só estes dois arquivos, e só o resumo anterior exato: uma instalação que aplicou a 0001 e a 0002
     * com o texto antigo continua com elas APLICADAS; qualquer outra mudança em qualquer etapa continua DIVERGENTE.
     */
    private const RESUMOS_ANTERIORES = [
        '0001_estrutura.sql' => '17cfb855007570076ebc90abe027e7c8dd144949ebed6fd012197935ece8c1d3',
        '0002_conteudo.sql'  => '7b8aa2a6675f22036cf1cd68ac1c0dd1725f609172f9f6d4a06c13344f1f342c',
    ];

    /**
     * Situação de cada migration do diretório.
     *
     *   PENDENTE     está no disco e não no banco
     *   APLICADA     está nos dois, com o mesmo hash
     *   DIVERGENTE   está nos dois, com hash diferente — o arquivo foi editado
     *   ORFA         está no banco e não no disco — arquivo apagado ou renomeado
     */
    public function estado(): array
    {
        $this->garantirTabela();

        $noDisco = $this->doDisco();
        $noBanco = $this->doBanco();
        $estado = [];

        foreach ($noDisco as $versao => $info) {
            $registro = $noBanco[$versao] ?? null;

            if ($registro === null) {
                $situacao = 'PENDENTE';
            } elseif ($registro['hash'] === $info['hash'] || (self::RESUMOS_ANTERIORES[$info['arquivo']] ?? '') === $registro['hash']) {
                $situacao = 'APLICADA';
            } else {
                $situacao = 'DIVERGENTE';
            }

            $estado[$versao] = [
                'versao' => $versao,
                'arquivo' => $info['arquivo'],
                'situacao' => $situacao,
                'hash_disco' => $info['hash'],
                'hash_banco' => $registro['hash'] ?? null,
                'aplicada_em' => $registro['aplicada_em'] ?? null,
            ];
        }

        foreach ($noBanco as $versao => $registro) {
            if (isset($estado[$versao])) {
                continue;
            }

            $estado[$versao] = [
                'versao' => $versao,
                'arquivo' => $registro['arquivo'],
                'situacao' => 'ORFA',
                'hash_disco' => '',
                'hash_banco' => $registro['hash'],
                'aplicada_em' => $registro['aplicada_em'],
            ];
        }

        ksort($estado, SORT_NATURAL);

        return $estado;
    }

    /** @return array[] só as divergentes */
    public function divergentes(): array
    {
        return array_filter($this->estado(), fn($m) => $m['situacao'] === 'DIVERGENTE');
    }

    /** @return array[] só as pendentes, em ordem */
    public function pendentes(): array
    {
        return array_filter($this->estado(), fn($m) => $m['situacao'] === 'PENDENTE');
    }

    // ------------------------------------------------------------------
    // Aplicação
    // ------------------------------------------------------------------

    /**
     * Aplica o que falta, em ordem.
     *
     * RECUSA COMEÇAR se houver qualquer divergência. Aplicar migrations novas
     * sobre um estado que já não é o esperado produz um banco que ninguém
     * consegue reproduzir.
     *
     * Quando uma migration falha, a exceção sobe — mas `resultados()` já tem o
     * que rodou antes dela. Ver a mudança 4 do cabeçalho.
     */
    public function aplicar(bool $simular = false): int
    {
        $this->mensagens = [];
        $this->resultados = [];
        $this->garantirTabela();

        /* A TRAVA. Ver `travar()`. Simulação não altera nada e não precisa dela. */
        if (!$simular && !$this->travar()) {
            throw new RuntimeException(
                'Já há uma atualização em andamento neste banco de dados. '
                . 'Espere ela terminar antes de tentar de novo. Nada foi aplicado.'
            );
        }

        try {
            return $this->aplicarPendentes($simular);
        } finally {
            if (!$simular) {
                $this->destravar();
            }
        }
    }

    /**
     * Onda 3, Etapa 11: UMA etapa por chamada — a tela "Atualizar o sistema" em passos, cada um numa requisição curta.
     * A mesma trava, a mesma recusa das divergentes e o mesmo adiamento da etapa que exige privilégio (a adiada não conta
     * como passo: segue para a próxima). Devolve o resultado da etapa aplicada, ou null quando não há o que aplicar.
     *
     * @return array{arquivo:string, versao:string, duracao_ms:int, situacao:string}|null
     */
    public function aplicarProxima(): ?array
    {
        $this->mensagens = [];
        $this->resultados = [];
        $this->garantirTabela();

        if (!$this->travar()) {
            throw new RuntimeException(
                'Já há uma atualização em andamento neste banco de dados. '
                . 'Espere ela terminar antes de tentar de novo. Nada foi aplicado.'
            );
        }

        try {
            $this->recusarDivergentes();
            foreach ($this->pendentes() as $migration) {
                $tratada = $this->adiarPorPrivilegio($migration);
                if ($tratada === false) {
                    continue; // adiada: o sistema funciona sem ela
                }
                if ($tratada === null) {
                    $this->aplicarUma($migration);
                }
                $ultima = end($this->resultados);
                return $ultima ?: null;
            }
            return null;
        } finally {
            $this->destravar();
        }
    }

    /**
     * D-132 (1.103.0, C9): as migrations do disco com número repetido, número => os arquivos (vazio = nenhuma).
     *
     * @return array<string, list<string>>
     */
    public function repetidas(): array
    {
        $this->repetidas = [];
        $this->doDisco();
        foreach ($this->repetidas as $n => $arquivos) {
            $this->repetidas[$n] = array_values(array_unique($arquivos));
            sort($this->repetidas[$n]);
        }

        return $this->repetidas;
    }

    /** D-132: com número repetido no disco, para antes de aplicar qualquer coisa e diz quais são. */
    private function recusarRepetidas(): void
    {
        $repetidas = $this->repetidas();

        if (!empty($repetidas)) {
            $lista = [];
            foreach ($repetidas as $n => $arquivos) {
                $lista[] = $n . ' (' . implode(' e ', $arquivos) . ')';
            }

            throw new RuntimeException(
                'Há migrations com o mesmo número: ' . implode('; ', $lista) . '. '
                . 'Cada migration precisa de um número só dela (uma das duas seria ignorada). '
                . 'Nada foi aplicado.'
            );
        }
    }

    private function recusarDivergentes(): void
    {
        $this->recusarRepetidas();

        $divergentes = $this->divergentes();

        if (!empty($divergentes)) {
            $lista = implode(', ', array_column($divergentes, 'arquivo'));

            throw new RuntimeException(
                'Migrations já aplicadas foram alteradas no disco: ' . $lista . '. '
                . 'Migration aplicada é imutável — a correção é sempre uma migration '
                . 'NOVA. Nada foi aplicado.'
            );
        }
    }

    private function aplicarPendentes(bool $simular): int
    {
        $this->recusarDivergentes();

        $pendentes = $this->pendentes();

        if (empty($pendentes)) {
            $this->mensagens[] = 'Nada a aplicar: o banco está em dia.';

            return 0;
        }

        $aplicadas = 0;

        foreach ($pendentes as $migration) {
            if (!$simular) {
                $tratada = $this->adiarPorPrivilegio($migration);

                if ($tratada !== null) {
                    $aplicadas += $tratada ? 1 : 0;
                    continue;
                }
            }

            if ($simular) {
                $this->mensagens[] = "[simulação] aplicaria {$migration['arquivo']}";
                $this->resultados[] = [
                    'arquivo' => $migration['arquivo'],
                    'versao' => $migration['versao'],
                    'duracao_ms' => 0,
                    'situacao' => 'SIMULADA',
                ];
                $aplicadas++;
                continue;
            }

            $this->aplicarUma($migration);
            $aplicadas++;
        }

        return $aplicadas;
    }

    /** @return string[] */
    public function mensagens(): array
    {
        return $this->mensagens;
    }

    /**
     * O mesmo, em colunas. A tela 5 do instalador lê daqui.
     *
     * @return array<int, array{arquivo:string, versao:string, duracao_ms:int, situacao:string}>
     */
    public function resultados(): array
    {
        return $this->resultados;
    }

    // ------------------------------------------------------------------
    // A trava
    // ------------------------------------------------------------------

    /** O nome da trava. Por BANCO, e não global: dois sistemas no mesmo servidor não se estorvam. */
    private function nomeDaTrava(): string
    {
        $banco = defined('DBNAME') ? (string) constant('DBNAME') : 'vcostaedu';

        /* O MySQL limita o nome a 64 caracteres, e um nome de banco longo
           truncado poderia colidir com outro. O hash resolve os dois. */
        return 'vcostaedu_migrar_' . substr(sha1($banco), 0, 32);
    }

    /**
     * IMPEDE DUAS ATUALIZAÇÕES AO MESMO TEMPO.
     *
     * ============================== O QUE FOI MEDIDO ============================
     *
     * Antes desta trava, dois `aplicar` simultâneos foram medidos assim: o
     * primeiro aplicou as oito etapas; o segundo **rodou o SQL de novo** e só
     * então morreu, com `Duplicate entry '0001' for key 'PRIMARY'` ao tentar
     * registrar o que o outro já havia registrado.
     *
     * O banco terminou correto — mas **por acidente**, e o acidente tem nome: as
     * migrations desta base são reexecutáveis (`CREATE TABLE IF NOT EXISTS`,
     * `INSERT ... WHERE NOT EXISTS`). Uma migration futura com `ALTER TABLE ...
     * ADD COLUMN` ou `UPDATE` sem guarda seria aplicada DUAS VEZES, e o segundo
     * `ALTER` falharia no meio deixando estado parcial — exatamente o desfecho
     * que a §4.7 do levantamento descreve como o mais caro.
     *
     * E a mensagem que a segunda pessoa recebia era um erro de chave duplicada:
     * verdade técnica que não diz nada a quem está atualizando o sistema.
     *
     * ========================= POR QUE `GET_LOCK` DO MYSQL =====================
     *
     * Porque a trava precisa valer entre PROCESSOS que não se conhecem — a tela
     * do navegador e o comando de linha rodam em processos diferentes, e agora
     * as duas portas existem. Um arquivo de lock no disco não serve quando o
     * sistema roda em mais de uma máquina contra o mesmo banco; uma tabela de
     * controle exigiria limpar sozinha quando o processo morre.
     *
     * `GET_LOCK` resolve os dois: é do servidor de banco, que é o recurso
     * compartilhado, e o MySQL a solta sozinho quando a conexão cai — inclusive
     * se o PHP morrer de timeout no meio.
     *
     * Espera ZERO segundos de propósito: quem chegou depois recebe uma resposta
     * imediata dizendo que há outra atualização em curso. Ficar pendurado numa
     * tela sem explicação é pior que uma recusa clara.
     */
    private function travar(): bool
    {
        $comando = $this->conexao->prepare('SELECT GET_LOCK(:nome, 0) AS obtida');
        $comando->execute([':nome' => $this->nomeDaTrava()]);
        $linha = $comando->fetch(PDO::FETCH_ASSOC);

        /* `GET_LOCK` devolve 1 (obtida), 0 (ocupada) ou NULL (erro). NULL não
           pode virar "pode seguir": na dúvida, não aplica. */
        return isset($linha['obtida']) && (int) $linha['obtida'] === 1;
    }

    private function destravar(): void
    {
        try {
            $comando = $this->conexao->prepare('SELECT RELEASE_LOCK(:nome)');
            $comando->execute([':nome' => $this->nomeDaTrava()]);
        } catch (Throwable $e) {
            /* A conexão vai fechar no fim da requisição e o MySQL solta a trava
               sozinho. Falhar aqui não pode derrubar uma aplicação que deu certo. */
            error_log('[vcostaedu][migrations] falha ao soltar a trava: ' . $e->getMessage());
        }
    }

    // ------------------------------------------------------------------
    // Interno
    // ------------------------------------------------------------------

    private function aplicarUma(array $migration): void
    {
        $caminho = $this->diretorio . DIRECTORY_SEPARATOR . $migration['arquivo'];
        $sql = (string) file_get_contents($caminho);
        $inicio = microtime(true);

        /* Onda 3, Etapa 2: as migrations escritas antes do modo estrito, que gravam um texto maior que a coluna (o resumo
           de duas versões), são aplicadas como sempre foram — sem STRICT_TRANS_TABLES, só nelas; a 0014 alarga a coluna e
           devolve o texto inteiro. Uma migration aplicada não muda (o hash), então a exceção fica aqui, pelo nome. */
        $modoAntes = null;
        if (in_array($migration['arquivo'], self::ANTERIORES_AO_MODO_ESTRITO, true)) {
            $modoAntes = (string) $this->conexao->query('SELECT @@SESSION.sql_mode')->fetchColumn();
            $semEstrito = implode(',', array_diff(explode(',', $modoAntes), ['STRICT_TRANS_TABLES', 'STRICT_ALL_TABLES']));
            $this->conexao->exec("SET SESSION sql_mode = " . $this->conexao->quote($semEstrito));
        }

        try {
            /* Onda 6, Etapa 3 (DUVIDAS 238): cada comando do arquivo é lido até o fim, inclusive o resultado de uma
               CONSULTA (SELECT) — antes, o exec() deixava esse resultado aberto na conexão e a gravação seguinte (ou o
               registro da etapa) falhava ("comandos fora de ordem", o erro 2014). Um comando que falha no meio do arquivo
               também aparece aqui, e não só o primeiro. */
            $comando = $this->conexao->query($sql);
            if ($comando !== false) {
                do {
                    if ($comando->columnCount() > 0) {
                        $comando->fetchAll();
                    }
                } while ($comando->nextRowset());
                $comando->closeCursor();
            }
        } catch (Throwable $e) {
            throw new RuntimeException(
                "Falha ao aplicar {$migration['arquivo']}: " . $e->getMessage()
                . ' ATENÇÃO: como DDL não é transacional, parte da migration pode '
                . 'ter sido aplicada. Confira o estado antes de tentar de novo.'
            );
        } finally {
            if ($modoAntes !== null) {
                $this->conexao->exec("SET SESSION sql_mode = " . $this->conexao->quote($modoAntes));
            }
        }

        $duracao = (int) round((microtime(true) - $inicio) * 1000);

        $this->registrar($migration['versao'], $migration['arquivo'], $migration['hash_disco'], $duracao);

        $this->mensagens[] = sprintf('aplicada %s (%d ms)', $migration['arquivo'], $duracao);
        $this->resultados[] = [
            'arquivo' => $migration['arquivo'],
            'versao' => $migration['versao'],
            'duracao_ms' => $duracao,
            'situacao' => 'APLICADA',
        ];
    }

    /** O privilégio que a migration declara exigir, na primeira linha (mudança 6). */
    private function privilegioExigido(array $migration): string
    {
        $caminho = $this->diretorio . DIRECTORY_SEPARATOR . $migration['arquivo'];
        $arquivo = @fopen($caminho, 'r');
        $primeira = $arquivo ? (string) fgets($arquivo) : '';
        if ($arquivo) {
            fclose($arquivo);
        }

        return preg_match('/^--\s*vcostaedu:exige-privilegio\s+([A-Z ]+)\s*$/', $primeira, $m) ? trim($m[1]) : '';
    }

    /**
     * Mudança 6: tenta a migration que exige privilégio; sem ele, ADIA (não registra) e o runner segue.
     * Devolve null quando a migration não exige privilégio (segue o caminho normal), true quando foi
     * aplicada e false quando ficou adiada.
     */
    private function adiarPorPrivilegio(array $migration): ?bool
    {
        $privilegio = $this->privilegioExigido($migration);

        if ($privilegio === '') {
            return null;
        }

        try {
            $this->aplicarUma($migration);

            return true;
        } catch (Throwable $e) {
            $texto = $e->getMessage();
            $semPrivilegio = str_contains($texto, '1142') || str_contains($texto, '1419')
                || stripos($texto, 'command denied') !== false;

            if (!$semPrivilegio) {
                throw $e;
            }

            error_log('[vcostaedu][migrations] ' . $migration['arquivo'] . ' adiada: ' . $texto);
            $this->mensagens[] = "ADIADA {$migration['arquivo']}: o usuário do banco não tem o privilégio {$privilegio}. "
                . 'O sistema funciona sem ela; peça o privilégio e rode "php bin/migrar.php aplicar" de novo.';
            $this->resultados[] = [
                'arquivo' => $migration['arquivo'],
                'versao' => $migration['versao'],
                'duracao_ms' => 0,
                'situacao' => 'ADIADA',
            ];
        }

        return false;
    }

    private function registrar(string $versao, string $arquivo, string $hash, int $duracao): void
    {
        $comando = $this->conexao->prepare(
            'INSERT INTO adms_migrations (versao, arquivo, hash, duracao_ms)
             VALUES (:v, :a, :h, :d)'
        );

        $comando->execute([':v' => $versao, ':a' => $arquivo, ':h' => $hash, ':d' => $duracao]);
    }

    /** @var array<string, list<string>> D-132: número => os arquivos com esse número (preenchido por doDisco) */
    private array $repetidas = [];

    /** @return array<string, array{arquivo:string, hash:string}> */
    private function doDisco(): array
    {
        $lista = [];

        foreach (glob($this->diretorio . '/*.sql') ?: [] as $caminho) {
            $arquivo = basename($caminho);

            /* A versão é o prefixo numérico: "0004_estrutura.sql" -> "0004".
               Arquivo sem o prefixo é IGNORADO em silêncio, e isso é herdado de
               propósito: é como o `database/manual/` do projeto de referência mantém .sql
               fora do runner. Quem criar migration sem o prefixo não vai vê-la
               no `status` — está dito aqui porque o sintoma é confuso. */
            if (!preg_match('/^(\d+)_/', $arquivo, $encontrado)) {
                continue;
            }

            /* D-132 (1.103.0, C9): dois arquivos com o mesmo número — um deles seria ignorado em silêncio (a versão é a
               chave). Guardados aqui; recusarRepetidas() para tudo antes de aplicar qualquer coisa. */
            if (isset($lista[$encontrado[1]])) {
                $this->repetidas[$encontrado[1]][] = $lista[$encontrado[1]]['arquivo'];
                $this->repetidas[$encontrado[1]][] = $arquivo;
            }

            $lista[$encontrado[1]] = [
                'arquivo' => $arquivo,
                'hash' => hash_file('sha256', $caminho),
            ];
        }

        ksort($lista, SORT_NATURAL);

        return $lista;
    }

    /** @return array<string, array{arquivo:string, hash:string, aplicada_em:string}> */
    private function doBanco(): array
    {
        $linhas = $this->conexao
            ->query('SELECT versao, arquivo, hash, aplicada_em FROM adms_migrations')
            ->fetchAll(PDO::FETCH_ASSOC);

        $lista = [];

        foreach ($linhas as $linha) {
            $lista[$linha['versao']] = [
                'arquivo' => $linha['arquivo'],
                'hash' => $linha['hash'],
                'aplicada_em' => $linha['aplicada_em'],
            ];
        }

        return $lista;
    }

    /**
     * A TABELA DE CONTROLE É CRIADA AQUI, E NÃO POR UMA MIGRATION.
     *
     * É um ovo-e-galinha: o runner precisa CONSULTAR a tabela para saber o que
     * já rodou, então ela não pode depender de uma migration que ele ainda não
     * sabe se deve aplicar.
     *
     * A consequência prática, e é ela que faz a instalação do zero funcionar:
     * com banco vazio, o runner cria esta tabela sozinho e em seguida aplica a
     * 0001. Não existe estado em que ele precise de algo que ainda não pode
     * rodar.
     *
     * E a consequência que obriga cuidado: NENHUMA migration deste projeto pode
     * criar `adms_migrations`. Se alguma criar, as duas definições passam a
     * divergir, e o desencontro só aparece na segunda instalação.
     *
     * Charset e collation declarados aqui também, pela mesma razão das
     * migrations: a declaração vence o padrão do banco, e é isso que faz a régua
     * de charset da tela 3 ser aviso e não recusa.
     */
    private function garantirTabela(): void
    {
        $this->conexao->exec(
            'CREATE TABLE IF NOT EXISTS `adms_migrations` (
                `versao`      VARCHAR(20)  NOT NULL,
                `arquivo`     VARCHAR(255) NOT NULL,
                `hash`        CHAR(64)     NOT NULL,
                `aplicada_em` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `duracao_ms`  INT UNSIGNED NOT NULL DEFAULT 0,
                PRIMARY KEY (`versao`),
                UNIQUE KEY `uk_adms_migrations_arquivo` (`arquivo`)
            ) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci'
        );
    }
}
