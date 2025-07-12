<?php
if ( ! defined( 'WCMP_PLUGIN_URL' ) ) {
	echo 'Direct access not allowed.';
	exit; }

// include resources
wp_enqueue_style( 'wcmp-admin-style', plugin_dir_url( __FILE__ ) . '../css/style.admin.css', array(), '5.0.181' );
wp_enqueue_media();
wp_enqueue_script( 'wcmp-admin-js', plugin_dir_url( __FILE__ ) . '../js/admin.js', array(), '5.0.181' );
$wcmp_js = array(
	'File Name'         => __( 'File Name', 'music-player-for-woocommerce' ),
	'Choose file'       => __( 'Choose file', 'music-player-for-woocommerce' ),
	'Delete'            => __( 'Delete', 'music-player-for-woocommerce' ),
	'Select audio file' => __( 'Select audio file', 'music-player-for-woocommerce' ),
	'Select Item'       => __( 'Select Item', 'music-player-for-woocommerce' ),
);
wp_localize_script( 'wcmp-admin-js', 'wcmp', $wcmp_js );

$ffmpeg             = $GLOBALS['WooCommerceMusicPlayer']->get_global_attr( '_wcmp_ffmpeg', false );
$ffmpeg_path        = $GLOBALS['WooCommerceMusicPlayer']->get_global_attr( '_wcmp_ffmpeg_path', '' );
$ffmpeg_system_path = defined( 'PHP_OS' ) && strtolower( PHP_OS ) == 'linux' && function_exists( 'shell_exec' ) ? @shell_exec( 'which ffmpeg' ) : '';
$ffmpeg_watermark   = $GLOBALS['WooCommerceMusicPlayer']->get_global_attr( '_wcmp_ffmpeg_watermark', '' );

$troubleshoot_default_extension = $GLOBALS['WooCommerceMusicPlayer']->get_global_attr( '_wcmp_default_extension', false );
$force_main_player_in_title     = $GLOBALS['WooCommerceMusicPlayer']->get_global_attr( '_wcmp_force_main_player_in_title', 1 );
$ios_controls                   = $GLOBALS['WooCommerceMusicPlayer']->get_global_attr( '_wcmp_ios_controls', false );
$troubleshoot_onload            = $GLOBALS['WooCommerceMusicPlayer']->get_global_attr( '_wcmp_onload', false );
$include_main_player_hook       = trim( $GLOBALS['WooCommerceMusicPlayer']->get_global_attr( '_wcmp_main_player_hook', '' ) );
$main_player_hook_title         = trim( $GLOBALS['WooCommerceMusicPlayer']->get_global_attr( '_wcmp_main_player_hook_title', 1 ) );
$disable_302         			= trim( $GLOBALS['WooCommerceMusicPlayer']->get_global_attr( '_wcmp_disable_302', 0 ) );
$include_all_players_hook       = trim( $GLOBALS['WooCommerceMusicPlayer']->get_global_attr( '_wcmp_all_players_hook', '' ) );

$enable_player   = $GLOBALS['WooCommerceMusicPlayer']->get_global_attr( '_wcmp_enable_player', false );
$show_in         = $GLOBALS['WooCommerceMusicPlayer']->get_global_attr( '_wcmp_show_in', 'all' );
$players_in_cart = $GLOBALS['WooCommerceMusicPlayer']->get_global_attr( '_wcmp_players_in_cart', false );
$player_style    = $GLOBALS['WooCommerceMusicPlayer']->get_global_attr( '_wcmp_player_layout', WCMP_DEFAULT_PLAYER_LAYOUT );
$volume          = $GLOBALS['WooCommerceMusicPlayer']->get_global_attr( '_wcmp_player_volume', WCMP_DEFAULT_PLAYER_VOLUME );
$player_controls = $GLOBALS['WooCommerceMusicPlayer']->get_global_attr( '_wcmp_player_controls', WCMP_DEFAULT_PLAYER_CONTROLS );
$single_player   = $GLOBALS['WooCommerceMusicPlayer']->get_global_attr( '_wcmp_single_player', false );
$secure_player   = $GLOBALS['WooCommerceMusicPlayer']->get_global_attr( '_wcmp_secure_player', false );
$file_percent    = $GLOBALS['WooCommerceMusicPlayer']->get_global_attr( '_wcmp_file_percent', WCMP_FILE_PERCENT );
$player_title    = intval( $GLOBALS['WooCommerceMusicPlayer']->get_global_attr( '_wcmp_player_title', 1 ) );
$merge_grouped   = intval( $GLOBALS['WooCommerceMusicPlayer']->get_global_attr( '_wcmp_merge_in_grouped', 0 ) );
$preload         = $GLOBALS['WooCommerceMusicPlayer']->get_global_attr(
	'_wcmp_preload',
	// This option is only for compatibility with versions previous to 1.0.28
					$GLOBALS['WooCommerceMusicPlayer']->get_global_attr( 'preload', 'none' )
);
$play_simultaneously = $GLOBALS['WooCommerceMusicPlayer']->get_global_attr( '_wcmp_play_simultaneously', 0 );
$play_all            = $GLOBALS['WooCommerceMusicPlayer']->get_global_attr(
	'_wcmp_play_all',
	// This option is only for compatibility with versions previous to 1.0.28
					$GLOBALS['WooCommerceMusicPlayer']->get_global_attr( 'play_all', 0 )
);
$loop                     = intval( $GLOBALS['WooCommerceMusicPlayer']->get_global_attr( '_wcmp_loop', 0 ) );
$on_cover                 = intval( $GLOBALS['WooCommerceMusicPlayer']->get_global_attr( '_wcmp_on_cover', 0 ) );
$playback_counter_column  = $GLOBALS['WooCommerceMusicPlayer']->get_global_attr( '_wcmp_playback_counter_column', 1 );
$analytics_integration    = $GLOBALS['WooCommerceMusicPlayer']->get_global_attr( '_wcmp_analytics_integration', 'ua' );
$analytics_property       = $GLOBALS['WooCommerceMusicPlayer']->get_global_attr( '_wcmp_analytics_property', '' );
$analytics_api_secret     = $GLOBALS['WooCommerceMusicPlayer']->get_global_attr( '_wcmp_analytics_api_secret', '' );
$message                  = $GLOBALS['WooCommerceMusicPlayer']->get_global_attr( '_wcmp_message', '' );
$registered_only          = $GLOBALS['WooCommerceMusicPlayer']->get_global_attr( '_wcmp_registered_only', 0 );
$purchased                = $GLOBALS['WooCommerceMusicPlayer']->get_global_attr( '_wcmp_purchased', 0 );
$reset_purchased_interval = $GLOBALS['WooCommerceMusicPlayer']->get_global_attr( '_wcmp_reset_purchased_interval', 'daily' );
$fade_out                 = $GLOBALS['WooCommerceMusicPlayer']->get_global_attr( '_wcmp_fade_out', 1 );
$purchased_times_text     = $GLOBALS['WooCommerceMusicPlayer']->get_global_attr( '_wcmp_purchased_times_text', '- purchased %d time(s)' );
$apply_to_all_players     = $GLOBALS['WooCommerceMusicPlayer']->get_global_attr( '_wcmp_apply_to_all_players', 0 );
?>
<h1><?php esc_html_e( 'Music Player for WooCommerce - Global Settings', 'music-player-for-woocommerce' ); ?></h1>
<form method="post">
	<div class="postbox">
		<h2 class="hndle" style="padding:5px;"><?php esc_html_e( 'Registering of Plugin', 'music-player-for-woocommerce' ); ?></h2>
		<div class="inside">
			<label><?php esc_html_e( 'Enter the email address of buyer', 'music-player-for-woocommerce' ); ?>:</label>
			<?php
				do_action( 'wcmp_register_user' );
			?>
			<input type="submit" value="<?php esc_attr_e( 'Register', 'music-player-for-woocommerce' ); ?>" class="button-primary" />
		</div>
	</div>
