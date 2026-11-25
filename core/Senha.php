<?php

/**
 * A REGRA DA SENHA — uma só, para o instalador, o painel e o portal.
 *
 * Premia o comprimento (mínimo de 12 caracteres) e não exige símbolo nem maiúscula. Recusa:
 *   - menos de 12 caracteres;
 *   - uma senha da lista local de senhas comuns (assets/js/vcostaedu-senhas-comuns.js — o mesmo arquivo que o medidor
 *     do navegador usa), inclusive com números ou símbolos só no fim ("palavra2026!");
 *   - um caractere só repetido;
 *   - a que contém o nome, o usuário ou o e-mail de quem vai usá-la.
 * Tudo local: nenhuma consulta fora do servidor.
 *
 * O medidor do navegador (assets/js/vcostaedu-senha.js) aplica as mesmas recusas para avisar antes de enviar; quem
 * decide é esta classe.
 */

declare(strict_types=1);

final class VcSenha
{
    public const MINIMO = 12;
    /** O bcrypt só considera os primeiros 72 bytes. */
    public const MAXIMO_BYTES = 72;

    /** @return list<string> */
    public static function comuns(): array
    {
        static $lista = null;
        if ($lista !== null) {
            return $lista;
        }
        $js = (string) @file_get_contents(dirname(__DIR__) . '/assets/js/vcostaedu-senhas-comuns.js');
        $ini = strpos($js, '[');
        $fim = strrpos($js, ']');
        $itens = ($ini !== false && $fim !== false) ? json_decode(substr($js, $ini, $fim - $ini + 1), true) : null;

        return $lista = is_array($itens) ? array_values(array_map('strval', $itens)) : [];
    }

    /** Minúsculas, sem acento e sem espaço: a forma em que a senha é comparada com a lista e com os dados pessoais. */
    public static function normalizar(string $texto): string
    {
        $texto = mb_strtolower($texto, 'UTF-8');
        $texto = strtr($texto, [
            'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
            'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i', 'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
            'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ç' => 'c', 'ñ' => 'n',
        ]);

        return (string) preg_replace('/\s+/u', '', $texto);
    }

    /**
     * Os pedaços dos dados pessoais que a senha não pode conter: o valor inteiro, cada palavra do nome e as partes do
     * e-mail antes do @ — só os de 4 caracteres ou mais (um "ana" dentro de "bananada" não é a pessoa).
     *
     * @param array<string,string> $pessoais ['nome' => ..., 'usuario' => ..., 'email' => ...]
     * @return list<string>
     */
    public static function pedacosPessoais(array $pessoais): array
    {
        $pedacos = [];
        foreach ($pessoais as $chave => $valor) {
            $valor = trim((string) $valor);
            if ($valor === '') {
                continue;
            }
            if ($chave === 'email' && str_contains($valor, '@')) {
                $valor = strstr($valor, '@', true);
            }
            $pedacos[] = self::normalizar($valor);
            foreach (preg_split('/[\s._\-@]+/u', $valor) ?: [] as $parte) {
                $pedacos[] = self::normalizar($parte);
            }
        }

        return array_values(array_unique(array_filter($pedacos, static fn(string $p): bool => mb_strlen($p) >= 4)));
    }

    /**
     * O que impede a senha, em português, uma frase por problema. Vazio = a senha serve.
     *
     * @param array<string,string> $pessoais ['nome' => ..., 'usuario' => ..., 'email' => ...]
     * @return list<string>
     */
    public static function problemas(string $senha, array $pessoais = []): array
    {
        $erros = [];
        if (mb_strlen($senha, 'UTF-8') < self::MINIMO) {
            $erros[] = 'A senha precisa ter pelo menos ' . self::MINIMO . ' caracteres.';
        }
        if (strlen($senha) > self::MAXIMO_BYTES) {
            $erros[] = 'A senha pode ter no máximo ' . self::MAXIMO_BYTES . ' caracteres.';
        }
        $n = self::normalizar($senha);
        if ($n !== '') {
            $nucleo = (string) preg_replace('/[^a-z]+$/', '', $n);
            $comuns = self::comuns();
            if (in_array($n, $comuns, true) || (strlen($nucleo) >= 4 && in_array($nucleo, $comuns, true))
                || preg_match('/^(.)\1+$/u', $n)) {
                $erros[] = 'Esta senha é muito comum e fácil de adivinhar. Escolha outra.';
            }
            foreach (self::pedacosPessoais($pessoais) as $p) {
                if (str_contains($n, $p)) {
                    $erros[] = 'A senha não pode conter o seu nome, o seu usuário ou o seu e-mail.';
                    break;
                }
            }
        }

        return $erros;
    }

    /**
     * Os atributos que ligam o componente do navegador (assets/js/vcostaedu-componentes.js) a um campo de senha.
     *
     * @param string $tipo 'nova', 'confirmar' ou 'atual'
     * @param array<string,string> $valores dados pessoais que o formulário não tem (nome, usuário, e-mail)
     * @param string $campos seletores dos campos do formulário com os dados pessoais ("#nome,#usuario,#email")
     * @param string $de na confirmação, o seletor do campo da senha nova
     */
    public static function atributos(string $tipo, array $valores = [], string $campos = '', string $de = ''): string
    {
        $h = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $a = ' data-vc-senha="' . $h($tipo) . '"';
        $a .= ' autocomplete="' . ($tipo === 'atual' ? 'current-password' : 'new-password') . '"';
        if ($tipo === 'nova') {
            $a .= ' minlength="' . self::MINIMO . '"';
        }
        $valores = array_filter(array_map('strval', $valores), static fn(string $v): bool => trim($v) !== '');
        if ($valores !== []) {
            $a .= ' data-vc-senha-valores="' . $h((string) json_encode($valores, JSON_UNESCAPED_UNICODE)) . '"';
        }
        if ($campos !== '') {
            $a .= ' data-vc-senha-campos="' . $h($campos) . '"';
        }
        if ($de !== '') {
            $a .= ' data-vc-senha-de="' . $h($de) . '"';
        }

        return $a;
    }

    /** A frase de ajuda dos formulários, a mesma em todos. */
    public static function ajuda(): string
    {
        return 'Use pelo menos ' . self::MINIMO . ' caracteres. Uma frase longa, que só você conheça, protege mais '
            . 'do que símbolos e maiúsculas.';
    }
}
