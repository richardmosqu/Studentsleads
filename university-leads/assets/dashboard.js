/* University Leads — drag & drop del tablero kanban. */
( function () {
	'use strict';

	function updateStatus( leadId, estado, revert ) {
		var body = new URLSearchParams();
		body.append( 'action', 'ul_update_status' );
		body.append( 'lead', leadId );
		body.append( 'estado', estado );
		body.append( '_wpnonce', ULDash.nonce );

		fetch( ULDash.ajaxurl, { method: 'POST', credentials: 'same-origin', body: body } )
			.then( function ( res ) { return res.json(); } )
			.then( function ( json ) {
				if ( ! json || ! json.success ) {
					throw new Error( 'save failed' );
				}
			} )
			.catch( function () {
				window.alert( ULDash.errorMsg );
				if ( revert ) {
					revert();
				}
			} );
	}

	function updateCounts() {
		document.querySelectorAll( '.ul-column' ).forEach( function ( col ) {
			var count = col.querySelectorAll( '.ul-card' ).length;
			var badge = col.querySelector( '.ul-column__count' );
			if ( badge ) {
				badge.textContent = count;
			}
		} );
	}

	function syncSelect( card, estado ) {
		var select = card.querySelector( '.ul-card__status' );
		if ( select ) {
			select.value = estado;
		}
	}

	document.addEventListener( 'dragstart', function ( e ) {
		var card = e.target.closest ? e.target.closest( '.ul-card' ) : null;
		if ( ! card ) {
			return;
		}
		e.dataTransfer.setData( 'text/plain', card.dataset.lead );
		e.dataTransfer.effectAllowed = 'move';
		card.classList.add( 'ul-card--dragging' );
	} );

	document.addEventListener( 'dragend', function ( e ) {
		if ( e.target.classList && e.target.classList.contains( 'ul-card' ) ) {
			e.target.classList.remove( 'ul-card--dragging' );
		}
	} );

	document.querySelectorAll( '.ul-column__body' ).forEach( function ( col ) {
		col.addEventListener( 'dragover', function ( e ) {
			e.preventDefault();
			e.dataTransfer.dropEffect = 'move';
			col.classList.add( 'ul-column__body--over' );
		} );
		col.addEventListener( 'dragleave', function () {
			col.classList.remove( 'ul-column__body--over' );
		} );
		col.addEventListener( 'drop', function ( e ) {
			e.preventDefault();
			col.classList.remove( 'ul-column__body--over' );

			var leadId = e.dataTransfer.getData( 'text/plain' );
			var card = document.querySelector( '.ul-card[data-lead="' + leadId + '"]' );
			if ( ! card || card.parentElement === col ) {
				return;
			}

			var prevParent = card.parentElement;
			col.prepend( card );
			syncSelect( card, col.dataset.estado );
			updateCounts();

			updateStatus( leadId, col.dataset.estado, function () {
				prevParent.prepend( card );
				syncSelect( card, prevParent.dataset.estado );
				updateCounts();
			} );
		} );
	} );

	// Alternativa sin drag & drop (táctil/teclado): el selector de estado.
	document.querySelectorAll( '.ul-card__status' ).forEach( function ( select ) {
		select.addEventListener( 'change', function () {
			var card = select.closest( '.ul-card' );
			var target = document.querySelector( '.ul-column__body[data-estado="' + select.value + '"]' );
			if ( ! card || ! target ) {
				return;
			}
			var prevParent = card.parentElement;
			target.prepend( card );
			updateCounts();
			updateStatus( card.dataset.lead, select.value, function () {
				prevParent.prepend( card );
				syncSelect( card, prevParent.dataset.estado );
				updateCounts();
			} );
		} );
	} );
} )();
