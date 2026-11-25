<?php
/*
 * A DEMONSTRAÇÃO EM PASSOS — o cenário fictício e a montagem, divididos em passos curtos (Onda 3, Etapa 4).
 *
 * Uma implementação só para os dois caminhos:
 *   - a TELA ("Como você quer começar" → Conhecer com dados de demonstração): cada passo num chamado do navegador,
 *     dentro do PHP da própria requisição (sem executar programa nenhum, sem o cliente mysql), com o estado no banco
 *     (painel/app/adms/Models/demonstracao/AdmsDemonstracaoMontagem.php);
 *   - a LINHA DE COMANDO (database/ferramentas/seed_demo_processar.php), que roda os mesmos passos em sequência.
 *
 * Cada passo monta PELAS TELAS do sistema, por HTTP (como sempre foi: as regras das telas valem para a demonstração),
 * e acha o que os passos anteriores criaram pelo banco (os nomes do cenário). Cada passo pode ser rodado de novo sem
 * duplicar nada: o que já existe é pulado.
 *
 * Este arquivo só define classes e constantes: incluí-lo não executa nada.
 */

/** O navegador da montagem: um pote de cookies por pessoa fictícia; o do administrador pode usar a sessão de quem pediu. */
final class VcDemoNavegador
{
    private string $jar;
    public string $html = '';
    public string $url = '';
    public int $code = 0;
    private string $cookieFixo;

    public function __construct(string $cookieFixo = '')
    {
        $this->jar = (string) tempnam(sys_get_temp_dir(), 'vcdemo');
        $this->cookieFixo = $cookieFixo;
    }

    public function __destruct()
    {
        @unlink($this->jar);
    }

    /** Se o último pedido seguiu o redirecionamento (req) ou parou nele (gravar). */
    public bool $seguiu = true;

    /**
     * Uma gravação em série, sem abrir a tela seguinte (Onda 6, Etapa 2). A gravação de uma tela termina, quase sempre,
     * com o redirecionamento para a lista; seguir o redirecionamento desenha a lista inteira, que ninguém lê aqui — na
     * escola, eram centenas de telas a mais. A gravação e as regras dela são as mesmas; confere() aceita o
     * redirecionamento como resposta (e ainda recusa erro do PHP e resposta cortada). Quem precisa ler a tela de depois
     * (o link de acesso, por exemplo) usa req().
     */
    public function gravar(string $url, array $post, bool $multipart = false): string
    {
        return $this->req($url, $post, $multipart, false);
    }

    /** POST em array vira form-urlencoded; $multipart = true manda como o navegador manda um formulário com arquivo. */
    public function req(string $url, ?array $post = null, bool $multipart = false, bool $seguir = true): string
    {
        $this->seguiu = $seguir;
        $ch = curl_init($url);
        $op = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => $seguir,
            CURLOPT_COOKIEJAR      => $this->jar,
            CURLOPT_COOKIEFILE     => $this->jar,
            CURLOPT_TIMEOUT        => 60,
        ];
        if ($this->cookieFixo !== '') {
            $op[CURLOPT_COOKIE] = $this->cookieFixo;
        }
        curl_setopt_array($ch, $op);
        if ($post !== null) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $multipart ? $post : http_build_query($post));
        }
        /* Onda 6, Etapa 2: a nova tentativa da resposta cortada (Onda 4, DUVIDAS 270) saiu — a resposta cortada era a
           conexão com o banco que não abria por falta de porta, e a conexão por requisição (D-51) acabou com ela. Se uma
           resposta vier cortada, o passo para com o que veio (confere()), sem adivinhar. */
        $this->html = (string) curl_exec($ch);
        $this->code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        $this->url = (string) curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        if ($this->cortada()) {
            error_log('[vcostaedu][demonstracao] resposta cortada (' . strlen($this->html) . ' bytes) em ' . preg_replace('#^https?://[^/]+#', '', $this->url) . ': ' . substr(preg_replace('/\s+/', ' ', $this->html), 0, 120));
        }
        /* A medição (Onda 6, Etapa 2), só na linha de comando e só com VCOSTAEDU_DEMO_PERFIL=<arquivo>: cada pedido, com
           o tempo do pedido e o do redirecionamento seguido, numa linha. */
        if (PHP_SAPI === 'cli' && ($perfil = getenv('VCOSTAEDU_DEMO_PERFIL'))) {
            file_put_contents($perfil, implode("\t", [preg_replace('#^https?://[^/]+#', '', $url), $post === null ? 'GET' : 'POST',
                round(curl_getinfo($ch, CURLINFO_TOTAL_TIME) * 1000), round(curl_getinfo($ch, CURLINFO_REDIRECT_TIME) * 1000),
                preg_replace('#^https?://[^/]+#', '', $this->url), strlen($this->html)]) . "\n", FILE_APPEND);
        }
        curl_close($ch);
        return $this->html;
    }

    /** A resposta cortada: 200 com menos de 1 KB, que não é página nem JSON. */
    public function cortada(): bool
    {
        $t = ltrim($this->html);
        return $this->code === 200 && strlen($t) < 1024 && stripos($t, '<html') === false && stripos($t, '<!doctype') === false && !in_array($t[0] ?? '', ['{', '['], true);
    }

    /** Valores atuais do primeiro formulário POST da página (inputs, selects, textareas). */
    public function form(): array
    {
        $d = new DOMDocument();
        @$d->loadHTML('<?xml encoding="utf-8"?>' . $this->html);
        $x = new DOMXPath($d);
        $o = [];
        foreach ($x->query('//form[@method="POST" or @method="post"][1]//input[@name]') as $i) {
            $t = strtolower($i->getAttribute('type'));
            if (in_array($t, ['file', 'submit', 'button'], true) || (in_array($t, ['radio', 'checkbox'], true) && !$i->hasAttribute('checked'))) {
                continue;
            }
            $o[$i->getAttribute('name')] = $i->getAttribute('value');
        }
        foreach ($x->query('//form[@method="POST" or @method="post"][1]//select[@name]') as $s) {
            $v = '';
            foreach ($x->query('.//option', $s) as $op) {
                if ($op->hasAttribute('selected')) {
                    $v = $op->getAttribute('value');
                }
            }
            $o[$s->getAttribute('name')] = $v;
        }
        foreach ($x->query('//form[@method="POST" or @method="post"][1]//textarea[@name]') as $t) {
            $o[$t->getAttribute('name')] = $t->textContent;
        }
        return $o;
    }

    public function alerta(): string
    {
        if (!preg_match_all("/<div class=['\"]alert[^>]*>(.*?)<\/div>/s", $this->html, $m)) {
            return '';
        }
        return trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags(implode(' ', $m[1])))), " ×");
    }

    public function erroPhp(): string
    {
        return preg_match('/(Warning|Notice|Fatal error|Deprecated|Uncaught)[^<]{0,160}/', $this->html, $m) ? $m[0] : '';
    }
}

/** Um passo que não fechou: a mensagem diz o quê (sem caminho do servidor). */
final class VcDemoFalha extends RuntimeException
{
}

/**
 * O contexto da montagem: o banco, os endereços, o administrador e as senhas fictícias (geradas UMA vez, no começo,
 * e guardadas no estado da montagem: o passo repetido entra com a mesma senha).
 */
final class VcDemoContexto
{
    /** @var array<string, VcDemoNavegador> */
    private array $sessoes = [];
    /** @var callable(string):void */
    private $log;

    /**
     * @param callable():VcDemoNavegador $admin  o navegador do administrador (já dentro do painel)
     * @param array<string|int, string>  $senhas as senhas da equipe e dos alunos fictícios
     */
    public function __construct(public PDO $pdo, public string $base, private $admin, public array $senhas, ?callable $log = null)
    {
        $this->base = rtrim($base, '/') . '/';
        $this->log = $log ?? static function (string $t): void {
        };
    }

    public function painel(): string
    {
        return $this->base . 'painel/';
    }

    public function portal(): string
    {
        return $this->base . 'portal/';
    }

    public function log(string $t): void
    {
        ($this->log)($t);
    }

    public function um(string $sql, array $p = [])
    {
        $s = $this->pdo->prepare($sql);
        $s->execute($p);
        $v = $s->fetchColumn();
        return $v === false ? null : $v;
    }

    public function adm(): VcDemoNavegador
    {
        return $this->sessoes['@adm'] ??= ($this->admin)();
    }

    /** A pessoa fictícia, no painel (um login por passo). */
    public function entrar(string $login): VcDemoNavegador
    {
        if (isset($this->sessoes[$login])) {
            return $this->sessoes[$login];
        }
        $senha = (string) ($this->senhas[$login] ?? '');
        $n = new VcDemoNavegador();
        $n->req($this->painel() . 'login/acesso', ['usuario' => $login, 'senha' => $senha, 'SendLogin' => 'Acessar']);
        if ($n->code !== 200 || str_contains($n->html, 'name="SendLogin"')) {
            VcDemoPassos::falha("login de {$login} no painel não entrou.");
        }
        return $this->sessoes[$login] = $n;
    }
}

