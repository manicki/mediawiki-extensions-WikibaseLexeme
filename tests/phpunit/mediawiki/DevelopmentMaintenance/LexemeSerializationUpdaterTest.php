<?php

namespace Wikibase\Lexeme\Tests\MediaWiki\DevelopmentMaintenance;

use PHPUnit\Framework\TestCase;
use Wikibase\Lexeme\DevelopmentMaintenance\LexemeSerializationUpdater;
use Wikimedia\Rdbms\FakeResultWrapper;
use Wikimedia\Rdbms\IDatabase;

/**
 * @covers Wikibase\Lexeme\DevelopmentMaintenance\LexemeSerializationUpdater
 *
 * @license GPL-2.0-or-later
 */
class LexemeSerializationUpdaterTest extends TestCase {

	public function testGivenLexemeDataWithoutFormsField_fieldIsAdded() {
		$db = $this->getDB( [ $this->getLexemeDataWithNoFormsField() ] );

		$updater = $this->newUpdater( $db );

		$updater->update();

		$updatedData = $db->getUpdateData();

		$this->assertCount( 1, $updatedData );
		$this->assertEquals( [ 'old_id' => 1 ], $updatedData[0]['conds'] );
		$this->assertEquals(
			$this->getLexemeDataWithNextFormIdButNoForms(),
			json_decode( $updatedData[0]['new']['old_text'], true )
		);
	}

	public function testGivenLexemeDataWithoutNextFormIdField_fieldIsAdded() {
		$db = $this->getDB( [ $this->getLexemeDataWithFormsAndNoNextFormId() ] );

		$updater = $this->newUpdater( $db );

		$updater->update();

		$updatedData = $db->getUpdateData();

		$this->assertCount( 1, $updatedData );
		$this->assertEquals( [ 'old_id' => 1 ], $updatedData[0]['conds'] );
		$this->assertEquals(
			$this->getLexemeDataWithNextFormId(),
			json_decode( $updatedData[0]['new']['old_text'], true )
		);
	}

	public function testGivenLexemeDataWithNoFormsAndNoNextFormIdField_fieldIsAdded() {
		$db = $this->getDB( [ $this->getLexemeDataWithNoFormsAndNoNextFormId() ] );

		$updater = $this->newUpdater( $db );

		$updater->update();

		$updatedData = $db->getUpdateData();

		$this->assertCount( 1, $updatedData );
		$this->assertEquals(
			$this->getLexemeDataWithNextFormIdButNoForms(),
			json_decode( $updatedData[0]['new']['old_text'], true )
		);
	}

	public function testGivenLexemeDataWithFormIdsMissingLexemeId_formIdsArePrepended() {
		$lexemeDataWithFormIdsPrependedWithLexemeId = $this->getLexemeDataWithNextFormId();

		$db = $this->getDB( [ $this->getLexemeDataWithFormIdsMissingLexemeIdPart() ] );

		$updater = $this->newUpdater( $db );

		$updater->update();

		$updatedData = $db->getUpdateData();

		$this->assertCount( 1, $updatedData );
		$this->assertEquals( [ 'old_id' => 1 ], $updatedData[0]['conds'] );
		$this->assertEquals(
			$lexemeDataWithFormIdsPrependedWithLexemeId,
			json_decode( $updatedData[0]['new']['old_text'], true )
		);
	}

	public function testGivenLexemeDataWithFormIdsContainingWrongLexemeId_formIdsArePrepended() {
		$lexemeDataWithFormIdsPrependedWithLexemeId = $this->getLexemeDataWithNextFormId();

		$db = $this->getDB( [ $this->getLexemeDataWithFormIdsContainingWrongLexemeIdPart() ] );

		$updater = $this->newUpdater( $db );

		$updater->update();

		$updatedData = $db->getUpdateData();

		$this->assertCount( 1, $updatedData );
		$this->assertEquals( [ 'old_id' => 1 ], $updatedData[0]['conds'] );
		$this->assertEquals(
			$lexemeDataWithFormIdsPrependedWithLexemeId,
			json_decode( $updatedData[0]['new']['old_text'], true )
		);
	}

	public function testGivenLexemeDataWithInvalidFormStatementId_statementIdsIsUpdated() {
		$lexemeDataWithFormIdsPrependedWithLexemeId = $this->getLexemeDataWithStatementOnForm();

		$db = $this->getDB( [ $this->getLexemeDataWithFormStatementIdNotContainingFormId() ] );

		$updater = $this->newUpdater( $db );

		$updater->update();

		$updatedData = $db->getUpdateData();

		$this->assertCount( 1, $updatedData );
		$this->assertEquals( [ 'old_id' => 1 ], $updatedData[0]['conds'] );
		$this->assertEquals(
			$lexemeDataWithFormIdsPrependedWithLexemeId,
			json_decode( $updatedData[0]['new']['old_text'], true )
		);
	}

	public function testGivenLexemeDataStructureIsUpToDate_noUpdateDone() {
		$db = $this->getDB( [ $this->getLexemeDataWithNextFormId() ] );

		$updater = $this->newUpdater( $db );

		$updater->update();

		$updatedData = $db->getUpdateData();

		$this->assertEmpty( $updatedData );
	}

