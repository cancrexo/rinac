<?php

namespace rinac\Core;

if (!defined('ABSPATH')) {
    exit;
}

final class Localization {
    public static function load_textdomain(): void {
        load_plugin_textdomain(
            Constants::TEXT_DOMAIN,
            false,
            dirname(Constants::plugin_basename()) . '/languages'
        );
    }
}

