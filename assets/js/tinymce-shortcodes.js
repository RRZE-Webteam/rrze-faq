tinymce.PluginManager.add( 'rrze_shortcode', function ( editor ) {
	if ( 'undefined' !== typeof phpvar )
		for ( i = 0; i < phpvar.length; i++ )
			( shortcode = phpvar[ i ].shortcode ),
				editor.addMenuItem( 'insert_' + phpvar[ i ].name, {
					id: i,
					icon: phpvar[ i ].icon,
					text: phpvar[ i ].title,
					context: 'insert',
					onclick() {
						editor.insertContent(
							phpvar[ this.settings.id ].shortcode
						);
					},
				} );
} );
