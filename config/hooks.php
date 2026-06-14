<?php
/**
 * Boot order: services listed here are resolved from the container and have
 * their registerHooks() called during Plugin::boot(). Each must implement
 * Swift\Contract\HasHooks.
 *
 * @package Swift
 *
 * @return array<class-string>
 */

declare(strict_types=1);

use Swift\Admin\Settings;
use Swift\Service\SwiftService;

defined('ABSPATH') || exit;

return [
    SwiftService::class,
    ...(is_admin() ? [Settings::class] : []),
];
