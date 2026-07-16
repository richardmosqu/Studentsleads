/* University Leads — wizard conversacional del formulario.
   Sin JS el formulario se muestra completo en una página y funciona igual. */
( function () {
	'use strict';

	function initWizard( form ) {
		var steps = Array.prototype.slice.call( form.querySelectorAll( '.ul-step' ) );
		if ( ! steps.length ) {
			return;
		}

		var prevBtn = form.querySelector( '[data-ul-prev]' );
		var nextBtn = form.querySelector( '[data-ul-next]' );
		var submitBtn = form.querySelector( '.ul-submit' );
		var bar = form.querySelector( '.ul-progress__bar' );
		var label = form.querySelector( '.ul-progress__label' );
		var nameInput = form.querySelector( '[data-ul-nombre-input]' );
		var nameSpans = form.querySelectorAll( '[data-ul-nombre]' );
		var current = 0;
		var booted = false;

		form.classList.add( 'ul-form--wizard' );

		function firstField( step ) {
			return step.querySelector( 'input:not([type="hidden"]):not([type="radio"]), textarea' ) ||
				step.querySelector( 'input[type="radio"]' );
		}

		function show( index ) {
			current = Math.max( 0, Math.min( index, steps.length - 1 ) );

			steps.forEach( function ( step, i ) {
				step.classList.toggle( 'ul-step--active', i === current );
			} );

			var isLast = current === steps.length - 1;
			prevBtn.style.visibility = current === 0 ? 'hidden' : 'visible';
			nextBtn.hidden = isLast;
			submitBtn.hidden = ! isLast;

			var pct = Math.round( ( current / ( steps.length - 1 ) ) * 100 );
			if ( bar ) {
				bar.style.width = Math.max( pct, 4 ) + '%';
			}
			if ( label ) {
				label.textContent = ( current + 1 ) + ' / ' + steps.length;
			}

			if ( booted ) {
				var field = firstField( steps[ current ] );
				if ( field && field.type !== 'radio' ) {
					try {
						field.focus( { preventScroll: true } );
					} catch ( err ) {
						field.focus();
					}
				}
			}
			booted = true;
		}

		function shake( step ) {
			step.classList.remove( 'ul-step--shake' );
			// Reinicia la animación de aviso.
			void step.offsetWidth;
			step.classList.add( 'ul-step--shake' );
		}

		function stepIsValid() {
			var step = steps[ current ];

			// Grupos de selección múltiple: exigen al menos una opción marcada.
			if ( step.hasAttribute( 'data-min' ) ) {
				var boxes = step.querySelectorAll( 'input[type="checkbox"]' );
				var any = Array.prototype.some.call( boxes, function ( b ) {
					return b.checked;
				} );
				if ( boxes.length && ! any ) {
					boxes[ 0 ].setCustomValidity( 'Elige al menos una opción, por favor.' );
					boxes[ 0 ].reportValidity();
					shake( step );
					return false;
				}
				if ( boxes.length ) {
					boxes[ 0 ].setCustomValidity( '' );
				}
			}

			var fields = step.querySelectorAll( 'input, textarea, select' );
			for ( var i = 0; i < fields.length; i++ ) {
				if ( ! fields[ i ].checkValidity() ) {
					fields[ i ].reportValidity();
					shake( step );
					return false;
				}
			}
			return true;
		}

		nextBtn.addEventListener( 'click', function () {
			if ( stepIsValid() ) {
				show( current + 1 );
			}
		} );

		prevBtn.addEventListener( 'click', function () {
			show( current - 1 );
		} );

		// Enter avanza (excepto en el textarea final).
		form.addEventListener( 'keydown', function ( e ) {
			if ( e.key !== 'Enter' || e.target.tagName === 'TEXTAREA' || e.target.type === 'submit' ) {
				return;
			}
			e.preventDefault();
			if ( current === steps.length - 1 ) {
				if ( stepIsValid() ) {
					form.requestSubmit ? form.requestSubmit() : form.submit();
				}
			} else if ( stepIsValid() ) {
				show( current + 1 );
			}
		} );

		// Las preguntas de opciones avanzan solas al elegir.
		form.addEventListener( 'change', function ( e ) {
			// Selección múltiple: marca visual y limpieza del aviso, sin auto-avance.
			if ( e.target.type === 'checkbox' ) {
				var option = e.target.closest( '.ul-option' );
				if ( option ) {
					option.classList.toggle( 'ul-option--selected', e.target.checked );
				}
				var boxes = form.querySelectorAll( 'input[type="checkbox"][name="' + e.target.name + '"]' );
				if ( boxes.length ) {
					boxes[ 0 ].setCustomValidity( '' );
				}
				return;
			}
			if ( e.target.type !== 'radio' ) {
				return;
			}
			// Marca visual de la opción elegida (fallback de :has()).
			var group = form.querySelectorAll( 'input[name="' + e.target.name + '"]' );
			group.forEach( function ( radio ) {
				radio.closest( '.ul-option' ).classList.toggle( 'ul-option--selected', radio.checked );
			} );
			var step = e.target.closest( '.ul-step' );
			if ( ! step || ! step.hasAttribute( 'data-auto' ) || steps[ current ] !== step ) {
				return;
			}
			window.setTimeout( function () {
				if ( steps[ current ] === step && current < steps.length - 1 ) {
					show( current + 1 );
				}
			}, 350 );
		} );

		// Personalización: el nombre aparece en los textos siguientes.
		if ( nameInput ) {
			nameInput.addEventListener( 'input', function () {
				var first = nameInput.value.trim().split( /\s+/ )[ 0 ] || '';
				nameSpans.forEach( function ( span ) {
					span.textContent = first ? ', ' + first : '';
				} );
			} );
		}

		show( 0 );
	}

	document.querySelectorAll( '.ul-form[data-ul-wizard]' ).forEach( initWizard );
} )();
