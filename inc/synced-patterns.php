<?php
/**
 * Synced Pattern Functions
 *
 * Utilities for creating and referencing synced (reusable) patterns.
 * Synced patterns are stored as wp_block posts and can be reused across multiple posts.
 *
 * @package HM\Block_Pattern_Transformer
 */

namespace HM\Block_Pattern_Transformer\Synced_Patterns;

use function HM\Block_Pattern_Transformer\Pattern_Transformer\get_pattern_by_slug;

/**
 * Find or create a synced (reusable) pattern.
 *
 * Synced patterns are stored as wp_block posts. This function checks if a synced
 * pattern exists for the given slug, and creates one if not.
 *
 * @param string $pattern_slug Pattern slug (e.g., 'theme/resources').
 * @return int|false Post ID of the synced pattern, or false on failure.
 */
function get_or_create( string $pattern_slug ) {
	global $wpdb;

	// Derive a title from the pattern slug.
	$title = ucwords( str_replace( [ '/', '-' ], ' ', $pattern_slug ) );

	// Check if synced pattern already exists by title.
	$existing_id = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'wp_block' AND post_title = %s AND post_status = 'publish' LIMIT 1",
			$title
		)
	);

	if ( $existing_id ) {
		return (int) $existing_id;
	}

	// Get pattern content from registry.
	$pattern_content = get_pattern_by_slug( $pattern_slug );

	if ( empty( $pattern_content ) ) {
		return false;
	}

	// Create new wp_block post.
	$post_id = wp_insert_post(
		[
			'post_title'   => $title,
			'post_content' => $pattern_content,
			'post_status'  => 'publish',
			'post_type'    => 'wp_block',
		],
		true
	);

	if ( is_wp_error( $post_id ) ) {
		return false;
	}

	return $post_id;
}

/**
 * Create a synced pattern block reference.
 *
 * Creates a wp:block reference that points to a synced pattern (wp_block post).
 *
 * @param int $synced_pattern_id Post ID of the synced pattern.
 * @return array Block structure for synced pattern reference.
 */
function create_block_reference( int $synced_pattern_id ) : array {
	return [
		'blockName'    => 'core/block',
		'attrs'        => [
			'ref' => $synced_pattern_id,
		],
		'innerBlocks'  => [],
		'innerHTML'    => '',
		'innerContent' => [],
	];
}
