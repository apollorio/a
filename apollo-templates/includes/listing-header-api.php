<?php

/**
 * Apollo Listing Header — public API.
 *
 * A listing header is the month/search/filter band that sits at the top of an
 * archive-style Apollo screen (/eventos, and any future listing that needs the
 * same three affordances). It is a BLOCK, not a shell: it renders inside
 * whatever container the caller already opened — normally .ax-main from
 * apollo_plus_open() — and it never emits <head>, <body> or chrome.
 *
 * Ownership follows registry chapter 18-canvas-shell.json: apollo-templates is
 * the canonical owner of Apollo+ page chrome and of the template-part include
 * API (apollo_plus_part()). A listing header is chrome that more than one
 * plugin will want, so it lives here rather than inside the first plugin that
 * happens to need it.
 *
 * ONE-LINE USE — anywhere inside an Apollo blank-canvas / Apollo+ page:
 *
 *     apollo_listing_header();
 *
 * TYPICAL USE — a controlled component; the caller owns the data:
 *
 *     apollo_listing_header( array(
 *         'id'     => 'eventosHeader',
 *         'month'  => 7,
 *         'year'   => 2026,
 *         'months' => array( array( 'label' => 'Janeiro', 'abbr' => 'JAN', 'count' => 4 ), ... ),
 *         'filter' => array( 'groups' => array( ... ) ),
 *     ) );
 *
 * DATA FLOW (registry 03-apollo-rule.$apollo_rule.data_flow): every label,
 * option and count is a PHP value rendered server-side. The JS kernel owns
 * interaction only — it never invents a term, a month name or a count. An
 * empty group renders its empty state instead of a fixture.
 *
 * @package Apollo\Templates
 * @since   1.5.1
 */

if (! defined('ABSPATH')) {
    exit;
}

if (! function_exists('apollo_listing_header_skins')) {
    /**
     * Registered skins → the part slug that ships their CSS.
     *
     * A skin only restyles; the markup contract (classes + data-* hooks) is the
     * same for all of them, so a page can switch skin without any other change.
     *
     * @return array<string, string>
     */
    function apollo_listing_header_skins(): array
    {
        return (array) apply_filters(
            'apollo/listing_header/skins',
            array(
                // Lab "Header 02" — Kinetic Mask on the white canvas.
                'apple' => 'listing-header/skins/apple',
            )
        );
    }
}

if (! function_exists('apollo_listing_header_months')) {
    /**
     * Twelve month rows in the site locale, ready for the 'months' arg.
     *
     * @param array<int, int> $counts Optional per-month event counts, index 0-11.
     * @return array<int, array{label:string, abbr:string, count:int}>
     */
    function apollo_listing_header_months(array $counts = array()): array
    {
        $rows = array();
        for ($i = 0; $i < 12; $i++) {
            $stamp = mktime(0, 0, 0, $i + 1, 1, 2000);
            $rows[] = array(
                'label' => date_i18n('F', $stamp),
                'abbr'  => mb_strtoupper(mb_substr(date_i18n('M', $stamp), 0, 3)),
                'count' => isset($counts[$i]) ? (int) $counts[$i] : 0,
            );
        }
        return $rows;
    }
}

