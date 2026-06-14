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
 */
final class SwiftService implements HasHooks
{
    private const OPTION = 'swift_settings';

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
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    /**
     * Enqueue the (tiny) button stylesheet on the front end when the feature is
     * enabled and we are on a page that can show the button.
     */
    public function enqueueAssets(): void
    {
        if (! $this->isEnabled() || is_admin()) {
            return;
        }

        wp_enqueue_style(
            'swift',
            \Swift\Plugin::instance()->url('assets/css/buy-now.css'),
            [],
            \Swift\VERSION,
        );
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
