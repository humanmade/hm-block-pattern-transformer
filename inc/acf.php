<?php
/**
 * ACF Block Extraction Functions
 *
 * Utilities for extracting data from Advanced Custom Fields (ACF) blocks.
 * ACF blocks store field data in the block's 'data' attribute.
 *
 * @package HM\Block_Pattern_Transformer
 */

namespace HM\Block_Pattern_Transformer\ACF;

/**
 * Extract field value from ACF block data.
 *
 * ACF blocks store data in the 'data' attribute of the block.
 *
 * @param array  $block Parsed block array.
 * @param string $field_name Field name to extract.
 * @param mixed  $default Default value if field not found.
 * @return mixed Field value.
 */
function extract_field( array $block, string $field_name, $default = '' ) {
	return $block['attrs']['data'][ $field_name ] ?? $default;
}

/**
 * Extract repeater field data.
 *
 * ACF repeater fields are stored as indexed arrays with field names suffixed by _0, _1, etc.
 *
 * @param array  $block Parsed block array.
 * @param string $repeater_name Repeater field name.
 * @param array  $field_names Field names within repeater.
 * @return array Array of repeater rows.
 */
function extract_repeater( array $block, string $repeater_name, array $field_names ) : array {
	$data = $block['attrs']['data'] ?? [];
	$count = (int) ( $data[ $repeater_name ] ?? 0 );
	$rows = [];

	for ( $i = 0; $i < $count; $i++ ) {
		$row = [];
		foreach ( $field_names as $field_name ) {
			$key = "{$repeater_name}_{$i}_{$field_name}";
			$row[ $field_name ] = $data[ $key ] ?? '';
		}
		$rows[] = $row;
	}

	return $rows;
}
