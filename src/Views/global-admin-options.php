<?php
// Security check - use ABSPATH instead
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// include resources
wp_enqueue_style( 'bfp-admin-style', BFP_PLUGIN_URL . 'css/style-admin.css', array(), '5.0.181' );
wp_enqueue_style( 'bfp-admin-notices', BFP_PLUGIN_URL . 'css/admin-notices.css', array(), '5.0.181' );
wp_enqueue_media();
wp_enqueue_script( 'bfp-admin-js', BFP_PLUGIN_URL . 'js/admin.js', array(), '5.0.181' );
$bfp_js = array(
	'File Name'         => __( 'File Name', 'bandfront-player' ),
	'Choose file'       => __( 'Choose file', 'bandfront-player' ),
	'Delete'            => __( 'Delete', 'bandfront-player' ),
	'Select audio file' => __( 'Select audio file', 'bandfront-player' ),
	'Select Item'       => __( 'Select Item', 'bandfront-player' ),
);
wp_localize_script( 'bfp-admin-js', 'bfp', $bfp_js );

// Add AJAX localization
wp_localize_script( 'bfp-admin-js', 'bfp_ajax', array(
    'saving_text' => __('Saving settings...', 'bandfront-player'),
    'error_text' => __('An unexpected error occurred. Please try again.', 'bandfront-player'),
    'dismiss_text' => __('Dismiss this notice', 'bandfront-player'),
));

// Get all settings using state manager's admin form method
$settings = $GLOBALS['BandfrontPlayer']->getConfig()->getAdminFormSettings();

// For cloud settings, use bulk fetch
$cloud_settings = $GLOBALS['BandfrontPlayer']->getConfig()->getStates(array(
    '_bfp_cloud_active_tab',
    '_bfp_cloud_dropbox',
    '_bfp_cloud_s3',
    '_bfp_cloud_azure'
));

// Handle special cases
$ffmpeg_system_path = defined( 'PHP_OS' ) && strtolower( PHP_OS ) == 'linux' && function_exists( 'shell_exec' ) ? @shell_exec( 'which ffmpeg' ) : '';

// Cloud Storage Settings
$bfp_cloud_settings = get_option('_bfp_cloud_drive_addon', array());
$bfp_drive = isset($bfp_cloud_settings['_bfp_drive']) ? $bfp_cloud_settings['_bfp_drive'] : false;
$bfp_drive_key = isset($bfp_cloud_settings['_bfp_drive_key']) ? $bfp_cloud_settings['_bfp_drive_key'] : '';
$bfp_drive_api_key = get_option('_bfp_drive_api_key', '');

// Get cloud storage settings using bulk fetch for performance
$settings_keys = array(
    '_bfp_cloud_active_tab',
    '_bfp_cloud_dropbox',
    '_bfp_cloud_s3',
    '_bfp_cloud_azure'
);

$cloud_settings = $GLOBALS['BandfrontPlayer']->getConfig()->getStates($settings_keys);

$cloud_active_tab = $cloud_settings['_bfp_cloud_active_tab'];
$cloud_dropbox = $cloud_settings['_bfp_cloud_dropbox'];
$cloud_s3 = $cloud_settings['_bfp_cloud_s3'];
$cloud_azure = $cloud_settings['_bfp_cloud_azure'];

// Remove Google Drive settings UI from the settings page if present.
// remove_all_actions( 'bfp_general_settings', 10 );

?>
<h1><?php echo "\xF0\x9F\x8C\x88"; ?> <?php esc_html_e( 'Bandfront Player - Global Settings', 'bandfront-player' ); ?></h1>
<p class="bfp-tagline">a player for the storefront theme</p>

<div class="bfp-tips-container">
	<div id="bandcamp_nuke_tips_header">
		<h2 onclick="jQuery('#bandcamp_nuke_tips_body').toggle();">
			💥 <?php esc_html_e( 'Tips On How To Setup Bandfront [+|-]', 'bandfront-player' ); ?>
		</h2>
	</div>
	<div id="bandcamp_nuke_tips_body" class="bfp-tips-body">
		<div class="bfp-tips-grid">
			<div class="bfp-tips-card bfp-tips-card-start">
				<h3>🚀 <?php esc_html_e( 'Getting Started', 'bandfront-player' ); ?></h3>
				<p>
					<?php esc_html_e( 'New to Bandfront? Start here for a complete setup guide.', 'bandfront-player' ); ?>
				</p>
				<a href="#" onclick="window.open('/how-to-start', '_blank')" class="bfp-tips-link bfp-tips-link-start">
					<?php esc_html_e( 'How To Start Guide →', 'bandfront-player' ); ?>
				</a>
			</div>
			
			<div class="bfp-tips-card bfp-tips-card-shortcodes">
				<h3>📝 <?php esc_html_e( 'Shortcodes', 'bandfront-player' ); ?></h3>
				<p>
					<?php esc_html_e( 'Advanced users can embed players anywhere with shortcodes.', 'bandfront-player' ); ?>
				</p>
				<a href="#" onclick="window.open('/shortcodes', '_blank')" class="bfp-tips-link bfp-tips-link-shortcodes">
					<?php esc_html_e( 'Shortcode Reference →', 'bandfront-player' ); ?>
				</a>
			</div>
			
			<div class="bfp-tips-card bfp-tips-card-customization">
				<h3>🎨 <?php esc_html_e( 'Customization', 'bandfront-player' ); ?></h3>
				<p>
					<?php esc_html_e( 'Make your players match your brand with custom CSS and styling.', 'bandfront-player' ); ?>
				</p>
				<a href="#" onclick="window.open('/customisation', '_blank')" class="bfp-tips-link bfp-tips-link-customization">
					<?php esc_html_e( 'Customization Guide →', 'bandfront-player' ); ?>
				</a>
			</div>
		</div>
		
		<div class="bfp-tips-protip">
			<p>
				🎯 <?php esc_html_e( 'Pro Tip: Combine all three for maximum Bandcamp replacement!', 'bandfront-player' ); ?>
			</p>
		</div>
	</div>
