( function () {
	'use strict';

	if ( typeof FrontEditHTML === 'undefined' ) {
		return;
	}

	var editMode      = false;
	var touched       = new Set();
	var originals     = new Map();
	var imageChanges  = new Map();
	var toolbar       = null;

	document.addEventListener( 'click', function ( e ) {
		if ( e.target.closest( '#frontedit-launcher' ) ) {
			e.preventDefault();
			toggleEditMode();
		}
	} );

	/*
	 * While editing, a plain click on an editable link/button would otherwise
	 * still fire its normal behavior — navigating away, or triggering a
	 * popup/lightbox script bound to that same click — which fights with
	 * placing a text cursor to edit it. Suppress that in the capture phase
	 * (so it never reaches the site's own click handlers), but let a
	 * Ctrl/Cmd-click through untouched so the real link or popup can still
	 * be tested on purpose.
	 *
	 * Handling the image picker here too (rather than a separate listener on
	 * the <img>) avoids a conflict: stopPropagation() during capture means an
	 * element-level listener registered later would never fire anyway.
	 */
	document.addEventListener( 'click', function ( e ) {
		if ( ! editMode || e.ctrlKey || e.metaKey ) {
			return;
		}
		var el = e.target.closest( '[data-fe-id]' );
		if ( ! el ) {
			return;
		}
		e.preventDefault();
		e.stopPropagation();

		if ( el.tagName === 'IMG' ) {
			openImagePicker( el );
		}
	}, true );

	function openImagePicker( el ) {
		if ( typeof wp === 'undefined' || ! wp.media ) {
			updateStatus( 'Media library unavailable.' );
			return;
		}

		var frame = wp.media( {
			title: 'Select Image',
			button: { text: 'Use this image' },
			multiple: false
		} );

		frame.on( 'select', function () {
			var attachment = frame.state().get( 'selection' ).first().toJSON();
			el.src = attachment.url;
			el.alt = attachment.alt || '';
			el.removeAttribute( 'srcset' );
			el.removeAttribute( 'sizes' );

			imageChanges.set( el, { src: attachment.url, alt: attachment.alt || '' } );
			touched.add( el );
			updateStatus();
		} );

		frame.open();
	}

	function toggleEditMode() {
		editMode = ! editMode;
		document.body.classList.toggle( 'frontedit-edit-mode', editMode );

		var launcher = document.getElementById( 'frontedit-launcher' );
		if ( launcher ) {
			launcher.hidden = editMode;
		}

		var elements = document.querySelectorAll( '[data-fe-id]' );
		elements.forEach( function ( el ) {
			if ( el.tagName !== 'IMG' ) {
				el.contentEditable = editMode ? 'true' : 'false';
			}
		} );

		if ( editMode ) {
			attachListeners( elements );
			showToolbar();
		} else {
			hideToolbar();
			touched.clear();
			originals.clear();
			imageChanges.clear();
		}
	}

	function attachListeners( elements ) {
		elements.forEach( function ( el ) {
			if ( ! originals.has( el ) ) {
				originals.set( el, el.tagName === 'IMG' ? { src: el.src, alt: el.alt } : el.innerHTML );
			}
			if ( el.tagName !== 'IMG' ) {
				el.addEventListener( 'input', onEdit );
				el.addEventListener( 'paste', onPaste );
			}
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
			'<span class="fe-status">Edit mode &mdash; click text to edit, click an image to replace it</span>' +
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
		originals.forEach( function ( value, el ) {
			if ( el.tagName === 'IMG' ) {
				el.src = value.src;
				el.alt = value.alt;
			} else {
				el.innerHTML = value;
			}
		} );
		imageChanges.clear();
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
			var feId = el.getAttribute( 'data-fe-id' );
			if ( el.tagName === 'IMG' ) {
				var data = imageChanges.get( el ) || { src: el.src, alt: el.alt };
				changes.push( { fe_id: feId, type: 'image', src: data.src, alt: data.alt } );
			} else {
				changes.push( { fe_id: feId, type: 'text', html: el.innerHTML } );
			}
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
