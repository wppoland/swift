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
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        add_filter('plugin_action_links_' . plugin_basename(\Swift\PLUGIN_FILE), [$this, 'actionLinks']);
    }

    /**
     * Enqueue the settings-page stylesheet and enhancement script, but only on
     * our own screen, so we never touch the rest of wp-admin.
     */
    public function enqueueAssets(string $hookSuffix): void
    {
        if ($hookSuffix !== 'woocommerce_page_' . self::PAGE) {
            return;
        }

        wp_enqueue_style(
            'swift-admin',
            \Swift\Plugin::instance()->url('assets/css/admin.css'),
            [],
            \Swift\VERSION,
        );

        wp_enqueue_script(
            'swift-admin',
            \Swift\Plugin::instance()->url('assets/js/admin.js'),
            [],
            \Swift\VERSION,
            true,
        );
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
        $enabled  = (bool) ($settings['enabled'] ?? false);
        $label    = (string) ($settings['button_text'] ?? '');
        if ($label === '') {
            $label = __('Buy now', 'swift');
        }
        ?>
        <div class="wrap swift-admin">
            <h1>
                <?php echo esc_html(get_admin_page_title()); ?>
                <?php if ($enabled) : ?>
                    <span class="swift-status swift-status--on">
                        <span class="swift-status__dot" aria-hidden="true"></span>
                        <?php esc_html_e('Live', 'swift'); ?>
                    </span>
                <?php else : ?>
                    <span class="swift-status swift-status--off">
                        <span class="swift-status__dot" aria-hidden="true"></span>
                        <?php esc_html_e('Off', 'swift'); ?>
                    </span>
                <?php endif; ?>
            </h1>

            <p class="swift-admin__intro">
                <?php esc_html_e('Swift adds a "Buy now" button so shoppers can go straight to checkout and skip the cart — fewer clicks, more completed orders. Configure where it appears and how it behaves below; changes apply to your storefront as soon as you save.', 'swift'); ?>
            </p>

            <form method="post" action="options.php">
                <?php settings_fields(self::PAGE); ?>

                <div class="swift-card">
                    <h2><?php esc_html_e('Basics', 'swift'); ?></h2>
                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <?php esc_html_e('Enable Buy Now', 'swift'); ?>
                                    <?php $this->help('master-switch', __('The master switch. When off, Swift adds no button, scripts or styles to your storefront at all — a clean, zero-footprint disable.', 'swift')); ?>
                                </th>
                                <td>
                                    <label for="swift_enabled">
                                        <input
                                            type="checkbox"
                                            id="swift_enabled"
                                            name="<?php echo esc_attr(self::OPTION); ?>[enabled]"
                                            value="1"
                                            <?php checked($enabled, true); ?>
                                        />
                                        <?php esc_html_e('Show the Buy Now button on your store.', 'swift'); ?>
                                    </label>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="swift_button_text"><?php esc_html_e('Button label', 'swift'); ?></label>
                                    <?php $this->help('button-label', __('The wording on the button. Short, action-led labels convert best — try "Buy now", "Get it now" or "Express checkout".', 'swift')); ?>
                                </th>
                                <td>
                                    <input
                                        type="text"
                                        id="swift_button_text"
                                        name="<?php echo esc_attr(self::OPTION); ?>[button_text]"
                                        value="<?php echo esc_attr((string) ($settings['button_text'] ?? '')); ?>"
                                        class="regular-text"
                                        maxlength="60"
                                    />
                                    <p class="description"><?php esc_html_e('Text shown on the Buy Now button. Leave empty to use the default ("Buy now").', 'swift'); ?></p>
                                </td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="swift-preview" aria-hidden="true">
                        <span class="swift-preview__label"><?php esc_html_e('Live preview:', 'swift'); ?></span>
                        <span class="swift-preview__btn swift-preview__btn--<?php echo esc_attr($style); ?>"><?php echo esc_html($label); ?></span>
                    </div>
                </div>

                <div class="swift-card">
                    <h2><?php esc_html_e('Placement', 'swift'); ?></h2>
                    <p class="swift-card__hint"><?php esc_html_e('Choose where the button shows up across your store.', 'swift'); ?></p>
                    <table class="form-table" role="presentation">
                        <tbody>
                            <?php
                            $this->checkboxRow('show_on_single', __('Single product page', 'swift'), __('Show the button on single product pages.', 'swift'), $settings, __('The product detail page. This is the highest-intent spot — the shopper is already looking at the item.', 'swift'));
                            $this->checkboxRow('show_on_loop', __('Shop & archive loops', 'swift'), __('Show the button on shop and archive product loops (simple products only).', 'swift'), $settings, __('Product grids on the shop and category pages. Lets shoppers buy without opening each product. Simple products only; variable products need a chosen variation.', 'swift'));
                            ?>
                            <tr>
                                <th scope="row">
                                    <?php esc_html_e('Position on single product', 'swift'); ?>
                                    <?php $this->help('single-position', __('Whether Swift\'s button sits above or below WooCommerce\'s own "Add to cart" button on the product page.', 'swift')); ?>
                                </th>
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
                                    <p class="description">
                                        <?php esc_html_e('Tip: you can also drop the button anywhere with the', 'swift'); ?>
                                        <code>[swift_buy_now]</code>
                                        <?php esc_html_e('shortcode — add id="123" to target a specific product.', 'swift'); ?>
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="swift-card">
                    <h2><?php esc_html_e('Behaviour', 'swift'); ?></h2>
                    <p class="swift-card__hint"><?php esc_html_e('What happens when a shopper clicks Buy now.', 'swift'); ?></p>
                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <?php esc_html_e('Redirect to', 'swift'); ?>
                                    <?php $this->help('redirect-target', __('Where the shopper lands after clicking. "Checkout" is the fast path that skips the cart entirely; "Cart" sends them to the cart page to review first.', 'swift')); ?>
                                </th>
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
                            $this->checkboxRow('clear_cart', __('Empty the cart first', 'swift'), __('Clear the cart before adding, so the buyer checks out with only the chosen product.', 'swift'), $settings, __('Recommended for a true "buy just this" flow. If a shopper already has items in their cart, this removes them so the Buy now purchase stands alone. Turn off to add to the existing cart instead.', 'swift'));
                            $this->checkboxRow('respect_quantity', __('Respect quantity', 'swift'), __('Carry the quantity chosen on the product page into the Buy Now purchase (single, simple products).', 'swift'), $settings, __('When on, the quantity the shopper picks in the product\'s quantity box is used for the Buy now purchase. When off, exactly one unit is bought. Single, simple products only.', 'swift'));
                            ?>
                        </tbody>
                    </table>
                </div>

                <div class="swift-card">
                    <h2><?php esc_html_e('Appearance', 'swift'); ?></h2>
                    <p class="swift-card__hint"><?php esc_html_e('How the button looks. Use the live preview above to check your choices.', 'swift'); ?></p>
                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <?php esc_html_e('Button style', 'swift'); ?>
                                    <?php $this->help('button-style', __('"Theme default" inherits your theme\'s button look (safest, blends in). "Solid" and "Outline" use the accent colour below so the button stands out from the standard add-to-cart button.', 'swift')); ?>
                                </th>
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
                                    <?php $this->help('accent-colour', __('A hex colour used by the Solid and Outline styles. Leave empty to use Swift\'s velocity violet (#5b3df5). Pick a high-contrast brand colour so the button is easy to see and tap. Ignored by the "Theme default" style.', 'swift')); ?>
                                </th>
                                <td>
                                    <input
                                        type="text"
                                        id="swift_accent_color"
                                        name="<?php echo esc_attr(self::OPTION); ?>[accent_color]"
                                        value="<?php echo esc_attr((string) ($settings['accent_color'] ?? '')); ?>"
                                        class="regular-text"
                                        inputmode="text"
                                        pattern="#?([A-Fa-f0-9]{3}|[A-Fa-f0-9]{6})"
                                        placeholder="#5b3df5"
                                    />
                                    <p class="description"><?php esc_html_e('Hex colour for the Solid / Outline styles. Leave empty for Swift\'s velocity violet (#5b3df5).', 'swift'); ?></p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render an accessible "?" help affordance plus its tooltip surface.
     *
     * The button is keyboard-focusable and points at the tip via
     * `aria-describedby`. The admin script promotes the tip to a native popover
     * where supported and falls back to inline help (still announced via
     * aria-describedby) where it is not — so the help is available to everyone
     * regardless of JS or browser support.
     */
    private function help(string $key, string $text): void
    {
        $id = 'swift-tip-' . sanitize_html_class($key);
        ?>
        <button
            type="button"
            class="swift-help"
            aria-describedby="<?php echo esc_attr($id); ?>"
            aria-label="<?php esc_attr_e('Help', 'swift'); ?>"
        >?</button>
        <span class="swift-tip" id="<?php echo esc_attr($id); ?>" role="tooltip"><?php echo esc_html($text); ?></span>
        <?php
    }

    /**
     * Render a single checkbox row in the form-table.
     *
     * @param array<string, mixed> $settings
     * @param string               $tip Optional extended help shown via the "?" affordance.
     */
    private function checkboxRow(string $key, string $label, string $help, array $settings, string $tip = ''): void
    {
        $id = 'swift_' . $key;
        ?>
        <tr>
            <th scope="row">
                <?php echo esc_html($label); ?>
                <?php
                if ($tip !== '') {
                    $this->help($key, $tip);
                }
                ?>
            </th>
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