</div>

<form method="post" enctype="multipart/form-data">
<input type="hidden" name="action" value="bfp_save_settings" />
<input type="hidden" name="bfp_nonce" value="<?php echo esc_attr( wp_create_nonce( 'bfp_updating_plugin_settings' ) ); ?>" />

<table class="widefat bfp-table-noborder">
	<tr>
			<table class="widefat bfp-settings-table bfp-general-settings-section">
				<tr>
					<td class="bfp-section-header">
						<h2 onclick="jQuery(this).closest('table').find('.bfp-section-content').toggle(); jQuery(this).closest('.bfp-section-header').find('.bfp-section-arrow').toggleClass('bfp-section-arrow-open');" style="cursor: pointer;">
							⚙️ <?php esc_html_e( 'General Settings', 'bandfront-player' ); ?>
						</h2>
						<span class="bfp-section-arrow">▶</span>
					</td>
				</tr>
				<tbody class="bfp-section-content" style="display: none;">
				<tr>
					<td class="bfp-column-30"><label for="_bfp_registered_only">👤 <?php esc_html_e( 'Registered users only', 'bandfront-player' ); ?></label></td>
					<td><input aria-label="<?php esc_attr_e( 'Include the players only for registered users', 'bandfront-player' ); ?>" type="checkbox" id="_bfp_registered_only" name="_bfp_registered_only" <?php checked( $settings['_bfp_registered_only'] ); ?> /><br>
					<em class="bfp-em-text"><?php esc_html_e( 'Only show audio players to logged-in users', 'bandfront-player' ); ?></em></td>
				</tr>
				<tr>
					<td class="bfp-column-30">🛒 <?php esc_html_e( 'Full tracks for buyers', 'bandfront-player' ); ?></td>
					<td>
						<label><input aria-label="<?php esc_attr_e( 'For buyers, play the purchased audio files instead of the truncated files for demo', 'bandfront-player' ); ?>" type="checkbox" name="_bfp_purchased" <?php checked( $settings['_bfp_purchased'] ); ?> />
						<?php esc_html_e( 'Let buyers hear full tracks instead of demos', 'bandfront-player' ); ?></label><br>
						<label class="bfp-settings-label"><?php esc_html_e( 'Reset access', 'bandfront-player' ); ?>
						<select aria-label="<?php esc_attr_e( 'Reset files interval', 'bandfront-player' ); ?>" name="_bfp_reset_purchased_interval">
							<option value="daily" <?php selected( $settings['_bfp_reset_purchased_interval'], 'daily' ); ?>><?php esc_html_e( 'daily', 'bandfront-player' ); ?></option>
							<option value="never" <?php selected( $settings['_bfp_reset_purchased_interval'], 'never' ); ?>><?php esc_html_e( 'never', 'bandfront-player' ); ?></option>
						</select></label>
					</td>
				</tr>
				<tr>
					<td class="bfp-column-30"><label for="_bfp_purchased_times_text">📊 <?php esc_html_e( 'Purchase count text', 'bandfront-player' ); ?></label></td>
					<td><input aria-label="<?php esc_attr_e( 'Purchased times text', 'bandfront-player' ); ?>" type="text" id="_bfp_purchased_times_text" name="_bfp_purchased_times_text" value="<?php echo esc_attr( $settings['_bfp_purchased_times_text'] ); ?>" class="bfp-input-full" /><br>
					<i class="bfp-em-text"><?php esc_html_e( 'Text shown in playlists when displaying purchase counts (use %d for the number)', 'bandfront-player' ); ?></i>
					</td>
				</tr>
				<?php
					do_action( 'bfp_general_settings' );
				?>
				</tbody>
			</table>
	</tr>
</table>