/** O cenário (tudo inventado) e os passos. */
final class VcDemoPassos
{
    public const ANO = 2026;
    public const POLO = 'Polo Central Fictício';
    public const CURSO = 'Pós-graduação em Gestão de Projetos';
    public const TURMA = 'PGP-2026';
    public const ENCONTROS = 15; // 60 h por disciplina, encontros de 4 h
    /* Disciplinas da grade (60 h cada: 360 h no curso), SEM módulo (D-268). Chave curta → nome. */
    public const DISC = [
        'FAP' => 'Fundamentos de Gestão de Projetos',
        'MPA' => 'Métodos Ágeis',
        'GPS' => 'Gestão de Riscos',
        'OFP' => 'Planejamento e Cronograma',
        'GOC' => 'Liderança de Equipes',
        'STF' => 'Trabalho de Conclusão de Curso',
    ];
    /* o código (a abreviação) de cada matéria, que sai no histórico; as chaves (FAP, MPA…) são só internas */
    public const CODIGO = ['FAP' => 'GP01', 'GOC' => 'GP02', 'GPS' => 'GP03', 'MPA' => 'GP04', 'OFP' => 'GP05', 'STF' => 'GP06', 'EST' => 'DE01', 'GDA' => 'DE02', 'TDA' => 'DE03'];
    /* Onda 3, Etapa 8: a demonstração não põe mais um "Id no Moodle" fictício nas ofertas — com o Moodle de verdade
       conectado, um número inventado levaria aluno fictício a uma disciplina real com aquele número */
    /* um usuário de cada perfil, pelo PAPEL do nível; a ordem guarda os ids da equipe (os registros acadêmicos citam esses ids) */
    public const EQUIPE = [
        'direcao.demo'     => ['Diana Diretora Acadêmica (fictícia)', 'direcao', 1],
        'secretaria.demo'  => ['Sônia Secretária (fictícia)', 'secretaria', 1],
        'prof.laura.demo'  => ['Profa. Laura Exemplar (fictícia)', 'professor', 1],
        'prof.marcos.demo' => ['Prof. Marcos Modelo (fictício)', 'professor', 2],
        'supervisao.demo'  => ['Sérgio Supervisor da Secretaria (fictício)', 'supervisao_secretaria', 2],
        'coord.demo'       => ['Cecília Coordenadora do Curso (fictícia)', 'coordenacao', 1],
    ];
    public const PROFESSOR = ['FAP' => 'prof.laura.demo', 'GPS' => 'prof.laura.demo', 'GOC' => 'prof.laura.demo',
                              'MPA' => 'prof.marcos.demo', 'OFP' => 'prof.marcos.demo', 'STF' => 'prof.marcos.demo'];
    /* Alunos: RA → [nome, gênero]. O 260108 fica SEM senha (mostra o "Enviar link de acesso"). */
    public const ALUNOS = [
        260101 => ['Ana Beatriz Exemplo', 1], 260102 => ['Bruno Fictício Lima', 2],
        260103 => ['Carla Modelo Souza', 1],  260104 => ['Diego Inventado Rocha', 2],
        260105 => ['Elisa Amostra Nunes', 1], 260106 => ['Fábio Protótipo Dias', 2],
        260107 => ['Gabriela Simulada Reis', 1], 260108 => ['Heitor Hipotético Alves', 2],
    ];
    public const SEM_SENHA = 260108;
    /* Dados pessoais FICTÍCIOS (nascimento, naturalidade/UF, mãe e pai). CPF e RG são gerados a cada execução. */
    public const PESSOAIS = [
        260101 => ['1990-02-11', 'Cidade Exemplo', 'DF', 'Maria Exemplo da Silva', 'José Exemplo da Silva'],
        260102 => ['1987-07-23', 'Vila Imaginária', 'DF', 'Ana Fictícia Lima', 'Paulo Fictício Lima'],
        260103 => ['1992-10-05', 'Cidade Exemplo', 'DF', 'Rosa Modelo Souza', 'Carlos Modelo Souza'],
        260104 => ['1991-05-14', 'Cidade Exemplo', 'DF', 'Lúcia Inventada Rocha', 'Marcos Inventado Rocha'],
        260105 => ['1989-01-30', 'Vila Imaginária', 'DF', 'Teresa Amostra Nunes', 'Rui Amostra Nunes'],
        260106 => ['1994-04-18', 'Cidade Exemplo', 'DF', 'Helena Protótipo Dias', 'Antônio Protótipo Dias'],
        260107 => ['1995-12-02', 'Vila Imaginária', 'DF', 'Clara Simulada Reis', 'Jorge Simulado Reis'],
        260108 => ['1988-11-02', 'Vila Imaginária', 'DF', 'Sônia Hipotética Alves', 'Pedro Hipotético Alves'],
        250101 => ['1985-03-27', 'Cidade Exemplo', 'DF', 'Beatriz Imaginária Costa', 'Rafael Imaginário Costa'],
        250102 => ['1993-08-09', 'Vila Imaginária', 'DF', 'Márcia Suposta Pereira', 'Luís Suposto Pereira'],
    ];
    public const AJUSTE = [260107, 'OFP']; // a secretaria tira Gabriela de Planejamento e Cronograma (cursará depois)
    /* Notas das 2 atividades e faltas: [nota 1, nota 2 (null = ainda sem nota), faltas, compensadas]. */
    public const LANCAMENTOS = [
        'FAP' => [260101 => ['9,0', '8,5', 0, 0], 260102 => ['8,0', '7,5', 1, 0], 260103 => ['7,0', '7,5', 2, 0], 260104 => ['8,5', '8,0', 1, 0],
                  260105 => ['7,5', '8,0', 5, 2], 260106 => ['6,9', '7,0', 0, 0], 260107 => ['9,5', '9,0', 0, 0], 260108 => ['8,0', '8,0', 1, 0]],
        'MPA' => [260101 => ['8,0', '9,0', 0, 0], 260102 => ['7,0', '7,0', 2, 0], 260103 => ['5,5', '6,0', 1, 0], 260104 => ['7,5', '8,0', 0, 0],
                  260105 => ['8,0', '8,5', 1, 0], 260106 => ['7,5', '7,0', 0, 0], 260107 => ['8,5', '9,0', 1, 0], 260108 => ['7,0', '8,0', 0, 0]],
        'GPS' => [260101 => ['9,0', '9,5', 0, 0], 260102 => ['8,0', '8,0', 1, 0], 260103 => ['7,5', '7,0', 0, 0], 260104 => ['8,0', '9,0', 5, 0],
                  260105 => ['7,0', '7,5', 2, 0], 260106 => ['8,0', '7,5', 1, 0], 260107 => ['9,0', '8,5', 0, 0], 260108 => ['7,5', '7,5', 1, 0]],
        'OFP' => [260101 => ['8,5', '8,0', 1, 0], 260102 => ['7,5', '8,5', 0, 0], 260103 => ['7,0', '7,0', 2, 0], 260104 => ['7,5', '7,0', 1, 0],
                  260105 => ['8,0', '7,0', 0, 0], 260106 => ['7,0', '7,5', 3, 0], 260108 => ['9,0', '8,5', 0, 0]],
        'GOC' => [260101 => ['9,0', null, 0, 0], 260102 => ['8,0', null, 1, 0], 260103 => ['6,5', null, 0, 0], 260104 => ['7,0', null, 2, 0],
                  260105 => ['8,5', null, 0, 0], 260106 => ['7,5', null, 1, 0], 260107 => ['9,0', null, 0, 0], 260108 => [null, null, 0, 0]],
        'STF' => [260101 => ['8,5', null, 0, 0], 260102 => ['7,0', null, 0, 0], 260103 => ['7,5', null, 1, 0], 260104 => ['6,0', null, 0, 0],
                  260105 => ['9,0', null, 0, 0], 260106 => ['8,0', null, 2, 0], 260107 => ['8,0', null, 0, 0], 260108 => ['7,0', null, 0, 0]],
    ];
    public const ENCERRAR = ['FAP', 'MPA', 'GPS', 'OFP']; // GOC e STF ficam abertas
    public const DATAS = ['FAP' => ['2026-02-02', '2026-04-30'], 'MPA' => ['2026-02-02', '2026-04-30'], 'GPS' => ['2026-05-04', '2026-07-31'],
        'OFP' => ['2026-05-04', '2026-07-31'], 'GOC' => ['2026-08-03', '2026-11-30'], 'STF' => ['2026-08-03', '2026-11-30']];
    public const TITULACAO = ['prof.laura.demo' => 3, 'prof.marcos.demo' => 2]; // Doutor, Mestre
    public const EAD = 260108; // Heitor: matrícula EaD (mostra o ato do MEC)
    /* a turma 2025 concluída, num segundo curso curto (3 disciplinas de 120 h, sem módulo, exige TCC) */
    public const CURSO2 = 'Pós-graduação em Docência no Ensino Superior';
    public const TURMA2 = 'PDES-2025';
    public const TURMA2R = 'PDES-2025-R'; // nova oferta de Avaliação da Aprendizagem, para quem reprovou
    public const DISC2 = ['EST' => 'Avaliação da Aprendizagem', 'GDA' => 'Didática no Ensino Superior', 'TDA' => 'Tecnologias Educacionais'];
    public const PROF2 = ['EST' => 'prof.laura.demo', 'GDA' => 'prof.marcos.demo', 'TDA' => 'prof.laura.demo'];
    public const PERIODO2 = ['EST' => ['2025-02-03', '2025-04-30'], 'GDA' => ['2025-05-05', '2025-07-31'], 'TDA' => ['2025-08-04', '2025-10-31'], 'EST-R' => ['2025-09-01', '2025-11-14']];
    public const ALUNOS2 = [250101 => ['Irene Imaginária Costa', 1], 250102 => ['João Suposto Pereira', 2]];
    public const LANC2 = [
        'EST'   => [250101 => ['5,0', '6,0', 2, 0], 250102 => ['7,5', '8,0', 1, 0]],
        'GDA'   => [250101 => ['8,5', '9,0', 0, 0], 250102 => ['7,0', '7,5', 3, 0]],
        'TDA'   => [250101 => ['9,0', '8,0', 1, 0], 250102 => ['8,0', '8,5', 0, 0]],
        'EST-R' => [250101 => ['8,0', '8,5', 1, 0]],
    ];
    public const INSTITUICAO = 'Faculdade Horizonte (fictícia)';
    /* o administrador da instalação ganha, enquanto a demonstração dura, um nome fictício de pessoa (como a equipe); o
       nome dele fica guardado e volta na remoção (database/seed_demo.sql) */
    public const ADMIN_FICTICIO = 'Alice Administradora (fictícia)';
    /* os cursos e a faixa de RA da demonstração (a montagem pela tela recusa uma instalação que já tem outros) */
    public const CURSOS_DEMO = ['Pós-graduação em Gestão de Projetos', 'Pós-graduação em Docência no Ensino Superior', 'Graduação Fictícia de Demonstração',
        'Especialização em Gestão Pública Exemplar', 'Especialização em Dados Públicos Exemplar']; // os dois últimos: os nomes de antes da 1.31.0
    /* a instituição fictícia, com o nome de antes da 1.31.0 (a demonstração já montada continua sendo reconhecida) */
    public const INSTITUICOES_DEMO = ['Faculdade Horizonte (fictícia)', 'Escola de Governo Exemplar (fictícia)'];

    /**
     * Os passos, na ordem: chave => [o grupo que a tela mostra, o método].
     * Os grupos são os da janela de progresso, em linguagem de uso.
     */
    public const PASSOS = [
        'equipe'         => ['A instituição e a equipe', 'passoEquipe'],
        'instituicao'    => ['A instituição e a equipe', 'passoInstituicao'],
        'matriz'         => ['Cursos e matriz', 'passoMatriz'],
        'oferta'         => ['Cursos e matriz', 'passoOferta'],
        'alunos'         => ['Alunos e matrículas', 'passoAlunos'],
        'links'          => ['Alunos e matrículas', 'passoLinks'],
        'enturmacao'     => ['Alunos e matrículas', 'passoEnturmacao'],
        'notas_FAP'      => ['Notas e faltas', 'passoNotas'],
        'notas_MPA'      => ['Notas e faltas', 'passoNotas'],
        'notas_GPS'      => ['Notas e faltas', 'passoNotas'],
        'notas_OFP'      => ['Notas e faltas', 'passoNotas'],
        'notas_GOC'      => ['Notas e faltas', 'passoNotas'],
        'notas_STF'      => ['Notas e faltas', 'passoNotas'],
        'encerramento'   => ['Notas e faltas', 'passoEncerramento'],
        'turma2025'      => ['A turma de 2025, já concluída', 'passoTurma2025'],
        'turma2025_alunos' => ['A turma de 2025, já concluída', 'passoTurma2025Alunos'],
        'turma2025_notas' => ['A turma de 2025, já concluída', 'passoTurma2025Notas'],
        'turma2025_fim'  => ['A turma de 2025, já concluída', 'passoTurma2025Conclusao'],
        'modelos'        => ['Documentos', 'passoModelos'],
        'emissoes'       => ['Documentos', 'passoEmissoes'],
        'emissoes_2'     => ['Documentos', 'passoEmissoes2'],
        'arquivos'       => ['Documentos', 'passoArquivos'],
    ];

    public static function falha(string $msg): never
    {
        throw new VcDemoFalha($msg);
    }

    public static function confere(VcDemoNavegador $n, string $passo): void
    {
        if ($n->cortada()) { // Onda 4, Etapa 4: a resposta cortada (Onda 6: sem nova tentativa)
            self::falha("{$passo}: a resposta veio cortada (" . strlen($n->html) . ' bytes: "' . substr(strip_tags($n->html), 0, 80) . '"). Tente de novo.');
        }
        $redirecionou = !$n->seguiu && in_array($n->code, [301, 302, 303], true); // gravar(): o redirecionamento é a resposta
        if (($n->code !== 200 && !$redirecionou) || $n->erroPhp() !== '') {
            self::falha("{$passo}: HTTP {$n->code} {$n->erroPhp()} (parou em " . preg_replace('#^https?://[^/]+#', '', $n->url) . ').');
        }
    }

