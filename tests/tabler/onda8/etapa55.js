/*
 * PROVA DA 1.103.0, C6.1 — a porta do banco (D-131). Usa a instalação do ciclo (o ensino superior, instalado do zero
 * pelo instalador da tela, nesta pasta).
 *   1. o instalador gravou a porta nas duas Config.php (a constante PORTA, 3306 de partida), e os dois modelos
 *      Config.example.php (a instalação pela linha de comando) trazem a constante;
 *   2. a tela dos dados do banco tem o campo Porta, com 3306, e recusa porta fora de 1 a 65535 (o código da tela);
 *   3. a conexão das telas usa a porta: com 3306 conecta; com a porta 1, recusa;
 *   4. a ferramenta de linha de comando conecta pela porta da Config (o status das migrations);
 *   sabotagem: a conexão sem a porta — com a porta 1, ainda conecta: a prova reprova; o arquivo volta igual (sha256).
 * Ambiente: VCOSTAEDU_BASE, VCOSTAEDU_BANCO_TESTE.
 */
const fs = require('fs');
const path = require('path');
const crypto = require('crypto');
const { spawnSync } = require('child_process');
const L = require('../lib');
const X = require('./_escola');

const ETAPA = 'onda8/etapa55';
const PHP = process.env.VCOSTAEDU_PHP || 'C:/xampp/php/php.exe';
const BANCO = path.join(L.RAIZ, 'core', 'Banco.php');
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
const { sql } = X;
const ler = (...p) => fs.readFileSync(path.join(L.RAIZ, ...p), 'utf8');
const conecta = (porta) => (spawnSync(PHP, [path.join(__dirname, 'porta_conexao.php'), String(porta)], { encoding: 'utf8', cwd: L.RAIZ }).stdout || '').trim();

(async () => {
  const shaB = sha(BANCO);
  try {
    L.ok(sql("SELECT valor FROM adms_instalacao_estado WHERE chave = 'instituicao.tipo'") === 'superior', 'o banco do ciclo: o ensino superior, instalado do zero pelo instalador');

    /* 1. as Config.php e os modelos */
    const cfgs = ['painel', 'portal'].map((a) => ler(a, 'core', 'Config.php'));
    const modelos = ['painel', 'portal'].map((a) => ler(a, 'core', 'Config.example.php'));
    L.ok(cfgs.every((c) => /define\('PORTA', 3306\);/.test(c)) && modelos.every((c) => /define\('PORTA', 3306\);/.test(c)),
      '1. o instalador gravou PORTA (3306) nas duas Config.php; os dois modelos da linha de comando trazem a constante');

    /* 2. a tela */
    const idx = ler('instalar', 'index.php');
    L.ok(/id="porta" name="porta"[^>]*min="1" max="65535"/.test(idx.replace(/\s+/g, ' ')) && /'porta' => '3306'/.test(idx)
      && /A porta é um número de 1 a 65535/.test(idx) && /'PORTA'\s+=> \(int\)/.test(ler('instalar', 'gerador.php')),
      '2. a tela dos dados do banco tem o campo Porta (3306 de partida) e recusa porta fora de 1 a 65535; o gerador grava a constante');
    /* 1.103.1 (D-135): o navegador não preenche o usuário e a senha do banco com um login salvo */
    const banco = idx.slice(idx.indexOf('action="?passo=banco"'), idx.indexOf('action="?passo=banco"') + 8000).replace(/\s+/g, ' ');
    L.ok(/id="usuario" name="usuario"[^>]*autocomplete="off"/.test(banco) && /id="senha" name="senha"[^>]*autocomplete="new-password"/.test(banco),
      '2. no passo do banco, o usuário com autocomplete="off" e a senha com autocomplete="new-password" (o navegador não preenche com um login salvo)');

    /* 3. a conexão das telas */
    const c3306 = conecta(3306);
    const c1 = conecta(1);
    L.ok(c3306 === 'conectou' && c1 === 'recusou', `3. a conexão das telas usa a porta: 3306 ${c3306}; a porta 1 ${c1}`);

    /* 4. a linha de comando */
    const st = spawnSync(PHP, [path.join(L.RAIZ, 'bin', 'migrar.php'), 'status'], { encoding: 'utf8', cwd: L.RAIZ });
    L.ok(/aplicadas/.test(st.stdout), '4. a linha de comando conecta pela Config (o status das migrations)');

    /* sabotagem: a conexão sem a porta */
    const orig = fs.readFileSync(BANCO, 'utf8');
    const trecho = "self::abrir('mysql:host=' . HOST . ';port=' . (defined('PORTA') ? (int) constant('PORTA') : 3306) . ';dbname=' . DBNAME";
    if (!orig.includes(trecho)) { throw new Error('a sabotagem não achou o trecho'); }
    let sab = null;
    try {
      fs.writeFileSync(BANCO, orig.replace(trecho, "self::abrir('mysql:host=' . HOST . ';dbname=' . DBNAME"));
      sab = conecta(1);
    } finally {
      fs.writeFileSync(BANCO, orig);
    }
    L.ok(sab === 'conectou', 'sabotagem (a conexão sem a porta): com a porta 1, ainda conecta — a prova reprova');
    L.ok(sha(BANCO) === shaB, 'o arquivo sabotado voltou igual (sha256)');
  } catch (e) {
    L.ok(false, 'a prova parou: ' + e.message.split(String.fromCharCode(10))[0]);
  } finally {
    if (sha(BANCO) !== shaB) { console.log('  [AVISO] o arquivo sabotado não voltou igual'); }
  }
  process.exit(L.resumo(ETAPA) ? 0 : 1);
})();