<table class="widefat bfp-table-noborder">
	<tr>
			<table class="widefat bfp-player-settings bfp-settings-table bfp-player-settings-section">
				<tr>
					<td class="bfp-section-header">
						<h2 onclick="jQuery(this).closest('table').find('.bfp-section-content').toggle(); jQuery(this).closest('.bfp-section-header').find('.bfp-section-arrow').toggleClass('bfp-section-arrow-open');" style="cursor: pointer;">
							🎵 <?php esc_html_e( 'Player Settings', 'bandfront-player' ); ?>
						</h2>
						<span class="bfp-section-arrow">▶</span>
					</td>
				</tr>
				<tbody class="bfp-section-content" style="display: none;">
				<tr>
					<td class="bfp-column-30"><label for="_bfp_enable_player">🎧 <?php esc_html_e( 'Enable players on all products', 'bandfront-player' ); ?></label></td>
					<td><div class="bfp-tooltip"><span class="bfp-tooltiptext"><?php esc_html_e( 'Players will show automatically for products with audio files', 'bandfront-player' ); ?></span><input aria-label="<?php esc_attr_e( 'Enable player', 'bandfront-player' ); ?>" type="checkbox" id="_bfp_enable_player" name="_bfp_enable_player" <?php checked( $settings['_bfp_enable_player'] ); ?> /></div></td>
				</tr>
				<tr>
					<td class="bfp-column-30"><label for="_bfp_players_in_cart">🛒 <?php esc_html_e( 'Show players in cart', 'bandfront-player' ); ?></label></td>
					<td>
						<input aria-label="<?php esc_attr_e( 'Include players in cart', 'bandfront-player' ); ?>" type="checkbox" id="_bfp_players_in_cart" name="_bfp_players_in_cart" <?php checked( $settings['_bfp_players_in_cart'] ); ?> />
					</td>
				</tr>
				<tr>
					<td class="bfp-column-30"><label for="_bfp_merge_in_grouped">📦 <?php esc_html_e( 'Merge grouped products', 'bandfront-player' ); ?></label></td>
					<td><input aria-label="<?php esc_attr_e( 'Merge in grouped products', 'bandfront-player' ); ?>" type="checkbox" id="_bfp_merge_in_grouped" name="_bfp_merge_in_grouped" <?php checked( $settings['_bfp_merge_in_grouped'] ); ?> /><br /><em class="bfp-em-text"><?php esc_html_e( 'Show "Add to cart" buttons and quantity fields within player rows for grouped products', 'bandfront-player' ); ?></em></td>
				</tr>
				<tr>
					<td valign="top" class="bfp-column-30">🎨 <?php esc_html_e( 'Player appearance', 'bandfront-player' ); ?></td>
					<td>
						<label><input type="radio" name="_bfp_player_layout" value="dark" <?php checked( $settings['_bfp_player_layout'], 'dark' ); ?>> 🌙 <?php esc_html_e('Dark', 'bandfront-player'); ?></label><br>
						<label><input type="radio" name="_bfp_player_layout" value="light" <?php checked( $settings['_bfp_player_layout'], 'light' ); ?>> ☀️ <?php esc_html_e('Light', 'bandfront-player'); ?></label><br>
						<label><input type="radio" name="_bfp_player_layout" value="custom" <?php checked( $settings['_bfp_player_layout'], 'custom' ); ?>> 🎨 <?php esc_html_e('Custom', 'bandfront-player'); ?></label><br><br>
						<label><input aria-label="<?php esc_attr_e( 'Show a single player instead of one player per audio file.', 'bandfront-player' ); ?>" name="_bfp_single_player" type="checkbox" <?php checked( $settings['_bfp_single_player'] ); ?> />
						<span class="bfp-single-player-label">🎭 <?php esc_html_e( 'Single player mode (one player for all tracks)', 'bandfront-player' ); ?></span></label>
					</td>
				</tr>
				<tr>
					<td class="bfp-column-30">
						<label for="_bfp_play_all">▶️ <?php esc_html_e( 'Auto-play next track', 'bandfront-player' ); ?></label>
					</td>
					<td>
						<input aria-label="<?php esc_attr_e( 'Play all', 'bandfront-player' ); ?>" type="checkbox" id="_bfp_play_all" name="_bfp_play_all" <?php checked( $settings['_bfp_play_all'] ); ?> />
					</td>
				</tr>
				<tr>
					<td class="bfp-column-30">
						<label for="_bfp_loop">🔄 <?php esc_html_e( 'Loop tracks', 'bandfront-player' ); ?></label>
					</td>
					<td>
						<input aria-label="<?php esc_attr_e( 'Loop', 'bandfront-player' ); ?>" type="checkbox" id="_bfp_loop" name="_bfp_loop" <?php checked( $settings['_bfp_loop'] ); ?> />
					</td>
				</tr>
				<tr>
					<td class="bfp-column-30">
						<label for="_bfp_play_simultaneously">🎵 <?php esc_html_e( 'Allow multiple players', 'bandfront-player' ); ?></label>
					</td>
					<td>
						<input aria-label="<?php esc_attr_e( 'Allow multiple players to play simultaneously', 'bandfront-player' ); ?>" type="checkbox" id="_bfp_play_simultaneously" name="_bfp_play_simultaneously" <?php checked( $settings['_bfp_play_simultaneously'] ); ?> /><br />
						<i class="bfp-em-text"><?php
							esc_html_e( 'Let multiple players play at the same time instead of stopping others when one starts', 'bandfront-player' );
						?></i>
					</td>
				</tr>
				<tr>
					<td><label for="_bfp_player_volume" >🔊 <?php esc_html_e( 'Default volume (0.0 to 1.0)', 'bandfront-player' ); ?></label></td>
					<td>
						<input aria-label="<?php esc_attr_e( 'Player volume', 'bandfront-player' ); ?>" type="number" id="_bfp_player_volume" name="_bfp_player_volume" min="0" max="1" step="0.01" value="<?php echo esc_attr( $settings['_bfp_player_volume'] ); ?>" />
					</td>
				</tr>
				<tr valign="top">
					<td>
						🎛️ <?php esc_html_e( 'Player controls', 'bandfront-player' ); ?>
					</td>
					<td>
						<label><input aria-label="<?php esc_attr_e( 'Player controls', 'bandfront-player' ); ?>" type="radio" value="button" name="_bfp_player_controls" <?php checked( $settings['_bfp_player_controls'], 'button' ); ?> /> <?php esc_html_e( 'Play/pause button only', 'bandfront-player' ); ?></label><br />
						<label><input aria-label="<?php esc_attr_e( 'Player controls', 'bandfront-player' ); ?>" type="radio" value="all" name="_bfp_player_controls" <?php checked( $settings['_bfp_player_controls'], 'all' ); ?> /> <?php esc_html_e( 'Full controls (progress bar, volume, etc.)', 'bandfront-player' ); ?></label><br />
						<label><input aria-label="<?php esc_attr_e( 'Player controls', 'bandfront-player' ); ?>" type="radio" value="default" name="_bfp_player_controls" <?php checked( $settings['_bfp_player_controls'], 'default' ); ?> /> <?php esc_html_e( 'Smart controls (minimal on shop, full on product pages)', 'bandfront-player' ); ?></label>
					</td>
				</tr>
				<tr>
					<td class="bfp-column-30"><label for="_bfp_player_title">🏷️ <?php esc_html_e( 'Show track titles', 'bandfront-player' ); ?></label></td>
					<td>
						<input aria-label="<?php esc_attr_e( 'Display the player title', 'bandfront-player' ); ?>" type="checkbox" id="_bfp_player_title" name="_bfp_player_title" <?php checked( $settings['_bfp_player_title'] ); ?> />
					</td>
				</tr>
				</tbody>
			</table>
	</tr>
