<?php

/**
 * Feed — screen styles (PHASE 001).
 *
 * Lifted verbatim out of the previous monolithic page-feed.php so the visual
 * result is unchanged while the screen itself becomes modular. Kept as a
 * template-part (not a stylesheet) because it is screen-scoped and must load
 * inside the Apollo+ document head alongside the shell's own CSS.
 *
 * @package Apollo\Templates
 * @since   1.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<style id="apollo-feed-screen">

        /* ── Design tokens ── */
        /* ── SCREEN-LOCAL LAYOUT VARS (phase audit fix) ──────────────────────────────
   These were declared on :root, which forks the token system core.js owns.
   Worse, one of them — --r-pill: 100px — SHADOWED core.js's own --r-pill
   (999px) for the entire /feed document, so every pill on the page rendered
   with the wrong radius.

   They are not design tokens: they are this screen's layout constants
   (column widths, gutters). Scoped to the screen root so they behave like
   local variables and cannot leak. --r-pill is deleted outright: core.js owns
   it. --ease-out now derives from core.js's --ease rather than restating a
   curve. */
.feed-shell, [data-screen="feed"] {
            --feed-max: 680px;
            --sidebar-w: 340px;
            --feed-gap: 24px;
            --radius-card: 14px;
            --ease-out: var(--ease);
            --ease-spring: var(--ease-snappy);
        }

        /* ── Layout ── */
        .feed-shell {
            display: grid;
            grid-template-columns: 1fr minmax(0, var(--feed-max)) var(--sidebar-w);
            gap: var(--feed-gap);
            max-width: 1120px;
            margin: 0 auto;
            padding: 80px 20px 40px;
            min-height: 100vh;
            min-height: 100dvh;
        }

        .feed-spacer {
            display: block
        }

        .feed-main {
            display: flex;
            flex-direction: column;
            gap: 0;
        }

        .feed-sidebar {
            position: sticky;
            top: 80px;
            height: fit-content;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        @media (max-width: 1024px) {
            .feed-shell {
                grid-template-columns: 1fr;
                max-width: var(--feed-max);
            }

            .feed-spacer,
            .feed-sidebar {
                display: none
            }
        }

        /* ── Tab Bar ── */
        .feed-tabs {
            display: flex;
            gap: 4px;
            padding: 6px;
            background: var(--surface, #f5f5f5);
            border-radius: var(--r-pill);
            margin-bottom: 20px;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .feed-tabs::-webkit-scrollbar {
            display: none
        }

        .feed-tab {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 10px 16px;
            border: none;
            background: transparent;
            border-radius: var(--r-pill);
            font: 500 13px/1 var(--ff-main, system-ui);
            color: var(--txt-muted, #666);
            cursor: pointer;
            transition: all .25s var(--ease-out);
            white-space: nowrap;
        }

        .feed-tab i {
            font-size: 16px
        }

        .feed-tab:hover {
            color: var(----txt-color-hover, #111)
        }

        .feed-tab--active {
            background: var(--bg, #fff);
            color: var(----txt-color-hover, #111);
            box-shadow: 0 1px 4px rgba(var(--rgb-d), .08);
            font-weight: 600;
        }

        /* ── Tab Panels ── */
        .feed-panel {
            display: none;
            flex-direction: column;
            gap: 16px;
        }

        .feed-panel--active {
            display: flex
        }

        /* ── Compose Box ── */
        .feed-compose {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 16px;
            background: var(--surface, #f8f8f8);
            border: 1px solid var(--border, #e5e5e5);
            border-radius: var(--radius-card);
        }

        .feed-compose__avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            flex-shrink: 0;
        }

        .feed-compose__input {
            flex: 1;
            background: var(--bg, #fff);
            border: 1px solid var(--border, #e5e5e5);
            border-radius: var(--r-pill);
            padding: 10px 16px;
            font: 400 14px/1.4 var(--ff-main, system-ui);
            color: var(----txt-color-hover, #111);
            outline: none;
            cursor: pointer;
            transition: border-color .2s;
        }

        .feed-compose__input:hover,
        .feed-compose__input:focus {
            border-color: var(--primary, FF9820);
        }

        /* ── Post Card ── */
        .post-card {
            background: var(--surface, #f8f8f8);
            border: 1px solid var(--border, #e5e5e5);
            border-radius: var(--radius-card);
            overflow: hidden;
            transition: transform .2s var(--ease-out), box-shadow .2s;
        }

        .post-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(var(--rgb-d), .06);
        }

        .post-card__head {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 14px 16px 0;
        }

        .post-card__avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            object-fit: cover;
        }

        .post-card__meta {
            flex: 1;
            min-width: 0;
        }

        .post-card__name {
            font: 600 14px/1.2 var(--ff-main, system-ui);
            color: var(----txt-color-hover, #111);
            margin: 0;
        }

        .post-card__time {
            font: 400 12px/1 var(--ff-main, system-ui);
            color: var(--txt-muted, #888);
        }

        .post-card__more {
            background: none;
            border: none;
            font-size: 18px;
            color: var(--txt-muted, #888);
            cursor: pointer;
            padding: 4px;
        }

        .post-card__body {
            padding: 12px 16px;
        }

        .post-card__text {
            font: 400 14px/1.6 var(--ff-main, system-ui);
            color: var(----txt-color-hover, #111);
            margin: 0;
        }

        .post-card__text a {
            color: var(--primary, FF9820);
            text-decoration: none;
        }

        /* ── Embeds (SoundCloud, Spotify, YouTube) ── */
        .post-card__embed {
            padding: 0 16px 4px;
        }

        .post-card__embed iframe {
            width: 100%;
            border: none;
            border-radius: 12px;
        }

        .post-card__embed--sc iframe {
            height: 166px
        }

        .post-card__embed--spotify iframe {
            height: 152px
        }

        .post-card__embed--yt iframe {
            aspect-ratio: 16/9;
            height: auto
        }

        /* ── Post Image ── */
        .post-card__image {
            padding: 0 16px;
        }

        .post-card__image img {
            width: 100%;
            border-radius: 12px;
            display: block;
        }

        /* ── Event Share Card ── */
        .post-card__event {
            margin: 0 16px;
            background: var(--bg, #fff);
            border: 1px solid var(--border, #e5e5e5);
            border-radius: 12px;
            overflow: hidden;
        }

        .post-card__event-img {
            width: 100%;
            aspect-ratio: 16/9;
            object-fit: cover;
        }

        .post-card__event-info {
            padding: 12px 14px;
        }

        .post-card__event-title {
            font: 600 14px/1.3 var(--ff-main, system-ui);
            color: var(----txt-color-hover, #111);
            margin: 0 0 4px;
        }

        .post-card__event-meta {
            font: 400 12px/1.4 var(--ff-main, system-ui);
            color: var(--txt-muted, #888);
        }

        /* ── Post Actions ── */
        .post-card__actions {
            display: flex;
            gap: 0;
            padding: 4px 8px;
            border-top: 1px solid var(--border, #e5e5e5);
        }

        .post-card__action {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 10px 0;
            background: none;
            border: none;
            font: 400 13px/1 var(--ff-main, system-ui);
            color: var(--txt-muted, #888);
            cursor: pointer;
            border-radius: 8px;
            transition: background .15s, color .15s;
        }

        .post-card__action i {
            font-size: 18px
        }

        .post-card__action:hover {
            background: rgba(var(--primary-rgb), .08);
            color: var(--primary, FF9820);
        }

        .post-card__action--active {
            color: var(--primary, FF9820);
        }

        /* ── Sidebar Widgets ── */
        .sw-box {
            background: var(--surface, #f8f8f8);
            border: 1px solid var(--border, #e5e5e5);
            border-radius: var(--radius-card);
            overflow: hidden;
        }

        .sw-box__head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 16px 10px;
        }

        .sw-box__title {
            font: 700 13px/1 var(--ff-mono, monospace);
            color: var(----txt-color-hover, #111);
            text-transform: uppercase;
            letter-spacing: .04em;
        }

        .sw-box__link {
            font: 500 11px/1 var(--ff-main, system-ui);
            color: var(--primary, FF9820);
            text-decoration: none;
        }

        .sw-box__link:hover {
            text-decoration: underline
        }

        .sw-box__list {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .sw-box__item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 16px;
            border-top: 1px solid var(--border, #e5e5e5);
            transition: background .15s;
            cursor: pointer;
        }

        .sw-box__item:hover {
            background: rgba(var(--rgb-d), .02)
        }

        .sw-box__item-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: rgba(var(--primary-rgb), .1);
            display: grid;
            place-items: center;
            font-size: 15px;
            color: var(--primary, FF9820);
            flex-shrink: 0;
        }

        .sw-box__item-text {
            flex: 1;
            min-width: 0;
        }

        .sw-box__item-label {
            font: 500 13px/1.3 var(--ff-main, system-ui);
            color: var(----txt-color-hover, #111);
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .sw-box__item-sub {
            font: 400 11px/1 var(--ff-main, system-ui);
            color: var(--txt-muted, #888);
        }

        /* ── Stats Grid ── */
        .sw-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1px;
            background: var(--border, #e5e5e5);
        }

        .sw-stat {
            background: var(--surface, #f8f8f8);
            padding: 14px 16px;
            text-align: center;
        }

        .sw-stat__num {
            font: 800 20px/1 var(--ff-mono, monospace);
            color: var(----txt-color-hover, #111);
            display: block;
        }

        .sw-stat__label {
            font: 400 10px/1 var(--ff-mono, monospace);
            color: var(--txt-muted, #888);
            text-transform: uppercase;
            letter-spacing: .06em;
            margin-top: 4px;
            display: block;
        }

        /* ── Safety Modal ── */
        .safety-overlay {
            position: fixed;
            inset: 0;
            background: rgba(var(--rgb-d), .6);
            z-index: 9999;
            display: none;
            place-items: center;
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
        }

        .safety-overlay--open {
            display: grid
        }

        .safety-modal {
            background: var(--bg, #fff);
            border-radius: 20px;
            max-width: 420px;
            width: 90%;
            padding: 32px;
            text-align: center;
        }

        .safety-modal__icon {
            font-size: 40px;
            color: var(--primary, FF9820);
            margin-bottom: 12px;
        }

        .safety-modal__title {
            font: 700 18px/1.3 var(--ff-main, system-ui);
            color: var(----txt-color-hover, #111);
            margin: 0 0 8px;
        }

        .safety-modal__text {
            font: 400 14px/1.6 var(--ff-main, system-ui);
            color: var(--txt-muted, #666);
            margin: 0 0 20px;
        }

        .safety-modal__btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 12px 28px;
            background: var(--primary, FF9820);
            color: #fff;
            border: none;
            border-radius: var(--r-pill);
            font: 600 14px/1 var(--ff-main, system-ui);
            cursor: pointer;
            transition: transform .2s, opacity .2s;
        }

        .safety-modal__btn:hover {
            transform: scale(1.04)
        }

        /* ── Empty state ── */
        .feed-empty {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
            padding: 60px 20px;
            text-align: center;
        }

        .feed-empty__icon {
            font-size: 48px;
            color: var(--txt-muted, #ccc);
        }

        .feed-empty__title {
            font: 600 16px/1.3 var(--ff-main, system-ui);
            color: var(----txt-color-hover, #111);
            margin: 0;
        }

        .feed-empty__desc {
            font: 400 14px/1.5 var(--ff-main, system-ui);
            color: var(--txt-muted, #888);
            margin: 0;
            max-width: 320px;
        }

        /* ── Trending Tags ── */
        .sw-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            padding: 0 16px 14px;
        }

        .sw-tag {
            font: 500 11px/1 var(--ff-mono, monospace);
            color: var(--txt-muted, #888);
            background: var(--bg, #fff);
            border: 1px solid var(--border, #e5e5e5);
            border-radius: var(--r-pill);
            padding: 6px 12px;
            text-decoration: none;
            transition: all .15s;
        }

        .sw-tag:hover {
            border-color: var(--primary, FF9820);
            color: var(--primary, FF9820);
        }
    
</style>
