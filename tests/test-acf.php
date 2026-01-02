<?php
/**
 * ACF Functions Tests
 *
 * @package HM\Block_Pattern_Transformer\Tests
 */

namespace HM\Block_Pattern_Transformer\Tests;

use PHPUnit\Framework\TestCase;
use HM\Block_Pattern_Transformer\ACF;

/**
 * Test ACF extraction functions.
 */
class ACFTest extends TestCase {

	/**
	 * Test extract_field with existing field.
	 */
	public function test_extract_field_returns_value() {
		$block = [
			'blockName' => 'acf/hero',
			'attrs' => [
				'data' => [
					'title' => 'Hello World',
					'subtitle' => 'Welcome to the site',
				],
			],
		];

		$result = ACF\extract_field( $block, 'title' );
		$this->assertEquals( 'Hello World', $result );
	}

	/**
	 * Test extract_field with missing field returns default.
	 */
	public function test_extract_field_returns_default_for_missing() {
		$block = [
			'blockName' => 'acf/hero',
			'attrs' => [
				'data' => [],
			],
		];

		$result = ACF\extract_field( $block, 'title', 'Default Title' );
		$this->assertEquals( 'Default Title', $result );
	}

	/**
	 * Test extract_field with missing data attribute.
	 */
	public function test_extract_field_handles_missing_data_attribute() {
		$block = [
			'blockName' => 'acf/hero',
			'attrs' => [],
		];

		$result = ACF\extract_field( $block, 'title', 'Fallback' );
		$this->assertEquals( 'Fallback', $result );
	}

	/**
	 * Test extract_repeater with multiple rows.
	 */
	public function test_extract_repeater_returns_rows() {
		$block = [
			'blockName' => 'acf/stats',
			'attrs' => [
				'data' => [
					'stats' => 3,
					'stats_0_number' => '95%',
					'stats_0_label' => 'Satisfaction',
					'stats_1_number' => '50+',
					'stats_1_label' => 'Countries',
					'stats_2_number' => '10M',
					'stats_2_label' => 'Users',
				],
			],
		];

		$result = ACF\extract_repeater( $block, 'stats', [ 'number', 'label' ] );

		$this->assertCount( 3, $result );
		$this->assertEquals( '95%', $result[0]['number'] );
		$this->assertEquals( 'Satisfaction', $result[0]['label'] );
		$this->assertEquals( '50+', $result[1]['number'] );
		$this->assertEquals( 'Countries', $result[1]['label'] );
	}

	/**
	 * Test extract_repeater with zero rows.
	 */
	public function test_extract_repeater_returns_empty_for_zero_rows() {
		$block = [
			'blockName' => 'acf/stats',
			'attrs' => [
				'data' => [
					'stats' => 0,
				],
			],
		];

		$result = ACF\extract_repeater( $block, 'stats', [ 'number', 'label' ] );
		$this->assertEmpty( $result );
	}

	/**
	 * Test extract_repeater with missing count field.
	 */
	public function test_extract_repeater_handles_missing_count() {
		$block = [
			'blockName' => 'acf/stats',
			'attrs' => [
				'data' => [],
			],
		];

		$result = ACF\extract_repeater( $block, 'stats', [ 'number', 'label' ] );
		$this->assertEmpty( $result );
	}

	/**
	 * Test extract_repeater with partial data in rows.
	 */
	public function test_extract_repeater_handles_partial_row_data() {
		$block = [
			'blockName' => 'acf/stats',
			'attrs' => [
				'data' => [
					'stats' => 2,
					'stats_0_number' => '95%',
					// Missing stats_0_label
					'stats_1_label' => 'Countries',
					// Missing stats_1_number
				],
			],
		];

		$result = ACF\extract_repeater( $block, 'stats', [ 'number', 'label' ] );

		$this->assertCount( 2, $result );
		$this->assertEquals( '95%', $result[0]['number'] );
		$this->assertEquals( '', $result[0]['label'] );
		$this->assertEquals( '', $result[1]['number'] );
		$this->assertEquals( 'Countries', $result[1]['label'] );
	}
}
