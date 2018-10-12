<?php

class ACYTPostType {

	public function __construct() {

		add_action( 'init', array( $this, 'register_custom_post_type' ) );
		add_action( 'add_meta_boxes_youtube', array( $this, 'acyt_add_meta_boxes' ) );
		add_action( 'save_post_youtube', array( $this, 'acyt_save_meta_box' ), 10, 2 );
		add_action( 'admin_notices', array( $this, 'acyt_display_errors' ) );

		add_filter( 'the_content', array( $this, 'acyt_content_filter' ) );
	}

	public function register_custom_post_type() {
		$labels = array(
			'name'               => 'Apple Videotips',
			'singular_name'      => 'Video',
			'add_new'            => 'Nieuwe video toevoegen',
			'add_new_item'       => 'Nieuwe video toevoegen',
			'edit_item'          => 'Video bewerken',
			'new_item'           => 'Nieuwe video',
			'all_items'          => 'Alle video\'s',
			'view_item'          => 'Bekijk video',
			'search_items'       => 'Doorzoek video\'s',
			'not_found'          => 'Geen video\'s gevonden',
			'not_found_in_trash' => 'Geen video\'s gevonden in de prullenbak',
			'menu_name'          => 'Video\'s'
		);

		register_post_type(
		// Naam van posttype
			'YouTube',
			// Eigenschappen van posttype
			array(
				'labels'          => $labels,
				'public'          => true,
				'has_archive'     => true,
				'supports'        => array( 'title', 'editor', 'thumbnail' ),
				'rewrite'         => array( 'slug' => 'video' ),
				'capability_type' => 'post'
			) );
	}

	public function acyt_add_meta_boxes() {
		add_meta_box(
		// Uniek ID
			'acyt-yt-videoid',
			// Titel
			'YouTube Video ID',
			// Functie die de output moet maken
			array( $this, 'acyt_build_meta_box' ),
			// Naam van posttype
			'youtube',
			// waar moet de metabox komen te staan
			'side',
			// prio
			'high' );
	}

	public function acyt_build_meta_box( $post ) {
		wp_nonce_field( basename( __FILE__ ), 'acyt-yt-videoid_nonce' );

		$current_yt_id 				= esc_html( get_post_meta( $post->ID, '_acyt-yt-videoid', true ) );
		$current_vtt_media_id = esc_html( get_post_meta( $post->ID, '_acyt_subtitle_file', true ) );
		$publish_ts    				= get_post_meta( $post->ID, '_acyt-original-publish-date', true );
		$publish_date  				= esc_html( strftime( "%c", $publish_ts ) );

		$htmloutput = "<div class='inside'>";
		$htmloutput .= "<p>ID: <input type='text' name='acyt-yt-videoid' value='" . $current_yt_id . "' /></p>";
		$htmloutput .= "<p>VTT media ID: <input type='text' name='_acyt_subtitle_file' value='" . $current_vtt_media_id . "' /></p>";
		$htmloutput .= "</div>";

		echo $htmloutput;
	}

	// from https://wordpress.stackexchange.com/questions/40301/how-do-i-set-a-featured-image-thumbnail-by-image-url-when-using-wp-insert-post
	protected function Generate_Featured_Image( $image_url, $post_id, $unique_part = NULL ) {
		$unique_id  = uniqid( $unique_part, true );
		$upload_dir = wp_upload_dir();
		$image_data = file_get_contents( $image_url );
		$filename   = pathinfo( basename( $image_url ), PATHINFO_FILENAME ) . "-" . $unique_id . "." . pathinfo( basename( $image_url ), PATHINFO_EXTENSION );
		if ( wp_mkdir_p( $upload_dir['path'] ) ) {
			$file = $upload_dir['path'] . '/' . $filename;
		} else {
			$file = $upload_dir['basedir'] . '/' . $filename;
		}
		file_put_contents( $file, $image_data );

		$wp_filetype = wp_check_filetype( $filename, NULL );
		$attachment  = array(
			'post_mime_type' => $wp_filetype['type'],
			'post_title'     => sanitize_file_name( $filename ),
			'post_content'   => '',
			'post_status'    => 'inherit'
		);
		$attach_id   = wp_insert_attachment( $attachment, $file, $post_id );
		require_once( ABSPATH . 'wp-admin/includes/image.php' );
		$attach_data = wp_generate_attachment_metadata( $attach_id, $file );
		$res1        = wp_update_attachment_metadata( $attach_id, $attach_data );
		$res2        = set_post_thumbnail( $post_id, $attach_id );
	}

