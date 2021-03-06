<?php

namespace Wikibase\Lexeme\Tests\DataModel;

use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Snak\Snak;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataModel\Form;
use Wikibase\Lexeme\DataModel\FormSet;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\LexemeId;
use Wikibase\Lexeme\DataModel\Sense;

/**
 * @method static NewLexeme havingId(LexemeId | string $lexemeId)
 */
class NewLexeme {

	/**
	 * @var ItemId
	 */
	private $lexicalCategory;

	/**
	 * @var ItemId
	 */
	private $language;

	/**
	 * @var LexemeId|null
	 */
	private $lexemeId;

	/**
	 * @var Snak[]
	 */
	private $statements = [];

	/**
	 * @var string[] Lemmas indexed by language
	 */
	private $lemmas = [];

	/**
	 * @var Sense[]
	 */
	private $senses = [];

	/**
	 * @var Form[]
	 */
	private $forms = [];

	public static function create() {
		return new self();
	}

	/**
	 * @param Form|NewForm $form
	 *
	 * @return self
	 */
	public static function havingForm( $form ) {
		$result = new self();
		return $result->withForm( $form );
	}

	public function __construct() {
		$this->lexicalCategory = $this->newRandomItemId();
		$this->language = $this->newRandomItemId();
	}

	/**
	 * @return Lexeme
	 */
	public function build() {
		$forms = new FormSet( $this->forms );
		$nextFormId = $forms->maxFormIdNumber() + 1;

		$lemmas = new TermList();
		foreach ( $this->lemmas as $lang => $term ) {
			$lemmas->setTextForLanguage( $lang, $term );
		}

		if ( $lemmas->isEmpty() ) {
			$lemmas->setTextForLanguage(
				$this->newRandomLanguageCode(),
				$this->newRandomLemma()
			);
		}

		$lexeme = new Lexeme(
			$this->lexemeId,
			$lemmas,
			$this->lexicalCategory,
			$this->language,
			null,
			$nextFormId,
			$forms,
			$this->senses
		);

		foreach ( $this->statements as $statement ) {
			$lexeme->getStatements()->addNewStatement( $statement );
		}

		return $lexeme;
	}

	/**
	 * @param ItemId|string $itemId
	 *
	 * @return self
	 */
	public function withLexicalCategory( $itemId ) {
		$result = clone $this;
		if ( !$itemId instanceof ItemId ) {
			$itemId = new ItemId( $itemId );
		}
		$result->lexicalCategory = $itemId;
		return $result;
	}

	/**
	 * @param ItemId|string $itemId
	 *
	 * @return self
	 */
	public function withLanguage( $itemId ) {
		$result = clone $this;
		if ( !$itemId instanceof ItemId ) {
			$itemId = new ItemId( $itemId );
		}
		$result->language = $itemId;
		return $result;
	}

	/**
	 * @param LexemeId|string $lexemeId
	 *
	 * @return self
	 */
	public function withId( $lexemeId ) {
		$result = clone $this;
		if ( !$lexemeId instanceof LexemeId ) {
			$lexemeId = new LexemeId( $lexemeId );
		}
		$result->lexemeId = $lexemeId;
		return $result;
	}

	public function withStatement( Snak $statement ) {
		$result = clone $this;
		$result->statements[] = clone $statement;
		return $result;
	}

	/**
	 * @param string $language
	 * @param string $lemma
	 *
	 * @return self
	 */
	public function withLemma( $language, $lemma ) {
		$result = clone $this;
		$result->lemmas[$language] = $lemma;
		return $result;
	}

	private function newRandomItemId() {
		return new ItemId( 'Q' . mt_rand( 1, ItemId::MAX ) );
	}

	private function newRandomLanguageCode() {
		return $this->newRandomString( 2 );
	}

	private function newRandomLemma() {
		return $this->newRandomString( mt_rand( 5, 10 ) );
	}

	private function newRandomString( $length ) {
		$characters = 'abcdefghijklmnopqrstuvwxyz';

		return substr( str_shuffle( $characters ), 0, $length );
	}

	public function __clone() {
		$this->statements = $this->cloneArrayOfObjects( $this->statements );
		$this->forms = $this->cloneArrayOfObjects( $this->forms );
	}

	/**
	 * @param Sense|NewSense $sense
	 *
	 * @return self
	 */
	public function withSense( $sense ) {
		$result = clone $this;

		if ( $sense instanceof NewSense ) {
			$sense = $sense->build();
		} elseif ( !$sense instanceof Sense ) {
			throw new \InvalidArgumentException( '$sense has incorrect type' );
		}

		$result->senses[] = $sense;
		return $result;
	}

	/**
	 * @param Form|NewForm $form
	 *
	 * @return self
	 */
	public function withForm( $form ) {
		$result = clone $this;

		if ( $form instanceof NewForm ) {
			$form = $form->build();
		}

		$result->forms[] = $form;

		return $result;
	}

	/**
	 * @param string $name
	 * @param array $arguments
	 *
	 * @return self
	 */
	public static function __callStatic( $name, $arguments ) {
		$result = new self();
		$methodName = str_replace( 'having', 'with', $name );
		return call_user_func_array( [ $result, $methodName ], $arguments );
	}

	/**
	 * @param object[] $objects
	 *
	 * @return object[]
	 */
	private function cloneArrayOfObjects( array $objects ) {
		$result = [];
		foreach ( $objects as $object ) {
			$result[] = clone $object;
		}
		return $result;
	}

}
