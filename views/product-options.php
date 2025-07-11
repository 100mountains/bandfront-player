<?php
if (!defined('ABSPATH')) {
	exit; // Exit if accessed directly
}
/**
 * Product Options View for Bandfront Player
 *
 * @package BandfrontPlayer
 * @since 0.1
 */



/**
 * Bandfront Player Product Options
 * Handles display and management of product-specific player settings
 */


// include resources
wp_enqueue_style( 'bfp-admin-style', plugin_dir_url( __FILE__ ) . '../css/style-admin.css', array(), '5.0.181' );
wp_enqueue_script( 'bfp-admin-js', plugin_dir_url( __FILE__ ) . '../js/admin.js', array(), '5.0.181' );
$bfp_js = array(
	'File Name'         => __( 'File Name', 'bandfront-player' ),
	'Choose file'       => __( 'Choose file', 'bandfront-player' ),
	'Delete'            => __( 'Delete', 'bandfront-player' ),
	'Select audio file' => __( 'Select audio file', 'bandfront-player' ),
	'Select Item'       => __( 'Select Item', 'bandfront-player' ),
);
wp_localize_script( 'bfp-admin-js', 'bfp', $bfp_js );

if (
	isset( $_REQUEST['post'] ) &&
	is_numeric( $_REQUEST['post'] ) &&
	( $post_id = intval( $_REQUEST['post'] ) ) &&
	( $check_post = get_post( $post_id ) )
) {
	$post = $check_post;
}

if ( empty( $post ) ) {
	global $post;
}

