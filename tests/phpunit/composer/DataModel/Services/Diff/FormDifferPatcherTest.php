<?php

namespace Wikibase\Lexeme\Tests\DataModel\Services\Diff;

use PHPUnit\Framework\TestCase;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Entity\PropertyId;
use Wikibase\DataModel\Snak\PropertySomeValueSnak;
use Wikibase\DataModel\Statement\Statement;
use Wikibase\Lexeme\DataModel\Form;
use Wikibase\Lexeme\DataModel\FormId;
use Wikibase\Lexeme\DataModel\Services\Diff\FormDiffer;
use Wikibase\Lexeme\DataModel\Services\Diff\FormPatcher;
use Wikibase\Lexeme\Tests\DataModel\NewForm;
use Wikibase\Lexeme\Tests\ErisGenerators\ErisTest;
use Wikibase\Lexeme\Tests\ErisGenerators\WikibaseLexemeGenerators;

/**
 * @covers \Wikibase\Lexeme\DataModel\Services\Diff\FormDiffer
 * @covers \Wikibase\Lexeme\DataModel\Services\Diff\FormPatcher
 *
 * @license GPL-2.0-or-later
 */
class FormDifferPatcherTest extends TestCase {

	use ErisTest;

	public function testProperty_PatchingLexemeWithGeneratedDiffAlwaysRestoresItToTheTargetState() {
		$differ = new FormDiffer();
		$patcher = new FormPatcher();

		//Line below is needed to reproduce failures. In case of failure seed will be in the output
		//$this->eris()->seed(1504876177284329)->forAll( ...

		$this->eris()
			->forAll(
			WikibaseLexemeGenerators::form( new FormId( 'L1-F1' ) ),
			WikibaseLexemeGenerators::form( new FormId( 'L1-F1' ) )
		)
			->then( function ( Form $form1, Form $form2 ) use ( $differ, $patcher ) {
				$patch = $differ->diff( $form1, $form2 );
				$patcher->patch( $form1, $patch );

				$this->assertEquals( $form1, $form2 );
			} );
	}

	public function testDiffAndPatchCanChangeRepresentations() {
		$differ = new FormDiffer();
		$patcher = new FormPatcher();
		$form1 = NewForm::havingId( 'F1' )
			->andRepresentation( 'en', 'cat' )
			->build();
		$form2 = NewForm::havingId( 'F1' )
			->andRepresentation( 'en', 'goat' )
			->build();

		$diff = $differ->diff( $form1, $form2 );
		$patcher->patch( $form1, $diff );

		$this->assertEquals( $form2, $form1 );
	}

	public function testDiffAndPatchCanAtomicallyChangeRepresentations() {
		$differ = new FormDiffer();
		$patcher = new FormPatcher();
		$form1 = NewForm::havingId( 'F1' )
			->andRepresentation( 'en', 'en-value' )
			->build();
		$form2 = NewForm::havingId( 'F1' )
			->andRepresentation( 'en', 'en-value' )
			->andRepresentation( 'fr', 'fr-value' )
			->build();
		$latestForm = NewForm::havingId( 'F1' )
			->andRepresentation( 'de', 'de-value' )
			->build();

		$diff = $differ->diff( $form1, $form2 );
		$patcher->patch( $latestForm, $diff );

		$this->assertEquals(
			'fr-value',
			$latestForm->getRepresentations()->getByLanguage( 'fr' )->getText()
		);
		$this->assertEquals(
			'de-value',
			$latestForm->getRepresentations()->getByLanguage( 'de' )->getText()
		);
	}

	public function testDiffAndPatchCanAtomicallyChangeGrammaticalFeatures() {
		$differ = new FormDiffer();
		$patcher = new FormPatcher();
		$form1 = NewForm::havingId( 'F1' )
			->andGrammaticalFeature( 'Q1' )
			->build();
		$form2 = NewForm::havingId( 'F1' )
			->andGrammaticalFeature( 'Q1' )
			->andGrammaticalFeature( 'Q2' )
			->build();
		$latestForm = NewForm::havingId( 'F1' )
			->andGrammaticalFeature( 'Q3' )
			->build();

		$diff = $differ->diff( $form1, $form2 );
		$patcher->patch( $latestForm, $diff );

		$this->assertHasGrammaticalFeature( new ItemId( 'Q3' ), $latestForm );
		$this->assertHasGrammaticalFeature( new ItemId( 'Q2' ), $latestForm );
		$this->assertDoentHaveGrammaticalFeature( new ItemId( 'Q1' ), $latestForm );
	}

	public function testDiffAndPatchCanChangeStatements() {
		$differ = new FormDiffer();
		$patcher = new FormPatcher();
		$form1 = NewForm::havingId( 'F1' )
			->andStatement( $this->someStatement( 'P1', 'guid1' ) )
			->build();
		$form2 = NewForm::havingId( 'F1' )
			->andStatement( $this->someStatement( 'P1', 'guid1' ) )
			->andStatement( $this->someStatement( 'P2', 'guid2' ) )
			->build();
		$latestForm = NewForm::havingId( 'F1' )
			->andStatement( $this->someStatement( 'P3', 'guid3' ) )
			->build();

		$diff = $differ->diff( $form1, $form2 );
		$patcher->patch( $latestForm, $diff );

		$this->assertNotNull( $latestForm->getStatements()->getFirstStatementWithGuid( 'guid3' ) );
		$this->assertNotNull( $latestForm->getStatements()->getFirstStatementWithGuid( 'guid2' ) );
		$this->assertNull( $latestForm->getStatements()->getFirstStatementWithGuid( 'guid1' ) );
	}

	public function testPatchDoesNotComplainWhenCantRemoveGrammaticalFeature() {
		$differ = new FormDiffer();
		$patcher = new FormPatcher();
		$form1 = NewForm::havingId( 'F1' )
			->andGrammaticalFeature( 'Q1' )
			->build();
		$form2 = NewForm::havingId( 'F1' )
			->build();
		$formWithoutGrammaticalFeature = NewForm::havingId( 'F1' )
			->build();

		$diff = $differ->diff( $form1, $form2 );
		$patcher->patch( $formWithoutGrammaticalFeature, $diff );

		$this->assertEquals( [], $formWithoutGrammaticalFeature->getGrammaticalFeatures() );
	}

	public function testDiffPatchCanRemoveGrammaticalFeature() {
		$differ = new FormDiffer();
		$patcher = new FormPatcher();
		$form1 = NewForm::havingId( 'F1' )
			->andGrammaticalFeature( 'Q1' )
			->build();
		$form2 = NewForm::havingId( 'F1' )
			->build();

		$diff = $differ->diff( $form1, $form2 );
		$patcher->patch( $form1, $diff );

		$this->assertEquals( [], $form1->getGrammaticalFeatures() );
	}

	private function assertHasGrammaticalFeature( ItemId $gf, Form $form ) {
		$this->assertContains(
			$gf,
			$form->getGrammaticalFeatures(),
			"Expected to have grammatical feature {$gf->getSerialization()}"
			. " but doesn't",
			false,
			false
		);
	}

	private function assertDoentHaveGrammaticalFeature( ItemId $gf, Form $form ) {
		$this->assertNotContains(
			$gf,
			$form->getGrammaticalFeatures(),
			"Expected not to have grammatical feature {$gf->getSerialization()}"
			. " but has",
			false,
			false
		);
	}

	/**
	 * @return mixed
	 */
	private function someStatement( $propertyId, $guid ) {
		$statement = new Statement(
			new PropertySomeValueSnak( new PropertyId( $propertyId ) )
		);
		$statement->setGuid( $guid );
		return $statement;
	}

}
