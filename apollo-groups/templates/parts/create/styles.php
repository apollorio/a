<?php
/**
 * Criar Comuna — screen styles (Apollo+).
 *
 * Tokens only — no :root. Matches /comunas luxury restraint.
 *
 * @package Apollo\Groups
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<style id="apollo-create-comuna-screen">
[data-screen="criar-comuna"] .crc-screen {
  color: var(--txt-color);
  font-family: var(--ff-main);
  padding: 8px 0 72px;
  box-sizing: border-box;
  max-width: 640px;
}

.crc-back {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  margin-bottom: 28px;
  font-family: var(--ff-mono);
  font-size: calc(var(--fs-r, 1) * 10px);
  text-transform: uppercase;
  letter-spacing: .08em;
  color: var(--muted);
  text-decoration: none;
  transition: color .2s var(--ease, ease);
}
.crc-back:hover { color: var(--primary); }

.crc-hero {
  position: relative;
  margin-bottom: 36px;
  overflow: hidden;
}
.crc-hero-bg {
  position: absolute;
  top: -8px;
  left: -6px;
  font-family: var(--ff-heading);
  font-size: min(18vw, 120px);
  font-weight: 800;
  line-height: .85;
  color: transparent;
  -webkit-text-stroke: 1px color-mix(in srgb, var(--txt-heading) 6%, transparent);
  white-space: nowrap;
  pointer-events: none;
  user-select: none;
  z-index: 0;
}
.crc-hero-inner { position: relative; z-index: 1; }
.crc-eyebrow {
  font-family: var(--ff-mono);
  font-size: calc(var(--fs-r, 1) * 10px);
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .16em;
  color: var(--primary);
  margin: 0 0 12px;
}
.crc-hero .display-text {
  font-size: clamp(2rem, 5vw, 3rem);
  line-height: .95;
  letter-spacing: -.04em;
  margin: 0 0 12px;
  color: var(--txt-heading);
}
.crc-hero .display-text .thin {
  font-weight: 300;
  color: var(--muted);
}
.crc-hero-desc {
  font-size: var(--fs-p4, calc(var(--fs-u, 1) * 14px));
  color: var(--muted);
  max-width: 42ch;
  line-height: 1.55;
  margin: 0;
}

.crc-form {
  display: flex;
  flex-direction: column;
  gap: 28px;
}

.crc-field {
  position: relative;
}
.crc-input,
.crc-textarea,
.crc-select {
  width: 100%;
  background: transparent;
  border: none;
  border-bottom: 1px solid var(--border);
  padding: 14px 0 12px;
  font-family: var(--ff-main);
  font-size: 16px;
  color: var(--txt-heading);
  border-radius: 0;
  outline: none;
  transition: border-color .3s var(--ease, ease);
  box-sizing: border-box;
}
.crc-textarea {
  resize: vertical;
  min-height: 72px;
  line-height: 1.5;
}
.crc-input:focus,
.crc-textarea:focus,
.crc-select:focus {
  border-bottom-color: var(--primary);
}
.crc-label {
  position: absolute;
  top: 14px;
  left: 0;
  font-family: var(--ff-mono);
  font-size: 11px;
  color: var(--muted);
  pointer-events: none;
  text-transform: uppercase;
  letter-spacing: .06em;
  transition: all .3s var(--ease, ease);
}
.crc-input:focus ~ .crc-label,
.crc-input:not(:placeholder-shown) ~ .crc-label,
.crc-textarea:focus ~ .crc-label,
.crc-textarea:not(:placeholder-shown) ~ .crc-label,
.crc-select:valid ~ .crc-label,
.crc-select:focus ~ .crc-label {
  top: -8px;
  font-size: 10px;
  color: var(--primary);
}

.crc-select {
  appearance: none;
  -webkit-appearance: none;
  cursor: pointer;
  padding-right: 28px;
}
.crc-select:invalid { color: transparent; }
.crc-select-wrap { position: relative; }
.crc-select-arrow {
  position: absolute;
  right: 0;
  bottom: 14px;
  pointer-events: none;
  color: var(--muted);
  font-size: 16px;
}

.crc-hint {
  margin-top: 8px;
  font-family: var(--ff-mono);
  font-size: calc(var(--fs-r, 1) * 10px);
  color: var(--muted);
  line-height: 1.4;
}

/* ── Administradores (multi-select) ─────────────────────────── */
.crc-admins-label {
  display: flex;
  align-items: center;
  gap: 8px;
  font-family: var(--ff-mono);
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: .06em;
  color: var(--muted);
  margin: 0 0 4px;
}
.crc-admins-label i { font-size: 13px; }

