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
<p style="font-style: italic; font-size: 12px; color: #666; margin-top: -10px;">a player for the storefront theme that makes bandcamp irrelevant</p>

<div style="padding: 20px; background: linear-gradient(135deg, #9C27B0 0%, #7B1FA2 50%, #6A1B9A 100%); border-radius: 12px; margin-bottom: 25px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
	<div id="bandcamp_nuke_tips_header">
		<h2 style="margin-top:0; margin-bottom:15px; cursor:pointer; color: white; font-size: 24px; text-shadow: 1px 1px 2px rgba(0,0,0,0.3);" onclick="jQuery('#bandcamp_nuke_tips_body').toggle();">
			💥 <?php esc_html_e( 'Tips On How To Setup Bandfront [+|-]', 'bandfront-player' ); ?>
		</h2>
	</div>
	<div id="bandcamp_nuke_tips_body" style="display: none;">
		<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px; margin-top: 20px;">
			<div style="padding: 15px; background: linear-gradient(135deg, #E8F5E8 0%, #C8E6C9 100%); border-radius: 8px;">
				<h3 style="color: #2E7D32; margin-top: 0; margin-bottom: 10px;">🚀 <?php esc_html_e( 'Getting Started', 'bandfront-player' ); ?></h3>
				<p style="margin-bottom: 10px; color: #333;">
					<?php esc_html_e( 'New to Bandfront? Start here for a complete setup guide.', 'bandfront-player' ); ?>
				</p>
				<a href="#" onclick="window.open('/how-to-start', '_blank')" style="display: inline-block; padding: 8px 16px; background: #4CAF50; color: white; text-decoration: none; border-radius: 4px; font-weight: 600;">
					<?php esc_html_e( 'How To Start Guide →', 'bandfront-player' ); ?>
				</a>
			</div>
			
			<div style="padding: 15px; background: linear-gradient(135deg, #F3E5F5 0%, #E1BEE7 100%); border-radius: 8px;">
				<h3 style="color: #7B1FA2; margin-top: 0; margin-bottom: 10px;">📝 <?php esc_html_e( 'Shortcodes', 'bandfront-player' ); ?></h3>
				<p style="margin-bottom: 10px; color: #333;">
					<?php esc_html_e( 'Advanced users can embed players anywhere with shortcodes.', 'bandfront-player' ); ?>
				</p>
				<a href="#" onclick="window.open('/shortcodes', '_blank')" style="display: inline-block; padding: 8px 16px; background: #9C27B0; color: white; text-decoration: none; border-radius: 4px; font-weight: 600;">
					<?php esc_html_e( 'Shortcode Reference →', 'bandfront-player' ); ?>
				</a>
			</div>
			
			<div style="padding: 15px; background: linear-gradient(135deg, #FFF3E0 0%, #FFE0B2 100%); border-radius: 8px;">
				<h3 style="color: #E65100; margin-top: 0; margin-bottom: 10px;">🎨 <?php esc_html_e( 'Customization', 'bandfront-player' ); ?></h3>
				<p style="margin-bottom: 10px; color: #333;">
					<?php esc_html_e( 'Make your players match your brand with custom CSS and styling.', 'bandfront-player' ); ?>
				</p>
				<a href="#" onclick="window.open('/customisation', '_blank')" style="display: inline-block; padding: 8px 16px; background: #FF9800; color: white; text-decoration: none; border-radius: 4px; font-weight: 600;">
					<?php esc_html_e( 'Customization Guide →', 'bandfront-player' ); ?>
				</a>
			</div>
		</div>
		
		<div style="margin-top: 20px; padding: 15px; background: rgba(255,255,255,0.9); border-radius: 8px;">
			<p style="margin: 0; color: #1565C0; font-weight: 600; text-align: center;">
				🎯 <?php esc_html_e( 'Pro Tip: Combine all three for maximum Bandcamp replacement!', 'bandfront-player' ); ?>
			</p>
		</div>
	</div>
</div>

