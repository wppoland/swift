<?php
/**
 * Default settings, merged under the option key `swift_settings`.
 *
 * The feature ships enabled. The merchant tunes the button label, where it
 * appears (single product and/or shop loop), where it sits relative to the
 * native add-to-cart button, where it redirects (checkout or cart), its visual
 * style and whether the on-page quantity is carried into the purchase. All
 * product/cart logic lives in the storefront-kit DirectCheckoutEngine; these
 * values are passed through to it as the resolved settings and read by the
 * bundled button template.
 *
 * @package Swift
 *
 * @return array<string, mixed>
 */

declare(strict_types=1);

defined('ABSPATH') || exit;

return [
    'enabled' => true,

    // Button label.
    'button_text' => 'Buy now',

    // Where the button appears.
    'show_on_single' => true,
    'show_on_loop'   => false,

    // Single-product placement relative to the native add-to-cart button:
    // `after` (default) or `before`.
    'single_position' => 'after',

    // Where the buyer lands: `checkout` or `cart`.
    'redirect_target' => 'checkout',

    // Empty the cart before adding (single-item buy-now) so checkout shows only
    // the chosen product.
    'clear_cart' => true,

    // Carry the on-page quantity selector value into the Buy Now purchase on
    // single product pages (simple products). Off keeps a quantity of 1.
    'respect_quantity' => false,

    // Visual style for the button: `theme` (inherit), `solid` or `outline`.
    'button_style' => 'theme',

    // Optional accent colour (hex) applied to the `solid`/`outline` styles.
    // Empty string means "use the theme colour".
    'accent_color' => '',

    // Front-end fallback string used when add-to-cart fails.
    'add_failed_text' => 'Sorry, this product could not be added to your cart.',
];
