/**
 * Apollo Fav — JavaScript do sistema de favoritos
 *
 * Gerencia:
 * - Toggle de favorito via AJAX
 * - Animação do coração (pulse)
 * - Filtros por tipo no dashboard
 * - Paginação (Load More)
 * - Integração com REST API
 *
 * @package Apollo\Fav
 */

(function ($) {
    'use strict';

    // Guarda referência global
    const config = window.apolloFav || {};

    /**
     * Inicializa o sistema de favoritos quando o DOM estiver pronto.
     */
    $(document).ready(function () {
        initFavButtons();
        initDashboardFilters();
        initLoadMore();
    });

    // ═══════════════════════════════════════════════════
    // TOGGLE DE FAVORITO (Botão Apollo Heart)
    // ═══════════════════════════════════════════════════

    /**
     * Inicializa todos os botões de favorito na página.
     * Usa delegação de eventos para suportar conteúdo carregado via AJAX.
     */
    function initFavButtons() {
        $(document).on('click', '.apollo-fav-btn', function (e) {
            e.preventDefault();
            e.stopPropagation();

            const $btn = $(this);

            // Previne cliques duplos
            if ($btn.hasClass('apollo-fav--loading')) {
                return;
            }

            // Verifica se o usuário está logado
            if (!config.logged) {
                showToast(config.i18n?.login || 'Faça login para favoritar.', 'warning');
                // Redireciona para login após breve delay
                setTimeout(function () {
                    window.location.href = '/acesso';
                }, 1500);
                return;
            }

            const postId = $btn.data('post-id');
            const nonce = $btn.data('nonce') || config.nonce;

            if (!postId) {
                return;
            }

            // Marca como loading
            $btn.addClass('apollo-fav--loading');

            // Envia AJAX toggle
            $.ajax({
                url: config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'apollo_fav_toggle',
                    post_id: postId,
                    nonce: nonce,
                },
                success: function (response) {
                    if (response.success) {
                        const data = response.data;

                        // Atualiza estado visual do botão
                        if (data.action === 'added') {
                            $btn.addClass('apollo-fav--active');
                            showToast(config.i18n?.added || 'Adicionado aos favoritos!', 'success');
                        } else {
                            $btn.removeClass('apollo-fav--active');
                            showToast(config.i18n?.removed || 'Removido dos favoritos.', 'info');
                        }

                        // Atualiza contagem
                        (function (el, n) {
                            if (typeof ApolloTextFX !== 'undefined') { ApolloTextFX.counter(el, n); }
                            else { el.textContent = n; }
                        }($btn.find('.apollo-fav-count').get(0), data.count));

                        // Animação de pulse
                        $btn.addClass('apollo-fav--animating');
                        setTimeout(function () {
                            $btn.removeClass('apollo-fav--animating');
                        }, 400);

                        // Atualiza todos os botões do mesmo post na página
                        syncButtons(postId, data.action === 'added', data.count);
                    } else {
                        showToast(response.data?.message || config.i18n?.error || 'Erro.', 'error');
                    }
                },
                error: function (xhr) {
                    if (xhr.status === 401) {
                        showToast(config.i18n?.login || 'Faça login.', 'warning');
                    } else {
                        showToast(config.i18n?.error || 'Erro ao processar.', 'error');
                    }
                },
                complete: function () {
                    $btn.removeClass('apollo-fav--loading');
                },
            });
        });
    }

    /**
     * Sincroniza todos os botões do mesmo post_id na página.
     * Se houver múltiplas instâncias do botão (ex: grid + single), todas atualizam.
     *
     * @param {number} postId   ID do post.
     * @param {boolean} isActive Se está ativo.
     * @param {number} count    Nova contagem.
     */
    function syncButtons(postId, isActive, count) {
        $('.apollo-fav-btn[data-post-id="' + postId + '"]').each(function () {
            const $b = $(this);

            if (isActive) {
                $b.addClass('apollo-fav--active');
            } else {
                $b.removeClass('apollo-fav--active');
            }

            (function (el, n) {
                if (typeof ApolloTextFX !== 'undefined') { ApolloTextFX.counter(el, n); }
                else { el.textContent = n; }
            }($b.find('.apollo-fav-count').get(0), count));
        });
    }

    // ═══════════════════════════════════════════════════
    // FILTROS DO DASHBOARD
    // ═══════════════════════════════════════════════════

    /**
     * Inicializa os filtros por tipo de CPT no dashboard de favoritos.
     */
    function initDashboardFilters() {
        $(document).on('click', '.apollo-favs-filter', function (e) {
            e.preventDefault();

            const $filter = $(this);
            const type = $filter.data('type');
            const $dashboard = $filter.closest('.apollo-favs-dashboard');
            const $grid = $dashboard.find('.apollo-favs-grid');

            // Atualiza estado visual dos filtros
            $dashboard.find('.apollo-favs-filter').removeClass('active');
            $filter.addClass('active');

            // Carrega favoritos filtrados via AJAX
            $grid.addClass('apollo-fav--loading').css('opacity', '0.5');

            $.ajax({
                url: config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'apollo_fav_load_more',
                    nonce: config.nonce,
                    post_type: type || '',
                    offset: 0,
                    limit: parseInt($grid.data('limit')) || 12,
                },
                success: function (response) {
                    if (response.success) {
                        $grid.html(response.data.html);
                        $grid.data('offset', response.data.count);

                        // Mostra/esconde Load More
                        const $loadMore = $dashboard.find('.apollo-favs-load-more');
                        if (response.data.has_more) {
                            $loadMore.show();
                        } else {
                            $loadMore.hide();
                        }

                        // Se vazio, mostra mensagem
                        if (response.data.count === 0) {
                            $grid.html(
                                '<div class="apollo-favs-empty">' +
                                '<i class="ri-heart-add-line" style="font-size: 3rem; opacity: 0.3;"></i>' +
                                '<p>Nenhum favorito neste filtro.</p>' +
                                '</div>'
                            );
                        }
                    }
                },
                complete: function () {
                    $grid.removeClass('apollo-fav--loading').css('opacity', '1');
                },
            });
        });
    }

    // ═══════════════════════════════════════════════════
    // LOAD MORE (Paginação Infinita)
    // ═══════════════════════════════════════════════════

    /**
     * Inicializa o botão "Carregar mais" para paginação.
     */
    function initLoadMore() {
        $(document).on('click', '[data-action="apollo-fav-load-more"]', function (e) {
            e.preventDefault();

            const $btn = $(this);
            const $dashboard = $btn.closest('.apollo-favs-dashboard');
            const $grid = $dashboard.find('.apollo-favs-grid');
            const limit = parseInt($grid.data('limit')) || 12;
            const currentOffset = parseInt($grid.data('offset')) || 0;
            const newOffset = currentOffset + limit;

            // Tipo ativo nos filtros
            const activeType = $dashboard.find('.apollo-favs-filter.active').data('type') || '';

            $btn.prop('disabled', true).text('Carregando...');

            $.ajax({
                url: config.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'apollo_fav_load_more',
                    nonce: config.nonce,
                    post_type: activeType,
                    offset: newOffset,
                    limit: limit,
                },
                success: function (response) {
                    if (response.success) {
                        $grid.append(response.data.html);
                        $grid.data('offset', newOffset);

                        if (!response.data.has_more) {
                            $btn.closest('.apollo-favs-load-more').hide();
                        }
                    }
                },
                complete: function () {
                    $btn.prop('disabled', false).text('Carregar mais');
                },
            });
        });
    }

    // ═══════════════════════════════════════════════════
    // TOAST NOTIFICATION (Feedback visual)
    // ═══════════════════════════════════════════════════

    /**
     * Mostra uma notificação toast temporária.
     *
     * @param {string} message  Mensagem a exibir.
     * @param {string} type     Tipo: success, error, warning, info.
     */
    function showToast(message, type) {
        // Remove toasts existentes
        $('.apollo-fav-toast').remove();

        const icons = {
            success: '✓',
            error: '✕',
            warning: '⚠',
            info: 'ℹ',
        };

        const $toast = $(
            '<div class="apollo-fav-toast apollo-fav-toast--' + type + '">' +
            '<span class="apollo-fav-toast__icon">' + (icons[type] || 'ℹ') + '</span>' +
            '<span class="apollo-fav-toast__msg">' + message + '</span>' +
            '</div>'
        );

        // Estilos inline do toast (evita dependência de CSS extra)
        $toast.css({
            position: 'fixed',
            bottom: '24px',
            right: '24px',
            zIndex: 99999,
            display: 'flex',
            alignItems: 'center',
            gap: '8px',
            padding: '12px 20px',
            borderRadius: '8px',
            fontSize: '14px',
            fontWeight: '500',
            color: '#fff',
            background: type === 'success' ? '#2ed573' :
                        type === 'error' ? '#ff4757' :
                        type === 'warning' ? '#ffa502' : '#5352ed',
            boxShadow: '0 4px 12px rgba(var(--rgb-d),0.15)',
            transform: 'translateY(100px)',
            opacity: 0,
            transition: 'all 0.3s cubic-bezier(0.4, 0, 0.2, 1)',
        });

        $('body').append($toast);

        // Anima entrada
        requestAnimationFrame(function () {
            $toast.css({
                transform: 'translateY(0)',
                opacity: 1,
            });
        });

        // Auto-remove após 3s
        setTimeout(function () {
            $toast.css({
                transform: 'translateY(100px)',
                opacity: 0,
            });
            setTimeout(function () {
                $toast.remove();
            }, 300);
        }, 3000);
    }

})(jQuery);
