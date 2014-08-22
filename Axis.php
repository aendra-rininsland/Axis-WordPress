<?php
/*
Plugin Name: Axis
Version: 0.1.0
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
		add_filter( 'mce_buttons', array( 'AxisWP', 'register_buttons' ) );
		add_filter( 'kses_allowed_protocols', array( 'AxisWP', 'allow_data_protocol' ) );
		add_filter( 'tiny_mce_before_init', array( 'AxisWP', 'tinymce_options' ) );
		add_filter( 'mce_external_plugins', array( 'AxisWP', 'register_tinymce_javascript' ) );
		add_action( 'admin_enqueue_scripts', array( 'AxisWP', 'add_stylesheet' ) );
	}

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

	public static function add_stylesheet(  ) {
		wp_enqueue_style(  'axisWP', plugins_url( 'css/axis.css',__file__ ), array(  'dashicons'  ), '1.0'  );
		$params = array(
			'axisJSPath' => plugins_url( 'bower_components/axisJS/dist/index.html', __file__ )
		);


		wp_localize_script( 'jquery', 'axisWP', $params ); // Hooking to jQuery just 'cause.
	}

	/**
	 * Add to extended_valid_elements for TinyMCE
	 *
	 * @param $init assoc. array of TinyMCE options
	 * @return $init the changed assoc. array
	 */

	public static function tinymce_options(  $init  ) {
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
	public static function allow_data_protocol(  $protocols  ){
		$protocols[] = 'data';
		return $protocols;
	}

	// Installation/Deactivation/Uninstallation
	// Based on: http://wordpress.stackexchange.com/a/25979/1682

	public static function init(  ) {
		is_null(  self::$instance  ) AND self::$instance = new self;
		return self::$instance;
	}

	public static function on_activation(  ) {
		if ( ! current_user_can(  'activate_plugins' ) ) {
			return;
		}

		$plugin_req = $_REQUEST['plugin'];
		$plugin = isset( $plugin_req ) ? $plugin_req : '';
		check_admin_referer(  "activate-plugin_{$plugin}"  );

		# Uncomment the following line to see the function in action
		# exit(  var_dump(  $_GET  )  );
	}

	public static function on_deactivation(  ) {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		$plugin_req = $_REQUEST['plugin'];
		$plugin = isset( $plugin_req  ) ? $plugin_req : '';
		check_admin_referer(  "deactivate-plugin_{$plugin}"  );

		# Uncomment the following line to see the function in action
		# exit(  var_dump(  $_GET  )  );
	}

	public static function on_uninstall(  ) {
			if ( ! current_user_can(  'activate_plugins' ) )
					return;
			check_admin_referer(  'bulk-plugins'  );

			// Important: Check if the file is the one
			// that was registered during the uninstall hook.
			if (  __FILE__ != WP_UNINSTALL_PLUGIN  )
					return;

			# Uncomment the following line to see the function in action
			# exit(  var_dump(  $_GET  )  );
	}
}
