/* global MultilingualPress */
(function( $ ) {
	'use strict';

	/**
	 * Constructor for the MultilingualPress TermTranslator module.
	 * @constructor
	 */
	var TermTranslator = Backbone.View.extend( {
		el: '#mlp-term-translations',

		events: {
			'change select': 'propagateSelectedTerm'
		},

		/**
		 * Initializes the TermTranslator module.
		 */
		initialize: function() {
			this.$selects = this.$el.find( 'select' );
		},

		/**
		 * Propagates the new value of one term select element to all other term select elements.
		 * @param {Event} event - The change event of a term select element.
		 */
		propagateSelectedTerm: function( event ) {
			var $select,
				relationshipID;

			if ( this.isPropagating ) {
				return;
			}

			this.isPropagating = true;

			$select = $( event.target );

			relationshipID = this.getSelectedRelationshipID( $select );
			if ( 0 !== relationshipID ) {
				this.$selects.not( $select ).each( function( index, element ) {
					this.selectTerm( $( element ), relationshipID );
				}.bind( this ) );
			}

			this.isPropagating = false;
		},

		/**
		 * Returns the relationship ID of the given select element (i.e., its currently selected option).
		 * @param {Object} $select - A select element.
		 * @returns {number} - The relationship ID of the selected term.
		 */
		getSelectedRelationshipID: function( $select ) {
			return $select.find( 'option:selected' ).data( 'relationship-id' ) || 0;
		},

		/**
		 * Sets the given select element's value to that of the option with the given relationship ID, or the first
		 * option.
		 * @param {Object} $select - A select element.
		 * @param {number} relationshipID - The relationship ID of a term.
		 */
		selectTerm: function( $select, relationshipID ) {
			var $option = $select.find( 'option[data-relationship-id="' + relationshipID + '"]' );
			if ( $option.length ) {
				$select.val( $option.val() );
			} else if ( this.getSelectedRelationshipID( $select ) ) {
				$select.val( $select.find( 'option' ).first().val() );
			}
		}
	} );

	// Register the TermTranslator module for the Edit Tags admin page.
	MultilingualPress.registerModule( 'edit-tags.php', 'TermTranslator', TermTranslator );
})( jQuery );
