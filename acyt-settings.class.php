<?php

class ACYTSettingsPage {

	/**
	 * Holds the values to be used in the fields callbacks
	 */
	private $options;

	/**
	 * Start up
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
		add_action( 'admin_init', array( $this, 'page_init' ) );

		add_action( 'update_option', array( $this, 'acyt_validate_channelid' ) );
	}

	/**
	 * Add options page
	 */
	public function add_plugin_page() {
		// This page will be under "Settings"
		add_options_page(
			'ACYT Settings',
			'AC YouTube Settings',
			'manage_options',
			'acyt-settings',
			array( $this, 'create_admin_page' ) );
	}

	/**
	 * Options page callback
	 */
	public function create_admin_page() {

		// Set class property
		$this->options = get_option( 'acyt-yt-id' );
//		submit_button("text", "secondary","fetch-yt");
		$htmloutput = '<div class="wrap"><h1>ACYT Settings</h1><form method="post" action="options.php">';
		echo $htmloutput;

		// This prints out all hidden setting fields
		settings_fields( 'acyt-settings' );
		do_settings_sections( 'acyt-setting-admin' );
		submit_button();

		$closehtml = '</form></div>';
		echo $closehtml;
	}

	/**
	 * Register and add settings
	 */
	public function page_init() {
		register_setting(
		// Option group
			'acyt-settings',
			// Option name
			'acyt-yt-id',
			// Sanitize
			array( $this, 'sanitize' ) );

		add_settings_section(
			'acyt_setting_section_id',
			'Settings voor ACYT Plugin',
			array( $this, 'print_section_info' ),
			'acyt-setting-admin' );

		add_settings_field(
			'acyt-yt-id',
			'YouTube ID',
			array( $this, 'title_callback' ),
			'acyt-setting-admin',
			'acyt_setting_section_id' );
	}

	/**
	 * Sanitize each setting field as needed
	 *
	 * @param array $input
	 *            Contains all settings fields as array keys
	 */
	public function sanitize( $input ) {
		$new_input = array();
		if ( isset( $input['acyt-yt-id'] ) ) {
			$new_input['acyt-yt-id'] = sanitize_text_field( $input['acyt-yt-id'] );
		}

		return $new_input;
	}

	/**
	 * Print the Section text
	 */
	public function print_section_info() {
		print 'Zoek ff het ID van je kanaal:';
	}

	/**
	 * Get the settings option array and print one of its values
	 */
	public function title_callback() {
		printf(
			'<input type="text" id="acyt-yt-id" name="acyt-yt-id[acyt-yt-id]" value="%s" />',
			isset( $this->options['acyt-yt-id'] ) ? esc_attr( $this->options['acyt-yt-id'] ) : '' );
	}

	function acyt_validate_channelid() {
		$channelid = sanitize_text_field( $_POST['acyt-yt-id']['acyt-yt-id'] );

		if ( isset( $channelid ) ) {
			$channelid = esc_attr( $channelid );
			ac_refresh_youtube_posts($channelid);
		}
	}
}
