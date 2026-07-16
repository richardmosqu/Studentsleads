/* University Leads — selector de logo desde la biblioteca de medios. */
jQuery( function ( $ ) {
	'use strict';

	var frame;

	$( '.ul-media-pick' ).on( 'click', function ( e ) {
		e.preventDefault();

		if ( ! frame ) {
			frame = wp.media( {
				title: 'Elegir logo',
				multiple: false,
				library: { type: 'image' },
			} );

			frame.on( 'select', function () {
				var att = frame.state().get( 'selection' ).first().toJSON();
				$( '#ul-logo-id' ).val( att.id );
				$( '#ul-logo-preview' ).attr( 'src', att.url ).show();
				$( '.ul-media-remove' ).show();
			} );
		}

		frame.open();
	} );

	$( '.ul-media-remove' ).on( 'click', function ( e ) {
		e.preventDefault();
		$( '#ul-logo-id' ).val( '' );
		$( '#ul-logo-preview' ).hide();
		$( this ).hide();
	} );
} );
