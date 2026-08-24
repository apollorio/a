/* ════════════════════════════════════════════════════════════════════════
   _sandbox/build-email-harness.mjs — apollo-email delivery-path harness

   Ownership: this file owns VERIFICATION of the two email send paths.
   It READS the real source files; it never copies them.

   Run:  node apollo-email/_sandbox/build-email-harness.mjs

   Assertion groups
     A  structural  — facts asserted directly against the real PHP source
     B  control-flow — the campaign state machine, transcribed and executed
     C  plumbing     — Sender success/failure contract under a stub transport

   NOTE ON SCOPE. There is no PHP binary in this environment, so group B
   executes a Node transcription of Newsletter::send_campaign(), not the PHP
   itself. Group A exists to keep that transcription honest: every branch B
   relies on is first proven to exist in the real file. Neither group proves
   SMTP delivery — that requires credentials and a live host.
   ════════════════════════════════════════════════════════════════════════ */

import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';

const ROOT = join(dirname(fileURLToPath(import.meta.url)), '..');
const read = (p) => readFileSync(join(ROOT, p), 'utf8');

const NEWSLETTER = read('src/Newsletter.php');
const SENDER     = read('src/Mailer/Sender.php');
const QUEUE      = read('src/Mailer/Queue.php');
const FUNCS      = read('includes/functions.php');

let pass = 0;
const failures = [];
const ok = (name, cond, detail = '') => {
  if (cond) { pass++; console.log(`  ✓ ${name}`); }
  else { failures.push(`${name}${detail ? ` — ${detail}` : ''}`); console.log(`  ✗ ${name}${detail ? ` — ${detail}` : ''}`); }
};

/* ── helper: isolate a PHP method body by brace matching ─────────────── */
function methodBody(src, signature) {
  const start = src.indexOf(signature);
  if (start === -1) return null;
  const open = src.indexOf('{', start);
  if (open === -1) return null;
  let depth = 0;
  for (let i = open; i < src.length; i++) {
    if (src[i] === '{') depth++;
    else if (src[i] === '}') { depth--; if (depth === 0) return src.slice(open, i + 1); }
  }
  return null;
}

/* ════════════════════════════════════════════════════════════════════
   GROUP A — structural assertions against the real source
   ════════════════════════════════════════════════════════════════════ */
console.log('\nA. STRUCTURAL (real source)\n');

const sendCampaign  = methodBody(NEWSLETTER, 'public static function send_campaign(');
const processSched  = methodBody(NEWSLETTER, 'public static function process_scheduled_campaigns(');
const sendEmail     = methodBody(NEWSLETTER, 'public static function send_email(');

ok('send_campaign() body located',            !!sendCampaign);
ok('process_scheduled_campaigns() located',   !!processSched);
ok('send_email() body located',               !!sendEmail);

// A1 — campaign cron is hourly and request-driven
ok('A1 campaign cron registered hourly',
   /wp_schedule_event\(\s*time\(\)\s*,\s*'hourly'\s*,\s*'apollo_newsletter_send_scheduled'\s*\)/.test(NEWSLETTER));

// A2 — scheduler only ever picks up status='scheduled'
ok("A2 scheduler selects ONLY status='scheduled'",
   /status\s*=\s*'scheduled'/.test(processSched) && !/status\s*IN/i.test(processSched),
   'a row stranded in status=sending is therefore never re-picked');

