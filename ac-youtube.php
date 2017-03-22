<?php
/**
 * Plugin Name: YouTube Plugin voor Applecoach
 * Plugin URI: https://applecoach.nl
 * Description: Makkelijk implementeren van YouTube
 * Version: 1.0.0
 * Author: Niels Gouman
 * Author URI: https://nielsgouman.nl
 * License: GPL2
 */
require_once( 'acyt-settings.class.php' );
require_once( 'acyt-posttype.class.php' );

if ( is_admin() ) {
	$acyt_settings_page = new ACYTSettingsPage();
}

$acyt_post_type = new ACYTPostType();