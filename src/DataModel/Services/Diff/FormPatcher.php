<?php

namespace Wikibase\Lexeme\DataModel\Services\Diff;

use Diff\Patcher\ListPatcher;
use InvalidArgumentException;
use Wikibase\DataModel\Entity\EntityDocument;
use Wikibase\DataModel\Services\Diff\EntityDiff;
use Wikibase\DataModel\Services\Diff\EntityPatcherStrategy;
use Wikibase\DataModel\Services\Diff\StatementListPatcher;
use Wikibase\DataModel\Services\Diff\TermListPatcher;
use Wikibase\Lexeme\DataModel\Form;

/**
 * @license GPL-2.0+
 */
class FormPatcher implements EntityPatcherStrategy {

	private $termListPatcher;

	private $statementListPatcher;

	private $listPatcher;

	public function __construct() {
		$this->termListPatcher = new TermListPatcher();
		$this->statementListPatcher = new StatementListPatcher();
		$this->listPatcher = new ListPatcher();
	}

	/**
	 * @param string $entityType
	 *
	 * @return boolean
	 */
	public function canPatchEntityType( $entityType ) {
		return $entityType === 'form';
	}

	/**
	 * @param EntityDocument $entity
	 * @param EntityDiff $patch
	 *
	 * @throws InvalidArgumentException
	 */
	public function patchEntity( EntityDocument $entity, EntityDiff $patch ) {
		if ( !( $entity instanceof Form ) ) {
			throw new InvalidArgumentException( 'Can only patch Forms' );
		}

		return $this->patch( $entity, $patch );
	}

	/**
	 * @deprecated use self::patchEntity instead
	 *
	 * @param Form $form
	 * @param ChangeFormDiffOp $diff
	 */
	public function patch( Form $form, ChangeFormDiffOp $diff ) {
		$this->termListPatcher->patchTermList(
			$form->getRepresentations(),
			$diff->getRepresentationDiffOps()
		);
		$grammaticalFeatures = $form->getGrammaticalFeatures();
		$patchedGrammaticalFeatures = $this->listPatcher->patch(
			$grammaticalFeatures,
			$diff->getGrammaticalFeaturesDiffOps()
		);
		$form->setGrammaticalFeatures( $patchedGrammaticalFeatures );

		$this->statementListPatcher->patchStatementList(
			$form->getStatements(),
			$diff->getStatementsDiffOps()
		);
	}

}
