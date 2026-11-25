// Abre o Chromium VISÍVEL, maximizado, com depuração remota e um perfil próprio, e sai deixando a janela aberta.
// Os blocos conectam a ele (connectOverCDP). Uso: node abrir_navegador.js
const fs = require('fs');
const path = require('path');
const http = require('http');
const { spawn } = require('child_process');
const local = (() => { try { return JSON.parse(fs.readFileSync(path.join(__dirname, 'config.local.json'), 'utf8')); } catch (e) { return {}; } })();
const cfg = (k, p) => process.env['JORNADA_' + k] || local[k] || p;
const chrome = cfg('CHROMIUM', '');
const perfil = cfg('PERFIL', path.join(__dirname, 'saida', 'perfil'));
const porta = cfg('PORTA', '9333');
if (!chrome || !fs.existsSync(chrome)) { console.error('Defina JORNADA_CHROMIUM (ou CHROMIUM em config.local.json) com o caminho do chrome.exe do Playwright.'); process.exit(1); }
fs.mkdirSync(perfil, { recursive: true });
const p = spawn(chrome, [`--remote-debugging-port=${porta}`, `--user-data-dir=${perfil}`, '--start-maximized', '--no-first-run', '--no-default-browser-check',
  '--disable-features=Translate', 'about:blank'], { detached: true, stdio: 'ignore' });
p.unref();
const tentar = (n) => http.get(`http://127.0.0.1:${porta}/json/version`, r => { console.log(`Chromium aberto (pid ${p.pid}), depuração na porta ${porta}.`); process.exit(0); })
  .on('error', () => { if (n > 0) setTimeout(() => tentar(n - 1), 500); else { console.error('O Chromium não respondeu na porta ' + porta); process.exit(1); } });
tentar(30);
