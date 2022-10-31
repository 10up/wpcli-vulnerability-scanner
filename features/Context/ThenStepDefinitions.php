<?php

namespace WP_CLI\Tests\Context;

use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Exception;
use Requests;
use RuntimeException;

trait ThenStepDefinitions {

	use Support;

	/**
	 * @Then /^the return code should( not)? be (\d+)$/
	 */
	public function then_the_return_code_should_be( $not, $return_code ) {
		if (
			( ! $not && (int) $return_code !== $this->result->return_code )
			|| ( $not && (int) $return_code === $this->result->return_code )
		) {
			throw new RuntimeException( $this->result );
		}
	}

	/**
	 * @Then /^(STDOUT|STDERR) should( strictly)? (be|contain|not contain):$/
	 */
	public function then_stdout_stderr_should_contain( $stream, $strictly, $action, PyStringNode $expected ) {

		$stream = strtolower( $stream );

		$expected = $this->replace_variables( (string) $expected );

		$this->check_string( $this->result->$stream, $expected, $action, $this->result, (bool) $strictly );
	}

	/**
	 * @Then /^(STDOUT|STDERR) should be a number$/
	 */
	public function then_stdout_stderr_should_be_a_number( $stream ) {

		$stream = strtolower( $stream );

		$this->assert_numeric( trim( $this->result->$stream, "\n" ) );
	}

	/**
	 * @Then /^(STDOUT|STDERR) should not be a number$/
	 */
	public function then_stdout_stderr_should_not_be_a_number( $stream ) {

		$stream = strtolower( $stream );

		$this->assert_not_numeric( trim( $this->result->$stream, "\n" ) );
	}

	/**
	 * @Then /^STDOUT should be a table containing rows:$/
	 */
	public function then_stdout_should_be_a_table_containing_rows( TableNode $expected ) {
		$output      = $this->result->stdout;
		$actual_rows = explode( "\n", rtrim( $output, "\n" ) );

		$expected_rows = array();
		foreach ( $expected->getRows() as $row ) {
			$expected_rows[] = $this->replace_variables( implode( "\t", $row ) );
		}

		$this->compare_tables( $expected_rows, $actual_rows, $output );
	}

	/**
	 * @Then /^STDOUT should end with a table containing rows:$/
	 */
	public function then_stdout_should_end_with_a_table_containing_rows( TableNode $expected ) {
		$output      = $this->result->stdout;
		$actual_rows = explode( "\n", rtrim( $output, "\n" ) );

		$expected_rows = array();
		foreach ( $expected->getRows() as $row ) {
			$expected_rows[] = $this->replace_variables( implode( "\t", $row ) );
		}

		$start = array_search( $expected_rows[0], $actual_rows, true );

		if ( false === $start ) {
			throw new Exception( $this->result );
		}

		$this->compare_tables( $expected_rows, array_slice( $actual_rows, $start ), $output );
	}

	/**
	 * @Then /^STDOUT should be JSON containing:$/
	 */
	public function then_stdout_should_be_json_containing( PyStringNode $expected ) {
		$output   = $this->result->stdout;
		$expected = $this->replace_variables( (string) $expected );

		if ( ! $this->check_that_json_string_contains_json_string( $output, $expected ) ) {
			throw new Exception( $this->result );
		}
	}

	/**
	 * @Then /^STDOUT should be a JSON array containing:$/
	 */
	public function then_stdout_should_be_a_json_array_containing( PyStringNode $expected ) {
		$output   = $this->result->stdout;
		$expected = $this->replace_variables( (string) $expected );

		$actual_values   = json_decode( $output );
		$expected_values = json_decode( $expected );

		$missing = array_diff( $expected_values, $actual_values );
		if ( ! empty( $missing ) ) {
			throw new Exception( $this->result );
		}
	}

	/**
	 * @Then /^STDOUT should be CSV containing:$/
	 */
	public function then_stdout_should_be_csv_containing( TableNode $expected ) {
		$output = $this->result->stdout;

		$expected_rows = $expected->getRows();
		foreach ( $expected as &$row ) {
			foreach ( $row as &$value ) {
				$value = $this->replace_variables( $value );
			}
		}

		if ( ! $this->check_that_csv_string_contains_values( $output, $expected_rows ) ) {
			throw new Exception( $this->result );
		}
	}

	/**
	 * @Then /^STDOUT should be YAML containing:$/
	 */
	public function then_stdout_should_be_yaml_containing( PyStringNode $expected ) {
		$output   = $this->result->stdout;
		$expected = $this->replace_variables( (string) $expected );

		if ( ! $this->check_that_yaml_string_contains_yaml_string( $output, $expected ) ) {
			throw new Exception( $this->result );
		}
	}

