<?php
if ( ! class_exists( 'WCMP_CPMEDIAPLAYER_ADDON' ) ) {
	class WCMP_CPMEDIAPLAYER_ADDON {

		private $_wcmp;

		public function __construct( $wcmp ) {
			 $this->_wcmp = $wcmp;
			add_action( 'wcmp_addon_general_settings', array( $this, 'general_settings' ) );
			add_action( 'wcmp_save_setting', array( $this, 'save_general_settings' ) );
			add_filter( 'wcmp_audio_tag', array( $this, 'generate_player' ), 99, 4 );
			add_filter( 'wcmp_widget_audio_tag', array( $this, 'generate_player' ), 99, 4 );
			add_filter( 'wcmp_product_attr', array( $this, 'product_attr' ), 99, 3 );
			add_filter( 'wcmp_global_attr', array( $this, 'global_attr' ), 99, 2 );
		} // End __construct

		private function _player_exists() {
			 return defined( 'CPMP_VERSION' );
		} // End _player_exists

		private function _is_enabled() {
			return get_option( 'wcmp_addon_player' ) == 'cpmediaplayer';
		} // End _is_enabled

		private function _load_skins( $selected_option ) {
			// Skins
			$options = '';
			if ( defined( 'CPMP_PLUGIN_DIR' ) ) {
				$skins    = array();
				$skin_dir = CPMP_PLUGIN_DIR . '/skins';
				if ( file_exists( $skin_dir ) ) {
					$d = dir( $skin_dir );
					while ( false !== ( $entry = $d->read() ) ) {
						if ( '.' != $entry && '..' != $entry && is_dir( $skin_dir . '/' . $entry ) ) {
							$this_skin = $skin_dir . '/' . $entry . '/';
							if ( file_exists( $this_skin ) ) {
								$skin_data = parse_ini_file( $this_skin . 'config.ini', true );
								$options  .= '<option value="' . $skin_data['id'] . '" ' . ( $skin_data['id'] == $selected_option ? 'SELECTED' : '' ) . '>' . esc_html( $skin_data['name'] ) . '</option>';
							}
						}
					}
					$d->close();
				}
			}
			return $options;
		} // End _load_skins

		private function _get_skin() {
			return get_option( 'wcmp_cpmediaplayer_addon_skin', 'classic-skin' );
		} // End _get_skin

		private function _set_skin( $v ) {
			update_option( 'wcmp_cpmediaplayer_addon_skin', $v );
		} // End _set_skin

		public function general_settings() {
			$enabled = ( $this->_player_exists() && $this->_is_enabled() );

			print '<tr><td><input aria-label="' . esc_attr__( 'Use CP Media Player instead of the current plugin players', 'music-player-for-woocommerce' ) . '" type="radio" value="cpmediaplayer" name="wcmp_addon_player" ' . ( $enabled ? 'CHECKED' : '' ) . ( $this->_player_exists() ? '' : ' DISABLED' ) . ' class="wcmp_radio"></td><td width="100%"><b>' . esc_html__( 'Use "CP Media Player" instead of the current plugin players', 'music-player-for-woocommerce' ) . '</b><br>
            ' . esc_html__( 'Select player skin', 'music-player-for-woocommerce' ) . ': <select name="wcmp_cpmediaplayer_addon_skin" ' . ( $this->_player_exists() ? '' : ' DISABLED' ) . '>' . $this->_load_skins( $this->_get_skin() ) . '</select>
            <br><i>' .
			( $this->_player_exists()
				? __( 'The player functions configured above do not apply, except for audio protection if applicable.<br>This player <b>will take precedence</b> over the player configured in the products\' settings.', 'music-player-for-woocommerce' ) // phpcs:ignore WordPress.Security.EscapeOutput
				: esc_html__( 'The "CP Media Player" plugin is not installed on your WordPress.', 'music-player-for-woocommerce' )
			)
			. '</i></td></tr>';
		} // End general_settings

		public function save_general_settings() {
			if ( $this->_player_exists() ) {
				if ( isset( $_POST['wcmp_addon_player'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
					update_option( 'wcmp_addon_player', sanitize_text_field( wp_unslash( $_POST['wcmp_addon_player'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
				} else {
					delete_option( 'wcmp_addon_player' );
				}

				if ( isset( $_POST['wcmp_cpmediaplayer_addon_skin'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
					$this->_set_skin( sanitize_text_field( wp_unslash( $_POST['wcmp_cpmediaplayer_addon_skin'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
				}
			}
		} // End save_general_settings

		public function generate_player( $player, $product_id, $file_index, $url ) {
			if ( $this->_player_exists() && $this->_is_enabled() ) {
				wp_enqueue_style( 'wcmp-ap-cp-media-player-style', plugin_dir_url( __FILE__ ) . 'ap-cp-media-player/style.css', array(), WCMP_VERSION );
				return do_shortcode( '[cpm-player skin="' . esc_attr( $this->_get_skin() ) . '" playlist="false" type="audio"][cpm-item file="' . esc_attr( $url ) . '"][/cpm-player]' );
			}
			return $player;
		} // End generate_player

		public function product_attr( $value, $product_id, $attribute ) {
			if (
				! is_admin() &&
				$this->_player_exists() &&
				$this->_is_enabled() &&
				'_wcmp_player_controls' == $attribute
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
				'_wcmp_player_controls' == $attribute
			) {
				return 'all';
			}

			return $value;
		} // End global_attr

	} // End WCMP_CPMEDIAPLAYER_ADDON
}

new WCMP_CPMEDIAPLAYER_ADDON( $wcmp );
