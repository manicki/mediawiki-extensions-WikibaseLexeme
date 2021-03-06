<?php

namespace Wikibase\Lexeme\Api\Summary;

use Wikibase\Lexeme\DataModel\FormId;
use Wikibase\Lib\FormatableSummary;

/**
 * @license GPL-2.0-or-later
 */
class ChangeFormElementsSummary implements FormatableSummary {

	/**
	 * @var FormId
	 */
	private $formId;

	/**
	 * @param FormId $formId
	 */
	public function __construct( FormId $formId ) {
		$this->formId = $formId;
	}

	public function getUserSummary() {
		return null;
	}

	public function getLanguageCode() {
		return null;
	}

	public function getMessageKey() {
		// Effective message key: wikibase-lexeme-summary-update-form-elements
		return 'update-form-elements';
	}

	public function getCommentArgs() {
		return [ $this->formId->getSerialization() ];
	}

	public function getAutoSummaryArgs() {
		return [];
	}

}
