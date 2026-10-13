/**
 * VilloCity Alert Bar: remember dismissal per message for this visitor.
 */
( function () {
	'use strict';

	var STORAGE_PREFIX = 'vocAlertDismissed:';

	function read( key ) {
		try {
			return window.localStorage.getItem( STORAGE_PREFIX + key );
		} catch ( e ) {
			return null;
		}
	}

	function write( key ) {
		try {
			window.localStorage.setItem( STORAGE_PREFIX + key, '1' );
		} catch ( e ) {
			// Storage unavailable (private mode etc.): dismissal lasts for this page view only.
		}
	}

	document.querySelectorAll( '[data-voc-alert][data-voc-dismissible]' ).forEach( function ( bar ) {
		var key = bar.getAttribute( 'data-voc-alert' );

		if ( read( key ) ) {
			bar.hidden = true;
			return;
		}

		var button = bar.querySelector( '.voc-alert-bar__dismiss' );
		if ( button ) {
			button.addEventListener( 'click', function () {
				bar.hidden = true;
				write( key );
			} );
		}
	} );
}() );
