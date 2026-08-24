<?php
/**
 * Skeleton — placeholder MetricGroup for future features.
 *
 * Returns empty data with a "coming soon" status.
 * Used for OutNow and any pending plugin.
 *
 * @package Apollo\Statistics\Metrics
 * @since   2.0.0
 */

declare(strict_types=1);

namespace Apollo\Statistics\Metrics;

use Apollo\Statistics\Core\MetricGroup;

if (! defined('ABSPATH')) {
    exit;
}

class Skeleton extends MetricGroup {

    protected string $slug        = 'skeleton';
    protected string $label       = 'Coming Soon';
    protected string $description = 'Placeholder for future analytics.';
    protected string $icon        = 'ri-code-box-line';
    protected int    $priority    = 10;
    protected array  $displays    = array('admin_dashboard');
    protected array  $chart       = array('type' => 'placeholder', 'library' => 'html');
    protected bool   $default_enabled = false;

    public function with_config(string $slug, string $label, string $icon = ''): static {
        $clone = clone $this;
        $clone->slug  = $slug;
        $clone->label = $label;
        if ($icon !== '') {
            $clone->icon = $icon;
        }
        return $clone;
    }

    public function compute(array $args = array()): array {
        return array(
            'status'  => 'coming_soon',
            'message' => $this->label . ' — analytics will be available when the feature launches.',
        );
    }
}
