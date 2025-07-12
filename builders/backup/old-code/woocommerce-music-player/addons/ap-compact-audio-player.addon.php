<?php
if ( ! class_exists( 'WCMP_COMPACTAUDIOPLAYER_ADDON' ) ) {
	class WCMP_COMPACTAUDIOPLAYER_ADDON {

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
			 return defined( 'SC_AUDIO_PLUGIN_VERSION' );
		} // End _player_exists

		private function _is_enabled() {
			return get_option( 'wcmp_addon_player' ) == 'compactaudioplayer';
		} // End _is_enabled

		public function general_settings() {
			$enabled = ( $this->_player_exists() && $this->_is_enabled() );

			print '<tr><td><input aria-label="' . esc_attr__( 'Use Compact Audio Player instead of the current plugin players', 'music-player-for-woocommerce' ) . '" type="radio" value="compactaudioplayer" name="wcmp_addon_player" ' . ( $enabled ? 'CHECKED' : '' ) . ( $this->_player_exists() ? '' : ' DISABLED' ) . ' class="wcmp_radio"></td><td width="100%"><b>' . esc_html__( 'Use "Compact Audio Player" instead of the current plugin players', 'music-player-for-woocommerce' ) . '</b><br><i>' .
			( $this->_player_exists()
				? __( 'The player functions configured above do not apply, except for audio protection if applicable.<br>This player <b>will take precedence</b> over the player configured in the products\' settings.', 'music-player-for-woocommerce' ) // phpcs:ignore WordPress.Security.EscapeOutput
				: esc_html__( 'The "Compact WP Audio Player" plugin is not installed on your WordPress.', 'music-player-for-woocommerce' )
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
			}
		} // End save_general_settings

		public function generate_player( $player, $product_id, $file_index, $url ) {
			if ( $this->_player_exists() && $this->_is_enabled() ) {
				return do_shortcode( '[sc_embed_player fileurl="' . esc_attr( $url ) . '"]' );
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
				return 'button';
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
				return 'button';
			}

			return $value;
		} // End global_attr

	} // End WCMP_COMPACTAUDIOPLAYER_ADDON
}

new WCMP_COMPACTAUDIOPLAYER_ADDON( $wcmp );
