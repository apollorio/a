<?php

/**
 * Collects structured import problems for preview + commit runs.
 *
 * @package Apollo\Event
 * @since   1.7.15
 */

declare(strict_types=1);

namespace Apollo\Event\Import\Diagnostics;

if (! defined('ABSPATH')) {
    exit;
}

final class ImportDiagnostics
{
    /** @var array<int,array{code:string,severity:string,message:string,context:array<string,mixed>}> */
    private array $items = array();

    /**
     * @param array<string,mixed> $context
     */
    public function add(string $code, string $severity, string $message, array $context = array()): self
    {
        $this->items[] = array(
            'code'     => $code,
            'severity' => $severity,
            'message'  => $message,
            'context'  => $context,
        );

        return $this;
    }

    public function info(string $code, string $message, array $context = array()): self
    {
        return $this->add($code, 'info', $message, $context);
    }

    public function warn(string $code, string $message, array $context = array()): self
    {
        return $this->add($code, 'warn', $message, $context);
    }

    public function error(string $code, string $message, array $context = array()): self
    {
        return $this->add($code, 'error', $message, $context);
    }

    public function has_errors(): bool
    {
        foreach ($this->items as $item) {
            if ('error' === $item['severity']) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int,array{code:string,severity:string,message:string,context:array<string,mixed>}>
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * @return array<int,array{code:string,severity:string,message:string,context:array<string,mixed>}>
     */
    public function to_array(): array
    {
        return $this->all();
    }
}
