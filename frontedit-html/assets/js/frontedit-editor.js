( function () {
	'use strict';

	if ( typeof FrontEditHTML === 'undefined' ) {
		return;
	}

	var editMode  = false;
	var touched   = new Set();
	var originals = new Map();
	var toolbar   = null;

	document.addEventListener( 'click', function ( e ) {
		if ( e.target.closest( '#frontedit-launcher' ) ) {
			e.preventDefault();
			toggleEditMode();
		}
	} );

	function toggleEditMode() {
		editMode = ! editMode;
		document.body.classList.toggle( 'frontedit-edit-mode', editMode );

		var launcher = document.getElementById( 'frontedit-launcher' );
		if ( launcher ) {
			launcher.hidden = editMode;
		}

		var elements = document.querySelectorAll( '[data-fe-id]' );
		elements.forEach( function ( el ) {
			el.contentEditable = editMode ? 'true' : 'false';
		} );

		if ( editMode ) {
			attachListeners( elements );
			showToolbar();
		} else {
			hideToolbar();
			touched.clear();
			originals.clear();
		}
	}

	function attachListeners( elements ) {
		elements.forEach( function ( el ) {
			if ( ! originals.has( el ) ) {
				originals.set( el, el.innerHTML );
			}
			el.addEventListener( 'input', onEdit );
			el.addEventListener( 'paste', onPaste );
		} );
	}

	function onEdit( e ) {
		touched.add( e.currentTarget );
		updateStatus();
	}

	function onPaste( e ) {
		e.preventDefault();
		var text = ( e.clipboardData || window.clipboardData ).getData( 'text/plain' );
		document.execCommand( 'insertText', false, text );
	}

	function showToolbar() {
		toolbar = document.createElement( 'div' );
		toolbar.id = 'frontedit-toolbar';
		toolbar.innerHTML =
			'<span class="fe-status">Edit mode &mdash; click any highlighted text</span>' +
			'<button type="button" class="fe-save">Save Changes</button>' +
			'<button type="button" class="fe-cancel">Exit</button>';
		document.body.appendChild( toolbar );

		toolbar.querySelector( '.fe-save' ).addEventListener( 'click', saveChanges );
		toolbar.querySelector( '.fe-cancel' ).addEventListener( 'click', function () {
			revertAll();
			toggleEditMode();
		} );
	}

	function hideToolbar() {
		if ( toolbar && toolbar.parentNode ) {
			toolbar.parentNode.removeChild( toolbar );
		}
		toolbar = null;
	}

	function revertAll() {
		originals.forEach( function ( html, el ) {
			el.innerHTML = html;
		} );
	}

	function updateStatus( text ) {
		if ( ! toolbar ) {
			return;
		}
		var status = toolbar.querySelector( '.fe-status' );
		status.textContent = text || ( touched.size + ' change' + ( touched.size === 1 ? '' : 's' ) + ' unsaved' );
	}

	function saveChanges() {
		if ( touched.size === 0 ) {
			toggleEditMode();
			return;
		}

		var changes = [];
		touched.forEach( function ( el ) {
			changes.push( { fe_id: el.getAttribute( 'data-fe-id' ), html: el.innerHTML } );
		} );

		updateStatus( 'Saving...' );

		fetch( FrontEditHTML.restUrl, {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': FrontEditHTML.nonce
			},
			body: JSON.stringify( {
				post_id: FrontEditHTML.postId,
				changes: changes
			} )
		} )
			.then( function ( res ) { return res.json(); } )
			.then( function ( data ) {
				if ( data && data.success ) {
					updateStatus( 'Saved. Reloading...' );
					window.location.reload();
				} else {
					updateStatus( 'Error saving changes.' );
				}
			} )
			.catch( function () {
				updateStatus( 'Error saving changes.' );
			} );
	}
} )();
