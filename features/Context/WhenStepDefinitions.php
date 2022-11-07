<?php

namespace WP_CLI\Tests\Context;

use WP_CLI\Process;
use Exception;

trait WhenStepDefinitions {


	public function wpcli_tests_invoke_proc( $proc, $mode ) {
		$map    = array(
			'run' => 'run_check_stderr',
			'try' => 'run',
		);
		$method = $map[ $mode ];

		return $proc->$method();
	}

	public function wpcli_tests_capture_email_sends( $stdout ) {
		$stdout = preg_replace( '#WP-CLI test suite: Sent email to.+\n?#', '', $stdout, -1, $email_sends );

		return array( $stdout, $email_sends );
	}

	/**
	 * @When /^I launch in the background `([^`]+)`$/
	 */
	public function when_i_launch_in_the_background( $cmd ) {
		$this->background_proc( $cmd );
	}

	/**
	 * @When /^I (run|try) `([^`]+)`$/
	 */
	public function when_i_run( $mode, $cmd ) {
		$cmd          = $this->replace_variables( $cmd );
		$this->result = $this->wpcli_tests_invoke_proc( $this->proc( $cmd ), $mode );
		list( $this->result->stdout, $this->email_sends ) = $this->wpcli_tests_capture_email_sends( $this->result->stdout );
	}

	/**
	 * @When /^I (run|try) `([^`]+)` from '([^\s]+)'$/
	 */
	public function when_i_run_from_a_subfolder( $mode, $cmd, $subdir ) {
		$cmd          = $this->replace_variables( $cmd );
		$this->result = $this->wpcli_tests_invoke_proc( $this->proc( $cmd, array(), $subdir ), $mode );
		list( $this->result->stdout, $this->email_sends ) = $this->wpcli_tests_capture_email_sends( $this->result->stdout );
	}

	/**
	 * @When /^I (run|try) the previous command again$/
	 */
	public function when_i_run_the_previous_command_again( $mode ) {
		if ( ! isset( $this->result ) ) {
			throw new Exception( 'No previous command.' );
		}

		$proc         = Process::create( $this->result->command, $this->result->cwd, $this->result->env );
		$this->result = $this->wpcli_tests_invoke_proc( $proc, $mode );
		list( $this->result->stdout, $this->email_sends ) = $this->wpcli_tests_capture_email_sends( $this->result->stdout );
	}
}

