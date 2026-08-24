<?php

/**
 * Apollo Admin Panel — Footer JavaScript
 *
 * Tab switching, section navigation, dark mode, color sync, tooltips.
 *
 * @package Apollo\Admin
 */

if (! defined('ABSPATH')) {
    exit;
}
?>
<script>
    (function() {
            'use strict';

            const sections = ['admin', 'identity', 'email', 'events', 'social', 'system'];
            const sectionTabMap = {
                admin: 'tabs-admin',
                identity: 'tabs-identity',
                email: 'tabs-email',
                events: 'tabs-events',
                social: 'tabs-social',
                system: 'tabs-system'
            };

            // Mapeamento: seção antiga do sidebar → seção do #topbar-switch
            const topbarSectionMap = {
                admin: 'dashboard',
                identity: 'identity',
                email: 'email',
                events: 'events',
                social: 'social',
                system: 'system'
            };

            /* ─── TOPBAR-SWITCH: ativa seção modular ─── */
            function activateTopbarSection(topbarSection) {
                const tsw = document.getElementById('topbar-switch');
                if (!tsw) return;
                tsw.querySelectorAll('[data-role="topbar"]').forEach(function(tb) {
                    tb.classList.toggle('is-active', tb.dataset.section === topbarSection);
                });

                // Ativa o primeiro tab da seção visível e sua page
                const activeTb = tsw.querySelector('[data-role="topbar"].is-active');
                if (activeTb) {
                    const tabs = activeTb.querySelectorAll('.tab-btn');
                    tabs.forEach(t => t.classList.remove('active'));
                    document.querySelectorAll('.page').forEach(p => p.classList.remove('visible'));
                    if (tabs[0]) {
                        tabs[0].classList.add('active');
                        const page = document.getElementById('page-' + tabs[0].dataset.tab);
                        if (page) page.classList.add('visible');
                    }
                }
            }

            /* ═══ SECTION SWITCHER ═══ */
            window.switchSection = function(section) {
                // Update sidebar
                document.querySelectorAll('.nav-btn[data-section]').forEach(b => b.classList.remove('active'));
                const navBtn = document.querySelector('.nav-btn[data-section="' + section + '"]');
                if (navBtn) navBtn.classList.add('active');

                // Show correct tab bar (sistema legado)
                sections.forEach(s => {
                    const el = document.getElementById(sectionTabMap[s]);
                    if (el) el.style.display = s === section ? 'flex' : 'none';
                });

                // Hide all pages
                document.querySelectorAll('.page').forEach(p => p.classList.remove('visible'));

                // Activate first tab of that section (sistema legado)
                const tabBar = document.getElementById(sectionTabMap[section]);
                if (tabBar) {
                    const tabs = tabBar.querySelectorAll('.tab-btn');
                    tabs.forEach(t => t.classList.remove('active'));
                    if (tabs[0]) {
                        tabs[0].classList.add('active');
                        const pageId = 'page-' + tabs[0].dataset.tab;
                        const page = document.getElementById(pageId);
                        if (page) page.classList.add('visible');
                    }
                }

                // Ativa #topbar-switch (sistema modular)
                const mapped = topbarSectionMap[section] || section;
                activateTopbarSection(mapped);

                // Close mobile sidebar
                if (window.innerWidth <= 768) {
                    toggleMobileSidebar();
                }
            };

            /* ─── INICIALIZAÇÃO DO #topbar-switch ─── */
            document.addEventListener('DOMContentLoaded', function() {
                        var tsw = document.getElementById('topbar-switch');
                        if (!tsw) return;

                        // Se o #topbar-switch foi parar fora de .topbar, reposicioná-lo dentro
                        var topbar = document.querySelector('.topbar');
                        if (topbar && !topbar.contains(tsw)) {
                            var topbarRight = topbar.querySelector('.topbar-right');
                            if (topbarRight) {
                                topbar.insertBefore(tsw, topbarRight);
                            } else {
                                topbar.appendChild(tsw);
                            }
                        }

                        // Descobre seção ativa pelo nav-btn ou usa a primeira disponível
                        var activeNav = document.querySelector('.nav-btn[data-section].active');
                        var initSection = activeNav ?
                            (topbarSectionMap[activeNav.dataset.section] || activeNav.dataset.section) :
                            null;

                        if (initSection) {
                            activateTopbarSection(initSection);
                        } else {
                            var firstTb = tsw.querySelector('[data-role="topbar"]');
                            if (firstTb) firstTb.classList.add('is-active');
                        }

                        // Tab click dentro do #topbar-switch (com delegação para conteúdo dinâmico)
                        tsw.addEventListener('click', function(e) {
                            var btn = e.target.closest('.tab-btn');
                            if (!btn) return;
                            var tabBar = btn.closest('.topbar-tabs');
                            if (!tabBar) return;
                            tabBar.querySelectorAll('.tab-btn').forEach(function(t) {
                                t.classList.remove('active');
                            });
                            btn.classList.add('active');
                            document.querySelectorAll('.page').forEach(function(p) {
                                p.classList.remove('visible');
                            });
                            var page = document.getElementById('page-' + btn.dataset.tab);
                            if (page) page.classList.add('visible');

                            /* ═══ TAB CLICK ═══ */
                            document.querySelectorAll('.tab-btn').forEach(btn => {
                                btn.addEventListener('click', function() {
                                    const tabBar = this.closest('.topbar-tabs');
                                    tabBar.querySelectorAll('.tab-btn').forEach(t => t.classList.remove('active'));
                                    this.classList.add('active');

                                    document.querySelectorAll('.page').forEach(p => p.classList.remove('visible'));
                                    const page = document.getElementById('page-' + this.dataset.tab);
                                    if (page) page.classList.add('visible');
                                });
                            });

                            /* ═══ FEED TABS (sub-tabs within pages) ═══ */
                            document.querySelectorAll('.feed-tab').forEach(btn => {
                                btn.addEventListener('click', function() {
                                    const bar = this.closest('.feed-tabs');
                                    const page = this.closest('.page');

                                    bar.querySelectorAll('.feed-tab').forEach(t => t.classList.remove('active'));
                                    this.classList.add('active');

                                    page.querySelectorAll('.sub-content').forEach(sc => sc.classList.remove('visible'));
                                    const target = document.getElementById('sub-' + this.dataset.sub);
                                    if (target) target.classList.add('visible');
                                });
                            });

                            /* ═══ SUB-TAB HANDLER ═══ */
                            document.querySelectorAll('.sub-tab').forEach(btn => {
                                btn.addEventListener('click', function() {
                                    const bar = this.closest('.sub-tabs');
                                    const page = bar.closest('.page');

                                    bar.querySelectorAll('.sub-tab').forEach(t => t.classList.remove('active'));
                                    this.classList.add('active');

                                    page.querySelectorAll('.sub-content').forEach(sc => sc.classList.remove('visible'));
                                    const target = document.getElementById('sub-' + this.dataset.sub);
                                    if (target) target.classList.add('visible');
                                });
                            });

                            /* ═══ MOBILE SIDEBAR ═══ */
                            window.toggleMobileSidebar = function() {
                                const sidebar = document.getElementById('sidebar');
                                const overlay = document.getElementById('mobileOverlay');
                                if (sidebar) sidebar.classList.toggle('mobile-open');
                                if (overlay) overlay.classList.toggle('active');
                            };

                            /* ═══ ACCORDION ═══ */
                            document.querySelectorAll('.accordion-head').forEach(head => {
                                head.addEventListener('click', function() {
                                    this.parentElement.classList.toggle('open');
                                });
                            });

                            /* ═══ COLOR PICKER SYNC ═══ */
                            document.querySelectorAll('.color-pick').forEach(pick => {
                                const swatch = pick.querySelector('.color-swatch');
                                const hex = pick.querySelector('.color-hex');
                                if (swatch && hex) {
                                    swatch.addEventListener('input', () => hex.value = swatch.value);
                                    hex.addEventListener('input', () => {
                                        if (/^#[0-9a-f]{6}$/i.test(hex.value)) swatch.value = hex.value;
                                    });
                                }
                            });

                            /* ═══ DARK MODE ═══ */
                            window.toggleDarkMode = function() {
                                document.documentElement.classList.toggle('dark-mode');
                                document.body.classList.toggle('dark-mode');
                                localStorage.setItem('apollo_theme', document.body.classList.contains('dark-mode') ? 'dark' : 'light');
                            };

                            // Init theme from localStorage
                            if (localStorage.getItem('apollo_theme') === 'dark') {
                                document.documentElement.classList.add('dark-mode');
                                document.body.classList.add('dark-mode');
                            }

                            /* ═══ SAVE HANDLER ═══ */
                            const saveBtn = document.getElementById('apollo-save-btn');
                            if (saveBtn) {
                                saveBtn.addEventListener('click', function(e) {
                                    e.preventDefault();
                                    const form = document.getElementById('apollo-cpanel-form');
                                    if (!form) return;

                                    const formData = new FormData(form);
                                    formData.set('action', 'apollo_cpanel_save');

                                    saveBtn.disabled = true;
                                    saveBtn.textContent = 'Salvando…';

                                    fetch(ajaxurl, {
                                            method: 'POST',
                                            body: formData,
                                            credentials: 'same-origin'
                                        })
                                        .then(r => r.json())
                                        .then(data => {
                                            saveBtn.disabled = false;
                                            if (data.success) {
                                                saveBtn.style.background = 'var(--green)';
                                                saveBtn.textContent = 'Salvo ✓';
                                                setTimeout(() => {
                                                    saveBtn.style.background = '';
                                                    saveBtn.textContent = 'Salvar';
                                                }, 1500);
                                            } else {
                                                saveBtn.style.background = 'var(--red, #f44336)';
                                                saveBtn.textContent = 'Erro';
                                                setTimeout(() => {
                                                    saveBtn.style.background = '';
                                                    saveBtn.textContent = 'Salvar';
                                                }, 2000);
                                            }
                                        })
                                        .catch(err => {
                                            console.error(err);
                                            saveBtn.disabled = false;
                                            saveBtn.textContent = 'Salvar';
                                        });
                                });
                            }

                        })();
</script>