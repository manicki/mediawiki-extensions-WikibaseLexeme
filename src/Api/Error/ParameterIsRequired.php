<?php

namespace Wikibase\Lexeme\Api\Error;

/**
 * @license GPL-2.0-or-later
 */
class ParameterIsRequired implements ApiError {

	/**
	 * @var string
	 */
	private $parameterName;

	/**
	 * @param string $parameterName
	 */
	public function __construct( $parameterName ) {
		$this->parameterName = $parameterName;
	}

	/**
	 * @see ApiError::asApiMessage()
	 */
	public function asApiMessage() {
		$message = new \Message(
			'wikibaselexeme-api-error-parameter-required',
			[ $this->parameterName ]
		);
		return new \ApiMessage( $message, 'bad-request' );
	}

}