<form method="post" enctype="multipart/form-data">
<input type="hidden" name="bfp_nonce" value="<?php echo esc_attr( wp_create_nonce( 'bfp_updating_plugin_settings' ) ); ?>" />
<table class="widefat" style="border-left:0;border-right:0;border-bottom:0;padding-bottom:0;">
	<tr>
		<td>
			<table class="widefat" style="border:1px solid #e1e1e1;margin-bottom:20px;">
				<tr>
					<td colspan="2"><h2>⚙️ <?php esc_html_e( 'General Settings', 'bandfront-player' ); ?></h2></td>
				</tr>
				<tr>
					<td colspan="2">
						<table class="widefat" style="border:2px solid #4A90E2; border-radius: 8px; background: linear-gradient(135deg, #E3F2FD 0%, #F8F9FA 100%);">
							<tr>
								<td width="30%"><label for="_bfp_registered_only">👤 <?php esc_html_e( 'Registered users only', 'bandfront-player' ); ?></label></td>
								<td><input aria-label="<?php esc_attr_e( 'Include the players only for registered users', 'bandfront-player' ); ?>" type="checkbox" id="_bfp_registered_only" name="_bfp_registered_only" <?php print( ( $registered_only ) ? 'CHECKED' : '' ); ?> /><br>
								<em style="color: #666;"><?php esc_html_e( 'Only show audio players to logged-in users', 'bandfront-player' ); ?></em></td>
							</tr>
							<tr>
								<td width="30%">🛒 <?php esc_html_e( 'Full tracks for buyers', 'bandfront-player' ); ?></td>
								<td>
									<label><input aria-label="<?php esc_attr_e( 'For buyers, play the purchased audio files instead of the truncated files for demo', 'bandfront-player' ); ?>" type="checkbox" name="_bfp_purchased" <?php print( ( $purchased ) ? 'CHECKED' : '' ); ?> />
									<?php esc_html_e( 'Let buyers hear full tracks instead of demos', 'bandfront-player' ); ?></label><br>
									<label style="margin-left: 20px;"><?php esc_html_e( 'Reset access', 'bandfront-player' ); ?>
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
					<td width="30%"><label for="_bfp_fade_out">🎚️ <?php esc_html_e( 'Smooth fade out', 'bandfront-player' ); ?></label></td>
					<td><input aria-label="<?php esc_attr_e( 'Apply fade out to playing audio when possible', 'bandfront-player' ); ?>" type="checkbox" id="_bfp_fade_out" name="_bfp_fade_out" <?php print( ( $fade_out ) ? 'CHECKED' : '' ); ?> /><br>
					<em style="color: #666;"><?php esc_html_e( 'Gradually fade out audio when switching tracks', 'bandfront-player' ); ?></em></td>
				</tr>
				<tr>
					<td width="30%"><label for="_bfp_purchased_times_text">📊 <?php esc_html_e( 'Purchase count text', 'bandfront-player' ); ?></label></td>
					<td><input aria-label="<?php esc_attr_e( 'Purchased times text', 'bandfront-player' ); ?>" type="text" id="_bfp_purchased_times_text" name="_bfp_purchased_times_text" value="<?php print esc_attr( $purchased_times_text ); ?>" style="width:100%;" /><br>
					<i style="color: #666;"><?php esc_html_e( 'Text shown in playlists when displaying purchase counts (use %d for the number)', 'bandfront-player' ); ?></i>
					</td>
				</tr>
				<?php
					do_action( 'bfp_general_settings' );
				?>
			</table>


			<table class="widefat bfp-player-settings" style="border:1px solid #e1e1e1;">
				<tr><td colspan="2"><h2>🎵 <?php esc_html_e( 'Player Settings', 'bandfront-player' ); ?></h2></td></tr>
				<tr>
					<td width="30%"><label for="_bfp_enable_player">🎧 <?php esc_html_e( 'Enable players on all products', 'bandfront-player' ); ?></label></td>
					<td><div class="bfp-tooltip"><span class="bfp-tooltiptext"><?php esc_html_e( 'Players will show for downloadable products with audio files, or products where you\'ve added custom audio files', 'bandfront-player' ); ?></span><input aria-label="<?php esc_attr_e( 'Enable player', 'bandfront-player' ); ?>" type="checkbox" id="_bfp_enable_player" name="_bfp_enable_player" <?php echo ( ( $enable_player ) ? 'checked' : '' ); ?> /></div></td>
				</tr>
				<tr>
					<td width="30%">📍 <?php esc_html_e( 'Show players on', 'bandfront-player' ); ?></td>
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
					<td width="30%"><label for="_bfp_players_in_cart">🛒 <?php esc_html_e( 'Show players in cart', 'bandfront-player' ); ?></label></td>
					<td>
						<input aria-label="<?php esc_attr_e( 'Include players in cart', 'bandfront-player' ); ?>" type="checkbox" id="_bfp_players_in_cart" name="_bfp_players_in_cart" <?php echo ( ( $players_in_cart ) ? 'checked' : '' ); ?> />
					</td>
				</tr>
				<tr>
					<td width="30%"><label for="_bfp_merge_in_grouped">📦 <?php esc_html_e( 'Merge grouped products', 'bandfront-player' ); ?></label></td>
					<td><input aria-label="<?php esc_attr_e( 'Merge in grouped products', 'bandfront-player' ); ?>" type="checkbox" id="_bfp_merge_in_grouped" name="_bfp_merge_in_grouped" <?php echo ( ( $merge_grouped ) ? 'checked' : '' ); ?> /><br /><em style="color: #666;"><?php esc_html_e( 'Show "Add to cart" buttons and quantity fields within player rows for grouped products', 'bandfront-player' ); ?></em></td>
				</tr>
				<tr>
					<td valign="top" width="30%">🎨 <?php esc_html_e( 'Player appearance', 'bandfront-player' ); ?></td>
					<td>
						<table>
							<tr>
								<td><input aria-label="<?php esc_attr_e( 'Skin 1', 'bandfront-player' ); ?>" id="skin1" name="_bfp_player_layout" type="radio" value="mejs-classic" <?php echo ( ( 'mejs-classic' == $player_style ) ? 'checked' : '' ); ?> /></td>
								<td style="width:100%;padding-left:20px;"><label for="skin1"><img alt="<?php esc_attr_e( 'Skin 1', 'bandfront-player' ); ?>" src="<?php print esc_url( BFP_PLUGIN_URL ); ?>/views/assets/skin1.png" /></label></td>
							</tr>

							<tr>
								<td><input aria-label="<?php esc_attr_e( 'Skin 2', 'bandfront-player' ); ?>" id="skin2" name="_bfp_player_layout" type="radio" value="mejs-ted" <?php echo ( ( 'mejs-ted' == $player_style ) ? 'checked' : '' ); ?> /></td>
								<td style="width:100%;padding-left:20px;"><label for="skin2"><img alt="<?php esc_attr_e( 'Skin 2', 'bandfront-player' ); ?>" src="<?php print esc_url( BFP_PLUGIN_URL ); ?>/views/assets/skin2.png" /></label></td>
							</tr>

							<tr>
								<td><input aria-label="<?php esc_attr_e( 'Skin 3', 'bandfront-player' ); ?>" id="skin3" name="_bfp_player_layout" type="radio" value="mejs-wmp" <?php echo ( ( 'mejs-wmp' == $player_style ) ? 'checked' : '' ); ?> /></td>
								<td style="width:100%;padding-left:20px;"><label for="skin3"><img alt="<?php esc_attr_e( 'Skin 3', 'bandfront-player' ); ?>" src="<?php print esc_url( BFP_PLUGIN_URL ); ?>/views/assets/skin3.png" /></label></td>
							</tr>

							<tr>
								<td colspan="2" style="border-top: 1px solid #9C27B0;border-bottom: 1px solid #9C27B0; background: linear-gradient(135deg, #F3E5F5 0%, #E1BEE7 100%); padding: 10px;"><label><input aria-label="<?php esc_attr_e( 'Show a single player instead of one player per audio file.', 'bandfront-player' ); ?>" name="_bfp_single_player" type="checkbox" <?php echo ( ( $single_player ) ? 'checked' : '' ); ?> />
								<span style="padding-left:20px;">🎭 <?php esc_html_e( 'Single player mode (one player for all tracks)', 'bandfront-player' ); ?></label>
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td width="30%">
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
					<td width="30%">
						<label for="_bfp_play_all">▶️ <?php esc_html_e( 'Auto-play next track', 'bandfront-player' ); ?></label>
					</td>
					<td>
						<input aria-label="<?php esc_attr_e( 'Play all', 'bandfront-player' ); ?>" type="checkbox" id="_bfp_play_all" name="_bfp_play_all" <?php if ( $play_all ) {
							echo 'CHECKED';} ?> />
					</td>
				</tr>
				<tr>
					<td width="30%">
						<label for="_bfp_loop">🔄 <?php esc_html_e( 'Loop tracks', 'bandfront-player' ); ?></label>
					</td>
					<td>
						<input aria-label="<?php esc_attr_e( 'Loop', 'bandfront-player' ); ?>" type="checkbox" id="_bfp_loop" name="_bfp_loop" <?php if ( $loop ) {
							echo 'CHECKED';} ?> />
					</td>
				</tr>
				<tr>
					<td width="30%">
						<label for="_bfp_play_simultaneously">🎵 <?php esc_html_e( 'Allow multiple players', 'bandfront-player' ); ?></label>
					</td>
					<td>
						<input aria-label="<?php esc_attr_e( 'Allow multiple players to play simultaneously', 'bandfront-player' ); ?>" type="checkbox" id="_bfp_play_simultaneously" name="_bfp_play_simultaneously" <?php if ( $play_simultaneously ) {
							echo 'CHECKED';} ?> /><br />
						<i style="color: #666;"><?php
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
					<td width="30%">🎛️ <?php esc_html_e( 'Player controls', 'bandfront-player' ); ?></td>
					<td>
						<label><input aria-label="<?php esc_attr_e( 'Play/pause button only', 'bandfront-player' ); ?>" type="radio" name="_bfp_player_controls" value="button" <?php echo ( ( 'button' == $player_controls ) ? 'checked' : '' ); ?> /> <?php esc_html_e( 'Play/pause button only', 'bandfront-player' ); ?></label><br />
						<label><input aria-label="<?php esc_attr_e( 'All controls', 'bandfront-player' ); ?>" type="radio" name="_bfp_player_controls" value="all" <?php echo ( ( 'all' == $player_controls ) ? 'checked' : '' ); ?> /> <?php esc_html_e( 'Full controls (progress bar, volume, etc.)', 'bandfront-player' ); ?></label><br />
						<label><input aria-label="<?php esc_attr_e( 'Controls depending on context', 'bandfront-player' ); ?>" type="radio" name="_bfp_player_controls" value="default" <?php echo ( ( 'default' == $player_controls ) ? 'checked' : '' ); ?> /> <?php esc_html_e( 'Smart controls (minimal on shop, full on product pages)', 'bandfront-player' ); ?></label>
						<div class="bfp-on-cover" style="margin-top:10px; padding: 8px; background: linear-gradient(135deg, #E8F5E8 0%, #C8E6C9 100%); border-radius: 4px; border-left: 4px solid #4CAF50;">
							<label><input aria-label="<?php esc_attr_e( 'Player on cover images', 'bandfront-player' ); ?>" type="checkbox" name="_bfp_player_on_cover" value="default" <?php
							echo ( ( ! empty( $on_cover ) && ( 'button' == $player_controls || 'default' == $player_controls ) ) ? 'checked' : '' );
							?> />
							🖼️ <?php esc_html_e( 'Show play buttons on product images', 'bandfront-player' ); ?>
							<i style="color: #666; display: block; margin-top: 4px;">
							<?php
							esc_html_e( '(Experimental feature - appearance depends on your theme)', 'bandfront-player' );
							?>
							</i></label>
						</div>
					</td>
				</tr>
				<tr>
					<td width="30%"><label for="_bfp_player_title">🏷️ <?php esc_html_e( 'Show track titles', 'bandfront-player' ); ?></label></td>
					<td>
						<input aria-label="<?php esc_attr_e( 'Display the player title', 'bandfront-player' ); ?>" type="checkbox" id="_bfp_player_title" name="_bfp_player_title" <?php echo ( ( ! empty( $player_title ) ) ? 'checked' : '' ); ?> />
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<table class="widefat" style="border:1px solid #e1e1e1;">
							<tr><td colspan="2"><h2>🔒 <?php esc_html_e( 'File Protection', 'bandfront-player' ); ?></h2></td></tr>
							<tr>
								<td width="30%"><label for="_bfp_secure_player">🛡️ <?php esc_html_e( 'Protect audio files', 'bandfront-player' ); ?></label></td>
								<td><input aria-label="<?php esc_attr_e( 'Protect the file', 'bandfront-player' ); ?>" type="checkbox" id="_bfp_secure_player" name="_bfp_secure_player" <?php echo ( ( $secure_player ) ? 'checked' : '' ); ?> /><br>
								<em style="color: #666;"><?php esc_html_e( 'Create demo versions to prevent unauthorized downloading', 'bandfront-player' ); ?></em></td>
							</tr>
							<tr valign="top">
								<td width="30%"><label for="_bfp_file_percent">📊 <?php esc_html_e( 'Demo length (% of original)', 'bandfront-player' ); ?></label></td>
								<td>
									<input aria-label="<?php esc_attr_e( 'Percent of audio used for protected playbacks', 'bandfront-player' ); ?>" type="number" id="_bfp_file_percent" name="_bfp_file_percent" value="<?php echo esc_attr( $file_percent ); ?>" /> % <br />
									<em style="color: #666;"><?php esc_html_e( 'How much of the original track to include in demos (e.g., 30% = first 30 seconds of a 100-second track)', 'bandfront-player' ); ?></em>
								</td>
							</tr>
							<tr valign="top">
								<td width="30%">
									<label for="_bfp_message">💬 <?php esc_html_e( 'Demo notice text', 'bandfront-player' ); ?></label>
								</td>
								<td>
									<textarea aria-label="<?php esc_attr_e( 'Explaining that demos are partial versions of the original files', 'bandfront-player' ); ?>" id="_bfp_message" name="_bfp_message" style="width:100%;" rows="4"><?php echo esc_textarea( $message ); ?></textarea><br>
									<em style="color: #666;"><?php esc_html_e( 'Text shown next to players to explain these are preview versions', 'bandfront-player' ); ?></em>
								</td>
							</tr>
							<tr>
								<td colspan="2"><hr style="border: 1px solid #ddd; margin: 15px 0;" /></td>
							</tr>
							<tr>
								<td colspan="2"><i style="color: #666;"><?php esc_html_e( 'Advanced: FFmpeg can create higher-quality demo files with better audio processing than the default PHP method.', 'bandfront-player' ); ?></i></td>
							</tr>
							<tr>
								<td width="30%"><label for="_bfp_ffmpeg">⚡ <?php esc_html_e( 'Use FFmpeg for demos', 'bandfront-player' ); ?></label></td>
								<td><input aria-label="<?php esc_attr_e( 'Truncate the audio files for demo with ffmpeg', 'bandfront-player' ); ?>" type="checkbox" id="_bfp_ffmpeg" name="_bfp_ffmpeg" <?php print( ( $ffmpeg ) ? 'CHECKED' : '' ); ?> /><br>
								<em style="color: #666;"><?php esc_html_e( 'Requires FFmpeg to be installed on your server', 'bandfront-player' ); ?></em></td>
							</tr>
							<tr>
								<td width="30%"><label for="_bfp_ffmpeg_path">📁 <?php esc_html_e( 'FFmpeg path', 'bandfront-player' ); ?></label></td>
								<td>
									<input aria-label="<?php esc_attr_e( 'ffmpeg path', 'bandfront-player' ); ?>" type="text" id="_bfp_ffmpeg_path" name="_bfp_ffmpeg_path" value="<?php print esc_attr( empty( $ffmpeg_path ) && ! empty( $ffmpeg_system_path ) ? $ffmpeg_system_path : $ffmpeg_path ); ?>" style="width:100%;" /><br />
									<i style="color: #666;">Example: /usr/bin/</i>
								</td>
							</tr>
							<tr>
								<td width="30%"><label for="_bfp_ffmpeg_watermark">🎤 <?php esc_html_e( 'Audio watermark', 'bandfront-player' ); ?></label></td>
								<td>
									<input aria-label="<?php esc_attr_e( 'Watermark audio', 'bandfront-player' ); ?>" type="text" id="_bfp_ffmpeg_watermark" name="_bfp_ffmpeg_watermark" value="<?php print esc_attr( $ffmpeg_watermark ); ?>" style="width:calc( 100% - 60px ) !important;" class="bfp-file-url" /><input type="button" class="button-secondary bfp-select-file" value="<?php esc_attr_e( 'Select', 'bandfront-player' ); ?>" style="float:right;" /><br />
									<i style="color: #666;"><?php esc_html_e( 'Optional audio file to overlay on demos (experimental feature)', 'bandfront-player' ); ?></i>
								</td>
							</tr>
						</table>

						<table class="widefat" style="border:1px solid #e1e1e1;margin-top:10px;">
							<tr>
								<td>
									<div><h2>🎯 <?php esc_html_e( 'Scope', 'bandfront-player' ); ?></h2></div>
									<div><label><div class="bfp-tooltip"><span class="bfp-tooltiptext"><?php esc_html_e( 'Apply these settings to all products, even those with custom player settings', 'bandfront-player' ); ?></span><input aria-label="<?php esc_attr_e( 'Apply the previous settings to all products', 'bandfront-player' ); ?>" type="checkbox" name="_bfp_apply_to_all_players" <?php print $apply_to_all_players == 1 ? 'CHECKED' : ''; ?> /></div> <?php esc_html_e( 'Override individual product settings with these global settings', 'bandfront-player' ); ?></label></div>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>

