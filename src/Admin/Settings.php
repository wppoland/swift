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
        add_filter('plugin_action_links_' . plugin_basename(\Swift\PLUGIN_FILE), [$this, 'actionLinks']);
    }

    /**
     * Add a "Settings" link to the plugin's row on the Plugins screen.
     *
     * @param array<int|string, string> $links
     * @return array<int|string, string>
     */
    public function actionLinks(array $links): array
    {
        $url = admin_url('admin.php?page=' . self::PAGE);

        $settingsLink = sprintf(
            '<a href="%s">%s</a>',
            esc_url($url),
            esc_html__('Settings', 'swift'),
        );

        array_unshift($links, $settingsLink);

        return $links;
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
        $position = (string) ($settings['single_position'] ?? 'after');
        $style    = (string) ($settings['button_style'] ?? 'theme');
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
                        <tr>
                            <th scope="row"><?php esc_html_e('Position on single product', 'swift'); ?></th>
                            <td>
                                <fieldset>
                                    <legend class="screen-reader-text"><?php esc_html_e('Position on single product', 'swift'); ?></legend>
                                    <label for="swift_position_after">
                                        <input
                                            type="radio"
                                            id="swift_position_after"
                                            name="<?php echo esc_attr(self::OPTION); ?>[single_position]"
                                            value="after"
                                            <?php checked($position, 'after'); ?>
                                        />
                                        <?php esc_html_e('After the add-to-cart button', 'swift'); ?>
                                    </label>
                                    <br />
                                    <label for="swift_position_before">
                                        <input
                                            type="radio"
                                            id="swift_position_before"
                                            name="<?php echo esc_attr(self::OPTION); ?>[single_position]"
                                            value="before"
                                            <?php checked($position, 'before'); ?>
                                        />
                                        <?php esc_html_e('Before the add-to-cart button', 'swift'); ?>
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <p class="description">
                    <?php esc_html_e('You can also place the button anywhere with the', 'swift'); ?>
                    <code>[swift_buy_now]</code>
                    <?php esc_html_e('shortcode (add id="123" to target a specific product).', 'swift'); ?>
                </p>

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
                        $this->checkboxRow('respect_quantity', __('Respect quantity', 'swift'), __('Carry the quantity chosen on the product page into the Buy Now purchase (single, simple products).', 'swift'), $settings);
                        ?>
                    </tbody>
                </table>

                <h2><?php esc_html_e('Appearance', 'swift'); ?></h2>
                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row"><?php esc_html_e('Button style', 'swift'); ?></th>
                            <td>
                                <fieldset>
                                    <legend class="screen-reader-text"><?php esc_html_e('Button style', 'swift'); ?></legend>
                                    <label for="swift_style_theme">
                                        <input
                                            type="radio"
                                            id="swift_style_theme"
                                            name="<?php echo esc_attr(self::OPTION); ?>[button_style]"
                                            value="theme"
                                            <?php checked($style, 'theme'); ?>
                                        />
                                        <?php esc_html_e('Theme default', 'swift'); ?>
                                    </label>
                                    <br />
                                    <label for="swift_style_solid">
                                        <input
                                            type="radio"
                                            id="swift_style_solid"
                                            name="<?php echo esc_attr(self::OPTION); ?>[button_style]"
                                            value="solid"
                                            <?php checked($style, 'solid'); ?>
                                        />
                                        <?php esc_html_e('Solid (accent colour)', 'swift'); ?>
                                    </label>
                                    <br />
                                    <label for="swift_style_outline">
                                        <input
                                            type="radio"
                                            id="swift_style_outline"
                                            name="<?php echo esc_attr(self::OPTION); ?>[button_style]"
                                            value="outline"
                                            <?php checked($style, 'outline'); ?>
                                        />
                                        <?php esc_html_e('Outline (accent colour)', 'swift'); ?>
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="swift_accent_color"><?php esc_html_e('Accent colour', 'swift'); ?></label>
                            </th>
                            <td>
                                <input
                                    type="text"
                                    id="swift_accent_color"
                                    name="<?php echo esc_attr(self::OPTION); ?>[accent_color]"
                                    value="<?php echo esc_attr((string) ($settings['accent_color'] ?? '')); ?>"
                                    class="regular-text"
                                    placeholder="#2271b1"
                                />
                                <p class="description"><?php esc_html_e('Hex colour for the Solid / Outline styles (e.g. #2271b1). Leave empty to use the theme colour.', 'swift'); ?></p>
                            </td>
                        </tr>
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
        $position   = isset($raw['single_position']) ? sanitize_key((string) $raw['single_position']) : 'after';
        $style      = isset($raw['button_style']) ? sanitize_key((string) $raw['button_style']) : 'theme';
        $accent     = isset($raw['accent_color']) ? sanitize_hex_color((string) $raw['accent_color']) : '';

        if (! in_array($target, ['checkout', 'cart'], true)) {
            $target = 'checkout';
        }

        if (! in_array($position, ['after', 'before'], true)) {
            $position = 'after';
        }

        if (! in_array($style, ['theme', 'solid', 'outline'], true)) {
            $style = 'theme';
        }

        return array_merge($defaults, [
            'enabled'          => ! empty($raw['enabled']),
            'button_text'      => $buttonText !== '' ? $buttonText : (string) ($defaults['button_text'] ?? __('Buy now', 'swift')),
            'show_on_single'   => ! empty($raw['show_on_single']),
            'show_on_loop'     => ! empty($raw['show_on_loop']),
            'single_position'  => $position,
            'redirect_target'  => $target,
            'clear_cart'       => ! empty($raw['clear_cart']),
            'respect_quantity' => ! empty($raw['respect_quantity']),
            'button_style'     => $style,
            'accent_color'     => is_string($accent) ? $accent : '',
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
