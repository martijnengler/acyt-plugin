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

add_action("wp_ajax_nopriv_ac_refresh_youtube_posts", function(){
	ac_refresh_youtube_posts();
	wp_die();
});

function ac_refresh_youtube_posts($channelid = null)
{
	if(!$channelid)
	{
		$channelid = get_option("acyt-yt-id")["acyt-yt-id"];
	}
	$url = 'https://www.youtube.com/channel/' . $channelid;
	//			var_dump( $url );

	$response = wp_remote_get( $url );

	if ( is_array( $response ) && ! is_wp_error( $response ) ) {
		$status = $response['response']['code']; // array of http header lines
		//				var_dump($status);

		if ( $status == '200' ) {

			$feed      = fetch_feed( $url );
			$items     = $feed->get_items();
			global $wpdb;
			$paginaIds = $wpdb->get_col("SELECT pm.meta_value AS youtube_id FROM wp_posts p
				LEFT JOIN wp_postmeta pm ON p.ID = pm.post_ID
				WHERE p.post_type = 'youtube' AND pm.meta_key = '_acyt-yt-videoid'");

			foreach ( $items as $item ) {
				$titel      = $item->get_title();
				$videoId    = '';
				$text       = '';
				$thumbUrl   = '';
				$local_date = $item->get_local_date( "%F %T" );

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
				//						var_dump($local_date);
				//
				//						var_dump( "--------- Next! ---------" );

				if(in_array($videoId, $paginaIds))
				{
					continue;
				}

				$postid = wp_insert_post(
					array(
						'post_title'   => $titel,
						'post_date'    => $local_date,
						'post_type'    => 'youtube',
						'post_status'  => 'published',
						'post_content' => $text,
						'meta_input'   => array( '_acyt-yt-videoid' => $videoId )
					) );

				// gelijk post updaten zodat het wel een draft is, maar dan in ieder geval de date goed staat
				wp_update_post(
					array(
						'ID'          => $postid,
						'post_status' => 'draft'
					) );
			}
		}
	}
}
