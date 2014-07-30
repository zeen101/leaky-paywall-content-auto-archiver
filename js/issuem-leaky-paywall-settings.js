var $leaky_paywall_content_auto_archive_settings = jQuery.noConflict();

$leaky_paywall_content_auto_archive_settings(document).ready(function($) {
	
	$( 'input#add-expiration-row' ).live( 'click', function( event ) {
		event.preventDefault();
        var data = {
            'action': 'leaky-paywall-content-auto-archive-add-new-expiration-row',
            'row-key': ++content_auto_archiver_key_count,
        }
        $.post( ajaxurl, data, function( response ) {
            $( 'table#leaky_paywall_content_auto_archiver_wrapper' ).append( response );
        });
	});
		
	$( '.delete-expiration-row' ).live( 'click', function ( event ) {
		event.preventDefault();
		var parent = $( this ).parents( '.issuem-leaky-paywall-row-expiration' );
		parent.slideUp( 'normal', function() { $( this ).remove(); } );
	});
		
});