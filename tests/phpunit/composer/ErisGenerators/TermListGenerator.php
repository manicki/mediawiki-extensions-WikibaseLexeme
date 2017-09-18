<?php

namespace Wikibase\Lexeme\Tests\ErisGenerators;

use Eris\Generator;
use Eris\Generator\GeneratedValueSingle;
use Wikibase\DataModel\Term\Term;
use Wikibase\DataModel\Term\TermList;

/**
 * @license GPL-2.0+
 */
class TermListGenerator implements Generator {

	/**
	 * @var Generator
	 */
	private $termGenerator;

	/**
	 * @var int
	 */
	private $minimalSize;

	public function __construct( $minimalSize = 0 ) {
		$this->termGenerator = new TermGenerator();
		$this->minimalSize = $minimalSize;
	}

	/**
	 * @see Generator::__invoke
	 *
	 * @param int $size
	 * @param callable $rand
	 *
	 * @return GeneratedValueSingle<T>
	 */
	public function __invoke( $size, $rand ) {
		$generateTerm = $this->termGenerator;

		$size = max( $this->minimalSize, $size );
		$listSize = $rand( $this->minimalSize, $size );

		$result = new TermList( [] );

		$trials = 0;
		$maxTrials = 2 * $listSize;
		while ( $result->count() < $listSize && $trials < $maxTrials ) {
			$trials++;
			/** @var Term $term */
			$term = $generateTerm( $size, $rand )->unbox();
			if ( $result->hasTermForLanguage( $term->getLanguageCode() ) ) {
				continue;
			}

			$result->setTerm( $term );
		}

		return GeneratedValueSingle::fromJustValue( $result, 'TermList' );
	}

	/**
	 * @see Generator::shrink
	 *
	 * @param GeneratedValueSingle<T> $element
	 *
	 * @return GeneratedValueSingle<T>|GeneratedValueOptions<T>
	 */
	public function shrink( GeneratedValueSingle $element ) {
		/** @var TermList $termList */
		$termList = $element->unbox();

		if ( $termList->count() === 0 ) {
			return $element;
		} elseif ( $termList->count() === 1 ) {
			return GeneratedValueSingle::fromValueAndInput(
				new TermList( [] ),
				$element,
				'TermList'
			);
		} else {

			$terms = iterator_to_array( $termList );

			$splitIndex = (int)( count( $terms ) / 2 );
			$terms1 = array_slice( $terms, 0, $splitIndex );
			$terms2 = array_slice( $terms, $splitIndex );

			$shrunk1 = new TermList( $terms1 );
			$shrunk2 = new TermList( $terms2 );

			array_pop( $terms );
			$shrunkOneLess = new TermList( $terms );

			return new Generator\GeneratedValueOptions( [
				GeneratedValueSingle::fromValueAndInput( $shrunk1, $element, 'TermList' ),
				GeneratedValueSingle::fromValueAndInput( $shrunk2, $element, 'TermList' ),
				GeneratedValueSingle::fromValueAndInput( $shrunkOneLess, $element, 'TermList' ),
			] );
		}
	}

	/**
	 * @param GeneratedValueSingle $element
	 *
	 * @return bool
	 */
	public function contains( GeneratedValueSingle $element ) {
		return $element->unbox() instanceof TermList;
	}

}
