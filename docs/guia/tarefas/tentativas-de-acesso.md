---
titulo: Tentativas de acesso e limites
resumo: Como funciona a espera depois de muitas tentativas, como ajustar os limites por IP e como liberar alguém antes.
perfis: administrador
ordem: 66
relacionados: entrar-no-painel, esqueci-a-senha, conferir-um-documento, regras-de-nota-e-frequencia
telas: tentativas-acesso/listar
entrou: 1.7.0
mudou: 1.7.1
---
## Para que serve

Proteger as senhas contra quem tenta adivinhar. Depois de algumas falhas seguidas, a tela pede uma **espera** antes da próxima tentativa.

## Antes de começar

Há dois limites. O que chegar primeiro vale:

| Onde | Por usuário (fixo) | Por endereço (IP), ajustável |
|---|---|---|
| Login do painel e do portal | 5 falhas em 24 horas | padrão 50 falhas por hora (de 10 a 1000) |
| Esqueci a senha (painel e portal) | 3 pedidos em 1 hora | padrão 20 pedidos por hora (de 5 a 500) |
| Verificação pública de documento | — | padrão 60 consultas a cada 10 minutos (de 10 a 1000) |

A espera começa em **30 segundos** e **dobra** a cada nova falha, até **15 minutos**. Uma entrada certa zera a contagem daquele usuário.

Um mesmo IP pode ser de muita gente: um laboratório ou a rede da escola saem todos pelo mesmo endereço. Por isso o limite por IP é mais folgado e pode ser ajustado.

## Passo a passo

1. **Ajustar os limites por IP:** em **Configurações → Instituição**, no quadro **Limite de tentativas por IP**, mude os três números e clique em **Salvar**. Fora da faixa, nada é salvo, e a tela diz a faixa certa ([Regras de nota e frequência](guia:regras-de-nota-e-frequencia)).
2. **Ver quem está em espera:** em **Configurações → Tentativas de acesso**. As linhas em amarelo estão em espera agora.

![Tela Tentativas de acesso com uma linha em espera](img/administrador_22_tentativas.png)

3. **Liberar alguém antes do fim da espera:** clique em **Liberar** na linha, ou digite o usuário (ou o IP) no campo do alto e clique em **Liberar**.

> A tabela guarda só uma **dica** do usuário e do IP (parte do texto), nunca a senha. As tentativas bloqueadas ficam no registro de acessos, também sem senha e sem código de link.

## O que pode dar errado

- **"O limite por IP "Login: falhas por hora" deve ser um número inteiro de 10 a 1000."** — e as mensagens parecidas dos outros dois limites: use um número dentro da faixa.
- Digitou o usuário e nada mudou — use o mesmo texto que a pessoa digitou na entrada (usuário, e-mail, RA ou CPF), ou libere pela linha.
- Muitas linhas do mesmo IP — pode ser uma rede compartilhada (o laboratório) ou alguém tentando adivinhar senhas. Se for a rede da escola, aumente o limite por IP. Na dúvida, não libere o IP.
- Todos os alunos aparecem com o **mesmo IP** — o servidor está atrás de um proxy. Quem instala precisa informar esse proxy na configuração do servidor (`PROXIES_CONFIAVEIS`).
