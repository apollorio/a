<?php

/**
 * Meus Anúncios — screen styles (PHASE 008).
 *
 * Tokens only, per apollo-rio's core-tokens.md — no local :root. Prefix
 * "mna-" (Meus Anúncios) to avoid colliding with the marketplace's own "mk-"
 * classes or the create-form's ".apollo-adverts-form-*" classes in the same
 * plugin.
 *
 * @package Apollo\Adverts
 */

if (! defined('ABSPATH')) {
    exit;
}
?>
<style id="apollo-mna-screen">
.mna-screen{max-width:1180px;margin:0 auto;padding:0 var(--s-4,24px) 60px;}
.mna-hero{text-align:center;padding:40px 16px 28px;max-width:720px;margin:0 auto;}
.mna-kicker{font-family:var(--ff-mono);font-size:11px;text-transform:uppercase;letter-spacing:.14em;color:var(--muted);margin:0 0 6px;}
.mna-hero h1{font-size:var(--fs-h3);font-weight:700;letter-spacing:-.03em;color:var(--txt-heading);margin:0 0 18px;}
.mna-hero-kpis{display:flex;justify-content:center;gap:28px;flex-wrap:wrap;margin-bottom:22px;}
.mna-hero-kpi{display:flex;flex-direction:column;align-items:center;}
.mna-hero-kpi strong{font-size:1.7rem;font-weight:700;color:var(--txt-heading);line-height:1;}
.mna-hero-kpi span{font-size:11px;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;margin-top:4px;}
.mna-hero-actions{display:flex;justify-content:center;gap:10px;}
.mna-notice{font-size:11.5px;color:var(--muted);margin:10px auto 0;max-width:560px;line-height:1.5;}
.mna-notice-link{background:none;border:0;padding:0;color:var(--accent);font-weight:600;cursor:pointer;font-size:inherit;text-decoration:underline;}
.mna-card{background:var(--card);border:1px solid var(--border);border-radius:var(--r);padding:20px;margin-bottom:16px;}
.mna-table{width:100%;border-collapse:collapse;font-size:12.5px;}
.mna-table th{text-align:left;font-size:10.5px;text-transform:uppercase;letter-spacing:.05em;color:var(--muted);padding:0 10px 10px;font-weight:600;}
.mna-table td{padding:12px 10px;border-top:1px solid var(--border);color:var(--txt-color);vertical-align:middle;}
.mna-table tr td:first-child{width:64px;}
.mna-thumb{width:56px;height:56px;border-radius:var(--r-sm);object-fit:cover;display:block;}
.mna-no-thumb{width:56px;height:56px;border-radius:var(--r-sm);background:var(--surface);display:grid;place-items:center;color:var(--muted);font-size:18px;}
.mna-title-cell a{color:var(--txt-heading);font-weight:600;text-decoration:none;}
.mna-title-cell a:hover{text-decoration:underline;}
.mna-row-date{display:block;font-size:11px;color:var(--muted);margin-top:2px;}
.mna-tag{display:inline-block;padding:3px 9px;border-radius:var(--r-pill);font-size:10.5px;font-weight:600;color:#fff;white-space:nowrap;}
.mna-actions{display:flex;gap:6px;flex-wrap:wrap;}
.mna-btn{display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:var(--r-sm);border:1px solid var(--border);background:var(--surface);color:var(--txt-color);text-decoration:none;font-size:14px;cursor:pointer;}
.mna-btn:hover{background:var(--card);color:var(--txt-heading);}
.mna-btn.mna-btn-danger:hover{color:#fff;background:var(--accent-sunset-red);border-color:var(--accent-sunset-red);}
.mna-empty{text-align:center;padding:40px 16px;color:var(--muted);font-size:13px;}
.mna-pagination{display:flex;justify-content:center;gap:6px;margin-top:16px;}
.mna-pagination a,.mna-pagination span{display:inline-flex;align-items:center;justify-content:center;min-width:32px;height:32px;padding:0 8px;border-radius:var(--r-sm);border:1px solid var(--border);color:var(--txt-color);text-decoration:none;font-size:12.5px;}
.mna-pagination .current{background:var(--accent);border-color:var(--accent);color:#fff;font-weight:600;}
</style>
