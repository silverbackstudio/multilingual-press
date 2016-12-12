const { ajaxurl: ajaxUrl, jQuery: $ } = window;

// Internal pseudo-namespace for private data.
// NOTE: _this is shared between ALL instances of this module! So far, there is only one instance, so no problem NOW.
const _this = {
	/**
	 * Array of jQuery objects representing meta boxes with unsaved relationships.
	 * @type {jQuery[]}
	 */
	unsavedRelationships: []
};

/**
 * The MultilingualPress RelationshipControl module.
 */
class RelationshipControl extends Backbone.View {
	/**
	 * Constructor. Sets up the properties.
	 * @param {Object} [options={}] - Optional. The constructor options. Defaults to an empty object.
	 */
	constructor( options = {} ) {
		super( options );

		/**
		 * The event manager object.
		 * @type {EventManager}
		 */
		_this.EventManager = options.EventManager;

		/**
		 * The settings.
		 * @type {Object}
		 */
		_this.settings = options.settings;

		/**
		 * The set of utility methods.
		 * @type {Object}
		 */
		_this.Util = options.Util;
	}

	/**
	 * Returns the settings.
	 * @returns {Object} The settings.
	 */
	get settings() {
		return _this.settings;
	}

	/**
	 * Initializes the event handlers for all custom relationship control events.
	 */
	initializeEventHandlers() {
		_this.EventManager.on( {
			'RelationshipControl:connectExistingPost': this.connectExistingPost,
			'RelationshipControl:connectNewPost': this.connectNewPost,
			'RelationshipControl:disconnectPost': this.disconnectPost
		}, this );
	}

	/**
	 * Updates the unsaved relationships array for the meta box containing the changed radio input element.
	 * @param {Event} event - The change event of a radio input element.
	 * @returns {jQuery[]} The array of jQuery objects representing meta boxes with unsaved relationships.
	 */
	updateUnsavedRelationships( event ) {
		const $input = $( event.target );
		const $metaBox = $input.closest( '.mlp-translation-meta-box' );
		const $button = $metaBox.find( '.mlp-save-relationship-button' );
		const index = _this.unsavedRelationships.findIndex( ( e ) => e === $metaBox );

		if ( 'stay' === $input.val() ) {
			$button.prop( 'disabled', 'disabled' );

			if ( -1 !== index ) {
				_this.unsavedRelationships.splice( index, 1 );
			}
		} else if ( -1 === index ) {
			_this.unsavedRelationships.push( $metaBox );

			$button.removeAttr( 'disabled' );
		}

		return _this.unsavedRelationships;
	}

	/**
	 * Displays a confirm dialog informing the user about unsaved relationships, if any.
	 * @param {Event} event - The click event of the publish button.
	 */
	confirmUnsavedRelationships( event ) {
		if ( _this.unsavedRelationships.length && ! window.confirm( this.settings.l10n.unsavedRelationships ) ) {
			event.preventDefault();
		}
	}

	/**
	 * Triggers the according event in case of changed relationships.
	 * @param {Event} event - The click event of a save relationship button.
	 */
	saveRelationship( event ) {
		const $button = $( event.target );
		const $container = $button.closest( '.mlp-relationship-control' );
		const remoteSiteId = $container.data( 'remote-site-id' );
		const action = $( `input[name="mlp_rc_action[${remoteSiteId}]"]:checked` ).val();

		if ( 'stay' === action ) {
			return;
		}

		$button.prop( 'disabled', 'disabled' );

		const eventName = this.getEventName( action );

		/**
		 * Triggers the according event for the current relationship action, and passes data and the event's name.
		 */
		_this.EventManager.trigger( `RelationshipControl:${eventName}`, {
			action,
			remote_post_id: $container.data( 'remote-post-id' ),
			remote_site_id: remoteSiteId,
			source_post_id: $container.data( 'source-post-id' ),
			source_site_id: $container.data( 'source-site-id' ),
		}, eventName );
	}

	/**
	 * Returns the according event name for the given relationship action.
	 * @param {String} action - The relationship action.
	 * @returns {String} The event name.
	 */
	getEventName( action ) {
		switch ( action ) {
			case this.settings.actionConnectExisting:
				return 'connectExistingPost';

			case this.settings.actionConnectNew:
				return 'connectNewPost';

			case this.settings.actionDisconnect:
				return 'disconnectPost';
		}

		return '';
	}

	/**
	 * Handles changing a post's relationship by connecting a new post.
	 * @param {Object} data - The common data for all relationship requests.
	 */
	connectNewPost( data ) {
		const postData = data;

		postData.new_post_title = $( 'input[name="post_title"]' ).val();

		this.sendRequest( postData );
	}

	/**
	 * Handles changing a post's relationship by disconnecting the currently connected post.
	 * @param {Object} data - The common data for all relationship requests.
	 */
	disconnectPost( data ) {
		this.sendRequest( data );
	}

	/**
	 * Handles changing a post's relationship by connecting an existing post.
	 * @param {Object} data - The common data for all relationship requests.
	 * @returns {Boolean} Whether or not the request has been sent.
	 */
	connectExistingPost( data ) {
		const newPostId = Number( $( `input[name="mlp_add_post[${data.remote_site_id}]"]:checked` ).val() || 0 );
		if ( ! newPostId ) {
			window.alert( this.settings.l10n.noPostSelected );

			return false;
		}

		const postData = data;

		postData.new_post_id = newPostId;

		this.sendRequest( postData );

		return true;
	}

	/**
	 * Changes a post's relationhip by sending a synchronous AJAX request with the according new relationship data.
	 * @param {Object} data - The relationship data.
	 */
	sendRequest( data ) {
		$.ajax( {
			type: 'POST',
			url: ajaxUrl,
			data,
			success: _this.Util.reloadLocation,
			async: false
		} );
	}
}

export default RelationshipControl;