    /** As senhas fictícias (geradas uma vez, no começo da montagem). */
    public static function novasSenhas(): array
    {
        $s = [];
        foreach (array_keys(self::EQUIPE) as $login) {
            $s[$login] = self::senhaAleatoria();
        }
        foreach (array_keys(self::ALUNOS) as $ra) {
            if ($ra !== self::SEM_SENHA) {
                $s[(string) $ra] = self::senhaAleatoria();
            }
        }
        return $s;
    }

    /* Senha aleatória legível: 16 caracteres sem ambíguos (0/O, 1/l/I) — a mesma regra das ferramentas. */
    public static function senhaAleatoria(int $tamanho = 16): string
    {
        $alfabeto = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789';
        $senha = '';
        for ($i = 0; $i < $tamanho; $i++) {
            $senha .= $alfabeto[random_int(0, strlen($alfabeto) - 1)];
        }
        return $senha;
    }

    /* CPF de demonstração: 9 dígitos aleatórios + os 2 verificadores; gerado na hora, nunca gravado em arquivo. */
    public static function cpfDemo(): string
    {
        do {
            $n = '';
            for ($i = 0; $i < 9; $i++) {
                $n .= random_int(0, 9);
            }
        } while (preg_match('/^(\d)\1{8}$/', $n));
        for ($t = 9; $t < 11; $t++) {
            $s = 0;
            for ($i = 0; $i < $t; $i++) {
                $s += (int) $n[$i] * (($t + 1) - $i);
            }
            $n .= ((10 * $s) % 11) % 10;
        }
        return $n;
    }

    public static function rgDemo(): string
    {
        return (string) random_int(1000000, 9999999);
    }

    /** Roda um passo pela chave. */
    public static function rodar(string $chave, VcDemoContexto $c): void
    {
        if (!isset(self::PASSOS[$chave])) {
            self::falha("passo desconhecido: {$chave}");
        }
        $metodo = self::PASSOS[$chave][1];
        $metodo === 'passoNotas' ? self::passoNotas($c, substr($chave, 6)) : self::$metodo($c);
    }

    /* ------------------------------------------------------------- o que os passos anteriores criaram */
    private static function id(VcDemoContexto $c, string $sql, array $p, string $oQue): int
    {
        return (int) $c->um($sql, $p) ?: self::falha("{$oQue} não existe (o passo anterior não terminou).");
    }

    private static function polo(VcDemoContexto $c): int
    {
        return self::id($c, 'SELECT id FROM rm_polos WHERE nome = ?', [self::POLO], 'o polo');
    }

    private static function curso(VcDemoContexto $c): int
    {
        return self::id($c, 'SELECT id FROM rm_cursos WHERE nome = ?', [self::CURSO], 'o curso');
    }

    private static function grade(VcDemoContexto $c): int
    {
        return self::id($c, 'SELECT id FROM rm_cursos_grades WHERE curso_id = ? AND versao = 1', [self::curso($c)], 'a grade');
    }

    private static function materia(VcDemoContexto $c, string $k): int
    {
        return self::id($c, 'SELECT id FROM rm_materias WHERE nome = ?', [self::DISC[$k]], "a matéria {$k}");
    }

    private static function oferta(VcDemoContexto $c, string $k): int
    {
        return self::id($c, 'SELECT id FROM rm_professores_materias WHERE curso_id = ? AND ano = ? AND codmat = ?', [self::curso($c), self::ANO, self::materia($c, $k)], "a oferta {$k}");
    }

    private static function aluno(VcDemoContexto $c, int $ra): int
    {
        return self::id($c, 'SELECT id FROM col_usuarios WHERE rm = ?', [$ra], "o aluno {$ra}");
    }

    private static function usuarioId(VcDemoContexto $c, string $login): int
    {
        return (int) $c->um('SELECT id FROM adms_usuarios WHERE usuario = ?', [$login]);
    }

    private static function curso2(VcDemoContexto $c): int
    {
        return self::id($c, 'SELECT id FROM rm_cursos WHERE nome = ? AND exige_tcc = 1', [self::CURSO2], 'o curso 2025');
    }

    private static function of2(VcDemoContexto $c, string $k): int
    {
        if ($k === 'EST-R') {
            return self::id($c, 'SELECT id FROM rm_professores_materias WHERE curso_id = ? AND ano = 2025 AND codtur = ?', [self::curso2($c), self::TURMA2R], 'a oferta EST-R');
        }
        $mat = self::id($c, 'SELECT id FROM rm_materias WHERE nome = ?', [self::DISC2[$k]], "a matéria {$k}");
        return self::id($c, 'SELECT id FROM rm_professores_materias WHERE curso_id = ? AND ano = 2025 AND codmat = ? AND codtur = ?', [self::curso2($c), $mat, self::TURMA2], "a oferta 2025 {$k}");
    }

    private static function vazio(): array
    {
        return ['nome_social' => '', 'cpf' => '', 'telefone' => '', 'celular' => '', 'rg' => '', 'rg_org' => '', 'rg_uf' => '', 'nasc_data' => '', 'nasc_local' => '',
            'nasc_uf' => '', 'nasc_nac' => '', 'nome_mae' => '', 'nome_pai' => '', 'cep' => '', 'endereco' => '', 'numero' => '', 'complemento' => '', 'bairro' => '', 'cidade' => '', 'uf' => ''];
    }

    private static function pessoais(int $ra): array
    {
        return isset(self::PESSOAIS[$ra]) ? ['nasc_data' => self::PESSOAIS[$ra][0], 'nasc_local' => self::PESSOAIS[$ra][1], 'nasc_uf' => self::PESSOAIS[$ra][2], 'nasc_nac' => 'Brasileira',
            'nome_mae' => self::PESSOAIS[$ra][3], 'nome_pai' => self::PESSOAIS[$ra][4], 'cpf' => self::cpfDemo(), 'rg' => self::rgDemo(), 'rg_org' => 'SSP', 'rg_uf' => 'DF'] : [];
    }

    /* ------------------------------------------------------------- 1. administrador: polo, curso, equipe */
    public static function passoEquipe(VcDemoContexto $c): void
    {
        /* o administrador com nome fictício (o nome real fica guardado, uma vez só, e volta na remoção) */
        $adm0 = $c->pdo->query("SELECT u.id, u.nome FROM adms_usuarios u JOIN adms_niveis_acessos n ON n.id = u.adms_niveis_acesso_id WHERE n.papel = 'administrador' ORDER BY u.id LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if ($adm0 && !$c->um("SELECT valor FROM adms_instalacao_estado WHERE chave = 'demonstracao.admin_nome'")) {
            $c->pdo->prepare("INSERT INTO adms_instalacao_estado (chave, valor) VALUES ('demonstracao.admin_nome', ?)")->execute([json_encode(['id' => (int) $adm0['id'], 'nome' => (string) $adm0['nome']], JSON_UNESCAPED_UNICODE)]);
            $c->pdo->prepare('UPDATE adms_usuarios SET nome = ? WHERE id = ?')->execute([self::ADMIN_FICTICIO, (int) $adm0['id']]);
        }
        /* D-268: a demonstração é SEM módulo */
        ($c->um("SELECT valor FROM adms_parametros WHERE chave = 'matriz.modulos'") ?? '0') === '0' ?: self::falha('a demonstração é sem módulo: ponha "Organizar a matriz em módulos" em Não (Configurações da instituição) e rode de novo.');
        $adm = $c->adm();
        $p = $c->painel();
        if (!$c->um('SELECT id FROM rm_polos WHERE nome = ?', [self::POLO])) {
            $adm->req($p . 'polos/cadastrar', ['nome' => self::POLO, 'cidade' => 'Cidade Exemplo', 'uf' => 'DF', 'endereco' => 'Rua das Demonstrações, 100', 'instituicao_parceira' => '', 'situacao' => '1', 'SalvarPolo' => 'Salvar']);
            self::confere($adm, 'cadastrar o polo');
        }
        $polo = (int) $c->um('SELECT id FROM rm_polos WHERE nome = ?', [self::POLO]) ?: self::falha('o polo não foi criado: ' . $adm->alerta());
        /* 1.18.2 (D-492): a identidade do curso, fictícia, para os documentos e os vídeos saírem completos */
        if (!$c->um('SELECT id FROM rm_cursos WHERE nome = ?', [self::CURSO])) {
            $adm->req($p . 'cursos/cadastrar', ['nome' => self::CURSO, 'descricao' => 'Curso fictício de pós-graduação lato sensu (360 h), para demonstração.', 'periodo' => '1', 'modalidade_id' => '1', 'localidade' => $polo, 'filial' => '1',
                'nivel' => 'Especialização', 'abreviatura' => 'PGP', 'nome_extenso' => 'Gestão de Projetos', 'cabecalho_documento' => 'Curso de Pós-graduação em Gestão de Projetos (fictício)', 'CadCurso' => 'Cadastrar']);
            self::confere($adm, 'cadastrar o curso');
        }
        $curso = (int) $c->um('SELECT id FROM rm_cursos WHERE nome = ?', [self::CURSO]) ?: self::falha('o curso não foi criado: ' . $adm->alerta());
        $c->um("SELECT id FROM rm_cursos WHERE id = ? AND nivel <> '' AND abreviatura <> '' AND nome_extenso <> '' AND cabecalho_documento <> ''", [$curso]) ?: self::falha('o curso ficou sem a identidade (nível, sigla, nome por extenso, cabeçalho)');
        foreach (self::EQUIPE as $login => [$nome, $papel, $genero]) {
            if ($c->um('SELECT id FROM adms_usuarios WHERE usuario = ?', [$login])) {
                continue;
            }
            $nivel = (int) $c->um('SELECT id FROM adms_niveis_acessos WHERE papel = ?', [$papel]) ?: self::falha("o nível do papel {$papel} não existe (aplique as migrations)");
            $adm->req($p . 'usuarios/cadastrar', [
                'nome' => $nome, 'genero_id' => (string) $genero, 'usuario' => $login, 'senha' => (string) $c->senhas[$login],
                'email' => $login . '@exemplo.invalid', 'adms_niveis_acesso_id' => (string) $nivel, 'adms_sits_usuario_id' => '1',
                'imagem_antiga' => '', 'imagem_nova' => new CURLStringFile('', '', 'application/octet-stream'), 'CadUsuario' => 'Cadastrar',
            ], true);
            self::confere($adm, "cadastrar o usuário {$login}");
            $c->um('SELECT id FROM adms_usuarios WHERE usuario = ?', [$login]) ?: self::falha("usuário {$login} não criado: " . $adm->alerta());
        }
        /* a coordenação do curso, pela tela do curso (o modal "Adicionar coordenador") */
        $coordId = self::usuarioId($c, 'coord.demo');
        if (!$c->um('SELECT id FROM rm_cursos_coordenacao WHERE curso_id = ? AND usuario_id = ? AND situacao = 1', [$curso, $coordId])) {
            $adm->req($p . 'cursos/cadastrar-usuario/' . $curso, ['curso_id' => (string) $curso, 'usuario_id' => (string) $coordId, 'posicao_id' => '1', 'CadCoordenadorCurso' => 'Salvar']);
            self::confere($adm, 'indicar a coordenadora do curso');
            $c->um('SELECT id FROM rm_cursos_coordenacao WHERE curso_id = ? AND usuario_id = ? AND situacao = 1', [$curso, $coordId]) ?: self::falha('a coordenação do curso não foi gravada: ' . $adm->alerta());
        }
        $c->log('Polo, curso e equipe (um usuário de cada perfil abaixo do administrador: direção, supervisão, secretaria, coordenação e 2 professores) cadastrados pelo administrador; a coordenadora indicada no curso.');
    }

