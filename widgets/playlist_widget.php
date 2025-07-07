<?php

/**
 * Bandfront Player - Playlist Widget
 *
 * @package BandfrontPlayer
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}


if ( ! function_exists( 'bfp_register_playlist_widget' ) ) {
	function bfp_register_playlist_widget() {
		if ( ! class_exists( 'woocommerce' ) ) {
			return;
		}
		return register_widget( 'BFP_PLAYLIST_WIDGET' );
	}
}
add_action( 'widgets_init', 'bfp_register_playlist_widget' );

if ( ! class_exists( 'BFP_PLAYLIST_WIDGET' ) ) {
	class BFP_PLAYLIST_WIDGET extends WP_Widget {

		public function __construct() {
			$widget_ops = array(
				'classname'   => 'BFP_PLAYLIST_WIDGET',
				'description' => 'Includes a playlist with the audio files of products selected',
			);

			parent::__construct( 'BFP_PLAYLIST_WIDGET', 'Bandfront Player - Playlist', $widget_ops );
		}

		public function form( $instance ) {
			$instance = wp_parse_args(
				(array) $instance,
				array(
					'title'                     => '',
					'products_ids'              => '',
					'volume'                    => '',
					'highlight_current_product' => 0,
					'continue_playing'          => 0,
					'player_style'              => BFP_DEFAULT_PLAYER_LAYOUT,
				)
			);

			$title                     = sanitize_text_field( $instance['title'] );
			$products_ids              = sanitize_text_field( $instance['products_ids'] );
			$volume                    = sanitize_text_field( $instance['volume'] );
			$highlight_current_product = sanitize_text_field( $instance['highlight_current_product'] );
			$continue_playing          = sanitize_text_field( $instance['continue_playing'] );
			$player_style              = sanitize_text_field( $instance['player_style'] );
			$playlist_layout           = sanitize_text_field( ( ! empty( $instance['playlist_layout'] ) ) ? $instance['playlist_layout'] : 'new' );

			$play_all = sanitize_text_field(
				$GLOBALS['BandfrontPlayer']->get_global_attr(
					'_bfp_play_all',
					// This option is only for compatibility with versions previous to 1.0.28
											$GLOBALS['BandfrontPlayer']->get_global_attr(
												'play_all',
												0
											)
				)
			);
			$preload = sanitize_text_field(
				$GLOBALS['BandfrontPlayer']->get_global_attr(
					'_bfp_preload',
					// This option is only for compatibility with versions previous to 1.0.28
											$GLOBALS['BandfrontPlayer']->get_global_attr(
												'preload',
												'metadata'
											)
				)
			);
			?>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title', 'bandfront-player' ); ?>: <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" /></label>
			</p>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'products_ids' ) ); ?>"><?php esc_html_e( 'Products IDs', 'bandfront-player' ); ?>: <input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'products_ids' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'products_ids' ) ); ?>" type="text" value="<?php echo esc_attr( $products_ids ); ?>" placeholder="<?php esc_attr_e( 'Products IDs separated by comma, or a * for all', 'bandfront-player' ); ?>" /></label>
			</p>
			<p>
				<?php
					esc_html_e( 'Enter the ID of products separated by comma, or a * symbol to includes all products in the playlist.', 'bandfront-player' );
				?>
			</p>
			<p>
				<label><?php esc_html_e( 'Volume (enter a number between 0 and 1)', 'bandfront-player' ); ?>: <input class="widefat" name="<?php echo esc_attr( $this->get_field_name( 'volume' ) ); ?>" type="number" min="0" max="1" step="0.01" value="<?php echo esc_attr( $volume ); ?>" /></label>
			</p>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'playlist_layout' ) ); ?>"><?php esc_html_e( 'Playlist layout', 'bandfront-player' ); ?>: </label>
			</p>
			<p>
				<label><input name="<?php echo esc_attr( $this->get_field_name( 'playlist_layout' ) ); ?>" type="radio" value="new" <?php echo ( ( 'new' == $playlist_layout ) ? 'checked' : '' ); ?> style="float:left; margin-top:8px;" /><?php esc_html_e( 'New layout', 'bandfront-player' ); ?></label>
			</p>
			<p>
				<label><input name="<?php echo esc_attr( $this->get_field_name( 'playlist_layout' ) ); ?>" type="radio" value="old" <?php echo ( ( 'old' == $playlist_layout ) ? 'checked' : '' ); ?> style="float:left; margin-top:8px;" /><?php esc_html_e( 'Original layout', 'bandfront-player' ); ?></label>
			</p>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'player_style' ) ); ?>"><?php esc_html_e( 'Player layout', 'bandfront-player' ); ?>: </label>
			</p>
			<p>
				<label><input name="<?php echo esc_attr( $this->get_field_name( 'player_style' ) ); ?>" type="radio" value="mejs-classic" <?php echo ( ( 'mejs-classic' == $player_style ) ? 'checked' : '' ); ?> style="float:left; margin-top:8px;" /><img src="<?php print esc_url( BFP_PLUGIN_URL ); ?>/views/assets/skin1_btn.png" /></label>
			</p>
			<p>
				<label><input name="<?php echo esc_attr( $this->get_field_name( 'player_style' ) ); ?>" type="radio" value="mejs-ted" <?php echo ( ( 'mejs-ted' == $player_style ) ? 'checked' : '' ); ?> style="float:left; margin-top:8px;" /><img src="<?php print esc_url( BFP_PLUGIN_URL ); ?>/views/assets/skin2_btn.png" /></label>
			</p>
			<p>
				<label><input name="<?php echo esc_attr( $this->get_field_name( 'player_style' ) ); ?>" type="radio" value="mejs-wmp" <?php echo ( ( 'mejs-wmp' == $player_style ) ? 'checked' : '' ); ?> style="float:left; margin-top:16px;" /><img src="<?php print esc_url( BFP_PLUGIN_URL ); ?>/views/assets/skin3_btn.png" /></label>
			</p>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'play_all' ) ); ?>"><?php esc_html_e( 'Play all', 'bandfront-player' ); ?>: <input id="<?php echo esc_attr( $this->get_field_id( 'play_all' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'play_all' ) ); ?>" type="checkbox" <?php echo ( ( $play_all ) ? 'CHECKED' : '' );
				?> /></label>
			</p>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'preload' ) ); ?>"><?php esc_html_e( 'Preload', 'bandfront-player' ); ?>:</label><br />
				<label><input name="<?php echo esc_attr( $this->get_field_name( 'preload' ) ); ?>" type="radio" value="none" <?php echo ( ( 'none' == $preload ) ? 'CHECKED' : '' ); ?> /> None</label>
				<label><input name="<?php echo esc_attr( $this->get_field_name( 'preload' ) ); ?>" type="radio" value="metadata" <?php echo ( ( 'metadata' == $preload ) ? 'CHECKED' : '' ); ?> /> Metadata</label>
				<label><input name="<?php echo esc_attr( $this->get_field_name( 'preload' ) ); ?>" type="radio" value="auto" <?php echo ( ( 'auto' == $preload ) ? 'CHECKED' : '' ); ?> /> Auto</label>
			</p>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'highlight_current_product' ) ); ?>"><?php esc_html_e( 'Highlight the current product', 'bandfront-player' ); ?>: <input id="<?php echo esc_attr( $this->get_field_id( 'highlight_current_product' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'highlight_current_product' ) ); ?>" type="checkbox" <?php echo ( ( $highlight_current_product ) ? 'CHECKED' : '' ); ?> /></label>
			</p>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'continue_playing' ) ); ?>"><?php esc_html_e( 'Continue playing after navigate', 'bandfront-player' ); ?>: <input id="<?php echo esc_attr( $this->get_field_id( 'continue_playing' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'continue_playing' ) ); ?>" type="checkbox" <?php echo ( ( $continue_playing ) ? 'CHECKED' : '' ); ?> value="1" /></label>
			</p>
			<p>
				<?php
					esc_html_e( 'Continue playing the same song at same position after navigate. You can experiment some delay because the music player should to load the audio file again, and in some mobiles devices, where the action of the user is required, the player cannot starting playing automatically.', 'bandfront-player' );
				?>
			</p>
			<?php
		}

		public function update( $new_instance, $old_instance ) {
			$instance                              = $old_instance;
			$instance['title']                     = sanitize_text_field( $new_instance['title'] );
			$instance['products_ids']              = sanitize_text_field( $new_instance['products_ids'] );
			$instance['volume']                    = sanitize_text_field( $new_instance['volume'] );
			$instance['highlight_current_product'] = ( ! empty( $new_instance['highlight_current_product'] ) ) ? true : false;
			$instance['continue_playing']          = ( ! empty( $new_instance['continue_playing'] ) ) ? true : false;
			$instance['player_style']              = sanitize_text_field( $new_instance['player_style'] );
			$instance['playlist_layout']           = sanitize_text_field( ( ! empty( $new_instance['playlist_layout'] ) ) ? $new_instance['playlist_layout'] : 'new' );

			$global_settings                   = get_option( 'bfp_global_settings', array() );
			$global_settings['_bfp_play_all'] = ( ! empty( $new_instance['play_all'] ) ) ? 1 : 0;
			$global_settings['_bfp_preload']  = (
					! empty( $new_instance['preload'] ) &&
					in_array( $new_instance['preload'], array( 'none', 'metadata', 'auto' ) )
				) ? $new_instance['preload'] : 'metadata';

			update_option( 'bfp_global_settings', $global_settings );

			return $instance;
		}

		public function widget( $args, $instance ) {
			if ( ! is_array( $args ) ) {
				$args = array();
			}
			extract( $args, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract

			$title = empty( $instance['title'] ) ? ' ' : apply_filters( 'widget_title', $instance['title'] );

			$attrs = array(
				'products_ids'              => $instance['products_ids'],
				'highlight_current_product' => $instance['highlight_current_product'],
				'continue_playing'          => $instance['continue_playing'],
				'player_style'              => $instance['player_style'],
				'layout'                    => ( ! empty( $instance['playlist_layout'] ) ) ? $instance['playlist_layout'] : 'new',
			);

			if ( ! empty( $instance['volume'] ) && ( $volume = @floatval( $instance['volume'] ) ) != 0 ) { // phpcs:ignore Squiz.PHP.DisallowMultipleAssignments
				$attrs['volume'] = min( 1, $volume );
			}

			$output = $GLOBALS['BandfrontPlayer']->replace_playlist_shortcode( $attrs );

			if ( strlen( $output ) == 0 ) {
				return;
			}

			print $before_widget; // phpcs:ignore WordPress.Security.EscapeOutput
			if ( ! empty( $title ) ) {
				print $before_title . $title . $after_title; // phpcs:ignore WordPress.Security.EscapeOutput
			}
			print $output; // phpcs:ignore WordPress.Security.EscapeOutput
			print $after_widget; // phpcs:ignore WordPress.Security.EscapeOutput
		}
	} // End Class BFP_PLAYLIST_WIDGET
}