.crc-admins-search-wrap { position: relative; }
.crc-admins-search-wrap .crc-input { padding-left: 24px; }
.crc-admins-search-icon {
  position: absolute;
  left: 0;
  bottom: 14px;
  font-size: 14px;
  color: var(--muted);
  pointer-events: none;
}

.crc-chips {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 14px;
}
.crc-chips:empty { display: none; }
.crc-chip {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 5px 6px 5px 5px;
  border: 1px solid var(--border);
  border-radius: var(--r-pill, 999px);
  background: transparent;
  font-size: 12px;
  color: var(--txt-heading);
  max-width: 100%;
}
.crc-chip img {
  width: 22px;
  height: 22px;
  border-radius: 50%;
  object-fit: cover;
  flex: none;
}
.crc-chip span {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.crc-chip-remove {
  border: none;
  background: transparent;
  color: var(--muted);
  cursor: pointer;
  font-size: 14px;
  line-height: 1;
  padding: 0 4px;
  transition: color .2s var(--ease, ease);
}
.crc-chip-remove:hover { color: #ef4444; }
.crc-chip.is-locked { opacity: .7; padding-right: 12px; }

.crc-admins-list {
  margin-top: 14px;
  border: 1px solid var(--border);
  border-radius: var(--r-md, 10px);
  max-height: 250px;
  overflow-y: auto;
}
.crc-admins-list:empty { display: none; }
.crc-admin-row {
  display: flex;
  align-items: center;
  gap: 10px;
  width: 100%;
  padding: 10px 12px;
  background: transparent;
  border: none;
  border-bottom: 1px solid color-mix(in srgb, var(--border) 60%, transparent);
  cursor: pointer;
  text-align: left;
  font-family: var(--ff-main);
  font-size: 13px;
  color: var(--txt-heading);
  transition: background .2s var(--ease, ease);
}
.crc-admin-row:last-child { border-bottom: none; }
.crc-admin-row:hover { background: color-mix(in srgb, var(--primary) 6%, transparent); }
.crc-admin-row img {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  object-fit: cover;
  flex: none;
}
.crc-admin-row .crc-admin-name {
  flex: 1;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.crc-admin-row .crc-admin-user {
  font-family: var(--ff-mono);
  font-size: 10px;
  color: var(--muted);
}
.crc-admin-row[aria-selected="true"] .crc-admin-check { color: var(--primary); }
.crc-admin-check { font-size: 15px; color: var(--border); }

.crc-admins-status {
  display: none;
  margin-top: 12px;
  font-family: var(--ff-mono);
  font-size: 10px;
  text-transform: uppercase;
  letter-spacing: .06em;
  color: var(--muted);
}
.crc-admins-status.is-visible { display: block; }

.crc-error {
  display: none;
  font-family: var(--ff-mono);
  font-size: 11px;
  color: #ef4444;
  padding: 4px 0;
}
.crc-error.is-visible { display: block; }

.crc-actions {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 16px;
  margin-top: 8px;
  padding-top: 8px;
}

.crc-submit {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  border: 1px solid var(--border);
  background: var(--txt-heading);
  color: var(--card, #fff);
  padding: 16px 28px;
  font-family: var(--ff-mono);
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .1em;
  cursor: pointer;
  border-radius: var(--r-pill, 999px);
  transition: transform .25s var(--ease-snappy, ease), background .25s var(--ease, ease), border-color .25s;
}
.crc-submit:hover:not(:disabled) {
  transform: translateY(-1px);
  background: var(--primary);
  border-color: var(--primary);
  color: #fff;
}
.crc-submit:disabled {
  opacity: .55;
  cursor: not-allowed;
}

.crc-success {
  display: none;
  text-align: center;
  padding: 48px 16px;
}
.crc-success.is-visible { display: block; }
.crc-success-icon {
  font-size: 3rem;
  color: var(--primary);
  margin-bottom: 16px;
}
.crc-success h3 {
  font-size: 1.5rem;
  font-weight: 400;
  color: var(--txt-heading);
  margin: 0 0 8px;
}
.crc-success p {
  font-family: var(--ff-mono);
  font-size: 12px;
  color: var(--muted);
  margin: 0;
}

.crc-form.is-hidden { display: none; }

@media (max-width: 640px) {
  [data-screen="criar-comuna"] .crc-screen { padding-bottom: 48px; }
  .crc-submit { width: 100%; justify-content: center; }
}
</style>