    /* ------------------------------------------------------------- 1b. administrador: instituição, atos, titulação */
    public static function passoInstituicao(VcDemoContexto $c): void
    {
        $adm = $c->adm();
        $p = $c->painel();
        $pdo = $c->pdo;
        if (!$c->um("SELECT id FROM adms_empresas WHERE instituicao = 1 AND logo IS NOT NULL AND nome = ?", [self::INSTITUICAO])) {
            /* logos que nenhuma linha usa (sobra de uma demonstração apagada) saem antes */
            $logosEmUso = array_column($pdo->query("SELECT logo FROM adms_empresas WHERE logo IS NOT NULL")->fetchAll(PDO::FETCH_ASSOC), 'logo');
            foreach (glob(dirname(__DIR__, 2) . '/assets/instituicao/logo_*.png') ?: [] as $arq) {
                if (!in_array(basename($arq), $logosEmUso, true)) {
                    @unlink($arq);
                }
            }
            $logo = imagecreatetruecolor(240, 120);
            /* o logo fictício: neutro (um horizonte: o sol sobre a linha), as iniciais e "fictícia" */
            imagefill($logo, 0, 0, imagecolorallocate($logo, 38, 70, 83));
            imagefilledellipse($logo, 60, 66, 70, 70, imagecolorallocate($logo, 244, 162, 97));
            imagefilledrectangle($logo, 14, 66, 106, 104, imagecolorallocate($logo, 38, 70, 83));
            imagefilledrectangle($logo, 14, 66, 106, 69, imagecolorallocate($logo, 233, 236, 239));
            imagestring($logo, 5, 120, 40, 'FH', imagecolorallocate($logo, 255, 255, 255));
            imagestring($logo, 3, 120, 64, 'ficticia', imagecolorallocate($logo, 200, 215, 220));
            $logoArq = (string) tempnam(sys_get_temp_dir(), 'logo');
            imagepng($logo, $logoArq);
            imagedestroy($logo);
            $adm->req($p . 'dados-instituicao/editar', [
                'nome' => self::INSTITUICAO, 'razao_social' => 'Associação Horizonte de Educação (fictícia)',
                'cnpj' => '11.222.333/0001-81', 'codigo_emec' => '', 'site' => '', 'cep' => '88000-000', 'logradouro' => 'Rua das Demonstrações',
                'numero' => '100', 'complemento' => '', 'bairro' => 'Centro', 'cidade' => 'Cidade Exemplo', 'uf' => 'DF',
                'logo' => new CURLFile($logoArq, 'image/png', 'logo_ficticio.png'), 'SalvarInstituicao' => 'Salvar',
            ], true);
            @unlink($logoArq);
            self::confere($adm, 'dados da instituição');
            $c->um("SELECT id FROM adms_empresas WHERE instituicao = 1 AND logo IS NOT NULL") ?: self::falha('dados da instituição: ' . $adm->alerta());
        }
        foreach ([
            ['orgao' => 'Conselho Estadual de Educação (fictício)', 'tipo' => 'Resolução', 'numero' => '999/2024', 'data_ato' => '2024-03-15', 'veiculo_publicacao' => 'Diário Oficial do Estado (fictício)',
             'data_publicacao' => '2024-03-20', 'vigencia_inicio' => '2024-03-20', 'vigencia_fim' => '2029-03-19', 'modalidade_id' => '1', 'situacao' => '1', 'observacao' => 'Ato fictício da demonstração (presencial).'],
            ['orgao' => 'Ministério da Educação', 'tipo' => 'Portaria', 'numero' => '9999/2025', 'data_ato' => '2025-01-10', 'veiculo_publicacao' => 'Diário Oficial da União',
             'data_publicacao' => '2025-01-13', 'vigencia_inicio' => '2025-01-13', 'vigencia_fim' => '2029-01-12', 'modalidade_id' => '2', 'situacao' => '1', 'observacao' => 'Ato FICTÍCIO da demonstração (EaD); número inventado.'],
        ] as $ato) {
            if ($c->um('SELECT id FROM rm_atos_legais WHERE numero = ?', [$ato['numero']])) {
                continue;
            }
            $adm->req($p . 'atos-legais/cadastrar', $ato + ['SalvarAto' => 'Salvar']);
            self::confere($adm, "ato {$ato['numero']}");
            $c->um('SELECT id FROM rm_atos_legais WHERE numero = ?', [$ato['numero']]) ?: self::falha("ato {$ato['numero']}: " . $adm->alerta());
        }
        foreach (self::TITULACAO as $login => $tit) {
            $idU = self::usuarioId($c, $login);
            if ((int) $c->um('SELECT titulacao_id FROM adms_usuarios WHERE id = ?', [$idU]) === $tit) {
                continue;
            }
            $adm->req($p . "usuarios/editar/{$idU}");
            $form = $adm->form();
            unset($form['senha']);
            $adm->req($p . "usuarios/editar/{$idU}", array_merge($form, ['titulacao_id' => (string) $tit, 'imagem_nova' => new CURLStringFile('', '', 'application/octet-stream'), 'EditUsuario' => 'Salvar']), true);
            self::confere($adm, "titulação de {$login}");
            (int) $c->um('SELECT titulacao_id FROM adms_usuarios WHERE id = ?', [$idU]) === $tit ?: self::falha("titulação de {$login}: " . $adm->alerta());
        }
        $c->log('Administrador: dados e logo da instituição fictícia, 2 atos legais fictícios (presencial e EaD) e a titulação dos professores.');
    }

    /* ------------------------------------------------------------- 2. secretaria: ano, matérias, matriz */
    public static function passoMatriz(VcDemoContexto $c): void
    {
        $sec = $c->entrar('secretaria.demo');
        $p = $c->painel();
        $curso = self::curso($c);
        if (!$c->um('SELECT id FROM adms_periodos_letivos WHERE ano = ?', [self::ANO])) {
            $sec->req($p . 'anos-letivos/cadastrar', ['ano' => (string) self::ANO, 'SalvarAnoLetivo' => 'Salvar']);
            self::confere($sec, 'cadastrar o ano letivo');
            $c->um('SELECT id FROM adms_periodos_letivos WHERE ano = ?', [self::ANO]) ?: self::falha('ano letivo não criado: ' . $sec->alerta());
        }
        $materia = [];
        foreach (self::DISC as $k => $nome) {
            if (!$c->um('SELECT id FROM rm_materias WHERE nome = ?', [$nome])) {
                $sec->req($p . 'materias/cadastrar', ['abreviacao' => self::CODIGO[$k], 'nome' => $nome, 'carga_horaria' => '60', 'descricao' => 'Disciplina fictícia de demonstração', 'CadMaterias' => 'Cadastrar']);
                self::confere($sec, "cadastrar a matéria {$k}");
            }
            $materia[$k] = (int) $c->um('SELECT id FROM rm_materias WHERE nome = ?', [$nome]) ?: self::falha("matéria {$k} não criada: " . $sec->alerta());
        }
        if (!$c->um('SELECT id FROM rm_cursos_grades WHERE curso_id = ? AND versao = 1', [$curso])) {
            $sec->req($p . 'matriz/cadastrar-grade', ['curso_id' => $curso, 'versao' => '1', 'descricao' => 'Grade 2026 — 6 disciplinas, 360 h', 'SalvarGrade' => 'Salvar']);
            self::confere($sec, 'cadastrar a grade');
        }
        $grade = (int) $c->um('SELECT id FROM rm_cursos_grades WHERE curso_id = ? AND versao = 1', [$curso]) ?: self::falha('grade não criada: ' . $sec->alerta());
        foreach (self::DISC as $k => $nome) {
            if ($c->um('SELECT id FROM uni_rh_cursos_disciplinas_grade WHERE grade_id = ? AND materia_id = ?', [$grade, $materia[$k]])) {
                continue;
            }
            $sec->req($p . "matriz/adicionar-disciplina/{$grade}", ['materia_id' => $materia[$k], 'cargahoraria' => '']);
            self::confere($sec, "incluir {$k} na matriz");
            $c->um('SELECT id FROM uni_rh_cursos_disciplinas_grade WHERE grade_id = ? AND materia_id = ?', [$grade, $materia[$k]]) ?: self::falha("{$k} fora da matriz: " . $sec->alerta());
        }
        $c->log('Secretaria: ano letivo, 6 matérias e a matriz (a grade com 6 disciplinas, 360 h).');
        if ((int) $c->um('SELECT situacao FROM rm_cursos_grades WHERE id = ?', [$grade]) !== 3) {
            $c->adm()->req($p . "matriz/situacao-grade/{$grade}", ['situacao' => '3', 'observacao' => 'Matriz conferida e aprovada para a turma 2026 (demonstração).']);
            (int) $c->um('SELECT situacao FROM rm_cursos_grades WHERE id = ?', [$grade]) === 3 ?: self::falha('a grade não foi finalizada: ' . $c->adm()->alerta());
        }
        $c->log('Administrador finalizou a grade.');
    }

    /* ------------------------------------------------------------- 3. oferta 2026 e professores */
    public static function passoOferta(VcDemoContexto $c): void
    {
        $sec = $c->entrar('secretaria.demo');
        $p = $c->painel();
        $curso = self::curso($c);
        $grade = self::grade($c);
        $polo = self::polo($c);
        if (!$c->um('SELECT id FROM rm_professores_materias WHERE curso_id = ? AND ano = ? LIMIT 1', [$curso, self::ANO])) {
            $sec->req($p . "disciplinas/gerar-oferta?grade={$grade}", ['grade_id' => $grade, 'linhas' => array_map('intval', array_column($c->pdo->query('SELECT id FROM uni_rh_cursos_disciplinas_grade WHERE grade_id = ' . (int) $grade)->fetchAll(), 'id')),
                'ano' => (string) self::ANO, 'codtur' => self::TURMA, 'codfilial' => $polo, 'modalidade_id' => '', 'encontros' => (string) self::ENCONTROS, 'GerarOferta' => '1']);
            self::confere($sec, 'gerar a oferta');
        }
        foreach (self::DISC as $k => $nome) {
            $id = (int) $c->um('SELECT id FROM rm_professores_materias WHERE curso_id = ? AND ano = ? AND codmat = ?', [$curso, self::ANO, self::materia($c, $k)]) ?: self::falha("oferta {$k} não gerada: " . $sec->alerta());
            if ((string) $c->um('SELECT data_inicio FROM rm_professores_materias WHERE id = ?', [$id]) !== self::DATAS[$k][0]) {
                $sec->req($p . "disciplinas/editar/{$id}");
                $sec->req($p . "disciplinas/editar/{$id}", array_merge($sec->form(), ['data_inicio' => self::DATAS[$k][0], 'data_fim' => self::DATAS[$k][1], 'EditDisciplina' => 'Salvar']));
                self::confere($sec, "período de {$k}");
                (string) $c->um('SELECT data_inicio FROM rm_professores_materias WHERE id = ?', [$id]) === self::DATAS[$k][0] ?: self::falha("período de {$k}: " . $sec->alerta());
            }
            $prof = self::usuarioId($c, self::PROFESSOR[$k]);
            if (!$c->um('SELECT id FROM rm_professores_mat_liga WHERE rm_prof_mat_id = ? AND usuario_id = ? AND situacao = 1', [$id, $prof])) {
                $sec->req($p . 'disciplinas/adiciona-professor/' . self::ANO, ['rm_prof_mat_id' => $id, 'professores' => [$prof], 'AdicionaProfessor' => 'Salvar']);
                self::confere($sec, "professor de {$k}");
                $c->um('SELECT id FROM rm_professores_mat_liga WHERE rm_prof_mat_id = ? AND usuario_id = ? AND situacao = 1', [$id, $prof]) ?: self::falha("professor de {$k} não ligado: " . $sec->alerta());
            }
        }
        $c->log('Oferta 2026 gerada pela secretaria (6 disciplinas, ' . self::ENCONTROS . ' encontros); a secretaria pôs o id do Moodle e o professor de cada uma.');
    }

