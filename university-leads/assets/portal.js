/* University Leads — portal: el estado se guarda en cuanto se cambia el selector. */
( function () {
	'use strict';

	document.querySelectorAll( '.ul-plead__status' ).forEach( function ( form ) {
		var select = form.querySelector( 'select' );
		var button = form.querySelector( 'button' );
		var saving = form.querySelector( '.ul-plead__saving' );
		var saved = form.querySelector( '.ul-plead__saved' );
		if ( ! select ) {
			return;
		}

		// El botón solo hace falta sin JavaScript. Si se queda visible, el equipo
		// cambia varios selectores, pulsa uno y pierde el resto.
		if ( button ) {
			button.style.display = 'none';
		}

		select.addEventListener( 'change', function () {
			if ( saved ) {
				saved.hidden = true;
			}
			if ( saving ) {
				saving.hidden = false;
			}
			form.submit();
		} );
	} );
} )();
