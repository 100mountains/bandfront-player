<?php
if ( ! defined( 'BFP_PLUGIN_URL' ) ) {
	echo 'Direct access not allowed.';
	exit; }

// include resources
wp_enqueue_style( 'bfp-admin-style', plugin_dir_url( __FILE__ ) . '../css/style.admin.css', array(), '5.0.181' );
wp_enqueue_media();
wp_enqueue_script( 'bfp-admin-js', plugin_dir_url( __FILE__ ) . '../js/admin.js', array(), '5.0.181' );
$bfp_js = array(
	'File Name'         => __( 'File Name', 'bandfront-player' ),
	'Choose file'       => __( 'Choose file', 'bandfront-player' ),
	'Delete'            => __( 'Delete', 'bandfront-player' ),
	'Select audio file' => __( 'Select audio file', 'bandfront-player' ),
	'Select Item'       => __( 'Select Item', 'bandfront-player' ),
);
wp_localize_script( 'bfp-admin-js', 'bfp', $bfp_js );

$ffmpeg             = $GLOBALS['BandfrontPlayer']->get_global_attr( '_bfp_ffmpeg', false );
$ffmpeg_path        = $GLOBALS['BandfrontPlayer']->get_global_attr( '_bfp_ffmpeg_path', '' );
$ffmpeg_system_path = defined( 'PHP_OS' ) && strtolower( PHP_OS ) == 'linux' && function_exists( 'shell_exec' ) ? @shell_exec( 'which ffmpeg' ) : '';
$ffmpeg_watermark   = $GLOBALS['BandfrontPlayer']->get_global_attr( '_bfp_ffmpeg_watermark', '' );

$troubleshoot_default_extension = $GLOBALS['BandfrontPlayer']->get_global_attr( '_bfp_default_extension', false );
$force_main_player_in_title     = $GLOBALS['BandfrontPlayer']->get_global_attr( '_bfp_force_main_player_in_title', 1 );
$ios_controls                   = $GLOBALS['BandfrontPlayer']->get_global_attr( '_bfp_ios_controls', false );
$troubleshoot_onload            = $GLOBALS['BandfrontPlayer']->get_global_attr( '_bfp_onload', false );
$disable_302         			= trim( $GLOBALS['BandfrontPlayer']->get_global_attr( '_bfp_disable_302', 0 ) );

$enable_player   = $GLOBALS['BandfrontPlayer']->get_global_attr( '_bfp_enable_player', false );
$show_in         = $GLOBALS['BandfrontPlayer']->get_global_attr( '_bfp_show_in', 'all' );
$players_in_cart = $GLOBALS['BandfrontPlayer']->get_global_attr( '_bfp_players_in_cart', false );
$player_style    = $GLOBALS['BandfrontPlayer']->get_global_attr( '_bfp_player_layout', BFP_DEFAULT_PLAYER_LAYOUT );
$volume          = $GLOBALS['BandfrontPlayer']->get_global_attr( '_bfp_player_volume', BFP_DEFAULT_PLAYER_VOLUME );
$player_controls = $GLOBALS['BandfrontPlayer']->get_global_attr( '_bfp_player_controls', BFP_DEFAULT_PLAYER_CONTROLS );
$single_player   = $GLOBALS['BandfrontPlayer']->get_global_attr( '_bfp_single_player', false );
$secure_player   = $GLOBALS['BandfrontPlayer']->get_global_attr( '_bfp_secure_player', false );
$file_percent    = $GLOBALS['BandfrontPlayer']->get_global_attr( '_bfp_file_percent', BFP_FILE_PERCENT );
$player_title    = intval( $GLOBALS['BandfrontPlayer']->get_global_attr( '_bfp_player_title', 1 ) );
$merge_grouped   = intval( $GLOBALS['BandfrontPlayer']->get_global_attr( '_bfp_merge_in_grouped', 0 ) );
$preload         = $GLOBALS['BandfrontPlayer']->get_global_attr(
	'_bfp_preload',
	// This option is only for compatibility with versions previous to 1.0.28
					$GLOBALS['BandfrontPlayer']->get_global_attr( 'preload', 'none' )
);
$play_simultaneously = $GLOBALS['BandfrontPlayer']->get_global_attr( '_bfp_play_simultaneously', 0 );
$play_all            = $GLOBALS['BandfrontPlayer']->get_global_attr(
	'_bfp_play_all',
	// This option is only for compatibility with versions previous to 1.0.28
					$GLOBALS['BandfrontPlayer']->get_global_attr( 'play_all', 0 )
);
$loop                     = intval( $GLOBALS['BandfrontPlayer']->get_global_attr( '_bfp_loop', 0 ) );
$on_cover                 = intval( $GLOBALS['BandfrontPlayer']->get_global_attr( '_bfp_on_cover', 0 ) );
$playback_counter_column  = $GLOBALS['BandfrontPlayer']->get_global_attr( '_bfp_playback_counter_column', 1 );
$analytics_integration    = $GLOBALS['BandfrontPlayer']->get_global_attr( '_bfp_analytics_integration', 'ua' );
$analytics_property       = $GLOBALS['BandfrontPlayer']->get_global_attr( '_bfp_analytics_property', '' );
$analytics_api_secret     = $GLOBALS['BandfrontPlayer']->get_global_attr( '_bfp_analytics_api_secret', '' );
$message                  = $GLOBALS['BandfrontPlayer']->get_global_attr( '_bfp_message', '' );
$registered_only          = $GLOBALS['BandfrontPlayer']->get_global_attr( '_bfp_registered_only', 0 );
$purchased                = $GLOBALS['BandfrontPlayer']->get_global_attr( '_bfp_purchased', 0 );
$reset_purchased_interval = $GLOBALS['BandfrontPlayer']->get_global_attr( '_bfp_reset_purchased_interval', 'daily' );
$fade_out                 = $GLOBALS['BandfrontPlayer']->get_global_attr( '_bfp_fade_out', 1 );
$purchased_times_text     = $GLOBALS['BandfrontPlayer']->get_global_attr( '_bfp_purchased_times_text', '- purchased %d time(s)' );
$apply_to_all_players     = $GLOBALS['BandfrontPlayer']->get_global_attr( '_bfp_apply_to_all_players', 0 );

