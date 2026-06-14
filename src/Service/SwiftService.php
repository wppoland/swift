<?php

declare(strict_types=1);

namespace Swift\Service;

use Swift\Contract\HasHooks;
use WPPoland\StorefrontKit\Checkout\DirectCheckoutEngine;

defined('ABSPATH') || exit;

/**
 * Thin adapter over the storefront-kit {@see DirectCheckoutEngine}.
 *
 * Injects this plugin's text-domain ('swift'), option prefix ('swift_'),
 * request key, nonce action and labels into the namespace-neutral engine, and
 * supplies the closures it needs: one to report whether the feature is enabled,
 * one to resolve the merchant settings, and one to render the packaged button
 * template. All direct-checkout orchestration (button hooks, nonce, cart
 * handling, redirect) lives in the kit; this class only supplies localisation,
 * option storage and the button markup. The engine is stateless — no DB.
 *
 * On top of the engine this adapter adds FREE-only presentation controls that
 * stay plugin-local (no kit changes): single-product placement (before/after
 * the add-to-cart button), a `[swift_buy_now]` shortcode, button style/accent
 * options and an opt-in "respect the on-page quantity" behaviour driven by a
 * tiny vanilla script. None of this touches variable products, sticky bars or
 * per-product rules, which remain the Swift Pro upsell.
 */
final class SwiftService implements HasHooks
{
    private const OPTION = 'swift_settings';

    private const HANDLE = 'swift';

    private ?DirectCheckoutEngine $engine = null;

    public function __construct()
    {
        // The engine ships with storefront-kit >= 1.5.0. When present, wire it
        // with this plugin's text-domain / option prefix / request key.
        // Otherwise leave the service inert (see registerHooks()).
        if (! class_exists(DirectCheckoutEngine::class)) {
            return;
        }

        $this->engine = new DirectCheckoutEngine(
            requestKey: 'swift_buy_now',
            nonceAction: 'swift_buy_now',
            buttonTemplate: 'buy-now-button',
            labels: [
                'button'     => __('Buy now', 'swift'),
                'add_failed' => __('Sorry, this product could not be added to your cart.', 'swift'),
            ],
            isEnabled: fn (): bool => $this->isEnabled(),
            settings: fn (): array => $this->settings(),
            renderTemplate: function (string $template, array $context): void {
                $this->renderTemplate($template, $context);
            },
        );
    }

    public function registerHooks(): void
    {
        if (! $this->engine instanceof DirectCheckoutEngine) {
            // storefront-kit < 1.5.0 has no DirectCheckoutEngine. Bump the
            // `wppoland/storefront-kit` constraint (composer update) to enable
            // the Buy Now button. No hooks are registered until present.
            return;
        }

        $this->engine->registerHooks();

        // Honour the single-product placement option. The engine wires its
        // single button to `woocommerce_after_add_to_cart_button` at priority
        // 15; when the merchant prefers it above the add-to-cart button we move
        // that exact callable to the matching `before` hook. Uses only the
        // engine's public API — the kit is not modified.
        if ($this->settings()['single_position'] === 'before') {
            remove_action('woocommerce_after_add_to_cart_button', [$this->engine, 'renderSingleButton'], 15);
            add_action('woocommerce_before_add_to_cart_button', [$this->engine, 'renderSingleButton'], 15);
        }

        add_shortcode('swift_buy_now', [$this, 'shortcode']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    /**
     * Render the Buy Now button for the current (or a given) product via the
     * `[swift_buy_now]` shortcode, e.g. `[swift_buy_now id="42"]`.
     *
     * Defers all markup to the engine so the shortcode button is identical to
     * the hooked one. Variable products are deferred to Swift Pro and render
     * nothing here.
     *
     * @param array<string, mixed>|string $atts
     */
    public function shortcode(array|string $atts): string
    {
        if (! $this->engine instanceof DirectCheckoutEngine || ! $this->isEnabled()) {
            return '';
        }

        $atts = shortcode_atts(['id' => 0], is_array($atts) ? $atts : [], 'swift_buy_now');

        $productId = absint($atts['id']);

        if ($productId === 0) {
            $current = get_the_ID();
            $productId = $current !== false ? (int) $current : 0;
        }

        $product = $productId > 0 ? wc_get_product($productId) : null;

        if (! $product instanceof \WC_Product || ! $product->is_purchasable() || ! $product->is_in_stock()) {
            return '';
        }

        // Variable products require a chosen variation (a Swift Pro feature).
        if ($product->is_type('variable')) {
            return '';
        }

        ob_start();
        $this->renderTemplate('buy-now-button', [
            'product'     => $product,
            'context'     => 'shortcode',
            'settings'    => $this->settings(),
            'button'      => $this->engine->getButtonData($product),
            'request_key' => 'swift_buy_now',
        ]);

        return (string) ob_get_clean();
    }

    /**
     * Enqueue the (tiny) button stylesheet on the front end when the feature is
     * enabled and we are on a page that can show the button. When the merchant
     * has opted to respect the on-page quantity, also enqueue a small vanilla
     * script that mirrors the quantity input into the Buy Now form on single
     * product pages, and pass the chosen accent colour as a CSS variable.
     */
    public function enqueueAssets(): void
    {
        if (! $this->isEnabled() || is_admin()) {
            return;
        }

        wp_enqueue_style(
            self::HANDLE,
            \Swift\Plugin::instance()->url('assets/css/buy-now.css'),
            [],
            \Swift\VERSION,
        );

        $settings = $this->settings();

        $accent = (string) ($settings['accent_color'] ?? '');
        if ($accent !== '') {
            $css = ':root{--swift-buy-now-accent:' . $accent . ';}';
            wp_add_inline_style(self::HANDLE, $css);
        }

        if (! empty($settings['respect_quantity'])) {
            wp_enqueue_script(
                self::HANDLE,
                \Swift\Plugin::instance()->url('assets/js/buy-now.js'),
                [],
                \Swift\VERSION,
                true,
            );
        }
    }

    private function isEnabled(): bool
    {
        return (bool) ($this->settings()['enabled'] ?? false);
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

    /**
     * @param array<string, mixed> $context
     */
    private function renderTemplate(string $template, array $context): void
    {
        $file = SWIFT_DIR . 'templates/' . $template . '.php';

        if (! is_readable($file)) {
            return;
        }

        extract($context, EXTR_SKIP);
        require $file;
    }
}
