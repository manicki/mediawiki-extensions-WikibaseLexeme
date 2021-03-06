<?php

namespace Wikibase\Lexeme\DataModel\Serialization;

use Deserializers\Deserializer;
use Deserializers\Exceptions\DeserializationException;
use Deserializers\TypedObjectDeserializer;
use Wikibase\DataModel\Deserializers\TermDeserializer;
use Wikibase\DataModel\Deserializers\TermListDeserializer;
use Wikibase\DataModel\Entity\ItemId;
use Wikibase\DataModel\Statement\StatementList;
use Wikibase\DataModel\Term\TermList;
use Wikibase\Lexeme\DataModel\FormSet;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\Form;
use Wikibase\Lexeme\DataModel\FormId;
use Wikibase\Lexeme\DataModel\LexemeId;

/**
 * @license GPL-2.0-or-later
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class LexemeDeserializer extends TypedObjectDeserializer {

	/**
	 * @var Deserializer
	 */
	private $entityIdDeserializer;

	/**
	 * @var Deserializer
	 */
	private $termListDeserializer;

	/**
	 * @var Deserializer
	 */
	private $statementListDeserializer;

	public function __construct(
		Deserializer $entityIdDeserializer,
		Deserializer $statementListDeserializer
	) {
		parent::__construct( 'lexeme', 'type' );

		$this->entityIdDeserializer = $entityIdDeserializer;
		$this->termListDeserializer = new TermListDeserializer( new TermDeserializer() );
		$this->statementListDeserializer = $statementListDeserializer;
	}

	/**
	 * @param array $serialization
	 *
	 * @throws DeserializationException
	 * @return Lexeme
	 */
	public function deserialize( $serialization ) {
		$this->assertCanDeserialize( $serialization );

		return new Lexeme(
			$this->deserializeId( $serialization ),
			$this->deserializeLemmas( $serialization ),
			$this->deserializeLexicalCategory( $serialization ),
			$this->deserializeLanguage( $serialization ),
			$this->deserializeStatements( $serialization ),
			$serialization['nextFormId'],
			$this->deserializeForms( $serialization )
		);
	}

	/**
	 * @param array $serialization
	 *
	 * @return LexemeId|null
	 */
	private function deserializeId( array $serialization ) {
		if ( array_key_exists( 'id', $serialization ) ) {
			return new LexemeId( $serialization['id'] );
		}

		return null;
	}

	/**
	 * @param array $serialization
	 *
	 * @return StatementList|null
	 */
	private function deserializeStatements( array $serialization ) {
		if ( array_key_exists( 'claims', $serialization ) ) {
			return $this->statementListDeserializer->deserialize( $serialization['claims'] );
		}

		return null;
	}

	/**
	 * @param array $serialization
	 *
	 * @return TermList|null
	 */
	private function deserializeLemmas( array $serialization ) {
		if ( array_key_exists( 'lemmas', $serialization ) ) {
			return $this->termListDeserializer->deserialize( $serialization['lemmas'] );
		}

		return null;
	}

	/**
	 * @param array $serialization
	 *
	 * @return ItemId|null
	 */
	private function deserializeLexicalCategory( array $serialization ) {
		if ( array_key_exists( 'lexicalCategory', $serialization ) ) {
			return $this->entityIdDeserializer->deserialize( $serialization['lexicalCategory'] );
		}

		return null;
	}

	/**
	 * @param array $serialization
	 *
	 * @return ItemId|null
	 */
	private function deserializeLanguage( array $serialization ) {
		if ( array_key_exists( 'language', $serialization ) ) {
			return $this->entityIdDeserializer->deserialize( $serialization['language'] );
		}

		return null;
	}

	/**
	 * @param array $serialization
	 *
	 * @return FormSet
	 */
	private function deserializeForms( array $serialization ) {
		// TODO: Extract to a FormsDeserializer
		$forms = new FormSet( [] );

		if ( array_key_exists( 'forms', $serialization ) ) {
			foreach ( $serialization['forms'] as $formSerialization ) {
				$forms->add( $this->deserializeForm( $formSerialization ) );
			}
		}

		return $forms;
	}

	/**
	 * @param array $serialization
	 *
	 * @return Form
	 */
	private function deserializeForm( array $serialization ) {
		$id = null;

		if ( array_key_exists( 'id', $serialization ) ) {
			// We may want to use an EntityIdDeserializer here
			$id = new FormId( $serialization['id'] );
		}

		$representations = $this->termListDeserializer->deserialize(
			$serialization['representations']
		);

		$grammaticalFeatures = [];
		foreach ( $serialization['grammaticalFeatures'] as $featureId ) {
			$grammaticalFeatures[] = $this->entityIdDeserializer->deserialize( $featureId );
		}

		$statements = $this->statementListDeserializer->deserialize( $serialization['claims'] );

		return new Form( $id, $representations, $grammaticalFeatures, $statements );
	}

}
