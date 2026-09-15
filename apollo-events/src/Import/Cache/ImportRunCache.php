<?php

/**
 * Short-lived cache between preview and import (avoid double provider fetch).
 *
 * @package Apollo\Event
 * @since   1.7.15
 */

declare(strict_types=1);

namespace Apollo\Event\Import\Cache;

if (! defined('ABSPATH')) {
    exit;
}

final class ImportRunCache
{
    private const TTL = 600;

    private const PREFIX = 'apollo_imp_';

    public static function key(string $url, string $coupon): string
    {
        return self::PREFIX . md5(strtolower(trim($url)) . '|' . strtolower(trim($coupon)));
    }

    /**
     * @param array<string,mixed> $payload
     */
    public static function set(string $url, string $coupon, array $payload): void
    {
        set_transient(self::key($url, $coupon), $payload, self::TTL);
    }

    /**
     * @return array<string,mixed>|null
     */
    public static function get(string $url, string $coupon): ?array
    {
        $hit = get_transient(self::key($url, $coupon));

        return is_array($hit) ? $hit : null;
    }

    public static function forget(string $url, string $coupon): void
    {
        delete_transient(self::key($url, $coupon));
    }
}
