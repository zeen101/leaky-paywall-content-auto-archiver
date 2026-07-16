( function( $ )  {


	$(document).ready( function() {
		var $wrapper = $( '#leaky_paywall_content_auto_archiver_wrapper' );

		// Delegated on document so this survives DOM re-renders and any
		// timing weirdness with when the button lands in the tree. Direct
		// .click() bind broke silently after the UI restructure — likely
		// because something in LP admin was replacing / re-rendering the
		// button between our enqueue and the actual page ready. Matches
		// LP core's own pattern for add-restriction-row.
		$( document ).on( 'click', 'input#add-expiration-row', function( event ) {
			event.preventDefault();
			var data = {
				'action': 'leaky-paywall-content-auto-archive-add-new-expiration-row',
				'row-key': ++content_auto_archiver_key_count,
				'nonce': ( typeof lp_caa_settings !== 'undefined' ? lp_caa_settings.nonce : '' ),
			}
			$.post( ajaxurl, data, function( response ) {
				$wrapper.append( response );
			});
		});

		// Delegated so the handler catches rows added via AJAX too. The
		// previous direct .click() bind missed newly-appended rows —
		// the "Remove" button on a freshly-added row silently did nothing.
		$wrapper.on( 'click', '.delete-expiration-row', function ( event ) {
			event.preventDefault();
			var $parent = $( '#lp-caa-row-' + $( this ).data( 'id' ) );
			$parent.slideUp( 'normal', function() { $( this ).remove(); } );
		});
	});


})( jQuery );