/**
 * Swift — respect the on-page quantity.
 *
 * When the "respect quantity" option is on, the single-product Buy Now form
 * carries a hidden `quantity` field marked `data-swift-quantity`. On submit we
 * copy the value from WooCommerce's own quantity input (`input.qty` inside the
 * cart form) into that hidden field, so the buyer checks out with the quantity
 * they chose. Simple products only — variable products are a Swift Pro feature.
 *
 * No dependencies; plain DOM, no build step.
 */
( function () {
	'use strict';

	function readQuantity() {
		var input = document.querySelector( 'form.cart input.qty' );

		if ( ! input ) {
			return 1;
		}

		var value = parseInt( input.value, 10 );

		return isNaN( value ) || value < 1 ? 1 : value;
	}

	function init() {
		var forms = document.querySelectorAll(
			'form.swift-buy-now[data-swift-respect-qty]'
		);

		forms.forEach( function ( form ) {
			form.addEventListener( 'submit', function () {
				var field = form.querySelector( '[data-swift-quantity]' );

				if ( field ) {
					field.value = String( readQuantity() );
				}
			} );
		} );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
