<?php
/**
 * Minhas Comunas / Meus Núcleos — screen styles (PHASES 009 + 010).
 *
 * Tokens only, per apollo-rio's core-tokens.md — no local :root. Prefix "gmi-"
 * (Groups MIne) so it can never collide with the public directory's own "cmn-"
 * classes, which both screens sit alongside in the same plugin.
 *
 * One stylesheet serves both screens: they are the same component with a
 * different scope (management-only vs membership) and a different empty state,
 * so duplicating the CSS would just be two files to keep in sync.
 *
 * @package Apollo\Groups
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<style id="apollo-gmi-screen">
.gmi-screen{max-width:1180px;margin:0 auto;padding:0 var(--s-4,24px) 60px;}
.gmi-hero{text-align:center;padding:40px 16px 28px;max-width:720px;margin:0 auto;}
.gmi-kicker{font-family:var(--ff-mono);font-size:11px;text-transform:uppercase;letter-spacing:.14em;color:var(--muted);margin:0 0 6px;}
.gmi-hero h1{font-size:var(--fs-h3);font-weight:700;letter-spacing:-.03em;color:var(--txt-heading);margin:0 0 18px;}
.gmi-hero-kpis{display:flex;justify-content:center;gap:28px;flex-wrap:wrap;margin-bottom:22px;}
.gmi-hero-kpi{display:flex;flex-direction:column;align-items:center;}
.gmi-hero-kpi strong{font-size:1.7rem;font-weight:700;color:var(--txt-heading);line-height:1;}
.gmi-hero-kpi span{font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;margin-top:4px;}
.gmi-hero-actions{display:flex;justify-content:center;gap:10px;flex-wrap:wrap;}
.gmi-notice{font-size:11.5px;color:var(--muted);margin:12px auto 0;max-width:560px;line-height:1.5;}
.gmi-notice-link{background:none;border:0;padding:0;color:var(--accent);font-weight:600;cursor:pointer;font-size:inherit;text-decoration:underline;}
.gmi-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px;}
.gmi-card{display:flex;flex-direction:column;background:var(--card);border:1px solid var(--border);border-radius:var(--r);overflow:hidden;text-decoration:none;transition:transform .35s var(--ease),border-color .35s var(--ease);}
.gmi-card:hover{transform:translateY(-4px);border-color:var(--border-hover);}
.gmi-cover{position:relative;aspect-ratio:16/9;background:var(--surface);overflow:hidden;}
.gmi-cover img{width:100%;height:100%;object-fit:cover;display:block;filter:grayscale(.35);transition:filter .5s var(--ease),transform .6s var(--ease-smooth);}
.gmi-card:hover .gmi-cover img{filter:grayscale(0);transform:scale(1.04);}
.gmi-cover-empty{position:absolute;inset:0;display:grid;place-items:center;color:var(--muted);font-size:26px;}
.gmi-badges{position:absolute;top:8px;left:8px;display:flex;gap:6px;flex-wrap:wrap;z-index:2;}
.gmi-badge{display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:var(--r-pill);font-family:var(--ff-mono);font-size:9px;font-weight:600;text-transform:uppercase;letter-spacing:.06em;background:rgba(var(--rgb-theme),.16);color:var(--white-1);backdrop-filter:blur(8px);-webkit-backdrop-filter:blur(8px);}
.gmi-badge.is-owner{background:var(--accent);color:#fff;}
.gmi-body{padding:14px 16px 16px;display:flex;flex-direction:column;gap:6px;flex:1;}
.gmi-body h2{font-family:var(--ff-heading);font-size:15px;font-weight:700;color:var(--txt-heading);margin:0;line-height:1.25;}
.gmi-desc{font-size:12px;color:var(--txt-color);line-height:1.5;margin:0;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}
.gmi-meta{display:flex;align-items:center;gap:12px;margin-top:auto;padding-top:8px;font-family:var(--ff-mono);font-size:10.5px;text-transform:uppercase;letter-spacing:.05em;color:var(--muted);}
.gmi-meta i{font-size:13px;margin-right:3px;vertical-align:-.1em;}
.gmi-empty{text-align:center;padding:48px 20px;background:var(--card);border:1px solid var(--border);border-radius:var(--r);}
.gmi-empty i{font-size:34px;color:var(--muted);display:block;margin-bottom:12px;}
.gmi-empty p{font-size:13px;color:var(--muted);margin:0 0 16px;line-height:1.6;}
</style>
