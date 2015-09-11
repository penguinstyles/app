<?php
class WeiboTagController extends WikiaParserTagController {
	const PARSER_TAG_NAME = 'weibo';
	const TAG_SRC = 'http://widget.weibo.com/relationship/bulkfollow.php?';
	const TAG_SOURCE_ALLOWED_PARAMS_WITH_DEFAULTS = [
		'color' => '',
		'count' => '',
		'language' => '',
		'sense' => '',
		'showinfo' => '1',
		'showtitle' => '1',
		'uids' => '',
		'verified' => '',
		'wide' => '',
	];
	const TAG_ALLOWED_ATTRIBUTES = [
		'width',
		'height',
	];
	const TAG_DEFAULT_ATTRIBUTES = [
		'data-wikia-widget' => self::PARSER_TAG_NAME,
		'scrolling' => 'no',
		'frameborder' => '0',
	];

	private $tagBuildSource;
	private $tagBuilderHelper;
	private $validator;

	public function __construct() {
		parent::__construct();
		$this->tagBuilderHelper = new WikiaTagBuilderHelper();
		$this->validator = new WeiboTagValidator();
	}

	public static function onParserFirstCallInit( Parser $parser ) {
		$parser->setHook( self::PARSER_TAG_NAME, [ new static(), 'renderTag' ] );
		return true;
	}

	public function renderTag( $input, array $args, Parser $parser, PPFrame $frame ) {
		$isValid = $this->validator->validateAttributes( $args );
		if ( !$isValid ) {
			return '<strong class="error">' . wfMessage( 'weibotag-could-not-render' )->parse() . '</strong>';
		}

		$this->tagBuildSource = self::TAG_SRC . $this->tagBuilderHelper->buildTagSourceQueryParams(
			self::TAG_SOURCE_ALLOWED_PARAMS_WITH_DEFAULTS, $args
		);

		$iframe = Html::element(
			'iframe',  $this->buildTagAttributes( $args ), wfMessage( 'weibotag-could-not-render' )->text()
		);

		if ( $this->app->checkSkin( [ 'wikiamobile', 'mercury' ] ) ) {
			return Html::rawElement( 'script',  ['type' => 'x-wikia-widget'], $iframe );
		} else {
			return $iframe;
		}
	}

	private function buildTagAttributes( $args ) {
		$attributes = $this->tagBuilderHelper->buildTagAttributes( self::TAG_ALLOWED_ATTRIBUTES, $args );
		$attributes['src'] = $this->tagBuildSource;
		return array_merge( $attributes, self::TAG_DEFAULT_ATTRIBUTES );
	}


	protected function buildParamValidator( $paramName ) {
		return true;
	}
}
