<?php
/**
 * Pattern Transformer Integration Tests
 *
 * @package HM\Block_Pattern_Transformer\Tests
 */

namespace HM\Block_Pattern_Transformer\Tests;

use WP_UnitTestCase;
use HM\Block_Pattern_Transformer\Pattern_Transformer;

/**
 * Test pattern transformer functions with real WordPress.
 */
class PatternTransformerTest extends WP_UnitTestCase {

	/**
	 * Path to fixture files.
	 *
	 * @var string
	 */
	protected static string $fixtures_path;

	/**
	 * Set up fixtures path.
	 */
	public static function set_up_before_class() : void {
		parent::set_up_before_class();
		self::$fixtures_path = __DIR__ . '/fixtures/patterns/';
	}

	/**
	 * Load a pattern fixture file.
	 *
	 * @param string $name Pattern filename without extension.
	 * @return string Pattern content.
	 */
	protected function load_pattern( string $name ) : string {
		$file = self::$fixtures_path . $name . '.html';
		return file_get_contents( $file );
	}

	/**
	 * Test that blocks can be parsed and serialized round-trip.
	 */
	public function test_parse_and_serialize_blocks() {
		$content = $this->load_pattern( 'simple-heading-paragraph' );
		$blocks = parse_blocks( $content );
		$serialized = serialize_blocks( $blocks );

		// Should have heading and paragraph blocks (plus empty spacer blocks).
		$non_empty_blocks = array_filter( $blocks, fn( $b ) => ! empty( $b['blockName'] ) );
		$this->assertCount( 2, $non_empty_blocks );

		// Serialized output should contain the original content.
		$this->assertStringContainsString( 'Test Title', $serialized );
		$this->assertStringContainsString( 'Test paragraph content', $serialized );
	}

	/**
	 * Test update_block_text_content updates text safely.
	 */
	public function test_update_block_text_content() {
		$content = $this->load_pattern( 'simple-heading-paragraph' );
		$blocks = parse_blocks( $content );

		// Find the heading block.
		$heading = null;
		foreach ( $blocks as $block ) {
			if ( $block['blockName'] === 'core/heading' ) {
				$heading = $block;
				break;
			}
		}

		$this->assertNotNull( $heading );

		$updated = Pattern_Transformer\update_block_text_content( $heading, 'New Title' );

		$this->assertStringContainsString( 'New Title', $updated['innerHTML'] );
		$this->assertStringNotContainsString( 'Test Title', $updated['innerHTML'] );
		$this->assertStringContainsString( '<h2', $updated['innerHTML'] );
	}

	/**
	 * Test rebuild_inner_content preserves structure.
	 */
	public function test_rebuild_inner_content() {
		$content = $this->load_pattern( 'hero-section' );
		$blocks = parse_blocks( $content );

		// Find the group block.
		$group = null;
		foreach ( $blocks as $block ) {
			if ( $block['blockName'] === 'core/group' ) {
				$group = $block;
				break;
			}
		}

		$this->assertNotNull( $group );
		$this->assertNotEmpty( $group['innerBlocks'] );

		$rebuilt = Pattern_Transformer\rebuild_inner_content( $group );

		// Should maintain the wrapper structure with null placeholders for inner blocks.
		$this->assertStringContainsString( 'wp-block-group', $rebuilt['innerContent'][0] );
		$this->assertContains( null, $rebuilt['innerContent'] );
	}

	/**
	 * Test resolving pattern references tags blocks with source.
	 */
	public function test_resolve_and_tag_patterns_tags_blocks() {
		$pattern_content = $this->load_pattern( 'simple-paragraph' );

		// Register a test pattern.
		register_block_pattern(
			'test/simple-pattern',
			[
				'title' => 'Simple Pattern',
				'content' => $pattern_content,
			]
		);

		$blocks = parse_blocks( '<!-- wp:pattern {"slug":"test/simple-pattern"} /-->' );
		$resolved = Pattern_Transformer\resolve_and_tag_patterns( $blocks );

		// Should have resolved the pattern reference.
		$this->assertNotEmpty( $resolved );

		// Find the paragraph block.
		$paragraph = null;
		foreach ( $resolved as $block ) {
			if ( $block['blockName'] === 'core/paragraph' ) {
				$paragraph = $block;
				break;
			}
		}

		$this->assertNotNull( $paragraph, 'Paragraph block should exist' );
		$this->assertEquals( 'test/simple-pattern', $paragraph['_source_pattern'] ?? null );

		// Clean up.
		unregister_block_pattern( 'test/simple-pattern' );
	}

	/**
	 * Test apply_pattern_transformations replaces text.
	 */
	public function test_apply_pattern_transformations() {
		$content = $this->load_pattern( 'simple-heading-paragraph' );
		$blocks = parse_blocks( $content );

		// Tag blocks as coming from a pattern.
		$tagged_blocks = Pattern_Transformer\tag_blocks_recursively( $blocks, 'test/hero' );

		$transformations = [
			'test/hero' => [
				'core/heading' => [
					0 => [ 'textContent' => 'Transformed Title' ],
				],
			],
		];

		$result = Pattern_Transformer\apply_pattern_transformations( $tagged_blocks, $transformations );

		// Find the heading block in the result.
		$heading = null;
		foreach ( $result as $block ) {
			if ( $block['blockName'] === 'core/heading' ) {
				$heading = $block;
				break;
			}
		}

		$this->assertNotNull( $heading );
		$this->assertStringContainsString( 'Transformed Title', $heading['innerHTML'] );
	}
}
