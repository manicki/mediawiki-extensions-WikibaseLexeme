<?php

namespace Wikibase\Lexeme\Api;

use ApiBase;
use ApiMain;
use Wikibase\EditEntityFactory;
use Wikibase\Lexeme\Api\Error\LexemeNotFound;
use Wikibase\Lexeme\DataModel\Lexeme;
use Wikibase\Lexeme\DataModel\Serialization\FormSerializer;
use Wikibase\Lib\Store\EntityRevision;
use Wikibase\Lib\Store\EntityRevisionLookup;
use Wikibase\Lib\Store\StorageException;
use Wikibase\Repo\Api\ApiErrorReporter;
use Wikibase\SummaryFormatter;

/**
 * @license GPL-2.0-or-later
 */
class AddForm extends ApiBase {

	const LATEST_REVISION = 0;

	/**
	 * @var AddFormRequestParser
	 */
	private $requestParser;

	/**
	 * @var ApiErrorReporter
	 */
	private $errorReporter;

	/**
	 * @var FormSerializer
	 */
	private $formSerializer;

	/**
	 * @var EditEntityFactory
	 */
	private $editEntityFactory;

	/**
	 * @var SummaryFormatter
	 */
	private $summaryFormatter;

	/**
	 * @var EntityRevisionLookup
	 */
	private $entityRevisionLookup;

	/**
	 * @return self
	 */
	public static function newFromGlobalState( \ApiMain $mainModule, $moduleName ) {
		$wikibaseRepo = \Wikibase\Repo\WikibaseRepo::getDefaultInstance();
		$apiHelperFactory = $wikibaseRepo->getApiHelperFactory( $mainModule->getContext() );

		$serializerFactory = $wikibaseRepo->getBaseDataModelSerializerFactory();

		$formSerializer = new FormSerializer(
			$serializerFactory->newTermListSerializer(),
			$serializerFactory->newStatementListSerializer()
		);

		return new self(
			$mainModule,
			$moduleName,
			new AddFormRequestParser( $wikibaseRepo->getEntityIdParser() ),
			$formSerializer,
			$wikibaseRepo->getEntityRevisionLookup( 'uncached' ),
			$wikibaseRepo->newEditEntityFactory( $mainModule->getContext() ),
			$wikibaseRepo->getSummaryFormatter(),
			function ( $module ) use ( $apiHelperFactory ) {
				return $apiHelperFactory->getErrorReporter( $module );
			}
		);
	}

	public function __construct(
		ApiMain $mainModule,
		$moduleName,
		AddFormRequestParser $requestParser,
		FormSerializer $formSerializer,
		EntityRevisionLookup $entityRevisionLookup,
		EditEntityFactory $editEntityFactory,
		SummaryFormatter $summaryFormatter,
		callable $errorReporterInstantiator
	) {
		parent::__construct( $mainModule, $moduleName );

		$this->errorReporter = $errorReporterInstantiator( $this );
		$this->requestParser = $requestParser;
		$this->formSerializer = $formSerializer;
		$this->editEntityFactory = $editEntityFactory;
		$this->entityRevisionLookup = $entityRevisionLookup;
		$this->summaryFormatter = $summaryFormatter;
	}

