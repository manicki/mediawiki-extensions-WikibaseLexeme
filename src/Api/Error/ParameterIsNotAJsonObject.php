<?php

namespace Wikibase\Lexeme\Api\Error;

/**
 * @license GPL-2.0-or-later
 */
class ParameterIsNotAJsonObject implements ApiError {

	/**
	 * @var string
	 */
	private $parameterName;

	/**
	 * @var string
	 */
	private $given;

	/**
	 * @param string $parameterName
	 * @param string $given
	 */
	public function __construct( $parameterName, $given ) {
		$this->parameterName = $parameterName;
		$this->given = $given;
	}

	/**
	 * @see ApiError::asApiMessage()
	 */
	public function asApiMessage() {
		$message = new \Message(
			'wikibaselexeme-api-error-parameter-invalid-json-object',
			[ $this->parameterName, $this->given ]
		);
		return new \ApiMessage( $message, 'bad-request' );
	}

}
