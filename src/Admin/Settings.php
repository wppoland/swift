<?php

declare(strict_types=1);

namespace Swift\Admin;

defined('ABSPATH') || exit;

use Swift\Contract\HasHooks;

/**
 * Admin settings page registered under the WooCommerce menu.
 *
 * Stores settings in the `swift_settings` option (array): the master toggle, the
 * button label, where the button appears (single product / shop loop) and where
 * it redirects (checkout / cart). All output is escaped; all input is sanitised
 * on save.
 */
final class Settings implements HasHooks
{
    private const OPTION = 'swift_settings';
    private const PAGE   = 'swift-settings';

    public function registerHooks(): void
    {
        add_action('admin_menu', [$this, 'addMenuPage']);
        add_action('admin_init', [$this, 'registerSettings']);
    }

    public function addMenuPage(): void
    {
        add_submenu_page(
            'woocommerce',
            __('Swift – Quick Buy', 'swift'),
            __('Swift Quick Buy', 'swift'),
            'manage_woocommerce',
            self::PAGE,
            [$this, 'renderPage'],
        );
    }

    public function registerSettings(): void
    {
        register_setting(
            self::PAGE,
            self::OPTION,
            [
                'type'              => 'array',
                'sanitize_callback' => [$this, 'sanitize'],
            ],
        );

        // The menu uses manage_woocommerce; align the options.php save capability
        // so shop managers (not just admins with manage_options) can save.
        add_filter(
            'option_page_capability_' . self::PAGE,
            static fn (): string => 'manage_woocommerce',
        );
    }

    public function renderPage(): void
    {
        if (! current_user_can('manage_woocommerce')) {
            return;
        }

        $settings = $this->settings();
        $target   = (string) ($settings['redirect_target'] ?? 'checkout');
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields(self::PAGE); ?>

                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row"><?php esc_html_e('Enable Buy Now', 'swift'); ?></th>
                            <td>
                                <label for="swift_enabled">
                                    <input
                                        type="checkbox"
                                        id="swift_enabled"
                                        name="<?php echo esc_attr(self::OPTION); ?>[enabled]"
                                        value="1"
                                        <?php checked((bool) ($settings['enabled'] ?? false), true); ?>
                                    />
                                    <?php esc_html_e('Show the Buy Now button on your store.', 'swift'); ?>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="swift_button_text"><?php esc_html_e('Button label', 'swift'); ?></label>
                            </th>
                            <td>
                                <input
                                    type="text"
                                    id="swift_button_text"
                                    name="<?php echo esc_attr(self::OPTION); ?>[button_text]"
                                    value="<?php echo esc_attr((string) ($settings['button_text'] ?? '')); ?>"
                                    class="regular-text"
                                />
                                <p class="description"><?php esc_html_e('Text shown on the Buy Now button.', 'swift'); ?></p>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <h2><?php esc_html_e('Placement', 'swift'); ?></h2>
                <table class="form-table" role="presentation">
                    <tbody>
                        <?php
                        $this->checkboxRow('show_on_single', __('Single product page', 'swift'), __('Show the button on single product pages.', 'swift'), $settings);
                        $this->checkboxRow('show_on_loop', __('Shop &amp; archive loops', 'swift'), __('Show the button on shop and archive product loops (simple products only).', 'swift'), $settings);
                        ?>
                    </tbody>
                </table>

                <h2><?php esc_html_e('Behaviour', 'swift'); ?></h2>
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row"><?php esc_html_e('Redirect to', 'swift'); ?></th>
                            <td>
                                <fieldset>
                                    <legend class="screen-reader-text"><?php esc_html_e('Redirect to', 'swift'); ?></legend>
                                    <label for="swift_target_checkout">
                                        <input
                                            type="radio"
                                            id="swift_target_checkout"
                                            name="<?php echo esc_attr(self::OPTION); ?>[redirect_target]"
                                            value="checkout"
                                            <?php checked($target, 'checkout'); ?>
                                        />
                                        <?php esc_html_e('Checkout (skip the cart)', 'swift'); ?>
                                    </label>
                                    <br />
                                    <label for="swift_target_cart">
                                        <input
                                            type="radio"
                                            id="swift_target_cart"
                                            name="<?php echo esc_attr(self::OPTION); ?>[redirect_target]"
                                            value="cart"
                                            <?php checked($target, 'cart'); ?>
                                        />
                                        <?php esc_html_e('Cart', 'swift'); ?>
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                        <?php
                        $this->checkboxRow('clear_cart', __('Empty the cart first', 'swift'), __('Clear the cart before adding, so the buyer checks out with only the chosen product.', 'swift'), $settings);
                        ?>
                    </tbody>
                </table>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render a single checkbox row in the form-table.
     *
     * @param array<string, mixed> $settings
     */
    private function checkboxRow(string $key, string $label, string $help, array $settings): void
    {
        $id = 'swift_' . $key;
        ?>
        <tr>
            <th scope="row"><?php echo esc_html($label); ?></th>
            <td>
                <label for="<?php echo esc_attr($id); ?>">
                    <input
                        type="checkbox"
                        id="<?php echo esc_attr($id); ?>"
                        name="<?php echo esc_attr(self::OPTION); ?>[<?php echo esc_attr($key); ?>]"
                        value="1"
                        <?php checked((bool) ($settings[$key] ?? false), true); ?>
                    />
                    <?php echo esc_html($help); ?>
                </label>
            </td>
        </tr>
        <?php
    }

    /**
     * Sanitises the submitted settings before save, preserving defaults for any
     * field not on the form.
     *
     * @param mixed $raw
     * @return array<string, mixed>
     */
    public function sanitize(mixed $raw): array
    {
        if (! is_array($raw)) {
            $raw = [];
        }

        $defaults = $this->settings();

        $buttonText = isset($raw['button_text']) ? sanitize_text_field((string) $raw['button_text']) : '';
        $target     = isset($raw['redirect_target']) ? sanitize_key((string) $raw['redirect_target']) : 'checkout';

        if (! in_array($target, ['checkout', 'cart'], true)) {
            $target = 'checkout';
        }

        return array_merge($defaults, [
            'enabled'         => ! empty($raw['enabled']),
            'button_text'     => $buttonText !== '' ? $buttonText : (string) ($defaults['button_text'] ?? __('Buy now', 'swift')),
            'show_on_single'  => ! empty($raw['show_on_single']),
            'show_on_loop'    => ! empty($raw['show_on_loop']),
            'redirect_target' => $target,
            'clear_cart'      => ! empty($raw['clear_cart']),
        ]);
    }

    /**
     * Stored settings merged over packaged defaults.
     *
     * @return array<string, mixed>
     */
    private function settings(): array
    {
        $stored = get_option(self::OPTION, []);

        if (! is_array($stored)) {
            $stored = [];
        }

        /** @var array<string, mixed> $defaults */
        $defaults = require SWIFT_DIR . 'config/defaults.php';

        return array_merge($defaults, $stored);
    }
}
