<?php

namespace App\adms\Models\helper;

if (!defined('VCOSTAEDU')) {
    header("Location: /");
    die("Erro: Página não encontrada!");
}

/**
 * 1.18.3 (D-497) — PROTEÇÃO ENTRE NÍVEIS. Uma regra só, pela `ordem` de adms_niveis_acessos (menor = acima):
 * um usuário só cadastra, edita, troca a senha, desativa, apaga ou muda o nível de quem é de nível ABAIXO do seu, e só
 * atribui níveis abaixo do seu. Ninguém mexe nas permissões, no nome, na ordem ou na existência do próprio nível nem
 * de um nível acima. O próprio perfil e a própria senha passam pela tela Perfil (que só grava os campos dela).
 *
 * `podeAlterarNivel()` é a função única: todas as outras perguntas desta classe passam por ela. Usada em Usuarios,
 * NivelAcesso, Liberar e nos models que gravam usuário ou nível (a checagem vale também para o POST forjado).
 */
class AdmsHierarquia
{
    /** Mensagem padrão da recusa. */
    public const RECUSA = 'Você só pode alterar usuários e níveis de acesso abaixo do seu.';

    /** ordem do nível (null se não existe). Sempre do banco: a ordem pode ter mudado depois do login. */
    public static function ordem(int $nivelId): ?int
    {
        if ($nivelId <= 0) {
            return null;
        }
        $ler = new AdmsRead();
        $ler->fullRead("SELECT ordem FROM adms_niveis_acessos WHERE id = :id LIMIT :limit", "id={$nivelId}&limit=1");
        $r = $ler->getResultado();
        return $r ? (int) $r[0]['ordem'] : null;
    }

    /** A FUNÇÃO ÚNICA: o nível da sessão está ACIMA do nível alvo? (igual ou acima = não) */
    public static function podeAlterarNivel(int $nivelAlvoId): bool
    {
        $meu = self::ordem(AdmsNivel::nivel());
        $alvo = self::ordem($nivelAlvoId);
        return $meu !== null && $alvo !== null && $alvo > $meu;
    }

    /** O nível do usuário do painel (null se não existe). */
    public static function nivelDoUsuario(int $usuarioId): ?int
    {
        if ($usuarioId <= 0) {
            return null;
        }
        $ler = new AdmsRead();
        $ler->fullRead("SELECT adms_niveis_acesso_id FROM adms_usuarios WHERE id = :id LIMIT :limit", "id={$usuarioId}&limit=1");
        $r = $ler->getResultado();
        return $r ? (int) $r[0]['adms_niveis_acesso_id'] : null;
    }

    /** Pode editar, trocar a senha, desativar ou apagar este usuário? (ele mesmo: não — o próprio é pelo Perfil) */
    public static function podeAlterarUsuario(int $usuarioId): bool
    {
        if ($usuarioId === (int) ($_SESSION['colUsuario_id'] ?? 0)) {
            return false;
        }
        $nivel = self::nivelDoUsuario($usuarioId);
        return $nivel !== null && self::podeAlterarNivel($nivel);
    }

    /** O nível da linha de permissão (adms_nivacs_pgs.id), para os botões Liberar e Item de menu. */
    public static function nivelDaPermissao(int $nivacId): ?int
    {
        if ($nivacId <= 0) {
            return null;
        }
        $ler = new AdmsRead();
        $ler->fullRead("SELECT adms_niveis_acesso_id FROM adms_nivacs_pgs WHERE id = :id LIMIT :limit", "id={$nivacId}&limit=1");
        $r = $ler->getResultado();
        return $r ? (int) $r[0]['adms_niveis_acesso_id'] : null;
    }

    /** Os ids dos níveis que a sessão pode atribuir (abaixo do seu). */
    public static function niveisAtribuiveis(): array
    {
        $meu = self::ordem(AdmsNivel::nivel());
        if ($meu === null) {
            return [];
        }
        $ler = new AdmsRead();
        $ler->fullRead("SELECT id FROM adms_niveis_acessos WHERE ordem > :ordem", "ordem={$meu}");
        return array_map(fn($r) => (int) $r['id'], $ler->getResultado() ?: []);
    }

    /** Mensagem de recusa na sessão (o mesmo alerta das telas). */
    public static function recusar(?string $texto = null): void
    {
        $_SESSION['msg'] = "<div class='alert alert-danger alert-dismissible'><button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Fechar'></button>Erro: " . htmlspecialchars($texto ?? self::RECUSA, ENT_QUOTES, 'UTF-8') . "</div>";
    }
}
