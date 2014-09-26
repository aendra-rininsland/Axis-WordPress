<?php
/**
* Tests whether the plugin works on the backend
*/
class AxisBackendTest extends WP_UnitTestCase {
	/**
	* @covers AxisWP::register_buttons
	* @nb apply_filters() is only returning Axis' buttons. Not sure why. Test may be broken.
	*/
	function test_register_buttons() {
		// Arrange
		// Act
		$buttons = apply_filters( 'mce_buttons', array() );

		// Assert
		$this->assertContains( 'Axis', $buttons, 'Axis button not being added to TinyMCE' );
	}

	/**
	* @covers AxisWP::register_tinymce_javascript
	*/
	function test_register_tinymce_javascript() {
		// Arrange
		// Act
		$plugins = apply_filters( 'mce_external_plugins', array() );

		// Assert
		$this->assertArrayHasKey( 'Axis', $plugins );
	}

	/**
	* @covers AxisWP::add_admin_stylesheet
	*/
	function test_add_admin_stylesheet() {
		// Arrange
		$axisWP = new AxisWP;

		// Act
		$axisWP->add_admin_stylesheet();

		// Assert
		$this->assertArrayHasKey('axisWP', $GLOBALS['wp_styles']->registered, 'Axis admin stylesheet not registered' );
	}

	/**
	* @covers AxisWP::tinymce_options
	*/
	function test_tinymce_options() {
		// Arrange
		// Act
		$options = apply_filters( 'tiny_mce_before_init', array() );

		// Assert
		$this->assertEquals($options['extended_valid_elements'], '*[*]');
		$this->assertTrue($options['paste_data_images']);
	}

	/**
	* @covers AxisWP::allow_data_protocol
	*/
	function test_allow_data_protocol() {
		// Arrange
		$test_data_uri = 'data:image/png;base64,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y7JwOp24XC6IvLQCVEReyH6/5+HhgXEckW9nWRZOpxOXywWRl1KAishXZmYcDgfMDPl+MpPT6URmIvK1FaAi8pWYGYfDATND+pGZnE4nMhORr6UAFZHfaRxHHh4e2O/3SL/O5zOPj48sy4LI71WAishvNI4jh8OB3W6HvB6Xy4XT6cSyLIj8VgWoiFxhGAbGceTh4YHdboe8XpfLhcfHR5ZlYV1XRK5RgIrIFxiGgXfv3vHw8MAwDMh2rOvK4+MjHz9+ZF1XRL5EASoiv2AYBt69e8fDwwPDMCDbta4rj4+PfPz4kXVdEfklBaiI/BO73Y7D4cA4jsjtWZaF0+nE5XJB5J8pQEXk7+x2Ow6HA+M4IrIsC6fTicvlgsjfK0BF5CdmxsPDA/f394j83NPTE4+Pj2QmIn9RgIrcNDPjcDhgZoj8mszkdDqRmchtK0BFbtI4jvzwww+YGSLXykweHx95enpCblMBKnJTxnHkcDiw2+0Q+b0ulwun04llWZDbUoCK3IRxHDkcDux2O0S+tsvlwul0YlkW5DYUoCKbNQwD79694+HhgWEYEHlp67ry+PjIx48fWdcV2a4CVGSTjscjDw8PDMOAyLe2riuPj48cj0dkmwpQkU2qtSLyvZVSkG0qQEU2qdaKyPdWSkG2qQAV2aRaKyLfWykF2aYCVGSTaq2IfG+lFGSbClCRTTIzRL63zES2qQAVERGRKxWgIiIicqUCVERERK5UgIqIiMiVClARERG5UgEqIiIiVypARURE5EoFqIiIiFypABUREZErFaAiIiJypQJURERErlSAioiIyJUKUBEREblSASoiIiJXKkBFRETkSgWoiIiIXKkAFRERkSsVoCIiInKlAlRERESuVICKiIjIlQpQERERuVIBKiIiIlcqQEVERORKBaiIiIhcqQAVERGRKxWgIiIicqUCVERERK5UgIqIiMiVClARERG5UgEqIiIiVypARURE5EoFqIiIiFypABUREZErFaAiIiJypQJURERErlSAioiIyJUKUBEREblS2e129XK5ICIi8qV2ux1lHMe6LAsiIiJfahxHSkRUd0dERORLRQSl/uTt27c8PT0hIiLya+7v7/n06ROl/mRdV968ecPlckFERORf2e12fP78mWEYKPUn/OR8PvP27VsulwsiIiI/t9vt+PTpE/v9nr8o9Sf8n3VdmaaJp6cnRERE/ub+/p55nhmGgb8p9Sf8TGby448/kplcLhdEROT27HY7zIwffvgBM+Pn/h8Fh6RQ067egwAAAABJRU5ErkJggg==';
		$allowed_protocols = wp_allowed_protocols();

		// Act
		$result = wp_kses_bad_protocol( $test_data_uri, $allowed_protocols);

		// Assert
		$this->assertContains( 'data', $allowed_protocols );
		$this->assertStringStartsWith( 'data:image/png;base64,', $result );
	}


	public function setUp() {
			parent::setUp();
	}

	public function tearDown() {
			parent::tearDown();
	}

}