	public function testGivenLexemeDataWithoutSensesField_fieldIsAdded() {
		$db = $this->getDB( [ $this->getLexemeDataWithNoSensesField() ] );

		$updater = $this->newUpdater( $db );

		$updater->update();

		$updatedData = $db->getUpdateData();

		$this->assertCount( 1, $updatedData );
		$this->assertEquals( [ 'old_id' => 1 ], $updatedData[0]['conds'] );
		$this->assertEquals(
			$this->getLexemeDataWithNextFormIdButNoForms(),
			json_decode( $updatedData[0]['new']['old_text'], true )
		);
	}

	private function getLexemeDataWithFormsAndNoNextFormId() {
		return [
			'id' => 'L1',
			'type' => 'lexeme',
			'lemmas' => [
				[ 'en' => [ 'language' => 'en', 'value' => 'goat' ] ],
			],
			'language' => 'Q1',
			'lexicalCategory' => 'Q2',
			'claims' => [],
			'forms' => [
				[
					'id' => 'L1-F2',
					'representations' => [
						[ 'en' => [ 'language' => 'en', 'value' => 'goat' ] ],
					],
					'grammaticalFeatures' => 'Q3',
					'claims' => [],
				]
			],
			'senses' => [],
		];
	}

	private function getLexemeDataWithNoFormsAndNoNextFormId() {
		$data = $this->getLexemeDataWithFormsAndNoNextFormId();

		$data['forms'] = [];

		return $data;
	}

	private function getLexemeDataWithNoFormsField() {
		$data = $this->getLexemeDataWithNextFormIdButNoForms();

		unset( $data['forms'] );

		return $data;
	}

	private function getLexemeDataWithNoSensesField() {
		$data = $this->getLexemeDataWithNextFormIdButNoForms();

		unset( $data['senses'] );

		return $data;
	}

	private function getLexemeDataWithNextFormId() {
		return [
			'id' => 'L1',
			'type' => 'lexeme',
			'lemmas' => [
				[ 'en' => [ 'language' => 'en', 'value' => 'goat' ] ],
			],
			'language' => 'Q1',
			'lexicalCategory' => 'Q2',
			'claims' => [],
			'nextFormId' => 3,
			'forms' => [
				[
					'id' => 'L1-F2',
					'representations' => [
						[ 'en' => [ 'language' => 'en', 'value' => 'goat' ] ],
					],
					'grammaticalFeatures' => 'Q3',
					'claims' => [],
				]
			],
			'senses' => [],
		];
	}

	private function getLexemeDataWithNextFormIdButNoForms() {
		$data = $this->getLexemeDataWithNextFormId();

		$data['forms'] = [];
		$data['nextFormId'] = 1;

		return $data;
	}

	private function getLexemeDataWithFormIdsMissingLexemeIdPart() {
		$data = $this->getLexemeDataWithNextFormId();

		$data['forms'][0]['id'] = 'F2';

		return $data;
	}

	private function getLexemeDataWithFormIdsContainingWrongLexemeIdPart() {
		$data = $this->getLexemeDataWithNextFormId();

		$data['forms'][0]['id'] = 'L666-F2';

		return $data;
	}

	private function getLexemeDataWithStatementOnForm() {
		return [
			'id' => 'L1',
			'type' => 'lexeme',
			'lemmas' => [
				[ 'en' => [ 'language' => 'en', 'value' => 'goat' ] ],
			],
			'language' => 'Q1',
			'lexicalCategory' => 'Q2',
			'claims' => [],
			'nextFormId' => 3,
			'forms' => [
				[
					'id' => 'L1-F2',
					'representations' => [
						[ 'en' => [ 'language' => 'en', 'value' => 'goat' ] ],
					],
					'grammaticalFeatures' => 'Q3',
					'claims' => [
						'P1' => [ [
							'mainsnak' => [
								'snaktype' => 'value',
								'property' => 'P1',
								'hash' => '0123456789012345678901234567890123456789',
								'datavalue' => [
									'value' => 'turbo goat',
									'type' => 'string',
								],
							],
							'type' => 'statement',
							'id' => 'L1-F2$abcdef01-0123-3210-6666-0123456789abc',
							'rank' => 'normal',
						] ]
					],
				]
			],
			'senses' => [],
		];
	}

	private function getLexemeDataWithFormStatementIdNotContainingFormId() {
		$data = $this->getLexemeDataWithStatementOnForm();

		$data['forms'][0]['claims']['P1'][0]['id'] = 'P1$abcdef01-0123-3210-6666-0123456789abc';

		return $data;
	}

	private function newUpdater( IDatabase $db ) {
		return new LexemeSerializationUpdater( $db, 666 );
	}

	private function getDB( array $data ) {
		$selectReturnRows = [];

		foreach ( $data as $index => $entry ) {
			$selectReturnRows[] = (object)[
				'page_title' => 'L' . $index,
				'rev_id' => $index + 1,
				'old_id' => $index + 1,
				'old_text' => json_encode( $entry ),
			];
		}

		return $this->getMockForAbstractClass(
			DatabaseSpy::class,
			[
				new FakeResultWrapper( $selectReturnRows ),
				1
			]
		);
	}

}
