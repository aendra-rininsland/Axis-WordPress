<?php
/*
Plugin Name: Axis
Version: 1.0.1
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

	/**
	 * Constructor. Mainly adds filters and actions.
	 *
	 * @return null
	 */

	public function __construct() {
		// Backend stuff
		// @TODO This probably needs an is_admin()...
		add_filter( 'mce_buttons', array( 'AxisWP', 'register_buttons' ) );
		add_filter( 'kses_allowed_protocols', array( 'AxisWP', 'allow_data_protocol' ) );
		add_filter( 'tiny_mce_before_init', array( 'AxisWP', 'tinymce_options' ) );
		add_filter( 'mce_external_plugins', array( 'AxisWP', 'register_tinymce_javascript' ) );
		add_action( 'admin_enqueue_scripts', array( 'AxisWP', 'add_admin_stylesheet' ) );
		add_filter( 'attachment_fields_to_edit', array( 'AxisWP', 'add_chart_metadata_field' ), null, 2 );
		add_filter( 'attachment_fields_to_save', array( 'AxisWP', 'save_chart_metadata' ), null, 2 );
		add_action( 'wp_ajax_insert_axis_attachment', array( 'AxisWP', 'insert_axis_attachment_callback' ) );

		// Frontend stuff
		add_filter( 'the_content', array( 'AxisWP', 'convert_png_to_interactive' ) );
		add_action( 'wp_enqueue_scripts', array( 'AxisWP', 'add_frontend_js' ) );
		add_filter( 'image_send_to_editor', array( 'AxisWP', 'add_data_attribute' ), 10, 7 );
	}

	// Client-side (frontend) stuff

	/**
	 * Replaces the data-uri PNGs in the backend with a div.
	 * @TODO Replace DOMDocument with a better DOM parsing library.
	 *
	 * @param string $content
	 * @return string $content
	 */
	public static function convert_png_to_interactive( $content ) {
		$doc = new DOMDocument( '1.0', 'utf-8' );

		$phpversion = explode( '.', phpversion() );
		$content = mb_convert_encoding( $content, 'HTML-ENTITIES', 'UTF-8' ); // Oh FFS http://stackoverflow.com/a/8218649/467760

		if ( $phpversion[1] <= 3 ) {
			$doc->loadHTML( $content );
		} else {
			$doc->loadHTML( $content, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD ); // Via: http://stackoverflow.com/a/22490902/467760 (Needs PHP >= 5.4)
		}

		$xpath = new DOMXPath( $doc );
		$charts = $xpath->query( "//*[contains(@class, 'axisChart')]" );

		foreach ( $charts as $chart ){
			$chartConfig = $chart->getAttribute( 'data-axisjs' );
			$div = $doc->createElement( 'div' );
			$div->setAttribute( 'data-axisjs', $chartConfig );
			$div->setAttribute( 'class', 'axisChart' );
			$chart->parentNode->replaceChild( $div, $chart );
		}

		if ( $phpversion[1] <= 3 ) { // Via: http://stackoverflow.com/a/10657666/467760
			// Remove doctype node
			$doc->doctype->parentNode->removeChild( $doc->doctype );

			// Remove html element, preserving child nodes
			$html = $doc->getElementsByTagName( 'html' )->item( 0 );
			$fragment = $doc->createDocumentFragment();
			while ( $html->childNodes->length > 0 ) {
				$fragment->appendChild( $html->childNodes->item( 0 ) );
			}
			$html->parentNode->replaceChild( $fragment, $html );

			// Remove body element, preserving child nodes
			$body = $doc->getElementsByTagName( 'body' )->item( 0 );
			$fragment = $doc->createDocumentFragment();
			while ( $body->childNodes->length > 0 ) {
				$fragment->appendChild( $body->childNodes->item( 0 ) );
			}
			$body->parentNode->replaceChild( $fragment, $body );
		}
		$content = $doc->saveHTML();

		return $content;
	}

	/**
	 * Adds frontend JavaScript to the page.
	 *
	 * @return null
	 */

	public static function add_frontend_js() {
		if ( ! is_admin() ) {
			wp_enqueue_script( 'd3js', plugins_url( 'bower_components/d3/d3.min.js', __file__ ), array( 'jquery' ), '3.4.11', true );
			wp_enqueue_script( 'c3js', plugins_url( 'bower_components/c3/c3.min.js', __file__ ), array( 'jquery', 'd3js' ), '0.3.0', true );
			wp_enqueue_script( 'axis', plugins_url( 'js/axis.js', __file__ ), array( 'jquery', 'c3js', 'd3js' ), '0.1.0', true );
			wp_enqueue_style( 'c3jsCSS', plugins_url( 'bower_components/c3/c3.css', __file__ ) );
		}
	}


	// Admin-side backend stuff

	/**
	 * Adding custom field to the $form_fields array
	 * @see http://code.tutsplus.com/articles/creating-custom-fields-for-attachments-in-wordpress--net-13076
	 *
	 * @param array $form_fields
	 * @param object $post
	 * @return array
	 */

	public static function add_chart_metadata_field( $form_fields, $post ) {
		// $form_fields is a special array of fields to include in the attachment form
		// $post is the attachment record in the database
		//     $post->post_type == 'attachment'
		// (attachments are treated as posts in Wordpress)

		$form_fields['axisWP']['input'] = 'hidden';
		$form_fields['axisWP']['value'] = get_post_meta( $post->ID, '_axisWP', true );

		return $form_fields;
	}

	/**
	 * Saving the custom metadata field
	 *
	 * @param array $post
	 * @param array $attachment
	 * @return array
	 */
	public static function save_chart_metadata( $post, $attachment ) {
		// $attachment part of the form $_POST ($_POST[attachments][postID])
		// $post attachments wp post array - will be saved after returned
		//     $post['post_type'] == 'attachment'
		if ( isset( $attachment['_axisWP'] ) && count( $post['tax_input']['chart'] ) > 1 ) {
			// update_post_meta(postID, meta_key, meta_value);
			update_post_meta( $post['ID'], '_axisWP', $attachment['axisWP'] );
		}
		return $post;
	}

	/**
	 * AJAX callback that inserts chart as attachment into the WP database
	 */
	public static function insert_axis_attachment_callback() {
		// Get config
		$axis_config = json_decode( stripslashes( $_POST['axisConfig'] ), true );

		// This check might also need a nonce
		if ( ! current_user_can( 'upload_files' )
			|| ! current_user_can( 'edit_post', $_POST['parentID'] )
			|| ( isset( $axis_config['attachmentID'] ) && ! current_user_can( 'edit_post', $axis_config['attachmentID'] ) )
		) {
			return false;
		}

		// Begin saving PNG to filesystem

		// The following two conditionals are just permissions checks, really.
		if ( false === ( $creds = request_filesystem_credentials( 'admin-ajax.php', '', false, false, null ) ) ) {
			return false; // stop processing here
		}
		if ( ! WP_Filesystem( $creds ) ) {
			request_filesystem_credentials( 'admin-ajax.php', '', true, false, null );
			return false;
		}

		// Convert data URI to filesystem PNG
		global $wp_filesystem;
		$upload_dir = wp_upload_dir();
		$chart_filename = sanitize_title_with_dashes( $axis_config['chartTitle'] ) . '_' . time() . '.png';
		$filename = trailingslashit( $upload_dir['path'] ) . $chart_filename;
		$uriPhp = 'data://' . substr( $_POST['axisChart'], 5 ); // Via http://stackoverflow.com/questions/6735414/php-data-uri-to-file/6735458#6735458

		if ( function_exists( 'wpcom_vip_file_get_contents' ) ) {
			$binary = wpcom_vip_file_get_contents( $uriPhp ); // This might not work like I think it will. Cannot test it...
		} else {
			$binary = file_get_contents( $uriPhp ); //wpcom_vip_file_get_contents() doesn't exist in normal WP? Bizarre.
		}

		// Write it to filesystem
		$wp_filesystem->put_contents(
			$filename,
			$binary,
			FS_CHMOD_FILE // predefined mode settings for WP files
		);

		if ( ! isset($axis_config['attachmentID']) ) { // Insert new attachment
			$attachment = array(
				'guid' => $upload_dir['url'] . '/' . basename( $filename ),
				'post_title' => $axis_config['chartTitle'],
				'post_content' => '', // Must be empty string
				'post_status' => 'published',
				'post_mime_type' => 'image/png',
				'post_status' => 'inherit',
			);

			$attach_id = wp_insert_attachment( $attachment, $filename, $_POST['parentID'] );

			// Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
			require_once( ABSPATH . 'wp-admin/includes/image.php' );

			// Generate the metadata for the attachment, and update the database record.
			$attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
			wp_update_attachment_metadata( $attach_id, $attach_data );
			$axis_config['attachmentID'] = $attach_id;
			$attach_url = wp_get_attachment_image_src( $attach_id, 'full' );
			$axis_config['attachmentURL'] = $attach_url[0];
			update_post_meta( $attach_id, '_axisWP', addslashes( json_encode( $axis_config ) ) ); // Update attachment custom field
			echo json_encode( $axis_config ); // Return config to axisJS
			die();
		} else { // Update existing attachment
			update_attached_file( $axis_config['attachmentID'], $filename ); // Update filename to new version
			$attach_url = wp_get_attachment_image_src( $axis_config['attachmentID'], 'full' ); // Get full path
			$attach_data = wp_generate_attachment_metadata( $axis_config['attachmentID'], $filename );
			wp_update_attachment_metadata( $axis_config['attachmentID'], $attach_data );
			$axis_config['attachmentURL'] = $attach_url[0];
			update_post_meta( $axis_config['attachmentID'], '_axisWP', json_encode( $axis_config ) );

			// Update title (and possibly other metadata) separately
			$updates = array(
				'ID' => $axis_config['attachmentID'],
				'post_title' => (isset($axis_config['chartTitle']) ? $axis_config['chartTitle'] : null),
			);
			wp_update_post( $updates );

			echo json_encode( $axis_config ); // Return config back to axisJS
			die();
		}
	}

	/**
	 * Filter the image HTML markup to send to the editor.
	 *
	 * @since 2.5.0
	 *
	 * @param string $html    The image HTML markup to send.
	 * @param int    $id      The attachment id.
	 * @param string $caption The image caption.
	 * @param string $title   The image title.
	 * @param string $align   The image alignment.
	 * @param string $url     The image source URL.
	 * @param string $size    The image size.
	 * @param string $alt     The image alternative, or alt, text.
	 */
	public static function add_data_attribute( $html, $id, $alt, $title, $align, $url, $size ) {
		$axisJS_config = get_post_meta( $id, '_axisWP', true );
		if ( ! empty( $axisJS_config ) ) {
			return '<div class="mceNonEditable"><img src="' . $url . '" data-axisjs=\'' . base64_encode( $axisJS_config ) . '\' class="mceItem axisChart" /></div><br />';
		} else {
			return $html;
		}
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
		global $wp_version;
		$exploded_version = explode( '.', $wp_version );
		$major_version = $exploded_version[0];

		if ( $major_version > 3 ) {
			$plugin_array['Axis'] = plugins_url( '/js/axisJS-tinymce-plugin-wp-4x.js', __file__ );
		} else {
			$plugin_array['Axis'] = plugins_url( '/js/axisJS-tinymce-plugin.js', __file__ );
		}

		return $plugin_array;
	}

	/**
	 * Enqueues the icon stylesheet.
	 */

	public static function add_admin_stylesheet() {
		wp_enqueue_style( 'axisWP', plugins_url( 'css/axis.css', __file__ ), array( 'dashicons' ), '1.0' );

		if ( WP_DEBUG ) {
			$params = array(
				'axisJSPath' => plugins_url( 'bower_components/axisjs/app/index.html', __file__ ),
			);
		} else {
			$params = array(
				'axisJSPath' => plugins_url( 'bower_components/axisjs/dist/index.html', __file__ ),
			);
		}
		global $post;
		if ( isset($post->ID) ){
			$params['parentID'] = $post->ID;
		}
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
