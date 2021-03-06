<?php

namespace Wikibase\Lexeme\DataModel\Serialization;

use Serializers\DispatchableSerializer;
use Serializers\Exceptions\SerializationException;
use Serializers\Exceptions\UnsupportedObjectException;
use Serializers\Serializer;
use UnexpectedValueException;
use Wikibase\Lexeme\DataModel\FormSet;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\Sense;

/**
 * @license GPL-2.0-or-later
 * @author Amir Sarabadani <ladsgroup@gmail.com>
 */
class StorageLexemeSerializer implements DispatchableSerializer {

	/**
	 * @var Serializer
	 */
	private $termListSerializer;

	/**
	 * @var Serializer
	 */
	private $statementListSerializer;

	/**
	 * @var Serializer
	 */
	private $formSerializer;

	public function __construct(
		Serializer $termListSerializer,
		Serializer $statementListSerializer
	) {
		$this->termListSerializer = $termListSerializer;
		$this->statementListSerializer = $statementListSerializer;
		$this->formSerializer = new FormSerializer( $termListSerializer, $statementListSerializer );
	}

	/**
	 * @see DispatchableSerializer::isSerializerFor
	 *
	 * @param mixed $object
	 *
	 * @return bool
	 */
	public function isSerializerFor( $object ) {
		return $object instanceof Lexeme;
	}

	/**
	 * @see Serializer::serialize
	 *
	 * @param Lexeme $object
	 *
	 * @throws SerializationException
	 * @return array
	 */
	public function serialize( $object ) {
		if ( !$this->isSerializerFor( $object ) ) {
			throw new UnsupportedObjectException(
				$object,
				'LexemeSerializer can only serialize Lexeme objects.'
			);
		}

		return $this->getSerialized( $object );
	}

	/**
	 * @param Lexeme $lexeme
	 *
	 * @throws SerializationException
	 * @return array
	 */
	private function getSerialized( Lexeme $lexeme ) {
		$serialization = [ 'type' => $lexeme->getType() ];

		$id = $lexeme->getId();

		if ( $id !== null ) { // FIXME: Should fail if ID is not present
			$serialization['id'] = $id->getSerialization();
		}

		//FIXME: Should always present
		if ( !$lexeme->getLemmas()->isEmpty() ) {
			$serialization['lemmas'] = $this->termListSerializer->serialize(
				$lexeme->getLemmas()
			);
		}

		try {
			$serialization['lexicalCategory'] = $lexeme->getLexicalCategory()->getSerialization();
			$serialization['language'] = $lexeme->getLanguage()->getSerialization();
		} catch ( UnexpectedValueException $ex ) {
			throw new UnsupportedObjectException(
				$lexeme,
				'Can not serialize incomplete Lexeme',
				$ex
			);
		}

		$serialization['claims'] = $this->statementListSerializer->serialize(
			$lexeme->getStatements()
		);

		$serialization['nextFormId'] = $lexeme->getNextFormId();

		$serialization['forms'] = $this->serializeForms( $lexeme->getForms() );
		$serialization['senses'] = $this->serializeSenses( $lexeme->getSenses() );

		return $serialization;
	}

	/**
	 * @param FormSet $forms
	 *
	 * @return array[]
	 */
	private function serializeForms( FormSet $forms ) {
		$serialization = [];

		foreach ( $forms->toArray() as $form ) {
			$serialization[] = $this->formSerializer->serialize( $form );
		}

		return $serialization;
	}

	/**
	 * @param Sense[] $senses
	 *
	 * @return array[]
	 */
	private function serializeSenses( array $senses ) {
		$serialization = [];

		foreach ( $senses as $sense ) {
			$serialization[] = $this->serializeSense( $sense );
		}

		return $serialization;
	}

	/**
	 * @param Sense $sense
	 *
	 * @return array
	 */
	private function serializeSense( Sense $sense ) {
		$serialization = [];

		$serialization['id'] = $sense->getId()->getSerialization();
		$serialization['glosses'] = $this->termListSerializer->serialize( $sense->getGlosses() );

		$serialization['claims'] = $this->statementListSerializer->serialize(
			$sense->getStatements()
		);

		return $serialization;
	}

}
