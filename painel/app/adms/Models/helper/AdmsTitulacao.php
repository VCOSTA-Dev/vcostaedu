<?php

namespace App\adms\Models\helper;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * TITULAÇÃO do docente (E6a): uni_rh_titulacao — estrutura do sistema de origem de ensino superior, linhas da VCOSTAEDU
 * (Especialista, Mestre, Doutor). Informada no cadastro do usuário (professor); o docente de
 * cada disciplina no histórico é o professor da oferta, com esta titulação.
 */
class AdmsTitulacao
{
    public static function ativas(): array
    {
        $ler = new AdmsRead();
        $ler->fullRead("SELECT id, nome FROM uni_rh_titulacao WHERE status_id = :s ORDER BY id", "s=1");
        return $ler->getResultado() ?: [];
    }

    public static function existe(int $id): bool
    {
        foreach (self::ativas() as $t) {
            if ((int) $t['id'] === $id) {
                return true;
            }
        }
        return false;
    }

    /**
     * Tira `titulacao_id` dos dados do formulário (o campo é opcional; o validador de campos
     * vazios do cadastro de usuário recusaria o vazio) e devolve o valor a gravar:
     * id válido, null (vazio) ou false (inválido).
     */
    public static function extrair(array &$dados)
    {
        $v = trim((string) ($dados['titulacao_id'] ?? ''));
        unset($dados['titulacao_id']);
        if ($v === '') {
            return null;
        }
        return ctype_digit($v) && self::existe((int) $v) ? (int) $v : false;
    }
}
