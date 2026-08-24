<?php

/**
 * SEO Text Block — Server-Side Rendered Content for Crawlers
 *
 * Outputs structured semantic HTML immediately after <body> tag,
 * pushing text content to the top of the page for search engine indexing.
 * Content is visually hidden (accessible clip-path technique) but
 * fully readable by crawlers and screen readers.
 *
 * Usage in templates:
 *   <?php Apollo\Core\SEOTextBlock::render(); ?>
 *   (call right after <body> tag)
 *
 * @package Apollo\Core
 */

declare(strict_types=1);

namespace Apollo\Core;

if (! defined('ABSPATH')) {
    exit;
}

class SEOTextBlock
{
    /**
     * Initialize SEO text block hooks
     */
    public static function init(): void
    {
        // Auto-inject on wp_body_open if available (WP 5.2+)
        add_action('wp_body_open', array(static::class, 'render'), 1);

        // Add inline CSS for SEO block (in <head> so it's parsed before body)
        add_action('wp_head', array(static::class, 'print_styles'), 99);
    }

    /**
     * Print CSS for SEO text block in <head>
     */
    public static function print_styles(): void
    {
        echo '<style id="apollo-seo-block-css">';
        echo '#apollo-seo{position:absolute;clip:rect(0 0 0 0);clip-path:inset(50%);width:1px;height:1px;overflow:hidden;white-space:nowrap;padding:0;margin:-1px;border:0}';
        echo '</style>' . "\n";
    }

    /**
     * Render the SEO text block
     *
     * Outputs structured semantic HTML with the page's text content.
     * This block is visually hidden but accessible to crawlers and screen readers.
     */
    public static function render(): void
    {
        // Skip admin, REST, AJAX, and cron requests
        if (is_admin() || wp_doing_ajax() || wp_doing_cron() || defined('REST_REQUEST')) {
            return;
        }

        $data = static::gather_page_data();
        if (empty($data['title'])) {
            return;
        }

        echo '<div id="apollo-seo" role="main" aria-label="conteúdo principal">' . "\n";

        // Breadcrumbs
        if (! empty($data['breadcrumbs'])) {
            echo '<nav aria-label="breadcrumb"><ol>';
            foreach ($data['breadcrumbs'] as $crumb) {
                if (! empty($crumb['url'])) {
                    echo '<li><a href="' . esc_url($crumb['url']) . '">' . esc_html($crumb['label']) . '</a></li>';
                } else {
                    echo '<li>' . esc_html($crumb['label']) . '</li>';
                }
            }
            echo '</ol></nav>' . "\n";
        }

        // Title
        echo '<h1>' . esc_html($data['title']) . '</h1>' . "\n";

        // Description
        if (! empty($data['description'])) {
            echo '<p>' . esc_html($data['description']) . '</p>' . "\n";
        }

        // Content body
        if (! empty($data['content'])) {
            echo '<article>' . "\n";
            echo '<p>' . esc_html($data['content']) . '</p>' . "\n";
            echo '</article>' . "\n";
        }

        // Structured meta
        if (! empty($data['meta'])) {
            echo '<dl>' . "\n";
            foreach ($data['meta'] as $key => $value) {
                echo '<dt>' . esc_html($key) . '</dt>';
                echo '<dd>' . esc_html($value) . '</dd>' . "\n";
            }
            echo '</dl>' . "\n";
        }

        echo '</div>' . "\n";
    }

