<?php
if ( ! defined( 'WCMP_PLUGIN_URL' ) ) {
	echo 'Direct access not allowed.';
	exit; }

// include resources
wp_enqueue_style( 'wcmp-admin-style', plugin_dir_url( __FILE__ ) . '../../css/style.admin.css', array(), WCMP_VERSION );
wp_enqueue_script( 'wcmp-admin-js', plugin_dir_url( __FILE__ ) . '../../js/admin.js', array(), WCMP_VERSION );
wp_enqueue_script( 'wcmp-dokan-js', plugin_dir_url( __FILE__ ) . 'script.js', array(), WCMP_VERSION );

$wcmp_js = array(
	'File Name'         => __( 'File Name', 'music-player-for-woocommerce' ),
	'Choose file'       => __( 'Choose file', 'music-player-for-woocommerce' ),
	'Delete'            => __( 'Delete', 'music-player-for-woocommerce' ),
	'Select audio file' => __( 'Select audio file', 'music-player-for-woocommerce' ),
	'Select Item'       => __( 'Select Item', 'music-player-for-woocommerce' ),
);
wp_localize_script( 'wcmp-admin-js', 'wcmp', $wcmp_js );

global $post;
$enable_player    = $GLOBALS['WooCommerceMusicPlayer']->get_product_attr( $post->ID, '_wcmp_enable_player', false );
$show_in          = $GLOBALS['WooCommerceMusicPlayer']->get_product_attr( $post->ID, '_wcmp_show_in', 'all' );
$player_style     = $GLOBALS['WooCommerceMusicPlayer']->get_product_attr( $post->ID, '_wcmp_player_layout', WCMP_DEFAULT_PLAYER_LAYOUT );
$player_controls  = $GLOBALS['WooCommerceMusicPlayer']->get_product_attr( $post->ID, '_wcmp_player_controls', WCMP_DEFAULT_PLAYER_CONTROLS );
$secure_player    = $GLOBALS['WooCommerceMusicPlayer']->get_product_attr( $post->ID, '_wcmp_secure_player', false );
$file_percent     = $GLOBALS['WooCommerceMusicPlayer']->get_product_attr( $post->ID, '_wcmp_file_percent', WCMP_FILE_PERCENT );
$player_title     = intval( $GLOBALS['WooCommerceMusicPlayer']->get_product_attr( $post->ID, '_wcmp_player_title', 1 ) );
$merge_grouped    = intval( $GLOBALS['WooCommerceMusicPlayer']->get_product_attr( $post->ID, '_wcmp_merge_in_grouped', 0 ) );
$own_demos        = intval( $GLOBALS['WooCommerceMusicPlayer']->get_product_attr( $post->ID, '_wcmp_own_demos', 0 ) );
$direct_own_demos = intval( $GLOBALS['WooCommerceMusicPlayer']->get_product_attr( $post->ID, '_wcmp_direct_own_demos', 0 ) );
$demos_list       = $GLOBALS['WooCommerceMusicPlayer']->get_product_attr( $post->ID, '_wcmp_demos_list', array() );
$play_all         = intval(
	$GLOBALS['WooCommerceMusicPlayer']->get_product_attr(
		$post->ID,
		'_wcmp_play_all',
		// This option is only for compatibility with versions previous to 1.0.28
						$GLOBALS['WooCommerceMusicPlayer']->get_product_attr(
							$post->ID,
							'play_all',
							0
						)
	)
);
$preload  = $GLOBALS['WooCommerceMusicPlayer']->get_product_attr(
	$post->ID,
	'_wcmp_preload',
	$GLOBALS['WooCommerceMusicPlayer']->get_product_attr(
		$post->ID,
		'preload',
		'none'
	)
);
$on_cover = intval( $GLOBALS['WooCommerceMusicPlayer']->get_product_attr( $post->ID, '_wcmp_on_cover', 0 ) );
?>
<div class="dokan-edit-row wcmp-section">
	<input type="hidden" name="wcmp_nonce" value="<?php echo esc_attr( wp_create_nonce( 'wcmp_updating_product' ) ); ?>" />
	<div class="dokan-section-heading"><h2><?php esc_html_e( 'Music Player Settings', 'music-player-for-woocommerce' ); ?></h2></div>
	<div class="dokan-section-content">
