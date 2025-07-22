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

// include resources
wp_enqueue_style( 'bfp-admin-style', BFP_PLUGIN_URL . 'css/style-admin.css', array(), '0.1' );
wp_enqueue_style( 'bfp-admin-notices', BFP_PLUGIN_URL . 'css/admin-notices.css', array(), '0.1' );
wp_enqueue_media();
wp_enqueue_script( 'bfp-admin-js', BFP_PLUGIN_URL . 'js/admin.js', array(), '0.1' );
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

// Use the injected config instance instead of global
// Variables available: $config, $fileManager, $renderer

// Use bulk fetch for all product settings
$product_settings = $config->getStates(array(
    '_bfp_enable_player',
    '_bfp_audio_engine',
    '_bfp_group_cart_control',
    '_bfp_unified_player',
    '_bfp_play_all',
    '_bfp_loop',
    '_bfp_player_volume',
    '_bfp_demos'
), $post->ID);

// Extract variables for easier use
$enable_player = $product_settings['_bfp_enable_player'];
$audio_engine = $product_settings['_bfp_audio_engine'];
$group_cart_control = $product_settings['_bfp_group_cart_control'];
$single_player = $product_settings['_bfp_unified_player'];
$play_all = $product_settings['_bfp_play_all'];
$loop = $product_settings['_bfp_loop'];
$volume = $product_settings['_bfp_player_volume'];

// Extract demo settings from the consolidated array
$demos = $product_settings['_bfp_demos'];
$demos_enabled = $demos['enabled'] ?? false;
$file_percent = $demos['duration_percent'] ?? 50;
$own_demos = $demos['use_custom'] ?? false;
$direct_own_demos = $demos['direct_links'] ?? false;
$demos_list = $demos['demos_list'] ?? [];

// Get global audio engine for comparison
$global_audio_engine = $config->getState('_bfp_audio_engine');
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
					<td><div class="bfp-tooltip"><span class="bfp-tooltiptext"><?php esc_html_e( 'Player shows only if product is downloadable with audio files, or you\'ve selected custom audio files', 'bandfront-player' ); ?></span><input aria-label="<?php esc_attr_e( 'Enable player', 'bandfront-player' ); ?>" type="checkbox" id="_bfp_enable_player" name="_bfp_enable_player" <?php checked( $enable_player ); ?> /></div></td>
				</tr>
				<tr>
					<td><label for="_bfp_group_cart_control">📦 <?php esc_html_e( 'Merge grouped products', 'bandfront-player' ); ?></label></td>
					<td><input aria-label="<?php esc_attr_e( 'Merge in grouped products', 'bandfront-player' ); ?>" type="checkbox" id="_bfp_group_cart_control" name="_bfp_group_cart_control" <?php checked( $group_cart_control ); ?> /><br /><em class="bfp-em-text"><?php esc_html_e( 'Show "Add to cart" buttons and quantity fields within player rows for grouped products', 'bandfront-player' ); ?></em></td>
				</tr>
				<tr>
					<td valign="top">🎭 <?php esc_html_e( 'Player behavior', 'bandfront-player' ); ?></td>
					<td>
						<div class="bfp-checkbox-box">
							<label><input aria-label="<?php esc_attr_e( 'Show a single player instead of one player per audio file.', 'bandfront-player' ); ?>" name="_bfp_unified_player" type="checkbox" <?php checked( $single_player ); ?> />
							<span class="bfp-checkbox-label">🎭 <?php esc_html_e( 'Single player mode (one player for all tracks)', 'bandfront-player' ); ?></span></label>
						</div>
					</td>
				</tr>
				<tr>
					<td>
						<label for="_bfp_play_all">▶️ <?php esc_html_e( 'Auto-play next track', 'bandfront-player' ); ?></label>
					</td>
					<td>
						<input aria-label="<?php esc_attr_e( 'Play all', 'bandfront-player' ); ?>" type="checkbox" id="_bfp_play_all" name="_bfp_play_all" <?php checked( $play_all ); ?> />
					</td>
				</tr>
				<tr>
					<td>
						<label for="_bfp_loop">🔄 <?php esc_html_e( 'Loop tracks', 'bandfront-player' ); ?></label>
					</td>
					<td>
						<input aria-label="<?php esc_attr_e( 'Loop', 'bandfront-player' ); ?>" type="checkbox" id="_bfp_loop" name="_bfp_loop" <?php checked( $loop ); ?> />
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<table class="widefat bfp-settings-table">
							<tr><td colspan="2"><h2>🔒 <?php esc_html_e( 'File Truncation', 'bandfront-player' ); ?></h2></td></tr>
							<tr>
								<td width="30%"><label for="_bfp_demos_enabled">🛡️ <?php esc_html_e( 'Truncate audio files', 'bandfront-player' ); ?></label></td>
								<td><input aria-label="<?php esc_attr_e( 'Protect the file', 'bandfront-player' ); ?>" type="checkbox" id="_bfp_demos_enabled" name="_bfp_demos[enabled]" <?php checked( $demos_enabled ); ?> /><br>
								<em class="bfp-em-text"><?php esc_html_e( 'Create demo versions to prevent unauthorized downloading', 'bandfront-player' ); ?></em></td>
							</tr>
							<tr valign="top">
								<td width="30%"><label for="_bfp_demos_duration_percent">📊 <?php esc_html_e( 'Demo length (% of original)', 'bandfront-player' ); ?></label></td>
								<td>
									<input aria-label="<?php esc_attr_e( 'Percent of audio used for protected playbacks', 'bandfront-player' ); ?>" type="number" id="_bfp_demos_duration_percent" name="_bfp_demos[duration_percent]" value="<?php echo esc_attr( $file_percent ); ?>" /> % <br />
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
						<label><input aria-label="<?php esc_attr_e( 'Own demo files', 'bandfront-player' ); ?>" type="checkbox" name="_bfp_demos[use_custom]" <?php checked( $own_demos ); ?> /> 
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
								// Ensure demos_list is an array
								if (!is_array($demos_list)) {
									$demos_list = [];
								}
								
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
						<label><input aria-label="<?php esc_attr_e( 'Load directly the original demo files', 'bandfront-player' ); ?>" type="checkbox" name="_bfp_demos[direct_links]" <?php checked( $direct_own_demos ); ?> /> 
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

