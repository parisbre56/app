<?php

class NodeGroupTest extends WikiaBaseTest {
	protected function setUp() {
		$this->setupFile = dirname( __FILE__ ) . '/../../PortableInfobox.setup.php';
		parent::setUp();
	}

	/**
	 * @covers NodeGroup::getData
	 * @dataProvider groupNodeTestProvider
	 * @param $markup
	 * @param $params
	 * @param $expected
	 */
	public function testNodeGroup( $markup, $params, $expected ) {
		$node = \Wikia\PortableInfobox\Parser\Nodes\NodeFactory::newFromXML( $markup, $params );

		$this->assertEquals( $expected, $node->getData() );
	}

	public function groupNodeTestProvider() {
		return [
			[ '<group><data source="elem1"><label>l1</label><default>def1</default></data><data source="elem2">
				<label>l2</label><default>def2</default></data><data source="elem3"><label>l2</label></data></group>',
			  [ 'elem1' => 1, 'elem2' => 2 ],
			  [ 'value' =>
					[
						[ 'type' => 'data', 'isEmpty' => false, 'data' => [ 'label' => 'l1', 'value' => 1 ],
						  'source' => [ 'elem1' ] ],
						[ 'type' => 'data', 'isEmpty' => false, 'data' => [ 'label' => 'l2', 'value' => 2 ],
						  'source' => [ 'elem2' ] ],
						[ 'type' => 'data', 'isEmpty' => true, 'data' => [ 'label' => 'l2', 'value' => null ],
						  'source' => [ 'elem3' ] ]
					],
				'layout' => 'default'
			  ] ],
			[ '<group layout="horizontal"><data source="elem1"><label>l1</label><default>def1</default></data><data source="elem2">
				<label>l2</label><default>def2</default></data><data source="elem3"><label>l2</label></data></group>',
			  [ 'elem1' => 1, 'elem2' => 2 ],
			  [ 'value' =>
					[
						[ 'type' => 'data', 'isEmpty' => false, 'data' => [ 'label' => 'l1', 'value' => 1 ],
						  'source' => [ 'elem1' ] ],
						[ 'type' => 'data', 'isEmpty' => false, 'data' => [ 'label' => 'l2', 'value' => 2 ],
						  'source' => [ 'elem2' ] ],
						[ 'type' => 'data', 'isEmpty' => true, 'data' => [ 'label' => 'l2', 'value' => null ],
						  'source' => [ 'elem3' ] ],
					],
				'layout' => 'horizontal'
			  ] ],
			[ '<group  layout="loool"><data source="elem1"><label>l1</label><default>def1</default></data><data source="elem2">
				<label>l2</label><default>def2</default></data><data source="elem3"><label>l2</label></data></group>',
			  [ 'elem1' => 1, 'elem2' => 2 ],
			  [ 'value' =>
					[
						[ 'type' => 'data', 'isEmpty' => false, 'data' => [ 'label' => 'l1', 'value' => 1 ],
						  'source' => [ 'elem1' ] ],
						[ 'type' => 'data', 'isEmpty' => false, 'data' => [ 'label' => 'l2', 'value' => 2 ],
						  'source' => [ 'elem2' ] ],
						[ 'type' => 'data', 'isEmpty' => true, 'data' => [ 'label' => 'l2', 'value' => null ],
						  'source' => [ 'elem3' ] ],
					],
				'layout' => 'default'
			  ] ],
		];
	}
}