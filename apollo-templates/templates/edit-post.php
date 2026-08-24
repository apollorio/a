<?php

/**
 * Generic Frontend Edit Template — Canvas Mode
 *
 * Full HTML document (like apollo-users/edit-profile.php):
 *   - No wp_head()/wp_footer() by default — canvas mode
 *   - Loads Apollo CDN core.js for design tokens
 *   - Hero section with cover + avatar (if configured)
 *   - Card-based sections for each field group
 *   - Fixed save bar at bottom
 *   - Toast notification element
 *
 * Available variables (injected by FrontendRouter):
 *   $post       — WP_Post object
 *   $post_id    — int
 *   $post_type  — string
 *   $editor     — FrontendEditor instance
 *   $config     — array (from apollo_editor_config_{cpt})
 *   $sections   — array<string, array> (fields by section)
 *   $rest_nonce — string
 *   $ajax_nonce — string
 *
 * Theme overridable:
 *   - {theme}/apollo-templates/edit-post.php
 *   - {theme}/apollo-templates/edit-{cpt}.php  (CPT-specific)
 *
 * @package Apollo\Templates
 */

if (! defined('ABSPATH')) {
    exit;
}

// Extract config values
$page_title     = $config['page_title'] ?? __('Editar', 'apollo-templates');
$hero_enabled   = $config['hero_enabled'] ?? true;
$cover_field    = $config['cover_field'] ?? '';
$avatar_field   = $config['avatar_field'] ?? '';
$cancel_url     = $config['cancel_url'] ?: get_permalink($post_id);
$save_label     = $config['save_label'] ?? __('Salvar', 'apollo-templates');
$section_labels = $config['section_labels'] ?? array();
$section_icons  = $config['section_icons'] ?? array();

// Get fields renderer
$fields_renderer = \Apollo\Templates\FrontendFields::get_instance();
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> class="<?php echo is_user_logged_in() ? 'logged-in' : ''; ?>">

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html($page_title . ' — ' . $post->post_title . ' — ' . get_bloginfo('name')); ?></title>

    <!-- Apollo CDN — Design Tokens (loads fonts + RemixIcon) -->
    <script src="<?php echo esc_url( function_exists('apollo_cdn_core_js_url') ? apollo_cdn_core_js_url() : 'https://cdn.apollo.rio.br/v1.0.0/core.js?v=t0x1x&versao=bb' ); ?>" fetchpriority="high" crossorigin="anonymous"></script>

    <!-- Navbar v2 (from apollo-templates) -->
    <?php if (defined('APOLLO_TEMPLATES_URL')) : ?>
        <link rel="stylesheet" href="<?php echo esc_url(APOLLO_TEMPLATES_URL . 'assets/css/navbar.v2.css'); ?>">
        <script src="<?php echo esc_url(APOLLO_TEMPLATES_URL . 'assets/js/navbar.v2.js'); ?>" defer></script>
    <?php endif; ?>

    <!-- Frontend Editor CSS -->
    <link rel="stylesheet" href="<?php echo esc_url(APOLLO_TEMPLATES_URL . 'assets/css/frontend-editor.css'); ?>?v=<?php echo esc_attr(APOLLO_TEMPLATES_VERSION); ?>">

    <?php
    /**
     * Hook: apollo_editor_head
     * Allows plugins to add CSS/JS to the editor head.
     *
     * @param string   $post_type
     * @param \WP_Post $post
     */
    do_action('apollo_editor_head', $post_type, $post);
    ?>

    <?php
    // Enqueue WP scripts for AJAX (jQuery is needed) - REMOVED for blank canvas
    // wp_head();
    ?>
</head>

