Run correctly image or flowchart please attached how is renderedO Mermaid no app iOS renderiza caixa branca. Segue o fluxograma como imagem (retrato, texto grande).
1 INIT

PC/VPS → docker run WAHA → session apollo → QR no celular → plugin ON → salva URL + key → cria ou grava @g.us
2 ADD USER

Pede no site → OTP já ok → fila no apollo-core → admin recusa (fim) ou aprova → POST /groups/{id}/participants → entra no grupo
3 SEND LATER

Texto + horário no painel → WP-Cron → opcional puxa eventos → POST /api/sendText no @g.us
PC/VPS fica ligado só pelo Docker do WAHA. WordPress só dispara HTTP. How hard is to implement it as apollo-whatsapp takin consideration of -git clone repos to hurry steps which exist free open source to runNão é difícil o plugin. O que é chato é o Zap, não o PHP.
Dificuldade real do apollo-whatsapp: 4/10 no código, 6/10 no operacional.

Não existe plugin WP pronto pra clonar que faça fila + aprovar + add no grupo + cron. O atalho é não reescrever o WhatsApp — só clonar/subir o gateway e escrever a casca Apollo.

O que clonar / não clonar








































CoisaAçãoVale?WAHAdocker pull — não git clone pra dentro do WPSim. Motor pronto: criar grupo, add, send, join-requestevolution-apisó se WAHA travar no WindowsPlano B BRwaha-docscopiar endpoints PHPSimLaravel WAHA SDK / MultiWA WP pluginolhar endpoints, não mergeNão vale o débitonetflie Cloud PHPsó OTP 1:1 oficialOutro número, outro diaWawpmortoNão
WAHA já entrega o que o fluxo precisa:
textPOST /api/{session}/groups
POST /api/{session}/groups/{id}/participants/add
POST /api/sendText
GET  /api/{session}/groups/{id}/participants/join-requests
Plus Core é grátis. Tem helper do 9º dígito BR.

O que você escreve (só isso)
apollo-whatsapp = ~4 arquivos, dependente de apollo-core:

Cliente HTTP (wp_remote_post)
Settings (URL, key, session, groupId)
Fila + botão Aprovar no apollo-admin
Cron de mensagem

Hooks: apollo/login/..., user_meta _apollo_whatsapp_phone, REST eventos já existem. Não inventa CPT fora do core.

Tempo honesto (Grok Fast + VSCode)



































FatiaTempoClone acelera?Docker WAHA + QR30–90 minSim — zero códigoPlugin settings + ping sessionmeio diaSim — 80 linhasFila + Aprovar + add1 diaNão — é a cara ApolloCron texto no grupomeio diaQuase nãoBR phone + privacidade “não pode add” + sessão cair2–4 diasNão. Aqui dói
MVP usável: 2–3 dias.

Estável no Rio, grupo real: 1–2 semanas (add falha se o user bloqueou “add em grupo”).

Onde pega

Windows + Docker + LocalWP = o setup, não o plugin
PC/VPS tem que ficar ligado
Add à força falha por privacidade → fallback = mandar invite link
Número Apollo verificado não vai no WAHA. Usa número de operação
Ban se disparar add em massa


Veredito: corre. Não clona motor pra dentro do WP. Sobe WAHA, plugin fino. Primeiro commit: settings + session/status. Se o ping voltar WORKING, o resto é botão.Investigate deep recent technology in use, solutions to “BR phone + privacidade “não pode add” + sessão cair”Os três problemas não se resolvem no PHP. São regra do WhatsApp em 2026. O plugin trata com cadeia de fallback, não com um POST único.

1. Telefone BR (9º dígito + @lid)
O que quebra o match:

Celular ANATEL: 55 + DDD + 9 + 8 dígitos → 13 dígitos (5521999…).
Conta antiga no Zap ainda vive no wa_id sem o 9 → 12 dígitos (5521998…). Webhook ≠ o que o user digitou no Apollo.
Fixo não leva 9. Quem “normaliza” todo BR com 9 inventa número morto (45 3278… vira 45 93278…).
Em grupo, o id vira @lid (sem telefone). Add com 5511…@c.us às vezes falha se o grupo está em modo LID. Rollout até fim de 2026.

Solução no apollo-whatsapp (padrão 2026):

Salva o que o user digitou e a variante (com/sem 9). Meta: _apollo_wa_candidates.
Antes do add: GET /api/contacts/check-exists nas duas formas. Usa o JID que o WAHA devolver.
Motor: WAHA 2026.8.2+ (app “Brazilian Phone Numbers” + participant.pn no LID). Engine GOWS resolve @lid → PN.
Nunca confie no input cru. Confie no chatId que o check-exists devolveu.

textinput  21 99999-0000
try    5521999990000@c.us
try    552199990000@c.us     ← conta pré-2012
use    o que exists=true

2. Privacidade “não pode add”
Isso não é bug de API. Settings → Privacidade → Grupos:

Todos
Meus contatos
Meus contatos, exceto…
Ninguém (via “exceto todos”)

Se o user não tem o número de operação salvo, o add volta 403. API nenhuma fura isso. Tentar de novo = report + ban.
Cadeia que funciona (nessa ordem):






























#AçãoQuandoAparticipants/add com JID validadoPrivacidade = TodosBSalvar o user como contato no número WAHA, esperar ~3s, add de novoAjuda se a regra é “Meus contatos” e o user também salvou vocês — raroCGET /groups/{id}/invite-code → manda https://chat.whatsapp.com/{code} no privadoCaminho padrão RioDLiga “aprovar novos membros” no grupo + user entra pelo link → admin (plugin) dá approve no join-requestVIP que some / lista fechada
WAHA já tem as peças: invite-code, join-requests approve/reject, membership-approval.
UX Apollo: botão admin não promete “entrou”. Promete “convite enviado”. Status na fila: added | invited | pending_approval | blocked_privacy.
Não faça o número Apollo Business no WAHA. Add em massa no verificado queima o verde.

3. Sessão cair
Causas reais em 2026, não “Docker misterioso”:


















































SintomaCausaAntídotoCai ~24h, código 428Idle no WebSocketPresence available + keepalive. WAHA GOWS: WAHA_GOWS_KEEPALIVE_INTERVAL_MIN/MAX (ago/2026)401Logout no celular / “desconectar dispositivos”QR de novo. Não reconecta sozinho403Ban / restrictTroca número. Acabou440 device replacedDois WAHA/Evolution no mesmo número1 session = 1 númeroInvalid patch mac (NOWEB)Auth state podreApaga session, relinka. Evita NOWEB pra produçãoCai no Windows + WSL2Race no authState (Evolution 2.3.7)Não hospeda Evolution no teu Windows. VPS Linux ou WAHA Docker nativoIP de datacenterSync inicial leva pause/disconnectIP residencial / 4G se possível14 dias sem abrir o appMulti-device mata a sessãoAbrir o Zap no celular 1× por semana
Padrão que a galera está usando agora: daemon de keepalive (presence a cada ~25s — timeout do Zap ~30s) + snapshot da pasta sessions antes de restart. Ex.: whatsapp-keeper.
Stack Apollo recomendada pra sessão:
textWAHA 2026.8.2  engine=GOWS
volume persistente  ./sessions
1 número de operação (não o Business)
VPS Linux 24h  (não LocalWP no notebook)
webhook session.status → WP pinta “session DOWN”
admin só aprova se status=WORKING
celular: abre o Zap domingo
Pairing code (8 dígitos) é mais estável que QR em loop. Mesmo número não pode estar em outro Web/Desktop “oficial” brigando com o WAHA.

O que o plugin faz com isso (código mínimo)
textapprove(user):
  jid = resolve_br(user)          # 2 candidatos + check-exists
  if session != WORKING: fail "session down"
  r = waha.add(group, jid)
  if r == 200: status=added
  elif r == 403: 
      link = waha.invite_code(group)
      waha.sendText(jid, link)    # privado
      status=invited
  elif r == 409: status=already
Cron de mensagem só roda se GET /api/sessions/apollo = WORKING. Senão enfileira e não queima retry no Zap.

Resumo CTO:

BR = guardar duas formas + acreditar no check-exists.

Privacidade = link, add é bônus.

Sessão = GOWS + VPS + 1 número + presence + volume. Windows/LocalWP é lab, não produção.
Isso é a parte 6/10. O HTTP do plugin continua 4/10.Run investigating daemon de keep alive is best repo? Is there any extra trick or code or technology bein used for remote sending message in group and schedule system that can add user number as contact then add to group? 🥸Não. whatsapp-keeper não é o melhor repo pra você. É um daemon colado no OpenClaw, não no WAHA. Subir ele do lado do Docker é duas mãos no mesmo soquete — disputa sessão e apaga notificação do celular.
O keepalive já veio no motor em agosto/2026.