</table>

<table class="widefat bfp-table-noborder">
	<tr>
			<table class="widefat bfp-settings-table bfp-file-truncation-section">
				<tr>
					<td class="bfp-section-header">
						<h2 onclick="jQuery(this).closest('table').find('.bfp-section-content').toggle(); jQuery(this).closest('.bfp-section-header').find('.bfp-section-arrow').toggleClass('bfp-section-arrow-open');" style="cursor: pointer;">
							🔒 <?php esc_html_e( 'Create Demo Files', 'bandfront-player' ); ?>
						</h2>
						<span class="bfp-section-arrow">▶</span>
					</td>
				</tr>
				<tbody class="bfp-section-content" style="display: none;">
				<tr>
					<td class="bfp-column-30"><label for="_bfp_secure_player">🛡️ <?php esc_html_e( 'Enable demo files', 'bandfront-player' ); ?></label></td>
					<td><input aria-label="<?php esc_attr_e( 'Protect the file', 'bandfront-player' ); ?>" type="checkbox" id="_bfp_secure_player" name="_bfp_secure_player" <?php checked( $settings['_bfp_secure_player'] ); ?> /><br>
					<em class="bfp-em-text"><?php esc_html_e( 'Create truncated demo versions to prevent unauthorized downloading of full tracks', 'bandfront-player' ); ?></em></td>
				</tr>
				<tr valign="top">
					<td class="bfp-column-30"><label for="_bfp_file_percent">📊 <?php esc_html_e( 'Demo length (% of original)', 'bandfront-player' ); ?></label></td>
					<td>
						<input aria-label="<?php esc_attr_e( 'Percent of audio used for protected playbacks', 'bandfront-player' ); ?>" type="number" id="_bfp_file_percent" name="_bfp_file_percent" min="0" max="100" value="<?php echo esc_attr( $settings['_bfp_file_percent'] ); ?>" /> % <br />
						<em class="bfp-em-text"><?php esc_html_e( 'How much of the original track to include in demos (e.g., 30% = first 30 seconds of a 100-second track)', 'bandfront-player' ); ?></em>
					</td>
				</tr>
				<tr>
					<td class="bfp-column-30"><label for="_bfp_fade_out">🎚️ <?php esc_html_e( 'Smooth fade out', 'bandfront-player' ); ?></label></td>
					<td><input aria-label="<?php esc_attr_e( 'Apply fade out to playing audio when possible', 'bandfront-player' ); ?>" type="checkbox" id="_bfp_fade_out" name="_bfp_fade_out" <?php checked( $settings['_bfp_fade_out'] ); ?> /><br>
					<em class="bfp-em-text"><?php esc_html_e( 'Gradually fade out audio when switching tracks', 'bandfront-player' ); ?></em></td>
				</tr>
				<tr valign="top">
					<td class="bfp-column-30">
						<label for="_bfp_message">💬 <?php esc_html_e( 'Demo notice text', 'bandfront-player' ); ?></label>
					</td>
					<td>
						<textarea aria-label="<?php esc_attr_e( 'Explaining that demos are partial versions of the original files', 'bandfront-player' ); ?>" id="_bfp_message" name="_bfp_message" class="bfp-input-full" rows="4"><?php echo esc_textarea( $settings['_bfp_message'] ); ?></textarea><br>
						<em class="bfp-em-text"><?php esc_html_e( 'Text shown next to players to explain these are preview versions', 'bandfront-player' ); ?></em>
					</td>
				</tr>
				</tbody>
			</table>
	</tr>
</table>

