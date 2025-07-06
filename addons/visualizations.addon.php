<?php
if ( ! class_exists( 'BFP_VISUALIZATIONS_ADDON' ) ) {
	class BFP_VISUALIZATIONS_ADDON {

		private $_bfp;

		public function __construct( $bfp ) {
			 $this->_bfp = $bfp;
			add_action( 'bfp_addon_general_settings', array( $this, 'general_settings' ) );
			add_action( 'bfp_save_setting', array( $this, 'save_general_settings' ) );
		} // End __construct

		private function _is_enabled() {
			return get_option( 'bfp_addon_player' ) == 'visualizations';
		} // End _is_enabled

		public function general_settings() {
			$enabled = $this->_is_enabled();

			print '<tr><td><input aria-label="' . esc_attr__( 'Use Visualizations player', 'bandfront-player' ) . '" type="radio" value="visualizations" name="bfp_addon_player" ' . ( $enabled ? 'CHECKED' : '' ) . ' class="bfp_radio"></td><td width="100%"><b>' . esc_html__( 'Use "Visualizations" player', 'bandfront-player' ) . '</b><br><i>' .
			esc_html__( 'Audio visualizations for enhanced player experience.', 'bandfront-player' )
			. '</i></td></tr>';
		} // End general_settings

		public function save_general_settings() {
			if ( isset( $_POST['bfp_addon_player'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				update_option( 'bfp_addon_player', sanitize_text_field( wp_unslash( $_POST['bfp_addon_player'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
			} else {
				delete_option( 'bfp_addon_player' );
			}
		} // End save_general_settings

	} // End BFP_VISUALIZATIONS_ADDON
}

new BFP_VISUALIZATIONS_ADDON( $bfp );