Keepalive: o que usar de verdade













































PeçaServe pro Apollo?Por quêWAHA GOWS WAHA_GOWS_KEEPALIVE_INTERVAL_MIN/MAXSim — defaultPing nativo no engine. Sem repo extramarkOnlineOnConnect: false + presence só no número botSimavailable 24h no número pessoal mata push do iPhoneVolume ./sessions + restart limpoSimCorrupção = pre-keys infinitos (~2–3 sem)baileys-keep-aliveSó se você abandonar WAHA e escrever Baileys puroHeartbeat + backoff. Não pluga no WPwhatsapp-keeperNãoOpenClaw onlyWaSPNão agoraOutra camada em cima do Baileys. Troca o WAHA inteirofazer-ai/baileys-api health/stallIdeia boa, não clonarDetecta “socket vivo mas send morto”
Config WAHA (produção):
Bash-e WAHA_GOWS_KEEPALIVE_INTERVAL_MIN=20
-e WAHA_GOWS_KEEPALIVE_INTERVAL_MAX=25
-v ./sessions:/app/.sessions
Plugin: webhook session.status → se não for WORKING, não add e não cron.

Celular: abre o Zap 1×/semana. Isso vale mais que qualquer daemon.

Truque “salva contato → add no grupo”
Existe e é endpoint oficial do WAHA, não gambiarra de fórum:
textPUT /api/{session}/contacts/{chatId}
{ "firstName": "Apollo", "lastName": "User 123" }
Isso grava no agenda do celular ligado na sessão. Doc manda repetir 2–3 vezes com alguns segundos no meio (vários apps Zap no phone = flaky).
Depois:
textPOST /api/{session}/groups/{id}/participants/add
{ "participants": [{ "id": "5521999...@c.us" }] }
O que isso resolve e o que não resolve

Ajuda só se a privacidade do outro for “Meus contatos” e ele também tiver o número de operação salvo. Um lado só não basta.
Se for “Ninguém” / “exceto todos” → 403 eterno. Contato não fura.
Em GOWS (abr/2026) o 403 às vezes derrubava a sessão. HTTP 200 com Error: 403 no body. Não trate 403 como logout. Pegue o payload.

Ouro escondido no 403:
JSON"Error": 403,
"AddRequest": {
  "Code": "Xy7Kp9LmQ2vR8sTa",
  "Expiration": "2026-04-09T11:44:32+03:00"
}
Esse Code é o convite. Manda no privado:
https://chat.whatsapp.com/Xy7Kp9LmQ2vR8sTa
Não precisa de segundo GET de invite-code quando o add já devolveu isso.

Cadeia certa no apollo-whatsapp
text1. session == WORKING?
2. check-exists nas 2 formas BR → jid
3. PUT contact (2x, sleep 3s)
4. sleep 5s
5. POST participants/add
6. 200 + sem Error     → added
7. 409                 → already
8. Error 403 + Code    → sendText(jid, link) → invited
9. 403 sem Code        → GET invite-code → invited
10. nunca retry add em loop
Agenda no grupo (schedule): WP-Cron + lock. Volume baixo. Não clona n8n/Bull só pra isso. Fila: um send a cada 8–15s (rate limit informal anti-ban). Cron só se session WORKING.
Remote send no grupo já é:
textPOST /api/sendText
{ "session": "apollo", "chatId": "120363...@g.us", "text": "..." }
Nada de repo extra.

CTO:

Keepalive = env do GOWS, não clone do keeper.

Contato = PUT contacts antes do add, com espera.

Privacidade = use o AddRequest.Code do 403.

Schedule = Cron do core.
Isso é o stack 2026 sem inchação.Investigate Reddit l, dev forums, x dev community and related for most recent discussions of “Whatsapp dev code” to see what are topics and what indeed is the new gold
 github-diamond-seeker skillOuro 2026 não é estrela. É LID + engine Go + 403 com AddRequest.Code.

X está poluído (“waha” = “lá” em hindi). Reddit/HN/GitHub issues é onde o código mora.

Tópicos que a comunidade está discutindo agora























































TemaOndeO que é de fato@lid substitui telefoneReddit BB10, Evolution issues, GREEN-API, WAHA #2208Identidade anônima. Add/reply quebra se você só guarda @c.us. Rollout até fim de 2026Erro 463 / reachout timelockBaileys v7 rcConta limitada. Não é “session caiu”Coexistence Cloud + appr/WhatsappBusinessAPI jul/2026Oficial: mesmo número no app e Cloud. Docs velhos mentemService messages pagam em out/2026mesmo subReply 24h deixa de ser grátisUtility sem aprovação de templatemesmo sub, H2 2026IA classifica. Abuse = strike 7d → 30d → banWAHA vs Evolution no n8nr/n8n abr/2026Os dois caem. WAHA ganha por vários engines. Evolution = mais BaileysBan em setup / Grok Bot / “automation”r/whatsapp, r/WABAMeta flagra sessão Web ligada em bot. Número pessoal ≠ lablotusbail / supply chain BaileysWaSP readme, Dez 2025Fork suspeito. Não npm i aleatórioPre-keys explodem e corrompem sessionOpenClaw PR2–3 semanas sem prune = logout

Diamantes (código, não hype)
1. tulir/whatsmeow — 8.5/10

Go, MPL, LID nativo (GetPNForLID no add). É o que o WAHA GOWS e o mautrix/Beeper usam. Sessão longa > Node.

Hidden? Não é hidden, é o que a galera séria escolhe quando cansa do Baileys.
2. WAHA engine GOWS (não o repo inteiro) — 8/10 pra Apollo

Keepalive env, LID→PN, BR digit app, AddRequest.Code no 403. Hedging: troca engine sem trocar o plugin. HN: “usei WAHA em cima do Baileys/whatsmeow”.
3. Baileys v7 rc (WhiskeySockets) — 7.5/10 no protocolo, 4/10 no teu plugin

Ouro: mapping LID no onWhatsApp, 463, novo QR, mutex. Não clone isso no WordPress. Deixa o WAHA puxar.
4. Fork innovatorssoft/Baileys 7.4.7 (ago/2026) — 7/10 pontual

Persistir PN↔LID no onWhatsApp. Sinal do que importa. Não troque o motor por um fork de 44 stars sem auditoria.
5. Gnaneshdivi/personal-whatsapp-mcp — 6.5/10 ideia

whatsmeow via neonize, MCP local. Ouro é a pilha Go, não o MCP.
Não é ouro: whatsapp-keeper (OpenClaw only), Venom (parado 2024), Wawp, MultiWA WP form, WaSP como substituto do WAHA, Chrome extension “bulk sender”.

O ouro operacional (o que todo mundo que não toma ban faz)

Número de operação ≠ número Apollo verde. Consenso Reddit + X ID.
Texto não monótono + volume baixo. Comentário ID 28/08: “Baileys/WAHA ok se o texto não for robô”.
403 no add = convite, não logout. Body traz AddRequest.Code. Quem trata 403 como session fail relinka o dia inteiro.
Salva LID e PN juntos. Quem matcha só E.164 quebra grupo.
Engine Go (GOWS/whatsmeow) pra daemon 24h. Node Baileys é lab.
Oficial Cloud se um dia for broadcast pago / OTP Authentication template. Coexistence existe; grupo grande de 200 ainda não.
IP residencial / 1 session / prune pre-keys / celular 1× por semana.


Pra o apollo-whatsapp
Não persiga repo da semana. O gold já está no fluxo:
textWAHA GOWS 2026.8.2
  check-exists → guarda lid + pn
  PUT contact → add
  403 → manda chat.whatsapp.com/{AddRequest.Code}
  cron só se WORKING
X “dev community” nessa query é ruído. O fio vivo é r/WhatsappBusinessAPI + r/n8n + issues WAHA/Evolution + Matrix #whatsmeow.Verify best logic of implementing is there any controler admin could send message example using chat own system to replicated chat web where code gets there n replicate on group?!
And replying users in automatized answers flow by key words received then triggered flowchart?
Investigate solutions and surprise me?Melhor lógica: não clonar o WhatsApp Web. O painel Apollo é o Web.

O grupo recebe o que o admin manda. O bot por palavra-chave vive no privado, não no grupo. Grupo com auto-reply em tudo = ban + ódio.

