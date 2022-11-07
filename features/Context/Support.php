<?php
/**
 * Utility functions used by the Behat steps.
 */

namespace WP_CLI\Tests\Context;

use Behat\Behat\Exception\PendingException;
use Exception;
use Mustangostang\Spyc;

trait Support {


	protected function assert_regex( $regex, $actual ) {
		if ( ! preg_match( $regex, $actual ) ) {
			throw new Exception( 'Actual value: ' . var_export( $actual, true ) );
		}
	}

	protected function assert_not_regex( $regex, $actual ) {
		if ( preg_match( $regex, $actual ) ) {
			throw new Exception( 'Actual value: ' . var_export( $actual, true ) );
		}
	}

	protected function assert_equals( $expected, $actual ) {
		// phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison -- Deliberate loose comparison.
		if ( $expected != $actual ) {
			throw new Exception( 'Actual value: ' . var_export( $actual, true ) );
		}
	}

	protected function assert_not_equals( $expected, $actual ) {
		// phpcs:ignore WordPress.PHP.StrictComparisons.LooseComparison -- Deliberate loose comparison.
		if ( $expected == $actual ) {
			throw new Exception( 'Actual value: ' . var_export( $actual, true ) );
		}
	}

	protected function assert_numeric( $actual ) {
		if ( ! is_numeric( $actual ) ) {
			throw new Exception( 'Actual value: ' . var_export( $actual, true ) );
		}
	}

	protected function assert_not_numeric( $actual ) {
		if ( is_numeric( $actual ) ) {
			throw new Exception( 'Actual value: ' . var_export( $actual, true ) );
		}
	}

	protected function check_string( $output, $expected, $action, $message = false, $strictly = false ) {
		// Strip ANSI color codes before comparing strings.
		if ( ! $strictly ) {
			$output = preg_replace( '/\e[[][A-Za-z0-9];?[0-9]*m?/', '', $output );
		}

		switch ( $action ) {
			case 'be':
				$r = rtrim( $output, "\n" ) === $expected;
				break;

			case 'contain':
				$r = false !== strpos( $output, $expected );
				break;

			case 'not contain':
				$r = false === strpos( $output, $expected );
				break;

			default:
				throw new PendingException();
		}

		if ( ! $r ) {
			if ( false === $message ) {
				$message = $output;
			}
			throw new Exception( $message );
		}
	}

	protected function compare_tables( $expected_rows, $actual_rows, $output ) {
		// The first row is the header and must be present.
		if ( $expected_rows[0] !== $actual_rows[0] ) {
			throw new Exception( $output );
		}

		unset( $actual_rows[0] );
		unset( $expected_rows[0] );

		$missing_rows = array_diff( $expected_rows, $actual_rows );
		if ( ! empty( $missing_rows ) ) {
			throw new Exception( $output );
		}
	}

	protected function compare_contents( $expected, $actual ) {
		if ( gettype( $expected ) !== gettype( $actual ) ) {
			return false;
		}

		if ( is_object( $expected ) ) {
			foreach ( get_object_vars( $expected ) as $name => $value ) {
				if ( ! $this->compare_contents( $value, $actual->$name ) ) {
					return false;
				}
			}
		} elseif ( is_array( $expected ) ) {
			foreach ( $expected as $key => $value ) {
				if ( ! $this->compare_contents( $value, $actual[ $key ] ) ) {
					return false;
				}
			}
		} else {
			return $expected === $actual;
		}

		return true;
	}

	/**
	 * Compare two strings containing JSON to ensure that $actualJson contains at
	 * least what the JSON string $expectedJson contains.
	 *
	 * @param string $actual_json   the JSON string to be tested
	 * @param string $expected_json the expected JSON string
	 *
	 * @return bool Whether or not $actual_json contains $expected_json.
	 *
	 * Examples:
	 *   expected: {'a':1,'array':[1,3,5]}
	 *
	 *   1 )
	 *   actual: {'a':1,'b':2,'c':3,'array':[1,2,3,4,5]}
	 *   return: true
	 *
	 *   2 )
	 *   actual: {'b':2,'c':3,'array':[1,2,3,4,5]}
	 *   return: false
	 *     element 'a' is missing from the root object
	 *
	 *   3 )
	 *   actual: {'a':0,'b':2,'c':3,'array':[1,2,3,4,5]}
	 *   return: false
	 *     the value of element 'a' is not 1
	 *
	 *   4 )
	 *   actual: {'a':1,'b':2,'c':3,'array':[1,2,4,5]}
	 *   return: false
	 *     the contents of 'array' does not include 3
	 */
	protected function check_that_json_string_contains_json_string( $actual_json, $expected_json ) {
		$actual_value   = json_decode( $actual_json );
		$expected_value = json_decode( $expected_json );

		if ( ! $actual_value ) {
			return false;
		}

		return $this->compare_contents( $expected_value, $actual_value );
	}

	/**
	 * Compare two strings to confirm $actualCSV contains $expectedCSV
	 * Both strings are expected to have headers for their CSVs.
	 * $actualCSV must match all data rows in $expectedCSV
	 *
	 * @param string $actual_csv   A CSV string
	 * @param array  $expected_csv A nested array of values
	 * @return bool   Whether $actual_csv contains $expected_csv
	 */
	protected function check_that_csv_string_contains_values( $actual_csv, $expected_csv ) {
		$actual_csv = array_map( 'str_getcsv', explode( PHP_EOL, $actual_csv ) );

		if ( empty( $actual_csv ) ) {
			return false;
		}

		// Each sample must have headers.
		$actual_headers   = array_values( array_shift( $actual_csv ) );
		$expected_headers = array_values( array_shift( $expected_csv ) );

		// Each expected_csv must exist somewhere in actual_csv in the proper column.
		$expected_result = 0;
		foreach ( $expected_csv as $expected_row ) {
			$expected_row = array_combine( $expected_headers, $expected_row );
			foreach ( $actual_csv as $actual_row ) {
				if ( count( $actual_headers ) !== count( $actual_row ) ) {
					continue;
				}

				$actual_row = array_intersect_key(
					array_combine(
						$actual_headers,
						$actual_row
					),
					$expected_row
				);

				if ( $actual_row === $expected_row ) {
					$expected_result++;
				}
			}
		}

		return $expected_result >= count( $expected_csv );
	}

	/**
	 * Compare two strings containing YAML to ensure that $actualYaml contains at
	 * least what the YAML string $expectedYaml contains.
	 *
	 * @param string $actual_yaml   the YAML string to be tested
	 * @param string $expected_yaml the expected YAML string
	 *
	 * @return bool whether or not $actual_yaml contains $expected_json
	 */
	protected function check_that_yaml_string_contains_yaml_string( $actual_yaml, $expected_yaml ) {
		$actual_value   = Spyc::YAMLLoad( $actual_yaml );
		$expected_value = Spyc::YAMLLoad( $expected_yaml );

		if ( ! $actual_value ) {
			return false;
		}

		return $this->compare_contents( $expected_value, $actual_value );
	}
}
