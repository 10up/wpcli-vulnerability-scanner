<?php
/**
 * Polyfills used by Behat to support multiple versions of WP.
 *
 * This file will get installed as a must-use plugin in WP installs that are run
 * by the functional tests.
 */

/*
 * Add a polyfill for the get_theme_file_uri(), as it is required for the
 * TwentyTwenty theme's starter content, and will fatal the site if you install
 * WP 5.3 first (setting TwentyTwenty as the active theme) and then downgrade
 * to a version of WP lower than 4.7.
 *
 * Note: This is a quick fix, and a cleaner solution would be to change the
 * active theme on downgrading, if the current theme declares it is not
 * supported.
 *
 * See: https://github.com/WordPress/twentytwenty/issues/973
 */
if ( ! function_exists( 'get_theme_file_uri' ) ) {
	/**
	 * Retrieves the URL of a file in the theme.
	 *
	 * Searches in the stylesheet directory before the template directory so themes
	 * which inherit from a parent theme can just override one file.
	 *
	 * @since 4.7.0
	 *
	 * @param string $file Optional. File to search for in the stylesheet directory.
	 * @return string The URL of the file.
	 */
	function get_theme_file_uri( $file = '' ) {
		$file = ltrim( $file, '/' );

		if ( empty( $file ) ) {
			$url = get_stylesheet_directory_uri();
		} elseif ( file_exists( get_stylesheet_directory() . '/' . $file ) ) {
			$url = get_stylesheet_directory_uri() . '/' . $file;
		} else {
			$url = get_template_directory_uri() . '/' . $file;
		}

		/**
		 * Filters the URL to a file in the theme.
		 *
		 * @since 4.7.0
		 *
		 * @param string $url  The file URL.
		 * @param string $file The requested file to search for.
		 */
		return apply_filters( 'theme_file_uri', $url, $file );
	}
}

/*
 * Add a polyfill for the is_customize_preview(), as it is required for the
 * TwentyTwenty theme's starter content, and will fatal the site if you install
 * WP 5.3 first (setting TwentyTwenty as the active theme) and then downgrade
 * to a version of WP lower than 4.0.
 *
 * Note: This is a quick fix, and a cleaner solution would be to change the
 * active theme on downgrading, if the current theme declares it is not
 * supported.
 *
 * See: https://github.com/WordPress/twentytwenty/issues/973
 */
if ( ! function_exists( 'is_customize_preview' ) ) {
	/**
	 * Whether the site is being previewed in the Customizer.
	 *
	 * @since 4.0.0
	 *
	 * @global WP_Customize_Manager $wp_customize Customizer instance.
	 *
	 * @return bool True if the site is being previewed in the Customizer, false otherwise.
	 */
	function is_customize_preview() {
		return false;
	}
}