Dois canais, um número
textWAHA session (operação)
                           │
            ┌──────────────┴──────────────┐
            ▼                             ▼
     GRUPO @g.us                    DM 1:1 @c.us/@lid
     admin fala                     user fala
     cron de eventos                fluxo por keyword
     sem bot tagarela               handoff p/ humano
Réplica “chat web” = apollo-admin pane (o core já tem chat/notifications).

Webhook message / message.any → grava no core → a pane atualiza.

Admin digita na pane → POST /api/sendText no @g.us ou no JID da thread.

Não instala Chatwoot agora. Chatwoot é ouro se um dia tiver 5 atendentes. Hoje é elefante.
WAHA Dashboard já é um mini Web. Usa só pra QR/debug. Produto = pane Apollo.

Controller admin (espelho)





























Ação no WPVai pro ZapComposer “mandar no grupo”sendText → groupIdAbrir thread de um usersendText → jid deleResponder citandoreply_to no payloadDigitando…startTyping / stopTyping 800msSó admin vêmensagem com fromMe não dispara fluxo
Surpresa boa: prefixo interno wa/ (WAHA+Chatwoot já usa). No Apollo: /silent não replica, /grupo manda no @g.us, /dm 21… manda no privado.

Fluxo por keyword (o ouro)
Nunca responde toda mensagem do grupo.

No grupo só escuta âncora: !apollo !evento @apollo ou reply no bot.
No DM: state machine no _apollo_wa_flow_state.
textIN  message
    │
    ├─ session != WORKING        → drop
    ├─ fromMe                    → drop
    ├─ chat = grupo
    │     ├─ tem âncora?         → responde NO DM (whisper)
    │     └─ senão               → só espelha na pane
    └─ chat = DM
          ├─ estado HUMAN        → só pane, bot cala
          ├─ keyword EXATA
          │     humano / atendente → HUMAN + ping admin
          │     sair / para        → IDLE
          │     evento / hoje / rolê → GET /apollo/v1/events/upcoming
          │     entrar / grupo     → fila de join (o fluxo que já desenhamos)
          └─ senão                 → 1 fallback + “digita evento | entrar | humano”
Keyword exata / começa com, não regex gordo. Texto monótono + reply em grupo = o que o Reddit aponta como ban.

Handoff: user manda humano → bot para 2h → admin vê badge na pane.
Typebot/n8n = fase 2 se quiser desenhar fluxo no browser. MVP = JSON no core:
JSON{ "hoje": "events.upcoming", "entrar": "join.queue", "humano": "handoff" }

Surpresas que valem

Whisper de grupo — no grupo a pessoa manda !evento, a resposta cai no privado. Grupo limpo. Quase ninguém faz isso.
403 vira convite — já visto: AddRequest.Code no add. O fluxo entrar usa isso, não insiste add.
WOW no Zap — registry é WOW, não like. Reaction 😮 no grupo vira WOW no perfil Apollo (webhook message.reaction).
Evento cola URL — se alguém cola apollo.rio.br/evento/… no grupo, o plugin hidrata card e o cron não duplica.
Takeover por presença — admin abre a thread na pane = bot dessa thread mute até fechar. Igual Chatwoot “agent joined”, sem Chatwoot.


O que não fazer

Espelhar o Web inteiro no WP (mídia, status, 400 chats).
Bot respondendo cada “kkkk” no grupo.
Typebot + n8n + Chatwoot no dia 1.
Número verde Apollo nessa sessão.


Veredito: a lógica certa é pane Apollo = Web de operação + grupo como canal broadcast + DM como flowchart. Keyword só com âncora no grupo e estado no DM. Chatwoot/Typebot ficam na prateleira.
MVP: webhook → pane + composer grupo + 4 keywords no DM (evento, entrar, humano, sair). O resto é enfeite.ok u confirm found best logic, now method how wilkl be the structural base of it before scheduling how will run, how many screens, controls I have where, precisely answer my questions full of details to able myself to see it with imagination and detailed reply from uSim. Essa é a base estrutural antes de cron, código e Docker fino. É o mapa pra você enxergar o produto com os olhos.

Como roda (3 processos, não 1)
text[A] WordPress LocalWP / VPS
    apollo-core + apollo-whatsapp + login/users/events/admin
    PHP. Não segura sessão do Zap.

[B] WAHA Docker  (PC ou VPS Linux 24h)
    session "apollo"  engine GOWS
    QR uma vez. Volume ./sessions
    Webhook → https://seu-site/wp-json/apollo/v1/wa/webhook

[C] Celular do número de OPERAÇÃO
    Zap aberto ~1×/semana
    Não é o número verde Apollo
Se [B] dorme, o site continua. Fila e pane ficam. Nada sai no Zap.

Se [A] dorme, o Zap continua no celular; o plugin não vê.

Quantas telas: 7 (produto) + 1 (laboratório)



























































#TelaOndeQuem vê1SettingsWP Admin → Apollo → WhatsAppsó admin2Sessãomesma aba, bloco statusadmin3Fila de entradaApollo → WhatsApp → Filaadmin4Pane (réplica de chat)Apollo → WhatsApp → Conversasadmin5Fluxos (keywords)Apollo → WhatsApp → Fluxosadmin6Botão “Quero entrar”site logado (perfil / evento)user7Inbox do userperfil Apollo “meus pedidos Zap”userLWAHA Dashboardlocalhost:3000você, só setup/debug
Não tem tela 8 de “WhatsApp Web clone”. Mídia pesada, status, 400 conversas = Dashboard L, não o WP.

Tela 1 — Settings
Uma página. 4 cards.
Card A — Conexão