$enable_player    = $GLOBALS['BandfrontPlayer']->get_product_attr( $post->ID, '_bfp_enable_player', false );
$single_player    = $GLOBALS['BandfrontPlayer']->get_product_attr( $post->ID, '_bfp_single_player', BFP_DEFAULT_SINGLE_PLAYER );
$volume           = $GLOBALS['BandfrontPlayer']->get_product_attr( $post->ID, '_bfp_player_volume', BFP_DEFAULT_PLAYER_VOLUME );
$secure_player    = $GLOBALS['BandfrontPlayer']->get_product_attr( $post->ID, '_bfp_secure_player', false );
$file_percent     = $GLOBALS['BandfrontPlayer']->get_product_attr( $post->ID, '_bfp_file_percent', BFP_FILE_PERCENT );
$merge_grouped    = intval( $GLOBALS['BandfrontPlayer']->get_product_attr( $post->ID, '_bfp_merge_in_grouped', 0 ) );
$own_demos        = intval( $GLOBALS['BandfrontPlayer']->get_product_attr( $post->ID, '_bfp_own_demos', 0 ) );
$direct_own_demos = intval( $GLOBALS['BandfrontPlayer']->get_product_attr( $post->ID, '_bfp_direct_own_demos', 0 ) );
$demos_list       = $GLOBALS['BandfrontPlayer']->get_product_attr( $post->ID, '_bfp_demos_list', array() );
$play_all = intval(
	$GLOBALS['BandfrontPlayer']->get_product_attr(
		$post->ID,
		'_bfp_play_all',
		0
	)
);
$loop     = intval( $GLOBALS['BandfrontPlayer']->get_product_attr( $post->ID, '_bfp_loop', 0 ) );
$preload  = $GLOBALS['BandfrontPlayer']->get_product_attr(
	$post->ID,
	'_bfp_preload',
	$GLOBALS['BandfrontPlayer']->get_product_attr(
		$post->ID,
		'preload',
		'none'
	)
);
?>
<h2><?php echo "\xF0\x9F\x8C\x88"; ?> <?php esc_html_e( 'Product Music Player Settings', 'bandfront-player' ); ?></h2>
<p class="bfp-page-tagline">customize essential player settings for this specific product</p>
<input type="hidden" name="bfp_nonce" value="<?php echo esc_attr( wp_create_nonce( 'bfp_updating_product' ) ); ?>" />
<?php
// Always show the player settings table (no vendor plugin checks)
?>
<table class="widefat bfp-main-table">
	<tr>
		<td>
			<table class="widefat bfp-player-settings bfp-settings-table">
				<tr>
					<td><label for="_bfp_enable_player">🎧 <?php esc_html_e( 'Include music player', 'bandfront-player' ); ?></label></td>
					<td><div class="bfp-tooltip"><span class="bfp-tooltiptext"><?php esc_html_e( 'Player shows only if product is downloadable with audio files, or you\'ve selected custom audio files', 'bandfront-player' ); ?></span><input aria-label="<?php esc_attr_e( 'Enable player', 'bandfront-player' ); ?>" type="checkbox" id="_bfp_enable_player" name="_bfp_enable_player" <?php echo ( ( $enable_player ) ? 'checked' : '' ); ?> /></div></td>
				</tr>
				<tr>
					<td><label for="_bfp_merge_in_grouped">📦 <?php esc_html_e( 'Merge grouped products', 'bandfront-player' ); ?></label></td>
					<td><input aria-label="<?php esc_attr_e( 'Merge in grouped products', 'bandfront-player' ); ?>" type="checkbox" id="_bfp_merge_in_grouped" name="_bfp_merge_in_grouped" <?php echo ( ( $merge_grouped ) ? 'checked' : '' ); ?> /><br /><em class="bfp-em-text"><?php esc_html_e( 'Show "Add to cart" buttons and quantity fields within player rows for grouped products', 'bandfront-player' ); ?></em></td>
				</tr>
				<tr>
					<td valign="top">🎭 <?php esc_html_e( 'Player behavior', 'bandfront-player' ); ?></td>
					<td>
						<div class="bfp-checkbox-box">
							<label><input aria-label="<?php esc_attr_e( 'Show a single player instead of one player per audio file.', 'bandfront-player' ); ?>" name="_bfp_single_player" type="checkbox" <?php echo ( ( $single_player ) ? 'checked' : '' ); ?> />
							<span class="bfp-checkbox-label">🎭 <?php esc_html_e( 'Single player mode (one player for all tracks)', 'bandfront-player' ); ?></span></label>
						</div>
					</td>
				</tr>
				<tr>
					<td>
						⏭️ <?php esc_html_e( 'Preload behavior', 'bandfront-player' ); ?>
					</td>
					<td>
						<label><input aria-label="<?php esc_attr_e( 'Preload - none', 'bandfront-player' ); ?>" type="radio" name="_bfp_preload" value="none" <?php if ( 'none' == $preload ) {
							echo 'CHECKED';} ?> /> None</label><br />
						<label><input aria-label="<?php esc_attr_e( 'Preload - metadata', 'bandfront-player' ); ?>" type="radio" name="_bfp_preload" value="metadata" <?php if ( 'metadata' == $preload ) {
							echo 'CHECKED';} ?> /> Metadata</label><br />
						<label><input aria-label="<?php esc_attr_e( 'Preload - auto', 'bandfront-player' ); ?>" type="radio" name="_bfp_preload" value="auto" <?php if ( 'auto' == $preload ) {
							echo 'CHECKED';} ?> /> Auto</label><br />
					</td>
				</tr>
				<tr>
					<td>
						<label for="_bfp_play_all">▶️ <?php esc_html_e( 'Auto-play next track', 'bandfront-player' ); ?></label>
					</td>
					<td>
						<input aria-label="<?php esc_attr_e( 'Play all', 'bandfront-player' ); ?>" type="checkbox" id="_bfp_play_all" name="_bfp_play_all" <?php if ( ! empty( $play_all ) ) {
							echo 'CHECKED';} ?> />
					</td>
				</tr>
				<tr>
					<td>
						<label for="_bfp_loop">🔄 <?php esc_html_e( 'Loop tracks', 'bandfront-player' ); ?></label>
					</td>
					<td>
						<input aria-label="<?php esc_attr_e( 'Loop', 'bandfront-player' ); ?>" type="checkbox" id="_bfp_loop" name="_bfp_loop" <?php if ( ! empty( $loop ) ) {
							echo 'CHECKED';} ?> />
					</td>
				</tr>
				<tr>
					<td>🔊 <?php esc_html_e( 'Default volume (0.0 to 1.0)', 'bandfront-player' ); ?></td>
					<td>
						<input aria-label="<?php esc_attr_e( 'Player volume', 'bandfront-player' ); ?>" type="number" name="_bfp_player_volume" min="0" max="1" step="0.01" value="<?php echo esc_attr( $volume ); ?>" />
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<table class="widefat bfp-settings-table">
							<tr><td colspan="2"><h2>🔒 <?php esc_html_e( 'File Truncation', 'bandfront-player' ); ?></h2></td></tr>
							<tr>
								<td width="30%"><label for="_bfp_secure_player">🛡️ <?php esc_html_e( 'Truncate audio files', 'bandfront-player' ); ?></label></td>
								<td><input aria-label="<?php esc_attr_e( 'Protect the file', 'bandfront-player' ); ?>" type="checkbox" id="_bfp_secure_player" name="_bfp_secure_player" <?php echo ( ( $secure_player ) ? 'checked' : '' ); ?> /><br>
								<em class="bfp-em-text"><?php esc_html_e( 'Create demo versions to prevent unauthorized downloading', 'bandfront-player' ); ?></em></td>
							</tr>
							<tr valign="top">
								<td width="30%"><label for="_bfp_file_percent">📊 <?php esc_html_e( 'Demo length (% of original)', 'bandfront-player' ); ?></label></td>
								<td>
									<input aria-label="<?php esc_attr_e( 'Percent of audio used for protected playbacks', 'bandfront-player' ); ?>" type="number" id="_bfp_file_percent" name="_bfp_file_percent" value="<?php echo esc_attr( $file_percent ); ?>" /> % <br />
									<em class="bfp-em-text"><?php esc_html_e( 'How much of the original track to include in demos (e.g., 30% = first 30 seconds of a 100-second track)', 'bandfront-player' ); ?></em>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
