<?php
/**
 * ChartAdapter — transforms MetricGroup compute() output into amCharts 5 JSON.
 *
 * Maps each MetricGroup's chart config + raw data into the exact
 * structure expected by admin-charts.js renderers.
 *
 * @package Apollo\Statistics\Frontend
 * @since   2.0.0
 */

declare(strict_types=1);

namespace Apollo\Statistics\Frontend;

use Apollo\Statistics\Core\MetricGroup;

if (! defined('ABSPATH')) {
    exit;
}

final class ChartAdapter {

    /**
     * Convert a MetricGroup's render_data() output into amCharts-ready JSON.
     *
     * @param MetricGroup $metric  The metric instance.
     * @param array       $args    Compute args (days, since, limit, user_id, etc.).
     * @return array Chart-ready payload for admin-charts.js.
     */
    public static function adapt(MetricGroup $metric, array $args = array()): array {
        $rendered = $metric->render_data($args);

        if (($rendered['status'] ?? '') !== 'ok') {
            return $rendered;
        }

        $chart_type = $rendered['chart']['type'] ?? 'line';
        $data       = $rendered['data'] ?? array();

        $chart_data = self::transform($chart_type, $data);

        return array(
            'slug'       => $rendered['slug'],
            'label'      => $rendered['label'],
            'icon'       => $rendered['icon'],
            'chart_type' => self::resolve_chart_type($chart_type),
            'status'     => 'ok',
            'data'       => $chart_data,
        );
    }

    /**
     * Batch-adapt multiple metrics at once.
     *
     * @param MetricGroup[] $metrics
     * @param array         $args
     * @return array[] Array of chart-ready payloads.
     */
    public static function adapt_many(array $metrics, array $args = array()): array {
        $result = array();
        foreach ($metrics as $metric) {
            $result[] = self::adapt($metric, $args);
        }
        return $result;
    }

    /**
     * Resolve internal chart type → admin-charts.js renderer key.
     */
    private static function resolve_chart_type(string $type): string {
        $map = array(
            'line'        => 'time_series',
            'sparkline'   => 'sparkline',
            'bar'         => 'bar',
            'donut'       => 'donut',
            'funnel'      => 'funnel',
            'number'      => 'number',
            'table'       => 'table',
            'leaderboard' => 'leaderboard',
            'comparison'  => 'grouped_bar',
            'stacked'     => 'stacked_area',
        );

        return $map[$type] ?? 'time_series';
    }

    /**
     * Transform raw compute() data into chart-specific structure.
     *
     * @param string $chart_type MetricGroup chart type.
     * @param array  $data       Raw compute() result.
     * @return array Transformed data.
     */
    private static function transform(string $chart_type, array $data): array {
        switch ($chart_type) {
            case 'line':
            case 'sparkline':
                return self::transform_time_series($data);

            case 'donut':
                return self::transform_distribution($data);

            case 'bar':
            case 'table':
            case 'leaderboard':
                return self::transform_ranking($data);

            case 'funnel':
                return self::transform_funnel($data);

            case 'number':
                return self::transform_number($data);

            case 'comparison':
                return self::transform_comparison($data);

            default:
                return $data;
        }
    }

    /**
     * Time series: ensure {date, value} pairs sorted by date.
     */
    private static function transform_time_series(array $data): array {
        $series = $data['time_series'] ?? $data;
        if (! is_array($series)) {
            return array('time_series' => array());
        }

        $normalized = array();
        foreach ($series as $point) {
            $normalized[] = array(
                'date'  => $point['date'] ?? '',
                'value' => (int) ($point['value'] ?? $point['count'] ?? 0),
            );
        }

        usort($normalized, static fn(array $a, array $b): int => strcmp($a['date'], $b['date']));

        return array(
            'time_series' => $normalized,
            'total'       => array_sum(array_column($normalized, 'value')),
        );
    }

    /**
     * Distribution: ensure {label, value} pairs for donut/pie.
     */
    private static function transform_distribution(array $data): array {
        $items = $data['breakdown'] ?? $data;
        if (! is_array($items)) {
            return array('breakdown' => array());
        }

        $normalized = array();
        foreach ($items as $item) {
            $normalized[] = array(
                'label' => (string) ($item['label'] ?? $item['category'] ?? $item['name'] ?? ''),
                'value' => (int) ($item['value'] ?? $item['count'] ?? 0),
            );
        }

        return array(
            'breakdown' => $normalized,
            'total'     => array_sum(array_column($normalized, 'value')),
        );
    }

    /**
     * Ranking/leaderboard: ensure {label, value} or {label, value, meta}.
     */
    private static function transform_ranking(array $data): array {
        $items = $data['items'] ?? $data;
        if (! is_array($items)) {
            return array('items' => array());
        }

        $normalized = array();
        foreach ($items as $item) {
            $entry = array(
                'label' => (string) ($item['label'] ?? $item['name'] ?? $item['title'] ?? ''),
                'value' => (int) ($item['value'] ?? $item['total'] ?? $item['count'] ?? 0),
            );
            // Preserve extra fields (avatar_url, link, etc.).
            foreach ($item as $k => $v) {
                if (! isset($entry[$k])) {
                    $entry[$k] = $v;
                }
            }
            $normalized[] = $entry;
        }

        return array('items' => $normalized);
    }

    /**
     * Funnel: ensure {stage, count} pairs in order.
     */
    private static function transform_funnel(array $data): array {
        $stages = $data['stages'] ?? $data;
        if (! is_array($stages)) {
            return array('stages' => array());
        }

        $normalized = array();
        foreach ($stages as $stage) {
            $normalized[] = array(
                'stage' => (string) ($stage['stage'] ?? $stage['label'] ?? ''),
                'count' => (int) ($stage['count'] ?? $stage['value'] ?? 0),
            );
        }

        // Compute conversion rates between stages.
        for ($i = 0, $len = count($normalized); $i < $len; $i++) {
            $prev = $i > 0 ? $normalized[$i - 1]['count'] : $normalized[$i]['count'];
            $normalized[$i]['rate'] = $prev > 0
                ? round(($normalized[$i]['count'] / $prev) * 100, 1)
                : 0.0;
        }

        return array('stages' => $normalized);
    }

    /**
     * Number card: single value + optional delta.
     */
    private static function transform_number(array $data): array {
        return array(
            'value'       => (int) ($data['value'] ?? $data['total'] ?? 0),
            'label'       => (string) ($data['label'] ?? ''),
            'delta'       => (float) ($data['delta'] ?? 0),
            'delta_label' => (string) ($data['delta_label'] ?? ''),
        );
    }

    /**
     * Comparison: item vs average.
     */
    private static function transform_comparison(array $data): array {
        return array(
            'item_value' => (float) ($data['item_value'] ?? 0),
            'average'    => (float) ($data['average'] ?? 0),
            'ratio'      => (float) ($data['ratio'] ?? 0),
            'item_label' => (string) ($data['item_label'] ?? ''),
        );
    }
}