// Remove Google Drive settings UI from the settings page if present.
remove_all_actions( 'bfp_general_settings', 10 );

?>
<h1><?php echo "\xF0\x9F\x8C\x88"; ?> <?php esc_html_e( 'Bandfront Player - Global Settings', 'bandfront-player' ); ?></h1>
<p class="bfp-tagline">a player for the storefront theme that makes bandcamp irrelevant</p>

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
<input type="hidden" name="bfp_nonce" value="<?php echo esc_attr( wp_create_nonce( 'bfp_updating_plugin_settings' ) ); ?>" />
<table class="widefat bfp-main-table">
	<tr>
		<td>
			<table class="widefat bfp-settings-table">
				<tr>
					<td colspan="2"><h2>⚙️ <?php esc_html_e( 'General Settings', 'bandfront-player' ); ?></h2></td>
				</tr>
				<tr>
					<td colspan="2">
						<table class="widefat bfp-highlight-table">
							<tr>
								<td class="bfp-column-30"><label for="_bfp_registered_only">👤 <?php esc_html_e( 'Registered users only', 'bandfront-player' ); ?></label></td>
								<td><input aria-label="<?php esc_attr_e( 'Include the players only for registered users', 'bandfront-player' ); ?>" type="checkbox" id="_bfp_registered_only" name="_bfp_registered_only" <?php print( ( $registered_only ) ? 'CHECKED' : '' ); ?> /><br>
								<em class="bfp-em-text"><?php esc_html_e( 'Only show audio players to logged-in users', 'bandfront-player' ); ?></em></td>
							</tr>
							<tr>
								<td class="bfp-column-30">🛒 <?php esc_html_e( 'Full tracks for buyers', 'bandfront-player' ); ?></td>
								<td>
									<label><input aria-label="<?php esc_attr_e( 'For buyers, play the purchased audio files instead of the truncated files for demo', 'bandfront-player' ); ?>" type="checkbox" name="_bfp_purchased" <?php print( ( $purchased ) ? 'CHECKED' : '' ); ?> />
									<?php esc_html_e( 'Let buyers hear full tracks instead of demos', 'bandfront-player' ); ?></label><br>
									<label class="bfp-settings-label"><?php esc_html_e( 'Reset access', 'bandfront-player' ); ?>
									<select aria-label="<?php esc_attr_e( 'Reset files interval', 'bandfront-player' ); ?>" name="_bfp_reset_purchased_interval">
										<option value="daily" <?php if ( 'daily' == $reset_purchased_interval ) {
											print 'SELECTED';} ?>><?php esc_html_e( 'daily', 'bandfront-player' ); ?></option>
										<option value="never" <?php if ( 'never' == $reset_purchased_interval ) {
											print 'SELECTED';} ?>><?php esc_html_e( 'never', 'bandfront-player' ); ?></option>
									</select></label>
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td class="bfp-column-30"><label for="_bfp_fade_out">🎚️ <?php esc_html_e( 'Smooth fade out', 'bandfront-player' ); ?></label></td>
					<td><input aria-label="<?php esc_attr_e( 'Apply fade out to playing audio when possible', 'bandfront-player' ); ?>" type="checkbox" id="_bfp_fade_out" name="_bfp_fade_out" <?php print( ( $fade_out ) ? 'CHECKED' : '' ); ?> /><br>
					<em class="bfp-em-text"><?php esc_html_e( 'Gradually fade out audio when switching tracks', 'bandfront-player' ); ?></em></td>
				</tr>
				<tr>
					<td class="bfp-column-30"><label for="_bfp_purchased_times_text">📊 <?php esc_html_e( 'Purchase count text', 'bandfront-player' ); ?></label></td>
					<td><input aria-label="<?php esc_attr_e( 'Purchased times text', 'bandfront-player' ); ?>" type="text" id="_bfp_purchased_times_text" name="_bfp_purchased_times_text" value="<?php print esc_attr( $purchased_times_text ); ?>" class="bfp-input-full" /><br>
					<i class="bfp-em-text"><?php esc_html_e( 'Text shown in playlists when displaying purchase counts (use %d for the number)', 'bandfront-player' ); ?></i>
					</td>
				</tr>
				<?php
					do_action( 'bfp_general_settings' );
				?>
			</table>


			<table class="widefat bfp-player-settings bfp-settings-table">
				<tr><td colspan="2"><h2>🎵 <?php esc_html_e( 'Player Settings', 'bandfront-player' ); ?></h2></td></tr>
				<tr>
					<td class="bfp-column-30"><label for="_bfp_enable_player">🎧 <?php esc_html_e( 'Enable players on all products', 'bandfront-player' ); ?></label></td>
					<td><div class="bfp-tooltip"><span class="bfp-tooltiptext"><?php esc_html_e( 'Players will show for downloadable products with audio files, or products where you\'ve added custom audio files', 'bandfront-player' ); ?></span><input aria-label="<?php esc_attr_e( 'Enable player', 'bandfront-player' ); ?>" type="checkbox" id="_bfp_enable_player" name="_bfp_enable_player" <?php echo ( ( $enable_player ) ? 'checked' : '' ); ?> /></div></td>
				</tr>
				<tr>
					<td class="bfp-column-30">📍 <?php esc_html_e( 'Show players on', 'bandfront-player' ); ?></td>
					<td>
						<label><input aria-label="<?php esc_attr_e( 'Single entry pages', 'bandfront-player' ); ?>" type="radio" name="_bfp_show_in" value="single" <?php echo ( ( 'single' == $show_in ) ? 'checked' : '' ); ?> />
						<?php _e( 'Product pages only', 'bandfront-player' ); ?></label><br />

						<label><input aria-label="<?php esc_attr_e( 'Multiple entry pages', 'bandfront-player' ); ?>" type="radio" name="_bfp_show_in" value="multiple" <?php echo ( ( 'multiple' == $show_in ) ? 'checked' : '' ); ?> />
						<?php _e( 'Shop and archive pages only', 'bandfront-player' ); ?></label><br />

						<label><input aria-label="<?php esc_attr_e( 'Single and multiple entry pages', 'bandfront-player' ); ?>" type="radio" name="_bfp_show_in" value="all" <?php echo ( ( 'all' == $show_in ) ? 'checked' : '' ); ?> />
						<?php _e( 'All pages (shop, archives, and product pages)', 'bandfront-player' ); ?></label>
					</td>
				</tr>
				<tr>
					<td class="bfp-column-30"><label for="_bfp_players_in_cart">🛒 <?php esc_html_e( 'Show players in cart', 'bandfront-player' ); ?></label></td>
					<td>
						<input aria-label="<?php esc_attr_e( 'Include players in cart', 'bandfront-player' ); ?>" type="checkbox" id="_bfp_players_in_cart" name="_bfp_players_in_cart" <?php echo ( ( $players_in_cart ) ? 'checked' : '' ); ?> />
					</td>
				</tr>
				<tr>
					<td class="bfp-column-30"><label for="_bfp_merge_in_grouped">📦 <?php esc_html_e( 'Merge grouped products', 'bandfront-player' ); ?></label></td>
					<td><input aria-label="<?php esc_attr_e( 'Merge in grouped products', 'bandfront-player' ); ?>" type="checkbox" id="_bfp_merge_in_grouped" name="_bfp_merge_in_grouped" <?php echo ( ( $merge_grouped ) ? 'checked' : '' ); ?> /><br /><em class="bfp-em-text"><?php esc_html_e( 'Show "Add to cart" buttons and quantity fields within player rows for grouped products', 'bandfront-player' ); ?></em></td>
				</tr>
				<tr>
					<td valign="top" class="bfp-column-30">🎨 <?php esc_html_e( 'Player appearance', 'bandfront-player' ); ?></td>
					<td>
						<table class="bfp-player-skin-table">
							<tr>
								<td>
									<input aria-label="<?php esc_attr_e( 'Dark Mode', 'bandfront-player' ); ?>" id="skin1" name="_bfp_player_layout" type="radio" value="mejs-classic" <?php echo ( ( 'mejs-classic' == $player_style ) ? 'checked' : '' ); ?> />
								</td>
								<td class="bfp-player-skin-cell">
									<label for="skin1"><?php esc_html_e( 'Dark Mode', 'bandfront-player' ); ?></label>
								</td>
							</tr>
							<tr>
								<td>
									<input aria-label="<?php esc_attr_e( 'Light Mode', 'bandfront-player' ); ?>" id="skin2" name="_bfp_player_layout" type="radio" value="mejs-ted" <?php echo ( ( 'mejs-ted' == $player_style ) ? 'checked' : '' ); ?> />
								</td>
								<td class="bfp-player-skin-cell">
									<label for="skin2"><?php esc_html_e( 'Light Mode', 'bandfront-player' ); ?></label>
								</td>
							</tr>
							<tr>
								<td>
									<input aria-label="<?php esc_attr_e( 'Custom', 'bandfront-player' ); ?>" id="skin3" name="_bfp_player_layout" type="radio" value="mejs-wmp" <?php echo ( ( 'mejs-wmp' == $player_style ) ? 'checked' : '' ); ?> />
								</td>
								<td class="bfp-player-skin-cell">
									<label for="skin3"><?php esc_html_e( 'Custom', 'bandfront-player' ); ?></label>
								</td>
							</tr>
							<tr>
								<td colspan="2" class="bfp-single-player-row"><label><input aria-label="<?php esc_attr_e( 'Show a single player instead of one player per audio file.', 'bandfront-player' ); ?>" name="_bfp_single_player" type="checkbox" <?php echo ( ( $single_player ) ? 'checked' : '' ); ?> />
								<span class="bfp-single-player-label">🎭 <?php esc_html_e( 'Single player mode (one player for all tracks)', 'bandfront-player' ); ?></label>
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td class="bfp-column-30">
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
					<td class="bfp-column-30">
						<label for="_bfp_play_all">▶️ <?php esc_html_e( 'Auto-play next track', 'bandfront-player' ); ?></label>
					</td>
					<td>
						<input aria-label="<?php esc_attr_e( 'Play all', 'bandfront-player' ); ?>" type="checkbox" id="_bfp_play_all" name="_bfp_play_all" <?php if ( $play_all ) {
							echo 'CHECKED';} ?> />
					</td>
				</tr>
				<tr>
					<td class="bfp-column-30">
						<label for="_bfp_loop">🔄 <?php esc_html_e( 'Loop tracks', 'bandfront-player' ); ?></label>
					</td>
					<td>
						<input aria-label="<?php esc_attr_e( 'Loop', 'bandfront-player' ); ?>" type="checkbox" id="_bfp_loop" name="_bfp_loop" <?php if ( $loop ) {
							echo 'CHECKED';} ?> />
					</td>
				</tr>
				<tr>
					<td class="bfp-column-30">
						<label for="_bfp_play_simultaneously">🎵 <?php esc_html_e( 'Allow multiple players', 'bandfront-player' ); ?></label>
					</td>
					<td>
						<input aria-label="<?php esc_attr_e( 'Allow multiple players to play simultaneously', 'bandfront-player' ); ?>" type="checkbox" id="_bfp_play_simultaneously" name="_bfp_play_simultaneously" <?php if ( $play_simultaneously ) {
							echo 'CHECKED';} ?> /><br />
						<i class="bfp-em-text"><?php
							esc_html_e( 'Let multiple players play at the same time instead of stopping others when one starts', 'bandfront-player' );
						?></i>
					</td>
				</tr>
				<tr>
					<td><label for="_bfp_player_volume" >🔊 <?php esc_html_e( 'Default volume (0.0 to 1.0)', 'bandfront-player' ); ?></label></td>
					<td>
						<input aria-label="<?php esc_attr_e( 'Player volume', 'bandfront-player' ); ?>" type="number" id="_bfp_player_volume" name="_bfp_player_volume" min="0" max="1" step="0.01" value="<?php echo esc_attr( $volume ); ?>" />
					</td>
				</tr>
				<tr>
					<td class="bfp-column-30">🎛️ <?php esc_html_e( 'Player controls', 'bandfront-player' ); ?></td>
					<td>
						<label><input aria-label="<?php esc_attr_e( 'Play/pause button only', 'bandfront-player' ); ?>" type="radio" name="_bfp_player_controls" value="button" <?php echo ( ( 'button' == $player_controls ) ? 'checked' : '' ); ?> /> <?php esc_html_e( 'Play/pause button only', 'bandfront-player' ); ?></label><br />
						<label><input aria-label="<?php esc_attr_e( 'All controls', 'bandfront-player' ); ?>" type="radio" name="_bfp_player_controls" value="all" <?php echo ( ( 'all' == $player_controls ) ? 'checked' : '' ); ?> /> <?php esc_html_e( 'Full controls (progress bar, volume, etc.)', 'bandfront-player' ); ?></label><br />
						<label><input aria-label="<?php esc_attr_e( 'Controls depending on context', 'bandfront-player' ); ?>" type="radio" name="_bfp_player_controls" value="default" <?php echo ( ( 'default' == $player_controls ) ? 'checked' : '' ); ?> /> <?php esc_html_e( 'Smart controls (minimal on shop, full on product pages)', 'bandfront-player' ); ?></label>
						<div class="bfp-on-cover">
							<label><input aria-label="<?php esc_attr_e( 'Player on cover images', 'bandfront-player' ); ?>" type="checkbox" name="_bfp_player_on_cover" value="default" <?php
							echo ( ( ! empty( $on_cover ) && ( 'button' == $player_controls || 'default' == $player_controls ) ) ? 'checked' : '' );
							?> />
							🖼️ <?php esc_html_e( 'Show play buttons on product images', 'bandfront-player' ); ?>
							<i>
							<?php
							esc_html_e( '(Experimental feature - appearance depends on your theme)', 'bandfront-player' );
							?>
							</i></label>
						</div>
					</td>
				</tr>
				<tr>
					<td class="bfp-column-30"><label for="_bfp_player_title">🏷️ <?php esc_html_e( 'Show track titles', 'bandfront-player' ); ?></label></td>
					<td>
						<input aria-label="<?php esc_attr_e( 'Display the player title', 'bandfront-player' ); ?>" type="checkbox" id="_bfp_player_title" name="_bfp_player_title" <?php echo ( ( ! empty( $player_title ) ) ? 'checked' : '' ); ?> />
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<table class="widefat bfp-settings-table">
							<tr><td colspan="2"><h2>🔒 <?php esc_html_e( 'File Truncation', 'bandfront-player' ); ?></h2></td></tr>
							<tr>
								<td class="bfp-column-30"><label for="_bfp_secure_player">🛡️ <?php esc_html_e( 'Truncate audio files', 'bandfront-player' ); ?></label></td>
								<td><input aria-label="<?php esc_attr_e( 'Protect the file', 'bandfront-player' ); ?>" type="checkbox" id="_bfp_secure_player" name="_bfp_secure_player" <?php echo ( ( $secure_player ) ? 'checked' : '' ); ?> /><br>
								<em class="bfp-em-text"><?php esc_html_e( 'Create demo versions to prevent unauthorized downloading', 'bandfront-player' ); ?></em></td>
							</tr>
							<tr valign="top">
								<td class="bfp-column-30"><label for="_bfp_file_percent">📊 <?php esc_html_e( 'Demo length (% of original)', 'bandfront-player' ); ?></label></td>
								<td>
									<input aria-label="<?php esc_attr_e( 'Percent of audio used for protected playbacks', 'bandfront-player' ); ?>" type="number" id="_bfp_file_percent" name="_bfp_file_percent" value="<?php echo esc_attr( $file_percent ); ?>" /> % <br />
									<em class="bfp-em-text"><?php esc_html_e( 'How much of the original track to include in demos (e.g., 30% = first 30 seconds of a 100-second track)', 'bandfront-player' ); ?></em>
								</td>
							</tr>
							<tr valign="top">
								<td class="bfp-column-30">
									<label for="_bfp_message">💬 <?php esc_html_e( 'Demo notice text', 'bandfront-player' ); ?></label>
								</td>
								<td>
									<textarea aria-label="<?php esc_attr_e( 'Explaining that demos are partial versions of the original files', 'bandfront-player' ); ?>" id="_bfp_message" name="_bfp_message" class="bfp-input-full" rows="4"><?php echo esc_textarea( $message ); ?></textarea><br>
									<em class="bfp-em-text"><?php esc_html_e( 'Text shown next to players to explain these are preview versions', 'bandfront-player' ); ?></em>
								</td>
							</tr>
							<tr>
								<td colspan="2"><hr class="bfp-protection-divider" /></td>
							</tr>
							<tr>
								<td colspan="2"><i class="bfp-ffmpeg-info"><?php esc_html_e( 'Advanced: FFmpeg can create higher-quality demo files with better audio processing than the default PHP method.', 'bandfront-player' ); ?></i></td>
							</tr>
							<tr>
								<td class="bfp-column-30"><label for="_bfp_ffmpeg">⚡ <?php esc_html_e( 'Use FFmpeg for demos', 'bandfront-player' ); ?></label></td>
								<td><input aria-label="<?php esc_attr_e( 'Truncate the audio files for demo with ffmpeg', 'bandfront-player' ); ?>" type="checkbox" id="_bfp_ffmpeg" name="_bfp_ffmpeg" <?php print( ( $ffmpeg ) ? 'CHECKED' : '' ); ?> /><br>
								<em class="bfp-em-text"><?php esc_html_e( 'Requires FFmpeg to be installed on your server', 'bandfront-player' ); ?></em></td>
							</tr>
							<tr>
								<td class="bfp-column-30"><label for="_bfp_ffmpeg_path">📁 <?php esc_html_e( 'FFmpeg path', 'bandfront-player' ); ?></label></td>
								<td>
									<input aria-label="<?php esc_attr_e( 'ffmpeg path', 'bandfront-player' ); ?>" type="text" id="_bfp_ffmpeg_path" name="_bfp_ffmpeg_path" value="<?php print esc_attr( empty( $ffmpeg_path ) && ! empty( $ffmpeg_system_path ) ? $ffmpeg_system_path : $ffmpeg_path ); ?>" class="bfp-input-full" /><br />
									<i class="bfp-ffmpeg-example">Example: /usr/bin/</i>
								</td>
							</tr>
							<tr>
								<td class="bfp-column-30"><label for="_bfp_ffmpeg_watermark">🎤 <?php esc_html_e( 'Audio watermark', 'bandfront-player' ); ?></label></td>
								<td>
									<input aria-label="<?php esc_attr_e( 'Watermark audio', 'bandfront-player' ); ?>" type="text" id="_bfp_ffmpeg_watermark" name="_bfp_ffmpeg_watermark" value="<?php print esc_attr( $ffmpeg_watermark ); ?>" class="bfp-watermark-input bfp-file-url" /><input type="button" class="button-secondary bfp-select-file bfp-watermark-button" value="<?php esc_attr_e( 'Select', 'bandfront-player' ); ?>" /><br />
									<i class="bfp-em-text"><?php esc_html_e( 'Optional audio file to overlay on demos (experimental feature)', 'bandfront-player' ); ?></i>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>

