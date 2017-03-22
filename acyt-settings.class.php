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

			$url = 'https://www.youtube.com/channel/' . $channelid;
//			var_dump( $url );

			$response = wp_remote_get( $url );

			if ( is_array( $response ) && !is_wp_error( $response ) ) {
				$status = $response['response']['code']; // array of http header lines
//				var_dump($status);

				if ( $status == '200' ) {

					$feed  = fetch_feed( $url );
					$items = $feed->get_items();
					$paginaIds = get_all_page_ids();

					foreach ( $items as $item ) {
						$titel    = $item->get_title();
						$videoId  = '';
						$text     = '';
						$thumbUrl = '';


						if ( preg_match( '![?&]{1}v=([^&]+)!', $item->get_permalink() . '&', $m2 ) ) {
							$videoId = $m2[1];
						}

						$enclosure = $item->get_enclosure();

						if ( $enclosure ) {
							$thumbUrl = $enclosure->get_thumbnail();
							$text     = $enclosure->get_description();
						}

//						var_dump( $titel );
//						var_dump( $videoId );
//						var_dump( $thumbUrl );
//						var_dump( $text );
//
//						var_dump( "--------- Next! ---------" );

						$postbestaat = false;
						foreach ($paginaIds as $paginaId){
							if (get_post_meta( $paginaId, '_acyt-yt-videoid', true ) == $videoId){
								$postbestaat = true;
							}

						}

						if (!$postbestaat){
							wp_insert_post(
								array(
									'post_title'   => $titel,
									'post_type'    => 'youtube',
									'post_content' => $text,
									'meta_input' => array( '_acyt-yt-videoid' => $videoId )
								)
							);
						}
					}
				}
			}
		}
	}
}