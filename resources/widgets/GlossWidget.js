module.exports = ( function ( require, Vue ) {
	'use strict';

	function deepClone( object ) {
		return JSON.parse( JSON.stringify( object ) );
	}

	function applyGlossWidget( widgetElement, glosses, beforeUpdate, mw, getDirectionality ) {
		var template = '#gloss-widget-vue-template';
		var messages = require( 'wikibase.lexeme.i18n.Messages' );

		return new Vue( newGlossWidget( messages, widgetElement, template, glosses, beforeUpdate, getDirectionality ) );
	}

	/**
	 *
	 * @param {wikibase.lexeme.i18n.Messages} messages
	 * @param {string|HTMLElement} widgetElement
	 * @param {string} template
	 * @param {[{ value: string, language: string }]} glosses
	 * @param {function} beforeUpdate
	 * @param {function} getDirectionality
	 * @return {object}
	 */
	function newGlossWidget( messages, widgetElement, template, glosses, beforeUpdate, getDirectionality ) {
		return {
			el: widgetElement,
			template: template,

			beforeUpdate: beforeUpdate,

			data: {
				inEditMode: false,
				glosses: deepClone( glosses )
			},
			methods: {
				add: function () {
					this.glosses.push( { value: '', language: '' } );
				},
				remove: function ( gloss ) {
					var index = this.glosses.indexOf( gloss );
					this.glosses.splice( index, 1 );
				},
				edit: function () {
					this.inEditMode = true;
					if ( this.glosses.length === 0 ) {
						this.add();
					}
				},
				stopEditing: function () {
					this.inEditMode = false;
					this.glosses = this.glosses.filter( function ( gloss ) {
						return gloss.value.trim() !== '' && gloss.language.trim() !== '';
					} );
				}
			},
			filters: {
				message: function ( key ) {
					return messages.getUnparameterizedTranslation( key );
				},
				directionality: function ( languageCode ) {
					return getDirectionality( languageCode );
				}
			}
		};
	}

	return {
		applyGlossWidget: applyGlossWidget,
		newGlossWidget: newGlossWidget
	};

} )( require, Vue );
