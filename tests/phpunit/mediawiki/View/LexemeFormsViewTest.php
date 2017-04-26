<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\View;

use PHPUnit_Framework_TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\Lexeme\DataModel\LexemeForm;
use Wikibase\Lexeme\DataModel\LexemeFormId;
use Wikibase\Lexeme\View\LexemeFormsView;
use Wikibase\Lexeme\View\Template\LexemeTemplateFactory;
use Wikibase\Lib\EntityIdHtmlLinkFormatter;
use Wikibase\Lib\LanguageNameLookup;
use Wikibase\Lib\Store\EntityTitleLookup;
use Wikibase\View\DummyLocalizedTextProvider;

/**
 * @covers Wikibase\Lexeme\View\LexemeFormsView
 *
 * @group WikibaseLexeme
 *
 * @license GPL-2.0+
 * @author Thiemo Mättig
 */
class LexemeFormsViewTest extends PHPUnit_Framework_TestCase {

	public function testHtmlContainsTheFormsHeadline() {
		$view = $this->newFormsView();
		$html = $view->getHtml( [] );

		assertThat(
			$html,
			is( htmlPiece( havingChild(
				both( withTagName( 'h2' ) )
					->andAlso( havingChild(
						both( withAttribute( 'id' )->havingValue( 'forms' ) )
							->andAlso( havingTextContents( '(wikibase-lexeme-view-forms)' ) )
					) )
			) ) )
		);
	}

	public function testHtmlContainsFormsContainer() {
		$view = $this->newFormsView();
		$html = $view->getHtml( [] );

		assertThat(
			$html,
			is( htmlPiece( havingChild( tagMatchingOutline(
				'<div class="wikibase-lexeme-forms">'
			) ) ) )
		);
	}

	public function testHtmlContainsFormRepresentationWithIdAndLanguage() {
		$view = $this->newFormsView();
		$html = $view->getHtml( [
			new LexemeForm( new LexemeFormId( 'FORM_ID' ), 'FORM_REPRESENTATION', [] )
		] );

		assertThat(
			$html,
			is( htmlPiece( havingChild(
				both( tagMatchingOutline( '<h3 lang="some language">' ) )
					->andAlso( havingTextContents( containsString( 'FORM_REPRESENTATION (FORM_ID)' ) ) )
			) ) )
		);
	}

	public function testHtmlContainsFormGrammaticalFeatures() {
		$view = $this->newFormsView();
		$grammaticalFeature = new ItemId( 'Q1' );
		$lexemeForm = new LexemeForm(
			new LexemeFormId( 'FORM_ID' ),
			'FORM_REPRESENTATION',
			[ $grammaticalFeature ]
		);

		$html = $view->getHtml( [ $lexemeForm ] );

		assertThat(
			$html,
			is( htmlPiece( havingChild( havingTextContents( containsString( 'Q1' ) ) ) ) )
		);
	}

	private function newFormsView() {
		return new LexemeFormsView(
			new DummyLocalizedTextProvider(),
			new LexemeTemplateFactory( [
				'wikibase-lexeme-form' => '<h3 lang="$1">$2 $3 $4</h3>',
				'wikibase-lexeme-form-id' => '$1',
			] ),
			new EntityIdHtmlLinkFormatter(
				$this->getMock( LabelDescriptionLookup::class ),
				$this->getMock( EntityTitleLookup::class ),
				$this->getMock( LanguageNameLookup::class )
			)
		);
	}

}
