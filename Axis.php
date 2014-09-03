<?php
/*
Plugin Name: Axis
Version: 0.1.1
Description: Plugin for adding charts to WordPress posts
Author: Ændrew Rininsland
Author URI: http://www.aendrew.com
Plugin URI: http://www.thetimes.co.uk
Text Domain: axiswp
Domain Path: /languages
License: MIT
*/

defined( 'ABSPATH' ) OR exit;

register_activation_hook( __FILE__, array( 'AxisWP', 'on_activation' ) );
register_deactivation_hook( __FILE__, array( 'AxisWP', 'on_deactivation' ) );
register_uninstall_hook( __FILE__, array( 'AxisWP', 'on_uninstall' ) );

add_action( 'plugins_loaded', array( 'AxisWP', 'init' ) );

class AxisWP {
	protected static $instance;

	public function __construct() {
		// Backend stuff
		add_filter( 'mce_buttons', array( 'AxisWP', 'register_buttons' ) );
		add_filter( 'kses_allowed_protocols', array( 'AxisWP', 'allow_data_protocol' ) );
		add_filter( 'tiny_mce_before_init', array( 'AxisWP', 'tinymce_options' ) );
		add_filter( 'mce_external_plugins', array( 'AxisWP', 'register_tinymce_javascript' ) );
		add_action( 'admin_enqueue_scripts', array( 'AxisWP', 'add_admin_stylesheet' ) );

		// Frontend stuff
		add_filter( 'the_content', array( 'AxisWP', 'convert_png_to_interactive' ) );
		add_action( 'wp_enqueue_scripts', array( 'AxisWP', 'add_frontend_js' ) );
	}

	// Client-side (frontend) stuff

	/**
	 * Replaces the data-uri PNGs in the backend with a div.
	 */
	public static function convert_png_to_interactive( $content ) {
		$dom = new DOMDocument;
		$dom->loadHTML( $content );
		$xpath = new DOMXPath( $dom );
		$charts = $xpath->query( "//*[contains(@class, 'axisChart')]" );

		foreach ( $charts as $chart ){
			$chartConfig = $chart->getAttribute( 'data-axisjs' );
			$div = $dom->createElement( 'div' );
			$div->setAttribute( 'data-axisjs', $chartConfig );
			$div->setAttribute( 'class', 'axisChart' );
			$chart->parentNode->replaceChild( $div, $chart );
		}

		$content = $dom->saveHTML();

		return $content;
	}

	/**
	 *  Adds frontend JavaScript to the page.
	 */

	public static function add_frontend_js() {
		if ( ! is_admin() ) {
			wp_enqueue_script( 'd3js', plugins_url( 'bower_components/d3/d3.min.js', __file__ ), array( 'jquery' ), '3.4.11', true );
			wp_enqueue_script( 'c3js', plugins_url( 'bower_components/c3/c3.min.js', __file__ ), array( 'jquery', 'd3js' ), '0.3.0', true );
			wp_enqueue_script( 'axis', plugins_url( 'js/axis.js', __file__ ), array( 'jquery', 'c3js', 'd3js' ), '0.1.0', true );
			wp_enqueue_style( 'c3jsCSS', plugins_url( 'bower_components/c3/c3.css', __file__ ) );
		}
	}


	// Admin-side stuff

	/**
	 * Adds the AxisWP button to TinyMCE.
	 */
	public static function register_buttons( $buttons ) {
		array_push( $buttons, 'separator', 'Axis' );
		return $buttons;
	}

	/**
	 * Registers the TinyMCE plugin.
	 */
	public static function register_tinymce_javascript( $plugin_array ) {
		$plugin_array['Axis'] = plugins_url( '/js/axisJS-tinymce-plugin.js', __file__ );
		return $plugin_array;
	}

	/**
	 * Enqueues the icon stylesheet.
	 */

	public static function add_admin_stylesheet() {
		wp_enqueue_style( 'axisWP', plugins_url( 'css/axis.css', __file__ ), array( 'dashicons' ), '1.0' );
		$params = array(
			'axisJSPath' => plugins_url( 'bower_components/axisjs/dist/index.html', __file__ )
		);


		wp_localize_script( 'jquery', 'axisWP', $params ); // Hooking to jQuery just 'cause.
	}

	/**
	 * Add to extended_valid_elements for TinyMCE
	 *
	 * @param $init assoc. array of TinyMCE options
	 * @return $init the changed assoc. array
	 */

	public static function tinymce_options( $init ) {
		// Command separated string of extended elements
		$ext = '*[*]';

		// Add to extended_valid_elements if it alreay exists
		if ( isset( $init['extended_valid_elements'] ) ) {
						$init['extended_valid_elements'] .= ',' . $ext;
		} else {
						$init['extended_valid_elements'] = $ext;
		}

		$init['paste_data_images'] = true;

		// Super important: return $init!
		return $init;
	}

	/**
	 * Enables saving the charts as data-URI PNGs.
	 */
	public static function allow_data_protocol( $protocols ){
		$protocols[] = 'data';
		return $protocols;
	}

	// Installation/Deactivation/Uninstallation
	// Based on: http://wordpress.stackexchange.com/a/25979/1682

	public static function init() {
		is_null( self::$instance ) AND self::$instance = new self;
		return self::$instance;
	}

	public static function on_activation() {
		if ( ! current_user_can(  'activate_plugins' ) ) {
			return;
		}

		$plugin_req = $_REQUEST['plugin'];
		$plugin = isset( $plugin_req ) ? $plugin_req : '';
		check_admin_referer( "activate-plugin_{$plugin}" );

		# Uncomment the following line to see the function in action
		# exit(  var_dump(  $_GET  )  );
	}

	public static function on_deactivation() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		$plugin_req = $_REQUEST['plugin'];
		$plugin = isset( $plugin_req  ) ? $plugin_req : '';
		check_admin_referer(  "deactivate-plugin_{$plugin}"  );

		# Uncomment the following line to see the function in action
		# exit(  var_dump(  $_GET  )  );
	}

	public static function on_uninstall() {
			if ( ! current_user_can(  'activate_plugins' ) )
					return;
			check_admin_referer( 'bulk-plugins' );

			// Important: Check if the file is the one
			// that was registered during the uninstall hook.
			if (  __FILE__ != WP_UNINSTALL_PLUGIN  )
					return;

			# Uncomment the following line to see the function in action
			# exit(  var_dump(  $_GET  )  );
	}
}
