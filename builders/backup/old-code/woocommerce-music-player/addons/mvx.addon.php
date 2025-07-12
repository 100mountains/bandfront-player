<?php
if ( ! class_exists( 'WCMP_MVX_ADDON' ) ) {
	class WCMP_MVX_ADDON {

		private $_wcmp;

		public function __construct( $wcmp ) {
			$this->_wcmp = $wcmp;

			add_action( 'wcmp_addon_general_settings', array( $this, 'general_settings' ) );
			add_action( 'wcmp_save_setting', array( $this, 'save_general_settings' ) );

			if (!defined('MVX_PLUGIN_TOKEN')) {
				return;
			}

			add_action( 'wcv_delete_post', array( $this, 'delete_product' ) );
			add_action( 'mvx_frontend_after_general_product_data', array( $this, 'product_settings' ), 10, 3 );
			add_action( 'save_post', array( $this, 'save_product_settings' ) );

			if ( get_current_user_id() ) {
				$user  = wp_get_current_user();
				$roles = (array) $user->roles;
				if ( in_array( 'dc_vendor', $roles ) ) {

					global $wcmp_mvx_flag;
					$wcmp_mvx_flag = true;

					if (
						get_option( 'wcmp_mvx_enabled', 0 ) &&
						! empty( $_SERVER['REQUEST_URI'] ) &&
						preg_match( '/\bdashboard\b/i', $_SERVER['REQUEST_URI'] )
					) {
						$GLOBALS['WooCommerceMusicPlayer']->init_force_in_title( 0 );
						add_filter( 'wcmp_global_attr', function( $value, $attr ) {
							if ( '_wcmp_force_main_player_in_title' == $attr ) {
								return 0;
							}
							return $value;
						}, 10, 2 );
					}
				}
			}

		} // End __construct

		public function general_settings() {
			$wcmp_mvx_enabled       = get_option( 'wcmp_mvx_enabled', 1 );
			$wcmp_mvx_hide_settings = get_option( 'wcmp_mvx_hide_settings', 0 );
			print '<tr><td><input aria-label="' . esc_attr__( 'Activate the MultiVendorX add-on', 'music-player-for-woocommerce' ) . '" type="checkbox" name="wcmp_mvx_enabled" ' . ( $wcmp_mvx_enabled ? 'CHECKED' : '' ) . '></td><td width="100%"><b>' . esc_html__( 'Activate the MultiVendorX add-on (Experimental add-on)', 'music-player-for-woocommerce' ) . '</b><br><i>' . esc_html__( 'If the "MultiVendorX" plugin is installed on the website, tick the checkbox to allow vendors to configure their music players.', 'music-player-for-woocommerce' ) . '</i><br><br>
            <input type="checkbox" aria-label="' . esc_attr__( 'Hide settings', 'music-player-for-woocommerce' ) . '" name="wcmp_mvx_hide_settings" ' . ( $wcmp_mvx_hide_settings ? 'CHECKED' : '' ) . '> ' . esc_html__( 'Hides the players settings from vendors interface.', 'music-player-for-woocommerce' ) . '</td></tr>';
		} // End general_settings

		public function save_general_settings() {
			update_option( 'wcmp_mvx_enabled', ( ! empty( $_POST['wcmp_mvx_enabled'] ) ) ? 1 : 0 ); // phpcs:ignore WordPress.Security.NonceVerification
			update_option( 'wcmp_mvx_hide_settings', ( ! empty( $_POST['wcmp_mvx_hide_settings'] ) ) ? 1 : 0 ); // phpcs:ignore WordPress.Security.NonceVerification
		} // End save_general_settings

		public function save_product_settings( $post_id ) {
			if ( ! get_option( 'wcmp_mvx_enabled', 0 ) ) {
				return;
			}

			$post_obj = get_post( $post_id );

			if ( ! empty( $post_obj ) ) {
				$GLOBALS['WooCommerceMusicPlayer']->save_post( $post_id, $post_obj, null );
			}

		} // End save_product_settings

		public function product_settings( $mvx_obj, $product_obj, $post_obj ) {

			if ( ! get_option( 'wcmp_mvx_enabled', 0 ) ) {
				return;
			}

			print '
			<style>
				.mvx-wcmp-settings{ padding: 20px; }
				.mvx-wcmp-settings table{ width: 100% !important; border: 0 !important; }
				.mvx-wcmp-settings table td{ padding-top: 10px !important; padding-bottom: 10px !important; }
				.mvx-wcmp-settings br{ display: block !important; margin-top: 10px;}
				.mvx-wcmp-settings input[type="number"]{width: 100px !important; }
				.mvx-wcmp-settings .wcmp-highlight-box *{line-height: 20px !important;}
				.mvx-wcmp-settings .wcmp-select-file{white-space:nowrap; margin-left:10px;margin-right:10px;}
				.mvx-wcmp-settings .wcmp-demo-files table tr>td:first-child{padding-right:10px;}
			</style>
			<div class="control-label mvx-wcmp-settings">
			<hr />
			<h3 style="margin-top:20px;">' . esc_html__( 'Music Player', 'music-player-for-woocommerce' ) . '</h3>
			';

			global $post;
			$post = $post_obj;
			ob_start();
			$GLOBALS['WooCommerceMusicPlayer']->woocommerce_player_settings();
			$content = ob_get_contents();
			ob_end_clean();

			$content = str_replace( 'button', 'btn btn-default', $content );
			print $content;

			print '</div>';
		} // End product_settings

		public function delete_product( $post_id ) {
			$this->_wcmp->delete_post( $post_id );
		} // End delete_product

	} // End WCMP_MVX_ADDON
}

new WCMP_MVX_ADDON( $wcmp );
