<?php
if ( ! class_exists( 'BFP_HTML5AUDIOPLAYER_ADDON' ) ) {
	class BFP_HTML5AUDIOPLAYER_ADDON {

		private $_bfp;
		private $_player_flag = false;

		public function __construct( $bfp ) {
			 $this->_bfp = $bfp;
			add_action( 'bfp_addon_general_settings', array( $this, 'general_settings' ) );
			add_action( 'bfp_save_setting', array( $this, 'save_general_settings' ) );
			add_filter( 'bfp_audio_tag', array( $this, 'generate_player' ), 99, 4 );
			add_filter( 'bfp_widget_audio_tag', array( $this, 'generate_player' ), 99, 4 );
			add_filter( 'bfp_product_attr', array( $this, 'product_attr' ), 99, 3 );
			add_filter( 'bfp_global_attr', array( $this, 'global_attr' ), 99, 2 );
			add_action( 'wp_footer', array( $this, 'add_script' ) );
		} // End __construct

		private function _player_exists() {
			 return class_exists( 'H5APPlayer\Template\Player' );
		} // End _player_exists

		private function _is_enabled() {
			return get_option( 'bfp_addon_player' ) == 'html5audioplayer';
		} // End _is_enabled

		public function general_settings() {
			$enabled = ( $this->_player_exists() && $this->_is_enabled() );

			print '<tr><td><input aria-label="' . esc_attr__( 'Use HTML5 Audio Player instead of the current plugin players', 'bandfront-player' ) . '" type="radio" value="html5audioplayer" name="bfp_addon_player" ' . ( $enabled ? 'CHECKED' : '' ) . ( $this->_player_exists() ? '' : ' DISABLED' ) . ' class="bfp_radio"></td><td width="100%"><b>' . esc_html__( 'Use "HTML5 Audio Player" instead of the current plugin players', 'bandfront-player' ) . '</b><br><i>' .
			( $this->_player_exists()
				? __( 'The player functions configured above do not apply, except for audio protection if applicable.<br>This player <b>will take precedence</b> over the player configured in the products\' settings.', 'bandfront-player' ) // phpcs:ignore WordPress.Security.EscapeOutput
				: esc_html__( 'The "HTML5 Audio Player" plugin is not installed on your WordPress.', 'bandfront-player' )
			)
			. '</i></td></tr>';
		} // End general_settings

		public function save_general_settings() {
			if ( $this->_player_exists() ) {
				if ( isset( $_POST['bfp_addon_player'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
					update_option( 'bfp_addon_player', sanitize_text_field( wp_unslash( $_POST['bfp_addon_player'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
				} else {
					delete_option( 'bfp_addon_player' );
				}
			}
		} // End save_general_settings

		public function generate_player( $player, $product_id, $file_index, $url ) {
			if ( $this->_player_exists() && $this->_is_enabled() ) {
				wp_enqueue_style( 'bfp-ap-html5-audio-player-style', plugin_dir_url( __FILE__ ) . 'ap-html5-audio-player/style.css', array(), BFP_VERSION );
				$this->_player_flag = true;
				$d                  = '';
				if ( preg_match( '/data-duration="[^"]+"/', $player, $matches ) ) {
					$d = $matches[0];
				}

				return str_replace(
					'<audio',
					'<audio ' . $d . ' ',
					H5APPlayer\Template\Player::html(
						array(
							'template' => array(
								'attr'   => '',
								'width'  => 'auto',
								'source' => $url,
							),
						)
					)
				);
			}
			return $player;
		} // End generate_player

		public function product_attr( $value, $product_id, $attribute ) {
			if (
				! is_admin() &&
				$this->_player_exists() &&
				$this->_is_enabled() &&
				'_bfp_player_controls' == $attribute
			) {
				return 'all';
			}

			return $value;
		} // End product_attr

		public function global_attr( $value, $attribute ) {
			if (
				! is_admin() &&
				$this->_player_exists() &&
				$this->_is_enabled() &&
				'_bfp_player_controls' == $attribute
			) {
				return 'all';
			}

			return $value;
		} // End global_attr

		public function add_script() {
			if ( $this->_player_flag ) {
				print '<script>jQuery("audio[data-duration]").on("timeupdate", function(){var d = jQuery(this).data("duration"), c = jQuery(this).closest(".plyr--audio"); if(c.length) c.find(".plyr__time--duration").html(d);})</script>';
			}
		} // End add_script

	} // End BFP_HTML5AUDIOPLAYER_ADDON
}

new BFP_HTML5AUDIOPLAYER_ADDON( $bfp );