    /**
     * Gather page data based on current WordPress context
     *
     * @return array{title: string, description: string, content: string, breadcrumbs: array, meta: array}
     */
    private static function gather_page_data(): array
    {
        $data = array(
            'title'       => '',
            'description' => '',
            'content'     => '',
            'breadcrumbs' => array(),
            'meta'        => array(),
        );

        // Home / front page
        if (is_front_page() || is_home()) {
            $data['title']       = get_bloginfo('name');
            $data['description'] = get_bloginfo('description');
            $data['breadcrumbs'] = array(
                array('label' => 'Apollo Rio', 'url' => ''),
            );
            $data['content'] = esc_html(get_bloginfo('description'));
            return $data;
        }

        // Single post/page/CPT
        if (is_singular()) {
            $post = get_queried_object();
            if (! $post instanceof \WP_Post) {
                return $data;
            }

            $data['title']       = get_the_title($post);
            $data['description'] = wp_trim_words(wp_strip_all_tags($post->post_content), 30, '...');
            $data['breadcrumbs'] = static::build_breadcrumbs($post);

            // CPT-specific meta
            $post_type = get_post_type($post);
            $data['meta'] = static::get_cpt_meta($post, $post_type);

            // Content excerpt (first 500 chars, stripped of shortcodes)
            $clean_content = wp_strip_all_tags(strip_shortcodes($post->post_content));
            if (strlen($clean_content) > 500) {
                $clean_content = mb_substr($clean_content, 0, 500) . '...';
            }
            $data['content'] = $clean_content;

            return $data;
        }

        // Taxonomy archive
        if (is_tax() || is_category() || is_tag()) {
            $term = get_queried_object();
            if ($term instanceof \WP_Term) {
                $data['title']       = $term->name;
                $data['description'] = $term->description ?: "Conteúdo de {$term->name} no Apollo Rio";
                $data['breadcrumbs'] = array(
                    array('label' => 'Apollo Rio', 'url' => home_url('/')),
                    array('label' => $term->name, 'url' => ''),
                );
            }
            return $data;
        }

        // Post type archive
        if (is_post_type_archive()) {
            $post_type = get_queried_object();
            if ($post_type instanceof \WP_Post_Type) {
                $label = $post_type->labels->name ?? $post_type->name;
                $data['title']       = $label;
                $data['description'] = $post_type->description ?: "Lista de {$label} no Apollo Rio";
                $data['breadcrumbs'] = array(
                    array('label' => 'Apollo Rio', 'url' => home_url('/')),
                    array('label' => $label, 'url' => ''),
                );
            }
            return $data;
        }

        // Author archive (profile page)
        if (is_author()) {
            $author = get_queried_object();
            if ($author instanceof \WP_User) {
                $social_name = get_user_meta($author->ID, '_apollo_social_name', true) ?: $author->display_name;
                $bio         = get_user_meta($author->ID, '_apollo_bio', true) ?: '';

                $data['title']       = $social_name;
                $data['description'] = $bio ?: "Perfil de {$social_name} no Apollo Rio";
                $data['breadcrumbs'] = array(
                    array('label' => 'Apollo Rio', 'url' => home_url('/')),
                    array('label' => $social_name, 'url' => ''),
                );
            }
            return $data;
        }

        // Search results
        if (is_search()) {
            $query = get_search_query();
            $data['title']       = "Resultados para: {$query}";
            $data['description'] = "Busca por \"{$query}\" no Apollo Rio";
            return $data;
        }

        // 404
        if (is_404()) {
            $data['title']       = 'Página não encontrada';
            $data['description'] = 'A página que você procura não existe no Apollo Rio.';
            return $data;
        }

        return $data;
    }

    /**
     * Build breadcrumb trail for a post
     *
     * @param \WP_Post $post
     * @return array<array{label: string, url: string}>
     */
    private static function build_breadcrumbs(\WP_Post $post): array
    {
        $crumbs = array(
            array('label' => 'Apollo Rio', 'url' => home_url('/')),
        );

        $post_type_obj = get_post_type_object($post->post_type);
        if ($post_type_obj && ! empty($post_type_obj->labels->name)) {
            $archive_url = get_post_type_archive_link($post->post_type);
            $crumbs[] = array(
                'label' => $post_type_obj->labels->name,
                'url'   => $archive_url ?: '',
            );
        }

        $crumbs[] = array('label' => get_the_title($post), 'url' => '');

        return $crumbs;
    }

    /**
     * Get CPT-specific meta for SEO block
     *
     * @param \WP_Post $post
     * @param string   $post_type
     * @return array<string, string>
     */
    private static function get_cpt_meta(\WP_Post $post, string $post_type): array
    {
        $meta = array();

        switch ($post_type) {
            case 'event':
                $start = get_post_meta($post->ID, '_event_start_date', true);
                $end   = get_post_meta($post->ID, '_event_end_date', true);
                if ($start) {
                    $meta['Data'] = wp_date('d/m/Y H:i', strtotime($start));
                }
                if ($end) {
                    $meta['Término'] = wp_date('d/m/Y H:i', strtotime($end));
                }
                $loc_id = get_post_meta($post->ID, '_event_loc_id', true);
                if ($loc_id) {
                    $meta['Local'] = get_the_title((int) $loc_id);
                }
                // DJs
                $dj_ids = get_post_meta($post->ID, '_event_dj_ids', true);
                if (is_array($dj_ids) && ! empty($dj_ids)) {
                    $names = array();
                    foreach (array_slice($dj_ids, 0, 10) as $dj_id) {
                        $names[] = get_the_title((int) $dj_id);
                    }
                    $meta['DJs'] = implode(', ', array_filter($names));
                }
                break;

            case 'dj':
                $sounds = wp_get_post_terms($post->ID, 'sound', array('fields' => 'names'));
                if (! is_wp_error($sounds) && ! empty($sounds)) {
                    $meta['Sons'] = implode(', ', $sounds);
                }
                break;

            case 'local':
                $areas = wp_get_post_terms($post->ID, 'loc_area', array('fields' => 'names'));
                if (! is_wp_error($areas) && ! empty($areas)) {
                    $meta['Área'] = implode(', ', $areas);
                }
                $types = wp_get_post_terms($post->ID, 'loc_type', array('fields' => 'names'));
                if (! is_wp_error($types) && ! empty($types)) {
                    $meta['Tipo'] = implode(', ', $types);
                }
                break;

            case 'classified':
                $category = wp_get_post_terms($post->ID, 'classified_category', array('fields' => 'names'));
                if (! is_wp_error($category) && ! empty($category)) {
                    $meta['Categoria'] = implode(', ', $category);
                }
                break;

            case 'hub':
                $hub_type = get_post_meta($post->ID, '_hub_type', true);
                if ($hub_type) {
                    $meta['Tipo'] = $hub_type;
                }
                break;
        }

        return $meta;
    }
}
