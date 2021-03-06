<?php

namespace Wikibase\Lexeme\Diff;

use Diff\DiffOp\Diff\Diff;
use Diff\DiffOp\DiffOp;
use Diff\DiffOp\DiffOpAdd;
use Diff\DiffOp\DiffOpChange;
use Diff\DiffOp\DiffOpRemove;
use MessageLocalizer;
use MWException;
use Wikibase\Lexeme\DataModel\Services\Diff\FormDiff;
use Wikibase\Repo\Diff\BasicDiffView;
use Wikibase\Repo\Diff\ClaimDiffer;
use Wikibase\Repo\Diff\ClaimDifferenceVisualizer;

/**
 * Class for generating views of DiffOp objects of forms.
 *
 * @license GPL-2.0-or-later
 */
class FormDiffView extends BasicDiffView {

	/**
	 * @var ClaimDiffer
	 */
	private $claimDiffer;

	/**
	 * @var ClaimDifferenceVisualizer
	 */
	private $claimDiffVisualizer;

	/**
	 * @var ItemReferenceDifferenceVisualizer
	 */
	private $itemReferenceDifferenceVisualizer;

	/**
	 * @var MessageLocalizer
	 */
	private $messageLocalizer;

	/**
	 * @param string[] $path
	 * @param Diff $diff
	 * @param ClaimDiffer $claimDiffer
	 * @param ClaimDifferenceVisualizer $claimDiffVisualizer
	 * @param MessageLocalizer $messageLocalizer
	 */
	public function __construct(
		array $path,
		Diff $diff,
		ClaimDiffer $claimDiffer,
		ClaimDifferenceVisualizer $claimDiffVisualizer,
		ItemReferenceDifferenceVisualizer $itemReferenceDifferenceVisualizer,
		MessageLocalizer $messageLocalizer
	) {
		parent::__construct( $path, $diff );

		$this->claimDiffer = $claimDiffer;
		$this->claimDiffVisualizer = $claimDiffVisualizer;
		$this->itemReferenceDifferenceVisualizer = $itemReferenceDifferenceVisualizer;
		$this->messageLocalizer = $messageLocalizer;
	}

	/**
	 * @param string[] $path
	 * @param DiffOp $op
	 *
	 * @return string HTML
	 */
	protected function generateOpHtml( array $path, DiffOp $op ) {
		if ( $op->isAtomic() ) {
			return parent::generateOpHtml( $path, $op );
		}

		$html = '';

		foreach ( $op as $key => $subOp ) {
			if ( $subOp instanceof FormDiff ) {
				$html .= $this->generateFormOpHtml( $path, $subOp, $key );
			} else {
				$html .= $this->generateOpHtml( array_merge( $path, [ $key ] ), $subOp );
			}
		}

		return $html;
	}

	private function generateFormOpHtml( array $path, FormDiff $op, $key ) {
		$html = '';

		$html .= parent::generateOpHtml(
			array_merge(
				$path,
				[ $key, $this->messageLocalizer->msg( 'wikibaselexeme-diffview-representation' )->text() ]
			),
			$op->getRepresentationDiff()
		);

		$html .= ( new GrammaticalFeatureDiffVisualizer(
			$this->itemReferenceDifferenceVisualizer
		) )->visualize(
			array_merge(
				$path,
				[
					$key,
					$this->messageLocalizer->msg( 'wikibaselexeme-diffview-grammatical-feature' )->text()
				]
			),
			$op->getGrammaticalFeaturesDiff()
		);

		foreach ( $op->getStatementsDiff() as $claimDiffOp ) {
			$html .= $this->getClaimDiffHtml(
				$claimDiffOp,
				array_merge( $path, [ $key ] )
			);
		}

		return $html;
	}

	/**
	 * @param DiffOp $diffOp
	 *
	 * @return string HTML
	 * @throws MWException
	 */
	private function getClaimDiffHtml( DiffOp $diffOp, array $path ) {
		switch ( true ) {
			case $diffOp instanceof DiffOpChange:
				return $this->claimDiffVisualizer->visualizeClaimChange(
					$this->claimDiffer->diffClaims(
						$diffOp->getOldValue(),
						$diffOp->getNewValue()
					),
					$diffOp->getNewValue(),
					$path
				);

			case $diffOp instanceof DiffOpAdd:
				return $this->claimDiffVisualizer->visualizeNewClaim( $diffOp->getNewValue(), $path );

			case $diffOp instanceof DiffOpRemove:
				return $this->claimDiffVisualizer->visualizeRemovedClaim( $diffOp->getOldValue(), $path );

			default:
				throw new MWException( 'Encountered an unexpected diff operation type for a claim' );
		}
	}

}