if (! function_exists('apollo_listing_header_args')) {
    /**
     * Normalise + harden caller args into the shape every part expects.
     *
     * Parts are then free of defensive code: they can read $alh['month']['label']
     * without checking anything, which is what keeps them small.
     *
     * @param array<string, mixed> $args Raw caller args.
     * @return array<string, mixed>
     */
    function apollo_listing_header_args(array $args): array
    {
        static $auto = 0;

        $args = wp_parse_args(
            $args,
            array(
                'id'      => '',
                'skin'    => 'apple',
                'class'   => '',
                'label'   => __('Navegação da listagem', 'apollo-templates'),
                'mark'    => '',
                'month'   => (int) current_time('n') - 1,
                'year'    => (int) current_time('Y'),
                'months'  => array(),
                'scrub'   => true,
                /*
                 * DEPRECATED, 2026-08-09 — the `next` arrow is no longer
                 * rendered by parts/actions.php at all, on any viewport. The
                 * key is kept (and flipped to false) so a caller that still
                 * passes it keeps working instead of tripping an undefined
                 * index; it simply has no effect. Month navigation is the
                 * twelve-segment scrubber.
                 */
                'next'    => false,
                'prev'    => false,
                'search'  => true,
                'filter'  => true,
            )
        );

        $id = sanitize_html_class((string) $args['id']);
        if ('' === $id) {
            $id = 'alh-' . (++$auto);
        }

        $skins = apollo_listing_header_skins();
        $skin  = sanitize_key((string) $args['skin']);
        if (! isset($skins[$skin])) {
            $skin = 'apple';
        }

        $months = is_array($args['months']) && ! empty($args['months'])
            ? array_values($args['months'])
            : apollo_listing_header_months();
        $months = array_slice(array_pad($months, 12, array()), 0, 12);
        foreach ($months as $i => $row) {
            $row           = is_array($row) ? $row : array('label' => (string) $row);
            $months[ $i ] = array(
                'label' => (string) ($row['label'] ?? ''),
                'abbr'  => (string) ($row['abbr'] ?? mb_strtoupper(mb_substr((string) ($row['label'] ?? ''), 0, 3))),
                'count' => (int) ($row['count'] ?? 0),
            );
        }

        $index = (int) $args['month'];
        $index = ($index % 12 + 12) % 12;

        $search = $args['search'];
        if (true === $search) {
            $search = array();
        }
        if (is_array($search)) {
            $search = wp_parse_args(
                $search,
                array(
                    'title'       => __('Buscar', 'apollo-templates'),
                    'placeholder' => __('Nome, loc, DJ…', 'apollo-templates'),
                    'hint'        => __('Enter ou − para enviar', 'apollo-templates'),
                    'value'       => '',
                )
            );
        }

        $filter = $args['filter'];
        if (true === $filter) {
            $filter = array();
        }
        if (is_array($filter)) {
            $filter = wp_parse_args(
                $filter,
                array(
                    'title'  => __('Filtrar', 'apollo-templates'),
                    'clear'  => __('Limpar', 'apollo-templates'),
                    'apply'  => __('Aplicar', 'apollo-templates'),
                    'empty'  => __('Nenhum filtro disponível ainda', 'apollo-templates'),
                    'groups' => array(),
                )
            );
            $filter['groups'] = apollo_listing_header_groups((array) $filter['groups'], $id);
        }

        return array(
            'id'     => $id,
            'skin'   => $skin,
            'part'   => $skins[ $skin ],
            'class'  => trim('alh alh--' . $skin . ' ' . (string) $args['class']),
            'label'  => (string) $args['label'],
            'mark'   => (string) $args['mark'],
            'month'  => array(
                'index' => $index,
                'label' => $months[ $index ]['label'],
                'abbr'  => $months[ $index ]['abbr'],
                'count' => $months[ $index ]['count'],
            ),
            'year'   => (int) $args['year'],
            'months' => $months,
            'scrub'  => (bool) $args['scrub'],
            'next'   => (bool) $args['next'],
            'prev'   => (bool) $args['prev'],
            'search' => $search,
            'filter' => $filter,
        );
    }
}

if (! function_exists('apollo_listing_header_groups')) {
    /**
     * Normalise filter groups and drop the ones with nothing to show.
     *
     * A group with zero options is REMOVED rather than rendered empty: an
     * "Categorias" heading over nothing reads as a broken filter, and inventing
     * placeholder terms would be fiction. When every group drops out, the
     * overlay prints its single empty state instead.
     *
     * @param array<int, array<string, mixed>> $groups Raw groups.
     * @param string                           $id     Header instance id (input id prefix).
     * @return array<int, array<string, mixed>>
     */
    function apollo_listing_header_groups(array $groups, string $id): array
    {
        $out = array();

        foreach ($groups as $group) {
            if (! is_array($group)) {
                continue;
            }

            $options = array();
            foreach ((array) ($group['options'] ?? array()) as $option) {
                if (! is_array($option) || ! isset($option['value'])) {
                    continue;
                }
                $options[] = array(
                    'value' => (string) $option['value'],
                    'label' => (string) ($option['label'] ?? $option['value']),
                );
            }
            if (empty($options)) {
                continue;
            }

            $key   = sanitize_key((string) ($group['key'] ?? ''));
            $key   = '' !== $key ? $key : 'g' . count($out);
            $type  = 'radio' === ($group['type'] ?? 'checkbox') ? 'radio' : 'checkbox';
            $value = $group['value'] ?? ('radio' === $type ? '' : array());

            $out[] = array(
                'key'      => $key,
                'label'    => (string) ($group['label'] ?? ''),
                'type'     => $type,
                'name'     => (string) ($group['name'] ?? ('alh_' . $key . ('checkbox' === $type ? '[]' : ''))),
                'input_id' => $id . '-' . $key,
                /* Radios are a single-choice mode switch (e.g. "Período"), not
                   a filter the user added — counting them would make the Apply
                   badge read "1" on a pristine panel. */
                'counts'   => 'checkbox' === $type,
                'value'    => array_map('strval', (array) $value),
                'options'  => $options,
            );
        }

        return $out;
    }
}

if (! function_exists('apollo_listing_header')) {
    /**
     * Render a listing header.
     *
     * @param array<string, mixed> $args See apollo_listing_header_args().
     */
    function apollo_listing_header(array $args = array()): void
    {
        if (! function_exists('apollo_plus_part')) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                echo '<!-- apollo_listing_header: apollo_plus_part() unavailable -->';
            }
            return;
        }

        apollo_plus_part('listing-header/index', array('alh' => apollo_listing_header_args($args)));
    }
}

if (! function_exists('apollo_get_listing_header')) {
    /**
     * Same as apollo_listing_header() but returns the HTML.
     *
     * @param array<string, mixed> $args See apollo_listing_header_args().
     */
    function apollo_get_listing_header(array $args = array()): string
    {
        ob_start();
        apollo_listing_header($args);
        return (string) ob_get_clean();
    }
}
