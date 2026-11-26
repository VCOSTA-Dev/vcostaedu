<?php
/**
 * O TIPO DE INSTITUIÇÃO E O VOCABULÁRIO — um ponto só (docs/ESCOLA.md).
 *
 * Uma instalação é de UM tipo: ensino superior, escola (educação básica) ou curso livre (D-106). O tipo é dado da instalação (a chave
 * "instituicao.tipo" em adms_instalacao_estado; sem ela, superior). Aqui ficam o que cada tipo é para quem instala (o
 * nome e a frase da escolha) e as palavras da tela que mudam com ele. Tabelas, colunas e rotas não mudam: só o texto.
 *
 * Nada aqui lê banco ou sessão (o painel lê o tipo em AdmsTipoInstituicao; o instalador, na conexão dele).
 */
final class VcVocabulario
{
    public const SUPERIOR = 'superior';
    public const ESCOLA = 'escola';
    /** Fechamento (D-106): o curso livre (curso on-line) — a estrutura por turma da escola, sem etapas, boletim,
        conselho, horário nem os documentos; o financeiro e a matrícula pela secretaria são recursos de ligar e desligar. */
    public const LIVRE = 'livre';

    /** O nome e a frase de cada tipo, na escolha (o instalador e "Começar com os dados da minha instituição"). */
    public const TIPOS = [
        self::SUPERIOR => ['Ensino superior', 'Cursos de graduação e pós-graduação: a matriz com módulos, as disciplinas ofertadas, uma média por disciplina, o histórico e o TCC.'],
        self::ESCOLA => ['Escola (educação básica)', 'Ensino Fundamental 1 e 2 e Ensino Médio: séries, turmas, o ano letivo em bimestres ou trimestres, a recuperação, a média anual e o conselho de classe.'],
        self::LIVRE => ['Curso livre (curso on-line)', 'Cursos livres e on-line: o curso, a turma com vagas, as disciplinas no Moodle e a inscrição pelo site. Sem boletim, etapas, horário, conselho nem documentos escolares.'],
    ];

    /** D-106: os recursos que o curso livre liga e desliga (na instalação e em Configurações): chave => [nome, frase, ligado ao instalar]. */
    public const RECURSOS_LIVRE = [
        'financeiro' => ['Financeiro', 'A ficha financeira do aluno, os planos de pagamento, o recebimento, o caixa e os relatórios do financeiro.', false],
        'matricula' => ['Matrícula pela secretaria', 'A secretaria cadastra o aluno, matricula no curso, põe na turma e importa planilhas. Desligada, o aluno chega só pela inscrição do site (a interface de integração).', true],
    ];

    /** A estrutura é por turma (a escola e o curso livre: o curso, a turma com vagas e as disciplinas da turma)? */
    public static function porTurma(string $tipo): bool
    {
        return $tipo === self::ESCOLA || $tipo === self::LIVRE;
    }

    /** O módulo (uma página, um guia, uma rota) vale no tipo? $modulo: null/'' = o núcleo; senão a lista com vírgula. */
    public static function moduloNoTipo(?string $modulo, string $tipo): bool
    {
        if ($modulo === null || trim($modulo) === '') {
            return true;
        }
        return in_array($tipo, array_map('trim', explode(',', $modulo)), true);
    }

    /**
     * As palavras da escola no lugar das do superior, nos rótulos (o menu e o título das telas). A ordem importa: as
     * expressões com artigo vêm antes da palavra solta.
     */
    private const ESCOLA_TERMOS = [
        /* Onda 8, correção (D-85): na escola, o termo é "disciplina", como no ensino superior — "componente curricular" é
           outra coisa para o responsável (TCC, atividade complementar, estágio, extensão). Saíram as trocas de disciplina
           (que fica "disciplina") e a de matéria por "componente": a matéria do catálogo também se chama disciplina (o
           menu "Matérias" aparece como "Disciplinas"; o gênero é o mesmo, nada muda nos artigos). */
        'Matérias' => 'Disciplinas', 'matérias' => 'disciplinas', 'Matéria' => 'Disciplina', 'matéria' => 'disciplina',
        'Cursos' => 'Segmentos', 'cursos' => 'segmentos', 'Curso' => 'Segmento', 'curso' => 'segmento',
        'Módulos' => 'Séries', 'módulos' => 'séries', 'Módulo' => 'Série', 'módulo' => 'série',
        'Polos' => 'Unidades', 'polos' => 'unidades', 'Polo' => 'Unidade', 'polo' => 'unidade',
        'Coordenação de curso' => 'Coordenação de segmento',
    ];