    /* ------------------------------------------------------------- 4. alunos: cadastro e vínculo */
    public static function passoAlunos(VcDemoContexto $c): void
    {
        $sec = $c->entrar('secretaria.demo');
        $p = $c->painel();
        $curso = self::curso($c);
        $interno = (int) $c->um('SELECT modulo_id FROM uni_rh_cursos_disciplinas_grade WHERE grade_id = ? LIMIT 1', [self::grade($c)]); // o módulo interno (só para o vínculo)
        foreach (self::ALUNOS as $ra => [$nome, $genero]) {
            if (!$c->um('SELECT id FROM col_usuarios WHERE rm = ?', [$ra])) {
                $sec->req($p . 'academicos/cadastrar', ['nome' => $nome, 'rm' => (string) $ra, 'email' => 'aluno.' . $ra . '@exemplo.invalid', 'genero_id' => (string) $genero, 'col_sits_usuario_id' => '1', 'CadUsuarioAcad' => 'Cadastrar'] + self::pessoais($ra) + self::vazio());
                self::confere($sec, "cadastrar o aluno {$ra}");
            }
            $id = (int) $c->um('SELECT id FROM col_usuarios WHERE rm = ?', [$ra]) ?: self::falha("aluno {$ra} não criado: " . $sec->alerta());
            if ($c->um('SELECT id FROM rm_matriculas_curso WHERE usuario_id = ? AND curso_id = ?', [$id, $curso])) {
                continue;
            }
            $sec->req($p . "academicos/vincular/{$id}", ['ano' => (string) self::ANO, 'modulo_id' => $interno, 'forma_ingresso_id' => '1', 'data_ingresso' => '2026-02-02',
                'polo_id' => '', 'modalidade_id' => $ra === self::EAD ? '2' : '', 'previsao_conclusao' => '2027-06-30']);
            self::confere($sec, "vincular {$ra}");
        }
        $c->log('Secretaria: 8 alunos cadastrados e vinculados ao curso (grade 1, 2026).');
    }

    /* ------------------------------------------------------------- 4b. o link de acesso: o aluno define a senha */
    public static function passoLinks(VcDemoContexto $c): void
    {
        $sec = $c->entrar('secretaria.demo');
        $p = $c->painel();
        foreach (self::ALUNOS as $ra => $x) {
            if ($ra === self::SEM_SENHA) {
                continue;
            }
            $id = self::aluno($c, $ra);
            if (str_starts_with((string) $c->um('SELECT senha FROM col_usuarios WHERE id = ?', [$id]), '$2')) {
                continue; // já definiu a senha (o passo repetido)
            }
            $sec->req($p . "academicos/enviar-link/{$id}", ['EnviarLink' => '1']);
            self::confere($sec, "enviar link a {$ra}");
            if (!preg_match('#id="linkAcessoAluno" readonly value="([^"]+)"#', $sec->html, $m)) {
                self::falha("o link de acesso de {$ra} não apareceu na tela: " . $sec->alerta());
            }
            $senha = (string) $c->senhas[(string) $ra];
            $alu = new VcDemoNavegador();
            /* E7 (D-70): o link traz o token no fragmento (#t=…); aqui se faz o que o JavaScript da página faz — POST do token */
            preg_match('/#t=([A-Za-z0-9]+)/', html_entity_decode($m[1]), $tk) ?: self::falha("o link de acesso de {$ra} não traz o token no fragmento.");
            $alu->req($c->portal() . 'login/atualizar-senha', ['token' => $tk[1], 'senha' => $senha, 'senha_repetido' => $senha, 'AtualizaSenha' => 'Salvar']);
            self::confere($alu, "o aluno {$ra} definir a senha");
            $alu->req($c->portal() . 'login/acesso', ['usuario' => (string) $ra, 'senha' => $senha, 'SendLogin' => 'Acessar']);
            if (str_contains($alu->html, 'name="SendLogin"')) {
                self::falha("o aluno {$ra} não entrou no portal com a senha definida pelo link.");
            }
        }
        $c->log('7 alunos definiram a senha pelo link; ' . self::SEM_SENHA . ' segue sem senha.');
    }

    /* ------------------------------------------------------------- 4c. enturmação sugerida e confirmada */
    public static function passoEnturmacao(VcDemoContexto $c): void
    {
        $sec = $c->entrar('secretaria.demo');
        $p = $c->painel();
        $curso = self::curso($c);
        $ofertas = [];
        foreach (array_keys(self::DISC) as $k) {
            $ofertas[$k] = self::oferta($c, $k);
        }
        foreach (self::ALUNOS as $ra => $x) {
            if ((int) $c->um('SELECT COUNT(*) FROM portal_disciplinas_alunos WHERE rm = ? AND rm_prof_mat_id IN (' . implode(',', $ofertas) . ')', [(string) $ra]) > 0) {
                continue; // já enturmado (o passo repetido)
            }
            $sec->req($p . "portal-alunos/visualizar/{$ra}?ano=" . self::ANO);
            preg_match_all('#name="ofertas\[\]" value="(\d+)"[^>]*? checked#', $sec->html, $mm);
            $marcadas = array_map('intval', $mm[1]);
            count($marcadas) === 6 ?: self::falha("enturmação sugerida de {$ra}: esperava as 6 ofertas abertas marcadas, achei " . count($marcadas) . '.');
            if ($ra === self::AJUSTE[0]) {
                $marcadas = array_values(array_diff($marcadas, [$ofertas[self::AJUSTE[1]]]));
            }
            $pos = (int) $c->um('SELECT me.id FROM rm_matriculas_ensino me JOIN rm_matriculas_curso mc ON mc.id = me.matricula_curso_id WHERE me.usuario_id = ? AND mc.curso_id = ? AND me.ano = ?', [self::aluno($c, $ra), $curso, self::ANO]);
            $sec->req($p . 'portal-alunos/confirmar-enturmacao/' . $pos, ['ofertas' => $marcadas]);
            self::confere($sec, "confirmar a enturmação de {$ra}");
        }
        $enturmados = (int) $c->um('SELECT COUNT(*) FROM portal_disciplinas_alunos WHERE rm_prof_mat_id IN (' . implode(',', $ofertas) . ')');
        $enturmados === 47 ?: self::falha("enturmação: esperava 47 (8 alunos × 6 − 1 ajuste), achei {$enturmados}.");
        $c->log('Enturmação sugerida e confirmada pela secretaria (47 enturmações; Gabriela fora de Planejamento e Cronograma).');
    }

    /* ------------------------------------------------------------- 5. professores: atividades, notas e faltas */
    /** As compensações (a SECRETARIA lança cada uma, com tipo, justificativa e período). */
    private static function compensar(VcDemoContexto $c, int $of, array $porRa, string $rot): void
    {
        $ano = (int) $c->um('SELECT ano FROM rm_professores_materias WHERE id = ?', [$of]);
        foreach ($porRa as $ra => $q) {
            if ((int) $q < 1 || $c->um('SELECT id FROM rm_faltas_compensacoes WHERE rm_prof_mat_id = ? AND rm = ? LIMIT 1', [$of, (string) $ra])) {
                continue;
            }
            $sec = $c->entrar('secretaria.demo');
            $sec->req($c->painel() . "faltas/compensar/{$of}", ['rm' => (string) $ra, 'quantidade' => (string) $q, 'tipo' => 'atestado',
                'justificativa' => 'Atestado médico entregue na secretaria (demonstração, fictício).', 'inicio' => "{$ano}-04-06", 'fim' => "{$ano}-04-07"]);
            str_contains($sec->alerta(), 'Compensação lançada') ?: self::falha("compensação de {$ra} em {$rot}: " . $sec->alerta());
        }
    }

    /** Duas atividades, as notas e as faltas de uma oferta, pela professora ou pelo professor dela. */
    private static function lancar(VcDemoContexto $c, VcDemoNavegador $prof, int $of, array $porAluno, array $atividades, string $rot): void
    {
        $p = $c->painel();
        $pdo = $c->pdo;
        $ano = (int) $c->um('SELECT ano FROM rm_professores_materias WHERE id = ?', [$of]);
        foreach ($atividades as $n => [$nomeAtiv, $dia]) {
            if ($c->um('SELECT id FROM rm_disciplina_atividade WHERE rm_prof_mat_id = ? AND nomeAtividade = ?', [$of, $nomeAtiv])) {
                continue;
            }
            $inicio = str_starts_with($dia, '-') ? $ano . $dia : $dia;
            $prof->req($p . "atividades/cadastrar/{$of}?tipo=5", ['rm_prof_mat_id' => $of, 'tipo' => 5, 'peso' => 10, 'nomeAtividade' => $nomeAtiv,
                'descricao' => 'Atividade fictícia de demonstração', 'entrega_arquivo' => 2, 'data_inicial' => $inicio, 'data_final' => $ano . '-03-30', 'CadAtividadeNota' => 'Salvar']);
            self::confere($prof, "criar a atividade {$n} de {$rot}");
        }
        $ativ = array_map('intval', $pdo->query("SELECT id FROM rm_disciplina_atividade WHERE rm_prof_mat_id = {$of} ORDER BY data_inicial, id")->fetchAll(PDO::FETCH_COLUMN));
        count($ativ) === 2 ?: self::falha("{$rot}: esperava 2 atividades, achei " . count($ativ));
        $faltas = ['faltas' => [], 'compensadas' => []];
        foreach ($porAluno as $ra => [$n1, $n2, $f, $q]) {
            foreach ([$n1, $n2] as $i => $nota) {
                if ($nota === null) {
                    continue;
                }
                $linha = $pdo->prepare('SELECT id, nota FROM rm_disciplina_atividade_notas WHERE atividade_id = ? AND rm = ?');
                $linha->execute([$ativ[$i], (string) $ra]);
                $l = $linha->fetch(PDO::FETCH_ASSOC) ?: [];
                if (isset($l['nota']) && $l['nota'] !== null && abs((float) $l['nota'] - (float) str_replace(',', '.', $nota)) < 0.0001) {
                    continue; // a nota já está lançada (o passo repetido)
                }
                $prof->req($p . 'atividades/edit-notas', ['id' => (int) ($l['id'] ?? 0), 'nota' => $nota, 'action' => 'edit']);
                $j = json_decode($prof->html, true);
                (is_array($j) && ($j['erro'] ?? false) === true) ?: self::falha("nota {$nota} de {$ra} em {$rot}: " . mb_substr(strip_tags($prof->html), 0, 200));
            }
            $faltas['faltas'][$ra] = $f;
            $faltas['compensadas'][$ra] = $q;
        }
        $prof->req($p . "faltas/editar/{$of}", $faltas);
        self::confere($prof, "faltas de {$rot}");
        str_contains($prof->alerta(), 'Faltas salvas') ?: self::falha("faltas de {$rot}: " . $prof->alerta());
        self::compensar($c, $of, $faltas['compensadas'], $rot);
    }

    public static function passoNotas(VcDemoContexto $c, string $k): void
    {
        isset(self::LANCAMENTOS[$k]) ?: self::falha("disciplina desconhecida: {$k}");
        self::lancar($c, $c->entrar(self::PROFESSOR[$k]), self::oferta($c, $k), self::LANCAMENTOS[$k],
            [1 => ['Questionário da disciplina', '2026-03-02'], 2 => ['Trabalho final da disciplina', '2026-03-16']], $k);
        $c->log("Professores: as 2 atividades, as notas (com vírgula) e as faltas de {$k} lançadas pelas telas.");
    }

