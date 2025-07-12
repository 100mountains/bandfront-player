<?php
if ( ! class_exists( 'WCMP_WCVENDORS_ADDON' ) ) {
	class WCMP_WCVENDORS_ADDON {

		private $_wcmp;

		public function __construct( $wcmp ) {
			 $this->_wcmp = $wcmp;
			add_action(
				'admin_init',
				function() {
					if ( get_current_user_id() ) {
						$user  = wp_get_current_user();
						$roles = (array) $user->roles;
						if ( in_array( 'vendor', $roles ) && class_exists( 'WC_Vendors' ) ) {
							global $wcmp_wcv_flag;
							$wcmp_wcv_flag = true;
							if ( ! get_option( 'wcmp_wcv_enabled', 1 ) ) {
								remove_meta_box( 'wcmp_woocommerce_metabox', 'product', 'normal' );
							}
						}
					}
				},
				99
			);

			add_action( 'wcv_delete_post', array( $this, 'delete_product' ) );
			add_action( 'wcmp_addon_general_settings', array( $this, 'general_settings' ) );
			add_action( 'wcmp_save_setting', array( $this, 'save_general_settings' ) );
		} // End __construct

		public function general_settings() {
			$wcmp_wcv_enabled       = get_option( 'wcmp_wcv_enabled', 1 );
			$wcmp_wcv_hide_settings = get_option( 'wcmp_wcv_hide_settings', 0 );
			print '<tr><td><input aria-label="' . esc_attr__( 'Activate the WC Vendors add-on', 'music-player-for-woocommerce' ) . '" type="checkbox" name="wcmp_wcv_enabled" ' . ( $wcmp_wcv_enabled ? 'CHECKED' : '' ) . '></td><td width="100%"><b>' . esc_html__( 'Activate the WC Vendors add-on (Experimental add-on)', 'music-player-for-woocommerce' ) . '</b><br><i>' . esc_html__( 'If the "WC Vendors" plugin is installed on the website, check the checkbox to allow vendors to configure their music players.', 'music-player-for-woocommerce' ) . '</i><br><br>
            <input type="checkbox" aria-label="' . esc_attr__( 'Hide settings', 'music-player-for-woocommerce' ) . '" name="wcmp_wcv_hide_settings" ' . ( $wcmp_wcv_hide_settings ? 'CHECKED' : '' ) . '> ' . esc_html__( 'Hides the players settings from vendors interface.', 'music-player-for-woocommerce' ) . '</td></tr>';
		} // End general_settings

		public function save_general_settings() {
			update_option( 'wcmp_wcv_enabled', ( ! empty( $_POST['wcmp_wcv_enabled'] ) ) ? 1 : 0 ); // phpcs:ignore WordPress.Security.NonceVerification
			update_option( 'wcmp_wcv_hide_settings', ( ! empty( $_POST['wcmp_wcv_hide_settings'] ) ) ? 1 : 0 ); // phpcs:ignore WordPress.Security.NonceVerification
		} // End save_general_settings

		public function delete_product( $post_id ) {
			$this->_wcmp->delete_post( $post_id );
		} // End delete_product

	} // End WCMP_WCVENDORS_ADDON
}

new WCMP_WCVENDORS_ADDON( $wcmp );
