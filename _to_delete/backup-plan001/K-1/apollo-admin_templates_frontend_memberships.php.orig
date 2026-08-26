<?php

/**
 * Apollo Admin — Frontend Memberships Management
 *
 * Canvas template for managing memberships from the frontend.
 * Admin-only access enforced by FrontendPanel::handle_template().
 *
 * @package Apollo\Admin
 * @since   1.1.0
 */

if (! \defined('ABSPATH')) {
    exit;
}

if (! current_user_can('manage_options')) {
    wp_safe_redirect(home_url('/'));
    exit;
}

// Get membership posts (CPT: membership)
$memberships = get_posts(
    array(
        'post_type'      => 'membership',
        'posts_per_page' => 100,
        'post_status'    => array('publish', 'draft', 'pending'),
        'orderby'        => 'menu_order',
        'order'          => 'ASC',
    )
);

// Get membership settings from admin options
$settings = get_option(APOLLO_ADMIN_OPTION_KEY, array());
if (! \is_array($settings)) {
    $settings = array();
}

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Memberships — Apollo Admin</title>
    <?php wp_head(); ?>
    <script src="<?php echo esc_url(\defined('APOLLO_CDN_URL') ? apollo_cdn_core_js_url() : apollo_cdn_core_js_url()); ?>" fetchpriority="high"></script>
    <style>
        :root {
            --ap-font: "Space Grotesk", system-ui, -apple-system, sans-serif;
            --ap-orange: FF9820;
            --surface: #f4f4f5;
            --card-bg: #fff;
            --border: #e5e7eb;
            --text: #111827;
            --muted: #9ca3af;
        }

        [data-theme="dark"] {
            --surface: #0a0a0c;
            --card-bg: #18181b;
            --border: #27272a;
            --text: #e4e4e7;
            --muted: #71717a;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: var(--ap-font);
            background: var(--card);
            color: var(--text);
            min-height: 100vh;
        }

        .mem-page {
            max-width: 960px;
            margin: 0 auto;
            padding: calc(var(--nav-height, 60px) + 2rem) 1.5rem 3rem;
        }

        .mem-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .mem-header h1 {
            font-size: 1.5rem;
            font-weight: 700;
        }

        .mem-header .back-link {
            color: var(--muted);
            text-decoration: none;
            font-size: .85rem;
            margin-left: auto;
        }

        .mem-header .back-link:hover {
            color: var(--ap-orange);
        }

        /* Actions bar */
        .mem-actions {
            display: flex;
            gap: .75rem;
            margin-bottom: 2rem;
        }

        .mem-btn {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            padding: .5rem 1rem;
            border-radius: 8px;
            border: 1px solid var(--border);
            background: var(--card);
            color: var(--text);
            font-size: .82rem;
            font-weight: 500;
            cursor: pointer;
            transition: all .15s;
            text-decoration: none;
            font-family: inherit;
        }

        .mem-btn:hover {
            border-color: var(--ap-orange);
            color: var(--ap-orange);
        }

        .mem-btn--primary {
            background: var(--ap-orange);
            color: #fff;
            border-color: var(--ap-orange);
        }

        .mem-btn--primary:hover {
            background: #d65400;
        }

        /* Plans grid */
        .mem-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.25rem;
        }

        .mem-card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 1.5rem;
            transition: all .2s ease;
            position: relative;
        }

        .mem-card:hover {
            border-color: var(--ap-orange);
            box-shadow: 0 6px 20px rgba(var(--rgb-d), .06);
        }

        .mem-card .mc-status {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: .65rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .05em;
            padding: .2rem .6rem;
            border-radius: 6px;
        }

        .mc-status.publish {
            background: rgba(34, 197, 94, .1);
            color: #16a34a;
        }

        .mc-status.draft {
            background: rgba(158, 158, 158, .1);
            color: #9e9e9e;
        }

        .mc-status.pending {
            background: rgba(244, 95, 0, .1);
            color: var(--ap-orange);
        }

        .mem-card h3 {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: .35rem;
        }

        .mem-card .mc-price {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--ap-orange);
            margin-bottom: .75rem;
        }

        .mem-card .mc-price small {
            font-size: .7rem;
            font-weight: 400;
            color: var(--muted);
        }

        .mem-card .mc-features {
            list-style: none;
            padding: 0;
            margin: 0 0 1rem;
        }

        .mem-card .mc-features li {
            display: flex;
            align-items: center;
            gap: .4rem;
            font-size: .8rem;
            padding: .2rem 0;
            color: var(--text);
        }

        .mem-card .mc-features li i {
            color: #22c55e;
            font-size: .85rem;
        }

        .mem-card .mc-actions {
            display: flex;
            gap: .5rem;
        }

        .mc-edit-btn {
            flex: 1;
            padding: .45rem;
            border-radius: 8px;
            border: 1px solid var(--border);
            background: var(--card);
            color: var(--text);
            font-size: .78rem;
            font-weight: 500;
            cursor: pointer;
            transition: all .15s;
            text-decoration: none;
            text-align: center;
            font-family: inherit;
        }

        .mc-edit-btn:hover {
            border-color: var(--ap-orange);
            color: var(--ap-orange);
        }

        .mem-empty {
            text-align: center;
            padding: 3rem;
            color: var(--muted);
        }

        .mem-empty i {
            font-size: 2.5rem;
            display: block;
            margin-bottom: .75rem;
            opacity: .4;
        }

        @media (max-width:640px) {
            .mem-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body <?php body_class(); ?>>
    <?php wp_body_open(); ?>

    <div class="mem-page">
        <div class="mem-header">
            <i class="ri-vip-crown-fill" style="font-size:1.8rem;color:var(--ap-orange)"></i>
            <h1>Memberships</h1>
            <a href="<?php echo esc_url(home_url('/admin/panel')); ?>" class="back-link">
                <i class="ri-arrow-left-line"></i> Admin Panel
            </a>
        </div>

        <div class="mem-actions">
            <a href="<?php echo esc_url(admin_url('post-new.php?post_type=membership')); ?>" class="mem-btn mem-btn--primary" target="_blank">
                <i class="ri-add-line"></i> Novo Plano
            </a>
            <a href="<?php echo esc_url(admin_url('edit.php?post_type=membership')); ?>" class="mem-btn" target="_blank">
                <i class="ri-list-check"></i> Gerenciar no WP-Admin
            </a>
        </div>

        <div class="mem-grid">
            <?php if (empty($memberships)) : ?>
                <div class="mem-empty">
                    <i class="ri-vip-crown-fill"></i>
                    Nenhum plano de membership criado ainda.
                </div>
            <?php else : ?>
                <?php foreach ($memberships as $m) :
                    $price    = get_post_meta($m->ID, '_membership_price', true);
                    $period   = get_post_meta($m->ID, '_membership_period', true) ?: 'mês';
                    $features = get_post_meta($m->ID, '_membership_features', true);
                    if (\is_string($features)) {
                        $features = \array_filter(\array_map('trim', \explode("\n", $features)));
                    }
                    if (! \is_array($features)) {
                        $features = array();
                    }
                ?>
                    <div class="mem-card">
                        <span class="mc-status <?php echo esc_attr($m->post_status); ?>">
                            <?php echo esc_html($m->post_status); ?>
                        </span>
                        <h3><?php echo esc_html($m->post_title ?: '(Sem nome)'); ?></h3>
                        <div class="mc-price">
                            <?php if ($price) : ?>
                                R$<?php echo esc_html($price); ?> <small>/<?php echo esc_html($period); ?></small>
                            <?php else : ?>
                                <small>Preço não definido</small>
                            <?php endif; ?>
                        </div>
                        <?php if (! empty($features)) : ?>
                            <ul class="mc-features">
                                <?php foreach (\array_slice($features, 0, 6) as $feat) : ?>
                                    <li><i class="ri-check-fill"></i> <?php echo esc_html($feat); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                        <div class="mc-actions">
                            <a href="<?php echo esc_url(get_edit_post_link($m->ID, 'raw')); ?>" class="mc-edit-btn" target="_blank">
                                <i class="ri-pencil-fill"></i> Editar
                            </a>
                            <a href="<?php echo esc_url(get_permalink($m->ID)); ?>" class="mc-edit-btn" target="_blank">
                                <i class="ri-eye-fill"></i> Ver
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php wp_footer(); ?>
</body>

</html>