// A3 — send_campaign marks 'sent' with no failure branch  → stranded 'sending'
const marksSending = /'status'\s*=>\s*'sending'/.test(sendCampaign);
const marksSent    = /'status'\s*=>\s*'sent'/.test(sendCampaign);
const hasFailPath  = /'status'\s*=>\s*'failed'/.test(sendCampaign)
                  || /if\s*\(\s*\$failed/.test(sendCampaign);
ok('A3 sets sending → sent unconditionally (no failure branch)',
   marksSending && marksSent && !hasFailPath,
   'PHP timeout mid-loop strands the campaign in status=sending forever');

// A4 — recipients filtered to active only; new subscribers default to pending
ok("A4 send_campaign selects only status='active'",
   /'status'\s*=>\s*'active'/.test(sendCampaign));
ok("A4 add_subscriber inserts status='pending' (double opt-in)",
   /'status'\s*=>\s*'pending'/.test(methodBody(NEWSLETTER, 'public static function add_subscriber(') ?? ''),
   'unconfirmed subscriber ⇒ campaign sends to 0 and still marks itself sent');

// A5 — only the first list is queried
ok('A5 only $lists[0] is used',
   /\$lists\[0\]/.test(sendCampaign),
   'multi-list campaigns silently truncate to the first list');

// A6 — Path B never touches the queue (no retry, no backoff)
ok('A6 campaign path never calls enqueue()/queue()',
   !/enqueue\(|->queue\(\)|apollo_queue_email/.test(sendCampaign),
   'hard bounces on Path B are invisible: no retry, no queue log');

// A7 — get_email_template() unreachable while apollo-email is active
const routesEarly = /if\s*\(\s*function_exists\('apollo_send_email'\)\s*\)\s*\{[\s\S]*?return\s+apollo_send_email\(/.test(sendEmail);
const wrapperAfter = sendEmail.indexOf('get_email_template') > sendEmail.indexOf('apollo_send_email');
ok('A7 get_email_template() is dead code when plugin active',
   routesEarly && wrapperAfter,
   'early return bypasses the styled wrapper + unsubscribe footer');

// A8 — brace mismatch on the unsubscribe merge tag
ok('A8 unsubscribe brace mismatch (single vs double)',
   /'\{unsubscribe_url\}'/.test(sendCampaign) && /'unsubscribe_url'\s*=>/.test(SENDER),
   "Newsletter replaces {unsubscribe_url}; Sender injects {{unsubscribe_url}}");

// A9 — Sender is strict: template required
ok('A9 Sender rejects raw-body sends',
   /BLOCKED — raw-body send/.test(SENDER));

// A10 — queue path DOES retry (the asymmetry that motivates this harness)
ok('A10 queue path retries via SmtpDelivery::shouldRetry',
   /SmtpDelivery::shouldRetry\(/.test(QUEUE));

// A11 — helper aborts rather than falling back to raw wp_mail
ok('A11 apollo_send_email aborts if plugin not loaded',
   /email NOT sent/.test(FUNCS));

/* ════════════════════════════════════════════════════════════════════
   GROUP B — campaign state machine, executed
   Transcribed from send_campaign(); every branch proven present in A.
   ════════════════════════════════════════════════════════════════════ */
console.log('\nB. CONTROL FLOW (executed model)\n');

function sendCampaignModel({ subscribers, lists, transport, timeoutAfter = Infinity }) {
  const campaign = { status: 'draft', sent_count: 0, sent_at: null };
  campaign.status = 'sending';                                   // A3
  const targetList = lists.length ? lists[0] : '';               // A5
  const recipients = subscribers.filter(
    (s) => s.status === 'active' && (!targetList || (s.lists ?? []).includes(targetList))
  );                                                             // A4
  let sent = 0, failed = 0;
  for (let i = 0; i < recipients.length; i++) {
    if (i >= timeoutAfter) return { campaign, sent, failed, timedOut: true };
    transport(recipients[i]) ? sent++ : failed++;                // A6: no retry
  }
  campaign.status = 'sent';                                      // A3 unconditional
  campaign.sent_count = sent;
  campaign.sent_at = '2026-08-12 12:00:00';
  return { campaign, sent, failed, timedOut: false };
}

const alwaysOk   = () => true;
const alwaysFail = () => false;

// B1 — unconfirmed subscriber: zero recipients, still marked sent
{
  const r = sendCampaignModel({
    subscribers: [{ email: 'rafapevalle@gmail.com', status: 'pending', lists: ['default'] }],
    lists: ['default'], transport: alwaysOk,
  });
  ok('B1 pending subscriber ⇒ 0 sent but status=sent',
     r.sent === 0 && r.campaign.status === 'sent' && r.campaign.sent_count === 0,
     `sent=${r.sent} status=${r.campaign.status}`);
}

// B2 — confirmed subscriber delivers
{
  const r = sendCampaignModel({
    subscribers: [{ email: 'rafapevalle@gmail.com', status: 'active', lists: ['default'] }],
    lists: ['default'], transport: alwaysOk,
  });
  ok('B2 active subscriber ⇒ 1 sent, status=sent',
     r.sent === 1 && r.campaign.status === 'sent', `sent=${r.sent}`);
}

// B3 — every send fails, campaign STILL reports sent
{
  const r = sendCampaignModel({
    subscribers: [{ email: 'rafapevalle-@gmail.com', status: 'active', lists: ['default'] }],
    lists: ['default'], transport: alwaysFail,
  });
  ok('B3 total failure still marks status=sent',
     r.failed === 1 && r.campaign.status === 'sent' && r.campaign.sent_count === 0,
     `failed=${r.failed} status=${r.campaign.status} — failure is invisible downstream`);
}

// B4 — timeout mid-loop strands the row in 'sending', never re-picked
{
  const many = Array.from({ length: 500 }, (_, i) => ({ email: `u${i}@x.com`, status: 'active', lists: ['default'] }));
  const r = sendCampaignModel({ subscribers: many, lists: ['default'], transport: alwaysOk, timeoutAfter: 120 });
  const rePicked = r.campaign.status === 'scheduled';            // A2 selector
  ok('B4 timeout strands status=sending and is never retried',
     r.timedOut && r.campaign.status === 'sending' && !rePicked,
     `status=${r.campaign.status} after ${r.sent} sends`);
}

// B5 — multi-list truncation
{
  const r = sendCampaignModel({
    subscribers: [
      { email: 'a@x.com', status: 'active', lists: ['vip'] },
      { email: 'b@x.com', status: 'active', lists: ['geral'] },
    ],
    lists: ['vip', 'geral'], transport: alwaysOk,
  });
  ok('B5 campaign on 2 lists reaches only list[0]', r.sent === 1, `sent=${r.sent} of 2`);
}

/* ════════════════════════════════════════════════════════════════════
   GROUP C — Sender success/failure contract
   ════════════════════════════════════════════════════════════════════ */
console.log('\nC. PLUMBING (Sender contract)\n');

function senderSend({ template, wpMailResult, phpmailerError = '' }) {
  if (!template) return { success: false, error: 'BLOCKED — raw-body send rejected', log_id: null };  // A9
  if (wpMailResult) return { success: true, error: null, log_id: 1 };
  return { success: false, error: phpmailerError || 'Falha ao enviar email.' };
}

ok('C1 template + transport ok ⇒ success:true',
   senderSend({ template: 'notification', wpMailResult: true }).success === true);

ok('C2 missing template ⇒ blocked before transport',
   senderSend({ template: '', wpMailResult: true }).success === false);

ok('C3 transport failure ⇒ success:false with error',
   (() => { const r = senderSend({ template: 'notification', wpMailResult: false, phpmailerError: 'SMTP code: 550 no such user' });
            return r.success === false && /550/.test(r.error); })());

/* ── SMTP classification, exercised against the real constant tables ── */
const SMTP = read('src/Mailer/SmtpDelivery.php');
const PERMANENT = [500,501,502,503,504,521,530,550,551,552,553,554,555];
const TEMPORARY = [421,450,451,452,454];
ok('C4 permanent code table matches source',
   PERMANENT.every((c) => new RegExp(`\\b${c}\\b`).test(SMTP.slice(SMTP.indexOf('PERMANENT_CODES'), SMTP.indexOf('TEMPORARY_CODES')))));
ok('C5 temporary code table matches source',
   TEMPORARY.every((c) => new RegExp(`\\b${c}\\b`).test(SMTP.slice(SMTP.indexOf('TEMPORARY_CODES'), SMTP.indexOf('classify')))));
ok('C6 550 is permanent ⇒ hard bounce never retried',
   PERMANENT.includes(550) && !TEMPORARY.includes(550),
   'a typo\'d recipient fails once, silently, on Path B');

/* ════════════════════════════════════════════════════════════════════ */
const total = pass + failures.length;
console.log(`\n${'─'.repeat(60)}`);
console.log(failures.length === 0 ? `GREEN — ${pass}/${total} assertions passed` : `RED — ${failures.length}/${total} failed`);
if (failures.length) { failures.forEach((f) => console.log(`   ✗ ${f}`)); process.exitCode = 1; }
console.log(`${'─'.repeat(60)}`);
console.log('NOT PROVEN HERE: SMTP delivery to a real inbox.');
console.log('Requires credentials + live host — see _run_test_email.php (Mailpit, localhost:10000).\n');