<table class="widefat bfp-table-noborder">
	<tr>
		<td>
			<table class="widefat bfp-settings-table">
				<tr>
					<td><h2>📈 <?php esc_html_e( 'Analytics', 'bandfront-player' ); ?></h2></td>
				</tr>
				<tr>
					<td>
					<label><input aria-label="<?php esc_attr_e( 'Show "playback Counter" in the WooCommerce products list', 'bandfront-player' ); ?>" type="checkbox" name="_bfp_playback_counter_column" <?php print( ( $playback_counter_column ) ? 'CHECKED' : '' ); ?> />
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
						<label><input type="radio" name="_bfp_analytics_integration" value="ua" <?php print 'ua' == $analytics_integration ? 'CHECKED' : ''; ?>> <?php esc_html_e( 'Universal Analytics', 'bandfront-player' ); ?></label>
						<label class="bfp-analytics-radio-spacer"><input type="radio" name="_bfp_analytics_integration" value="g" <?php print 'g' == $analytics_integration ? 'CHECKED' : ''; ?>> <?php esc_html_e( 'Measurement Protocol (Google Analytics 4)', 'bandfront-player' ); ?></label>
					</td>
				</tr>
				<tr>
					<td>
						<div><?php esc_html_e( 'Measurement ID', 'bandfront-player' ); ?></div>
						<div><input aria-label="<?php esc_attr_e( 'Measurement id', 'bandfront-player' ); ?>" type="text" name="_bfp_analytics_property" value="<?php print esc_attr( $analytics_property ); ?>" class="bfp-analytics-input" placeholder="UA-XXXXX-Y"></div>
					</td>
				</tr>
				<tr class="bfp-analytics-g4" style="display:<?php print esc_attr( 'ua' == $analytics_integration ? 'none' : 'table-row' ); ?>;">
					<td class="bfp-input-full">
						<div><?php esc_html_e( 'API Secret', 'bandfront-player' ); ?></div>
						<div><input aria-label="<?php esc_attr_e( 'API Secret', 'bandfront-player' ); ?>" type="text" name="_bfp_analytics_api_secret" value="<?php print esc_attr( $analytics_api_secret ); ?>" class="bfp-analytics-input"></div>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