<?php
if ( ! get_option( 'wcmp_dokan_hide_settings', 0 ) ) :
	?>
		<div class="wcmp-highlight-box">
		<?php _e( '<p>The player uses the audio files associated to the product. If you want protecting the audio files for selling, tick the checkbox: <b>"Protect the file"</b>, in whose case the plugin will create a truncated version of the audio files for selling to be used for demo. The size of audio files for demo is based on the number entered through the attribute: <b>"Percent of audio used for protected playbacks"</b>.</p><p><b>Protecting the files prevents that malicious users can access to the original audio files without pay for them.</b></p>', 'music-player-for-woocommerce' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
		</div>
		<div class="wcmp-highlight-box">
			<div id="wcmp_tips_header">
				<div style="margin-top:2px;margin-bottom:5px;cursor:pointer;font-weight:bold;" onclick="jQuery('#wcmp_tips_body').toggle();">
					<?php esc_html_e( '[+|-] Tips', 'music-player-for-woocommerce' ); ?>
				</div>
			</div>
			<div id="wcmp_tips_body">
				<div class="wcmp-highlight-box">
					<a class="wcmp-tip"href="javascript:void(0);" onclick="jQuery(this).next('.wcmp-tip-text').toggle();">
					<?php esc_html_e( '[+|-] Using the audio files stored on Google Drive', 'music-player-for-woocommerce' ); ?>
					</a>
					<div class="wcmp-tip-text">
					<ul>
						<li>
							<p> -
							<?php _e( 'Go to Drive, press the right click on the file to use, and select the option: <b>"Get Shareable Link"</b>', 'music-player-for-woocommerce' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							</p>
							<p>
							<?php _e( 'The previous action will generate an url with the structure: <b>https://drive.google.com/open?id=FILE_ID</b>', 'music-player-for-woocommerce' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							</p>
						</li>
						<li>
							<p> -
								<?php
								esc_html_e(
									'Knowing the FILE_ID, extracted from the previous URL, enter the URL below, into the WooCommerce product, to allow the Music Player accessing to it:',
									'music-player-for-woocommerce'
								);
								?>
							</p>
							<p>
								<b>https://drive.google.com/uc?export=download&id=FILE_ID&.mp3</b>
							</p>
							<p>
								<?php _e( '<b>Note:</b> Pay attention to the use of the fake parameter: <b>&.mp3</b> as the last one in the URL', 'music-player-for-woocommerce' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							</p>
						</li>
					</div>
				</div>
				<div class="wcmp-highlight-box">
					<a class="wcmp-tip"href="javascript:void(0);" onclick="jQuery(this).next('.wcmp-tip-text').toggle();">
					<?php esc_html_e( '[+|-] Using the audio files stored on DropBox', 'music-player-for-woocommerce' ); ?>
					</a>
					<div class="wcmp-tip-text">
					<ul>
						<li>
							<p> -
							<?php _e( 'Sign in to <a href="https://www.dropbox.com/login" target="_blank">dropbox.com </a>', 'music-player-for-woocommerce' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							</p>
						</li>
						<li>
							<p> -
							<?php _e( "Hover your cursor over the file or folder you'd like to share and click <b>Share</b> when it appears.", 'music-player-for-woocommerce' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							</p>
						</li>
						<li>
							<p> -
							<?php
								esc_html_e(
									"If a link hasn't been created, click Create a link. (If a link was already created, click Copy link.",
									'music-player-for-woocommerce'
								);
							?>
							</p>
							<p>
							<?php
								esc_html_e(
									'The link structure would be similar to:',
									'music-player-for-woocommerce'
								);
							?><br> https://www.dropbox.com/s/rycvgn8iokfedmo/file.mp3?dl=0
							</p>
						</li>
						<li>
							<p> -
							<?php
								esc_html_e(
									'Enter the URL into the WooCommerce product with the following structure:',
									'music-player-for-woocommerce'
								);
							?><br> https://www.dropbox.com/s/rycvgn8iokfedmo/file.mp3?dl=1&.mp3
							</p>
							<p>
								<?php _e( '<b>Note:</b> Pay attention to the use of the fake parameter: <b>&.mp3</b> as the last one in the URL. Furthermore, the parameter <b>dl=0</b>, has been modified as <b>dl=1</b>', 'music-player-for-woocommerce' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
							</p>
						</li>
					</div>
				</div>
			</div>
		</div>
		<div>
			<div class="wcmp-dokan-attr">
				<label class="wcmp-dokan-attr-label">
					<input aria-label="<?php esc_attr_e( 'Enable player', 'music-player-for-woocommerce' ); ?>" type="checkbox" name="_wcmp_enable_player" <?php echo ( ( $enable_player ) ? 'checked' : '' ); ?> title="<?php esc_attr_e( 'The player is shown only if the product is "downloadable" with at least an audio file between the "Downloadable files", or you have selected your own audio files', 'music-player-for-woocommerce' ); ?>" /> <?php esc_html_e( 'Include music player', 'music-player-for-woocommerce' ); ?>
				</label>
			</div>
			<div class="wcmp-dokan-attr">
				<label for="_wcmp_show_in" class="wcmp-dokan-attr-label"><?php esc_html_e( 'Include in', 'music-player-for-woocommerce' ); ?></label>
				<div>
					<label><input aria-label="<?php esc_attr_e( 'Inlude on products pages only', 'music-player-for-woocommerce' ); ?>" type="radio" name="_wcmp_show_in" value="single" <?php echo ( ( 'single' == $show_in ) ? 'checked' : '' ); ?> />
					<?php _e( 'single-entry pages <i>(Product\'s page only)</i>', 'music-player-for-woocommerce' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></label>

					<label><input aria-label="<?php esc_attr_e( 'Include on multiple-entry pages', 'music-player-for-woocommerce' ); ?>" type="radio" name="_wcmp_show_in" value="multiple" <?php echo ( ( 'multiple' == $show_in ) ? 'checked' : '' ); ?> />
					<?php _e( 'multiple entries pages <i>(Shop pages, archive pages, but not in the product\'s page)</i>', 'music-player-for-woocommerce' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></label>

					<label><input aria-label="<?php esc_attr_e( 'Include on products and multiple-entry pages', 'music-player-for-woocommerce' ); ?>" type="radio" name="_wcmp_show_in" value="all" <?php echo ( ( 'all' == $show_in ) ? 'checked' : '' ); ?> />
					<?php _e( 'all pages <i>(with single or multiple-entries)</i>', 'music-player-for-woocommerce' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></label>
				</div>
			</div>
			<div class="wcmp-dokan-attr">
				<label class="wcmp-dokan-attr-label"><?php esc_html_e( 'Merge in grouped products', 'music-player-for-woocommerce' ); ?>
				<input aria-label="<?php esc_attr_e( 'Merge in grouped products', 'music-player-for-woocommerce' ); ?>" type="checkbox" name="_wcmp_merge_in_grouped" <?php echo ( ( $merge_grouped ) ? 'checked' : '' ); ?> /></label>
				<div><em><?php esc_html_e( 'In grouped products, display the "Add to cart" buttons and quantity fields in the players rows', 'music-player-for-woocommerce' ); ?></em></div>
			</div>
			<div class="wcmp-dokan-attr">
				<label class="wcmp-dokan-attr-label"><?php esc_html_e( 'Player layout', 'music-player-for-woocommerce' ); ?></label>
				<table border="0" width="100%">
					<tr>
						<td><input aria-label="<?php esc_attr_e( 'Skin 1', 'music-player-for-woocommerce' ); ?>" name="_wcmp_player_layout" type="radio" value="mejs-classic" <?php echo ( ( 'mejs-classic' == $player_style ) ? 'checked' : '' ); ?> /></td>
						<td><img alt="<?php esc_attr_e( 'skin 1', 'music-player-for-woocommerce' ); ?>" src="<?php print esc_url( WCMP_PLUGIN_URL ); ?>/views/assets/skin1.png" /></td>
					</tr>

					<tr>
						<td><input aria-label="<?php esc_attr_e( 'Skin 2', 'music-player-for-woocommerce' ); ?>" name="_wcmp_player_layout" type="radio" value="mejs-ted" <?php echo ( ( 'mejs-ted' == $player_style ) ? 'checked' : '' ); ?> /></td>
						<td><img alt="<?php esc_attr_e( 'Skin 2', 'music-player-for-woocommerce' ); ?>" src="<?php print esc_url( WCMP_PLUGIN_URL ); ?>/views/assets/skin2.png" /></td>
					</tr>

					<tr>
						<td><input aria-label="<?php esc_attr_e( 'Skin 3', 'music-player-for-woocommerce' ); ?>" name="_wcmp_player_layout" type="radio" value="mejs-wmp" <?php echo ( ( 'mejs-wmp' == $player_style ) ? 'checked' : '' ); ?> /></td>
						<td><img alt="<?php esc_attr_e( 'Skin 3', 'music-player-for-woocommerce' ); ?>" src="<?php print esc_url( WCMP_PLUGIN_URL ); ?>/views/assets/skin3.png" /></td>
					</tr>
				</table>
			</div>
			<div class="wcmp-dokan-attr">
				<label for="_wcmp_preload" class="wcmp-dokan-attr-label"><?php esc_html_e( 'Preload', 'music-player-for-woocommerce' ); ?></label>
				<div>
					<label><input aria-label="<?php esc_attr_e( 'Preload - none', 'music-player-for-woocommerce' ); ?>" type="radio" name="_wcmp_preload" value="none" <?php if ( 'none' == $preload ) {
						echo 'CHECKED';} ?> /> None</label>
					<label><input aria-label="<?php esc_attr_e( 'Preload - metadata', 'music-player-for-woocommerce' ); ?>" type="radio" name="_wcmp_preload" value="metadata" <?php if ( 'metadata' == $preload ) {
						echo 'CHECKED';} ?> /> Metadata</label>
					<label><input aria-label="<?php esc_attr_e( 'Preload - auto', 'music-player-for-woocommerce' ); ?>" type="radio" name="_wcmp_preload" value="auto" <?php if ( 'auto' == $preload ) {
						echo 'CHECKED';} ?> /> Auto</label>
				</div>
			</div>
			<div class="wcmp-dokan-attr">
				<label><?php esc_html_e( 'Play all', 'music-player-for-woocommerce' ); ?> <input aria-label="<?php esc_attr_e( 'Play all', 'music-player-for-woocommerce' ); ?>" type="checkbox" name="_wcmp_play_all" <?php if ( ! empty( $play_all ) ) {
					echo 'CHECKED';} ?> /></label>
			</div>
			<div class="wcmp-dokan-attr">
				<label class="wcmp-dokan-attr-label"><?php esc_html_e( 'Player controls', 'music-player-for-woocommerce' ); ?></label>
				<div>
					<label><input aria-label="<?php esc_attr_e( 'Play/pause button only', 'music-player-for-woocommerce' ); ?>" type="radio" name="_wcmp_player_controls" value="button" <?php echo ( ( 'button' == $player_controls ) ? 'checked' : '' ); ?> /> <?php esc_html_e( 'the play/pause button only', 'music-player-for-woocommerce' ); ?></label>
					<label><input aria-label="<?php esc_attr_e( 'All controls', 'music-player-for-woocommerce' ); ?>" type="radio" name="_wcmp_player_controls" value="all" <?php echo ( ( 'all' == $player_controls ) ? 'checked' : '' ); ?> /> <?php esc_html_e( 'all controls', 'music-player-for-woocommerce' ); ?></label>
					<label><input aria-label="<?php esc_attr_e( 'Controls depending on context', 'music-player-for-woocommerce' ); ?>" type="radio" name="_wcmp_player_controls" value="default" <?php echo ( ( 'default' == $player_controls ) ? 'checked' : '' ); ?> /> <?php esc_html_e( 'the play/pause button only, or all controls depending on context', 'music-player-for-woocommerce' ); ?></label>
					<div class="wcmp-on-cover" style="margin-top:10px;">
						<label><input aria-label="<?php esc_attr_e( 'Player on cover', 'music-player-for-woocommerce' ); ?>" type="checkbox" name="_wcmp_player_on_cover" value="default" <?php
						echo ( ( ! empty( $on_cover ) && ( 'button' == $player_controls || 'default' == $player_controls ) ) ? 'checked' : '' );
						?> /> <?php esc_html_e( 'for play/pause button players display them on cover images.', 'music-player-for-woocommerce' ); ?></label>
						<div><em><?php esc_html_e( '(This feature is experimental, and will depend on the theme active on the website.)', 'music-player-for-woocommerce' ); ?></em></div>
					</div>
				</div>
			</div>
			<div class="wcmp-dokan-attr">
				<label><?php esc_html_e( 'Display the player\'s title', 'music-player-for-woocommerce' ); ?> <input aria-label="<?php esc_attr_e( 'Display player title', 'music-player-for-woocommerce' ); ?>" type="checkbox" name="_wcmp_player_title" <?php echo ( ( ! empty( $player_title ) ) ? 'checked' : '' ); ?> /></label>
			</div>
			<div class="wcmp-dokan-attr">
				<label class="wcmp-dokan-attr-label"><?php esc_html_e( 'Protect the file', 'music-player-for-woocommerce' ); ?> <input aria-label="<?php esc_attr_e( 'Protect the file', 'music-player-for-woocommerce' ); ?>" type="checkbox" name="_wcmp_secure_player" <?php echo ( ( $secure_player ) ? 'checked' : '' ); ?> /></label>
				<label><?php esc_html_e( 'Percent of audio used for protected playbacks', 'music-player-for-woocommerce' ); ?>(%)<br />
				<input aria-label="<?php esc_attr_e( 'Percent of audio used for protected playbacks', 'music-player-for-woocommerce' ); ?>" type="number" name="_wcmp_file_percent" value="<?php echo esc_attr( $file_percent ); ?>" /></label>
				<div><em><?php esc_html_e( 'To prevent unauthorized copying of audio files, the files will be partially accessible', 'music-player-for-woocommerce' ); ?></em></div>
			</div>
	<?php
else :
	?>
		<div>
	<?php
endif; // End player settings
?>
			<div class="wcmp-dokan-attr">
				<label class="wcmp-dokan-attr-label"><input aria-label="<?php esc_attr_e( 'Select my own demo files', 'music-player-for-woocommerce' ); ?>" type="checkbox" name="_wcmp_own_demos" <?php echo ( ( $own_demos ) ? 'checked' : '' ); ?> /> <?php esc_html_e( 'Select my own demo files', 'music-player-for-woocommerce' ); ?></label>
				<div class="wcmp-demo-files" style="display:<?php echo ( $own_demos ) ? 'block' : 'none'; ?>;">
					<label><?php esc_html_e( 'Demo files', 'music-player-for-woocommerce' ); ?></label>
					<table class="dokan-table">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Name', 'music-player-for-woocommerce' ); ?></th>
								<th><?php esc_html_e( 'File URL', 'music-player-for-woocommerce' ); ?></th>
								<th>&nbsp;</th>
							</tr>
						</thead>
						<tbody>
							<?php
							foreach ( $demos_list as $demo ) {
								?>
								<tr>
									<td>
										<input aria-label="<?php esc_attr_e( 'File name', 'music-player-for-woocommerce' ); ?>" type="text" class="wcmp-file-name" placeholder="<?php esc_attr_e( 'File Name', 'music-player-for-woocommerce' ); ?>" name="_wcmp_file_names[]" value="<?php echo esc_attr( $demo['name'] ); ?>" />
									</td>
									<td>
										<input aria-label="<?php esc_attr_e( 'File URL', 'music-player-for-woocommerce' ); ?>" type="text" class="wcmp-file-url" placeholder="http://" name="_wcmp_file_urls[]" value="<?php echo esc_attr( $demo['file'] ); ?>" />
										<a href="#" class="wcmp-select-file dokan-btn dokan-btn-sm dokan-btn-default"><?php esc_html_e( 'Choose file', 'music-player-for-woocommerce' ); ?></a>
									</td>
									<td>
										<a href="#" class="wcmp-delete dokan-btn dokan-btn-sm dokan-btn-danger"><?php esc_html_e( 'Delete', 'music-player-for-woocommerce' ); ?></a>
									</td>
								</tr>
								<?php
							}
							?>
						</tbody>
						<tfoot>
							<tr>
								<th colspan="4">
									<a href="#" class="wcmp-dokan-add dokan-btn dokan-btn-sm dokan-btn-success"><?php esc_html_e( 'Add File', 'music-player-for-woocommerce' ); ?></a>
								</th>
							</tr>
						</tfoot>
					</table>
				</div>
				<label><input aria-label="<?php esc_attr_e( 'Load directly the original demo files', 'music-player-for-woocommerce' ); ?>" type="checkbox" name="_wcmp_direct_own_demos" <?php echo ( ( $direct_own_demos ) ? 'checked' : '' ); ?> /> <?php esc_html_e( 'Load directly the own demo files without preprocessing', 'music-player-for-woocommerce' ); ?></label>
			</div>
		</div>
	</div>
</div>
<script>jQuery(window).on('load', function(){
	var $ = jQuery;
	function coverSection()
	{
		var v = $('[name="_wcmp_player_controls"]:checked').val(),
			c = $('.wcmp-on-cover');
		if('default' == v || 'button' == v) c.show();
		else c.hide();
	};
	$(document).on('change', '[name="_wcmp_player_controls"]', function(){
		coverSection();
	});
	coverSection();
});</script>