<table class="widefat bfp-table-noborder">
	<tr>
			<table class="widefat bfp-settings-table bfp-analytics-section">
				<tr>
					<td class="bfp-section-header">
						<h2 onclick="jQuery(this).closest('table').find('.bfp-section-content').toggle(); jQuery(this).closest('.bfp-section-header').find('.bfp-section-arrow').toggleClass('bfp-section-arrow-open');" style="cursor: pointer;">
							📈 <?php esc_html_e( 'Analytics', 'bandfront-player' ); ?>
						</h2>
						<span class="bfp-section-arrow">▶</span>
					</td>
				</tr>
				<tbody class="bfp-section-content" style="display: none;">
				<tr>
					<td>
					<label><input aria-label="<?php esc_attr_e( 'Show "playback Counter" in the WooCommerce products list', 'bandfront-player' ); ?>" type="checkbox" name="_bfp_playback_counter_column" <?php checked( $settings['_bfp_playback_counter_column'] ); ?> />
					📊 <?php esc_html_e( 'Show playback counter in products list', 'bandfront-player' ); ?></label>
					</td>
				</tr>
				<tr>
					<td>
						<p class="bfp-analytics-note"><?php esc_html_e( 'Connect with Google Analytics to track when songs are played. Events include the audio file URL and product ID.', 'bandfront-player' ); ?></p>
						<p class="bfp-analytics-warning"><b>📝 <?php esc_html_e( 'Note', 'bandfront-player' ); ?></b>: <?php esc_html_e( 'If preload is set to "Metadata" or "Auto", events are tracked when files load, not just when they play.', 'bandfront-player' ); ?></p>
					</td>
				</tr>
				<tr>
					<td>
						<label><input type="radio" name="_bfp_analytics_integration" value="ua" <?php checked( $settings['_bfp_analytics_integration'], 'ua' ); ?>> <?php esc_html_e( 'Universal Analytics', 'bandfront-player' ); ?></label>
						<label class="bfp-analytics-radio-spacer"><input type="radio" name="_bfp_analytics_integration" value="g" <?php checked( $settings['_bfp_analytics_integration'], 'g' ); ?>> <?php esc_html_e( 'Measurement Protocol (Google Analytics 4)', 'bandfront-player' ); ?></label>
					</td>
				</tr>
				<tr>
					<td>
						<div><?php esc_html_e( 'Measurement ID', 'bandfront-player' ); ?></div>
						<div><input aria-label="<?php esc_attr_e( 'Measurement id', 'bandfront-player' ); ?>" type="text" name="_bfp_analytics_property" value="<?php echo esc_attr( $settings['_bfp_analytics_property'] ); ?>" class="bfp-analytics-input" placeholder="UA-XXXXX-Y"></div>
					</td>
				</tr>
				<tr class="bfp-analytics-g4" style="display:<?php echo esc_attr( $settings['_bfp_analytics_integration'] === 'ua' ? 'none' : 'table-row' ); ?>;">
					<td class="bfp-input-full">
						<div><?php esc_html_e( 'API Secret', 'bandfront-player' ); ?></div>
						<div><input aria-label="<?php esc_attr_e( 'API Secret', 'bandfront-player' ); ?>" type="text" name="_bfp_analytics_api_secret" value="<?php echo esc_attr( $settings['_bfp_analytics_api_secret'] ); ?>" class="bfp-analytics-input"></div>
					</td>
				</tr>
				</tbody>
			</table>
	</tr>
</table>

<?php 
// Cloud Storage Settings remain the same as they're using different options
?>

