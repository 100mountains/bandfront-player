<?php
/**
 * Global admin options template
 * 
 * Variables available in this template:
 * @var Bandfront\Core\Config $config Config instance
 * @var Bandfront\Storage\FileManager $fileManager FileManager instance
 * @var Bandfront\UI\Renderer $renderer Renderer instance
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// include resources
wp_enqueue_style( 'bfp-admin-style', BFP_PLUGIN_URL . 'assets/css/style-admin.css', array(), '5.0.181' );
wp_enqueue_style( 'bfp-admin-notices', BFP_PLUGIN_URL . 'assets/css/admin-notices.css', array(), '5.0.181' );
wp_enqueue_media();
wp_enqueue_script( 'bfp-admin-js', BFP_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), '5.0.181' );
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
    'ajax_url' => admin_url('admin-ajax.php'),
    'saving_text' => __('Saving settings...', 'bandfront-player'),
    'error_text' => __('An unexpected error occurred. Please try again.', 'bandfront-player'),
    'dismiss_text' => __('Dismiss this notice', 'bandfront-player'),
));

// Get all settings using the injected config instance
$settings = $config->getAdminFormSettings();

// Handle special cases
$ffmpeg_system_path = defined( 'PHP_OS' ) && strtolower( PHP_OS ) == 'linux' && function_exists( 'shell_exec' ) ? @shell_exec( 'which ffmpeg' ) : '';

// Get available layouts and controls
$playerLayouts = $config->getPlayerLayouts();
$playerControls = $config->getPlayerControls();

?>
<h1><?php echo "\xF0\x9F\x8C\x88"; ?> <?php esc_html_e( 'Bandfront Player - Global Settings', 'bandfront-player' ); ?></h1>
<p class="bfp-tagline">a player for the storefront theme</p>

<form method="post" enctype="multipart/form-data">
<input type="hidden" name="action" value="bfp_save_settings" />
<input type="hidden" name="bfp_nonce" value="<?php echo esc_attr( wp_create_nonce( 'bfp_updating_plugin_settings' ) ); ?>" />

<div class="bfp-admin-wrapper">
    <!-- Tab Navigation -->
    <h2 class="nav-tab-wrapper bfp-nav-tab-wrapper">
        <a href="#general" class="nav-tab nav-tab-active" data-tab="general-panel">
            <?php esc_html_e('General', 'bandfront-player'); ?>
        </a>
        <a href="#player" class="nav-tab" data-tab="player-panel">
            <?php esc_html_e('Player', 'bandfront-player'); ?>
        </a>
        <a href="#demos" class="nav-tab" data-tab="demos-panel">
            <?php esc_html_e('Demos', 'bandfront-player'); ?>
        </a>
        <a href="#audio-engine" class="nav-tab" data-tab="audio-engine-panel">
            <?php esc_html_e('Audio Engine', 'bandfront-player'); ?>
        </a>
        <a href="#cloud-storage" class="nav-tab" data-tab="cloud-storage-panel">
            <?php esc_html_e('Cloud Storage', 'bandfront-player'); ?>
        </a>
        <a href="#troubleshooting" class="nav-tab" data-tab="troubleshooting-panel">
            <?php esc_html_e('Troubleshooting', 'bandfront-player'); ?>
        </a>
        <?php if ($settings['_bfp_dev_mode']) : ?>
        <a href="#database-monitor" class="nav-tab" data-tab="database-monitor-panel">
            <?php esc_html_e('Database Monitor', 'bandfront-player'); ?>
        </a>
        <a href="#dev" class="nav-tab" data-tab="dev-panel">
            <?php esc_html_e('Dev', 'bandfront-player'); ?>
        </a>
        <?php endif; ?>
    </h2>
    
    <!-- Tab Content -->
    <div class="bfp-tab-content">
        
        <!-- General Tab -->
        <div id="general-panel" class="bfp-tab-panel active">
            <h3>⚙️ <?php esc_html_e('General Settings', 'bandfront-player'); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="_bfp_require_login">👤 <?php esc_html_e( 'Registered users only', 'bandfront-player' ); ?></label></th>
                    <td>
                        <input aria-label="<?php esc_attr_e( 'Include the players only for registered users', 'bandfront-player' ); ?>" type="checkbox" id="_bfp_require_login" name="_bfp_require_login" <?php checked( $settings['_bfp_require_login'] ); ?> />
                        <p class="description"><?php esc_html_e( 'Only show audio players to logged-in users', 'bandfront-player' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">🛒 <?php esc_html_e( 'Full tracks for buyers', 'bandfront-player' ); ?></th>
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
                    <th scope="row"><label for="_bfp_purchased_times_text">📊 <?php esc_html_e( 'Purchase count text', 'bandfront-player' ); ?></label></th>
                    <td>
                        <input aria-label="<?php esc_attr_e( 'Purchased times text', 'bandfront-player' ); ?>" type="text" id="_bfp_purchased_times_text" name="_bfp_purchased_times_text" value="<?php echo esc_attr( $settings['_bfp_purchased_times_text'] ); ?>" class="regular-text" />
                        <p class="description"><?php esc_html_e( 'Text shown in playlists when displaying purchase counts (use %d for the number)', 'bandfront-player' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="_bfp_dev_mode">🔧 <?php esc_html_e( 'Developer Mode', 'bandfront-player' ); ?></label></th>
                    <td>
                        <input aria-label="<?php esc_attr_e( 'Enable developer mode', 'bandfront-player' ); ?>" type="checkbox" id="_bfp_dev_mode" name="_bfp_dev_mode" <?php checked( $settings['_bfp_dev_mode'] ); ?> />
                        <p class="description"><?php esc_html_e( 'Enable database monitoring and developer tools tabs', 'bandfront-player' ); ?></p>
                    </td>
                </tr>
                <?php do_action( 'bfp_general_settings' ); ?>
            </table>
        </div>
        
        <!-- Player Tab -->
        <div id="player-panel" class="bfp-tab-panel" style="display:none;">
            <h3>🎵 <?php esc_html_e('Player Settings', 'bandfront-player'); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="_bfp_enable_player">🎧 <?php esc_html_e( 'Enable players on all products', 'bandfront-player' ); ?></label></th>
                    <td>
                        <input aria-label="<?php esc_attr_e( 'Enable player', 'bandfront-player' ); ?>" type="checkbox" id="_bfp_enable_player" name="_bfp_enable_player" <?php checked( $settings['_bfp_enable_player'] ); ?> />
                        <p class="description"><?php esc_html_e( 'Players will show automatically for products with audio files', 'bandfront-player' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="_bfp_players_in_cart">🛒 <?php esc_html_e( 'Show players in cart', 'bandfront-player' ); ?></label></th>
                    <td>
                        <input aria-label="<?php esc_attr_e( 'Include players in cart', 'bandfront-player' ); ?>" type="checkbox" id="_bfp_players_in_cart" name="_bfp_players_in_cart" <?php checked( $settings['_bfp_players_in_cart'] ); ?> />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="_bfp_group_cart_control">📦 <?php esc_html_e( 'Merge grouped products', 'bandfront-player' ); ?></label></th>
                    <td>
                        <input aria-label="<?php esc_attr_e( 'Merge in grouped products', 'bandfront-player' ); ?>" type="checkbox" id="_bfp_group_cart_control" name="_bfp_group_cart_control" <?php checked( $settings['_bfp_group_cart_control'] ); ?> />
                        <p class="description"><?php esc_html_e( 'Show "Add to cart" buttons and quantity fields within player rows for grouped products', 'bandfront-player' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">🎨 <?php esc_html_e( 'Player appearance', 'bandfront-player' ); ?></th>
                    <td>
                        <label><input type="radio" name="_bfp_player_layout" value="dark" <?php checked( $settings['_bfp_player_layout'], 'dark' ); ?>> 🌙 <?php esc_html_e('Dark', 'bandfront-player'); ?></label><br>
                        <label><input type="radio" name="_bfp_player_layout" value="light" <?php checked( $settings['_bfp_player_layout'], 'light' ); ?>> ☀️ <?php esc_html_e('Light', 'bandfront-player'); ?></label><br>
                        <label><input type="radio" name="_bfp_player_layout" value="custom" <?php checked( $settings['_bfp_player_layout'], 'custom' ); ?>> 🎨 <?php esc_html_e('Custom', 'bandfront-player'); ?></label><br><br>
                        <label><input aria-label="<?php esc_attr_e( 'Show a single player instead of one player per audio file.', 'bandfront-player' ); ?>" name="_bfp_unified_player" type="checkbox" <?php checked( $settings['_bfp_unified_player'] ); ?> />
                        <span class="bfp-single-player-label">🎭 <?php esc_html_e( 'Single player mode (one player for all tracks)', 'bandfront-player' ); ?></span></label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="_bfp_play_all">▶️ <?php esc_html_e( 'Auto-play next track', 'bandfront-player' ); ?></label></th>
                    <td>
                        <input aria-label="<?php esc_attr_e( 'Play all', 'bandfront-player' ); ?>" type="checkbox" id="_bfp_play_all" name="_bfp_play_all" <?php checked( $settings['_bfp_play_all'] ); ?> />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="_bfp_loop">🔄 <?php esc_html_e( 'Loop tracks', 'bandfront-player' ); ?></label></th>
                    <td>
                        <input aria-label="<?php esc_attr_e( 'Loop', 'bandfront-player' ); ?>" type="checkbox" id="_bfp_loop" name="_bfp_loop" <?php checked( $settings['_bfp_loop'] ); ?> />
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="_bfp_fade_out">🎚️ <?php esc_html_e( 'Smooth fade out', 'bandfront-player' ); ?></label></th>
                    <td>
                        <input aria-label="<?php esc_attr_e( 'Apply fade out to playing audio when possible', 'bandfront-player' ); ?>" type="checkbox" id="_bfp_fade_out" name="_bfp_fade_out" <?php checked( $settings['_bfp_fade_out'] ); ?> />
                        <p class="description"><?php esc_html_e( 'Gradually fade out audio when switching tracks', 'bandfront-player' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">🎛️ <?php esc_html_e( 'Player controls', 'bandfront-player' ); ?></th>
                    <td>
                        <label><input aria-label="<?php esc_attr_e( 'Player controls', 'bandfront-player' ); ?>" type="radio" value="button" name="_bfp_player_controls" <?php checked( $settings['_bfp_player_controls'], 'button' ); ?> /> <?php esc_html_e( 'Play/pause button only', 'bandfront-player' ); ?></label><br />
                        <label><input aria-label="<?php esc_attr_e( 'Player controls', 'bandfront-player' ); ?>" type="radio" value="all" name="_bfp_player_controls" <?php checked( $settings['_bfp_player_controls'], 'all' ); ?> /> <?php esc_html_e( 'Full controls (progress bar, volume, etc.)', 'bandfront-player' ); ?></label><br />
                    </td>
                </tr>
               
            </table>
        </div>
        
        <!-- Demos Tab (formerly Security) -->
        <div id="demos-panel" class="bfp-tab-panel" style="display:none;">
            <h3>🔒 <?php esc_html_e('Create Demo Files', 'bandfront-player'); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="_bfp_play_demos">🛡️ <?php esc_html_e( 'Enable demo files', 'bandfront-player' ); ?></label></th>
                    <td>
                        <input aria-label="<?php esc_attr_e( 'Protect the file', 'bandfront-player' ); ?>" type="checkbox" id="_bfp_play_demos" name="_bfp_play_demos" <?php checked( $settings['_bfp_play_demos'] ); ?> />
                        <p class="description"><?php esc_html_e( 'Create truncated demo versions to prevent unauthorized downloading of full tracks', 'bandfront-player' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="_bfp_demo_duration_percent">📊 <?php esc_html_e( 'Demo length (% of original)', 'bandfront-player' ); ?></label></th>
                    <td>
                        <input aria-label="<?php esc_attr_e( 'Percent of audio used for protected playbacks', 'bandfront-player' ); ?>" type="number" id="_bfp_demo_duration_percent" name="_bfp_demo_duration_percent" min="0" max="100" value="<?php echo esc_attr( $settings['_bfp_demo_duration_percent'] ); ?>" /> %
                        <p class="description"><?php esc_html_e( 'How much of the original track to include in demos (e.g., 30% = first 30 seconds of a 100-second track)', 'bandfront-player' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="_bfp_demo_message">💬 <?php esc_html_e( 'Demo notice text', 'bandfront-player' ); ?></label></th>
                    <td>
                        <textarea aria-label="<?php esc_attr_e( 'Explaining that demos are partial versions of the original files', 'bandfront-player' ); ?>" id="_bfp_demo_message" name="_bfp_demo_message" class="large-text" rows="4"><?php echo esc_textarea( $settings['_bfp_demo_message'] ); ?></textarea>
                        <p class="description"><?php esc_html_e( 'Text shown next to players to explain these are preview versions', 'bandfront-player' ); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Audio Engine Tab -->
        <div id="audio-engine-panel" class="bfp-tab-panel" style="display:none;">
            <h3>⚙️ <?php esc_html_e('Audio Engine', 'bandfront-player'); ?></h3>
            <table class="form-table">
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
                    <td colspan="2"><hr /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="_bfp_ffmpeg">⚡ <?php esc_html_e( 'Use FFmpeg for demos', 'bandfront-player' ); ?></label></th>
                    <td>
                        <input aria-label="<?php esc_attr_e( 'Truncate the audio files for demo with ffmpeg', 'bandfront-player' ); ?>" type="checkbox" id="_bfp_ffmpeg" name="_bfp_ffmpeg" <?php checked( $settings['_bfp_ffmpeg'] ); ?> />
                        <p class="description"><?php esc_html_e( 'Requires FFmpeg to be installed on your server', 'bandfront-player' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="_bfp_ffmpeg_path">📁 <?php esc_html_e( 'FFmpeg path', 'bandfront-player' ); ?></label></th>
                    <td>
                        <input aria-label="<?php esc_attr_e( 'ffmpeg path', 'bandfront-player' ); ?>" type="text" id="_bfp_ffmpeg_path" name="_bfp_ffmpeg_path" value="<?php echo esc_attr( empty( $settings['_bfp_ffmpeg_path'] ) && ! empty( $ffmpeg_system_path ) ? $ffmpeg_system_path : $settings['_bfp_ffmpeg_path'] ); ?>" class="regular-text" />
                        <p class="description">Example: /usr/bin/</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="_bfp_ffmpeg_watermark">🎤 <?php esc_html_e( 'Audio watermark', 'bandfront-player' ); ?></label></th>
                    <td>
                        <input aria-label="<?php esc_attr_e( 'Watermark audio', 'bandfront-player' ); ?>" type="text" id="_bfp_ffmpeg_watermark" name="_bfp_ffmpeg_watermark" value="<?php echo esc_attr( $settings['_bfp_ffmpeg_watermark'] ); ?>" class="regular-text bfp-file-url" />
                        <input type="button" class="button-secondary bfp-select-file" value="<?php esc_attr_e( 'Select', 'bandfront-player' ); ?>" />
                        <p class="description"><?php esc_html_e( 'Optional audio file to overlay on demos', 'bandfront-player' ); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Troubleshooting Tab -->
        <div id="troubleshooting-panel" class="bfp-tab-panel" style="display:none;">
            <h3>🔧 <?php esc_html_e('Troubleshooting', 'bandfront-player'); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row">🧱 <?php esc_html_e( 'Gutenberg blocks hiding your players?', 'bandfront-player' ); ?></th>
                    <td>
                        <label>
                        <input aria-label="<?php esc_attr_e( 'For the WooCommerce Gutenberg Blocks, include the main player in the products titles', 'bandfront-player' ); ?>" type="checkbox" name="_bfp_force_main_player_in_title" <?php checked( $settings['_bfp_force_main_player_in_title'] ); ?>/>
                        <?php esc_html_e( 'Force players to appear in product titles', 'bandfront-player' ); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">🗑️ <?php esc_html_e( 'Demo files corrupted or outdated?', 'bandfront-player' ); ?></th>
                    <td>
                        <label>
                        <input aria-label="<?php esc_attr_e( 'Delete the demo files generated previously', 'bandfront-player' ); ?>" type="checkbox" name="_bfp_delete_demos" />
                        <?php esc_html_e( 'Regenerate demo files', 'bandfront-player' ); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <p class="bfp-troubleshoot-protip">💡<?php esc_html_e( 'After changing troubleshooting settings, clear your website and browser caches for best results.', 'bandfront-player' ); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        
        <?php 
        // Include cloud tools template
        include_once plugin_dir_path(__FILE__) . 'cloud-tools.php';
        
        // Include dev tools template if dev mode is enabled
        if ($settings['_bfp_dev_mode']) {
            include_once plugin_dir_path(__FILE__) . 'dev-tools.php';
        }
        ?>
        
    </div>
</div>

<p class="submit">
    <input type="submit" value="<?php esc_attr_e( 'Save settings', 'bandfront-player' ); ?>" class="button-primary" />
</p>
</form>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Tab functionality
    $('.bfp-nav-tab-wrapper .nav-tab').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation(); // Prevent event bubbling
        
        var $this = $(this);
        var target = $this.data('tab');
        
        // Update active tab
        $('.bfp-nav-tab-wrapper .nav-tab').removeClass('nav-tab-active');
        $this.addClass('nav-tab-active');
        
        // Show corresponding panel
        $('.bfp-tab-panel').hide();
        $('#' + target).show();
        
        // Update URL hash without jumping
        if (history.pushState) {
            history.pushState(null, null, '#' + target.replace('-panel', ''));
        } else {
            // Fallback for older browsers - store scroll position
            var scrollPos = $(window).scrollTop();
            window.location.hash = target.replace('-panel', '');
            $(window).scrollTop(scrollPos);
        }
        
        return false; // Prevent default anchor behavior
    });
    
    // Check for hash on load
    if (window.location.hash) {
        var hash = window.location.hash.substring(1);
        // Handle both old and new hash formats
        if (hash === 'security') {
            hash = 'demos'; // Redirect old security hash to demos
        }
        $('.bfp-nav-tab-wrapper .nav-tab[data-tab="' + hash + '-panel"]').click();
    }
    
    // Handle dev mode toggle - reload page when changed
    $('#_bfp_dev_mode').on('change', function() {
        if ($(this).closest('form').find('input[name="action"]').val() === 'bfp_save_settings') {
            // Show a notice that page will reload after save
            if (this.checked) {
                $(this).closest('td').append('<p class="bfp-dev-mode-notice" style="color: #2271b1; margin-top: 5px;"><?php esc_html_e('Developer tabs will appear after saving settings.', 'bandfront-player'); ?></p>');
            } else {
                $(this).closest('td').append('<p class="bfp-dev-mode-notice" style="color: #2271b1; margin-top: 5px;"><?php esc_html_e('Developer tabs will be hidden after saving settings.', 'bandfront-player'); ?></p>');
            }
        }
    });
});
</script>