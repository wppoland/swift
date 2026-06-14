<?php
/**
 * Constants needed by PHPStan to analyse the plugin without bootstrapping WordPress.
 *
 * @package Swift
 */

declare(strict_types=1);

namespace {
    if (! defined('ABSPATH')) {
        define('ABSPATH', '/tmp/wordpress/');
    }
    if (! defined('SWIFT_DIR')) {
        define('SWIFT_DIR', '/tmp/swift/');
    }
    if (! defined('SWIFT_URL')) {
        define('SWIFT_URL', 'https://example.test/wp-content/plugins/swift/');
    }
    if (! defined('WP_UNINSTALL_PLUGIN')) {
        define('WP_UNINSTALL_PLUGIN', 'swift/swift.php');
    }
}

namespace Swift {
    if (! defined('Swift\\VERSION')) {
        define('Swift\\VERSION', '0.2.0');
    }
    if (! defined('Swift\\PLUGIN_FILE')) {
        define('Swift\\PLUGIN_FILE', '/tmp/swift/swift.php');
    }
}