	function acyt_save_meta_box( $post_id, $post ) {
		$error = false;

		// security check iets
		if ( ! isset( $_POST['acyt-yt-videoid_nonce'] ) || ! wp_verify_nonce( $_POST['acyt-yt-videoid_nonce'], basename( __FILE__ ) ) ) {
			return $post_id;
		}

		update_post_meta($post_id, "_acyt_subtitle_file", $_POST["_acyt_subtitle_file"]);

		/* Get the meta key. */
		$meta_key = '_acyt-yt-videoid';

		/* Get the meta value of the custom field key. */
		$meta_value     = get_post_meta( $post_id, $meta_key, true );
		$new_meta_value = sanitize_text_field( $_POST['acyt-yt-videoid'] );

		// Er was al een ID
		if ( isset( $meta_value ) ) {
			// En er is ook een ID ingevuld
			if ( isset( $new_meta_value ) ) {
				// Nieuwe ID is anders dan bekende ID
				if ( $new_meta_value != $meta_value ) {
					// Geldige embed?
					if ( wp_oembed_get( 'https://youtu.be/' . $new_meta_value ) ) {
						update_post_meta( $post_id, $meta_key, $new_meta_value );
					} else {
						$error = new WP_Error( 'acyt_embed_id_error', 'Oops! Dat is geen geldige YouTube Video ID!' );
					}
				}
			} // Was wel al een ID, maar nu niet meer
			else {
				// Verwijder oude ID
				delete_post_meta( $post_id, $meta_key, $meta_value );
			}
		} // Er was nog geen ID
		else {
			// Nu wel een ID ingevuld
			if ( isset( $new_meta_value ) ) {
				if ( wp_oembed_get( 'https://youtu.be/' . $new_meta_value ) ) {
					add_post_meta( $post_id, $meta_key, $new_meta_value );
				} else {
					$error = new WP_Error( 'acyt_embed_id_error', 'Oops! Dat is geen geldige YouTube Video ID!' );
				}

			}
		}

		if ( $error ) {
			add_filter( 'redirect_post_location', function ( $location ) use ( $error ) {
				return add_query_arg( 'acyt-error', $error->get_error_code(), $location );
			} );
		} elseif ( $new_meta_value and ! has_post_thumbnail( $post ) ) {
			$data                  = file_get_contents( "https://www.googleapis.com/youtube/v3/videos?key=" . AC_YT_API_KEY . "&part=snippet&id=" . $new_meta_value );
			$json                  = json_decode( $data );
			$youtube_thumbnail_url = $json->items[0]->snippet->thumbnails->maxres->url;

			$this->Generate_Featured_Image( $youtube_thumbnail_url, $post_id, $new_meta_value );
		}
	}

	function acyt_content_filter( $content ) {
		if ( is_singular() ) {
			$yt_videoid = get_post_meta( get_the_ID(), '_acyt-yt-videoid', true );

			if ( isset( $yt_videoid ) ) {
				$yt_embed = wp_oembed_get( 'https://youtu.be/' . $yt_videoid );

				$content = $yt_embed . $content;
			}
		}

		return $content;
	}

	function acyt_display_errors() {
		if ( array_key_exists( 'acyt-error', $_GET ) ) {
			$errortext = '';

			switch ( $_GET['acyt-error'] ) {
				case 'acyt_embed_id_error':
					$errortext = 'Oops! Dat is geen geldige YouTube Video ID! Een voorbeeld van een video ID is: _2RgGY3tSqE';
					break;
				default:
					$errortext = 'OMG. Geen idee wat er fout ging, maar er is in ieder geval iets niet goed met de YouTube Video ID!';
			}

			echo '<div class="notice notice-error"><p>' . $errortext . '</p></div>';
		}
	}
}
