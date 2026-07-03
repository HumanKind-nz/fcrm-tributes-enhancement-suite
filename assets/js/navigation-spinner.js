/**
 * FCRM navigation spinner.
 * Shows a full-screen loading overlay when a visitor clicks through to a tribute,
 * covering the FireHawk API fetch delay. No dependencies.
 */
( function () {
	'use strict';

	var overlay = null;

	function showSpinner() {
		if ( ! overlay ) {
			overlay = document.createElement( 'div' );
			overlay.className = 'fcrm-navigating';
			document.body.appendChild( overlay );
		}
		// Small delay prevents flashing on fast connections.
		window.setTimeout( function () {
			if ( overlay ) {
				overlay.classList.add( 'active' );
				document.body.style.overflow = 'hidden';
			}
		}, 100 );
	}

	function hideSpinner() {
		if ( overlay ) {
			overlay.classList.remove( 'active' );
			document.body.style.overflow = '';
		}
	}

	document.addEventListener( 'click', function ( e ) {
		// closest() is only defined on Element; guard against non-Element
		// targets (e.g. text nodes) so a stray click can never throw.
		if ( ! ( e.target instanceof Element ) ) {
			return;
		}

		// Tribute card click (ignore clicks that land on a nested link — the
		// link rule below covers those).
		var card = e.target.closest(
			'.fcrm-tribute-card[data-detail-url], .minimal-tribute-item[data-detail-url]'
		);
		if ( card ) {
			var url = card.getAttribute( 'data-detail-url' );
			if ( url && url !== '#' && ! e.target.closest( 'a' ) ) {
				showSpinner();
			}
		}

		// Direct tribute link click (independent of the card rule).
		var link = e.target.closest(
			'a[href*="?id="], a.tribute-name-link, a.tribute-image-link, a.elegant-name-link, a.gallery-name-link'
		);
		if ( link && ! e.ctrlKey && ! e.metaKey ) {
			showSpinner();
		}
	} );

	// Hide when restored from bfcache (browser back button).
	window.addEventListener( 'pageshow', function ( e ) {
		if ( e.persisted ) {
			hideSpinner();
		}
	} );

	// Safety fallback: hide once the page has fully loaded.
	window.addEventListener( 'load', function () {
		window.setTimeout( hideSpinner, 100 );
	} );
}() );
