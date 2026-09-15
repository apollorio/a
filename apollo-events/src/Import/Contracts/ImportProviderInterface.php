<?php

/**
 * Contract for URL import providers (BlueTicket, Shotgun, …).
 *
 * @package Apollo\Event
 * @since   1.7.15
 */

declare(strict_types=1);

namespace Apollo\Event\Import\Contracts;

if (! defined('ABSPATH')) {
    exit;
}

interface ImportProviderInterface
{
    /**
     * Provider slug stored in `_event_import_provider`.
     */
    public static function provider(): string;

    /**
     * Does this provider own the given URL?
     */
    public static function handles(string $url): bool;

    /**
     * Fetch + normalise one event from a public URL.
     *
     * @return array<string,mixed>|\WP_Error Normalised Apollo import payload.
     */
    public static function fetch(string $url, string $coupon = ''): array|\WP_Error;
}