<div class="bfp-admin-section">
    <h3><?php esc_html_e('Audio Format Generation', 'bandfront-player'); ?></h3>
    
    <?php
    $lastGenerated = get_post_meta($post->ID, '_bfp_formats_generated', true);
    $availableFormats = get_post_meta($post->ID, '_bfp_available_formats', true);
    ?>
    
    <table class="form-table">
        <tr>
            <th scope="row"><?php esc_html_e('Format Status', 'bandfront-player'); ?></th>
            <td>
                <?php if ($lastGenerated): ?>
                    <p class="description">
                        <strong><?php esc_html_e('Last generated:', 'bandfront-player'); ?></strong> 
                        <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $lastGenerated)); ?>
                    </p>
                    <?php if (is_array($availableFormats) && !empty($availableFormats)): ?>
                        <p class="description">
                            <strong><?php esc_html_e('Available formats:', 'bandfront-player'); ?></strong> 
                            <?php echo esc_html(strtoupper(implode(', ', $availableFormats))); ?>
                        </p>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="description"><?php esc_html_e('Audio formats have not been generated yet.', 'bandfront-player'); ?></p>
                <?php endif; ?>
                
                <p class="bfp-regenerate-formats">
                    <button type="button" id="bfp-regenerate-formats" class="button button-secondary">
                        <?php esc_html_e('Regenerate Audio Formats', 'bandfront-player'); ?>
                    </button>
                    <span class="spinner" style="float: none; margin-top: 0;"></span>
                </p>
                
                <div id="bfp-regenerate-status" style="display: none; margin-top: 10px;">
                    <div class="notice notice-info inline">
                        <p><?php esc_html_e('Processing... Please wait.', 'bandfront-player'); ?></p>
                    </div>
                </div>
            </td>
        </tr>
    </table>
</div>

<script>
jQuery(document).ready(function($) {
    $('#bfp-regenerate-formats').on('click', function() {
        var $button = $(this);
        var $spinner = $button.next('.spinner');
        var $status = $('#bfp-regenerate-status');
        
        $button.prop('disabled', true);
        $spinner.addClass('is-active');
        $status.show();
        
        // Force save the product to trigger regeneration
        $('#publish').click();
    });
});
</script>

<style>.bfp-player-settings tr td:first-child{width:225px;}</style>