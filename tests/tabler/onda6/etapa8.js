/*
 * PROVA DA ONDA 6, ETAPA 8 — a preparação para abrir o código (D-60).
 *   A. os arquivos do repositório: o README, como contribuir, a política de segurança, o código de conduta, a marca e o
 *      termo de contribuição (marcado como RASCUNHO para revisão jurídica); os contatos que faltam, com o marcador;
 *   B. o inventário do esqueleto herdado: o documento gerado bate com o código de agora (gerar de novo não muda nada);
 *   C. a pasta limpa, numa pasta temporária: só o último commit, sem o .git, sem o retorno.md, sem provas/; a varredura
 *      de higiene sobre o resultado sem nenhum achado;
 *   sabotagem: um "segredo" que está nos arquivos (o nome do produto) passado para a varredura — ela tem de achar.
 * Os nomes reais e os segredos a procurar vêm do ambiente (VCOSTAEDU_VARRER_NOMES, VCOSTAEDU_VARRER_SEGREDOS).
 */
const fs = require('fs');
const os = require('os');
const path = require('path');
const crypto = require('crypto');
const { spawnSync } = require('child_process');
const L = require('../lib');

const PHP = process.env.VCOSTAEDU_PHP || 'C:/xampp/php/php.exe';
const ETAPA = 'onda6/etapa8';
const ORIGEM = path.join(L.RAIZ, 'docs', 'ORIGEM_DO_CODIGO.md');
const sha = (f) => crypto.createHash('sha256').update(fs.readFileSync(f)).digest('hex');
const limpa = (destino, env = {}) => spawnSync(PHP, [path.join(L.RAIZ, 'database', 'ferramentas', 'implantacao', 'pasta_limpa.php'), destino], { encoding: 'utf8', cwd: L.RAIZ, env: { ...process.env, ...env }, maxBuffer: 1 << 26 });

console.log('\n== Onda 6, Etapa 8 — a preparação para abrir o código');
/* A. os arquivos */
const ler = (r) => { try { return fs.readFileSync(path.join(L.RAIZ, r), 'utf8'); } catch (e) { return ''; } };
const arquivos = ['README.md', 'CONTRIBUINDO.md', 'SEGURANCA.md', 'CODIGO_DE_CONDUTA.md', 'MARCA.md', 'docs/TERMO_DE_CONTRIBUICAO.md', 'LICENSE', 'TRADEMARK.md'];
const faltam = arquivos.filter((r) => ler(r).length < 200);
const marcadores = arquivos.flatMap((r) => (ler(r).match(/\[A DEFINIR:[^\]]+\]/g) || []).map((m) => `${r}: ${m}`));
L.ok(faltam.length === 0 && /RASCUNHO PARA REVISÃO JURÍDICA/.test(ler('docs/TERMO_DE_CONTRIBUICAO.md')) && /canal privado|privad/.test(ler('SEGURANCA.md')) && marcadores.length > 0,
  `os arquivos do repositório estão lá (${arquivos.length}); o termo, como rascunho para revisão jurídica; ${marcadores.length} contato(s) com o marcador [A DEFINIR]`);
fs.writeFileSync(path.join(__dirname, '..', 'saida', 'onda6-etapa8-marcadores.json'), JSON.stringify(marcadores, null, 1));

/* B. o inventário */
const antes = sha(ORIGEM);
const g = spawnSync(PHP, [path.join(L.RAIZ, 'database', 'ferramentas', 'origem_do_codigo.php')], { encoding: 'utf8', cwd: L.RAIZ });
const resumo = (g.stdout || '').trim().split('\n').pop();
L.ok(g.status === 0 && sha(ORIGEM) === antes, `o inventário do esqueleto herdado bate com o código de agora (${resumo})`);

/* C. a pasta limpa */
const destino = fs.mkdtempSync(path.join(os.tmpdir(), 'vcostaedu-publico-'));
fs.rmdirSync(destino);
const r = limpa(destino);
const tem = (rel) => fs.existsSync(path.join(destino, rel));
const relatorio = (() => { try { return fs.readFileSync(destino + '-varredura.txt', 'utf8'); } catch (e) { return ''; } })();
L.ok(r.status === 0 && tem('README.md') && tem('painel/index.php') && !tem('.git') && !tem('retorno.md') && !tem('provas') && /0 achado/.test(relatorio + r.stdout),
  `a pasta limpa: o último commit, sem o histórico, sem o retorno.md e sem as provas; a varredura sem achado (${(r.stdout || '').trim().split('\n').pop()})`);
fs.rmSync(destino, { recursive: true, force: true });
try { fs.unlinkSync(destino + '-varredura.txt'); } catch (e) { /* */ }

/* sabotagem: a varredura tem de achar o que está lá */
const destino2 = fs.mkdtempSync(path.join(os.tmpdir(), 'vcostaedu-publico-'));
fs.rmdirSync(destino2);
const s = limpa(destino2, { VCOSTAEDU_VARRER_SEGREDOS: [process.env.VCOSTAEDU_VARRER_SEGREDOS || '', 'Copyright'].filter(Boolean).join(',') });
fs.rmSync(destino2, { recursive: true, force: true });
try { fs.unlinkSync(destino2 + '-varredura.txt'); } catch (e) { /* */ }
L.ok(s.status !== 0, 'sabotagem (um segredo que está nos arquivos passado para a varredura): a varredura acha e a ferramenta sai com erro');
process.exit(L.resumo(ETAPA) ? 0 : 1);
