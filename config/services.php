<?php
/**
 * Service wiring. Returns a closure that registers every service in the
 * container. Keep services thin; product logic lives in storefront-kit engines
 * instantiated here with this plugin's text-domain / option prefix / asset URLs.
 *
 * @package Swift
 */

declare(strict_types=1);

use Swift\Admin\Settings;
use Swift\Container;
use Swift\Migrator;
use Swift\Service\SwiftService;

defined('ABSPATH') || exit;

return static function (Container $c): void {
    $c->singleton(Migrator::class, static fn (): Migrator => new Migrator());

    // Thin adapter over the storefront-kit DirectCheckoutEngine.
    $c->singleton(SwiftService::class, static fn (): SwiftService => new SwiftService());

    // Admin (only needed in wp-admin context).
    if (is_admin()) {
        $c->singleton(Settings::class, static fn (): Settings => new Settings());
    }
};