<table class="widefat bfp-table-noborder">
	<tr>
			<table class="widefat bfp-settings-table bfp-cloud-storage-section">
				<tr>
					<td class="bfp-section-header">
						<h2 onclick="jQuery(this).closest('table').find('.bfp-section-content').toggle(); jQuery(this).closest('.bfp-section-header').find('.bfp-section-arrow').toggleClass('bfp-section-arrow-open');" style="cursor: pointer;">
							☁️ <?php esc_html_e( 'Cloud Storage', 'bandfront-player' ); ?> 
						</h2>
						<span class="bfp-section-arrow">▶</span>
					</td>
				</tr>
				<tbody class="bfp-section-content" style="display: none;">
				<tr>
					<td>
						<p class="bfp-cloud-info"><?php esc_html_e( 'Automatically upload demo files to cloud storage to save server storage and bandwidth. Files are streamed directly from the cloud.', 'bandfront-player' ); ?></p>
						
						<input type="hidden" name="_bfp_cloud_active_tab" id="_bfp_cloud_active_tab" value="<?php echo esc_attr($cloud_active_tab); ?>" />
						
						<div class="bfp-cloud_tabs">
							<div class="bfp-cloud-tab-buttons">
								<button type="button" class="bfp-cloud-tab-btn <?php echo $cloud_active_tab === 'google-drive' ? 'bfp-cloud-tab-active' : ''; ?>" data-tab="google-drive">
									🗂️ <?php esc_html_e( 'Google Drive', 'bandfront-player' ); ?>
								</button>
								<button type="button" class="bfp-cloud-tab-btn <?php echo $cloud_active_tab === 'dropbox' ? 'bfp-cloud-tab-active' : ''; ?>" data-tab="dropbox">
									📦 <?php esc_html_e( 'Dropbox', 'bandfront-player' ); ?>
								</button>
								<button type="button" class="bfp-cloud-tab-btn <?php echo $cloud_active_tab === 'aws-s3' ? 'bfp-cloud-tab-active' : ''; ?>" data-tab="aws-s3">
									🛡️ <?php esc_html_e( 'AWS S3', 'bandfront-player' ); ?>
								</button>
								<button type="button" class="bfp-cloud-tab-btn <?php echo $cloud_active_tab === 'azure' ? 'bfp-cloud-tab-active' : ''; ?>" data-tab="azure">
									☁️ <?php esc_html_e( 'Azure Blob', 'bandfront-player' ); ?>
								</button>
							</div>
							
							<div class="bfp-cloud-tab-content">
								<!-- Google Drive Tab -->
								<div class="bfp-cloud-tab-panel <?php echo $cloud_active_tab === 'google-drive' ? 'bfp-cloud-tab-panel-active' : ''; ?>" data-panel="google-drive">
									<table class="widefat">
										<tr>
											<td class="bfp-column-30"><label for="_bfp_drive"><?php esc_html_e( 'Store demo files on Google Drive', 'bandfront-player' ); ?></label></td>
											<td><input aria-label="<?php esc_attr_e( 'Store demo files on Google Drive', 'bandfront-player' ); ?>" type="checkbox" id="_bfp_drive" name="_bfp_drive" <?php checked( $bfp_drive ); ?> /></td>
										</tr>
										<tr>
											<td class="bfp-column-30">
												<?php esc_html_e( 'Import OAuth Client JSON File', 'bandfront-player' ); ?><br>
												(<?php esc_html_e( 'Required to upload demo files to Google Drive', 'bandfront-player' ); ?>)
											</td>
											<td>
												<input aria-label="<?php esc_attr_e( 'OAuth Client JSON file', 'bandfront-player' ); ?>" type="file" name="_bfp_drive_key" />
												<?php
												if ( ! empty( $bfp_drive_key ) ) {
													echo '<span class="bfp-oauth-success">' . esc_html__( 'OAuth Client Available ✅', 'bandfront-player' ) . '</span>';
												}
												?>
												<br /><br />
												<div class="bfp-cloud-instructions">
													<h3><?php esc_html_e( 'To create an OAuth 2.0 client ID:', 'bandfront-player' ); ?></h3>
													<p>
														<ol>
															<li><?php esc_html_e( 'Go to the', 'bandfront-player' ); ?> <a href="https://console.cloud.google.com/" target="_blank"><?php esc_html_e( 'Google Cloud Platform Console', 'bandfront-player' ); ?></a>.</li>
															<li><?php esc_html_e( 'From the projects list, select a project or create a new one.', 'bandfront-player' ); ?></li>
															<li><?php esc_html_e( 'If the APIs & services page isn\'t already open, open the console left side menu and select APIs & services.', 'bandfront-player' ); ?></li>
															<li><?php esc_html_e( 'On the left, click Credentials.', 'bandfront-player' ); ?></li>
															<li><?php esc_html_e( 'Click + CREATE CREDENTIALS, then select OAuth client ID.', 'bandfront-player' ); ?></li>
															<li><?php esc_html_e( 'Select the application type Web application.', 'bandfront-player' ); ?></li>
															<li><?php esc_html_e( 'Enter BandFront Player in the Name field.', 'bandfront-player' ); ?></li>
															<li><?php esc_html_e( 'Enter the URL below as the Authorized redirect URIs:', 'bandfront-player' ); ?>
															<br><br><b><i><?php 
															$callback_url = get_home_url( get_current_blog_id() );
															$callback_url .= ( ( strpos( $callback_url, '?' ) === false ) ? '?' : '&' ) . 'bfp-drive-credential=1';
															print esc_html( $callback_url ); 
															?></i></b><br><br></li>
															<li><?php esc_html_e( 'Press the Create button.', 'bandfront-player' ); ?></li>
															<li><?php esc_html_e( 'In the OAuth client created dialog, press the DOWNLOAD JSON button and store it on your computer, and press the Ok button.', 'bandfront-player' ); ?></li>
															<li><?php esc_html_e( 'Finally, select the downloaded file through the Import OAuth Client JSON File field above.', 'bandfront-player' ); ?></li>
														</ol>
													</p>
												</div>
											</td>
										</tr>
										<tr>
											<td class="bfp-column-30">
												<label for="_bfp_drive_api_key"><?php esc_html_e( 'API Key', 'bandfront-player' ); ?></label><br>
												(<?php esc_html_e( 'Required to read audio files from players', 'bandfront-player' ); ?>)
											</td>
											<td>
												<input aria-label="<?php esc_attr_e( 'API Key', 'bandfront-player' ); ?>" type="text" id="_bfp_drive_api_key" name="_bfp_drive_api_key" value="<?php print esc_attr( $bfp_drive_api_key ); ?>" class="bfp-input-full" />
												<br /><br />
												<div class="bfp-cloud-instructions">
													<h3><?php esc_html_e( 'Get API Key:', 'bandfront-player' ); ?></h3>
													<p>
														<ol>
															<li><?php esc_html_e( 'Go to the', 'bandfront-player' ); ?> <a href="https://console.cloud.google.com/" target="_blank"><?php esc_html_e( 'Google Cloud Platform Console', 'bandfront-player' ); ?></a>.</li>
															<li><?php esc_html_e( 'From the projects list, select a project or create a new one.', 'bandfront-player' ); ?></li>
															<li><?php esc_html_e( 'If the APIs & services page isn\'t already open, open the console left side menu and select APIs & services.', 'bandfront-player' ); ?></li>
															<li><?php esc_html_e( 'On the left, click Credentials.', 'bandfront-player' ); ?></li>
															<li><?php esc_html_e( 'Click + CREATE CREDENTIALS, then select API Key.', 'bandfront-player' ); ?></li>
															<li><?php esc_html_e( 'Copy the API Key.', 'bandfront-player' ); ?></li>
															<li><?php esc_html_e( 'Finally, paste it in the API Key field above.', 'bandfront-player' ); ?></li>
														</ol>
													</p>
												</div>
											</td>
										</tr>
									</table>
								</div>
								
								<!-- Dropbox Tab -->
								<div class="bfp-cloud-tab-panel <?php echo $cloud_active_tab === 'dropbox' ? 'bfp-cloud-tab-panel-active' : ''; ?>" data-panel="dropbox">
									<div class="bfp-cloud-placeholder">
										<h3>📦 <?php esc_html_e( 'Dropbox Integration', 'bandfront-player' ); ?></h3>
										<p><?php esc_html_e( 'Coming soon! Dropbox integration will allow you to store your demo files on Dropbox with automatic syncing and bandwidth optimization.', 'bandfront-player' ); ?></p>
										<div class="bfp-cloud-features">
											<h4><?php esc_html_e( 'Planned Features:', 'bandfront-player' ); ?></h4>
											<ul>
												<li>✨ <?php esc_html_e( 'Automatic file upload to Dropbox', 'bandfront-player' ); ?></li>
												<li>🔄 <?php esc_html_e( 'Real-time synchronization', 'bandfront-player' ); ?></li>
												<li>📊 <?php esc_html_e( 'Bandwidth usage analytics', 'bandfront-player' ); ?></li>
												<li>🛡️ <?php esc_html_e( 'Advanced security controls', 'bandfront-player' ); ?></li>
											</ul>
										</div>
									</div>
								</div>
								
								<!-- AWS S3 Tab -->
								<div class="bfp-cloud-tab-panel <?php echo $cloud_active_tab === 'aws-s3' ? 'bfp-cloud-tab-panel-active' : ''; ?>" data-panel="aws-s3">
									<div class="bfp-cloud-placeholder">
										<h3>🛡️ <?php esc_html_e( 'Amazon S3 Storage', 'bandfront-player' ); ?></h3>
										<p><?php esc_html_e( 'Enterprise-grade cloud storage with AWS S3. Perfect for high-traffic websites requiring maximum reliability and global CDN distribution.', 'bandfront-player' ); ?></p>
										<div class="bfp-cloud-features">
											<h4><?php esc_html_e( 'Planned Features:', 'bandfront-player' ); ?></h4>
											<ul>
												<li>🌍 <?php esc_html_e( 'Global CDN with CloudFront integration', 'bandfront-player' ); ?></li>
												<li>⚡ <?php esc_html_e( 'Lightning-fast file delivery', 'bandfront-player' ); ?></li>
												<li>💰 <?php esc_html_e( 'Cost-effective storage pricing', 'bandfront-player' ); ?></li>
												<li>🔐 <?php esc_html_e( 'Enterprise security and encryption', 'bandfront-player' ); ?></li>
											</ul>
										</div>
									</div>
								</div>
								
								<!-- Azure Tab -->
								<div class="bfp-cloud-tab-panel <?php echo $cloud_active_tab === 'azure' ? 'bfp-cloud-tab-panel-active' : ''; ?>" data-panel="azure">
									<div class="bfp-cloud-placeholder">
										<h3>☁️ <?php esc_html_e( 'Microsoft Azure Blob Storage', 'bandfront-player' ); ?></h3>
										<p><?php esc_html_e( 'Microsoft Azure Blob Storage integration for seamless file management and global distribution with enterprise-level security.', 'bandfront-player' ); ?></p>
										<div class="bfp-cloud-features">
											<h4><?php esc_html_e( 'Planned Features:', 'bandfront-player' ); ?></h4>
											<ul>
												<li>🏢 <?php esc_html_e( 'Enterprise Active Directory integration', 'bandfront-player' ); ?></li>
												<li>🌐 <?php esc_html_e( 'Global edge locations', 'bandfront-player' ); ?></li>
												<li>📈 <?php esc_html_e( 'Advanced analytics and monitoring', 'bandfront-player' ); ?></li>
												<li>🔒 <?php esc_html_e( 'Compliance-ready security features', 'bandfront-player' ); ?></li>
											</ul>
										</div>
									</div>
								</div>
							</div>
						</div>
					</td>
				</tr>
			</table>	
	</tr>
