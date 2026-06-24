=== Swift – Quick Buy for WooCommerce ===
Contributors: motylanogha
Tags: woocommerce, buy now, direct checkout, skip cart, quick buy
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 0.2.0
Requires Plugins: woocommerce
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Add a Buy Now / quick buy button that takes shoppers straight to checkout, skipping the cart.

== Description ==

Swift adds a "Buy Now" quick buy button to your WooCommerce products. One click adds the product to the cart and sends the shopper straight to checkout, skipping the cart page so a purchase takes one click instead of three.

The button can appear on single product pages, on shop and archive loops, or both. You choose the label, where it redirects (checkout or cart), and whether the cart is emptied first so the buyer checks out with only the product they clicked.

Swift is stateless: it stores no per-product data and creates no database tables. It handles the button hooks, nonce verification, cart handling and redirect, and nothing else.

Swift is developed in the open. Source code, bug reports and feature requests live at https://github.com/wppoland/swift.

= Documentation and links =

* **Documentation** - https://plogins.com/swift/docs/
* **Plugin page** - https://plogins.com/swift/
* **Source code** - https://github.com/wppoland/swift
* **Bug reports and feature requests** - https://github.com/wppoland/swift/issues
* **Discussions and questions** - https://github.com/wppoland/swift/discussions


= What it does =

* Adds a "Buy Now" button that adds-to-cart and redirects in one click.
* Works on single product pages and/or shop and archive loops.
* Place the button **before or after** the native add-to-cart button on single product pages.
* Drop the button anywhere with the `[swift_buy_now]` shortcode (optionally targeting a product by id).
* Redirects to the **checkout** (skip the cart) or to the **cart**, whichever you prefer.
* Optionally empties the cart first so checkout shows only the chosen product.
* Optionally **respects the quantity** chosen on the product page (simple products).
* Pick a button **style**, theme default, solid, or outline, with an optional accent colour.
* Honours stock and purchasability, the button is hidden for out-of-stock or non-purchasable products, and is not shown for variable products in loops.

= Settings =

A simple WooCommerce settings page (WooCommerce → Swift Quick Buy) lets you:

* Enable or disable the Buy Now button.
* Set the button label.
* Choose where the button appears (single product, shop loops, or both).
* Choose whether it sits before or after the add-to-cart button on single products.
* Choose where it redirects (checkout or cart).
* Choose whether to empty the cart before adding.
* Choose whether to respect the quantity selected on the product page.
* Pick a button style (theme, solid, outline) and an optional accent colour.

= Shortcode =

Use `[swift_buy_now]` to render the Buy Now button anywhere, inside a page, post or block. It targets the current product by default; add an id to target a specific simple product:

`[swift_buy_now id="123"]`

== Installation ==

1. Upload the plugin to `/wp-content/plugins/swift`, or install via Plugins → Add New.
2. Activate it. WooCommerce must be active.
3. Visit **WooCommerce → Swift Quick Buy** to configure the button label, placement and redirect target.

== Frequently Asked Questions ==

= Does it require WooCommerce? =

Yes. Swift requires an active WooCommerce installation.

= Where does the Buy Now button appear? =

On single product pages and/or shop and archive product loops; you choose which in the settings. On loops it is shown only for simple, in-stock, purchasable products.

= Does it skip the cart? =

Yes, if you set the redirect target to "Checkout". The product is added to the cart and the shopper is taken straight to the checkout page. You can also choose to redirect to the cart instead.

= Can the Buy Now button empty the cart first? =

Yes. Swift can clear the cart before adding the selected product, so direct checkout contains only the item the shopper clicked.

= Does it respect the selected quantity? =

Yes for simple products on single product pages, when the "respect quantity" setting is enabled.

= Does it create database tables? =

No. Swift is stateless, it stores only its settings (one option) and creates no custom tables or product meta.

= Does it work with variable products? =

The free version is designed for simple products. On shop loops the button is shown for simple products only, since a variation must be chosen first. Full Buy Now support for variable products (with an inline variation picker) is planned for Swift Pro.

= Can I place the button with a shortcode? =

Yes. Use `[swift_buy_now]` for the current product or `[swift_buy_now id="123"]` for a specific simple product.

== Screenshots ==

1. The Buy Now button on a single product page.
2. The Swift Quick Buy settings screen.

== External Services ==

Swift does not connect to, send data to, or load anything from any external service. There is no SDK, no API client, no remote font, CDN or analytics endpoint, and no phone-home or licensing check, its CSS and JavaScript are bundled with the plugin and enqueued from your own site.

All of Swift's work happens on your server. It reads and writes a single settings option (`swift_settings`) and a schema-version marker (`swift_db_version`), and creates no custom database tables and no product meta. The Buy Now button adds the chosen product to the visitor's own WooCommerce cart and redirects them within your site to your checkout or cart page; nothing about the product, the cart or the shopper leaves your installation.

== Changelog ==

= 0.2.0 =
* New: `[swift_buy_now]` shortcode to place the Buy Now button anywhere (optionally targeting a product by id).
* New: choose whether the button sits before or after the add-to-cart button on single product pages.
* New: optionally respect the quantity chosen on the product page (simple products).
* New: button style options, theme default, solid, or outline, with an optional accent colour.
* New: "Settings" link in the plugins list row.
* New: uninstall cleanup removes the plugin's options (multisite-aware).
* Improved: redesigned settings page with grouped cards, a live button preview, a Live/Off status indicator, and accessible "?" help tooltips on every option.
* Improved: modern, themeable storefront button styles (CSS custom properties, dark-mode support, reduced-motion-safe transitions) with no layout shift.
* Improved: accessibility, keyboard-operable help tooltips, visible focus styles, and ARIA roles throughout the admin.
* Improved: robustness, the button never renders in a broken state for unpurchasable products, and the accent colour is scoped to Swift's own buttons.

= 0.1.0 =
* Initial release: a Buy Now button for WooCommerce that adds to cart and redirects straight to checkout (or cart), with a settings page for the label, placement and redirect target.