<table class="widefat" style="border:0;">
	<tr>
		<td>
			<table class="widefat" style="border:1px solid #e1e1e1;">
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
						<p style="color: #666;"><?php esc_html_e( 'Connect with Google Analytics to track when songs are played. Events include the audio file URL and product ID.', 'bandfront-player' ); ?></p>
						<p style="border:1px solid #4A90E2;margin-bottom:10px;padding:5px;background: linear-gradient(135deg, #E3F2FD 0%, #F8F9FA 100%); border-radius: 4px;"><b>📝 <?php esc_html_e( 'Note', 'bandfront-player' ); ?></b>: <?php esc_html_e( 'If preload is set to "Metadata" or "Auto", events are tracked when files load, not just when they play.', 'bandfront-player' ); ?></p>
					</td>
				</tr>
				<tr>
					<td>
						<label><input type="radio" name="_bfp_analytics_integration" value="ua" <?php print 'ua' == $analytics_integration ? 'CHECKED' : ''; ?>> <?php esc_html_e( 'Universal Analytics', 'bandfront-player' ); ?></label>
						<label style="margin-left:30px;"><input type="radio" name="_bfp_analytics_integration" value="g" <?php print 'g' == $analytics_integration ? 'CHECKED' : ''; ?>> <?php esc_html_e( 'Measurement Protocol (Google Analytics 4)', 'bandfront-player' ); ?></label>
					</td>
				</tr>
				<tr>
					<td>
						<div><?php esc_html_e( 'Measurement ID', 'bandfront-player' ); ?></div>
						<div><input aria-label="<?php esc_attr_e( 'Measurement id', 'bandfront-player' ); ?>" type="text" name="_bfp_analytics_property" value="<?php print esc_attr( $analytics_property ); ?>" style="width:100%;" placeholder="UA-XXXXX-Y"></div>
					</td>
				</tr>
				<tr class="bfp-analytics-g4" style="display:<?php print esc_attr( 'ua' == $analytics_integration ? 'none' : 'table-row' ); ?>;">
					<td style="width:100%;">
						<div><?php esc_html_e( 'API Secret', 'bandfront-player' ); ?></div>
						<div><input aria-label="<?php esc_attr_e( 'API Secret', 'bandfront-player' ); ?>" type="text" name="_bfp_analytics_api_secret" value="<?php print esc_attr( $analytics_api_secret ); ?>" style="width:100%;"></div>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
