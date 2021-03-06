wikibase.lexeme.widgets.buildLexemeHeader = ( function ( $, mw, require, wb, Vue, Vuex ) {
	'use strict';

	/** @type {wikibase.lexeme.widgets.LexemeHeader.newLexemeHeaderStore} */
	var newLexemeHeaderStore = require( 'wikibase.lexeme.widgets.LexemeHeader.newLexemeHeaderStore' );
	var newLemmaWidget = require( 'wikibase.lexeme.widgets.LemmaWidget.newLemmaWidget' );
	var newLanguageAndLexicalCategoryWidget = require( 'wikibase.lexeme.widgets.LanguageAndLexicalCategoryWidget' );
	var newLexemeHeader = require( 'wikibase.lexeme.widgets.LexemeHeader.newLexemeHeader' );
	var Lemma = require( 'wikibase.lexeme.datamodel.Lemma' );

	/**
	 * FIXME Use wikibase.lexeme.datamodel.Lexeme
	 *
	 * @param {Object} wbEntity
	 * @return {{lemmas: wikibase.lexeme.datamodel.Lemma[], lexicalCategory: string|null, language: string|null, id: string}}
	 */
	function hydrateLexeme( wbEntity ) {
		return {
			lemmas: hydrateLemmas( wbEntity.lemmas ),
			lexicalCategory: wbEntity.lexicalCategory,
			language: wbEntity.language,
			id: wbEntity.id
		};
	}

	/**
	 * Create an array of wikibase.lexeme.datamodel.Lemma from lemma information per wikibase entity object
	 *
	 * @param {Object} lemmaInfo
	 * @return {wikibase.lexeme.datamodel.Lemma[]}
	 */
	function hydrateLemmas( lemmaInfo ) {
		var lemmas = [];
		$.each( lemmaInfo, function ( index, lemma ) {
			lemmas.push( new Lemma( lemma.value, lemma.language ) );
		} );
		return lemmas;
	}

	/**
	 * @tutorial Parameter is _not_ wikibase.lexeme.datamodel.Lexeme! See hydrateLexeme()
	 *
	 * @param {{lemmas: wikibase.lexeme.datamodel.Lemma[], lexicalCategory: string|null, language: string|null, id: string}} lexeme
	 */
	function init( lexeme ) {
		var repoApi = new wb.api.RepoApi( new mw.Api() );

		var baseRevId = mw.config.get( 'wgCurRevisionId' );

		var store = new Vuex.Store( newLexemeHeaderStore(
			repoApi,
			lexeme,
			baseRevId,
			$( '.language-lexical-category-widget_language' ).html(),
			$( '.language-lexical-category-widget_lexical-category' ).html()
		) );

		var lemmaWidget = newLemmaWidget( '#lemma-widget-vue-template', mw.messages );
		var languageAndLexicalCategoryWidget = newLanguageAndLexicalCategoryWidget(
			'#language-and-lexical-category-widget-vue-template',
			repoApi,
			mw.messages
		);

		var header = newLexemeHeader(
			store,
			'#wb-lexeme-header',
			'#lexeme-header-widget-vue-template',
			lemmaWidget,
			languageAndLexicalCategoryWidget,
			mw.messages
		);

		header.methods.displayError = function ( error ) {
			var $saveButton = $( this.$el.querySelector( '.lemma-widget_save' ) );

			$saveButton.wbtooltip( {
				content: {
					code: error.code,
					message: error.info
				},
				permanent: true
			} );
			$saveButton.data( 'wbtooltip' ).show();
		};

		var app = new Vue( header );
	}

	return function () {
		$.Deferred( function ( deferred ) {
			mw.hook( 'wikibase.entityPage.entityLoaded' ).add( function ( wbEntity ) {
				deferred.resolve( hydrateLexeme( wbEntity ) );
			} );
		} )
			.then( init )
			.fail( function ( reason ) {
				// FIXME: Change to lexeme-extension-specific logger once defined
				mw.log.error( 'LexemeHeader could not be initialized from wikibase.entityPage.entityLoaded', reason );
			} )
		;
	};

} )( jQuery, mediaWiki, require, wikibase, Vue, Vuex );
