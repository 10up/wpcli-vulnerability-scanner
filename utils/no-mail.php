<?php
/**
 * This file is copied as a mu-plugin into new WP installs to reroute normal
 * mails into log entries.
 */

/**
 * Replace WP native pluggable wp_mail function for test purposes.
 *
 * @param string|string[] $to Array or comma-separated list of email addresses to send message.
 * @return bool Whether the email was sent successfully.
 *
 * @phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- WP native function.
 */
function wp_mail( $to ) {
	if ( is_array( $to ) ) {
		$to = join( ', ', $to );
	}

	// Log for testing purposes
	WP_CLI::log( "WP-CLI test suite: Sent email to {$to}." );

	// Assume sending mail always succeeds.
	return true;
}
// phpcs:enable
