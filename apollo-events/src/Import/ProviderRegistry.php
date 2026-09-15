<?php

/**
 * Resolves URL → import provider (plug-and-play via filter).
 *
 * @package Apollo\Event
 * @since   1.7.15
 */

declare(strict_types=1);

namespace Apollo\Event\Import;

use Apollo\Event\Import\Contracts\ImportProviderInterface;

if (! defined('ABSPATH')) {
    exit;
}

final class ProviderRegistry
{
    /** @var class-string<ImportProviderInterface>[] */
    private static array $defaults = array(
        BlueTicketProvider::class,
        ShotgunProvider::class,
    );

    /**
     * @return class-string<ImportProviderInterface>[]
     */
    public static function providers(): array
    {
        $list = apply_filters('apollo/import/providers', self::$defaults);

        return array_values(
            array_filter(
                $list,
                static function ($class): bool {
                    return is_string($class)
                        && is_subclass_of($class, ImportProviderInterface::class);
                }
            )
        );
    }

    /**
     * @return class-string<ImportProviderInterface>|null
     */
    public static function resolve_class(string $url): ?string
    {
        foreach (self::providers() as $class) {
            if ($class::handles($url)) {
                return $class;
            }
        }

        return null;
    }

    /**
     * @return array<string,mixed>|\WP_Error
     */
    public static function fetch(string $url, string $coupon = ''): array|\WP_Error
    {
        $class = self::resolve_class($url);
        if (null === $class) {
            $host = (string) wp_parse_url($url, PHP_URL_HOST);

            return new \WP_Error(
                'apollo_import_unsupported',
                sprintf(
                    /* translators: %s: hostname */
                    __('Ainda não sei importar de %s. Suportado hoje: blueticket.com.br, shotgun.live.', 'apollo-events'),
                    $host
                ),
                array('status' => 422)
            );
        }

        return $class::fetch($url, $coupon);
    }
}
