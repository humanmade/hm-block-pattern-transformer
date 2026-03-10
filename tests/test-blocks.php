<?php
/**
 * Blocks Functions Tests
 *
 * @package HM\Block_Pattern_Transformer\Tests
 */

namespace HM\Block_Pattern_Transformer\Tests;

use WP_UnitTestCase;
use HM\Block_Pattern_Transformer\Blocks;

/**
 * Test block creation functions.
 */
class BlocksTest extends WP_UnitTestCase {

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
	 * Test create_block with inner_html creates leaf block.
	 */
	public function test_create_block_with_inner_html() {
		$block = Blocks\create_block( 'core/html', [], '<div>Custom HTML</div>' );

		$this->assertEquals( 'core/html', $block['blockName'] );
		$this->assertEquals( '<div>Custom HTML</div>', $block['innerHTML'] );
		$this->assertCount( 1, $block['innerContent'] );
		$this->assertEquals( '<div>Custom HTML</div>', $block['innerContent'][0] );
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
	 * Test create_paragraph sanitizes disallowed HTML tags.
	 */
	public function test_create_paragraph_sanitizes_disallowed_tags() {
		$block = Blocks\create_paragraph( 'Text with <script>alert("xss")</script> removed' );

		// wp_kses removes tags but preserves text content.
		$this->assertStringNotContainsString( '<script>', $block['innerHTML'] );
		$this->assertStringNotContainsString( '</script>', $block['innerHTML'] );
		$this->assertStringContainsString( 'Text with', $block['innerHTML'] );
		$this->assertStringContainsString( 'removed', $block['innerHTML'] );
	}

	/**
	 * Test create_paragraph preserves allowed inline HTML.
	 */
	public function test_create_paragraph_preserves_allowed_tags() {
		$block = Blocks\create_paragraph( 'Text with <strong>bold</strong> and <em>italic</em> and <a href="https://example.com">link</a>' );

		$this->assertStringContainsString( '<strong>bold</strong>', $block['innerHTML'] );
		$this->assertStringContainsString( '<em>italic</em>', $block['innerHTML'] );
		$this->assertStringContainsString( '<a href="https://example.com">link</a>', $block['innerHTML'] );
	}

	/**
	 * Test create_paragraph converts line breaks to br tags.
	 */
	public function test_create_paragraph_converts_line_breaks() {
		$block = Blocks\create_paragraph( "Line one\nLine two" );

		$this->assertStringContainsString( 'Line one<br>', $block['innerHTML'] );
		$this->assertStringContainsString( 'Line two', $block['innerHTML'] );
	}

	/**
	 * Test create_paragraph filter allows customizing allowed HTML.
	 */
	public function test_create_paragraph_filter_customizes_allowed_html() {
		// Add a filter to allow <mark> tags.
		add_filter( 'hm.block_pattern_transformer.paragraph_allowed_html', function( $allowed ) {
			$allowed['mark'] = [ 'class' => true ];
			return $allowed;
		} );

		$block = Blocks\create_paragraph( 'Text with <mark class="highlight">highlighted</mark> content' );

		$this->assertStringContainsString( '<mark class="highlight">highlighted</mark>', $block['innerHTML'] );

		// Clean up filter.
		remove_all_filters( 'hm.block_pattern_transformer.paragraph_allowed_html' );
	}

	/**
	 * Test create_paragraph filter can restrict allowed HTML.
	 */
	public function test_create_paragraph_filter_restricts_allowed_html() {
		// Add a filter to only allow plain text (no HTML).
		add_filter( 'hm.block_pattern_transformer.paragraph_allowed_html', function() {
			return [];
		} );

		$block = Blocks\create_paragraph( 'Text with <strong>bold</strong> stripped' );

		$this->assertStringNotContainsString( '<strong>', $block['innerHTML'] );
		$this->assertStringContainsString( 'Text with', $block['innerHTML'] );
		$this->assertStringContainsString( 'bold', $block['innerHTML'] );
		$this->assertStringContainsString( 'stripped', $block['innerHTML'] );

		// Clean up filter.
		remove_all_filters( 'hm.block_pattern_transformer.paragraph_allowed_html' );
	}
}
