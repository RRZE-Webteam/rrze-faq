'use strict';
jQuery( document ).ready( function ( n ) {
	n( '.settings-media-browse' ).on( 'click', function ( t ) {
		t.preventDefault();
		const e = n( this ),
			a = ( wp.media.frames.file_frame = wp.media( {
				title: e.data( 'uploader_title' ),
				button: { text: e.data( 'uploader_button_text' ) },
				multiple: ! 1,
			} ) );
		a.on( 'select', function () {
			( attachment = a.state().get( 'selection' ).first().toJSON() ),
				e.prev( '.settings-media-url' ).val( attachment.url ).change();
		} ),
			a.open();
	} );
} );
