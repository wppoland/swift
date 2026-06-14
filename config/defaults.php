<?php
/**
 * Default settings, merged under the option key `swift_settings`.
 *
 * The feature ships enabled. The merchant tunes the button label, where it
 * appears (single product and/or shop loop) and where it redirects (checkout or
 * cart) from the Swift admin screen. All product logic lives in the
 * storefront-kit DirectCheckoutEngine; these values are passed through to it as
 * the resolved settings.
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

    // Where the buyer lands: `checkout` or `cart`.
    'redirect_target' => 'checkout',

    // Empty the cart before adding (single-item buy-now) so checkout shows only
    // the chosen product.
    'clear_cart' => true,

    // Front-end fallback string used when add-to-cart fails.
    'add_failed_text' => 'Sorry, this product could not be added to your cart.',
];