    private static function encerrada(VcDemoContexto $c, int $of): bool
    {
        return (bool) $c->um('SELECT id FROM rm_professores_materias WHERE id = ? AND encerrada_em IS NOT NULL', [$of]);
    }

    /* ------------------------------------------------------------- 6. encerramento */
    public static function passoEncerramento(VcDemoContexto $c): void
    {
        $sec = $c->entrar('secretaria.demo');
        $p = $c->painel();
        foreach (self::ENCERRAR as $k) {
            $of = self::oferta($c, $k);
            if (self::encerrada($c, $of)) {
                continue;
            }
            $sec->req($p . "disciplinas/encerrar/{$of}");
            str_contains($sec->html, 'name="ConfirmarEncerramento"') ?: self::falha("prévia do encerramento de {$k} sem o botão: " . $sec->alerta());
            $sec->req($p . "disciplinas/encerrar/{$of}", ['ConfirmarEncerramento' => '1']);
            str_contains($sec->url, "medias/final/{$of}") ?: self::falha("encerrar {$k}: " . $sec->alerta());
        }
        $c->log('Secretaria encerrou 4 disciplinas (FAP, MPA, GPS, OFP); Liderança de Equipes e Trabalho de Conclusão de Curso seguem abertas.');
    }

    /* ------------------------------------------------------------- 6b. a turma 2025: curso, matriz, oferta, alunos */
    public static function passoTurma2025(VcDemoContexto $c): void
    {
        $adm = $c->adm();
        $sec = $c->entrar('secretaria.demo');
        $p = $c->painel();
        $pdo = $c->pdo;
        $polo = self::polo($c);
        if (!$c->um('SELECT id FROM rm_cursos WHERE nome = ? AND exige_tcc = 1', [self::CURSO2])) {
            $adm->req($p . 'cursos/cadastrar', ['nome' => self::CURSO2, 'descricao' => 'Curso fictício de pós-graduação lato sensu (360 h), turma 2025 já concluída.', 'periodo' => '1', 'modalidade_id' => '1', 'localidade' => $polo, 'filial' => '1', 'exige_tcc' => '1',
                'nivel' => 'Especialização', 'abreviatura' => 'PDES', 'nome_extenso' => 'Docência no Ensino Superior', 'cabecalho_documento' => 'Curso de Pós-graduação em Docência no Ensino Superior (fictício)', 'CadCurso' => 'Cadastrar']);
            self::confere($adm, 'cadastrar o curso 2025');
        }
        $curso2 = (int) $c->um('SELECT id FROM rm_cursos WHERE nome = ? AND exige_tcc = 1', [self::CURSO2]) ?: self::falha('curso 2025 não criado: ' . $adm->alerta());
        $c->um("SELECT id FROM rm_cursos WHERE id = ? AND nivel <> '' AND abreviatura <> '' AND nome_extenso <> '' AND cabecalho_documento <> ''", [$curso2]) ?: self::falha('o curso 2025 ficou sem a identidade');
        if (!$c->um('SELECT id FROM adms_periodos_letivos WHERE ano = 2025')) {
            $sec->req($p . 'anos-letivos/cadastrar', ['ano' => '2025', 'SalvarAnoLetivo' => 'Salvar']);
            self::confere($sec, 'ano letivo 2025');
        }
        $mat2 = [];
        foreach (self::DISC2 as $k => $nome) {
            if (!$c->um('SELECT id FROM rm_materias WHERE nome = ?', [$nome])) {
                $sec->req($p . 'materias/cadastrar', ['abreviacao' => self::CODIGO[$k], 'nome' => $nome, 'carga_horaria' => '120', 'descricao' => 'Disciplina fictícia de demonstração', 'CadMaterias' => 'Cadastrar']);
            }
            $mat2[$k] = (int) $c->um('SELECT id FROM rm_materias WHERE nome = ?', [$nome]) ?: self::falha("matéria {$k}: " . $sec->alerta());
        }
        if (!$c->um('SELECT id FROM rm_cursos_grades WHERE curso_id = ? AND versao = 1', [$curso2])) {
            $sec->req($p . 'matriz/cadastrar-grade', ['curso_id' => $curso2, 'versao' => '1', 'descricao' => 'Grade 2025 — 3 disciplinas, 360 h', 'SalvarGrade' => 'Salvar']);
        }
        $grade2 = (int) $c->um('SELECT id FROM rm_cursos_grades WHERE curso_id = ? AND versao = 1', [$curso2]) ?: self::falha('grade 2025');
        foreach (self::DISC2 as $k => $nome) {
            if (!$c->um('SELECT id FROM uni_rh_cursos_disciplinas_grade WHERE grade_id = ? AND materia_id = ?', [$grade2, $mat2[$k]])) {
                $sec->req($p . "matriz/adicionar-disciplina/{$grade2}", ['materia_id' => $mat2[$k], 'cargahoraria' => '']);
            }
        }
        $interno2 = (int) $c->um('SELECT modulo_id FROM uni_rh_cursos_disciplinas_grade WHERE grade_id = ? LIMIT 1', [$grade2]) ?: self::falha('matriz 2025 sem disciplinas');
        if ((int) $c->um('SELECT situacao FROM rm_cursos_grades WHERE id = ?', [$grade2]) !== 3) {
            $adm->req($p . "matriz/situacao-grade/{$grade2}", ['situacao' => '3', 'observacao' => 'Matriz aprovada para a turma 2025 (demonstração).']);
            (int) $c->um('SELECT situacao FROM rm_cursos_grades WHERE id = ?', [$grade2]) === 3 ?: self::falha('grade 2025 não finalizada');
        }
        if (!$c->um('SELECT id FROM rm_professores_materias WHERE curso_id = ? AND ano = 2025 AND codtur = ? LIMIT 1', [$curso2, self::TURMA2])) {
            $sec->req($p . "disciplinas/gerar-oferta?grade={$grade2}", ['grade_id' => $grade2, 'linhas' => array_map('intval', array_column($pdo->query('SELECT id FROM uni_rh_cursos_disciplinas_grade WHERE grade_id = ' . (int) $grade2)->fetchAll(), 'id')),
                'ano' => '2025', 'codtur' => self::TURMA2, 'codfilial' => $polo, 'modalidade_id' => '', 'encontros' => '30', 'GerarOferta' => '1']);
        }
        foreach (array_keys(self::DISC2) as $k) {
            self::of2($c, $k);
        }
        /* nova oferta de Avaliação da Aprendizagem (turma R), pelo cadastro de oferta (administrador) */
        if (!$c->um('SELECT id FROM rm_professores_materias WHERE curso_id = ? AND ano = 2025 AND codtur = ?', [$curso2, self::TURMA2R])) {
            $linhaEst = (int) $c->um('SELECT id FROM uni_rh_cursos_disciplinas_grade WHERE grade_id = ? AND materia_id = ?', [$grade2, $mat2['EST']]);
            $adm->req($p . 'disciplinas/cadastrar', ['matriz_id' => $linhaEst, 'ano' => '2025', 'codtur' => self::TURMA2R, 'codfilial' => $polo, 'modalidade_id' => '', 'encontros' => '30',
                'data_inicio' => self::PERIODO2['EST-R'][0], 'data_fim' => self::PERIODO2['EST-R'][1], 'CadDisciplina' => 'Cadastrar']);
            self::confere($adm, 'oferta EST-R');
        }
        $estR = self::of2($c, 'EST-R');
        foreach (['EST', 'GDA', 'TDA'] as $k) {
            $of = self::of2($c, $k);
            if ((string) $c->um('SELECT data_inicio FROM rm_professores_materias WHERE id = ?', [$of]) === self::PERIODO2[$k][0]) {
                continue;
            }
            $sec->req($p . "disciplinas/editar/{$of}");
            $sec->req($p . "disciplinas/editar/{$of}", array_merge($sec->form(), ['data_inicio' => self::PERIODO2[$k][0], 'data_fim' => self::PERIODO2[$k][1], 'EditDisciplina' => 'Salvar']));
            self::confere($sec, "oferta 2025 {$k}");
        }
        foreach (['EST' => 'EST', 'GDA' => 'GDA', 'TDA' => 'TDA', 'EST-R' => 'EST'] as $k => $disc) {
            $of = self::of2($c, $k);
            $prof = self::usuarioId($c, self::PROF2[$disc]);
            if (!$c->um('SELECT id FROM rm_professores_mat_liga WHERE rm_prof_mat_id = ? AND usuario_id = ? AND situacao = 1', [$of, $prof])) {
                $sec->req($p . 'disciplinas/adiciona-professor/2025', ['rm_prof_mat_id' => $of, 'professores' => [$prof], 'AdicionaProfessor' => 'Salvar']);
            }
        }
        $c->log('Turma 2025 (' . self::CURSO2 . '): o curso, a matriz, as 3 ofertas e a nova oferta de Avaliação da Aprendizagem, com os professores.');
    }

    /* ------------------------------------------------------------- 6b'. a turma 2025: os alunos */
    public static function passoTurma2025Alunos(VcDemoContexto $c): void
    {
        $sec = $c->entrar('secretaria.demo');
        $p = $c->painel();
        $curso2 = self::curso2($c);
        $grade2 = self::id($c, 'SELECT id FROM rm_cursos_grades WHERE curso_id = ? AND versao = 1', [$curso2], 'a grade 2025');
        $interno2 = (int) $c->um('SELECT modulo_id FROM uni_rh_cursos_disciplinas_grade WHERE grade_id = ? LIMIT 1', [$grade2]) ?: self::falha('matriz 2025 sem disciplinas');
        $estR = self::of2($c, 'EST-R');
        /* alunos 2025: cadastro, vínculo e enturmação sugerida (as 3 disciplinas; a turma R fica para quem reprovar) */
        foreach (self::ALUNOS2 as $ra => [$nome, $genero]) {
            if (!$c->um('SELECT id FROM col_usuarios WHERE rm = ?', [$ra])) {
                $sec->req($p . 'academicos/cadastrar', ['nome' => $nome, 'rm' => (string) $ra, 'email' => 'aluno.' . $ra . '@exemplo.invalid', 'genero_id' => (string) $genero, 'col_sits_usuario_id' => '1', 'CadUsuarioAcad' => 'Cadastrar'] + self::pessoais($ra) + self::vazio());
            }
            $id = (int) $c->um('SELECT id FROM col_usuarios WHERE rm = ?', [$ra]) ?: self::falha("aluno {$ra}: " . $sec->alerta());
            if (!$c->um('SELECT id FROM rm_matriculas_curso WHERE usuario_id = ? AND curso_id = ?', [$id, $curso2])) {
                $sec->req($p . "academicos/vincular/{$id}", ['ano' => '2025', 'modulo_id' => $interno2, 'forma_ingresso_id' => '1', 'data_ingresso' => '2025-02-03', 'polo_id' => '', 'modalidade_id' => '']);
            }
        }
        foreach (self::ALUNOS2 as $ra => $x) {
            if ((int) $c->um('SELECT COUNT(*) FROM portal_disciplinas_alunos d JOIN rm_professores_materias o ON o.id = d.rm_prof_mat_id WHERE d.rm = ? AND o.curso_id = ?', [(string) $ra, $curso2]) > 0) {
                continue;
            }
            $sec->req($p . 'portal-alunos/confirmar-enturmacao/' . self::posicao2($c, $ra), ['ofertas' => array_values(array_diff(self::marcadas2($c, $ra), [$estR]))]); // a turma R fica para quem reprovar
            self::confere($sec, "enturmação 2025 de {$ra}");
        }
        $c->log('Turma 2025: 2 alunos vinculados e enturmados.');
    }

