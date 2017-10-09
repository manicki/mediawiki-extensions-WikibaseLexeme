/**
 * @license GPL-2.0+
 */
( function ( mw, wb, $ ) {
	'use strict';

	/**
	 * @constructor
	 *
	 * @param {mediaWiki.Api} api
	 * @param {string} lexemeId
	 */
	var SELF = wb.lexeme.entityChangers.FormChanger = function WbLexemeFormChanger(
		api,
		lexemeId
	) {
		this.api = api;
		this.lexemeId = lexemeId;
	};

	$.extend( SELF.prototype, {
		/**
		 * @type {mediaWiki.Api}
		 * @private
		 */
		api: null,

		/**
		 * @type {string}
		 * @private
		 */
		lexemeId: null,

		/**
		 * @param {wikibase.lexeme.datamodel.Form} form
		 * @return {jQuery.Promise}
		 */
		save: function ( form ) {
			var formSerializer = new wb.lexeme.serialization.FormSerializer(),
				lexemeDeserializer = new wb.lexeme.serialization.LexemeDeserializer();

			if ( form.getId() ) {
				return $.Deferred().resolve( form ).promise();// TODO: implement edit form
			}

			var serializedForm = formSerializer.serialize( form );
			var representations = [];
			for ( var languageKey in serializedForm.representations ) {
				if ( serializedForm.representations.hasOwnProperty( languageKey ) ) {
					representations.push( serializedForm.representations[ languageKey ] );
				}
			}

			serializedForm.representations = representations;
			delete serializedForm[ 'id' ];

			return this.api.postWithToken( 'csrf', {
				action: 'wblexemeaddform',
				lexemeId: this.lexemeId,
				data: JSON.stringify( serializedForm ),
				errorformat: 'plaintext',
				bot: 1
			} ).then( function ( data ) {
				return lexemeDeserializer.deserializeForm( data.form );
			} ).catch( function ( code, response ) {
				throw convertPlainTextErrorsToRepoApiError( response.errors );
			} );
		}
	} );

	function convertPlainTextErrorsToRepoApiError( errors ) {
		var $ul = $( '<ul>' );

		var code = '';
		errors.forEach( function ( e ) {
			if ( !code ) {
				code = e.code;
			}

			var $li = $( '<li>' ).text( e[ '*' ] );
			$ul.append( $li );
		} );

		var action = 'save';
		var detailedMessage = $ul.html();
		return new wb.api.RepoApiError(
			code,
			detailedMessage,
			[],
			action
		);
	}

}( mediaWiki, wikibase, jQuery ) );