<?php
/**
 * Uninstall cleanup for Swift – Quick Buy for WooCommerce.
 *
 * Runs when the plugin is deleted from the WordPress admin. Swift is stateless
 * (no custom tables, no product meta); it stores only its settings option and a
 * schema-version marker. Both are removed here so an uninstall leaves nothing
 * behind. Multisite-aware: deletes the options on every site in the network.
 *
 * @package Swift
 */

declare(strict_types=1);

defined('WP_UNINSTALL_PLUGIN') || exit;

/**
 * Delete the plugin's options on a single site.
 */
function swift_uninstall_cleanup(): void
{
    delete_option('swift_settings');
    delete_option('swift_db_version');
}

if (is_multisite()) {
    $swift_site_ids = get_sites(['fields' => 'ids', 'number' => 0]);

    foreach ($swift_site_ids as $swift_site_id) {
        switch_to_blog((int) $swift_site_id);
        swift_uninstall_cleanup();
        restore_current_blog();
    }

    unset($swift_site_ids, $swift_site_id);
} else {
    swift_uninstall_cleanup();
}
