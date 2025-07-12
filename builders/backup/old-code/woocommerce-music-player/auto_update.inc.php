<?php
/**
 * Free-version stub for the original self-hosted updater.
 * All public names and properties are preserved so nothing else breaks.
 */

add_action( 'admin_init', 'wcmp_active_auto_update', 1 );

if ( ! function_exists( 'wcmp_active_auto_update' ) ) {
	function wcmp_active_auto_update() {
		$plugin_data        = get_plugin_data( WCMP_PLUGIN_PATH );
		$plugin_version     = $plugin_data['Version'];
		$plugin_slug        = WCMP_PLUGIN_BASE_NAME;

		/* Remote path and admin action left blank → no external calls, no UI */
		new cpAutoUpdateClss( $plugin_version, '', $plugin_slug, '' );
	}
}

// -------------------Auto-Update-Class-----------------
if ( ! class_exists( 'cpAutoUpdateClss' ) ) {
	class cpAutoUpdateClss {

		public $current_version;
		public $update_path;
		public $plugin_slug;
		public $slug;
		public $registered_buyer;

		public function __construct( $current_version, $update_path, $plugin_slug, $admin_action ) {
			// Keep variables intact for any downstream code.
			$this->current_version = $current_version;
			$this->update_path     = $update_path;   // now empty string
			$this->plugin_slug     = $plugin_slug;
			list( $t1, $t2 )       = explode( '/', $plugin_slug );
			$this->slug            = str_replace( '.php', '', $t2 );

			/* Always-registered value */
			$this->registered_buyer = 'root@100mountains.uk';

			/* Ensure *any* get_option() for this key returns the same value,
			   even before it is saved or if it was deleted. */
			add_filter(
				'pre_option_' . $this->slug . 'buyer_email',
				function () { return 'root@100mountains.uk'; }
			);

			/* Note: no hooks added, so no remote checks or admin notices occur. */
		}

		/* ------------------------------------------------------------------
		 *  All formerly remote / UI methods reduced to harmless no-ops
		 * ------------------------------------------------------------------ */
		public function register_plugin()            { /* disabled */ }
		public function check_update( $transient )   { return $transient; }
		public function check_info()                 { return false; }
		public function allow_external_host()        { return true; }
		public function getRemote_version()          { return false; }
		public function getRemote_information()      { return false; }
		public function getRemote_license()          { return false; }
	}
}