	/**
	 * @Then /^(STDOUT|STDERR) should be empty$/
	 */
	public function then_stdout_stderr_should_be_empty( $stream ) {

		$stream = strtolower( $stream );

		if ( ! empty( $this->result->$stream ) ) {
			throw new Exception( $this->result );
		}
	}

	/**
	 * @Then /^(STDOUT|STDERR) should not be empty$/
	 */
	public function then_stdout_stderr_should_not_be_empty( $stream ) {

		$stream = strtolower( $stream );

		if ( '' === rtrim( $this->result->$stream, "\n" ) ) {
			throw new Exception( $this->result );
		}
	}

	/**
	 * @Then /^(STDOUT|STDERR) should be a version string (<|<=|>|>=|==|=|!=|<>) ([+\w.{}-]+)$/
	 */
	public function then_stdout_stderr_should_be_a_specific_version_string( $stream, $operator, $goal_ver ) {
		$goal_ver = $this->replace_variables( $goal_ver );
		$stream   = strtolower( $stream );
		if ( false === version_compare( trim( $this->result->$stream, "\n" ), $goal_ver, $operator ) ) {
			throw new Exception( $this->result );
		}
	}

	/**
	 * @Then /^the (.+) (file|directory) should( strictly)? (exist|not exist|be:|contain:|not contain:)$/
	 */
	public function then_a_specific_file_folder_should_exist( $path, $type, $strictly, $action, $expected = null ) {
		$path = $this->replace_variables( $path );

		// If it's a relative path, make it relative to the current test dir.
		if ( '/' !== $path[0] ) {
			$path = $this->variables['RUN_DIR'] . "/$path";
		}

		$exists = function ( $path ) use ( $type ) {
			// Clear the stat cache for the path first to avoid
			// potentially inaccurate results when files change outside of PHP.
			// See https://www.php.net/manual/en/function.clearstatcache.php
			clearstatcache( false, $path );

			if ( 'directory' === $type ) {
				return is_dir( $path );
			}

			return file_exists( $path );
		};

		switch ( $action ) {
			case 'exist':
				if ( ! $exists( $path ) ) {
					throw new Exception( "$path doesn't exist." );
				}
				break;
			case 'not exist':
				if ( $exists( $path ) ) {
					throw new Exception( "$path exists." );
				}
				break;
			default:
				if ( ! $exists( $path ) ) {
					throw new Exception( "$path doesn't exist." );
				}
				$action   = substr( $action, 0, -1 );
				$expected = $this->replace_variables( (string) $expected );
				if ( 'file' === $type ) {
					$contents = file_get_contents( $path );
				} elseif ( 'directory' === $type ) {
					$files = glob( rtrim( $path, '/' ) . '/*' );
					foreach ( $files as &$file ) {
						$file = str_replace( $path . '/', '', $file );
					}
					$contents = implode( PHP_EOL, $files );
				}
				$this->check_string( $contents, $expected, $action, false, (bool) $strictly );
		}
	}

	/**
	 * @Then /^the contents of the (.+) file should( not)? match (((\/.+\/)|(#.+#))([a-z]+)?)$/
	 */
	public function then_the_contents_of_a_specific_file_should_match( $path, $not, $expected ) {
		$path = $this->replace_variables( $path );
		// If it's a relative path, make it relative to the current test dir.
		if ( '/' !== $path[0] ) {
			$path = $this->variables['RUN_DIR'] . "/$path";
		}
		$contents = file_get_contents( $path );
		if ( $not ) {
			$this->assert_not_regex( $expected, $contents );
		} else {
			$this->assert_regex( $expected, $contents );
		}
	}

	/**
	 * @Then /^(STDOUT|STDERR) should( not)? match (((\/.+\/)|(#.+#))([a-z]+)?)$/
	 */
	public function then_stdout_stderr_should_match_a_string( $stream, $not, $expected ) {
		$stream = strtolower( $stream );
		if ( $not ) {
			$this->assert_not_regex( $expected, $this->result->$stream );
		} else {
			$this->assert_regex( $expected, $this->result->$stream );
		}
	}

	/**
	 * @Then /^an email should (be sent|not be sent)$/
	 */
	public function then_an_email_should_be_sent( $expected ) {
		if ( 'be sent' === $expected ) {
			$this->assert_not_equals( 0, $this->email_sends );
		} elseif ( 'not be sent' === $expected ) {
			$this->assert_equals( 0, $this->email_sends );
		} else {
			throw new Exception( 'Invalid expectation' );
		}
	}

	/**
	 * @Then the HTTP status code should be :code
	 */
	public function then_the_http_status_code_should_be( $return_code ) {
		$response = Requests::request( 'http://localhost:8080' );
		$this->assert_equals( $return_code, $response->status_code );
	}
}
