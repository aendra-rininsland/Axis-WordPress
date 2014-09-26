<?php
/**
 * Tests whether the plugin has been installed successfully
 */
class AxisSetupTest extends WP_UnitTestCase {
	/**
	 * @covers AxisWP::init
	 */
	function test_init() {
		// Arrange
		$axisWP = new AxisWP;

		// Act
		$axisWP_init = $axisWP->init();

		// Assert
		$this->assertInstanceOf( 'AxisWP',  $axisWP_init);
	}

	/**
	 * @covers AxisWP::__construct
	 */
	function test_constructor() {
		// Arrange
		// Act

		// Assert
		// Admin
		$this->assertInternalType('integer', has_filter( 'mce_buttons', array( 'AxisWP', 'register_buttons' ) ) );
		$this->assertInternalType('integer', has_filter( 'kses_allowed_protocols', array( 'AxisWP', 'allow_data_protocol' ) ) );
		$this->assertInternalType('integer', has_filter( 'tiny_mce_before_init', array( 'AxisWP', 'tinymce_options' ) ) );
		$this->assertInternalType('integer', has_filter( 'mce_external_plugins', array( 'AxisWP', 'register_tinymce_javascript' ) ) );
		$this->assertInternalType('integer', has_action( 'admin_enqueue_scripts', array( 'AxisWP', 'add_admin_stylesheet' ) ) );

		// Frontend
		$this->assertInternalType('integer', has_filter( 'the_content', array( 'AxisWP', 'convert_png_to_interactive' ) ) );
		$this->assertInternalType('integer', has_action( 'wp_enqueue_scripts', array( 'AxisWP', 'add_frontend_js' ) ) );
	}

	/**
	 * @covers AxisWP::on_activation
	 * @TODO Write activation unit test.
	 */
	function test_on_activation() {}

	/**
	 * @covers AxisWP::on_deactivation
	 * @TODO Write deactivation unit test.
	 */
	function test_on_deactivation() {}

	/**
	 * @covers AxisWP:on_uninstall
	 * @TODO Write uninstall unit test.
	 */
	function test_on_uninstall() {}

	public function setUp() {
			parent::setUp();
	}

	public function tearDown() {
			parent::tearDown();
	}
}
