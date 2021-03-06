<?php

class PortableInfoboxParserTagControllerTest extends WikiaBaseTest {

	/** @var PortableInfoboxParserTagController */
	protected $parser;
	/** @var Parser */
	protected $controller;

	protected function setUp() {
		$this->setupFile = dirname( __FILE__ ) . '/../PortableInfobox.setup.php';
		parent::setUp();
		$this->parser = $this->setUpParser();
		$this->controller = new PortableInfoboxParserTagController();
	}

	protected function tearDown() {
		// we use libxml only for tests here
		libxml_clear_errors();
		parent::tearDown();
	}

	protected function setUpParser() {
		$parser = new Parser();
		$options = new ParserOptions();
		$title = Title::newFromText( 'Test' );
		$parser->Options( $options );
		$parser->startExternalParse( $title, $options, 'text', true );
		return $parser;
	}

	protected function checkClassName( $output, $class ) {
		$result = new DOMDocument();
		$result->loadHTML( $output );
		$xpath = new DOMXPath( $result );
		return $xpath->query( '//aside[contains(@class, \'' . $class . '\')]' )->length > 0 ? true : false;
	}

	public function testEmptyInfobox() {
		$text = '';

		$marker = $this->controller->renderInfobox( $text, [ ], $this->parser,
			$this->parser->getPreprocessor()->newFrame() )[ 0 ];
		$output = $this->controller->replaceMarkers( $marker );

		$this->assertEquals( $output, '' );
	}

	public function testThemedInfobox() {
		$text = '<data><default>test</default></data>';
		$defaultTheme = 'test';

		$marker = $this->controller->renderInfobox( $text, [ 'theme' => $defaultTheme ], $this->parser,
			$this->parser->getPreprocessor()->newFrame() )[ 0 ];
		$output = $this->controller->replaceMarkers( $marker );

		$this->assertTrue( $this->checkClassName(
			$output,
			PortableInfoboxParserTagController::INFOBOX_THEME_PREFIX . $defaultTheme
		) );
	}

	public function testSourceThemedInfobox() {
		$text = '<data><default>test</default></data>';
		$themeVariableName = 'variableName';
		$themeName = 'variable';

		$marker = $this->controller->renderInfobox( $text, [ 'theme-source' => $themeVariableName ], $this->parser,
			$this->parser->getPreprocessor()->newCustomFrame( [ $themeVariableName => $themeName ] ) )[ 0 ];
		$output = $this->controller->replaceMarkers( $marker );

		$this->assertTrue( $this->checkClassName(
			$output,
			PortableInfoboxParserTagController::INFOBOX_THEME_PREFIX . $themeName
		) );
	}

	public function testEmptySourceDefaultThemedInfobox() {
		$text = '<data><default>test</default></data>';
		$themeVariableName = 'variableName';
		$themeName = 'variable';
		$defaultTheme = 'default';

		$marker = $this->controller->renderInfobox( $text,
			[ 'theme' => $defaultTheme, 'theme-source' => $themeVariableName ],
			$this->parser,
			$this->parser->getPreprocessor()->newFrame() )[ 0 ];
		$output = $this->controller->replaceMarkers( $marker );

		$this->assertTrue( $this->checkClassName(
			$output,
			PortableInfoboxParserTagController::INFOBOX_THEME_PREFIX . $defaultTheme
		) );
	}

	public function testNoThemeInfobox() {
		$text = '<data><default>test</default></data>';

		$marker = $this->controller->renderInfobox( $text, [ ], $this->parser,
			$this->parser->getPreprocessor()->newFrame() )[ 0 ];
		$output = $this->controller->replaceMarkers( $marker );

		$this->assertTrue( $this->checkClassName(
			$output,
			PortableInfoboxParserTagController::INFOBOX_THEME_PREFIX . PortableInfoboxParserTagController::DEFAULT_THEME_NAME
		) );
	}

	public function testWhiteSpacedThemeInfobox() {
		$text = '<data><default>test</default></data>';
		$defaultTheme = 'test test';
		$expectedName = 'test-test';

		$marker = $this->controller->renderInfobox( $text, [ 'theme' => $defaultTheme ], $this->parser,
			$this->parser->getPreprocessor()->newFrame() )[ 0 ];
		$output = $this->controller->replaceMarkers( $marker );

		$this->assertTrue( $this->checkClassName(
			$output,
			PortableInfoboxParserTagController::INFOBOX_THEME_PREFIX . $expectedName
		) );
	}

	public function testMultiWhiteSpacedThemeInfobox() {
		$text = '<data><default>test</default></data>';
		$defaultTheme = "test    test\n test\ttest";
		$expectedName = 'test-test-test-test';

		$marker = $this->controller->renderInfobox( $text, [ 'theme' => $defaultTheme ], $this->parser,
			$this->parser->getPreprocessor()->newFrame() )[ 0 ];
		$output = $this->controller->replaceMarkers( $marker );

		$this->assertTrue( $this->checkClassName(
			$output,
			PortableInfoboxParserTagController::INFOBOX_THEME_PREFIX . $expectedName
		) );
	}

	/**
	 * @dataProvider testGetLayoutDataProvider
	 */
	public function testGetLayout( $layout, $expectedOutput, $text, $message ) {
		$marker = $this->controller->renderInfobox( $text, $layout, $this->parser,
			$this->parser->getPreprocessor()->newFrame() )[ 0 ];
		$output = $this->controller->replaceMarkers( $marker );

		$this->assertTrue( $this->checkClassName(
			$output,
			$expectedOutput,
			$message
		) );
	}

	public function testGetLayoutDataProvider() {
		return [
			[
				'layout' => [ 'layout' => 'tabular' ],
				'expectedOutput' => 'portable-infobox-layout-tabular',
				'text' => '<data><default>test</default></data>',
				'message' => 'set tabular layout'
			],
			[
				'layout' => [ 'layout' => 'looool' ],
				'expectedOutput' => 'portable-infobox-layout-default',
				'text' => '<data><default>test</default></data>',
				'message' => 'invalid layout name'
			],
			[
				'layout' => [ 'layout' => '' ],
				'expectedOutput' => 'portable-infobox-layout-default',
				'text' => '<data><default>test</default></data>',
				'message' => 'layout is empty string'
			],
			[
				'layout' => [ 'layout' => 5 ],
				'expectedOutput' => 'portable-infobox-layout-default',
				'text' => '<data><default>test</default></data>',
				'message' => 'layout is an integer'
			],
			[
				'layout' => [ 'layout' => [] ],
				'expectedOutput' => 'portable-infobox-layout-default',
				'text' => '<data><default>test</default></data>',
				'message' => 'layout an empty table'
			],
			[
				'layout' => [],
				'expectedOutput' => 'portable-infobox-layout-default',
				'text' => '<data><default>test</default></data>',
				'message' => 'layout is not set'
			]
		];
	}
}
