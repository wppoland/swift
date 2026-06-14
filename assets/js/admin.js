/**
 * Swift — admin settings enhancements.
 *
 * Two small, dependency-free enhancements (no jQuery, no build step):
 *
 *  1. Accessible help tooltips. Each setting has a "?" button with its help text
 *     in an adjacent element. Where the browser supports the native Popover API
 *     we promote that element to a popover and toggle it on hover/focus. Where it
 *     is not supported we leave the help text inline and wire it via
 *     aria-describedby so screen-reader and keyboard users still get it. Either
 *     way the control is keyboard-operable and announced.
 *
 *  2. A live button preview that mirrors the chosen label, style and accent so
 *     merchants see the effect of their choices before saving.
 *
 * The markup is rendered server-side (escaped) in Settings::renderPage(); this
 * script only adds behaviour and degrades gracefully if it never runs.
 */
( function () {
	'use strict';

	var supportsPopover =
		typeof HTMLElement !== 'undefined' &&
		HTMLElement.prototype.hasOwnProperty( 'popover' );

	function setupTips( root ) {
		var triggers = root.querySelectorAll( '.swift-help' );

		triggers.forEach( function ( trigger ) {
			var tipId = trigger.getAttribute( 'aria-describedby' );
			var tip = tipId ? root.querySelector( '#' + cssEscape( tipId ) ) : null;

			if ( ! tip ) {
				return;
			}

			if ( ! supportsPopover ) {
				// Fallback: keep the help text visible inline. It is already
				// wired via aria-describedby, so just mark it for styling and
				// stop here.
				tip.setAttribute( 'data-swift-inline', '' );
				return;
			}

			tip.setAttribute( 'popover', 'manual' );

			var show = function () {
				try {
					positionTip( trigger, tip );
					tip.showPopover();
				} catch ( e ) {}
			};
			var hide = function () {
				try {
					tip.hidePopover();
				} catch ( e ) {}
			};

			trigger.addEventListener( 'mouseenter', show );
			trigger.addEventListener( 'mouseleave', hide );
			trigger.addEventListener( 'focus', show );
			trigger.addEventListener( 'blur', hide );
			trigger.addEventListener( 'click', function ( e ) {
				e.preventDefault();
			} );
			// Esc dismisses (in addition to the browser's light-dismiss).
			trigger.addEventListener( 'keydown', function ( e ) {
				if ( e.key === 'Escape' ) {
					hide();
				}
			} );
		} );
	}

	function positionTip( trigger, tip ) {
		var rect = trigger.getBoundingClientRect();
		tip.style.position = 'fixed';
		tip.style.insetInlineStart =
			Math.max( 8, rect.left - 8 ) + 'px';
		tip.style.insetBlockStart = rect.bottom + 8 + 'px';
		tip.style.margin = '0';
	}

	// Minimal CSS.escape polyfill for our known-safe ids.
	function cssEscape( value ) {
		if ( window.CSS && window.CSS.escape ) {
			return window.CSS.escape( value );
		}
		return value.replace( /([^a-zA-Z0-9_-])/g, '\\$1' );
	}

	function setupPreview( root ) {
		var preview = root.querySelector( '.swift-preview__btn' );

		if ( ! preview ) {
			return;
		}

		var labelInput = root.querySelector( '#swift_button_text' );
		var accentInput = root.querySelector( '#swift_accent_color' );
		var styleInputs = root.querySelectorAll(
			'input[name$="[button_style]"]'
		);

		var defaultLabel = preview.textContent.trim() || 'Buy now';

		var render = function () {
			// Label.
			if ( labelInput ) {
				var text = labelInput.value.trim();
				preview.textContent = text === '' ? defaultLabel : text;
			}

			// Style class.
			var style = 'theme';
			styleInputs.forEach( function ( input ) {
				if ( input.checked ) {
					style = input.value;
				}
			} );

			preview.classList.remove(
				'swift-preview__btn--theme',
				'swift-preview__btn--solid',
				'swift-preview__btn--outline'
			);
			preview.classList.add( 'swift-preview__btn--' + style );

			// Accent (solid/outline only).
			var accent = accentInput ? accentInput.value.trim() : '';
			if ( accent && ( style === 'solid' || style === 'outline' ) ) {
				preview.style.setProperty( '--swift-accent', accent );
				if ( style === 'solid' ) {
					preview.style.background = accent;
					preview.style.borderColor = accent;
					preview.style.color = '';
				} else {
					preview.style.background = 'transparent';
					preview.style.borderColor = accent;
					preview.style.color = accent;
				}
			} else {
				preview.style.background = '';
				preview.style.borderColor = '';
				preview.style.color = '';
			}
		};

		var debouncedRender = debounce( render, 80 );

		if ( labelInput ) {
			labelInput.addEventListener( 'input', debouncedRender );
		}
		if ( accentInput ) {
			accentInput.addEventListener( 'input', debouncedRender );
		}
		styleInputs.forEach( function ( input ) {
			input.addEventListener( 'change', render );
		} );

		render();
	}

	function debounce( fn, wait ) {
		var t;
		return function () {
			var ctx = this;
			var args = arguments;
			clearTimeout( t );
			t = setTimeout( function () {
				fn.apply( ctx, args );
			}, wait );
		};
	}

	function init() {
		var root = document.querySelector( '.swift-admin' );

		if ( ! root ) {
			return;
		}

		setupTips( root );
		setupPreview( root );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
} )();