</table>

<table class="widefat bfp-table-noborder">
	<tr>	
			<table class="widefat bfp-settings-table bfp-audio-engine-section">
				<tr>
					<td class="bfp-section-header">
						<h2 onclick="jQuery(this).closest('table').find('.bfp-section-content').toggle(); jQuery(this).closest('.bfp-section-header').find('.bfp-section-arrow').toggleClass('bfp-section-arrow-open');" style="cursor: pointer;">
							⚙️ <?php esc_html_e( 'Audio Engine', 'bandfront-player' ); ?>
						</h2>
						<span class="bfp-section-arrow">▶</span>
					</td>
				</tr>
				<tbody class="bfp-section-content" style="display: none;">
				<?php 
				// Get current audio engine settings
				$current_settings = array(
					'_bfp_audio_engine' => $settings['_bfp_audio_engine'] ?? 'mediaelement',
					'_bfp_enable_visualizations' => $settings['_bfp_enable_visualizations'] ?? 0
				);
				
				// Call the audio engine settings action with the current settings
				do_action('bfp_module_audio_engine_settings', $current_settings); 
				?>
				<tr>
					<td colspan="2"><hr class="bfp-protection-divider" /></td>
				</tr>
				<tr>
					<td class="bfp-column-30"><label for="_bfp_ffmpeg">⚡ <?php esc_html_e( 'Use FFmpeg for demos', 'bandfront-player' ); ?></label></td>
					<td><input aria-label="<?php esc_attr_e( 'Truncate the audio files for demo with ffmpeg', 'bandfront-player' ); ?>" type="checkbox" id="_bfp_ffmpeg" name="_bfp_ffmpeg" <?php checked( $settings['_bfp_ffmpeg'] ); ?> /><br>
					<em class="bfp-em-text"><?php esc_html_e( 'Requires FFmpeg to be installed on your server', 'bandfront-player' ); ?></em></td>
				</tr>
				<tr>
					<td class="bfp-column-30"><label for="_bfp_ffmpeg_path">📁 <?php esc_html_e( 'FFmpeg path', 'bandfront-player' ); ?></label></td>
					<td>
						<input aria-label="<?php esc_attr_e( 'ffmpeg path', 'bandfront-player' ); ?>" type="text" id="_bfp_ffmpeg_path" name="_bfp_ffmpeg_path" value="<?php echo esc_attr( empty( $settings['_bfp_ffmpeg_path'] ) && ! empty( $ffmpeg_system_path ) ? $ffmpeg_system_path : $settings['_bfp_ffmpeg_path'] ); ?>" class="bfp-input-full" /><br />
						<i class="bfp-ffmpeg-example">Example: /usr/bin/</i>
					</td>
				</tr>
				<tr>
					<td class="bfp-column-30"><label for="_bfp_ffmpeg_watermark">🎤 <?php esc_html_e( 'Audio watermark', 'bandfront-player' ); ?></label></td>
					<td>
						<input aria-label="<?php esc_attr_e( 'Watermark audio', 'bandfront-player' ); ?>" type="text" id="_bfp_ffmpeg_watermark" name="_bfp_ffmpeg_watermark" value="<?php echo esc_attr( $settings['_bfp_ffmpeg_watermark'] ); ?>" class="bfp-watermark-input bfp-file-url" /><input type="button" class="button-secondary bfp-select-file bfp-watermark-button" value="<?php esc_attr_e( 'Select', 'bandfront-player' ); ?>" /><br />
						<i class="bfp-em-text"><?php esc_html_e( 'Optional audio file to overlay on demos ', 'bandfront-player' ); ?></i>
					</td>
				</tr>
				</tbody>
			</table>
	</tr>
