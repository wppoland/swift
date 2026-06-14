=== Swift – Quick Buy for WooCommerce ===
Contributors: wppoland
Tags: woocommerce, buy now, direct checkout, skip cart, one click
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 0.1.0
Requires Plugins: woocommerce
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add a Buy Now button that takes shoppers straight to checkout, skipping the cart.

== Description ==

Swift adds a "Buy Now" button to your WooCommerce products. One click adds the product to the cart and sends the shopper straight to checkout — skipping the cart page entirely, so there is less friction between "I want this" and "I paid".

The button can appear on single product pages, on shop and archive loops, or both. You choose the label, where it redirects (checkout or cart), and whether the cart is emptied first so the buyer checks out with only the product they clicked.

Swift is **stateless** — it stores no per-product data and creates no database tables. It is a thin adapter over the shared, namespace-neutral `wppoland/storefront-kit` direct-checkout engine, which handles the button hooks, nonce verification, cart handling and redirect.

= What it does =

* Adds a "Buy Now" button that adds-to-cart and redirects in one click.
* Works on single product pages and/or shop and archive loops.
* Redirects to the **checkout** (skip the cart) or to the **cart** — your choice.
* Optionally empties the cart first so checkout shows only the chosen product.
* Honours stock and purchasability — the button is hidden for out-of-stock or non-purchasable products, and is not shown for variable products in loops.

= Settings =

A simple WooCommerce settings page (WooCommerce → Swift Quick Buy) lets you:

* Enable or disable the Buy Now button.
* Set the button label.
* Choose where the button appears (single product, shop loops, or both).
* Choose where it redirects (checkout or cart).
* Choose whether to empty the cart before adding.

= Engine =

The direct-checkout orchestration (button markup hooks, nonce, cart handling, redirect) is provided by the shared, namespace-neutral `wppoland/storefront-kit` DirectCheckout engine; this plugin is a thin adapter that supplies the text domain, options and button markup.

== Installation ==

1. Upload the plugin to `/wp-content/plugins/swift`, or install via Plugins → Add New.
2. Activate it. WooCommerce must be active.
3. Visit **WooCommerce → Swift Quick Buy** to configure the button label, placement and redirect target.

== Frequently Asked Questions ==

= Does it require WooCommerce? =

Yes. Swift requires an active WooCommerce installation.

= Where does the Buy Now button appear? =

On single product pages and/or shop and archive product loops — you choose in the settings. On loops it is shown for simple, in-stock, purchasable products.

= Does it skip the cart? =

Yes, if you set the redirect target to "Checkout". The product is added to the cart and the shopper is taken straight to the checkout page. You can also choose to redirect to the cart instead.

= Does it create database tables? =

No. Swift is stateless — it stores only its settings (one option) and creates no custom tables or product meta.

= Does it work with variable products? =

The free version is designed for simple products. On shop loops the button is shown for simple products only, since a variation must be chosen first. Full Buy Now support for variable products (with an inline variation picker) is planned for Swift Pro.

== Screenshots ==

1. The Buy Now button on a single product page.
2. The Swift Quick Buy settings screen.

== Changelog ==

= 0.1.0 =
* Initial release: a Buy Now button for WooCommerce that adds to cart and redirects straight to checkout (or cart), with a settings page for the label, placement and redirect target.