    private static function posicao2(VcDemoContexto $c, int $ra): int
    {
        return (int) $c->um('SELECT me.id FROM rm_matriculas_ensino me JOIN rm_matriculas_curso mc ON mc.id = me.matricula_curso_id WHERE me.usuario_id = ? AND mc.curso_id = ? AND me.ano = 2025', [self::aluno($c, $ra), self::curso2($c)]);
    }

    private static function mc2(VcDemoContexto $c, int $ra): int
    {
        return (int) $c->um('SELECT id FROM rm_matriculas_curso WHERE usuario_id = ? AND curso_id = ?', [self::aluno($c, $ra), self::curso2($c)]);
    }

    private static function marcadas2(VcDemoContexto $c, int $ra): array
    {
        $sec = $c->entrar('secretaria.demo');
        $sec->req($c->painel() . "portal-alunos/visualizar/{$ra}?ano=2025");
        preg_match_all('#name="ofertas\[\]" value="(\d+)"[^>]*? checked#', $sec->html, $mm);
        return array_map('intval', $mm[1]);
    }

    private static function encerrar2(VcDemoContexto $c, int $of, string $rot): void
    {
        if (self::encerrada($c, $of)) {
            return;
        }
        $sec = $c->entrar('secretaria.demo');
        $sec->req($c->painel() . "disciplinas/encerrar/{$of}", ['ConfirmarEncerramento' => '1']);
        str_contains($sec->url, "medias/final/{$of}") ?: self::falha("encerrar {$rot}: " . $sec->alerta());
    }

    /* ------------------------------------------------------------- 6c. a turma 2025: notas e encerramento */
    public static function passoTurma2025Notas(VcDemoContexto $c): void
    {
        $ativ = [1 => ['Avaliação da disciplina', '-03-02'], 2 => ['Trabalho final da disciplina', '-03-16']];
        foreach (['EST', 'GDA', 'TDA'] as $k) {
            $of = self::of2($c, $k);
            if (!self::encerrada($c, $of)) {
                self::lancar($c, $c->entrar(self::PROF2[$k]), $of, self::LANC2[$k], $ativ, $k);
                self::encerrar2($c, $of, $k);
            }
        }
        $c->log('Turma 2025: as 3 disciplinas lançadas e encerradas.');
    }

    /* ------------------------------------------------------------- 6d. a turma 2025: a nova oferta, o TCC e a conclusão */
    public static function passoTurma2025Conclusao(VcDemoContexto $c): void
    {
        $sec = $c->entrar('secretaria.demo');
        $p = $c->painel();
        $estR = self::of2($c, 'EST-R');
        /* Irene reprovou Avaliação da Aprendizagem: a secretaria a enturma na turma R (aberta), a professora lança, a secretaria encerra */
        if (!self::encerrada($c, $estR)) {
            if (!$c->um('SELECT id FROM portal_disciplinas_alunos WHERE rm = ? AND rm_prof_mat_id = ?', ['250101', $estR])) {
                $sec->req($p . 'portal-alunos/confirmar-enturmacao/' . self::posicao2($c, 250101), ['ofertas' => array_merge(self::marcadas2($c, 250101), [$estR])]);
                self::confere($sec, 'enturmar Irene na turma R');
            }
            self::lancar($c, $c->entrar(self::PROF2['EST']), $estR, self::LANC2['EST-R'], [1 => ['Avaliação da disciplina', '-03-02'], 2 => ['Trabalho final da disciplina', '-03-16']], 'EST-R');
            self::encerrar2($c, $estR, 'EST-R');
        }
        /* TCC: Irene aprovado; João em andamento. Conclusão: Irene aceita; João recusada (TCC pendente) */
        $sec->req($p . 'tcc/salvar/' . self::mc2($c, 250101), ['titulo' => 'Metodologias ativas numa turma fictícia de graduação', 'orientador_id' => (string) self::usuarioId($c, 'prof.laura.demo'),
            'nota' => '9,0', 'data_defesa' => '2025-12-05', 'situacao' => '2', 'observacao' => 'TCC fictício da demonstração.']);
        $sec->req($p . 'tcc/salvar/' . self::mc2($c, 250102), ['titulo' => 'Avaliação formativa em cursos fictícios a distância', 'orientador_id' => (string) self::usuarioId($c, 'prof.marcos.demo'),
            'nota' => '', 'data_defesa' => '', 'situacao' => '1', 'observacao' => 'Em elaboração.']);
        $conclusao = [];
        foreach ([250101, 250102] as $ra) {
            if ((int) $c->um('SELECT situacao_id FROM rm_matriculas_curso WHERE id = ?', [self::mc2($c, $ra)]) === 2) {
                $conclusao[$ra] = '';
                continue;
            }
            $sec->req($p . 'academicos/editar-vinculo/' . self::mc2($c, $ra), ['forma_ingresso_id' => '1', 'data_ingresso' => '2025-02-03', 'situacao_id' => '2', 'polo_id' => (string) self::polo($c), 'modalidade_id' => '1', 'data_conclusao' => '2025-12-15', 'observacao' => '']);
            $conclusao[$ra] = $sec->alerta();
        }
        (int) $c->um('SELECT situacao_id FROM rm_matriculas_curso WHERE id = ?', [self::mc2($c, 250101)]) === 2 ?: self::falha('conclusão de Irene: ' . $conclusao[250101]);
        (int) $c->um('SELECT situacao_id FROM rm_matriculas_curso WHERE id = ?', [self::mc2($c, 250102)]) === 1 ?: self::falha('a conclusão de João deveria ser recusada');
        $c->log('Turma 2025: Irene reprovada em Avaliação da Aprendizagem (' . self::TURMA2 . '), aprovada na turma ' . self::TURMA2R . '; TCC aprovado; CONCLUSÃO aceita (15/12/2025).');
        $c->log('Turma 2025: João com as disciplinas aprovadas e o TCC em andamento; conclusão RECUSADA: ' . preg_replace('/\s+/', ' ', (string) $conclusao[250102]));
    }

    /* ------------------------------------------------------------- 7. modelos */
    public static function passoModelos(VcDemoContexto $c): void
    {
        $adm = $c->adm();
        $p = $c->painel();
        $pdo = $c->pdo;
        $feitos = 0;
        foreach ([1, 2] as $modeloId) {
            $secoes = (string) $pdo->query("SELECT v.secoes FROM rm_historico_modelos m JOIN rm_historico_modelo_versoes v ON v.modelo_id = m.id AND v.versao = m.versao_atual WHERE m.id = {$modeloId}")->fetchColumn();
            $feitos += str_contains($secoes, 'Diretoria Acadêmica (fictícia)') ? 1 : 0;
        }
        if ($feitos < 2) {
            $formModelo = function (VcDemoNavegador $n): array {
                $plano = $n->form();
                preg_match_all('#name="cursos\[\]"[^>]*value="(\d+)"[^>]*checked#', $n->html, $cm);
                unset($plano['cursos[]']);
                parse_str(http_build_query($plano), $f);
                $f['cursos'] = $cm[1];
                return $f;
            };
            $achatar = function (array $a, string $pre = '') use (&$achatar): array {
                $o = [];
                foreach ($a as $k => $v) {
                    $n = $pre === '' ? (string) $k : "{$pre}[{$k}]";
                    $o = is_array($v) ? $o + $achatar($v, $n) : $o + [$n => (string) $v];
                }
                return $o;
            };
            /* assinatura fictícia: um traço desenhado aqui (PNG), enviado pela tela como o navegador faria */
            $assin = (string) tempnam(sys_get_temp_dir(), 'assin') . '.png';
            $im = imagecreatetruecolor(360, 90);
            imagefill($im, 0, 0, imagecolorallocate($im, 255, 255, 255));
            imagesetthickness($im, 3);
            $tinta = imagecolorallocate($im, 20, 40, 120);
            $px = 20;
            $py = 60;
            foreach ([[60, 25], [95, 70], [130, 30], [170, 65], [210, 35], [250, 62], [300, 40], [340, 55]] as [$x, $y]) {
                imageline($im, $px, $py, $x, $y, $tinta);
                [$px, $py] = [$x, $y];
            }
            imagepng($im, $assin);
            imagedestroy($im);
            $cabecalho = '<p style="text-align: center"><b>{{INSTITUICAO_NOME}}</b></p><p style="text-align: center">Mantida pela {{INSTITUICAO_MANTENEDORA}}</p>'
                . '<p style="text-align: center">Diretoria Acadêmica (fictícia)</p><p style="text-align: center">Programa de Pós-Graduação Lato Sensu (fictício)</p>'
                . '<p style="text-align: center">{{CURSO_NOME}}</p><p style="text-align: center">Polo: {{POLO_NOME}}</p>';
            $identificacao = '<table><tr><td style="width: 60%"><b>Nome:</b> {{ALUNO_NOME}}</td><td><b>RA:</b> {{ALUNO_RA}}</td></tr>'
                . '<tr><td><b>Nascimento:</b> {{ALUNO_NASCIMENTO}}</td><td><b>Naturalidade:</b> {{ALUNO_NATURALIDADE}}</td></tr>'
                . '<tr><td><b>CPF:</b> {{ALUNO_CPF}}</td><td><b>RG:</b> {{ALUNO_RG}} {{ALUNO_RG_ORGAO}}</td></tr>'
                . '<tr><td colspan="2"><b>Filiação:</b> {{ALUNO_MAE}} e {{ALUNO_PAI}}</td></tr></table>';
            if (!class_exists('\App\adms\Models\historico\AdmsHistoricoSecoes')) {
                require_once dirname(__DIR__, 2) . '/painel/vendor/autoload.php';
            }
            foreach ([1 => 'Histórico parcial — Faculdade Horizonte', 2 => 'Histórico final — Faculdade Horizonte'] as $modeloId => $nomeModelo) {
                $adm->req($p . "historico-modelos/editar/{$modeloId}");
                self::confere($adm, "abrir o modelo {$modeloId}");
                str_contains($adm->html, 'id="editorDocumento"') ?: self::falha("a tela do modelo {$modeloId} não abriu o editor visual");
                $f = $formModelo($adm);
                $f['nome'] = $nomeModelo;
                $tipoModelo = $modeloId === 1 ? 'PARCIAL' : 'FINAL';
                $secoesDemo = \App\adms\Models\historico\AdmsHistoricoSecoes::padrao($tipoModelo);
                foreach ($secoesDemo['secoes'] as $i => $s) {
                    if ($s['tipo'] === 'cabecalho') {
                        $secoesDemo['secoes'][$i]['html'] = $cabecalho;
                    } elseif ($s['tipo'] === 'aluno') {
                        $secoesDemo['secoes'][$i]['html'] = $identificacao;
                    }
                }
                $f['documento'] = \App\adms\Models\historico\AdmsHistoricoEditor::converter($secoesDemo, $tipoModelo)['documento'];
                $f['assinaturas']['blocos'] = [
                    ['nome' => 'Sônia Secretária (fictícia)', 'cargo' => 'Secretária acadêmica'],
                    ['nome' => 'Dra. Diretora Exemplar (fictícia)', 'cargo' => 'Diretora acadêmica'],
                ];
                $post = $achatar($f) + ['SalvarModelo' => 'Salvar modelo', 'assinatura_img[1]' => new CURLFile($assin, 'image/png', 'assinatura_diretora.png')];
                $adm->req($p . "historico-modelos/editar/{$modeloId}", $post, true);
                self::confere($adm, "personalizar o modelo {$modeloId}");
                str_contains($adm->html, 'modeloSalvo') ?: self::falha("modelo {$modeloId} não salvo: " . $adm->alerta());
            }
            @unlink($assin);
        }
        /* 1.18.0 (D-472): o que importa é a versão VIGENTE dos 2 modelos ter o cabeçalho da demonstração */
        foreach ([1, 2] as $modeloId) {
            $secoes = (string) $pdo->query("SELECT v.secoes FROM rm_historico_modelos m JOIN rm_historico_modelo_versoes v ON v.modelo_id = m.id AND v.versao = m.versao_atual WHERE m.id = {$modeloId}")->fetchColumn();
            str_contains($secoes, 'Diretoria Acadêmica (fictícia)') ?: self::falha("a versão vigente do modelo {$modeloId} não tem o cabeçalho da demonstração");
        }
        $c->log('Administrador personalizou os 2 modelos padrão no editor (cabeçalho em linhas, polo, 2 assinaturas).');
    }

