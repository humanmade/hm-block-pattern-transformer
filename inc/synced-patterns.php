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
 * Meta key used to store the unique key for synced pattern lookup.
 */
const SYNCED_PATTERN_KEY_META = '_hm_synced_pattern_key';

/**
 * Find or create a synced (reusable) pattern.
 *
 * Synced patterns are stored as wp_block posts. This function checks if a synced
 * pattern with the given key exists, and creates one from the pattern slug if not.
 *
 * The key should be unique for each distinct synced pattern you want to create.
 * For example, the same pattern might be used with different keys for different
 * contexts: 'cta-blog-posts' and 'cta-case-studies'.
 *
 * @param string $key Unique key to identify this synced pattern instance.
 * @param string $pattern_slug Pattern slug to get content from (e.g., 'theme/cta').
 * @param string $title Display title for the synced pattern.
 * @return int|false Post ID of the synced pattern, or false on failure.
 */
function get_or_create( string $key, string $pattern_slug, string $title ) {
	// Look up existing synced pattern by key.
	$existing = get_posts( [
		'post_type'      => 'wp_block',
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'fields'         => 'ids',
		'meta_query'     => [ // phpcs:ignore HM.Performance.SlowMetaQuery
			[
				'key'   => SYNCED_PATTERN_KEY_META,
				'value' => $key,
			],
		],
	] );

	if ( ! empty( $existing ) ) {
		return (int) $existing[0];
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

	// Store the key in post_meta for future lookups.
	update_post_meta( $post_id, SYNCED_PATTERN_KEY_META, $key );

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
