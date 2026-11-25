<?php

/**
 * A CONFERÊNCIA DA BARREIRA DA FAMÍLIA (Onda 7, Etapa 5; docs/RESPONSAVEIS.md, D-65) — usada por "Verificações de
 * segurança" (core/Verificacoes.php). Lê o código do portal do responsável e o banco, e diz o que está fora da regra:
 *
 *   - toda rota pública do controller da família (portal/app/adms/Controllers/Familia.php) começa perguntando:
 *     a lista dos filhos, por responsavel() (quem é o responsável ativo); toda outra, por exigir() (o aluno e o ano);
 *   - exigir() recusa quando o PONTO ÚNICO (VcAcessoFamilia::pode) diz não;
 *   - toda página do controller que o banco publica para o portal tem a rota no controller (nada sem a barreira);
 *   - o nível "Responsável" do portal só abre as páginas da família (e o sair).
 *
 * Nada aqui executa o código lido: é leitura do arquivo e do banco.
 */
final class VcBarreiraFamilia
{
    public const CONTROLLER = 'portal/app/adms/Controllers/Familia.php';
    public const NIVEL = 2;

    /** As rotas públicas (métodos de instância) do controller e o começo de cada uma. @return array<string, string> */
    public static function rotas(string $codigo): array
    {
        $r = [];
        if (preg_match_all('/public\s+function\s+([A-Za-z_][A-Za-z0-9_]*)\s*\([^)]*\)\s*(?::\s*[?A-Za-z\\\\]+)?\s*\{/', $codigo, $m, PREG_OFFSET_CAPTURE)) {
            foreach ($m[1] as $k => [$nome]) {
                $ini = $m[0][$k][1] + strlen($m[0][$k][0]);
                $r[$nome] = ltrim(substr($codigo, $ini, 300));
            }
        }
        return $r;
    }

    /** O corpo de um método privado (até o próximo método). */
    private static function corpo(string $codigo, string $nome): string
    {
        if (!preg_match('/private\s+function\s+' . preg_quote($nome, '/') . '\s*\([^)]*\)[^{]*\{/', $codigo, $m, PREG_OFFSET_CAPTURE)) {
            return '';
        }
        $ini = $m[0][1] + strlen($m[0][0]);
        $fim = preg_match('/\n\s*(public|private|protected)\s+(static\s+)?function\s/', $codigo, $n, PREG_OFFSET_CAPTURE, $ini) ? $n[0][1] : strlen($codigo);
        return substr($codigo, $ini, $fim - $ini);
    }

    /** @return string[] o que está fora da regra (vazio: tudo certo) */
    public static function problemas(\PDO $pdo, string $raiz): array
    {
        $arq = rtrim(str_replace('\\', '/', $raiz), '/') . '/' . self::CONTROLLER;
        $codigo = is_file($arq) ? (string) file_get_contents($arq) : '';
        if ($codigo === '') {
            return ['o controller do portal do responsável não foi encontrado'];
        }
        $p = [];
        $rotas = self::rotas($codigo);
        foreach ($rotas as $nome => $inicio) {
            if ($nome === 'index') {
                if (!preg_match('/^\$[A-Za-z_]+\s*=\s*\$this->responsavel\(\);/', $inicio)) {
                    $p[] = 'a lista dos filhos não começa conferindo o responsável';
                }
            } elseif (!preg_match('/^(\[[^\]]*\]\s*=\s*)?\$this->exigir\(\$[A-Za-z_]+\);/', $inicio)) {
                $p[] = "a rota \"{$nome}\" não começa pela barreira";
            }
        }
        $exigir = self::corpo($codigo, 'exigir');
        if (!preg_match('/if\s*\([^{;]*!\s*\$this->acesso\(\)->pode\([^{;]*\)\s*\{\s*\$this->recusar\(\);/s', $exigir)) {
            $p[] = 'a barreira não recusa quando o ponto único diz não';
        }
        if (!preg_match('/private\s+function\s+responsavel\(\)[^{]*\{[^}]*->ativo\(/s', $codigo)) {
            $p[] = 'a conferência do responsável não pergunta se ele está ativo';
        }
        try {
            $pg = $pdo->query("SELECT metodo FROM col_paginas WHERE controller = 'Familia'")->fetchAll(\PDO::FETCH_COLUMN);
            foreach ($pg as $metodo) {
                if (!isset($rotas[$metodo])) {
                    $p[] = "a página \"{$metodo}\" da família não tem a rota no controller";
                }
            }
            $st = $pdo->prepare("SELECT DISTINCT p.controller FROM col_nivacs_pgs n INNER JOIN col_paginas p ON p.id = n.adms_pagina_id
                  WHERE n.adms_niveis_acesso_id = ? AND n.permissao = 1 AND p.lib_pub <> 1 AND p.controller NOT IN ('Familia', 'Login')");
            $st->execute([self::NIVEL]);
            foreach ($st->fetchAll(\PDO::FETCH_COLUMN) as $c) {
                $p[] = "o nível Responsável abre uma página fora da família ({$c})";
            }
        } catch (\Throwable $e) {
            $p[] = 'as páginas do portal não puderam ser lidas';
        }
        return $p;
    }
}