    /** Os termos que as telas da escola pedem pelo nome (singular, plural). */
    private const TERMOS = [
        'curso'      => [self::SUPERIOR => ['curso', 'cursos'], self::ESCOLA => ['segmento', 'segmentos']],
        'modulo'     => [self::SUPERIOR => ['módulo', 'módulos'], self::ESCOLA => ['série', 'séries']],
        'disciplina' => [self::SUPERIOR => ['disciplina', 'disciplinas'], self::ESCOLA => ['disciplina', 'disciplinas']], // D-85
        'turma'      => [self::SUPERIOR => ['turma', 'turmas'], self::ESCOLA => ['turma', 'turmas']],
        'etapa'      => [self::SUPERIOR => ['período', 'períodos'], self::ESCOLA => ['etapa', 'etapas']],
        'polo'       => [self::SUPERIOR => ['polo', 'polos'], self::ESCOLA => ['unidade', 'unidades']],
    ];

    /** D-106: no curso livre, só o catálogo muda de nome — a matéria é a disciplina (os módulos do curso são disciplinas). */
    private const LIVRE_TERMOS = [
        'Matérias' => 'Disciplinas', 'matérias' => 'disciplinas', 'Matéria' => 'Disciplina', 'matéria' => 'disciplina',
        'Todas as Disciplinas' => 'Disciplinas das turmas',
    ];

    public static function valido(string $tipo): bool
    {
        return isset(self::TIPOS[$tipo]);
    }

    public static function nome(string $tipo): string
    {
        return self::TIPOS[$tipo][0] ?? self::TIPOS[self::SUPERIOR][0];
    }

    /** O termo de um conceito no tipo: termo('curso', 'escola') = "segmento"; $plural; $maiuscula na primeira letra. */
    public static function termo(string $conceito, string $tipo, bool $plural = false, bool $maiuscula = false): string
    {
        $t = self::TERMOS[$conceito][$tipo] ?? self::TERMOS[$conceito][self::SUPERIOR] ?? [$conceito, $conceito];
        $p = $t[$plural ? 1 : 0];
        return $maiuscula ? mb_strtoupper(mb_substr($p, 0, 1)) . mb_substr($p, 1) : $p;
    }

    /**
     * O rótulo no vocabulário do tipo (o menu, o título da tela). No superior, devolve o texto como veio — o superior
     * não muda. Na escola, troca as palavras inteiras (não dentro de outra palavra), as expressões com artigo antes.
     */
    public static function traduzir(string $texto, string $tipo): string
    {
        if (($tipo !== self::ESCOLA && $tipo !== self::LIVRE) || $texto === '') {
            return $texto;
        }
        $padroes = [];
        foreach ($tipo === self::LIVRE ? self::LIVRE_TERMOS : self::ESCOLA_TERMOS as $de => $para) {
            $padroes['/(?<![\p{L}\p{N}_])' . preg_quote($de, '/') . '(?![\p{L}\p{N}_])/u'] = $para;
        }
        /* uma passada por termo, na ordem; o que já foi trocado não é trocado de novo (os destinos não contêm origens) */
        foreach ($padroes as $re => $para) {
            $texto = (string) preg_replace($re, $para, $texto);
        }
        return $texto;
    }

    /** O mesmo, num trecho de HTML: só o texto entre as marcas muda (endereços e atributos ficam). */
    public static function traduzirHtml(string $html, string $tipo): string
    {
        if (($tipo !== self::ESCOLA && $tipo !== self::LIVRE) || $html === '') {
            return $html;
        }
        $partes = preg_split('/(<[^>]*>)/u', $html, -1, PREG_SPLIT_DELIM_CAPTURE) ?: [$html];
        foreach ($partes as $i => $p) {
            if ($p !== '' && $p[0] !== '<') {
                $partes[$i] = self::traduzir($p, $tipo);
            }
        }
        return implode('', $partes);
    }
}
