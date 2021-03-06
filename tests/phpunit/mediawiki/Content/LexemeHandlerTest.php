<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\Content;

use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Entity\EntityId;
use Wikibase\DataModel\Entity\EntityIdParser;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Services\Lookup\EntityLookup;
use Wikibase\DataModel\Services\Lookup\LabelDescriptionLookup;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\Content\LexemeContent;
use Wikibase\Lexeme\Search\LexemeFieldDefinitions;
use Wikibase\Lib\EntityTypeDefinitions;
use Wikibase\Lib\Store\EntityContentDataCodec;
use Wikibase\Lib\Store\LanguageFallbackLabelDescriptionLookupFactory;
use Wikibase\Lexeme\Content\LexemeHandler;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Repo\Content\EntityHandler;
use Wikibase\Repo\Search\Elastic\Fields\StatementProviderFieldDefinitions;
use Wikibase\Repo\Tests\Content\EntityHandlerTestCase;
use Wikibase\Repo\Validators\EntityConstraintProvider;
use Wikibase\Repo\Validators\ValidatorErrorLocalizer;
use Wikibase\Repo\WikibaseRepo;
use Wikibase\SettingsArray;
use Wikibase\Store\EntityIdLookup;
use Wikibase\TermIndex;

/**
 * @covers \Wikibase\Lexeme\Content\LexemeHandler
 *
 * @license GPL-2.0-or-later
 * @author Bene* < benestar.wikimedia@gmail.com >
 */
class LexemeHandlerTest extends EntityHandlerTestCase {

	/**
	 * @return string
	 */
	public function getModelId() {
		return LexemeContent::CONTENT_MODEL_ID;
	}

	/**
	 * @param SettingsArray|null $settings
	 *
	 * @return EntityHandler
	 */
	protected function getHandler( SettingsArray $settings = null ) {
		return $this->getWikibaseRepo( $settings )
			->getEntityContentFactory()
			->getContentHandlerForType( Lexeme::ENTITY_TYPE );
	}

	/**
	 * @param EntityId|null $id
	 *
	 * @return EntityDocument
	 */
	protected function newEntity( EntityId $id = null ) {
		if ( !$id ) {
			$id = new LexemeId( 'L7' );
		}

		$lexeme = new Lexeme( $id );
		$lexeme->setLemmas(
			new TermList(
				[
					new Term( 'en', 'goat' ),
					new Term( 'de', 'Ziege' ),
				]
			)
		);
		$lexeme->setLanguage( new ItemId( 'Q123' ) );
		$lexeme->setLexicalCategory( new ItemId( 'Q567' ) );

		return $lexeme;
	}

	/**
	 * Returns EntityContents that can be serialized by the EntityHandler deriving class.
	 *
	 * @return array[]
	 */
	public function contentProvider() {
		$content = $this->newEntityContent();

		return [
			[ $content ],
		];
	}

	/**
	 * @return array
	 */
	public function entityIdProvider() {
		return [
			[ 'L7' ],
		];
	}

	/**
	 * @return array
	 */
	protected function getExpectedSearchIndexFields() {
		return [ 'statement_count' ];
	}

	/**
	 * @return LexemeContent
	 */
	protected function getTestContent() {
		return $this->newEntityContent();
	}

	protected function getEntityTypeDefinitions() {
		return new EntityTypeDefinitions(
			require __DIR__ . '/../../../../WikibaseLexeme.entitytypes.php'
		);
	}

	protected function getEntitySerializer() {
		$baseModelSerializerFactory = WikibaseRepo::getDefaultInstance()
			->getBaseDataModelSerializerFactory();
		$entityTypeDefinitions = $this->getEntityTypeDefinitions();
		$serializerFactoryCallbacks = $entityTypeDefinitions->getSerializerFactoryCallbacks();
		return $serializerFactoryCallbacks['lexeme']( $baseModelSerializerFactory );
	}

	private function getMockWithoutConstructor( $className ) {
		return $this->getMockBuilder( $className )
			->disableOriginalConstructor()
			->getMock();
	}

	private function newLexemeHandler() {
		$labelLookupFactory = $this->getMockWithoutConstructor(
			LanguageFallbackLabelDescriptionLookupFactory::class
		);
		$labelLookupFactory->method( 'newLabelDescriptionLookup' )
			->will( $this->returnValue( $this->getMock( LabelDescriptionLookup::class ) ) );

		$fieldDefinitions = new LexemeFieldDefinitions(
			new StatementProviderFieldDefinitions( [], [] ),
			$this->getMock( EntityLookup::class ),
			new PropertyId( 'P123' )
		);

		return new LexemeHandler(
			$this->getMock( TermIndex::class ),
			$this->getMockWithoutConstructor( EntityContentDataCodec::class ),
			$this->getMockWithoutConstructor( EntityConstraintProvider::class ),
			$this->getMock( ValidatorErrorLocalizer::class ),
			$this->getMock( EntityIdParser::class ),
			$this->getMock( EntityIdLookup::class ),
			$this->getMock( EntityLookup::class ),
			$labelLookupFactory,
			$fieldDefinitions
		);
	}

	public function testAllowAutomaticIds() {
		$lexemeHandler = $this->newLexemeHandler();

		$this->assertTrue( $lexemeHandler->allowAutomaticIds() );
	}

	public function testCanCreateWithCustomId() {
		$lexemeHandler = $this->newLexemeHandler();

		$this->assertFalse( $lexemeHandler->canCreateWithCustomId( new LexemeId( 'L1' ) ) );
	}

	public function testDataForSearchIndex() {
		$handler = $this->getHandler();
		$engine = $this->getMock( \SearchEngine::class );

		$page = $this->getMockWikiPage( $handler );

		// TODO: test with statements!
		$data = $handler->getDataForSearchIndex( $page, new \ParserOutput(), $engine );
		$this->assertSame( 0, $data['statement_count'], 'statement_count' );
	}

	public function testExportTransform( $blob = null, $expected = null ) {
		$this->markTestSkipped( 'serialized data transformation issues are irrelevant to Lexemes' );
	}

}
