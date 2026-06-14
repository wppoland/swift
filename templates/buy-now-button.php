<?php
/**
 * Buy Now button.
 *
 * Rendered by the storefront-kit DirectCheckoutEngine on
 * `woocommerce_after_add_to_cart_button` (single product) and
 * `woocommerce_after_shop_loop_item` (shop loop), and by the
 * `[swift_buy_now]` shortcode.
 *
 * A plain GET form posts the product id under `$request_key` plus the nonce to
 * the product permalink; the engine intercepts it on `template_redirect`, adds
 * the product to the cart and redirects to the configured target. A hidden
 * `quantity` field (default 1) is included so the optional "respect quantity"
 * script can mirror the on-page quantity selector into the purchase on single
 * product pages.
 *
 * @var \WC_Product          $product
 * @var string               $context     'single', 'loop' or 'shortcode'.
 * @var array<string, mixed> $settings
 * @var array{product_id:int, action_url:string, nonce_field:string, label:string} $button
 * @var string               $request_key
 *
 * @package Swift/Templates
 */

declare(strict_types=1);

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template-scope variables are injected by the engine and confined to this file.

defined('ABSPATH') || exit;

$swift_label = isset($button['label']) && (string) $button['label'] !== ''
    ? (string) $button['label']
    : __('Buy now', 'swift');

$swift_style = isset($settings['button_style']) ? (string) $settings['button_style'] : 'theme';
if (! in_array($swift_style, ['theme', 'solid', 'outline'], true)) {
    $swift_style = 'theme';
}

$swift_respect_qty = ! empty($settings['respect_quantity']) && (string) $context === 'single';

$swift_button_classes = 'button swift-buy-now-button swift-buy-now-button--' . $swift_style;
?>
<form class="swift-buy-now swift-buy-now--<?php echo esc_attr((string) $context); ?>" method="get" action="<?php echo esc_url((string) ($button['action_url'] ?? '')); ?>"<?php echo $swift_respect_qty ? ' data-swift-respect-qty' : ''; ?>>
    <input type="hidden" name="<?php echo esc_attr((string) $request_key); ?>" value="<?php echo esc_attr((string) ($button['product_id'] ?? 0)); ?>" />
    <input type="hidden" name="_wpnonce" value="<?php echo esc_attr((string) ($button['nonce_field'] ?? '')); ?>" />
    <input type="hidden" name="quantity" value="1" data-swift-quantity />
    <button type="submit" class="<?php echo esc_attr($swift_button_classes); ?>">
        <?php echo esc_html($swift_label); ?>
    </button>
</form>
<?php
// phpcs:enable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