<table class="widefat bfp-table-noborder" style="padding-bottom:20px;">
	<tr>
		<td>
			<table class="widefat bfp-player-demos bfp-settings-table">
				<tr>
					<td colspan="2"><h2>🎼 <?php esc_html_e( 'Custom Demo Files', 'bandfront-player' ); ?></h2></td>
				</tr>
				<tr valign="top">
					<td colspan="2" class="bfp-demo-checkbox-box">
						<label><input aria-label="<?php esc_attr_e( 'Own demo files', 'bandfront-player' ); ?>" type="checkbox" name="_bfp_own_demos" <?php echo ( ( $own_demos ) ? 'checked' : '' ); ?> /> 
						<strong>🎵 <?php esc_html_e( 'Use my own custom demo files', 'bandfront-player' ); ?></strong></label>
						<p class="bfp-demo-description">
							<?php esc_html_e( 'Upload your own demo versions instead of auto-generating them from the original files', 'bandfront-player' ); ?>
						</p>
					</td>
				</tr>
				<tr valign="top" class="bfp-demo-files <?php echo ( $own_demos ) ? 'bfp-demo-files-row' : 'bfp-demo-files-hidden'; ?>">
					<td>
						<div class="bfp-demo-files-label"><b><?php esc_html_e( 'Demo files', 'bandfront-player' ); ?></b></div>
						<table class="widefat">
							<thead>
								<tr>
									<th><?php esc_html_e( 'Name', 'bandfront-player' ); ?></th>
									<th colspan="2"><?php esc_html_e( 'File URL', 'bandfront-player' ); ?></th>
									<th>&nbsp;</th>
								</tr>
							</thead>
							<tbody>
								<?php
								foreach ( $demos_list as $demo ) {
									?>
									<tr>
										<td>
											<input aria-label="<?php esc_attr_e( 'File name', 'bandfront-player' ); ?>" type="text" class="bfp-file-name" placeholder="<?php esc_attr_e( 'File Name', 'bandfront-player' ); ?>" name="_bfp_file_names[]" value="<?php echo esc_attr( $demo['name'] ); ?>" />
										</td>
										<td>
											<input aria-label="<?php esc_attr_e( 'File URL', 'bandfront-player' ); ?>" type="text" class="bfp-file-url" placeholder="http://" name="_bfp_file_urls[]" value="<?php echo esc_attr( $demo['file'] ); ?>" />
										</td>
										<td width="1%">
											<a href="#" class="btn btn-default button bfp-select-file"><?php esc_html_e( 'Choose file', 'bandfront-player' ); ?></a>
										</td>
										<td width="1%">
											<a href="#" class="bfp-delete"><?php esc_html_e( 'Delete', 'bandfront-player' ); ?></a>
										</td>
									</tr>
									<?php
								}
								?>
							</tbody>
							<tfoot>
								<tr>
									<th colspan="4">
										<a href="#" class="button bfp-add"><?php esc_html_e( 'Add File', 'bandfront-player' ); ?></a>
									</th>
								</tr>
							</tfoot>
						</table>
					</td>
				</tr>
				<tr valign="top">
					<td colspan="2" class="bfp-direct-demo-box">
						<label><input aria-label="<?php esc_attr_e( 'Load directly the original demo files', 'bandfront-player' ); ?>" type="checkbox" name="_bfp_direct_own_demos" <?php echo ( ( $direct_own_demos ) ? 'checked' : '' ); ?> /> 
						<strong>⚡ <?php esc_html_e( 'Load demo files directly (no preprocessing)', 'bandfront-player' ); ?></strong></label>
						<p class="bfp-demo-description">
							<?php esc_html_e( 'Skip processing and use your demo files exactly as uploaded', 'bandfront-player' ); ?>
						</p>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
<?php
	/**
	 * Module options
	 */
	do_action( 'bfp_module_product_settings', $post->ID );
	?>
<style>.bfp-player-settings tr td:first-child{width:225px;}</style>