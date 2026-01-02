<?php
/**
 * Blocks Functions Tests
 *
 * @package HM\Block_Pattern_Transformer\Tests
 */

namespace HM\Block_Pattern_Transformer\Tests;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use HM\Block_Pattern_Transformer\Blocks;

/**
 * Test block creation functions.
 */
class BlocksTest extends TestCase {

	/**
	 * Set up Brain\Monkey before each test.
	 */
	protected function setUp() : void {
		parent::setUp();
		Monkey\setUp();

		// Mock WordPress functions.
		Functions\when( 'wpautop' )->returnArg();
		Functions\when( 'wp_strip_all_tags' )->alias( 'strip_tags' );
	}

	/**
	 * Tear down Brain\Monkey after each test.
	 */
	protected function tearDown() : void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Test create_heading creates proper block structure.
	 */
	public function test_create_heading_returns_block_structure() {
		$block = Blocks\create_heading( 'Test Heading', 2 );

		$this->assertEquals( 'core/heading', $block['blockName'] );
		$this->assertEquals( 2, $block['attrs']['level'] );
		$this->assertStringContainsString( 'Test Heading', $block['innerHTML'] );
		$this->assertStringContainsString( '<h2', $block['innerHTML'] );
	}

	/**
	 * Test create_heading with different levels.
	 */
	public function test_create_heading_respects_level() {
		$h1 = Blocks\create_heading( 'Title', 1 );
		$h3 = Blocks\create_heading( 'Title', 3 );

		$this->assertEquals( 1, $h1['attrs']['level'] );
		$this->assertStringContainsString( '<h1', $h1['innerHTML'] );

		$this->assertEquals( 3, $h3['attrs']['level'] );
		$this->assertStringContainsString( '<h3', $h3['innerHTML'] );
	}

	/**
	 * Test create_heading strips existing heading tags.
	 */
	public function test_create_heading_strips_existing_tags() {
		$block = Blocks\create_heading( '<h1>Nested Title</h1>', 2 );

		$this->assertStringContainsString( 'Nested Title', $block['innerHTML'] );
		$this->assertStringNotContainsString( '<h1>', $block['innerHTML'] );
	}

	/**
	 * Test create_paragraph creates proper block structure.
	 */
	public function test_create_paragraph_returns_block_structure() {
		$block = Blocks\create_paragraph( 'Test content' );

		$this->assertEquals( 'core/paragraph', $block['blockName'] );
		$this->assertStringContainsString( 'Test content', $block['innerHTML'] );
		$this->assertStringContainsString( '<p>', $block['innerHTML'] );
	}

	/**
	 * Test create_paragraphs splits content.
	 */
	public function test_create_paragraphs_splits_by_double_newline() {
		$content = "First paragraph\n\nSecond paragraph\n\nThird paragraph";
		$blocks = Blocks\create_paragraphs( $content );

		$this->assertCount( 3, $blocks );
		$this->assertStringContainsString( 'First paragraph', $blocks[0]['innerHTML'] );
		$this->assertStringContainsString( 'Second paragraph', $blocks[1]['innerHTML'] );
		$this->assertStringContainsString( 'Third paragraph', $blocks[2]['innerHTML'] );
	}

	/**
	 * Test create_paragraphs filters empty paragraphs.
	 */
	public function test_create_paragraphs_filters_empty() {
		$content = "First\n\n\n\nSecond";
		$blocks = Blocks\create_paragraphs( $content );

		// Should only have 2 blocks, not empty ones.
		$this->assertCount( 2, $blocks );
	}

	/**
	 * Test create_block creates custom block.
	 */
	public function test_create_block_returns_custom_block() {
		$block = Blocks\create_block( 'theme/hero', [ 'className' => 'is-featured' ] );

		$this->assertEquals( 'theme/hero', $block['blockName'] );
		$this->assertEquals( 'is-featured', $block['attrs']['className'] );
		$this->assertEmpty( $block['innerBlocks'] );
	}

	/**
	 * Test create_block with inner blocks.
	 */
	public function test_create_block_with_inner_blocks() {
		$inner = Blocks\create_paragraph( 'Inner content' );
		$block = Blocks\create_block( 'core/group', [], [ $inner ] );

		$this->assertCount( 1, $block['innerBlocks'] );
		$this->assertEquals( 'core/paragraph', $block['innerBlocks'][0]['blockName'] );

		// innerContent should have null placeholders.
		$this->assertCount( 1, $block['innerContent'] );
		$this->assertNull( $block['innerContent'][0] );
	}

	/**
	 * Test create_wrapper_block creates block with wrapper HTML.
	 */
	public function test_create_wrapper_block_returns_proper_structure() {
		$inner = Blocks\create_paragraph( 'Content' );
		$block = Blocks\create_wrapper_block(
			'core/group',
			'<div class="wp-block-group">',
			'</div>',
			[],
			[ $inner ]
		);

		$this->assertEquals( 'core/group', $block['blockName'] );
		$this->assertCount( 1, $block['innerBlocks'] );

		// innerContent should have opening, null, closing.
		$this->assertCount( 3, $block['innerContent'] );
		$this->assertEquals( '<div class="wp-block-group">', $block['innerContent'][0] );
		$this->assertNull( $block['innerContent'][1] );
		$this->assertEquals( '</div>', $block['innerContent'][2] );
	}

	/**
	 * Test strip_html removes tags.
	 */
	public function test_strip_html_removes_tags() {
		$result = Blocks\strip_html( '<p>Some <strong>content</strong></p>' );

		$this->assertEquals( 'Some content', $result );
	}
}
