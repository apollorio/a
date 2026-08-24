<?php

/**
 * Template Name: Apollo Sobre
 * Template Post Type: page
 *
 * Apollo Platform About/Sobre Page Template
 * Institutional copy — original home page preserved as /sobre
 *
 * @package Apollo\Templates
 * @since 3.0.0
 */

defined('ABSPATH') || exit;

// Template parts directory.
$template_dir = plugin_dir_path(__FILE__) . 'template-parts/home/';

/**
 * Hook: apollo_before_home_content
 */
do_action('apollo_before_home_content');

ob_start();
?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">
    <link href="https://fonts.cdnfonts.com/css/alimony" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@phosphor-icons/web@2.0.3/src/bold/style.css">
    <link rel="stylesheet" href="https://assets.apollo.rio.br/css/home.css">
    <?php if (defined('APOLLO_TEMPLATES_URL') && defined('APOLLO_TEMPLATES_VERSION')) : ?>
        <link rel="stylesheet" href="<?php echo esc_url(APOLLO_TEMPLATES_URL . 'assets/css/navbar.v2.css'); ?>?v=<?php echo esc_attr(APOLLO_TEMPLATES_VERSION); ?>">
        <script src="<?php echo esc_url(APOLLO_TEMPLATES_URL . 'assets/js/navbar.v2.js'); ?>?v=<?php echo esc_attr(APOLLO_TEMPLATES_VERSION); ?>" defer></script>
    <?php endif; ?>

    <style>
        /* ═══════════════════════════════════════════════
		APOLLO HOME — CDN-ALIGNED CSS
		Backward-compat aliases → CDN Design Tokens
		═══════════════════════════════════════════════ */

        :root {
            /* Legacy aliases (used by home.css) → mapped to CDN tokens */
            --apollo-black: rgba(var(--txt-rgb), 0.95);
            --apollo-gray: rgba(var(--txt-rgb), 0.45);
            --apollo-light: var(--bg);
            --apollo-orange: var(--primary);
            --apollo-gradient: linear-gradient(135deg, var(--primary), #d94f00);
        }

        body {
            font-family: var(--ff-main);
            color: rgba(var(--txt-rgb), 0.87);
            background: #fff;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            overflow-x: hidden;
        }

        /* Container */
        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 var(--space-4);
        }

        /* Typography — DM Serif Display for home headings */
        h2 {
            font-family: 'DM Serif Display', Georgia, serif;
            font-size: 2.4rem;
            font-weight: 700;
            line-height: 1.1;
            margin-bottom: var(--space-4);
        }

        @media (min-width: 768px) {
            h2 {
                font-size: 3rem;
            }
        }

        /* Reveal Animation (GSAP-compatible) */
        .reveal-up {
            opacity: 0;
            transform: translateY(30px);
            transition: opacity 0.8s var(--ease-smooth), transform 0.8s var(--ease-smooth);
        }

        .reveal-up.visible {
            opacity: 1;
            transform: translateY(0);
        }

        .delay-100 {
            transition-delay: 0.1s;
        }

        .delay-200 {
            transition-delay: 0.2s;
        }

        .delay-300 {
            transition-delay: 0.3s;
        }

        /* Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: var(--bg);
        }

        ::-webkit-scrollbar-thumb {
            background: var(--border);
            border-radius: var(--r-sm);
        }

        ::-webkit-scrollbar-thumb:hover {
            background: rgba(var(--txt-rgb), 0.3);
        }

        /* Skeleton Loading */
        .skeleton {
            background: linear-gradient(90deg, var(--bg) 25%, var(--border) 50%, var(--bg) 75%);
            background-size: 200% 100%;
            animation: skeleton-loading 1.5s infinite;
        }

        @keyframes skeleton-loading {
            0% {
                background-position: 200% 0;
            }

            100% {
                background-position: -200% 0;
            }
        }

        /* Accessibility */
        .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(var(--rgb-d), 0);
            white-space: nowrap;
            border: 0;
        }
    </style>
<?php
$extra_head = ob_get_clean();

if (function_exists('apollo_render_document_open')) {
    apollo_render_document_open(
        array(
            'title'      => get_bloginfo('name') . ' — ' . __('A mão extra da cena', 'apollo-core'),
            'extra_head' => $extra_head,
        )
    );
} else {
    ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <?php
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    echo $extra_head;
}
?>
</head>

<body <?php body_class('apollo-home'); ?>>
    <?php wp_body_open(); ?>

    <?php apollo_render_navbar(); ?>

    <main>

        <?php
        /**
         * Hero Section
         * Video background hero with title and CTA
         */
        if (file_exists($template_dir . 'hero.php')) {
            include $template_dir . 'hero.php';
        }

        /**
         * Infra Section
         * Infraestrutura Cultural Digital hero block
         */
        if (file_exists($template_dir . 'infra.php')) {
            include $template_dir . 'infra.php';
        }

        if (file_exists($template_dir . 'marquee.php')) {
            include $template_dir . 'marquee.php';
        }

        /**
         * Mission Section
         * Manifesto and feature cards
         */
        if (file_exists($template_dir . 'mission.php')) {
            include $template_dir . 'mission.php';
        }

        /**
         * Events Listing Section
         * Dynamic events grid from database
         */
        if (file_exists($template_dir . 'events-listing.php')) {
            include $template_dir . 'events-listing.php';
        }

        /**
         * Classifieds Section
         * Ticket resales and accommodations
         */
        if (file_exists($template_dir . 'classifieds.php')) {
            include $template_dir . 'classifieds.php';
        }

        /**
         * HUB Section
         * Featured DJ/Artist profile card
         */
        if (file_exists($template_dir . 'hub-section.php')) {
            include $template_dir . 'hub-section.php';
        }

        /**
         * Tools Section
         * Accordion with platform features
         */
        if (file_exists($template_dir . 'tools-accordion.php')) {
            include $template_dir . 'tools-accordion.php';
        }

        /**
         * Footer
         */
        if (file_exists($template_dir . 'footer.php')) {
            include $template_dir . 'footer.php';
        }

        /**
         * Coupon Modal
         * Popup for Apollo discounts
         */
        if (file_exists($template_dir . 'coupon-modal.php')) {
            include $template_dir . 'coupon-modal.php';
        }
        ?>

    </main>

    <script>
        /**
         * Scroll Reveal Animation
         */
        (function() {
            var revealElements = document.querySelectorAll('.reveal-up');

            function reveal() {
                revealElements.forEach(function(el) {
                    var windowHeight = window.innerHeight;
                    var revealTop = el.getBoundingClientRect().top;
                    var revealPoint = 150;

                    if (revealTop < windowHeight - revealPoint) {
                        el.classList.add('visible');
                    }
                });
            }

            window.addEventListener('scroll', reveal);
            reveal(); // Initial check
        })();

        /**
         * Smooth scroll for anchor links
         */
        document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
            anchor.addEventListener('click', function(e) {
                var target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    e.preventDefault();
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        /**
         * Marquee in-view detection
         */
        (function() {
            var mq = document.querySelector('.marquee-wrapper');
            if (mq) {
                var obs = new IntersectionObserver(function(entries) {
                    entries.forEach(function(e) {
                        if (e.isIntersecting) mq.classList.add('in-view');
                    });
                }, {
                    threshold: 0.3
                });
                obs.observe(mq);
            }
        })();
    </script>

    <?php
    /**
     * Hook: apollo_after_home_content
     *
     * Fires after all home content but before footer scripts and closing tags.
     */
    do_action('apollo_after_home_content');

    /* Canvas Mode - NO wp_footer() to prevent theme interference */
    ?>
</body>

</html>