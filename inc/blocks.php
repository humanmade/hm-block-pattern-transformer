<?php
/**
 * Block Creation Functions
 *
 * Utilities for creating WordPress blocks programmatically.
 * These functions return properly structured block arrays that can be
 * serialized and used in post content.
 *
 * @package HM\Block_Pattern_Transformer
 */

namespace HM\Block_Pattern_Transformer\Blocks;

/**
 * Create a heading block.
 *
 * @param string $content Heading content (may include HTML).
 * @param int    $level Heading level (1-6).
 * @return array Heading block array.
 */
function create_heading( string $content, int $level = 2 ) : array {
	// Strip any existing heading tags.
	$content = preg_replace( '/<\/?h[1-6][^>]*>/', '', $content );
	$content = trim( $content );

	return [
		'blockName' => 'core/heading',
		'attrs' => [
			'level' => $level,
		],
		'innerBlocks' => [],
		'innerHTML' => sprintf( '<h%d class="wp-block-heading">%s</h%d>', $level, $content, $level ),
		'innerContent' => [ sprintf( '<h%d class="wp-block-heading">%s</h%d>', $level, $content, $level ) ],
	];
}

/**
 * Create a paragraph block.
 *
 * @param string $content Paragraph content.
 * @return array Paragraph block array.
 */
function create_paragraph( string $content ) : array {
	$content = trim( $content );

	// Convert line breaks to <br> tags if needed.
	$content = wpautop( $content );

	return [
		'blockName' => 'core/paragraph',
		'attrs' => [],
		'innerBlocks' => [],
		'innerHTML' => sprintf( '<p>%s</p>', $content ),
		'innerContent' => [ sprintf( '<p>%s</p>', $content ) ],
	];
}

/**
 * Create multiple paragraph blocks from text with line breaks.
 *
 * @param string $content Content with potential multiple paragraphs.
 * @return array Array of paragraph block arrays.
 */
function create_paragraphs( string $content ) : array {
	// Split by double line breaks or <p> tags.
	$paragraphs = preg_split( '/\r\n\r\n|\n\n|<\/p>\s*<p[^>]*>/', $content );
	$blocks = [];

	foreach ( $paragraphs as $paragraph ) {
		$paragraph = trim( strip_tags( $paragraph, '<a><strong><em><br>' ) );
		if ( ! empty( $paragraph ) ) {
			$blocks[] = create_paragraph( $paragraph );
		}
	}

	return $blocks;
}

/**
 * Create a custom block with attributes and optional inner blocks.
 *
 * For blocks with inner blocks but no wrapper HTML, innerContent will be
 * an array of nulls (one per inner block). These nulls act as placeholders
 * that serialize_blocks() replaces with the serialized inner blocks.
 *
 * For blocks with wrapper HTML, use create_wrapper_block() instead.
 *
 * @param string $block_name Block name (e.g., 'theme/hero').
 * @param array  $attrs Block attributes.
 * @param array  $inner_blocks Inner blocks (optional).
 * @return array Block array.
 */
function create_block( string $block_name, array $attrs = [], array $inner_blocks = [] ) : array {
	// For blocks without wrapper HTML, innerContent is just nulls for each inner block.
	// The nulls tell serialize_blocks() where to place each serialized inner block.
	$inner_content = empty( $inner_blocks ) ? [] : array_fill( 0, count( $inner_blocks ), null );

	return [
		'blockName' => $block_name,
		'attrs' => $attrs,
		'innerBlocks' => $inner_blocks,
		'innerHTML' => '',
		'innerContent' => $inner_content,
	];
}

/**
 * Create a block with wrapper HTML and inner blocks.
 *
 * The innerContent array should interleave static HTML with null placeholders
 * for inner blocks. For example, a group block:
 * [ '<div class="wp-block-group">', null, '</div>' ]
 *
 * @param string $block_name Block name (e.g., 'core/group').
 * @param string $opening_html Opening wrapper HTML.
 * @param string $closing_html Closing wrapper HTML.
 * @param array  $attrs Block attributes.
 * @param array  $inner_blocks Inner blocks.
 * @return array Block array.
 */
function create_wrapper_block( string $block_name, string $opening_html, string $closing_html, array $attrs = [], array $inner_blocks = [] ) : array {
	// Build innerContent: opening HTML, null for each inner block, closing HTML.
	$inner_content = [ $opening_html ];
	foreach ( $inner_blocks as $inner_block ) {
		$inner_content[] = null;
	}
	$inner_content[] = $closing_html;

	return [
		'blockName' => $block_name,
		'attrs' => $attrs,
		'innerBlocks' => $inner_blocks,
		'innerHTML' => $opening_html . $closing_html,
		'innerContent' => $inner_content,
	];
}

/**
 * Strip HTML tags from a value.
 *
 * Useful for extracting plain text from content that may contain HTML.
 *
 * @param string $value Value to strip.
 * @return string Plain text value.
 */
function strip_html( string $value ) : string {
	return wp_strip_all_tags( $value );
}