</form>
<form method="post" enctype="multipart/form-data">
<input type="hidden" name="wcmp_nonce" value="<?php echo esc_attr( wp_create_nonce( 'wcmp_updating_plugin_settings' ) ); ?>" />
<table class="widefat" style="border-left:0;border-right:0;border-bottom:0;padding-bottom:0;">
	<tr>
		<td>
			<div style="border:1px solid #E6DB55;margin-bottom:10px;padding:5px;background-color: #FFFFE0;">
			<?php
			_e( '<p>The player uses the audio files associated to the product. If you want protecting the audio files for selling, tick the checkbox: <b>"Protect the file"</b>, in whose case the plugin will create a truncated version of the audio files for selling to be used for demo. The size of audio files for demo is based on the number entered through the attribute: <b>"Percent of audio used for protected playbacks"</b>.</p><p><b>Protecting the files prevents that malicious users can access to the original audio files without pay for them.</b></p>', 'music-player-for-woocommerce' ); // phpcs:ignore WordPress.Security.EscapeOutput
			?>
			</div>
		</td>
	</tr>
	<tr>
		<td>
			<table class="widefat" style="border:1px solid #e1e1e1;margin-bottom:20px;">
				<tr>
					<td colspan="2"><h2><?php esc_html_e( 'General Settings', 'music-player-for-woocommerce' ); ?></h2></td>
				</tr>
				<tr>
					<td colspan="2">
						<table class="widefat" style="border:2px solid #E6DB55;">
							<tr>
								<td width="30%"><label for="_wcmp_registered_only"><?php esc_html_e( 'Include the players only for registered users', 'music-player-for-woocommerce' ); ?></label></td>
								<td><input aria-label="<?php esc_attr_e( 'Include the players only for registered users', 'music-player-for-woocommerce' ); ?>" type="checkbox" id="_wcmp_registered_only" name="_wcmp_registered_only" <?php print( ( $registered_only ) ? 'CHECKED' : '' ); ?> /></td>
							</tr>
							<tr>
								<td width="30%"><?php esc_html_e( 'For buyers, play the purchased audio files instead of the truncated files for demo', 'music-player-for-woocommerce' ); ?></td>
								<td>
									<label><input aria-label="<?php esc_attr_e( 'For buyers, play the purchased audio files instead of the truncated files for demo', 'music-player-for-woocommerce' ); ?>" type="checkbox" name="_wcmp_purchased" <?php print( ( $purchased ) ? 'CHECKED' : '' ); ?> />
									<?php esc_html_e( 'Reset the files', 'music-player-for-woocommerce' ); ?></label>
									<select aria-label="<?php esc_attr_e( 'Reset files interval', 'music-player-for-woocommerce' ); ?>" name="_wcmp_reset_purchased_interval">
										<option value="daily" <?php if ( 'daily' == $reset_purchased_interval ) {
											print 'SELECTED';} ?>><?php esc_html_e( 'daily', 'music-player-for-woocommerce' ); ?></option>
										<option value="never" <?php if ( 'never' == $reset_purchased_interval ) {
											print 'SELECTED';} ?>><?php esc_html_e( 'never', 'music-player-for-woocommerce' ); ?></option>
									</select>
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td width="30%"><label for="_wcmp_fade_out"><?php esc_html_e( 'Apply fade out to playing audio when possible', 'music-player-for-woocommerce' ); ?></label></td>
					<td><input aria-label="<?php esc_attr_e( 'Apply fade out to playing audio when possible', 'music-player-for-woocommerce' ); ?>" type="checkbox" id="_wcmp_fade_out" name="_wcmp_fade_out" <?php print( ( $fade_out ) ? 'CHECKED' : '' ); ?> /></td>
				</tr>
				<tr>
					<td width="30%"><label for="_wcmp_purchased_times_text"><?php esc_html_e( 'Text for product purchased times in playlists', 'music-player-for-woocommerce' ); ?></label></td>
					<td><input aria-label="<?php esc_attr_e( 'Purchased times text', 'music-player-for-woocommerce' ); ?>" type="text" id="_wcmp_purchased_times_text" name="_wcmp_purchased_times_text" value="<?php print esc_attr( $purchased_times_text ); ?>" style="width:100%;" /><br>
					<i><?php esc_html_e( 'Texts to display when the playlist shortcode includes the purchased_times attribute.<br>Ex.', 'music-player-for-woocommerce' ); ?>[wcmp-playlist products_ids="*" controls="track" purchased_times="1"]</i>
					</td>
				</tr>
				<?php
					do_action( 'wcmp_general_settings' );
				?>
			</table>
			<table class="widefat" style="border:1px solid #e1e1e1;margin-bottom:20px;">
				<tr>
					<td><h2><?php esc_html_e( 'Troubleshoot Area', 'music-player-for-woocommerce' ); ?></h2></td>
				</tr>
				<tr>
					<td style="font-weight:600;">
						<?php esc_html_e( 'Q: Are audio players not visible or working properly on iPads or iPhones? Activate the iPads and iPhone native controls.', 'music-player-for-woocommerce' ); ?>
					</td>
				</tr>
				<tr>
					<td style="padding-bottom:20px;">
						<label>
						<input aria-label="<?php esc_attr_e( 'On iPads and iPhones, use native controls', 'music-player-for-woocommerce' ); ?>" type="checkbox" name="_wcmp_ios_controls" <?php if ( $ios_controls ) {
							print 'CHECKED';} ?>/>
						<?php esc_html_e( 'tick the checkbox if the players do not work properly on iPads or iPhones', 'music-player-for-woocommerce' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<td style="font-weight:600;">
						<?php esc_html_e( 'Q: Could an installed optimizer or cache management plugin be causing player loading issues? Try loading players on page load.', 'music-player-for-woocommerce' ); ?>
					</td>
				</tr>
				<tr>
					<td style="padding-bottom:20px;">
						<label>
						<input aria-label="<?php esc_attr_e( 'Loading players in the onload event', 'music-player-for-woocommerce' ); ?>" type="checkbox" name="_wcmp_onload" <?php if ( $troubleshoot_onload ) {
							print 'CHECKED';} ?>/>
						<?php esc_html_e( 'tick the checkbox if the players are not being loaded properly', 'music-player-for-woocommerce' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<td style="font-weight:600;">
						<?php esc_html_e( 'Q: Are the audio files in product settings missing file extensions, or are they stored in the cloud and not recognized as audio files by the plugin? Check the box to treat unidentified files as audio files.', 'music-player-for-woocommerce' ); ?>
					</td>
				</tr>
				<tr>
					<td style="padding-bottom:20px;">
						<label>
						<input aria-label="<?php esc_attr_e( 'For files whose extensions cannot be determined handle them as mp3 files', 'music-player-for-woocommerce' ); ?>" type="checkbox" name="_wcmp_default_extension" <?php if ( $troubleshoot_default_extension ) {
							print 'CHECKED';} ?>/>
						<?php esc_html_e( 'handle them as mp3 files', 'music-player-for-woocommerce' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<td style="font-weight:600;">
						<?php esc_html_e( 'Q: Are the store or product templates created with WooCommerce Gutenberg Blocks hiding the audio players? Check the box to force the plugin to load the players next to product titles.', 'music-player-for-woocommerce' ); ?>
					</td>
				</tr>
				<tr>
					<td style="padding-bottom:20px;">
						<label>
						<input aria-label="<?php esc_attr_e( 'For the WooCommerce Gutenberg Blocks, include the main player in the products titles', 'music-player-for-woocommerce' ); ?>" type="checkbox" name="_wcmp_force_main_player_in_title" <?php if ( $force_main_player_in_title ) {
							print 'CHECKED';} ?>/>
						<?php esc_html_e( 'Includes the main player in front of products titles', 'music-player-for-woocommerce' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<td style="font-weight:600;">
						<?php esc_html_e( 'Q: Have you installed a custom WordPress theme that doesn\'t use the standard WooCommerce hooks and players are not visible on shop pages? If so, please enter the hook names separated by commas that the plugin should check to load the audio players on the shop pages.', 'music-player-for-woocommerce' ); ?>
					</td>
				</tr>
				<tr>
					<td>
						<input aria-label="<?php esc_attr_e( 'WooCommerce hook used to display the players in the shop pages', 'music-player-for-woocommerce' ); ?>" type="text" name="_wcmp_main_player_hook" value="<?php print esc_attr( $include_main_player_hook ); ?>" style="width:100%" /><br />
						<i><?php _e( 'The plugin uses by default the <b>woocommerce_shop_loop_item_title</b> hook. If the player is not being displayed, enter the hook used by the theme active on your website.', 'music-player-for-woocommerce' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></i><br><br>
						<label>
						<input type="checkbox" name="_wcmp_main_player_hook_title" aria-label="<?php esc_attr_e( 'Force the player in the title', 'music-player-for-woocommerce' ); ?>" <?php if ( $main_player_hook_title ) {
							print 'checked';} ?>> <?php esc_html_e( 'Forces the audio player to be displayed in the product title.', 'music-player-for-woocommerce' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<td style="font-weight:600;">
						<?php esc_html_e( 'Q: Have you installed a custom WordPress theme that doesn\'t use the standard WooCommerce hooks and players are not visible on product pages? If so, please enter the hook names separated by commas that the plugin should check to load the audio players on the product pages.', 'music-player-for-woocommerce' ); ?>
					</td>
				</tr>
				<tr>
					<td>
						<input aria-label="<?php esc_attr_e( 'WooCommerce hook used to display the players in the products pages', 'music-player-for-woocommerce' ); ?>" type="text" name="_wcmp_all_players_hook" value="<?php print esc_attr( $include_all_players_hook ); ?>" style="width:100%" /><br />
						<i><?php _e( 'The plugin uses by default the <b>woocommerce_single_product_summary</b> hook. If the player is not being displayed, enter the hook used by the theme active on your website.', 'music-player-for-woocommerce' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></i>
					</td>
				</tr>
				<tr>
					<td>
						<?php _e( 'Click on the <a href="https://docs.woocommerce.com/wc-apidocs/hook-docs.html" target="_blank">THIS LINK</a> for the list of available <a href="https://docs.woocommerce.com/wc-apidocs/hook-docs.html" target="_blank" style="font-weight:bold;font-size:1.3em;">WooCommerce Hooks</a>', 'music-player-for-woocommerce' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
					</td>
				</tr>
				<tr>
					<td style="font-weight:600;">
						<?php esc_html_e( 'Q: Are the audio players visible but not working correctly? Try disabling 302 redirects.', 'music-player-for-woocommerce' ); ?>
					</td>
				</tr>
				<tr>
					<td>
						<label>
						<input aria-label="<?php esc_attr_e( 'Disable 302 redirection', 'music-player-for-woocommerce' ); ?>" type="checkbox" name="_wcmp_disable_302" <?php if ( $disable_302 ) {
							print 'CHECKED';} ?>/>
						<?php esc_html_e( 'Load demo files with PHP code instead of redirecting browsers to them.', 'music-player-for-woocommerce' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<td  style="font-weight:600;"><?php esc_html_e( 'Q: Are the demo files outdated or were they generated incorrectly? Delete the previously generated demo files to allow the plugin to regenerate them.', 'music-player-for-woocommerce' ); ?></td>
				</tr>
				<tr>
					<td>
						<label>
						<input aria-label="<?php esc_attr_e( 'Delete the demo files generated previously', 'music-player-for-woocommerce' ); ?>" type="checkbox" name="_wcmp_delete_demos" /> (<?php esc_html_e( 'only applied to demo files stored locally on the website', 'music-player-for-woocommerce' ); ?>)
						</label>
					</td>
				</tr>
				<tr>
					<td>
						<p style="border:1px solid #E6DB55;margin-bottom:10px;padding:10px;background-color:#FFFFE0;"><b><?php esc_html_e( 'Attention !!!', 'music-player-for-woocommerce' ); ?></b> <?php esc_html_e( 'After modifying the troubleshooting area attributes, it is recommended to clear the website and browser caches.', 'music-player-for-woocommerce' ); ?></p>
					</td>
				</tr>
			</table>

			<table class="widefat wcmp-player-settings" style="border:1px solid #e1e1e1;">
				<tr><td colspan="2"><h2><?php esc_html_e( 'Player Settings', 'music-player-for-woocommerce' ); ?></h2></td></tr>
				<tr>
					<td width="30%"><label for="_wcmp_enable_player"><?php esc_html_e( 'Include music player in all products', 'music-player-for-woocommerce' ); ?></label></td>
					<td><div class="wcmp-tooltip"><span class="wcmp-tooltiptext"><?php esc_html_e( 'The player is shown only if the product is "downloadable" with at least an audio file between the "Downloadable files", or you have selected your own audio files', 'music-player-for-woocommerce' ); ?></span><input aria-label="<?php esc_attr_e( 'Enable player', 'music-player-for-woocommerce' ); ?>" type="checkbox" id="_wcmp_enable_player" name="_wcmp_enable_player" <?php echo ( ( $enable_player ) ? 'checked' : '' ); ?> /></div></td>
				</tr>
				<tr>
					<td width="30%"><?php esc_html_e( 'Include in', 'music-player-for-woocommerce' ); ?></td>
					<td>
						<label><input aria-label="<?php esc_attr_e( 'Single entry pages', 'music-player-for-woocommerce' ); ?>" type="radio" name="_wcmp_show_in" value="single" <?php echo ( ( 'single' == $show_in ) ? 'checked' : '' ); ?> />
						<?php _e( 'single-entry pages <i>(Product\'s page only)</i>', 'music-player-for-woocommerce' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></label><br />

						<label><input aria-label="<?php esc_attr_e( 'Multiple entry pages', 'music-player-for-woocommerce' ); ?>" type="radio" name="_wcmp_show_in" value="multiple" <?php echo ( ( 'multiple' == $show_in ) ? 'checked' : '' ); ?> />
						<?php _e( 'multiple entries pages <i>(Shop pages, archive pages, but not in the product\'s page)</i>', 'music-player-for-woocommerce' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></label><br />

						<label><input aria-label="<?php esc_attr_e( 'Single and multiple entry pages', 'music-player-for-woocommerce' ); ?>" type="radio" name="_wcmp_show_in" value="all" <?php echo ( ( 'all' == $show_in ) ? 'checked' : '' ); ?> />
						<?php _e( 'all pages <i>(with single or multiple-entries)</i>', 'music-player-for-woocommerce' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></label>
					</td>
				</tr>
				<tr>
					<td width="30%"><label for="_wcmp_players_in_cart"><?php esc_html_e( 'Include players in cart', 'music-player-for-woocommerce' ); ?></label></td>
					<td>
						<input aria-label="<?php esc_attr_e( 'Iclude players in cart', 'music-player-for-woocommerce' ); ?>" type="checkbox" id="_wcmp_players_in_cart" name="_wcmp_players_in_cart" <?php echo ( ( $players_in_cart ) ? 'checked' : '' ); ?> />
					</td>
				</tr>
				<tr>
					<td width="30%"><label for="_wcmp_merge_in_grouped"><?php esc_html_e( 'Merge in grouped products', 'music-player-for-woocommerce' ); ?></label></td>
					<td><input aria-label="<?php esc_attr_e( 'Merge in grouped products', 'music-player-for-woocommerce' ); ?>" type="checkbox" id="_wcmp_merge_in_grouped" name="_wcmp_merge_in_grouped" <?php echo ( ( $merge_grouped ) ? 'checked' : '' ); ?> /><br /><em><?php esc_html_e( 'In grouped products, display the "Add to cart" buttons and quantity fields in the players rows', 'music-player-for-woocommerce' ); ?></em></td>
				</tr>
				<tr>
					<td valign="top" width="30%"><?php esc_html_e( 'Player layout', 'music-player-for-woocommerce' ); ?></td>
					<td>
						<table>
							<tr>
								<td><input aria-label="<?php esc_attr_e( 'Skin 1', 'music-player-for-woocommerce' ); ?>" id="skin1" name="_wcmp_player_layout" type="radio" value="mejs-classic" <?php echo ( ( 'mejs-classic' == $player_style ) ? 'checked' : '' ); ?> /></td>
								<td style="width:100%;padding-left:20px;"><label for="skin1"><img alt="<?php esc_attr_e( 'Skin 1', 'music-player-for-woocommerce' ); ?>" src="<?php print esc_url( WCMP_PLUGIN_URL ); ?>/views/assets/skin1.png" /></label></td>
							</tr>

							<tr>
								<td><input aria-label="<?php esc_attr_e( 'Skin 2', 'music-player-for-woocommerce' ); ?>" id="skin2" name="_wcmp_player_layout" type="radio" value="mejs-ted" <?php echo ( ( 'mejs-ted' == $player_style ) ? 'checked' : '' ); ?> /></td>
								<td style="width:100%;padding-left:20px;"><label for="skin2"><img alt="<?php esc_attr_e( 'Skin 2', 'music-player-for-woocommerce' ); ?>" src="<?php print esc_url( WCMP_PLUGIN_URL ); ?>/views/assets/skin2.png" /></label></td>
							</tr>

							<tr>
								<td><input aria-label="<?php esc_attr_e( 'Skin 3', 'music-player-for-woocommerce' ); ?>" id="skin3" name="_wcmp_player_layout" type="radio" value="mejs-wmp" <?php echo ( ( 'mejs-wmp' == $player_style ) ? 'checked' : '' ); ?> /></td>
								<td style="width:100%;padding-left:20px;"><label for="skin3"><img alt="<?php esc_attr_e( 'Skin 3', 'music-player-for-woocommerce' ); ?>" src="<?php print esc_url( WCMP_PLUGIN_URL ); ?>/views/assets/skin3.png" /></label></td>
							</tr>

							<tr>
								<td><input aria-label="<?php esc_attr_e( 'Custom Skin', 'music-player-for-woocommerce' ); ?>" id="custom_skin" name="_wcmp_player_layout" type="radio" value="wcmp-custom-skin" <?php echo ( ( 'wcmp-custom-skin' == $player_style ) ? 'checked' : '' ); ?> /></td>
								<td style="width:100%;padding-left:20px;"><i id="wcmp-custom-skin-settings"></i>
									<label for="custom_skin"><b><?php esc_html_e( 'Custom Skin (BETA)', 'music-player-for-woocommerce' ); ?></b></label>
									<label for="wcmp_skin_generator_api_key" style="display:block;margin-top:10px;"><b><?php esc_html_e( 'Enter the Google AI API Key', 'music-player-for-woocommerce' ); ?></b> (<a href="https://aistudio.google.com/apikey" target="_blank"><?php esc_html_e( 'Click here to access the Google AI Studio and generate an API key.', 'music-player-for-woocommerce' ); ?></a>)</label>
									<div style="display:flex;flex-direction:row;gap:5px;margin-top:10px;">
										<input type="text" id="wcmp_skin_generator_api_key" name="wcmp_skin_generator_api_key" style="flex-grow:1;" placeholder="<?php esc_attr_e( 'Enter the Google AI API Key', 'music-player-for-woocommerce' ); ?>">
										<button class="button-primary" type="button" onclick="wcmp_save_api_key();"><?php esc_html_e( 'Save API Key', 'music-player-for-woocommerce') ?></button>
										<button class="button-secondary" type="button" onclick="wcmp_clear_api_key();"><?php esc_html_e( 'Clear API Key', 'music-player-for-woocommerce') ?></button>
									</div>
									<div style="margin:10px 0;"><?php esc_html_e( 'Video tutorial:', 'music-player-for-woocommerce' ); ?> <a href="https://youtu.be/1gEqK42xCTE" target="_blank"><?php esc_html_e( 'Click Here', 'music-player-for-woocommerce' ); ?></a></div>
									<label for="_wcmp_skin_generator_description"><b><?php esc_html_e( 'Custom Skin Description', 'music-player-for-woocommerce' ); ?></b></label>
									<div style="margin-top:10px;">
										<textarea id="_wcmp_skin_generator_description" name="_wcmp_skin_generator_description" rows="5" style="width:100%;" placeholder="<?php esc_attr_e( 'Enter the skin description', 'music-player-for-woocommerce' ); ?>"><?php
											print esc_textarea( $GLOBALS['WooCommerceMusicPlayer']->get_global_attr( '_wcmp_skin_generator_description', '' ) );
										?></textarea>
									</div>
									<div style="text-align:right;margin:5px 0;">
										<button class="button-primary wcmp-skin-generate-btn" type="button" onclick="wcmp_generate_skin(this);"><?php esc_html_e( 'Generate Skin', 'music-player-for-woocommerce') ?></button>
									</div>
									<label for="_wcmp_custom_skin"><b><?php esc_html_e( 'Custom Skin Code', 'music-player-for-woocommerce' ); ?></b></label>
									<div style="margin-top:10px;">
										<textarea id="_wcmp_custom_skin" name="_wcmp_custom_skin" rows="5" style="width:100%;" placeholder="<?php esc_attr_e( 'Enter the skin description', 'music-player-for-woocommerce' ); ?>"><?php
											print esc_textarea( $GLOBALS['WooCommerceMusicPlayer']->get_global_attr( '_wcmp_custom_skin', '' ) );
										?></textarea>
									</div>
									<div style="text-align:right;margin:5px 0;">
										<button class="button-primary wcmp-skin-preview-btn" type="button" onclick="wcmp_player_preview(this);"><?php esc_html_e( 'Preview', 'music-player-for-woocommerce') ?></button>
									</div>
									<div class="wcmp_custom_skin_preview"></div>
									<script>
										function wcmp_save_api_key() {
											let api_key = String(document.getElementsByName('wcmp_skin_generator_api_key')[0].value).trim();
											localStorage.setItem('wcmp_skin_generator_api_key', api_key);
											alert( '<?php print esc_js( 'API Key stored in the browser.', 'music-player-for-woocommerce' ); ?>' );
										}
										function wcmp_clear_api_key() {
											localStorage.removeItem('wcmp_skin_generator_api_key');
											document.getElementsByName('wcmp_skin_generator_api_key')[0].value = '';
										}
										async function wcmp_player_preview(btn) {
											let previewContainer = jQuery('.wcmp_custom_skin_preview');
											previewContainer.html('');

											let customSkin = String( document.getElementsByName('_wcmp_custom_skin')[0].value ).trim();

											if ( '' == customSkin ) {
												alert( '<?php print esc_js( 'The custom skin code is empty.', 'music-player-for-woocommerce' ); ?>' );
												return;
											}
											btn.disabled = true;
											let url   = '<?php print esc_js( admin_url() ); ?>';
											let nonce = '<?php print esc_js( wp_create_nonce( 'wcmp-custom-skin-preview' ) ); ?>';

											url += ( url.indexOf('?') == -1 ? '?' : '&' ) + '_wcmp_nonce=' + encodeURIComponent( nonce );

											let formData = new FormData();
											formData.append( 'wcmp_custom_skin', customSkin );
											formData.append( 'wcmp_skin_generator_action', 1 );

											const response = await fetch(
												url,
												{
													'method' : 'POST',
													'body'   : formData
												}
											);
											if ( response.ok ) {
												const output = await response.text();
												try {
													const iframe  = jQuery( '<iframe style="width:100%;border:none;display:block;"></iframe>' );
													const encodedHTML = encodeURIComponent(output);
													iframe.appendTo(previewContainer);
													iframe.attr('src', 'data:text/html;charset=utf-8,' + encodedHTML);
												} catch ( err ) {
													alert( err.message );
												}
											}
											btn.disabled = false;
										}
										async function wcmp_generate_skin( btn ) {
											let api_key = String(document.getElementsByName('wcmp_skin_generator_api_key')[0].value).trim();
											let description = String(document.getElementsByName('_wcmp_skin_generator_description')[0].value).trim();

											if ( '' == api_key || '' == description ) {
												alert( '<?php print esc_js( 'API Key and Skin Description are required.', 'music-player-for-woocommerce' ); ?>' );
											} else {
												btn.disabled = true;
												let url   = '<?php print esc_js( admin_url() ); ?>';
												let nonce = '<?php print esc_js( wp_create_nonce( 'wcmp-ai-skin-generator' ) ); ?>';

												url += ( url.indexOf('?') == -1 ? '?' : '&' ) + '_wcmp_nonce=' + encodeURIComponent( nonce );

												let formData = new FormData();
												formData.append( 'wcmp_skin_generator_api_key', api_key );
												formData.append( 'wcmp_skin_generator_description', description );
												formData.append( 'wcmp_skin_generator_action', 1 );

												const response = await fetch(
													url,
													{
														'method' : 'POST',
														'body'   : formData
													}
												);
	'					'
												if ( response.ok ) {
													const output = await response.text();
													try {
														let result = JSON.parse( output );
														if ( 'error' in result ) {
															throw new Error( result['error'] );
														}

														if ( 'success' in result ) {
															document.getElementsByName('_wcmp_custom_skin')[0].value = result['success'];
															// Display the player preview.
															document.querySelector('.wcmp-skin-preview-btn').click();
														}
													} catch ( err ) {
														alert( err.message );
													}
												}
												btn.disabled = false;
											}
										}
										(function() {
											let api_key = localStorage.getItem('wcmp_skin_generator_api_key');
											if(api_key) document.getElementsByName('wcmp_skin_generator_api_key')[0].value = api_key;
										})();
									</script>
								</td>
							</tr>

							<tr>
								<td colspan="2" style="border-top: 1px solid #DADADA;border-bottom: 1px solid #DADADA;"><label><input aria-label="<?php esc_attr_e( 'Show a single player instead of one player per audio file.', 'music-player-for-woocommerce' ); ?>" name="_wcmp_single_player" type="checkbox" <?php echo ( ( $single_player ) ? 'checked' : '' ); ?> />
								<span style="padding-left:20px;"><?php esc_html_e( 'Show a single player instead of one player per audio file.', 'music-player-for-woocommerce' ); ?></label>
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td width="30%">
						<?php esc_html_e( 'Preload', 'music-player-for-woocommerce' ); ?>
					</td>
					<td>
						<label><input aria-label="<?php esc_attr_e( 'Preload - none', 'music-player-for-woocommerce' ); ?>" type="radio" name="_wcmp_preload" value="none" <?php if ( 'none' == $preload ) {
							echo 'CHECKED';} ?> /> None</label><br />
						<label><input aria-label="<?php esc_attr_e( 'Preload - metadata', 'music-player-for-woocommerce' ); ?>" type="radio" name="_wcmp_preload" value="metadata" <?php if ( 'metadata' == $preload ) {
							echo 'CHECKED';} ?> /> Metadata</label><br />
						<label><input aria-label="<?php esc_attr_e( 'Preload - auto', 'music-player-for-woocommerce' ); ?>" type="radio" name="_wcmp_preload" value="auto" <?php if ( 'auto' == $preload ) {
							echo 'CHECKED';} ?> /> Auto</label><br />
					</td>
				</tr>
				<tr>
					<td width="30%">
						<label for="_wcmp_play_all"><?php esc_html_e( 'Play all', 'music-player-for-woocommerce' ); ?></label>
					</td>
					<td>
						<input aria-label="<?php esc_attr_e( 'Play all', 'music-player-for-woocommerce' ); ?>" type="checkbox" id="_wcmp_play_all" name="_wcmp_play_all" <?php if ( $play_all ) {
							echo 'CHECKED';} ?> />
					</td>
				</tr>
				<tr>
					<td width="30%">
						<label for="_wcmp_loop"><?php esc_html_e( 'Loop', 'music-player-for-woocommerce' ); ?></label>
					</td>
					<td>
						<input aria-label="<?php esc_attr_e( 'Loop', 'music-player-for-woocommerce' ); ?>" type="checkbox" id="_wcmp_loop" name="_wcmp_loop" <?php if ( $loop ) {
							echo 'CHECKED';} ?> />
					</td>
				</tr>
				<tr>
					<td width="30%">
						<label for="_wcmp_play_simultaneously"><?php esc_html_e( 'Allow multiple players to play simultaneously', 'music-player-for-woocommerce' ); ?></label>
					</td>
					<td>
						<input aria-label="<?php esc_attr_e( 'Allow multiple players to play simultaneously', 'music-player-for-woocommerce' ); ?>" type="checkbox" id="_wcmp_play_simultaneously" name="_wcmp_play_simultaneously" <?php if ( $play_simultaneously ) {
							echo 'CHECKED';} ?> /><br />
						<i><?php
							esc_html_e( 'By default, only one player would be playing at once. By pressing the play button of a player, the other players would stop. By ticking the checkbox, multiple players could play simultaneously.', 'music-player-for-woocommerce' );
						?></i>
					</td>
				</tr>
				<tr>
					<td><label for="_wcmp_player_volume" ><?php esc_html_e( 'Player volume (from 0 to 1)', 'music-player-for-woocommerce' ); ?></label></td>
					<td>
						<input aria-label="<?php esc_attr_e( 'Player volume', 'music-player-for-woocommerce' ); ?>" type="number" id="_wcmp_player_volume" name="_wcmp_player_volume" min="0" max="1" step="0.01" value="<?php echo esc_attr( $volume ); ?>" />
					</td>
				</tr>
				<tr>
					<td width="30%"><?php esc_html_e( 'Player controls', 'music-player-for-woocommerce' ); ?></td>
					<td>
						<label><input aria-label="<?php esc_attr_e( 'Play/pause button only', 'music-player-for-woocommerce' ); ?>" type="radio" name="_wcmp_player_controls" value="button" <?php echo ( ( 'button' == $player_controls ) ? 'checked' : '' ); ?> /> <?php esc_html_e( 'the play/pause button only', 'music-player-for-woocommerce' ); ?></label><br />
						<label><input aria-label="<?php esc_attr_e( 'All controls', 'music-player-for-woocommerce' ); ?>" type="radio" name="_wcmp_player_controls" value="all" <?php echo ( ( 'all' == $player_controls ) ? 'checked' : '' ); ?> /> <?php esc_html_e( 'all controls', 'music-player-for-woocommerce' ); ?></label><br />
						<label><input aria-label="<?php esc_attr_e( 'Controls depending on context', 'music-player-for-woocommerce' ); ?>" type="radio" name="_wcmp_player_controls" value="default" <?php echo ( ( 'default' == $player_controls ) ? 'checked' : '' ); ?> /> <?php esc_html_e( 'the play/pause button only, or all controls depending on context', 'music-player-for-woocommerce' ); ?></label>
						<div class="wcmp-on-cover" style="margin-top:10px;">
							<label><input aria-label="<?php esc_attr_e( 'Player on cover images', 'music-player-for-woocommerce' ); ?>" type="checkbox" name="_wcmp_player_on_cover" value="default" <?php
							echo ( ( ! empty( $on_cover ) && ( 'button' == $player_controls || 'default' == $player_controls ) ) ? 'checked' : '' );
							?> />
							<?php esc_html_e( 'for play/pause button players display them on cover images.', 'music-player-for-woocommerce' ); ?>
							<i>
							<?php
							esc_html_e( '(This feature is experimental, and will depend on the theme active on the website.)', 'music-player-for-woocommerce' );
							?>
							</i></label>
						</div>
					</td>
				</tr>
				<tr>
					<td width="30%"><label for="_wcmp_player_title"><?php esc_html_e( 'Display the player\'s title', 'music-player-for-woocommerce' ); ?></label></td>
					<td>
						<input aria-label="<?php esc_attr_e( 'Display the player title', 'music-player-for-woocommerce' ); ?>" type="checkbox" id="_wcmp_player_title" name="_wcmp_player_title" <?php echo ( ( ! empty( $player_title ) ) ? 'checked' : '' ); ?> />
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<table class="widefat" style="border:1px solid #e1e1e1;">
							<tr><td colspan="2"><h2><?php esc_html_e( 'Files Protection', 'music-player-for-woocommerce' ); ?></h2></td></tr>
							<tr>
								<td width="30%"><label for="_wcmp_secure_player"><?php esc_html_e( 'Protect the file', 'music-player-for-woocommerce' ); ?></label></td>
								<td><input aria-label="<?php esc_attr_e( 'Protect the file', 'music-player-for-woocommerce' ); ?>" type="checkbox" id="_wcmp_secure_player" name="_wcmp_secure_player" <?php echo ( ( $secure_player ) ? 'checked' : '' ); ?> /></td>
							</tr>
							<tr valign="top">
								<td width="30%"><label for="_wcmp_file_percent"><?php esc_html_e( 'Percent of audio used for protected playbacks', 'music-player-for-woocommerce' ); ?></label></td>
								<td>
									<input aria-label="<?php esc_attr_e( 'Percent of audio used for protected playbacks', 'music-player-for-woocommerce' ); ?>" type="number" id="_wcmp_file_percent" name="_wcmp_file_percent" value="<?php echo esc_attr( $file_percent ); ?>" /> % <br />
									<em><?php esc_html_e( 'To prevent unauthorized copying of audio files, the files will be partially accessible', 'music-player-for-woocommerce' ); ?></em>
								</td>
							</tr>
							<tr valign="top">
								<td width="30%">
									<label for="_wcmp_message"><?php esc_html_e( 'Text to display beside the player explaining that demos are partial versions of the original files', 'music-player-for-woocommerce' ); ?></label>
								</td>
								<td>
									<textarea aria-label="<?php esc_attr_e( 'Explaining that demos are partial versions of the original files', 'music-player-for-woocommerce' ); ?>" id="_wcmp_message" name="_wcmp_message" style="width:100%;" rows="4"><?php echo esc_textarea( $message ); ?></textarea>
								</td>
							</tr>
							<tr>
								<td colspan="2"><hr /></td>
							</tr>
							<tr>
								<td colspan="2"><i><?php esc_html_e( 'Audio files for demos are created by directly truncating the content of the original files using PHP by default. FFmpeg is a powerful command-line tool used for manipulating multimedia files.', 'music-player-for-woocommerce' ); ?></i></td>
							</tr>
							<tr>
								<td width="30%"><label for="_wcmp_ffmpeg"><?php esc_html_e( 'Truncate the audio files for demo with ffmpeg', 'music-player-for-woocommerce' ); ?></label></td>
								<td><input aria-label="<?php esc_attr_e( 'Truncate the audio files for demo with ffmpeg', 'music-player-for-woocommerce' ); ?>" type="checkbox" id="_wcmp_ffmpeg" name="_wcmp_ffmpeg" <?php print( ( $ffmpeg ) ? 'CHECKED' : '' ); ?> /></td>
							</tr>
							<tr>
								<td width="30%"><label for="_wcmp_ffmpeg_path"><?php esc_html_e( 'ffmpeg path', 'music-player-for-woocommerce' ); ?></label></td>
								<td>
									<input aria-label="<?php esc_attr_e( 'ffmpeg path', 'music-player-for-woocommerce' ); ?>" type="text" id="_wcmp_ffmpeg_path" name="_wcmp_ffmpeg_path" value="<?php print esc_attr( empty( $ffmpeg_path ) && ! empty( $ffmpeg_system_path ) ? $ffmpeg_system_path : $ffmpeg_path ); ?>" style="width:100%;" /><br />
									<i>Ex: /usr/bin/</i>
								</td>
							</tr>
							<tr>
								<td width="30%"><label for="_wcmp_ffmpeg_watermark"><?php esc_html_e( 'Watermark audio', 'music-player-for-woocommerce' ); ?></label></td>
								<td>
									<input aria-label="<?php esc_attr_e( 'Watermark audio', 'music-player-for-woocommerce' ); ?>" type="text" id="_wcmp_ffmpeg_watermark" name="_wcmp_ffmpeg_watermark" value="<?php print esc_attr( $ffmpeg_watermark ); ?>" style="width:calc( 100% - 60px ) !important;" class="wcmp-file-url" /><input type="button" class="button-secondary wcmp-select-file" value="<?php esc_attr_e( 'Select', 'music-player-for-woocommerce' ); ?>" style="float:right;" /><br />
									<i><?php esc_html_e( 'Select an audio file if you want to apply a watermark to the audio files for demos. The watermark will be applied to the protected demos (Experimental feature). The watermark is applied to the audio files for demos when they are created with the FFmpeg application.', 'music-player-for-woocommerce' ); ?></i>
								</td>
							</tr>
						</table>

						<table class="widefat" style="border:1px solid #e1e1e1;margin-top:10px;">
							<tr>
								<td>
									<div><h2><?php esc_html_e( 'Scope', 'music-player-for-woocommerce' ); ?></h2></div>
									<div><label><div class="wcmp-tooltip"><span class="wcmp-tooltiptext"><?php esc_html_e( 'Ticking the checkbox the previous settings are applied to all products, even if they have a player enabled.', 'music-player-for-woocommerce' ); ?></span><input aria-label="<?php esc_attr_e( 'Apply the previous settings to all products', 'music-player-for-woocommerce' ); ?>" type="checkbox" name="_wcmp_apply_to_all_players" <?php print $apply_to_all_players == 1 ? 'CHECKED' : ''; ?> /></div> <?php esc_html_e( 'Apply the above settings to all product pages on the website, including existing ones.', 'music-player-for-woocommerce' ); ?></label></div>
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
					<td><h2><?php esc_html_e( 'Analytics', 'music-player-for-woocommerce' ); ?></h2></td>
				</tr>
				<tr>
					<td>
					<label><input aria-label="<?php esc_attr_e( 'Show "playback Counter" in the WooCommerce products list', 'music-player-for-woocommerce' ); ?>" type="checkbox" name="_wcmp_playback_counter_column" <?php print( ( $playback_counter_column ) ? 'CHECKED' : '' ); ?> />
					<?php esc_html_e( 'Show "playback Counter" in the WooCommerce products list', 'music-player-for-woocommerce' ); ?></label>
					</td>
				</tr>
				<tr>
					<td>
						<p><?php esc_html_e( 'Allows the integration with Google Analytics for registering new events when the songs are played. The event information would include: the URL to the audio file as the event label and the product\'s id as its value.', 'music-player-for-woocommerce' ); ?></p>
						<p style="border:1px solid #E6DB55;margin-bottom:10px;padding:5px;background-color: #FFFFE0;"><b><?php esc_html_e( 'Note', 'music-player-for-woocommerce' ); ?></b>: <?php esc_html_e( 'If the preload option is configured as Metadata or Auto in the players settings, the event would be registered when the audio file is loaded by the player and not exactly when they are playing.', 'music-player-for-woocommerce' ); ?></p>
					</td>
				</tr>
				<tr>
					<td>
						<label><input type="radio" name="_wcmp_analytics_integration" value="ua" <?php print 'ua' == $analytics_integration ? 'CHECKED' : ''; ?>> <?php esc_html_e( 'Universal Analytics', 'music-player-for-woocommerce' ); ?></label>
						<label style="margin-left:30px;"><input type="radio" name="_wcmp_analytics_integration" value="g" <?php print 'g' == $analytics_integration ? 'CHECKED' : ''; ?>> <?php esc_html_e( 'Measurement Protocol (Google Analytics 4)', 'music-player-for-woocommerce' ); ?></label>
					</td>
				</tr>
				<tr>
					<td>
						<div><?php esc_html_e( 'Measurement Id', 'music-player-for-woocommerce' ); ?></div>
						<div><input aria-label="<?php esc_attr_e( 'Measurement id', 'music-player-for-woocommerce' ); ?>" type="text" name="_wcmp_analytics_property" value="<?php print esc_attr( $analytics_property ); ?>" style="width:100%;" placeholder="UA-XXXXX-Y"></div>
					</td>
				</tr>
				<tr class="wcmp-analytics-g4" style="display:<?php print esc_attr( 'ua' == $analytics_integration ? 'none' : 'table-row' ); ?>;">
					<td style="width:100%;">
						<div><?php esc_html_e( 'API Secret', 'music-player-for-woocommerce' ); ?></div>
						<div><input aria-label="<?php esc_attr_e( 'API Secret', 'music-player-for-woocommerce' ); ?>" type="text" name="_wcmp_analytics_api_secret" value="<?php print esc_attr( $analytics_api_secret ); ?>" style="width:100%;"></div>
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
					<td colspan="2"><h2><?php esc_html_e( 'Add ons', 'music-player-for-woocommerce' ); ?></h2></td>
				</tr>
				<?php do_action( 'wcmp_addon_general_settings' ); ?>
			</table>
		</td>
	</tr>
</table>
<div style="margin-top:20px;"><input type="submit" value="<?php esc_attr_e( 'Save settings', 'music-player-for-woocommerce' ); ?>" class="button-primary" /></div>
</form>
<script>jQuery(window).on('load', function(){
	var $ = jQuery;
	function coverSection()
	{
		var v = $('[name="_wcmp_player_controls"]:checked').val(),
			c = $('.wcmp-on-cover');
		if(v == 'default' || v == 'button') c.show();
		else c.hide();
	};
	$(document).on('change', '[name="_wcmp_player_controls"]', function(){
		coverSection();
	});
	$(document).on('change', '[name="_wcmp_analytics_integration"]', function(){
		var v = $('[name="_wcmp_analytics_integration"]:checked').val();
		$('.wcmp-analytics-g4').css('display', v == 'g' ? 'table-row' : 'none');
		$('[name="_wcmp_analytics_property"]').attr('placeholder', v == 'g' ? 'G-XXXXXXXX' : 'UA-XXXXX-Y');
	});
	$('[name="_wcmp_analytics_integration"]:eq(0)').change();
	coverSection();
});</script>
<style>.wcmp-player-settings tr td:first-child{width:225px;}</style>