<?php 
// Cloud Storage Settings
$bfp_cloud_settings = get_option('_bfp_cloud_drive_addon', array());
$bfp_drive = isset($bfp_cloud_settings['_bfp_drive']) ? $bfp_cloud_settings['_bfp_drive'] : false;
$bfp_drive_key = isset($bfp_cloud_settings['_bfp_drive_key']) ? $bfp_cloud_settings['_bfp_drive_key'] : '';
$bfp_drive_api_key = get_option('_bfp_drive_api_key', '');
?>
<table class="widefat bfp-table-noborder">
	<tr>
		<td>
			<table class="widefat bfp-settings-table">
				<tr>
					<td>
						<h2 onclick="jQuery('.bfp-cloud-content').toggle(); jQuery('.bfp-cloud-arrow').toggleClass('bfp-cloud-arrow-open');" style="cursor: pointer;">
							☁️ <?php esc_html_e( 'Cloud Storage', 'bandfront-player' ); ?> 
							<span class="bfp-cloud-arrow">▶</span>
						</h2>
					</td>
				</tr>
				<tr class="bfp-cloud-content" style="display: none;">
					<td>
						<p class="bfp-cloud-info"><?php esc_html_e( 'Automatically upload demo files to cloud storage to save server storage and bandwidth. Files are streamed directly from the cloud.', 'bandfront-player' ); ?></p>
						
						<div class="bfp-cloud-tabs">
							<div class="bfp-cloud-tab-buttons">
								<button type="button" class="bfp-cloud-tab-btn bfp-cloud-tab-active" data-tab="google-drive">
									🗂️ <?php esc_html_e( 'Google Drive', 'bandfront-player' ); ?>
								</button>
								<button type="button" class="bfp-cloud-tab-btn" data-tab="dropbox">
									📦 <?php esc_html_e( 'Dropbox', 'bandfront-player' ); ?>
								</button>
								<button type="button" class="bfp-cloud-tab-btn" data-tab="aws-s3">
									🛡️ <?php esc_html_e( 'AWS S3', 'bandfront-player' ); ?>
								</button>
								<button type="button" class="bfp-cloud-tab-btn" data-tab="azure">
									☁️ <?php esc_html_e( 'Azure Blob', 'bandfront-player' ); ?>
								</button>
							</div>
							
							<div class="bfp-cloud-tab-content">
								<!-- Google Drive Tab -->
								<div class="bfp-cloud-tab-panel bfp-cloud-tab-panel-active" data-panel="google-drive">
									<table class="widefat">
										<tr>
											<td class="bfp-column-30"><label for="_bfp_drive"><?php esc_html_e( 'Store demo files on Google Drive', 'bandfront-player' ); ?></label></td>
											<td><input aria-label="<?php esc_attr_e( 'Store demo files on Google Drive', 'bandfront-player' ); ?>" type="checkbox" id="_bfp_drive" name="_bfp_drive" <?php print( ( $bfp_drive ) ? 'CHECKED' : '' ); ?> /></td>
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
								<div class="bfp-cloud-tab-panel" data-panel="dropbox">
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
								<div class="bfp-cloud-tab-panel" data-panel="aws-s3">
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
								<div class="bfp-cloud-tab-panel" data-panel="azure">
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
		</td>
	</tr>
