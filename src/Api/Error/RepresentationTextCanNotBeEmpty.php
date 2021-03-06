<?php

namespace Wikibase\Lexeme\Api\Error;

/**
 * @license GPL-2.0-or-later
 */
class RepresentationTextCanNotBeEmpty implements ApiError {

	/**
	 * @var string
	 */
	private $parameterName;

	/**
	 * @var string[]
	 */
	private $fieldPath;

	/**
	 * @param string $parameterName
	 * @param string[] $fieldPath
	 */
	public function __construct( $parameterName, array $fieldPath ) {
		$this->parameterName = $parameterName;
		$this->fieldPath = $fieldPath;
	}

	/**
	 * @see ApiError::asApiMessage()
	 */
	public function asApiMessage() {
		$message = new \Message(
			'wikibaselexeme-api-error-representation-text-cannot-be-empty',
			[]
		);
		return new \ApiMessage(
			$message,
			'unprocessable-request',
			[
				'parameterName' => $this->parameterName,
				'fieldPath' => $this->fieldPath
			]
		);
	}

}
