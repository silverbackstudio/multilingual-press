;( function( $ ) {
	"use strict";

	var term_translator = {

		init: function() {
			this.isPropagating = false;

			this.propagate_term_selection();
		},

		propagate_term_selection: function() {
			var $table = $( '.mlp_term_selections' );

			if ( $table.length ) {
				var $selects = $table.find( 'select' );

				$table.on( 'change', 'select', function() {
					if ( term_translator.isPropagating ) {
						return;
					}

					term_translator.isPropagating = true;

					var $this = $( this ),
						relationship = $this.find( '[value="' + $this.val() + '"]' ).data( 'relationship' ) || '0';

					$selects.not( $this ).each( function() {
						var $this = $( this ),
							$option = $this.find( 'option[data-relationship="' + relationship + '"]' ),
							currentRelationship = $this.find( '[value="' + $this.val() + '"]' ).data( 'relationship' ) || '0';

						if ( relationship !== '0' ) {
							if ( $option.length ) {
								$this.val( $option.val() );
							} else if ( currentRelationship !== '0' ) {
								$this.val( $this.find( 'option' ).first().val() );
							}
						}
					} );

					term_translator.isPropagating = false;
				} );
			}
		}
	};

	$( function() {
		term_translator.init();
	} );

} )( jQuery );