    /* ------------------------------------------------------------- 7b. as emissões */
    public static function passoEmissoes(VcDemoContexto $c, int $parte = 1): void
    {
        $sec = $c->entrar('secretaria.demo');
        $p = $c->painel();
        $curso = self::curso($c);
        $curso2 = self::curso2($c);
        $mcDe = fn(int $ra, int $cursoId) => (int) $c->um('SELECT mc.id FROM rm_matriculas_curso mc JOIN col_usuarios u ON u.id = mc.usuario_id WHERE u.rm = ? AND mc.curso_id = ?', [$ra, $cursoId]);
        $emitir = function (int $ra, int $cursoId, string $tipo, int $modeloId) use ($c, $sec, $p, $mcDe): array {
            $mc = $mcDe($ra, $cursoId);
            $ja = (string) $c->um('SELECT codigo FROM rm_historico_emissoes WHERE matricula_curso_id = ? AND tipo = ? ORDER BY id DESC LIMIT 1', [$mc, $tipo]);
            if ($ja !== '') {
                return ['', $ja, false]; // já emitido (o passo repetido)
            }
            $sec->req($p . "historico-emissoes/emitir/{$mc}?tipo={$tipo}&modelo={$modeloId}");
            self::confere($sec, "tela de emissão {$ra} {$tipo}");
            $conf = str_contains($sec->html, 'name="confirmo_alertas"') ? ['confirmo_alertas' => '1'] : [];
            $sec->req($p . "historico-emissoes/emitir/{$mc}", ['tipo' => $tipo, 'modelo' => (string) $modeloId, 'EmitirHistorico' => 'Emitir'] + $conf);
            self::confere($sec, "emitir {$ra} {$tipo}");
            $recusa = preg_match("#id=.emissaoRecusada.>(.*?)</div>#s", $sec->html, $rm) ? trim(preg_replace("/\s+/", " ", html_entity_decode(strip_tags(str_replace("<li>", " • ", $rm[1]))))) : $sec->alerta();
            return [$recusa, (string) $c->um('SELECT codigo FROM rm_historico_emissoes WHERE matricula_curso_id = ? AND tipo = ? ORDER BY id DESC LIMIT 1', [$mc, $tipo]), (bool) $conf];
        };
        if ($parte === 1) {
            $e1 = $emitir(260104, $curso, 'PARCIAL', 1);
            $e1[1] !== '' ?: self::falha('PARCIAL do Diego: ' . $e1[0]);
            $e2 = $emitir(250101, $curso2, 'FINAL', 2);
            $e2[1] !== '' ?: self::falha('FINAL da Irene: ' . $e2[0]);
            $c->log("Secretaria emitiu: PARCIAL do Diego ({$e1[1]}" . ($e1[2] ? ', com alertas confirmados' : '') . "), FINAL da Irene ({$e2[1]}).");
            return;
        }
        $e3 = $emitir(250102, $curso2, 'FINAL', 2);
        $e3[1] === '' ?: self::falha('o FINAL do João deveria ser recusado');
        $e4 = $emitir(260108, $curso, 'PARCIAL', 1);
        $e4[1] !== '' ?: self::falha('PARCIAL do Heitor: ' . $e4[0]);
        $c->log("Secretaria emitiu o PARCIAL do Heitor, EaD ({$e4[1]}" . ($e4[2] ? ', com alertas confirmados' : '') . ').');
        $c->log('FINAL do João RECUSADO: ' . preg_replace('/\s+/', ' ', $e3[0]));
    }

    public static function passoEmissoes2(VcDemoContexto $c): void
    {
        self::passoEmissoes($c, 2);
    }

    /* ------------------------------------------------------------- 7c. os arquivos órfãos de demonstrações anteriores */
    public static function passoArquivos(VcDemoContexto $c): void
    {
        $pdo = $c->pdo;
        $pastaHist = rtrim(str_replace('\\', '/', defined('PASTA_STORAGE') ? PASTA_STORAGE : dirname(__DIR__, 2) . '/storage/'), '/') . '/historico/';
        $usadosPdf = array_merge($pdo->query('SELECT pdf_arquivo FROM rm_historico_emissoes')->fetchAll(PDO::FETCH_COLUMN), $pdo->query('SELECT pdf_arquivo FROM rm_historico_emissao_vias')->fetchAll(PDO::FETCH_COLUMN));
        foreach (glob($pastaHist . 'emissoes/*.pdf') ?: [] as $arq) {
            if (!in_array(basename($arq), $usadosPdf, true)) {
                @unlink($arq);
            }
        }
        $versoes = implode(' ', $pdo->query('SELECT secoes FROM rm_historico_modelo_versoes')->fetchAll(PDO::FETCH_COLUMN));
        foreach (glob($pastaHist . 'assinaturas/assin_*.png') ?: [] as $arq) {
            if (!str_contains($versoes, basename($arq))) {
                @unlink($arq);
            }
        }
        $c->log('Os arquivos de demonstrações anteriores (PDFs e assinaturas sem uso) saíram.');
    }
}

/**
 * A LIMPEZA DA DEMONSTRAÇÃO dentro do PHP: o mesmo database/seed_demo.sql que o limpar_demo.php roda pelo cliente
 * mysql, aqui pelo PDO (as instruções uma a uma, respeitando o DELIMITER dos gatilhos), com as mesmas travas: as
 * variáveis da sessão que o próprio SQL confere (a demonstração e o banco), e, antes, nenhuma emissão real.
 */
final class VcDemoLimpeza
{
    public const GATILHOS = ['rm_historico_emissoes_imutavel', 'rm_historico_emissoes_sem_apagar', 'rm_historico_emissao_vias_imutavel', 'rm_historico_emissao_vias_sem_apagar',
        'rm_media_fechamento_resultados_imutavel', 'rm_media_fechamento_resultados_sem_apagar'];

    /** As instruções do arquivo SQL, uma a uma (sem os comentários; o DELIMITER respeitado). */
    public static function instrucoes(string $sql): array
    {
        $saida = [];
        $delim = ';';
        $atual = '';
        foreach (preg_split('/\R/', $sql) as $linha) {
            $t = trim($linha);
            if ($atual === '' && ($t === '' || str_starts_with($t, '--') || str_starts_with($t, '#'))) {
                continue;
            }
            if (preg_match('/^DELIMITER\s+(\S+)$/i', $t, $m)) {
                $delim = $m[1];
                continue;
            }
            $atual .= $linha . "\n";
            if (str_ends_with(rtrim($t), $delim)) {
                $i = trim($atual);
                $i = trim(substr($i, 0, strlen($i) - strlen($delim)));
                if ($i !== '') {
                    $saida[] = $i;
                }
                $atual = '';
            }
        }
        if (trim($atual) !== '') {
            $saida[] = trim($atual);
        }
        return $saida;
    }

    /** Roda o seed_demo.sql no banco desta conexão. Devolve as mensagens; lança VcDemoFalha na recusa. */
    public static function rodar(PDO $pdo): array
    {
        $reais = (int) $pdo->query("SELECT COUNT(*) FROM rm_historico_emissoes WHERE ambiente <> 'demonstracao'")->fetchColumn();
        if ($reais > 0) {
            VcDemoPassos::falha("há {$reais} emissão(ões) de histórico que não são da demonstração. A limpeza apagaria documentos reais. Nada foi alterado.");
        }
        $banco = (string) $pdo->query('SELECT DATABASE()')->fetchColumn();
        $pdo->exec("SET @vcostaedu_limpeza_demo = 'demonstracao', @vcostaedu_banco_demonstracao = " . $pdo->quote($banco));
        $arquivo = dirname(__DIR__) . '/seed_demo.sql';
        $sql = (string) file_get_contents($arquivo);
        $mensagens = [];
        try {
            foreach (self::instrucoes($sql) as $i) {
                $st = $pdo->query($i);
                if ($st !== false && $st->columnCount() > 0) {
                    foreach ($st->fetchAll(PDO::FETCH_NUM) as $l) {
                        $mensagens[] = implode(' ', array_map('strval', $l));
                    }
                }
                if ($st !== false) {
                    $st->closeCursor();
                }
            }
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'more than 1 row')) {
                VcDemoPassos::falha('a limpeza da demonstração foi recusada pelas travas do banco (a demonstração não está ligada, ou há emissão real). Nada foi alterado.');
            }
            VcDemoPassos::falha('a limpeza da demonstração parou: ' . preg_replace('/SQLSTATE\[[^\]]*\]:?\s*/', '', $e->getMessage()));
        } finally {
            $pdo->exec('SET @vcostaedu_limpeza_demo = NULL, @vcostaedu_banco_demonstracao = NULL');
        }
        return array_values(array_filter($mensagens, fn($m) => !str_starts_with($m, 'Trava da demonstração')));
    }

    /** Os gatilhos de imutabilidade que faltam (a limpeza os tira e recria). */
    public static function gatilhosFaltando(PDO $pdo): array
    {
        $ha = $pdo->query("SELECT TRIGGER_NAME FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = DATABASE() AND (TRIGGER_NAME LIKE 'rm\\_historico%' OR TRIGGER_NAME LIKE 'rm\\_media%')")->fetchAll(PDO::FETCH_COLUMN);
        return array_values(array_diff(self::GATILHOS, $ha));
    }
}

/**
 * O CENÁRIO DA DEMONSTRAÇÃO (Onda 4, Etapa 4): a faculdade fictícia (VcDemoPassos) numa instalação de ensino superior, o
 * colégio fictício (VcDemoEscola, demonstracao_escola.php) numa de escola — os dois com os mesmos passos curtos, a mesma
 * janela, a mesma linha de comando e a mesma limpeza.
 */
require_once __DIR__ . '/demonstracao_escola.php';
require_once __DIR__ . '/demonstracao_livre.php'; // Fechamento (D-106): o curso livre

final class VcDemo
{
    /** @return class-string o cenário do tipo desta instalação */
    public static function cenario(PDO $pdo): string
    {
        try {
            $t = $pdo->query("SELECT valor FROM adms_instalacao_estado WHERE chave = 'instituicao.tipo'")->fetchColumn();
        } catch (Throwable $e) {
            $t = false;
        }
        return $t === 'escola' ? VcDemoEscola::class : ($t === 'livre' ? VcDemoLivre::class : VcDemoPassos::class);
    }

    /** Os cursos (ou segmentos) e as instituições fictícias dos dois cenários: a demonstração é reconhecida pelos dois. */
    public static function cursosDemo(): array
    {
        return array_merge(VcDemoPassos::CURSOS_DEMO, VcDemoEscola::CURSOS_DEMO, VcDemoLivre::CURSOS_DEMO);
    }

    public static function instituicoesDemo(): array
    {
        return array_merge(VcDemoPassos::INSTITUICOES_DEMO, VcDemoEscola::INSTITUICOES_DEMO, VcDemoLivre::INSTITUICOES_DEMO);
    }
}