URL do WAHA (http://127.0.0.1:3000 ou VPS)
API key
Nome da session (apollo)
Botão Testar → ping WORKING / SCAN_QR / FAILED (bolinha verde/âmbar/vermelha)

Card B — Grupo

Campo groupId (120363…@g.us)
Botão Listar grupos da session (dropdown)
Botão Criar grupo (nome + você como admin)
Toggle “aprovar novos membros no Zap”
Preview do invite link (readonly)

Card C — Número

Número de operação (só display, vem da session me)
Aviso fixo: não use o Business verificado aqui

Card D — Comportamento

Toggle “espelhar grupo na pane”
Toggle “bot no DM”
Toggle “âncora no grupo” (!evento !apollo)
Prefixo interno (/ comandos do admin)
Rate: segundos entre sends (default 10)

Salva em options via apollo-core. Zero option solta.

Tela 2 — Sessão (pode ser o topo da 1)
Não é página nova. É a faixa viva:
text● WORKING    session apollo    5521…    last ping 12s
[ Recarregar ] [ Parar envios ] [ Abrir QR no Dashboard ]
Se não é WORKING: botões Aprovar e Composer desligam. Texto: “session down — nada sai”.

Tela 3 — Fila de entrada
Tabela. Uma linha = um pedido.
Colunas:

user (avatar Apollo) · telefone (candidato 12 e 13) · JID resolvido · pedido em · status · ações
Status que você vê de verdade:

queued — pediu, espera você
adding — tentando add
added — entrou
invited — 403, link mandado no DM
pending_approval — join-request no grupo
already — já tava
blocked — privacidade sem code
rejected — você recusou
session_down — tentou com WAHA morto

Controles por linha:

Aprovar · Recusar · Reenviar link · Abrir DM na pane
Controles no topo:

filtro por status · “aprovar os 10 queued” (com confirm) · busca nome/número
Aprovar não promete “entrou”. O label do botão é Processar entrada. Depois a linha muda sozinha (webhook / poll curto).

Tela 4 — Pane (o “chat web” de vocês)
Duas colunas, desktop. No iPhone: lista → thread.
Esquerda — lista

Aba Grupo (1 thread só: o @g.us)
Aba DMs (quem falou com o número)
Badge se estado HUMAN (user pediu atendente)
Ponto se bot mutado porque você abriu a thread

Direita — thread

Histórico (texto; mídia = “anexo, ver no Zap”)
No grupo: nome de quem falou + lid/pn se tiver
Composer
Toggle Destino: Grupo | esta DM
Botão Calar bot nesta thread / Liberar bot
Comandos no composer:
/grupo … força grupo
/silent só registra no WP
/humano marca thread HUMAN

Você digita “rolê sábado no D-Edge” + Enter → sai no grupo se Destino = Grupo.

A mensagem aparece na pane com fromMe (não dispara keyword).
Isso é o controle remoto. Não precisa abrir o celular pra falar no grupo.

Tela 5 — Fluxos
Lista simples. Não é Typebot.
Cada linha = trigger → ação.
Triggers de fábrica (editáveis):



































KeywordOnde escutaAçãoevento hoje rolêDM + âncora no grupopuxa upcoming, responde no DMentrar grupoDMcria linha na Filahumano atendenteDMestado HUMAN + badge panesair paraDMestado IDLE!evento !apollosó grupomesma ação, resposta no DM (whisper)
Controles: on/off por linha · editar texto de resposta · “usar eventos ao vivo” sim/não · fallback genérico.
Estado por user: _apollo_wa_flow_state = IDLE | FLOW | HUMAN.

HUMAN dura 2h ou até você clicar Liberar.

Tela 6 — Front do user
Não é admin. É um bloco no perfil / página do evento.

Se não tem telefone OTP: “confirma teu Zap primeiro” (apollo-login)
Se tem e não pediu: botão Quero entrar no grupo Apollo
Se pediu: estado em texto humano
“na fila” / “convite no teu Zap” / “já tá no grupo”

Zero chat do user com o grupo por aqui. Só o pedido.

Tela 7 — “Meus pedidos” do user
Uma lista curta no perfil. Pedido + data + status. Sem botão de add. Transparência.

Tela L — WAHA Dashboard
Só você. QR, event monitor, testar send.

Usuário Apollo nunca entra aqui.

Controles: onde está cada poder





















































Quero…Onde apertoLigar o Zap na APITela 1 + QR na LEscolher o grupoTela 1 Card BMandar texto no grupo agoraTela 4 composer, destino GrupoAprovar entradaTela 3 AprovarRecusar vipTela 3 RecusarMandar link de novoTela 3 Reenviar linkResponder um userTela 4, aba DMParar o bot de um userTela 4 “Calar bot” ou user manda humanoMudar o que o bot respondeTela 5Ver se a sessão vivefaixa Tela 2User pedir grupoTela 6
Cron / resumo semanal ainda não. Essa base existe sem relógio. O relógio só reusa Tela 4 (send no grupo) e Tela 1 (WORKING?).

O que existe por baixo (pra imaginar o dado)

Pedido de fila → tabela/meta registrada no core
Telefone → _apollo_whatsapp_phone + _apollo_wa_candidates (12 e 13 dígitos)
JID + LID quando o check-exists voltar
Opt-in → _apollo_whatsapp_optin
Estado do fluxo → _apollo_wa_flow_state
groupId + URL WAHA → option do plugin
Mensagens da pane → store do core (não DOM). HTML só renderiza

Nada sensível no data- do front. $apollo_rule.

Filme de 40 segundos (pra ver na cabeça)

Você abre WP. Faixa verde WORKING.
User no celular confirma OTP no cadastro, clica Quero entrar.
Tela 3 ganha linha queued.
Você aperta Processar.
Ou a linha vira added, ou invited e o user recebe o link no Zap.
No grupo alguém manda !evento. O grupo não explode. O user recebe a lista no privado.
Outro manda humano no DM. Badge na pane. Você abre a thread e fala como gente. Bot calado.
Você escreve na pane “sábado 23h Experience” → cai no grupo. Relógio ainda nem existe.


Estrutura fechada: 7 telas de produto, 1 lab, 3 processos.

Admin fala pelo pane. Fila é mesa de porta. Fluxo é porteiro no DM. Grupo é sala, não robô.
Quando isso estiver nítido pra você, o schedule é só mais um botão na Tela 1 + um job que usa o mesmo composer do grupo.Explore webhook securityWebhook sem HMAC no Apollo é porta aberta: qualquer um POSTA message e o plugin “responde”, aprova fila, manda no grupo. WAHA já nasceu com a trava certa — o plugin tem que exigir, não “se tiver header”.

O que o WAHA manda
Headers reais do sender (WebhookSender.ts):

























HeaderPra quêX-Webhook-HmacHMAC-SHA512 do body cruX-Webhook-Hmac-Algorithmsha512X-Webhook-Request-Idid único (replay)X-Webhook-Timestampunix ms (janela de tempo)
Assinatura:
textX-Webhook-Hmac = hex( HMAC_SHA512( hmac.key, raw_body ) )
Config na session:
JSON"webhooks": [{
  "url": "https://apollo.rio.br/wp-json/apollo/v1/wa/webhook",
  "events": ["session.status", "message", "message.any", "message.reaction", "group.v2.participants"],
  "hmac": { "key": "<32+ bytes random>" }
}]
Env global: WHATSAPP_HOOK_HMAC_KEY. Preferir por session no Apollo.

Pipeline no WP (ordem rígida)
POST /wp-json/apollo/v1/wa/webhook — permission_callback não é is_user_logged_in. É machine. Auth = criptografia.

HTTPS only em prod. LocalWP lab ok.
Body raw — file_get_contents('php://input'). Se o WP parsear JSON antes, o HMAC quebra.
Header obrigatório. Sem X-Webhook-Hmac → 401, log, para.
Algo allowlist. Só sha512. Outro valor → 401.
HMAC hash_equals.
hash_hmac('sha512', $raw, $secret) vs header. Timing-safe.
Timestamp. |now_ms - X-Webhook-Timestamp| > 5 min → 401 replay.
Request-Id. Transient apollo_wa_hook_{id} TTL 10 min. Repetiu → 200 vazio (ack, sem reprocessar). WAHA retria erro; idempotência é obrigatória.
Session name. payload.session === option session. Senão drop.
Tamanho. Body > 256 KB → 413.
Depois disso: switch do event. Nunca eval, nunca SQL cru com body.

Secret: wp_generate_password(48, true, true) na Tela 1, gravado em option não autoload se possível. Nunca no JS, nunca no DOM, nunca no Git.

O outro lado (WP → WAHA)
Webhook é inbound. Outbound também é superfície.

Todo wp_remote_post leva X-Api-Key.
No Docker: WAHA_API_KEY=sha512:{hash} — o container guarda hash; o plugin guarda a chave plain só no WP.
Dashboard com user/pass fortes. Em prod: WAHA_DASHBOARD_ENABLED=false se não precisa.
WAHA não na internet aberta. Bind 127.0.0.1 ou rede Docker. WP fala com ele na LAN. Se VPS remoto: túnel / allowlist IP do WP.
Media download do WAHA também pede a key.


O que não conta como segurança

































AtalhoPor que caiQuery ?token=abc na URL do webhookvaza em access log, CDN, referrer“Se o JSON tem session: apollo”qualquer um inventa o JSONIP allowlist sozinhoDocker/NAT muda; HMAC continua necessárioComparar HMAC com ===timing attack (menor, mas não use)register_rest_route com __return_true e zero HMAC12-point verificator do core: failLogar body + hmac keyvazou o segredo
Nonce WP não se aplica aqui. Nonce é browser logado. Webhook é server-to-server.

Ameaças que o HMAC sozinho não mata

Replay dentro de 5 min → Request-Id resolve.
Retry do WAHA depois que você já processou o add → Request-Id + status da fila (adding/added) evita double-add.
SSRF reverso se um dia o plugin buscar URL que veio no payload (hasMedia) — só baixa media do host WAHA allowlist, com API key.
Grupo flood → rate no handler (ex. 30 events/min/session), fila o resto.
Admin composer → nonce + cap manage_options / cap Apollo. Webhook nunca dispara Aprovar. Aprovar é humano na Tela 3.


Checklist Apollo (auditoria)

 HMAC key ≥ 32 bytes, gerada no activate
 Verificação SHA512 + hash_equals no raw body
 Timestamp ±5 min
 Dedupe Request-Id
 Session allowlist
 REST route sem cookie auth, com fail-closed
 Key não vai pro front
 WAHA API key no outbound
 WAHA não público
 401 não revela se a key está “quase certa”

Fail-closed: dúvida → não processa. 200 só depois de HMAC ok (pra o WAHA parar de retriar lixo, use 401 no fail de auth e 200 no replay já visto).
Isso é a porta. Sem isso, as 7 telas são teatro.write me flow chart  structure getting all together of brainstorm and prepare waha-registry.json for bein the bible during building it up where we define now all namings slugs etc;
BE SURE THAT THE PRESENTED IS BEST SOLUTION!!!
textWebhook sem HMAC no Apollo é porta aberta: qualquer um POSTA `message` e o plugin “responde”, aprova fila, manda no grupo. WAHA já nasceu com a trava certa — o plugin tem que **exigir**, não “se tiver header”.

---

### O que o WAHA manda

Headers reais do sender (`WebhookSender.ts`):

| Header | Pra quê |
|---|---|
| `X-Webhook-Hmac` | HMAC-**SHA512** do **body cru** |
| `X-Webhook-Hmac-Algorithm` | `sha512` |
| `X-Webhook-Request-Id` | id único (replay) |
| `X-Webhook-Timestamp` | unix ms (janela de tempo) |

Assinatura:
X-Webhook-Hmac = hex( HMAC_SHA512( hmac.key, raw_body ) )
textConfig na session:

```json
"webhooks": [{
  "url": "https://apollo.rio.br/wp-json/apollo/v1/wa/webhook",
  "events": ["session.status", "message", "message.any", "message.reaction", "group.v2.participants"],
  "hmac": { "key": "<32+ bytes random>" }
}]
Env global: WHATSAPP_HOOK_HMAC_KEY. Preferir **por session** no Apollo.

### Pipeline no WP (ordem rígida)
POST /wp-json/apollo/v1/wa/webhook — permission_callback **não** é is_user_logged_in. É machine. Auth = criptografia.

**HTTPS only** em prod. LocalWP lab ok.
**Body raw** — file_get_contents('php://input'). Se o WP parsear JSON antes, o HMAC quebra.
**Header obrigatório.** Sem X-Webhook-Hmac → 401, log, para.
**Algo allowlist.** Só sha512. Outro valor → 401.
**HMAC hash_equals.**
hash_hmac('sha512', $raw, $secret) vs header. Timing-safe.
**Timestamp.** |now_ms - X-Webhook-Timestamp| > 5 min → 401 replay.
**Request-Id.** Transient apollo_wa_hook_{id} TTL 10 min. Repetiu → 200 vazio (ack, sem reprocessar). WAHA **retria** erro; idempotência é obrigatória.
**Session name.** payload.session === option session. Senão drop.
**Tamanho.** Body > 256 KB → 413.
**Depois** disso: switch do event. Nunca eval, nunca SQL cru com body.

Secret: wp_generate_password(48, true, true) na Tela 1, gravado em option **não** autoload se possível. Nunca no JS, nunca no DOM, nunca no Git.

### O outro lado (WP → WAHA)
Webhook é inbound. Outbound também é superfície.

Todo wp_remote_post leva X-Api-Key.
No Docker: WAHA_API_KEY=sha512:{hash} — o container guarda hash; o plugin guarda a chave **plain** só no WP.
Dashboard com user/pass fortes. Em prod: WAHA_DASHBOARD_ENABLED=false se não precisa.
WAHA **não** na internet aberta. Bind 127.0.0.1 ou rede Docker. WP fala com ele na LAN. Se VPS remoto: túnel / allowlist IP do WP.
Media download do WAHA também pede a key.


### O que **não** conta como segurança

































AtalhoPor que caiQuery ?token=abc na URL do webhookvaza em access log, CDN, referrer“Se o JSON tem session: apollo”qualquer um inventa o JSONIP allowlist sozinhoDocker/NAT muda; HMAC continua necessárioComparar HMAC com ===timing attack (menor, mas não use)register_rest_route com __return_true e zero HMAC12-point verificator do core: failLogar body + hmac keyvazou o segredo
Nonce WP **não** se aplica aqui. Nonce é browser logado. Webhook é server-to-server.

### Ameaças que o HMAC sozinho não mata

**Replay** dentro de 5 min → Request-Id resolve.
**Retry do WAHA** depois que você já processou o add → Request-Id + status da fila (adding/added) evita double-add.
**SSRF reverso** se um dia o plugin *buscar* URL que veio no payload (hasMedia) — só baixa media do host WAHA allowlist, com API key.
**Grupo flood** → rate no handler (ex. 30 events/min/session), fila o resto.
**Admin composer** → nonce + cap manage_options / cap Apollo. Webhook **nunca** dispara Aprovar. Aprovar é humano na Tela 3.


### Checklist Apollo (auditoria)

 HMAC key ≥ 32 bytes, gerada no activate
 Verificação SHA512 + hash_equals no raw body
 Timestamp ±5 min
 Dedupe Request-Id
 Session allowlist
 REST route sem cookie auth, com fail-closed
 Key não vai pro front
 WAHA API key no outbound
 WAHA não público
 401 não revela se a key está “quase certa”

Fail-closed: dúvida → não processa. 200 só depois de HMAC ok (pra o WAHA parar de retriar lixo, use 401 no fail de auth e 200 no replay já visto).
Isso é a porta. Sem isso, as 7 telas são teatro.
Sim. Essa é a base estrutural **antes** de cron, código e Docker fino. É o mapa pra você enxergar o produto com os olhos.

## Como roda (3 processos, não 1)
text[A] WordPress LocalWP / VPS
    apollo-core + apollo-whatsapp + login/users/events/admin
    PHP. Não segura sessão do Zap.

[B] WAHA Docker  (PC ou VPS Linux 24h)
    session "apollo"  engine GOWS
    QR uma vez. Volume ./sessions
    Webhook → https://seu-site/wp-json/apollo/v1/wa/webhook

[C] Celular do número de OPERAÇÃO
    Zap aberto ~1×/semana
    Não é o número verde Apollo
Se [B] dorme, o site continua. Fila e pane ficam. Nada sai no Zap.
Se [A] dorme, o Zap continua no celular; o plugin não vê.

## Quantas telas: **7** (produto) + **1** (laboratório)



























































#TelaOndeQuem vê1SettingsWP Admin → Apollo → WhatsAppsó admin2Sessãomesma aba, bloco statusadmin3Fila de entradaApollo → WhatsApp → Filaadmin4Pane (réplica de chat)Apollo → WhatsApp → Conversasadmin5Fluxos (keywords)Apollo → WhatsApp → Fluxosadmin6Botão “Quero entrar”site logado (perfil / evento)user7Inbox do userperfil Apollo “meus pedidos Zap”userLWAHA Dashboardlocalhost:3000você, só setup/debug
Não tem tela 8 de “WhatsApp Web clone”. Mídia pesada, status, 400 conversas = Dashboard L, não o WP.

### Tela 1 — Settings
Uma página. 4 cards.
**Card A — Conexão**

URL do WAHA (http://127.0.0.1:3000 ou VPS)
API key
Nome da session (apollo)
Botão **Testar** → ping WORKING / SCAN_QR / FAILED (bolinha verde/âmbar/vermelha)

**Card B — Grupo**

Campo groupId (120363…@g.us)
Botão **Listar grupos da session** (dropdown)
Botão **Criar grupo** (nome + você como admin)
Toggle “aprovar novos membros no Zap”
Preview do invite link (readonly)

**Card C — Número**

Número de operação (só display, vem da session me)
Aviso fixo: *não use o Business verificado aqui*

**Card D — Comportamento**

Toggle “espelhar grupo na pane”
Toggle “bot no DM”
Toggle “âncora no grupo” (!evento !apollo)
Prefixo interno (/ comandos do admin)
Rate: segundos entre sends (default 10)

Salva em options via **apollo-core**. Zero option solta.

### Tela 2 — Sessão (pode ser o topo da 1)
Não é página nova. É a faixa viva:
text● WORKING    session apollo    5521…    last ping 12s
[ Recarregar ] [ Parar envios ] [ Abrir QR no Dashboard ]
Se não é WORKING: botões Aprovar e Composer **desligam**. Texto: “session down — nada sai”.

### Tela 3 — Fila de entrada
Tabela. Uma linha = um pedido.
Colunas:
user (avatar Apollo) · telefone (candidato 12 e 13) · JID resolvido · pedido em · status · ações
Status que você vê de verdade:

queued — pediu, espera você
adding — tentando add
added — entrou
invited — 403, link mandado no DM
pending_approval — join-request no grupo
already — já tava
blocked — privacidade sem code
rejected — você recusou
session_down — tentou com WAHA morto

Controles **por linha**:
**Aprovar** · **Recusar** · **Reenviar link** · **Abrir DM na pane**
Controles **no topo**:
filtro por status · “aprovar os 10 queued” (com confirm) · busca nome/número
Aprovar **não** promete “entrou”. O label do botão é **Processar entrada**. Depois a linha muda sozinha (webhook / poll curto).

### Tela 4 — Pane (o “chat web” de vocês)
Duas colunas, desktop. No iPhone: lista → thread.
**Esquerda — lista**

Aba **Grupo** (1 thread só: o @g.us)
Aba **DMs** (quem falou com o número)
Badge se estado HUMAN (user pediu atendente)
Ponto se bot mutado porque **você** abriu a thread

**Direita — thread**

Histórico (texto; mídia = “anexo, ver no Zap”)
No grupo: nome de quem falou + lid/pn se tiver
Composer
Toggle **Destino: Grupo | esta DM**
Botão **Calar bot nesta thread** / **Liberar bot**
Comandos no composer:
/grupo … força grupo
/silent só registra no WP
/humano marca thread HUMAN

Você digita “rolê sábado no D-Edge” + Enter → sai no grupo se Destino = Grupo.
A mensagem aparece na pane com fromMe (não dispara keyword).
Isso **é** o controle remoto. Não precisa abrir o celular pra falar no grupo.

### Tela 5 — Fluxos
Lista simples. Não é Typebot.
Cada linha = trigger → ação.
Triggers de fábrica (editáveis):



































KeywordOnde escutaAçãoevento hoje rolêDM + âncora no grupopuxa upcoming, responde **no DM**entrar grupoDMcria linha na Filahumano atendenteDMestado HUMAN + badge panesair paraDMestado IDLE!evento !apollosó grupomesma ação, resposta no **DM** (whisper)
Controles: on/off por linha · editar texto de resposta · “usar eventos ao vivo” sim/não · fallback genérico.
Estado por user: _apollo_wa_flow_state = IDLE | FLOW | HUMAN.
HUMAN dura 2h ou até você clicar Liberar.

### Tela 6 — Front do user
Não é admin. É um bloco no perfil / página do evento.

Se **não** tem telefone OTP: “confirma teu Zap primeiro” (apollo-login)
Se tem e **não** pediu: botão **Quero entrar no grupo Apollo**
Se pediu: estado em texto humano
“na fila” / “convite no teu Zap” / “já tá no grupo”

Zero chat do user com o grupo por aqui. Só o pedido.

### Tela 7 — “Meus pedidos” do user
Uma lista curta no perfil. Pedido + data + status. Sem botão de add. Transparência.

### Tela L — WAHA Dashboard
Só você. QR, event monitor, testar send.
Usuário Apollo **nunca** entra aqui.

## Controles: onde está cada poder





















































Quero…Onde apertoLigar o Zap na APITela 1 + QR na LEscolher o grupoTela 1 Card BMandar texto no grupo agoraTela 4 composer, destino GrupoAprovar entradaTela 3 AprovarRecusar vipTela 3 RecusarMandar link de novoTela 3 Reenviar linkResponder um userTela 4, aba DMParar o bot de um userTela 4 “Calar bot” ou user manda humanoMudar o que o bot respondeTela 5Ver se a sessão vivefaixa Tela 2User pedir grupoTela 6
Cron / resumo semanal **ainda não**. Essa base existe sem relógio. O relógio só reusa Tela 4 (send no grupo) e Tela 1 (WORKING?).

## O que existe por baixo (pra imaginar o dado)

Pedido de fila → tabela/meta **registrada no core**
Telefone → _apollo_whatsapp_phone + _apollo_wa_candidates (12 e 13 dígitos)
JID + LID quando o check-exists voltar
Opt-in → _apollo_whatsapp_optin
Estado do fluxo → _apollo_wa_flow_state
groupId + URL WAHA → option do plugin
Mensagens da pane → store do core (não DOM). HTML só renderiza

Nada sensível no data- do front. $apollo_rule.

## Filme de 40 segundos (pra ver na cabeça)

Você abre WP. Faixa **verde WORKING**.
User no celular confirma OTP no cadastro, clica **Quero entrar**.
Tela 3 ganha linha queued.
Você aperta **Processar**.
Ou a linha vira added, ou invited e o user recebe o link no Zap.
No grupo alguém manda !evento. O grupo **não** explode. O user recebe a lista no privado.
Outro manda humano no DM. Badge na pane. Você abre a thread e fala como gente. Bot calado.
Você escreve na pane “sábado 23h Experience” → cai no grupo. Relógio ainda nem existe.


**Estrutura fechada:** 7 telas de produto, 1 lab, 3 processos.
Admin fala pelo **pane**. Fila é **mesa de porta**. Fluxo é **porteiro no DM**. Grupo é **sala**, não robô.
Quando isso estiver nítido pra você, o schedule é só mais um botão na Tela 1 + um job que usa o mesmo composer do grupo.
**Melhor lógica: não clonar o WhatsApp Web. O painel Apollo **é** o Web.**
O grupo recebe o que o admin manda. O bot por palavra-chave vive no **privado**, não no grupo. Grupo com auto-reply em tudo = ban + ódio.

### Dois canais, um número
textWAHA session (operação)
                           │
            ┌──────────────┴──────────────┐
            ▼                             ▼
     GRUPO @g.us                    DM 1:1 @c.us/@lid
     admin fala                     user fala
     cron de eventos                fluxo por keyword
     sem bot tagarela               handoff p/ humano
Réplica “chat web” = apollo-admin pane (o core já tem chat/notifications).
Webhook message / message.any → grava no core → a pane atualiza.
Admin digita na pane → POST /api/sendText no @g.us ou no JID da thread.
Não instala Chatwoot agora. Chatwoot é ouro se um dia tiver 5 atendentes. Hoje é elefante.
WAHA Dashboard já é um mini Web. Usa só pra QR/debug. Produto = pane Apollo.

### Controller admin (espelho)





























Ação no WPVai pro ZapComposer “mandar no grupo”sendText → groupIdAbrir thread de um usersendText → jid deleResponder citandoreply_to no payloadDigitando…startTyping / stopTyping 800msSó admin vêmensagem com fromMe não dispara fluxo
Surpresa boa: prefixo interno wa/ (WAHA+Chatwoot já usa). No Apollo: /silent não replica, /grupo manda no @g.us, /dm 21… manda no privado.

### Fluxo por keyword (o ouro)
**Nunca** responde toda mensagem do grupo.
No grupo só escuta **âncora**: !apollo !evento @apollo ou reply no bot.
No DM: state machine no _apollo_wa_flow_state.
textIN  message
    │
    ├─ session != WORKING        → drop
    ├─ fromMe                    → drop
    ├─ chat = grupo
    │     ├─ tem âncora?         → responde NO DM (whisper)
    │     └─ senão               → só espelha na pane
    └─ chat = DM
          ├─ estado HUMAN        → só pane, bot cala
          ├─ keyword EXATA
          │     humano / atendente → HUMAN + ping admin
          │     sair / para        → IDLE
          │     evento / hoje / rolê → GET /apollo/v1/events/upcoming
          │     entrar / grupo     → fila de join (o fluxo que já desenhamos)
          └─ senão                 → 1 fallback + “digita evento | entrar | humano”
Keyword **exata / começa com**, não regex gordo. Texto monótono + reply em grupo = o que o Reddit aponta como ban.
Handoff: user manda humano → bot para 2h → admin vê badge na pane.
Typebot/n8n = fase 2 se quiser desenhar fluxo no browser. MVP = JSON no core:
JSON{ "hoje": "events.upcoming", "entrar": "join.queue", "humano": "handoff" }

### Surpresas que valem

**Whisper de grupo** — no grupo a pessoa manda !evento, a resposta cai **no privado**. Grupo limpo. Quase ninguém faz isso.
**403 vira convite** — já visto: AddRequest.Code no add. O fluxo entrar usa isso, não insiste add.
**WOW no Zap** — registry é WOW, não like. Reaction 😮 no grupo vira WOW no perfil Apollo (webhook message.reaction).
**Evento cola URL** — se alguém cola apollo.rio.br/evento/… no grupo, o plugin hidrata card e o cron não duplica.
**Takeover por presença** — admin abre a thread na pane = bot dessa thread mute até fechar. Igual Chatwoot “agent joined”, sem Chatwoot.


### O que **não** fazer

Espelhar o Web inteiro no WP (mídia, status, 400 chats).
Bot respondendo cada “kkkk” no grupo.
Typebot + n8n + Chatwoot no dia 1.
Número verde Apollo nessa sessão.


**Veredito:** a lógica certa é pane Apollo = Web de operação + grupo como canal broadcast + DM como flowchart. Keyword só com âncora no grupo e estado no DM. Chatwoot/Typebot ficam na prateleira.
MVP: webhook → pane + composer grupo + 4 keywords no DM (evento, entrar, humano, sair). O resto é enfeite.
**Ouro 2026 não é estrela. É LID + engine Go + 403 com AddRequest.Code.**
X está poluído (“waha” = “lá” em hindi). Reddit/HN/GitHub issues é onde o código mora.

### Tópicos que a comunidade está discutindo agora























































TemaOndeO que é de fato**@lid substitui telefone**Reddit BB10, Evolution issues, GREEN-API, WAHA #2208Identidade anônima. Add/reply quebra se você só guarda @c.us. Rollout até fim de 2026**Erro 463 / reachout timelock**Baileys v7 rcConta limitada. Não é “session caiu”**Coexistence Cloud + app**r/WhatsappBusinessAPI jul/2026Oficial: mesmo número no app **e** Cloud. Docs velhos mentem**Service messages pagam em out/2026**mesmo subReply 24h deixa de ser grátis**Utility sem aprovação de template**mesmo sub, H2 2026IA classifica. Abuse = strike 7d → 30d → ban**WAHA vs Evolution no n8n**r/n8n abr/2026Os dois caem. WAHA ganha por **vários engines**. Evolution = mais Baileys**Ban em setup / Grok Bot / “automation”**r/whatsapp, r/WABAMeta flagra sessão Web ligada em bot. Número pessoal ≠ lab**lotusbail / supply chain Baileys**WaSP readme, Dez 2025Fork suspeito. Não npm i aleatório**Pre-keys explodem e corrompem session**OpenClaw PR2–3 semanas sem prune = logout

### Diamantes (código, não hype)
**1. tulir/whatsmeow** — 8.5/10
Go, MPL, LID nativo (GetPNForLID no add). É o que o **WAHA GOWS** e o mautrix/Beeper usam. Sessão longa > Node.
*Hidden?* Não é hidden, é o que a galera séria escolhe quando cansa do Baileys.
**2. WAHA engine GOWS** (não o repo inteiro) — 8/10 pra Apollo
Keepalive env, LID→PN, BR digit app, AddRequest.Code no 403. Hedging: troca engine sem trocar o plugin. HN: “usei WAHA em cima do Baileys/whatsmeow”.
**3. Baileys v7 rc (WhiskeySockets)** — 7.5/10 no protocolo, 4/10 no teu plugin
Ouro: mapping LID no onWhatsApp, 463, novo QR, mutex. **Não** clone isso no WordPress. Deixa o WAHA puxar.
**4. Fork innovatorssoft/Baileys 7.4.7** (ago/2026) — 7/10 pontual
Persistir PN↔LID no onWhatsApp. Sinal do que importa. Não troque o motor por um fork de 44 stars sem auditoria.
**5. Gnaneshdivi/personal-whatsapp-mcp** — 6.5/10 ideia
whatsmeow via neonize, MCP local. Ouro é a **pilha Go**, não o MCP.
**Não é ouro:** whatsapp-keeper (OpenClaw only), Venom (parado 2024), Wawp, MultiWA WP form, WaSP como substituto do WAHA, Chrome extension “bulk sender”.

### O ouro operacional (o que todo mundo que não toma ban faz)

**Número de operação ≠ número Apollo verde.** Consenso Reddit + X ID.
**Texto não monótono + volume baixo.** Comentário ID 28/08: “Baileys/WAHA ok se o texto não for robô”.
**403 no add = convite, não logout.** Body traz AddRequest.Code. Quem trata 403 como session fail relinka o dia inteiro.
**Salva LID e PN juntos.** Quem matcha só E.164 quebra grupo.
**Engine Go (GOWS/whatsmeow) pra daemon 24h.** Node Baileys é lab.
**Oficial Cloud** se um dia for broadcast pago / OTP Authentication template. Coexistence existe; grupo grande de 200 **ainda não**.
**IP residencial / 1 session / prune pre-keys / celular 1× por semana.**


### Pra o apollo-whatsapp
Não persiga repo da semana. O gold já está no fluxo:
textWAHA GOWS 2026.8.2
  check-exists → guarda lid + pn
  PUT contact → add
  403 → manda chat.whatsapp.com/{AddRequest.Code}
  cron só se WORKING
X “dev community” nessa query é ruído. O fio vivo é **r/WhatsappBusinessAPI + r/n8n + issues WAHA/Evolution + Matrix #whatsmeow**.
**Não. whatsapp-keeper não é o melhor repo pra você.** É um daemon colado no **OpenClaw**, não no WAHA. Subir ele do lado do Docker é duas mãos no mesmo soquete — disputa sessão e apaga notificação do celular.
O keepalive **já veio no motor** em agosto/2026.

### Keepalive: o que usar de verdade













































PeçaServe pro Apollo?Por quê**WAHA GOWS** WAHA_GOWS_KEEPALIVE_INTERVAL_MIN/MAX**Sim — default**Ping nativo no engine. Sem repo extramarkOnlineOnConnect: false + presence só no número **bot**Simavailable 24h no número pessoal **mata push** do iPhoneVolume ./sessions + restart limpoSimCorrupção = pre-keys infinitos (~2–3 sem)baileys-keep-aliveSó se você **abandonar** WAHA e escrever Baileys puroHeartbeat + backoff. Não pluga no WPwhatsapp-keeper**Não**OpenClaw onlyWaSPNão agoraOutra camada em cima do Baileys. Troca o WAHA inteirofazer-ai/baileys-api health/stallIdeia boa, não clonarDetecta “socket vivo mas send morto”
Config WAHA (produção):
Bash-e WAHA_GOWS_KEEPALIVE_INTERVAL_MIN=20
-e WAHA_GOWS_KEEPALIVE_INTERVAL_MAX=25
-v ./sessions:/app/.sessions
Plugin: webhook session.status → se não for WORKING, **não** add e **não** cron.
Celular: abre o Zap 1×/semana. Isso vale mais que qualquer daemon.

### Truque “salva contato → add no grupo”
Existe e é endpoint oficial do WAHA, não gambiarra de fórum:
textPUT /api/{session}/contacts/{chatId}
{ "firstName": "Apollo", "lastName": "User 123" }
Isso grava no **agenda do celular** ligado na sessão. Doc manda **repetir 2–3 vezes** com alguns segundos no meio (vários apps Zap no phone = flaky).
Depois:
textPOST /api/{session}/groups/{id}/participants/add
{ "participants": [{ "id": "5521999...@c.us" }] }
**O que isso resolve e o que não resolve**

Ajuda **só** se a privacidade do *outro* for “Meus contatos” **e** ele também tiver o número de operação salvo. Um lado só não basta.
Se for “Ninguém” / “exceto todos” → **403 eterno**. Contato não fura.
Em GOWS (abr/2026) o 403 às vezes **derrubava a sessão**. HTTP 200 com Error: 403 no body. **Não trate 403 como logout.** Pegue o payload.

Ouro escondido no 403:
JSON"Error": 403,
"AddRequest": {
  "Code": "Xy7Kp9LmQ2vR8sTa",
  "Expiration": "2026-04-09T11:44:32+03:00"
}
Esse Code **é o convite**. Manda no privado:
https://chat.whatsapp.com/Xy7Kp9LmQ2vR8sTa
Não precisa de segundo GET de invite-code quando o add já devolveu isso.

### Cadeia certa no apollo-whatsapp
text1. session == WORKING?
2. check-exists nas 2 formas BR → jid
3. PUT contact (2x, sleep 3s)
4. sleep 5s
5. POST participants/add
6. 200 + sem Error     → added
7. 409                 → already
8. Error 403 + Code    → sendText(jid, link) → invited
9. 403 sem Code        → GET invite-code → invited
10. nunca retry add em loop
Agenda no grupo (schedule): **WP-Cron + lock**. Volume baixo. Não clona n8n/Bull só pra isso. Fila: um send a cada 8–15s (rate limit informal anti-ban). Cron **só** se session WORKING.
Remote send no grupo já é:
textPOST /api/sendText
{ "session": "apollo", "chatId": "120363...@g.us", "text": "..." }
Nada de repo extra.

**CTO:**
Keepalive = env do GOWS, não clone do keeper.
Contato = PUT contacts antes do add, com espera.
Privacidade = use o AddRequest.Code do 403.
Schedule = Cron do core.
Isso é o stack 2026 sem inchação.
Os três problemas **não se resolvem no PHP**. São regra do WhatsApp em 2026. O plugin trata com **cadeia de fallback**, não com um POST único.

### 1. Telefone BR (9º dígito + @lid)
O que quebra o match:

Celular ANATEL: 55 + DDD + 9 + 8 dígitos → **13 dígitos** (5521999…).
Conta **antiga** no Zap ainda vive no wa_id **sem** o 9 → **12 dígitos** (5521998…). Webhook ≠ o que o user digitou no Apollo.
Fixo **não** leva 9. Quem “normaliza” todo BR com 9 inventa número morto (45 3278… vira 45 93278…).
Em grupo, o id vira @lid (sem telefone). Add com 5511…@c.us às vezes falha se o grupo está em modo LID. Rollout até fim de 2026.

**Solução no apollo-whatsapp (padrão 2026):**

Salva o que o user digitou **e** a variante (com/sem 9). Meta: _apollo_wa_candidates.
Antes do add: GET /api/contacts/check-exists nas **duas** formas. Usa o JID que o WAHA devolver.
Motor: WAHA **2026.8.2+** (app “Brazilian Phone Numbers” + participant.pn no LID). Engine **GOWS** resolve @lid → PN.
Nunca confie no input cru. Confie no chatId que o check-exists devolveu.

textinput  21 99999-0000
try    5521999990000@c.us
try    552199990000@c.us     ← conta pré-2012
use    o que exists=true

### 2. Privacidade “não pode add”
Isso **não é bug de API**. Settings → Privacidade → Grupos:

Todos
Meus contatos
Meus contatos, exceto…
Ninguém (via “exceto todos”)

Se o user **não tem o número de operação salvo**, o add volta **403**. API nenhuma fura isso. Tentar de novo = report + ban.
**Cadeia que funciona (nessa ordem):**






























#AçãoQuandoAparticipants/add com JID validadoPrivacidade = TodosBSalvar o user como **contato** no número WAHA, esperar ~3s, add de novoAjuda se a regra é “Meus contatos” *e* o user também salvou vocês — raroCGET /groups/{id}/invite-code → manda https://chat.whatsapp.com/{code} no **privado****Caminho padrão Rio**DLiga “aprovar novos membros” no grupo + user entra pelo link → admin (plugin) dá approve no join-requestVIP que some / lista fechada
WAHA já tem as peças: invite-code, join-requests approve/reject, membership-approval.
UX Apollo: botão admin **não** promete “entrou”. Promete **“convite enviado”**. Status na fila: added | invited | pending_approval | blocked_privacy.
Não faça o número Apollo Business no WAHA. Add em massa no verificado queima o verde.

### 3. Sessão cair
Causas reais em 2026, não “Docker misterioso”:


















































SintomaCausaAntídotoCai ~24h, código **428**Idle no WebSocketPresence available + keepalive. WAHA GOWS: WAHA_GOWS_KEEPALIVE_INTERVAL_MIN/MAX (ago/2026)**401**Logout no celular / “desconectar dispositivos”QR de novo. Não reconecta sozinho**403**Ban / restrictTroca número. Acabou**440** device replacedDois WAHA/Evolution no **mesmo** número1 session = 1 númeroInvalid patch mac (NOWEB)Auth state podreApaga session, relinka. Evita NOWEB pra produçãoCai no Windows + WSL2Race no authState (Evolution 2.3.7)**Não hospeda Evolution no teu Windows.** VPS Linux ou WAHA Docker nativoIP de datacenterSync inicial leva pause/disconnectIP residencial / 4G se possível14 dias sem abrir o appMulti-device mata a sessãoAbrir o Zap no celular 1× por semana
Padrão que a galera está usando agora: **daemon de keepalive** (presence a cada ~25s — timeout do Zap ~30s) + snapshot da pasta sessions antes de restart. Ex.: whatsapp-keeper.
Stack Apollo recomendada pra sessão:
textWAHA 2026.8.2  engine=GOWS
volume persistente  ./sessions
1 número de operação (não o Business)
VPS Linux 24h  (não LocalWP no notebook)
webhook session.status → WP pinta “session DOWN”
admin só aprova se status=WORKING
celular: abre o Zap domingo
Pairing code (8 dígitos) é mais estável que QR em loop. Mesmo número não pode estar em outro Web/Desktop “oficial” brigando com o WAHA.

### O que o plugin faz com isso (código mínimo)
textapprove(user):
  jid = resolve_br(user)          # 2 candidatos + check-exists
  if session != WORKING: fail "session down"
  r = waha.add(group, jid)
  if r == 200: status=added
  elif r == 403: 
      link = waha.invite_code(group)
      waha.sendText(jid, link)    # privado
      status=invited
  elif r == 409: status=already
Cron de mensagem **só roda** se GET /api/sessions/apollo = WORKING. Senão enfileira e não queima retry no Zap.

**Resumo CTO:**
BR = guardar **duas** formas + acreditar no check-exists.
Privacidade = **link**, add é bônus.
Sessão = GOWS + VPS + 1 número + presence + volume. Windows/LocalWP é lab, não produção.
Isso é a parte 6/10. O HTTP do plugin continua 4/10.
**Não é difícil o plugin. O que é chato é o Zap, não o PHP.**
Dificuldade real do apollo-whatsapp: **4/10 no código, 6/10 no operacional.**
Não existe plugin WP pronto pra clonar que faça fila + aprovar + add no grupo + cron. O atalho é **não reescrever o WhatsApp** — só clonar/subir o gateway e escrever a casca Apollo.

### O que clonar / não clonar








































CoisaAçãoVale?WAHAdocker pull — **não** git clone pra dentro do WPSim. Motor pronto: criar grupo, add, send, join-requestevolution-apisó se WAHA travar no WindowsPlano B BRwaha-docscopiar endpoints PHPSimLaravel WAHA SDK / MultiWA WP pluginolhar endpoints, **não** mergeNão vale o débitonetflie Cloud PHPsó OTP 1:1 oficialOutro número, outro diaWawpmortoNão
WAHA já entrega o que o fluxo precisa:
textPOST /api/{session}/groups
POST /api/{session}/groups/{id}/participants/add
POST /api/sendText
GET  /api/{session}/groups/{id}/participants/join-requests
Plus Core é grátis. Tem helper do 9º dígito BR.

### O que você **escreve** (só isso)
apollo-whatsapp = ~4 arquivos, dependente de apollo-core:

Cliente HTTP (wp_remote_post)
Settings (URL, key, session, groupId)
Fila + botão Aprovar no apollo-admin
Cron de mensagem

Hooks: apollo/login/..., user_meta _apollo_whatsapp_phone, REST eventos já existem. **Não inventa CPT fora do core.**

### Tempo honesto (Grok Fast + VSCode)



































FatiaTempoClone acelera?Docker WAHA + QR30–90 minSim — zero códigoPlugin settings + ping sessionmeio diaSim — 80 linhasFila + Aprovar + add1 diaNão — é a cara ApolloCron texto no grupomeio diaQuase nãoBR phone + privacidade “não pode add” + sessão cair2–4 diasNão. Aqui dói
**MVP usável:** 2–3 dias.
**Estável no Rio, grupo real:** 1–2 semanas (add falha se o user bloqueou “add em grupo”).

### Onde pega

Windows + Docker + LocalWP = o setup, não o plugin
PC/VPS tem que ficar ligado
Add à força falha por privacidade → fallback = mandar invite link
Número Apollo verificado **não** vai no WAHA. Usa número de operação
Ban se disparar add em massa


**Veredito:** corre. Não clona motor pra dentro do WP. Sobe WAHA, plugin fino. Primeiro commit: settings + session/status. Se o ping voltar WORKING, o resto é botão.
O Mermaid no app iOS renderiza caixa branca. Segue o fluxograma como **imagem** (retrato, texto grande).
**1 INIT**
PC/VPS → docker run WAHA → session apollo → QR no celular → plugin ON → salva URL + key → cria ou grava @g.us
**2 ADD USER**
Pede no site → OTP já ok → fila no apollo-core → admin recusa (fim) ou aprova → POST /groups/{id}/participants → entra no grupo
**3 SEND LATER**
Texto + horário no painel → WP-Cron → opcional puxa eventos → POST /api/sendText no @g.us
PC/VPS fica ligado **só** pelo Docker do WAHA. WordPress só dispara HTTP.
Travado: esta é a melhor solução.

Não Cloud de 8 pessoas, não Chatwoot no dia 1, não Baileys no PHP, não número verde no WAHA.

Pane Apollo = Web. Fila = porta. DM = fluxo. Grupo = sala. HMAC = porta.
Fluxo único:

Bíblia (slugs, hooks, REST, meta, telas, join chain, HMAC):


Nomes que não mudam mais

















































CoisaValorPluginapollo-whatsappSession WAHAapolloEngineGOWSREST/wp-json/apollo/v1/wa/*WebhookPOST /wa/webhookHooksapollo/whatsapp/{action}Meta_apollo_wa_* + _apollo_whatsapp_phoneCapapollo_manage_whatsappFilaapollo_wa_queuePane msgsapollo_wa_messages
Proibido no código: apollo-waha, apollo-whats, apollo-channel como slug deste plugin.

7 telas (slug)

apollo-wa-settings
strip na 1 (WORKING / morto)
apollo-wa-queue — botão Processar entrada
apollo-wa-pane
apollo-wa-flows
shortcode [apollo_wa_join]
apollo-wa-my-requests
L. Dashboard 127.0.0.1:3000 — lab


O JSON é o SSOT na hora de construir. Qualquer nome novo primeiro entra no registry, depois no PHP. Cron fica not_in_mvp e reusa pane/send.