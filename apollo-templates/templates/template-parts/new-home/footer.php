<?php

/**
 * New Home — Footer
 *
 * SVG Apollo wordmark background (aprio-ft-v2) + footer grid with links.
 *
 * @package Apollo\Templates
 * @since   6.0.0
 */

if (! defined('ABSPATH')) {
    exit;
}
?>

<footer class="nh-footer">

    <!-- SVG Apollo Logo Footer — visual mark, sits BEHIND content -->
    <div class="aprio-ft-v2" aria-hidden="true">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 702.646 186.076" preserveAspectRatio="xMidYMax meet"
            xml:space="preserve"
            style="shape-rendering:geometricPrecision;text-rendering:geometricPrecision;image-rendering:optimizeQuality;fill-rule:evenodd;clip-rule:evenodd">
            <g>
                <path
                    d="M190.339 77.131c-3.03-19.698 10.386-35.841 26.537-38.295 32.333-4.913 49.321 31.891 31.757 52.379-4.405 5.138-10.321 10.91-20.461 12.342-19.496 2.753-35.377-10.451-37.833-26.426zm-.191 51.243c2.223.595 5.294 2.42 7.625 3.384 41.906 17.323 90.36-12.602 90.363-60.959.002-44.339-42.895-73.945-82.515-62.877-6.143 1.716-10.628 4.614-15.549 6.068l-.086-8.007c-2.815-.659-30.086-.664-32.906-.018l.17 179.549c2.289.745 29.92.737 32.796.048l.102-57.188zM97.789 75.98c-2.067 16.382-17.516 29.961-36.583 27.732-8.386-.98-16.01-5.298-21.232-11.624-17.779-21.539-1.961-57.555 30.492-53.353C87.233 40.906 100.16 57.18 97.789 75.98zm.115 52.557.132 7.931 32.931.209-.064-130.916-32.834-.103-.088 8.159c-6.414-.859-31.3-19.488-67.581 1.939-39.214 23.158-41.681 84.783-.435 110.523 8.759 5.467 20.132 10.092 33.021 10.391 6.899.16 12.846-.477 19.316-2.209 6.92-1.854 13.797-5.786 15.602-5.924zM669.464 75.98c-2.082 16.381-17.514 29.969-36.598 27.731-17.117-2.008-30.709-17.133-28.305-37.523 3.238-27.456 40.777-38.195 58.197-15.441 4.878 6.371 8.049 14.658 6.706 25.233zm-97.73 1.727c1.517 10.02 3.378 16.638 7.699 24.694 11.569 21.564 37.237 37.366 64.287 33.978 37.212-4.662 62.323-36.862 58.552-71.724-6.286-58.136-78.174-79.226-115.065-36.12-8.5 9.932-18.384 29.944-15.473 49.172zM359.31 38.807c19.268-2.899 35.816 10.334 38.014 26.806 2.675 20.054-10.634 35.659-26.955 37.953-19.625 2.758-35.319-10.621-37.766-26.536-3.056-19.87 10.565-35.794 26.707-38.223zm-59.331 40.627c2.804 19.334 11.869 32.828 23.131 42.124 55.133 45.508 134.387-18.025 98.608-83.068-3.941-7.165-9.148-12.539-15.075-17.749-11.088-9.746-29.98-17.271-49.736-14.586-36.395 4.945-62.053 37.933-56.928 73.279zM443.11.166V50.65c0 17.136-1.075 30.853 5.067 45.432 8.69 20.63 30.126 41.165 60.561 40.564l-.049-32.604c-21.745-1.252-32.614-15.153-32.597-36.122.018-22.558 0-45.118 0-67.912L443.11.166zM507.255.003V50.65c0 17.035-1.123 31.134 5.178 45.688 5.419 12.517 13.202 21.832 22.406 28.212 7.139 4.949 26.313 14.235 38.043 11.832l-.044-32.064c-8.489-2.264-14.091-.463-23.77-10.694-6.571-6.947-8.848-12.537-8.832-25.128.029-22.805.001-45.612.001-68.496l-32.982.003z" />
            </g>
        </svg>
    </div><!-- /.aprio-ft-v2 -->

    <!-- Footer text content — sits ABOVE the SVG bg via z-index -->
    <div class="container">
        <div class="nh-footer-grid">
            <div class="nh-footer-brand">
                <p class="nh-footer-wordmark reveal-up">APOLLO::RIO</p>
                <p class="reveal-up">Rede de hospitalidade descentralizada e arquivo cultural. Construído pela
                    comunidade.</p>
            </div>
            <div class="nh-footer-links">
                <ul>
                    <li><a href="<?php echo esc_url(home_url('/manifesto')); ?>" class="reveal-up">Manifesto</a></li>
                    <li><a href="<?php echo esc_url(home_url('/seguranca')); ?>" class="reveal-up">Segurança</a></li>
                    <li><a href="https://instagram.com/apollorio" rel="noopener noreferrer" target="_blank"
                            class="reveal-up">Instagram</a></li>
                    <li><a href="<?php echo esc_url(home_url('/contato')); ?>" class="reveal-up">Contato</a></li>
                </ul>
            </div>
        </div>
        <div class="nh-footer-bottom">
            <span class="reveal-up">&copy; 2024–<?php echo esc_html(date('Y')); ?> APOLLO PROJECT</span>
            <span class="reveal-up">RIO DE JANEIRO, BRASIL</span>
        </div>
    </div>

</footer>