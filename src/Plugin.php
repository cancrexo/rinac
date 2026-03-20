<?php

declare(strict_types=1);

namespace Rinac;

final class Plugin
{
    public static function instance(): self
    {
        static $instance = null;

        if (null === $instance) {
            $instance = new self();
        }

        return $instance;
    }

    private function __construct()
    {
    }
}

