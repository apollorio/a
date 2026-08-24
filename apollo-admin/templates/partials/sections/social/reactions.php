<?php

/**
 * Social Section — Reactions (Wow + Fav + Depoimentos)
 *
 * Page ID: page-soc-reactions
 * 3 feed-tabs: Wow (apollo-wow), Fav (apollo-fav), Depoimentos (apollo-comment)
 *
 * @package Apollo\Admin
 */

if (! defined('ABSPATH')) {
    exit;
}

?>
<div class="page" id="page-soc-reactions">
    <div class="feed-tabs">
        <button class="feed-tab active" data-sub="rx-wow" title="<?php esc_attr_e('Wow (Reactions)', 'apollo-admin'); ?>"><i class="ri-brain-ai-3-line"></i></button>
        <button class="feed-tab" data-sub="rx-fav" title="<?php esc_attr_e('Fav (Favorites)', 'apollo-admin'); ?>"><i class="ri-shining-2-fill"></i></button>
        <button class="feed-tab" data-sub="rx-comment" title="<?php esc_attr_e('Depoimentos', 'apollo-admin'); ?>"><i class="ri-speak-ai-line"></i></button>
    </div>

    <!-- Wow (Reactions) -->
    <div class="sub-content visible" id="sub-rx-wow">
        <div class="panel">
            <div class="panel-header"><i class="ri-brain-ai-3-line"></i> <?php esc_html_e('Reactions System', 'apollo-admin'); ?> <span class="badge">apollo-wow</span></div>
            <div class="panel-body">
                <div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[wow_enable]" value="1" <?php checked($apollo['wow_enable'] ?? true); ?>><span class="switch-track"></span></label>
                    <div class="toggle-text"><span class="toggle-title"><?php esc_html_e('Enable Reactions', 'apollo-admin'); ?></span><span class="toggle-desc"><?php esc_html_e('Enable the reaction system across the platform', 'apollo-admin'); ?></span></div>
                </div>
                <div class="form-grid" style="margin-top:12px">
                    <div class="field"><label class="field-label"><?php esc_html_e('Available Types', 'apollo-admin'); ?></label><input type="text" class="apollo-input input" name="apollo[wow_types]" value="<?php echo esc_attr($apollo['wow_types'] ?? 'like,love,fire,support,celebrate'); ?>"><span class="field-hint"><?php esc_html_e('Comma-separated reaction types', 'apollo-admin'); ?></span></div>
                    <div class="field">
                        <label class="field-label"><?php esc_html_e('Display Style', 'apollo-admin'); ?></label>
                        <select class="select" name="apollo[wow_style]">
                            <option value="emoji" <?php selected($apollo['wow_style'] ?? 'emoji', 'emoji'); ?>><?php esc_html_e('Emoji', 'apollo-admin'); ?></option>
                            <option value="icon" <?php selected($apollo['wow_style'] ?? 'emoji', 'icon'); ?>><?php esc_html_e('Icon', 'apollo-admin'); ?></option>
                            <option value="text" <?php selected($apollo['wow_style'] ?? 'emoji', 'text'); ?>><?php esc_html_e('Text', 'apollo-admin'); ?></option>
                        </select>
                    </div>
                </div>
                <div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[wow_anon]" value="1" <?php checked($apollo['wow_anon'] ?? false); ?>><span class="switch-track"></span></label>
                    <div class="toggle-text"><span class="toggle-title"><?php esc_html_e('Enable Anonymous Reactions', 'apollo-admin'); ?></span><span class="toggle-desc"><?php esc_html_e('Allow reactions from non-logged-in users', 'apollo-admin'); ?></span></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Fav (Favorites) -->
    <div class="sub-content" id="sub-rx-fav">
        <div class="panel">
            <div class="panel-header"><i class="ri-shining-2-fill"></i> <?php esc_html_e('Favorites System', 'apollo-admin'); ?> <span class="badge">apollo-fav</span></div>
            <div class="panel-body">
                <div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[fav_enable]" value="1" <?php checked($apollo['fav_enable'] ?? true); ?>><span class="switch-track"></span></label>
                    <div class="toggle-text"><span class="toggle-title"><?php esc_html_e('Enable Favorites', 'apollo-admin'); ?></span><span class="toggle-desc"><?php esc_html_e('Enable the fav system', 'apollo-admin'); ?></span></div>
                </div>
                <div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[fav_collections]" value="1" <?php checked($apollo['fav_collections'] ?? false); ?>><span class="switch-track"></span></label>
                    <div class="toggle-text"><span class="toggle-title"><?php esc_html_e('Enable Collections', 'apollo-admin'); ?></span><span class="toggle-desc"><?php esc_html_e('Allow users to organize favs into named collections', 'apollo-admin'); ?></span></div>
                </div>
                <div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[fav_public]" value="1" <?php checked($apollo['fav_public'] ?? false); ?>><span class="switch-track"></span></label>
                    <div class="toggle-text"><span class="toggle-title"><?php esc_html_e('Enable Public Favs', 'apollo-admin'); ?></span><span class="toggle-desc"><?php esc_html_e('Make favorites publicly visible on user profile', 'apollo-admin'); ?></span></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Depoimentos -->
    <div class="sub-content" id="sub-rx-comment">
        <div class="panel">
            <div class="panel-header"><i class="ri-speak-ai-line"></i> <?php esc_html_e('Depoimentos', 'apollo-admin'); ?> <span class="badge">apollo-comment</span></div>
            <div class="panel-body">
                <div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[dep_reactions]" value="1" <?php checked($apollo['dep_reactions'] ?? true); ?>><span class="switch-track"></span></label>
                    <div class="toggle-text"><span class="toggle-title"><?php esc_html_e('Enable Reactions on Depoimentos', 'apollo-admin'); ?></span><span class="toggle-desc"><?php esc_html_e('Allow reactions on testimonials', 'apollo-admin'); ?></span></div>
                </div>
                <div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[dep_threading]" value="1" <?php checked($apollo['dep_threading'] ?? true); ?>><span class="switch-track"></span></label>
                    <div class="toggle-text"><span class="toggle-title"><?php esc_html_e('Enable Threading', 'apollo-admin'); ?></span><span class="toggle-desc"><?php esc_html_e('Enable threaded/nested replies', 'apollo-admin'); ?></span></div>
                </div>
                <div class="form-grid" style="margin-top:12px">
                    <div class="field"><label class="field-label"><?php esc_html_e('Max Thread Depth', 'apollo-admin'); ?></label><input type="number" class="apollo-input input" name="apollo[dep_thread_depth]" value="<?php echo esc_attr($apollo['dep_thread_depth'] ?? 3); ?>"></div>
                </div>
                <div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[dep_media]" value="1" <?php checked($apollo['dep_media'] ?? false); ?>><span class="switch-track"></span></label>
                    <div class="toggle-text"><span class="toggle-title"><?php esc_html_e('Enable Media in Comments', 'apollo-admin'); ?></span><span class="toggle-desc"><?php esc_html_e('Allow image/video attachments in depoimentos', 'apollo-admin'); ?></span></div>
                </div>
                <div class="toggle-row"><label class="custom-checkbox" class="switch"><input type="checkbox" name="apollo[dep_approval]" value="1" <?php checked($apollo['dep_approval'] ?? true); ?>><span class="switch-track"></span></label>
                    <div class="toggle-text"><span class="toggle-title"><?php esc_html_e('Require Approval', 'apollo-admin'); ?></span><span class="toggle-desc"><?php esc_html_e('Moderate depoimentos before publishing', 'apollo-admin'); ?></span></div>
                </div>
            </div>
        </div>

        <!-- Depoimentos Feed Preview -->
        <div class="panel">
            <div class="panel-header"><i class="ri-gallery-line"></i> <?php esc_html_e('Feed Preview — Recent Depoimentos', 'apollo-admin'); ?> <span class="badge"><?php esc_html_e('Live Example', 'apollo-admin'); ?></span></div>
            <div class="panel-body" style="padding: var(--space-6);">
                <div class="depoimentos-container">
                    <div class="depoimentos-grid">
                        <!-- Featured Depoimento -->
                        <div class="depoimento-card featured">
                            <div class="depoimento-user">
                                <div class="depoimento-avatar"><img src="https://i.pravatar.cc/150?img=12" alt="Avatar"></div>
                                <div class="depoimento-meta">
                                    <p class="depoimento-author">Mariana Costa</p>
                                    <p class="depoimento-role">DJ • Produtora</p>
                                </div>
                            </div>
                            <div class="depoimento-content">
                                <h4>A plataforma transformou completamente a forma como gerencio minha agenda e me conecto com o público.</h4>
                                <p>Desde que comecei a usar o Apollo, consegui aumentar minha visibilidade em 300% e organizar melhor meus eventos. A integração com redes sociais é perfeita!</p>
                            </div>
                        </div>
                        <!-- Standard Depoimento 1 -->
                        <div class="depoimento-card standard">
                            <div class="depoimento-user">
                                <div class="depoimento-avatar"><img src="https://i.pravatar.cc/150?img=33" alt="Avatar"></div>
                                <div class="depoimento-meta">
                                    <p class="depoimento-author">Rafael Silva</p>
                                    <p class="depoimento-role">Produtor de Eventos</p>
                                </div>
                            </div>
                            <div class="depoimento-content">
                                <h4>Sistema completo e intuitivo</h4>
                                <p>Finalmente uma ferramenta que entende as necessidades da cena cultural carioca. Recomendo!</p>
                            </div>
                        </div>
                        <!-- Standard Depoimento 2 -->
                        <div class="depoimento-card standard">
                            <div class="depoimento-user">
                                <div class="depoimento-avatar"><img src="https://i.pravatar.cc/150?img=27" alt="Avatar"></div>
                                <div class="depoimento-meta">
                                    <p class="depoimento-author">Juliana Ferreira</p>
                                    <p class="depoimento-role">Gestora Cultural</p>
                                </div>
                            </div>
                            <div class="depoimento-content">
                                <h4>Gestão profissional ao alcance de todos</h4>
                                <p>A melhor plataforma para gerenciar espaços culturais no Rio. Dashboard claro e funcionalidades completas.</p>
                            </div>
                        </div>
                        <!-- Standard Depoimento 3 -->
                        <div class="depoimento-card standard">
                            <div class="depoimento-user">
                                <div class="depoimento-avatar"><img src="https://i.pravatar.cc/150?img=68" alt="Avatar"></div>
                                <div class="depoimento-meta">
                                    <p class="depoimento-author">Carlos Mendes</p>
                                    <p class="depoimento-role">Músico • Band Leader</p>
                                </div>
                            </div>
                            <div class="depoimento-content">
                                <h4>Conectando artistas e público</h4>
                                <p>O sistema de networking do Apollo me ajudou a encontrar colaboradores e expandir minha rede na indústria.</p>
                            </div>
                        </div>
                        <!-- Standard Depoimento 4 -->
                        <div class="depoimento-card standard">
                            <div class="depoimento-user">
                                <div class="depoimento-avatar"><img src="https://i.pravatar.cc/150?img=45" alt="Avatar"></div>
                                <div class="depoimento-meta">
                                    <p class="depoimento-author">Ana Paula Santos</p>
                                    <p class="depoimento-role">Fotógrafa de Eventos</p>
                                </div>
                            </div>
                            <div class="depoimento-content">
                                <h4>Interface moderna e responsiva</h4>
                                <p>Perfeito tanto no desktop quanto no mobile. Facilita muito meu trabalho nos eventos.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>