<body class="apollo-editor-page apollo-editor-<?php echo esc_attr($post_type); ?>">

    <?php apollo_render_navbar(); ?>

    <!-- Toast notification element -->
    <div id="apollo-toast"></div>

    <div class="apollo-editor-container">

        <!-- Page Header -->
        <div class="apollo-editor-header">
            <a href="<?php echo esc_url($cancel_url); ?>" class="apollo-editor-back">
                <i class="ri-arrow-left-line"></i>
                <?php esc_html_e('Voltar', 'apollo-templates'); ?>
            </a>
            <span class="apollo-editor-page-title">
                <?php echo esc_html($page_title); ?> · <?php echo esc_html(ucfirst($post_type)); ?>
            </span>

            <?php if ($post->post_status === 'draft') : ?>
                <span class="tag" style="margin-left:auto; color: var(--primary);">
                    <i class="ri-draft-line"></i> <?php esc_html_e('Rascunho', 'apollo-templates'); ?>
                </span>
            <?php endif; ?>
        </div>

        <?php
        /*
		═══════════════════════════════════════════════════════════════════
			HERO SECTION (cover + avatar)
			═══════════════════════════════════════════════════════════════════ */
        if ($hero_enabled && ($cover_field || $avatar_field)) :
        ?>
            <div class="apollo-editor-hero">
                <?php
                // Cover image
                if ($cover_field) :
                    $cover_raw = get_post_meta($post_id, $cover_field, true);
                    $cover_url = '';
                    if (is_numeric($cover_raw) && (int) $cover_raw > 0) {
                        $cover_url = (string) wp_get_attachment_image_url((int) $cover_raw, 'medium_large');
                    } elseif (is_string($cover_raw) && $cover_raw !== '') {
                        $cover_url = $cover_raw;
                    }
                ?>
                    <div class="apollo-image-upload apollo-image-cover"
                        data-meta-key="<?php echo esc_attr($cover_field); ?>"
                        data-post-id="<?php echo esc_attr((string) $post_id); ?>">
                        <div class="apollo-image-preview <?php echo $cover_url ? 'has-image' : ''; ?>">
                            <?php if ($cover_url) : ?>
                                <img src="<?php echo esc_url($cover_url); ?>" alt="">
                            <?php endif; ?>
                            <div class="apollo-image-overlay">
                                <button type="button" class="apollo-image-upload-btn hero-edit-btn">
                                    <i class="ri-camera-line"></i>
                                    <span><?php esc_html_e('Alterar capa', 'apollo-templates'); ?></span>
                                </button>
                                <?php if ($cover_url) : ?>
                                    <button type="button" class="apollo-image-delete-btn hero-edit-btn">
                                        <i class="ri-delete-bin-line"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <input type="file" class="apollo-image-file-input" accept="image/jpeg,image/png,image/webp,image/gif" style="display:none">
                        <input type="hidden" name="<?php echo esc_attr($cover_field); ?>" value="<?php echo esc_attr((string) $cover_raw); ?>">
                    </div>
                <?php endif; ?>

                <?php
                // Avatar / main image
                if ($avatar_field) :
                    $avatar_raw = get_post_meta($post_id, $avatar_field, true);
                    $avatar_url = '';
                    if (is_numeric($avatar_raw) && (int) $avatar_raw > 0) {
                        $avatar_url = (string) wp_get_attachment_image_url((int) $avatar_raw, 'medium_large');
                    } elseif (is_string($avatar_raw) && $avatar_raw !== '') {
                        $avatar_url = $avatar_raw;
                    }
                ?>
                    <div class="apollo-editor-hero-avatar">
                        <div class="apollo-image-upload"
                            data-meta-key="<?php echo esc_attr($avatar_field); ?>"
                            data-post-id="<?php echo esc_attr((string) $post_id); ?>">
                            <div class="apollo-image-preview <?php echo $avatar_url ? 'has-image' : ''; ?>">
                                <?php if ($avatar_url) : ?>
                                    <img src="<?php echo esc_url($avatar_url); ?>" alt="">
                                <?php endif; ?>
                                <div class="apollo-image-overlay">
                                    <button type="button" class="apollo-image-upload-btn hero-edit-btn">
                                        <i class="ri-camera-line"></i>
                                    </button>
                                </div>
                            </div>
                            <input type="file" class="apollo-image-file-input" accept="image/jpeg,image/png,image/webp,image/gif" style="display:none">
                            <input type="hidden" name="<?php echo esc_attr($avatar_field); ?>" value="<?php echo esc_attr((string) $avatar_raw); ?>">
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php
        /*
		═══════════════════════════════════════════════════════════════════
			FORM + FIELD SECTIONS
			═══════════════════════════════════════════════════════════════════ */
        ?>
        <form id="apollo-editor-form"
            class="apollo-editor-form"
            data-post-id="<?php echo esc_attr((string) $post_id); ?>"
            data-post-type="<?php echo esc_attr($post_type); ?>"
            method="post"
            enctype="multipart/form-data"
            novalidate>

            <?php wp_nonce_field('apollo_frontend_editor', '_apollo_editor_nonce'); ?>
            <input type="hidden" name="action" value="apollo_frontend_save">
            <input type="hidden" name="post_id" value="<?php echo esc_attr((string) $post_id); ?>">
            <input type="hidden" name="post_type" value="<?php echo esc_attr($post_type); ?>">

            <?php
            /**
             * Hook: apollo_editor_before_fields
             *
             * @param string   $post_type
             * @param \WP_Post $post
             */
            do_action('apollo_editor_before_fields', $post_type, $post);
            ?>

            <?php
            /*
			 * Render sections as separate cards.
			 * Each section key maps to a card with title + icon.
			 */
            $section_order = $config['sections'] ?? array_keys($sections);
            $card_index    = 0;

            foreach ($section_order as $section_key) :
                if (! isset($sections[$section_key]) || empty($sections[$section_key])) {
                    continue;
                }

                $section_fields = $sections[$section_key];
                $section_label  = $section_labels[$section_key] ?? ucfirst($section_key);
                $section_icon   = $section_icons[$section_key] ?? 'ri-file-list-line';
                ++$card_index;

                // Skip hero fields if hero section is rendered separately
                if ($section_key === 'hero' && $hero_enabled && ($cover_field || $avatar_field)) {
                    // Render hero fields (title, etc.) inside a special card after the hero
                    $has_non_image_hero_fields = false;
                    foreach ($section_fields as $f) {
                        if (($f['type'] ?? '') !== 'image') {
                            $has_non_image_hero_fields = true;
                            break;
                        }
                    }

                    if ($has_non_image_hero_fields) :
            ?>
                        <div class="apollo-editor-card" style="<?php echo $avatar_field ? 'padding-top: 56px;' : ''; ?> animation-delay: <?php echo ($card_index * 0.1); ?>s;">
                            <ul class="apollo-editor-fields">
                                <?php
                                foreach ($section_fields as $field) {
                                    // Skip image fields (rendered in hero above)
                                    if (($field['type'] ?? '') === 'image') {
                                        continue;
                                    }
                                    $name  = $field['name'] ?? '';
                                    $type  = $field['type'] ?? 'text';
                                    $value = '';

                                    // Get value
                                    $is_core = $field['is_core'] ?? false;
                                    if ($is_core) {
                                        switch ($name) {
                                            case 'post_title':
                                                $value = $post->post_title;
                                                break;
                                            case 'post_content':
                                                $value = $post->post_content;
                                                break;
                                            case 'post_excerpt':
                                                $value = $post->post_excerpt;
                                                break;
                                        }
                                    } else {
                                        $meta_val = get_post_meta($post_id, $name, true);
                                        $value    = is_array($meta_val) ? wp_json_encode($meta_val) : (string) $meta_val;
                                    }

                                    $field = wp_parse_args(
                                        $field,
                                        array(
                                            'label'       => '',
                                            'icon'        => '',
                                            'placeholder' => '',
                                            'required'    => false,
                                            'maxlength'   => 0,
                                            'class'       => '',
                                            'options'     => array(),
                                            'taxonomy'    => '',
                                            'description' => '',
                                            'readonly'    => false,
                                            'rows'        => 4,
                                        )
                                    );

                                    $li_classes = array('apollo-editor-field', 'apollo-field-' . $type);
                                    if ($field['class']) {
                                        $li_classes[] = $field['class'];
                                    }
                                    if ($field['required']) {
                                        $li_classes[] = 'apollo-field-required';
                                    }

                                    echo '<li class="' . esc_attr(implode(' ', $li_classes)) . '">';

                                    // Label
                                    if ($field['label'] && $type !== 'section') {
                                        echo '<label class="field-label" for="field-' . esc_attr($name) . '">';
                                        if ($field['icon']) {
                                            echo '<i class="' . esc_attr($field['icon']) . '"></i> ';
                                        }
                                        echo esc_html($field['label']);
                                        if ($field['required']) {
                                            echo ' <span class="required-mark">*</span>';
                                        }
                                        if ((int) $field['maxlength'] > 0 && in_array($type, array('text', 'textarea'))) {
                                            echo ' <small class="char-counter" data-field="' . esc_attr($name) . '">(' . mb_strlen($value) . '/' . (int) $field['maxlength'] . ')</small>';
                                        }
                                        echo '</label>';
                                    }

                                    // Field
                                    $fields_renderer->render($type, $field, $value, $post);

                                    // Help text
                                    if ($field['description']) {
                                        echo '<span class="apollo-field-help">' . esc_html($field['description']) . '</span>';
                                    }

                                    echo '</li>';
                                }
                                ?>
                            </ul>
                        </div>
                <?php
                    endif;
                    continue;
                }
                ?>

                <div class="apollo-editor-card" style="animation-delay: <?php echo ($card_index * 0.1); ?>s;">
                    <div class="apollo-editor-card-title">
                        <i class="<?php echo esc_attr($section_icon); ?>"></i>
                        <?php echo esc_html($section_label); ?>
                    </div>

                    <ul class="apollo-editor-fields">
                        <?php
                        foreach ($section_fields as $field) {
                            $name  = $field['name'] ?? '';
                            $type  = $field['type'] ?? 'text';
                            $value = '';

                            // Get value
                            $is_core = $field['is_core'] ?? false;
                            if ($is_core) {
                                switch ($name) {
                                    case 'post_title':
                                        $value = $post->post_title;
                                        break;
                                    case 'post_content':
                                        $value = $post->post_content;
                                        break;
                                    case 'post_excerpt':
                                        $value = $post->post_excerpt;
                                        break;
                                    case '_thumbnail_id':
                                        $value = (string) get_post_thumbnail_id($post_id);
                                        break;
                                }
                            } elseif ($type === 'taxonomy' && ! empty($field['taxonomy'])) {
                                $terms = wp_get_post_terms($post_id, $field['taxonomy'], array('fields' => 'ids'));
                                $value = is_wp_error($terms) ? '' : implode(',', $terms);
                            } else {
                                $meta_val = get_post_meta($post_id, $name, true);
                                $value    = is_array($meta_val) ? wp_json_encode($meta_val) : (string) $meta_val;
                            }

                            if ($type === 'hidden') {
                                echo '<input type="hidden" name="' . esc_attr($name) . '" value="' . esc_attr($value) . '">';
                                continue;
                            }

                            $field = wp_parse_args(
                                $field,
                                array(
                                    'label'       => '',
                                    'icon'        => '',
                                    'placeholder' => '',
                                    'required'    => false,
                                    'maxlength'   => 0,
                                    'class'       => '',
                                    'options'     => array(),
                                    'taxonomy'    => '',
                                    'description' => '',
                                    'readonly'    => false,
                                    'rows'        => 4,
                                )
                            );

                            $li_classes = array('apollo-editor-field', 'apollo-field-' . $type, 'apollo-field--' . sanitize_html_class($name));
                            if ($field['class']) {
                                $li_classes[] = $field['class'];
                            }
                            if ($field['required']) {
                                $li_classes[] = 'apollo-field-required';
                            }

                            echo '<li class="' . esc_attr(implode(' ', $li_classes)) . '">';

                            // Label
                            if ($field['label'] && $type !== 'section') {
                                echo '<label class="field-label" for="field-' . esc_attr($name) . '">';
                                if ($field['icon']) {
                                    echo '<i class="' . esc_attr($field['icon']) . '"></i> ';
                                }
                                echo esc_html($field['label']);
                                if ($field['required']) {
                                    echo ' <span class="required-mark">*</span>';
                                }
                                if ((int) $field['maxlength'] > 0 && in_array($type, array('text', 'textarea'))) {
                                    echo ' <small class="char-counter" data-field="' . esc_attr($name) . '">(' . mb_strlen($value) . '/' . (int) $field['maxlength'] . ')</small>';
                                }
                                echo '</label>';
                            }

                            // Field
                            $fields_renderer->render($type, $field, $value, $post);

                            // Help text
                            if ($field['description']) {
                                echo '<span class="apollo-field-help">' . esc_html($field['description']) . '</span>';
                            }

                            echo '</li>';
                        }
                        ?>
                    </ul>
                </div>

            <?php endforeach; ?>

            <?php
            /**
             * Hook: apollo_editor_after_fields
             *
             * @param string   $post_type
             * @param \WP_Post $post
             */
            do_action('apollo_editor_after_fields', $post_type, $post);
            ?>

        </form>

    </div><!-- .apollo-editor-container -->

    <!-- Save Bar — fixed bottom -->
    <div class="apollo-save-bar">
        <span class="apollo-save-status"></span>

        <a href="<?php echo esc_url($cancel_url); ?>" class="btn btn-ghost" data-tooltip="<?php esc_attr_e('Cancelar', 'apollo-templates'); ?>">
            <i class="ri-close-line"></i>
        </a>

        <button type="button" class="btn btn-primary apollo-save-btn" data-tooltip="<?php echo esc_attr($save_label); ?>">
            <i class="ri-save-line"></i>
        </button>
    </div>

    <!-- Localize script data -->
    <script>
        var apolloEditor =
            <?php
            echo wp_json_encode(
                array(
                    'ajaxUrl'     => admin_url('admin-ajax.php'),
                    'restUrl'     => rest_url('apollo/v1/'),
                    'restNonce'   => $rest_nonce,
                    'ajaxNonce'   => $ajax_nonce,
                    'postId'      => $post_id,
                    'postType'    => $post_type,
                    'viewUrl'     => get_permalink($post_id),
                    'maxUpload'   => wp_max_upload_size(),
                    'allowedMime' => array('image/jpeg', 'image/png', 'image/webp', 'image/gif'),
                    'i18n'        => array(
                        'saving'       => __('Salvando...', 'apollo-templates'),
                        'saved'        => __('Salvo com sucesso!', 'apollo-templates'),
                        'error'        => __('Erro ao salvar', 'apollo-templates'),
                        'uploading'    => __('Enviando imagem...', 'apollo-templates'),
                        'uploaded'     => __('Imagem enviada!', 'apollo-templates'),
                        'uploadError'  => __('Erro no upload', 'apollo-templates'),
                        'confirm'      => __('Descartar alterações?', 'apollo-templates'),
                        'required'     => __('Campo obrigatório', 'apollo-templates'),
                        'invalidUrl'   => __('URL inválida', 'apollo-templates'),
                        'invalidEmail' => __('E-mail inválido', 'apollo-templates'),
                        'deleted'      => __('Imagem removida', 'apollo-templates'),
                    ),
                )
            );
            ?>;
    </script>

    <!-- jQuery (from WP) -->
    <?php wp_print_scripts('jquery'); ?>

    <!-- Frontend Editor JS -->
    <script src="<?php echo esc_url(APOLLO_TEMPLATES_URL . 'assets/js/frontend-editor.js'); ?>?v=<?php echo esc_attr(APOLLO_TEMPLATES_VERSION); ?>"></script>

    <?php if (defined('APOLLO_TEMPLATES_URL')) : ?>
        <script src="<?php echo esc_url(APOLLO_TEMPLATES_URL . 'assets/js/navbar.v2.js'); ?>"></script>
    <?php endif; ?>

    <?php
    /**
     * Hook: apollo_editor_footer
     *
     * @param string   $post_type
     * @param \WP_Post $post
     */
    do_action('apollo_editor_footer', $post_type, $post);
    ?>

    <?php
    // wp_footer(); // Removed for blank canvas
    ?>

</body>

</html>