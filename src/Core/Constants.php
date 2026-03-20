<?php

namespace rinac\Core;

if (!defined('ABSPATH')) {
    exit;
}

final class Constants {
    public const VERSION = '0.1.0';
    public const TEXT_DOMAIN = 'rinac';
    public const PREFIX = 'rinac_';

    public static function plugin_basename(): string {
        return plugin_basename(dirname(__DIR__, 2) . '/plugin.php');
    }
}