	/**
	 * @see ApiBase::execute()
	 *
	 * @throws \ApiUsageException
	 */
	public function execute() {
		/*
		 * {
			  "representations": [
				{
				  "representation": "",
				  "language": ""
				},
				{
				  "representation": "",
				  "language": ""
				}
			  ],
			  "grammaticalFeatures": [
				"Q1",
				"Q2"
			  ]
			}
		 *
		 */

		//FIXME: Response structure? - Added form
		//FIXME: Representation text normalization

		//TODO: Corresponding HTTP codes on failure (e.g. 400, 404, 422) (?)
		//TODO: Documenting response structure. Is it possible?

		$params = $this->extractRequestParams();
		$parserResult = $this->requestParser->parse( $params );

		if ( $parserResult->hasErrors() ) {
			//TODO: Increase stats counter on failure
			// `wikibase.repo.api.errors.total` counter
			// What does it mean? What it is used for?
			// Comment from Grafana dashboard:
			//     Used to spot issues with the Wikibase API.
			//     Spikes can indicate issues with Wikibase deployments.
			$this->dieStatus( $parserResult->asFatalStatus() );
		}

		$request = $parserResult->getRequest();

		try {
			$lexemeId = $request->getLexemeId();
			$lexemeRevision = $this->entityRevisionLookup->getEntityRevision(
				$lexemeId,
				self::LATEST_REVISION,
				EntityRevisionLookup::LATEST_FROM_MASTER
			);

			if ( !$lexemeRevision ) {
				$error = new LexemeNotFound( $lexemeId );
				$this->dieWithError( $error->asApiMessage() );
			}
		} catch ( StorageException $e ) {
			//TODO Test it
			if ( $e->getStatus() ) {
				$this->dieStatus( $e->getStatus() );
			} else {
				//FIXME Do what???
			}
		}
		/** @var Lexeme $lexeme */
		$lexeme = $lexemeRevision->getEntity();
		$newForm = $request->addFormTo( $lexeme );

		$editEntity = $this->editEntityFactory->newEditEntity(
			$this->getUser(),
			$request->getLexemeId(),
			$lexemeRevision->getRevisionId()
		);
		$summaryString = $this->summaryFormatter->formatSummary(
			new AddFormSummary( $lexeme->getId(), $newForm )
		);
		$flags = EDIT_UPDATE;
		if ( isset( $params['bot'] ) && $params['bot'] && $this->getUser()->isAllowed( 'bot' ) ) {
			$flags |= EDIT_FORCE_BOT;
		}

		$tokenThatDoesNotNeedChecking = false;
		//FIXME: Handle failure
		$status = $editEntity->attemptSave(
			$lexeme,
			$summaryString,
			$flags,
			$tokenThatDoesNotNeedChecking
		);

		if ( !$status->isGood() ) {
			$this->dieStatus( $status ); //Seems like it is good enough
		}

		/** @var EntityRevision $entityRevision */
		$entityRevision = $status->getValue()['revision'];
		$revisionId = $entityRevision->getRevisionId();

		$serializedForm = $this->formSerializer->serialize( $newForm );

		$apiResult = $this->getResult();
		$apiResult->addValue( null, 'lastrevid', $revisionId );
		// TODO: Do we really need `success` property in response?
		$apiResult->addValue( null, 'success', 1 );
		$apiResult->addValue( null, 'form', $serializedForm );
	}

	/**
	 * @see ApiBase::getAllowedParams
	 */
	protected function getAllowedParams() {
		return array_merge(
			[
				'lexemeId' => [
					self::PARAM_TYPE => 'string',
					self::PARAM_REQUIRED => true,
				],
				'data' => [
					self::PARAM_TYPE => 'text',
					self::PARAM_REQUIRED => true,
				],
				'bot' => [
					self::PARAM_TYPE => 'boolean',
					self::PARAM_DFLT => false,
				]
			]
		);
	}

	/**
	 * @see ApiBase::isWriteMode()
	 */
	public function isWriteMode() {
		return true;
	}

	/**
	 * As long as this codebase is in development and APIs might change any time without notice, we
	 * mark all as internal. This adds an "unstable" notice, but does not hide them in any way.
	 */
	public function isInternal() {
		return true;
	}

	/**
	 * @see ApiBase::needsToken()
	 */
	public function needsToken() {
		return 'csrf';
	}

	/**
	 * @see ApiBase::mustBePosted()
	 */
	public function mustBePosted() {
		return true;
	}

	protected function getExamplesMessages() {
		$lexemeId = 'L12';
		$exampleData = [
			'representations' => [
				[ 'representation' => 'color', 'language' => 'en-US' ],
				[ 'representation' => 'colour', 'language' => 'en-GB' ],
			],
			'grammaticalFeatures' => [
				'Q1', 'Q2'
			]
		];

		$query = http_build_query( [
			'action' => $this->getModuleName(),
			'lexemeId' => $lexemeId,
			'data' => json_encode( $exampleData )
		] );

		$languages = array_map( function ( $r ) {
			return $r['language'];
		}, $exampleData['representations'] );
		$representations = array_map( function ( $r ) {
			return $r['representation'];
		}, $exampleData['representations'] );

		$representationsText = $this->getLanguage()->commaList( $representations );
		$languagesText = $this->getLanguage()->commaList( $languages );
		$grammaticalFeaturesText = $this->getLanguage()->commaList( $exampleData['grammaticalFeatures'] );

		$exampleMessage = new \Message(
			'apihelp-wbladdform-example-1',
			[
				$lexemeId,
				$representationsText,
				$languagesText,
				$grammaticalFeaturesText
			]
		);

		return [
			$query => $exampleMessage
		];
	}

}