</table>

<table class="widefat bfp-table-noborder">
	<tr>
			<table class="widefat bfp-settings-table bfp-troubleshoot-section">
				<tr>
					<td class="bfp-section-header">
						<h2 onclick="jQuery(this).closest('table').find('.bfp-section-content').toggle(); jQuery(this).closest('.bfp-section-header').find('.bfp-section-arrow').toggleClass('bfp-section-arrow-open');" style="cursor: pointer;">
							🔧 <?php esc_html_e( 'Troubleshooting', 'bandfront-player' ); ?>
						</h2>
						<span class="bfp-section-arrow">▶</span>
					</td>
				</tr>
				<tbody class="bfp-section-content" style="display: none;">
				<tr>
					<td class="bfp-troubleshoot-item bfp-troubleshoot-gutenberg">
						<p>
							🧱 <?php esc_html_e( 'Gutenberg blocks hiding your players?', 'bandfront-player' ); ?>
						</p>
						<label>
						<input aria-label="<?php esc_attr_e( 'For the WooCommerce Gutenberg Blocks, include the main player in the products titles', 'bandfront-player' ); ?>" type="checkbox" name="_bfp_force_main_player_in_title" <?php checked( $settings['_bfp_force_main_player_in_title'] ); ?>/>
						<?php esc_html_e( 'Force players to appear in product titles', 'bandfront-player' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<td class="bfp-troubleshoot-item bfp-troubleshoot-cleanup">
						<p>🗑️ <?php esc_html_e( 'Demo files corrupted or outdated?', 'bandfront-player' ); ?></p>
						<label>
						<input aria-label="<?php esc_attr_e( 'Delete the demo files generated previously', 'bandfront-player' ); ?>" type="checkbox" name="_bfp_delete_demos" />
						<?php esc_html_e( 'Regenerate demo files', 'bandfront-player' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<td>
						<p class="bfp-troubleshoot-protip">💡<?php esc_html_e( 'After changing troubleshooting settings, clear your website and browser caches for best results.', 'bandfront-player' ); ?></p>
					</td>
				</tr>
				</tbody>
			</table>
	</tr>
</table>
<div class="bfp-submit-wrapper"><input type="submit" value="<?php esc_attr_e( 'Save settings', 'bandfront-player' ); ?>" class="button-primary" /></div>
</form>