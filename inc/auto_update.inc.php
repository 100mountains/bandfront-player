<?php
/**
 * updatez
 */

add_action( 'admin_init', 'bfp_active_auto_update', 1 );

if ( ! function_exists( 'bfp_active_auto_update' ) ) {
	function bfp_active_auto_update() {
		$plugin_data        = get_plugin_data( BFP_PLUGIN_PATH );
		$plugin_version     = $plugin_data['Version'];
		$plugin_slug        = BFP_PLUGIN_BASE_NAME;

		/* Remote path and admin action left blank → no external calls, no UI */
		new bfpAutoUpdateClss( $plugin_version, '', $plugin_slug, '' );
	}
}

// -------------------Auto-Update-Class-----------------
if ( ! class_exists( 'bfpAutoUpdateClss' ) ) {
	class bfpAutoUpdateClss {

		public $current_version;
		public $update_path;
		public $plugin_slug;
		public $slug;

		public function __construct( $current_version, $update_path, $plugin_slug, $admin_action ) {
			// Keep variables intact for any downstream code.
			$this->current_version = $current_version;
			$this->update_path     = $update_path;   // now empty string
			$this->plugin_slug     = $plugin_slug;
			list( $t1, $t2 )       = explode( '/', $plugin_slug );
			$this->slug            = str_replace( '.php', '', $t2 );

		}

		/* ------------------------------------------------------------------
		 *  TBC: These methods are placeholders.
		 * ------------------------------------------------------------------ */
	
		public function check_update( $transient )   { return $transient; }
		public function check_info()                 { return false; }
		public function allow_external_host()        { return true; }
		public function getRemote_version()          { return false; }
		public function getRemote_information()      { return false; }

	}
}
