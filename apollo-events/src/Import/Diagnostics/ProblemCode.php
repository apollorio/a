<?php

/**
 * Structured problem codes for import diagnostics.
 *
 * @package Apollo\Event
 * @since   1.7.15
 */

declare(strict_types=1);

namespace Apollo\Event\Import\Diagnostics;

if (! defined('ABSPATH')) {
    exit;
}

final class ProblemCode
{
    public const NO_TITLE       = 'NO_TITLE';
    public const NO_DATE        = 'NO_DATE';
    public const NO_COVER       = 'NO_COVER';
    public const COVER_NOT_IMAGE = 'COVER_NOT_IMAGE';
    public const NO_VENUE       = 'NO_VENUE';
    public const LOC_MISSING    = 'LOC_MISSING';
    public const SIDELOAD_FAILED = 'SIDELOAD_FAILED';
    public const BANNER_DESYNC  = 'BANNER_DESYNC';
    public const PROVIDER_ERROR = 'PROVIDER_ERROR';
    public const UNSUPPORTED    = 'UNSUPPORTED';
    public const INFO           = 'INFO';
    public const WARN           = 'WARN';
}
