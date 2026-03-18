<?php
/**
 * Block Creation Functions
 *
 * Utilities for creating WordPress blocks programmatically.
 * These functions return properly structured block arrays that can be
 * serialized and used in post content.
 *
 * @package HM\Rehydrator
 */

namespace HM\Rehydrator\Blocks;

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

	$html = sprintf( '<h%d class="wp-block-heading">%s</h%d>', $level, $content, $level );

	return create_block(
		block_name: 'core/heading',
		attrs: [ 'level' => $level ],
		inner_html: $html
	);
}

/**
 * Create a paragraph block.
 *
 * Content is sanitized using wp_kses. The allowed HTML tags can be filtered
 * via the 'hm.rehydrator.paragraph_allowed_html' hook.
 *
 * @param string $content Paragraph content.
 * @return array Paragraph block array.
 */
function create_paragraph( string $content ) : array {
	$content = trim( $content );

	// Sanitize HTML - allow common inline elements.
	$allowed_html = apply_filters( 'hm.rehydrator.paragraph_allowed_html', [
		'a'      => [
			'href'   => true,
			'title'  => true,
			'target' => true,
			'rel'    => true,
			'class'  => true,
		],
		'strong' => [],
		'b'      => [],
		'em'     => [],
		'i'      => [],
		'br'     => [],
		'code'   => [],
		'sub'    => [],
		'sup'    => [],
	] );
	$content = wp_kses( $content, $allowed_html );

	// Convert single line breaks to <br> tags.
	$content = nl2br( $content, false );

	$html = sprintf( '<p>%s</p>', $content );

	return create_block(
		block_name: 'core/paragraph',
		inner_html: $html
	);
}

/**
 * Create multiple paragraph blocks from text with line breaks.
 *
 * Double line breaks (or existing paragraph tags) are used to split content
 * into separate paragraph blocks. Each paragraph is sanitized via create_paragraph().
 *
 * @param string $content Content with potential multiple paragraphs.
 * @return array Array of paragraph block arrays.
 */
function create_paragraphs( string $content ) : array {
	// Split by double line breaks or <p> tags.
	$paragraphs = preg_split( '/\r\n\r\n|\n\n|<\/p>\s*<p[^>]*>/', $content );
	$blocks = [];

	foreach ( $paragraphs as $paragraph ) {
		$paragraph = trim( $paragraph );
		if ( ! empty( $paragraph ) ) {
			$blocks[] = create_paragraph( $paragraph );
		}
	}

	return $blocks;
}

/**
 * Create a leaf block with attributes and optional HTML content.
 *
 * Use this for blocks that don't contain other blocks (headings, paragraphs,
 * images, etc.). For container blocks with inner blocks, use create_wrapper_block().
 *
 * @param string $block_name Block name (e.g., 'core/heading').
 * @param array  $attrs Block attributes.
 * @param string $inner_html HTML content for the block.
 * @return array Block array.
 */
function create_block( string $block_name, array $attrs = [], string $inner_html = '' ) : array {
	return [
		'blockName' => $block_name,
		'attrs' => $attrs,
		'innerBlocks' => [],
		'innerHTML' => $inner_html,
		'innerContent' => empty( $inner_html ) ? [] : [ $inner_html ],
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