<table class="widefat" style="border:0;">
	<tr>
		<td>
			<table class="widefat" style="border:1px solid #e1e1e1;">
				<tr>
					<td colspan="2"><h2>🧩 <?php esc_html_e( 'Add-ons', 'bandfront-player' ); ?></h2></td>
				</tr>
				<?php do_action( 'bfp_addon_general_settings' ); ?>
			</table>
		</td>
	</tr>
</table>
<table class="widefat" style="border:0;">
	<tr>
		<td>
			<table class="widefat" style="border:1px solid #e1e1e1;margin-bottom:20px;">
				<tr>
					<td><h2>🔧 <?php esc_html_e( 'Troubleshooting', 'bandfront-player' ); ?></h2></td>
				</tr>
				<tr>
					<td style="padding: 15px; background: linear-gradient(135deg, #FFF3E0 0%, #FFE0B2 100%); border-radius: 8px; margin-bottom: 15px;">
						<h3 style="color: #E65100; margin-top: 0;">📱 Mobile Issues</h3>
						<p style="font-weight:600; color: #333;">
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
					<td style="padding: 15px; background: linear-gradient(135deg, #E8F5E8 0%, #C8E6C9 100%); border-radius: 8px; margin-bottom: 15px;">
						<h3 style="color: #2E7D32; margin-top: 0;">⚡ Performance Issues</h3>
						<p style="font-weight:600; color: #333;">
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
					<td style="padding: 15px; background: linear-gradient(135deg, #F3E5F5 0%, #E1BEE7 100%); border-radius: 8px; margin-bottom: 15px;">
						<h3 style="color: #7B1FA2; margin-top: 0;">📁 File Recognition</h3>
						<p style="font-weight:600; color: #333;">
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
					<td style="padding: 15px; background: linear-gradient(135deg, #E3F2FD 0%, #BBDEFB 100%); border-radius: 8px; margin-bottom: 15px;">
						<h3 style="color: #1565C0; margin-top: 0;">🧱 Gutenberg Blocks</h3>
						<p style="font-weight:600; color: #333;">
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
					<td style="padding: 15px; background: linear-gradient(135deg, #FFF8E1 0%, #FFECB3 100%); border-radius: 8px; margin-bottom: 15px;">
						<h3 style="color: #F57F17; margin-top: 0;">🔗 Redirect Issues</h3>
						<p style="font-weight:600; color: #333;">
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
					<td style="padding: 15px; background: linear-gradient(135deg, #FFEBEE 0%, #FFCDD2 100%); border-radius: 8px; margin-bottom: 15px;">
						<h3 style="color: #C62828; margin-top: 0;">🗑️ File Cleanup</h3>
						<p style="font-weight:600; color: #333;"><?php esc_html_e( 'Demo files corrupted or outdated?', 'bandfront-player' ); ?></p>
						<label>
						<input aria-label="<?php esc_attr_e( 'Delete the demo files generated previously', 'bandfront-player' ); ?>" type="checkbox" name="_bfp_delete_demos" />
						<?php esc_html_e( 'Delete old demo files (local files only)', 'bandfront-player' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<td>
						<p style="border:1px solid #4CAF50;margin-bottom:10px;padding:10px;background: linear-gradient(135deg, #E8F5E8 0%, #C8E6C9 100%); border-radius: 8px;"><b>💡 <?php esc_html_e( 'Pro Tip!', 'bandfront-player' ); ?></b> <?php esc_html_e( 'After changing troubleshooting settings, clear your website and browser caches for best results.', 'bandfront-player' ); ?></p>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
<div style="margin-top:20px;"><input type="submit" value="<?php esc_attr_e( 'Save settings', 'bandfront-player' ); ?>" class="button-primary" /></div>
</form>
<script>jQuery(window).on('load', function(){
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
	$('[name="_bfp_analytics_integration"]:eq(0)').change();
	coverSection();
});</script>
<style>.bfp-player-settings tr td:first-child{width:225px;}</style>
