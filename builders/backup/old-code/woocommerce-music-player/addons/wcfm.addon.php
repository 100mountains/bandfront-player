<?php
if ( ! class_exists( 'WCMP_WCFM_ADDON' ) ) {
	class WCMP_WCFM_ADDON {

		private $_wcmp;

		public function __construct( $wcmp ) {
			 $this->_wcmp = $wcmp;

			if ( get_option( 'wcmp_wcfm_enabled', 1 ) ) {
				add_action( 'after_wcfm_products_downloadable', array( $this, 'product_settings' ), 10, 2 );
				add_action( 'save_post_product', array( $this, 'save_product_settings' ), 10, 3 );
			}
			add_action( 'wp_ajax_delete_wcfm_product', array( $this, 'delete_product' ) );
			add_action( 'wcmp_addon_general_settings', array( $this, 'general_settings' ) );
			add_action( 'wcmp_save_setting', array( $this, 'save_general_settings' ) );
		} // End __construct

		private function recursive_sanitize_text_field( $array ) {
			foreach ( $array as $key => &$value ) {
				if ( is_array( $value ) ) {
					$value = $this->recursive_sanitize_text_field( $value );
				} else {
					$value = sanitize_text_field( wp_unslash( $value ) );
				}
			}

			return $array;
		}

		public function general_settings() {
			$wcmp_wcfm_enabled       = get_option( 'wcmp_wcfm_enabled', 1 );
			$wcmp_wcfm_hide_settings = get_option( 'wcmp_wcfm_hide_settings', 0 );
			print '<tr><td><input aria-label="' . esc_attr__( 'Activate the WCFM add-on', 'music-player-for-woocommerce' ) . '" type="checkbox" name="wcmp_wcfm_enabled" ' . ( $wcmp_wcfm_enabled ? 'CHECKED' : '' ) . '></td><td width="100%"><b>' . esc_html__( 'Activate the WCFM add-on', 'music-player-for-woocommerce' ) . '</b><br><i>' . esc_html__( 'If the "WCFM - Marketplace" plugin is installed on the website, check the checkbox to allow vendors to configure their music players.', 'music-player-for-woocommerce' ) . '</i><br><br>
            <input type="checkbox" aria-label="' . esc_attr__( 'Hide settings', 'music-player-for-woocommerce' ) . '" name="wcmp_wcfm_hide_settings" ' . ( $wcmp_wcfm_hide_settings ? 'CHECKED' : '' ) . '> ' . esc_html__( 'Hides the players settings from vendors interface.', 'music-player-for-woocommerce' ) . '</td></tr>';
		} // End general_settings

		public function save_general_settings() {
			update_option( 'wcmp_wcfm_enabled', ( ! empty( $_POST['wcmp_wcfm_enabled'] ) ) ? 1 : 0 ); // phpcs:ignore WordPress.Security.NonceVerification
			update_option( 'wcmp_wcfm_hide_settings', ( ! empty( $_POST['wcmp_wcfm_hide_settings'] ) ) ? 1 : 0 ); // phpcs:ignore WordPress.Security.NonceVerification
		} // End save_general_settings

		public function product_settings( $product_id, $product_type ) {
			global $wcmp_wcfm_flag;
			$wcmp_wcfm_flag = true;

			$post = get_post( $product_id );
			wp_enqueue_style( 'wcmp-wcfm-css', plugin_dir_url( __FILE__ ) . 'wcfm/style.css', array(), WCMP_VERSION );
			wp_enqueue_script( 'wcmp-wcfm-js', plugin_dir_url( __FILE__ ) . 'wcfm/script.js', array(), WCMP_VERSION );
			?>
			<div class="page_collapsible simple variable grouped" id="wcfm_products_manage_form_wcmp_head"><label class="wcfmfa fa-object-group"></label><?php esc_html_e( 'Music Player', 'music-player-for-woocommerce' ); ?><span></span></div>
			<div class="wcfm-container simple variable grouped">
				<div id="wcfm_products_manage_form_wcmp_expander" class="wcfm-content">
				<input type="hidden" name="wcmp_nonce" value="<?php echo esc_attr( wp_create_nonce( 'wcmp_updating_product' ) ); ?>" />
				<?php
				include_once dirname( __FILE__ ) . '/../views/player_options.php';
				?>
				</div>
			</div>
			<!-- end collapsible -->
			<div class="wcfm_clearfix"></div>
			<?php
		} // End product_settings

		public function save_product_settings( $post_id, $post, $update ) {
			if ( ! empty( $_POST['wcfm_products_manage_form'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				global $wcmp_wcfm_flag;
				$wcmp_wcfm_flag = true;
				$arr            = wp_parse_args( is_array( $_POST['wcfm_products_manage_form'] ) ? $this->recursive_sanitize_text_field( $_POST['wcfm_products_manage_form'] ) : sanitize_text_field( wp_unslash( $_POST['wcfm_products_manage_form'] ) ) ); // @codingStandardsIgnoreLine
				if ( ! empty( $arr ) ) {
					$_POST    = array_replace_recursive( $_POST, $arr ); // phpcs:ignore WordPress.Security.NonceVerification
					$_REQUEST = array_replace_recursive( $_REQUEST, $arr );

					$post = wc_get_product( $post_id );
					$this->_wcmp->save_post( $post_id, $post, true );
				}
			}
		} // End save_product_settings

		public function delete_product() {
			$proid = isset( $_POST['proid'] ) && is_numeric( $_POST['proid'] ) ? intval( $_POST['proid'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
			if ( $proid ) {
				$this->_wcmp->delete_post( $proid );
			}
		} // End delete_product

		// ******************** PRIVATE METHODS ************************


	} // End WCMP_WCFM_ADDON
}

new WCMP_WCFM_ADDON( $wcmp );
