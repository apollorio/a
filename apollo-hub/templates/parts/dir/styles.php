<?php

/**
 * Hub.rio directory — screen styles (PHASE 005).
 *
 * Ported VERBATIM from _official_layout/js/view.hub.js's injected STYLE
 * string. Every custom property here was checked against
 * apollo-rio's core-tokens.md and resolves against core.js — no local
 * :root, no invented tokens. One rule added beyond the mockup: the inline
 * `data-apollo-suporte` trigger word inside the notice paragraph needs a
 * minimal button reset so it reads as text, not a stray control.
 *
 * @package Apollo\Hub
 */

if (! defined('ABSPATH')) {
    exit;
}
?>
<style id="apollo-hub-dir-screen">
.hub-screen .hub-hero{text-align:center;padding:44px 16px 28px;max-width:640px;margin:0 auto;}
.hub-screen .hub-hero > i{font-size:44px;color:var(--accent);display:block;margin-bottom:14px;line-height:1;vertical-align:0;}
.hub-screen .hub-hero h1{font-size:calc(var(--fsx,1)*28px);font-weight:700;letter-spacing:-.03em;color:var(--txt-heading);margin:0 0 6px;}
.hub-screen .hub-hero > p.hub-tag{font-family:var(--ff-mono);font-size:10.5px;text-transform:uppercase;letter-spacing:.16em;color:var(--muted);margin:0 0 18px;}
.hub-screen .hub-notice{margin:0 auto;max-width:520px;padding:16px 18px;border-radius:var(--r);background:var(--surface);border:1px solid rgba(var(--rgb-diff),.04);box-shadow:inset 0 1px 0 0 rgba(var(--rgb-theme),.9),inset 0 0 0 1px rgba(var(--rgb-theme),.4);text-align:left;}
.hub-screen .hub-notice p{font-size:calc(var(--fs-r,1)*13px);line-height:1.55;color:var(--txt-color);margin:0;}
.hub-screen .hub-notice-link{display:inline;padding:0;border:0;background:none;font:inherit;color:var(--accent);font-weight:600;cursor:pointer;text-decoration:underline;text-underline-offset:2px;}
.hub-screen .hub-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:14px;max-width:860px;margin:28px auto 0;padding:0 8px 40px;}
.hub-screen .hub-tile{display:flex;align-items:center;gap:14px;border:1px solid var(--border);border-radius:var(--r);background:var(--card);padding:18px;cursor:pointer;transition:all .4s var(--ease);text-decoration:none;color:inherit;}
.hub-screen .hub-tile:hover{border-color:var(--border-hover);transform:translateY(-3px);box-shadow:var(--shadow-1);}
.hub-screen .hub-tile > i{font-size:22px;color:var(--txt-heading);width:44px;height:44px;border-radius:var(--r-sm);background:var(--surface);display:inline-flex!important;align-items:center;justify-content:center;flex-shrink:0;line-height:1!important;vertical-align:0!important;text-align:center;transition:color .4s ease,background .4s ease;}
.hub-screen .hub-tile > i::before{line-height:1;vertical-align:0;display:block;}
.hub-screen .hub-tile:hover > i{color:var(--accent);}
.hub-screen .hub-tile b{display:block;font-size:14px;color:var(--txt-heading);}
.hub-screen .hub-tile span span{font-size:11.5px;color:var(--muted);}
</style>