</table>
<table class="widefat bfp-table-noborder">

	<tr>
		<td>
			<table class="widefat bfp-settings-table">
				<tr>
					<td colspan="2"><h2>⚙️ <?php esc_html_e( 'Audio Engine', 'bandfront-player' ); ?></h2></td>
				</tr>
				<?php do_action( 'bfp_addon_general_settings' ); ?>
			</table>
		</td>
	</tr>
</table>
<table class="widefat bfp-table-noborder">
	<tr>
		<td>
			<table class="widefat bfp-settings-table">
				<tr>
					<td><h2>🔧 <?php esc_html_e( 'Troubleshooting', 'bandfront-player' ); ?></h2></td>
				</tr>
				<tr>
					<td class="bfp-troubleshoot-item bfp-troubleshoot-mobile">
						<h3>📱 Mobile Issues</h3>
						<p>
							<?php esc_html_e( 'Players not working on iPads or iPhones?', 'bandfront-player' ); ?>
						</p>
						<label>
						<input aria-label="<?php esc_attr_e( 'On iPads and iPhones, use native controls', 'bandfront-player' ); ?>" type="checkbox" name="_bfp_ios_controls" <?php if ( $ios_controls ) {
							print 'CHECKED';} ?>/>
						<?php esc_html_e( 'Use native iOS controls for better compatibility', 'bandfront-player' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<td class="bfp-troubleshoot-item bfp-troubleshoot-performance">
						<h3>⚡ Performance Issues</h3>
						<p>
							<?php esc_html_e( 'Cache or optimizer plugins causing problems?', 'bandfront-player' ); ?>
						</p>
						<label>
						<input aria-label="<?php esc_attr_e( 'Loading players in the onload event', 'bandfront-player' ); ?>" type="checkbox" name="_bfp_onload" <?php if ( $troubleshoot_onload ) {
							print 'CHECKED';} ?>/>
						<?php esc_html_e( 'Load players after page fully loads', 'bandfront-player' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<td class="bfp-troubleshoot-item bfp-troubleshoot-files">
						<h3>📁 File Recognition</h3>
						<p>
							<?php esc_html_e( 'Files missing extensions or stored in cloud?', 'bandfront-player' ); ?>
						</p>
						<label>
						<input aria-label="<?php esc_attr_e( 'For files whose extensions cannot be determined handle them as mp3 files', 'bandfront-player' ); ?>" type="checkbox" name="_bfp_default_extension" <?php if ( $troubleshoot_default_extension ) {
							print 'CHECKED';} ?>/>
						<?php esc_html_e( 'Treat unrecognized files as MP3 audio', 'bandfront-player' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<td class="bfp-troubleshoot-item bfp-troubleshoot-gutenberg">
						<h3>🧱 Gutenberg Blocks</h3>
						<p>
							<?php esc_html_e( 'Gutenberg blocks hiding your players?', 'bandfront-player' ); ?>
						</p>
						<label>
						<input aria-label="<?php esc_attr_e( 'For the WooCommerce Gutenberg Blocks, include the main player in the products titles', 'bandfront-player' ); ?>" type="checkbox" name="_bfp_force_main_player_in_title" <?php if ( $force_main_player_in_title ) {
							print 'CHECKED';} ?>/>
						<?php esc_html_e( 'Force players to appear in product titles', 'bandfront-player' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<td class="bfp-troubleshoot-item bfp-troubleshoot-redirect">
						<h3>🔗 Redirect Issues</h3>
						<p>
							<?php esc_html_e( 'Players visible but not working?', 'bandfront-player' ); ?>
						</p>
						<label>
						<input aria-label="<?php esc_attr_e( 'Disable 302 redirection', 'bandfront-player' ); ?>" type="checkbox" name="_bfp_disable_302" <?php if ( $disable_302 ) {
							print 'CHECKED';} ?>/>
						<?php esc_html_e( 'Load files directly instead of using redirects', 'bandfront-player' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<td class="bfp-troubleshoot-item bfp-troubleshoot-cleanup">
						<h3>🗑️ File Cleanup</h3>
						<p><?php esc_html_e( 'Demo files corrupted or outdated?', 'bandfront-player' ); ?></p>
						<label>
						<input aria-label="<?php esc_attr_e( 'Delete the demo files generated previously', 'bandfront-player' ); ?>" type="checkbox" name="_bfp_delete_demos" />
						<?php esc_html_e( 'Delete old demo files (local files only)', 'bandfront-player' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<td>
						<p class="bfp-troubleshoot-protip"><b>💡 <?php esc_html_e( 'Pro Tip!', 'bandfront-player' ); ?></b> <?php esc_html_e( 'After changing troubleshooting settings, clear your website and browser caches for best results.', 'bandfront-player' ); ?></p>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
<div class="bfp-submit-wrapper"><input type="submit" value="<?php esc_attr_e( 'Save settings', 'bandfront-player' ); ?>" class="button-primary" /></div>
</form>
<script>
jQuery(window).on('load', function(){
    var $ = jQuery;
    function coverSection()
    {
        var v = $('[name="_bfp_player_controls"]:checked').val(),
            c = $('.bfp-on-cover');
        if(v == 'default' || v == 'button') c.show();
        else c.hide();
    };
    $(document).on('change', '[name="_bfp_player_controls"]', function(){
        coverSection();
    });
    $(document).on('change', '[name="_bfp_analytics_integration"]', function(){
        var v = $('[name="_bfp_analytics_integration"]:checked').val();
        $('.bfp-analytics-g4').css('display', v == 'g' ? 'table-row' : 'none');
        $('[name="_bfp_analytics_property"]').attr('placeholder', v == 'g' ? 'G-XXXXXXXX' : 'UA-XXXXX-Y');
    });
    
    // Cloud Storage Tab Functionality
    $(document).on('click', '.bfp-cloud-tab-btn', function(){
        var tab = $(this).data('tab');
        
        // Update tab buttons
        $('.bfp-cloud-tab-btn').removeClass('bfp-cloud-tab-active');
        $(this).addClass('bfp-cloud-tab-active');
        
        // Update tab panels
        $('.bfp-cloud-tab-panel').removeClass('bfp-cloud-tab-panel-active');
        $('.bfp-cloud-tab-panel[data-panel="' + tab + '"]').addClass('bfp-cloud-tab-panel-active');
    });
    
    $('[name="_bfp_analytics_integration"]:eq(0)').change();
    coverSection();
});
</script>
<style>.bfp-player-settings tr td:first-child{width:225px;}</style>