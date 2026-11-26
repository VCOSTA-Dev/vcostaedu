<?php

/**
 * O MODO DE SQL DO SISTEMA (Onda 3, Etapa 2) — num ponto só.
 *
 * O código herdado foi escrito com o banco sem o modo estrito, e os servidores atuais vêm com ele ligado. Para que o
 * sistema se comporte igual em qualquer servidor, TODA conexão do produto (o painel, o portal, a API, o runner das
 * migrations, o instalador e as ferramentas de linha de comando) fixa o mesmo modo ao abrir, por estas opções:
 *
 *   STRICT_TRANS_TABLES          valor que não cabe, ou coluna obrigatória sem valor, é ERRO (não é truncado em silêncio);
 *   NO_ZERO_IN_DATE, NO_ZERO_DATE data com zero ("0000-00-00", "2026-00-10") é recusada;
 *   ERROR_FOR_DIVISION_BY_ZERO   divisão por zero numa gravação é erro;
 *   NO_ENGINE_SUBSTITUTION       tabela com motor indisponível é erro (não vira outro motor).
 *
 *   ONLY_FULL_GROUP_BY           consulta agrupada com coluna solta (sem agregação) é ERRO, e não uma linha qualquer
 *                                (Onda 6, Etapa 3; DUVIDAS 138: a lista da enturmação, a única que dependia disso,
 *                                passou a escolher a linha mais recente).
 */
final class VcBanco
{
    public const MODO = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

    /** O comando que fixa o modo na conexão (também serve ao cliente `mysql` de linha de comando, em --init-command). */
    public static function comando(): string
    {
        return "SET SESSION sql_mode = '" . self::MODO . "'";
    }

    /** As opções do PDO com o modo fixado; as do chamador ficam (as chaves dele valem primeiro). */
    public static function opcoes(array $opcoes = []): array
    {
        return $opcoes + [\PDO::MYSQL_ATTR_INIT_COMMAND => self::comando()];
    }

    /** Abre uma conexão com o modo fixado. */
    public static function abrir(string $dsn, string $usuario, string $senha, array $opcoes = []): \PDO
    {
        /* Onda 6, Etapa 1: a nova tentativa por falta de porta livre (Onda 4, DUVIDAS 270) saiu. A falta de porta vinha
           das dezenas de conexões curtas por tela; com uma conexão por requisição (daRequisicao), ela não acontece. */
        return new \PDO($dsn, $usuario, $senha, self::opcoes($opcoes));
    }

    /**
     * A CONEXÃO DA REQUISIÇÃO (Onda 6, Etapa 1; DUVIDAS 270). O painel, o portal e a API abriam uma conexão a cada
     * consulta (dezenas por tela). Agora há UMA por requisição, aberta na primeira consulta e reaproveitada por todas:
     * os helpers de leitura e gravação (AdmsConn), a transação (\Core\Transacao), a auditoria (que grava dentro da
     * transação), as travas do banco (GET_LOCK, que vale por conexão e aceita a mesma trava duas vezes) e as classes
     * que pedem um PDO. Erro de SQL é exceção e a leitura padrão é por nome (FETCH_ASSOC), como as conexões próprias
     * já pediam (o AdmsRead já pedia leitura por nome em cada consulta).
     *
     * Ficam com conexão própria, de propósito: o runner das migrations (muda o modo do banco durante uma etapa e o
     * devolve, e a trava dele é por conexão), o instalador (ainda sem Config) e as ferramentas de linha de comando.
     */
    private static ?\PDO $daRequisicao = null;

    public static function daRequisicao(): \PDO
    {
        return self::$daRequisicao ??= self::abrir('mysql:host=' . HOST . ';port=' . (defined('PORTA') ? (int) constant('PORTA') : 3306) . ';dbname=' . DBNAME . ';charset=utf8mb4', USER, PASS,
            [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC]);
    }

    /** O motivo da última recusa do modo estrito, em português (anotar() guarda; motivo() lê e limpa). */
    private static string $motivo = '';

    /** Os campos como a tela os chama; o que não está aqui vira "um dos campos" (nunca o nome da coluna). */
    private const CAMPOS = ['nome' => 'nome', 'nome_social' => 'nome social', 'email' => 'e-mail', 'telefone' => 'telefone', 'celular' => 'celular',
        'cpf' => 'CPF', 'rg' => 'RG', 'endereco' => 'endereço', 'bairro' => 'bairro', 'cidade' => 'cidade', 'complemento' => 'complemento',
        'nome_mae' => 'nome da mãe', 'nome_pai' => 'nome do pai', 'nasc_local' => 'local de nascimento', 'nasc_data' => 'data de nascimento',
        'observacao' => 'observação', 'titulo' => 'título', 'descricao' => 'descrição', 'nome_atividade' => 'nome da atividade', 'codtur' => 'turma',
        'data_ingresso' => 'data de ingresso', 'previsao_conclusao' => 'previsão de conclusão', 'data_conclusao' => 'data de conclusão'];

    /** Guarda o motivo, se a recusa for do modo estrito (o texto grande, o campo obrigatório vazio, a data inválida…). */
    public static function anotar(\Throwable $e): void
    {
        $codigo = $e instanceof \PDOException ? (int) ($e->errorInfo[1] ?? 0) : 0;
        $col = preg_match("/column '([^']+)'/i", $e->getMessage(), $m) ? $m[1] : '';
        $campo = isset(self::CAMPOS[$col]) ? 'o campo "' . self::CAMPOS[$col] . '"' : 'um dos campos';
        $textos = [
            1406 => "{$campo} passa do tamanho permitido",
            1048 => "{$campo} é obrigatório e veio vazio",
            1364 => "{$campo} é obrigatório e veio vazio",
            1292 => "{$campo} tem uma data ou um valor inválido",
            1366 => "{$campo} tem um valor inválido",
            1264 => "{$campo} tem um número fora do limite",
            1365 => 'a conta tem uma divisão por zero',
        ];
        if (isset($textos[$codigo])) {
            self::$motivo = $textos[$codigo];
        }
    }

    public static function motivo(): string
    {
        $m = self::$motivo;
        self::$motivo = '';
        return $m;
    }

    /** O modo em uso numa conexão (o instalador e a verificação do ambiente o informam). */
    public static function modoEmUso(\PDO $pdo): string
    {
        return (string) $pdo->query('SELECT @@SESSION.sql_mode')->fetchColumn();
    }

    /** O modo em uso tem tudo o que o sistema fixa? */
    public static function modoCompleto(string $emUso): bool
    {
        $tem = array_map('trim', explode(',', strtoupper($emUso)));
        return !array_diff(explode(',', self::MODO), $tem);
    }
}
