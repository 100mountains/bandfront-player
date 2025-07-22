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
wp_enqueue_media();
// Get all settings using the injected config instance
$settings = $config->getAdminFormSettings();
// Handle special cases
$ffmpeg_system_path = defined( 'PHP_OS' ) && strtolower( PHP_OS ) == 'linux' && function_exists( 'shell_exec' ) ? @shell_exec( 'which ffmpeg' ) : '';
// Get available layouts and controls
$playerLayouts = $config->getPlayerLayouts();
$playerControls = $config->getPlayerControls();
?>
<h1><?php echo "\xF0\x9F\x8C\x88"; ?> <?php esc_html_e( 'BΔΠD⇋FRØΠT ⇄ PLAYΞR ⫷Gl⨶bal Settings⫸ ꧂ 🛸', 'bandfront-player' ); ?></h1>
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
        <?php if ($settings['_bfp_dev_mode']) : ?>
        <a href="#database-monitor" class="nav-tab" data-tab="database-monitor-panel">
            <?php esc_html_e('Database Monitor', 'bandfront-player'); ?>
        </a>
        <a href="#dev" class="nav-tab" data-tab="dev-panel">
            <?php esc_html_e('Dev', 'bandfront-player'); ?>
        </a>
        <?php endif; ?>
        <?php if ($settings['_bfp_sndloop_mode']) : ?>
        <a href="#sndloop" class="nav-tab" data-tab="sndloop-panel">
            <?php esc_html_e('SNDLOOP', 'bandfront-player'); ?>
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
                    <th scope="row"><label for="_bfp_purchased_times_text">� <?php esc_html_e( 'Purchase count text', 'bandfront-player' ); ?></label></th>
                    <td>
                        <input aria-label="<?php esc_attr_e( 'Purchased times text', 'bandfront-player' ); ?>" type="text" id="_bfp_purchased_times_text" name="_bfp_purchased_times_text" value="<?php echo esc_attr( $settings['_bfp_purchased_times_text'] ); ?>" class="regular-text" />
                        <p class="description"><?php esc_html_e( 'Text shown in playlists when displaying purchase counts (use %d for the number)', 'bandfront-player' ); ?></p>
                    </td>
                </tr>
                <!-- Purchasers Display Settings -->
                <tr>
                    <th scope="row"><label for="_bfp_show_purchasers">👥 <?php esc_html_e( 'Show Product Purchasers', 'bandfront-player' ); ?></label></th>
                    <td>
                        <input aria-label="<?php esc_attr_e( 'Show product purchasers', 'bandfront-player' ); ?>" type="checkbox" id="_bfp_show_purchasers" name="_bfp_show_purchasers" <?php checked( $settings['_bfp_show_purchasers'] ); ?> />
                        <p class="description"><?php esc_html_e( 'Display avatars of users who purchased the product on product pages', 'bandfront-player' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="_bfp_max_purchasers_display">� <?php esc_html_e( 'Maximum Purchasers to Display', 'bandfront-player' ); ?></label></th>
                    <td>
                        <input aria-label="<?php esc_attr_e( 'Maximum purchasers to display', 'bandfront-player' ); ?>" type="number" id="_bfp_max_purchasers_display" name="_bfp_max_purchasers_display" value="<?php echo esc_attr( $settings['_bfp_max_purchasers_display'] ); ?>" min="1" max="50" step="1" class="small-text" />
                        <p class="description"><?php esc_html_e( 'Maximum number of purchaser avatars to show', 'bandfront-player' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="_bfp_dev_mode">🔧 <?php esc_html_e( 'Developer Mode', 'bandfront-player' ); ?></label></th>
                    <td>
                        <input aria-label="<?php esc_attr_e( 'Enable developer mode', 'bandfront-player' ); ?>" type="checkbox" id="_bfp_dev_mode" name="_bfp_dev_mode" <?php checked( $settings['_bfp_dev_mode'] ); ?> />
                        <p class="description"><?php esc_html_e( 'Enable database monitoring and developer tools tabs', 'bandfront-player' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="_bfp_sndloop_mode">🎵 <?php esc_html_e( 'SNDLOOP', 'bandfront-player' ); ?></label></th>
                    <td>
                        <input aria-label="<?php esc_attr_e( 'Enable SNDLOOP network integration', 'bandfront-player' ); ?>" type="checkbox" id="_bfp_sndloop_mode" name="_bfp_sndloop_mode" <?php checked( $settings['_bfp_sndloop_mode'] ); ?> />
                        <p class="description"><?php esc_html_e( 'Enable SNDLOOP decentralized music discovery network integration', 'bandfront-player' ); ?></p>
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
                        <select name="_bfp_player_layout" id="_bfp_player_layout">
                            <option value="dark" <?php selected( $settings['_bfp_player_layout'], 'dark' ); ?>>🌙 <?php esc_html_e('Dark', 'bandfront-player'); ?></option>
                            <option value="light" <?php selected( $settings['_bfp_player_layout'], 'light' ); ?>>☀️ <?php esc_html_e('Light', 'bandfront-player'); ?></option>
                            <option value="custom" <?php selected( $settings['_bfp_player_layout'], 'custom' ); ?>>🎨 <?php esc_html_e('Custom', 'bandfront-player'); ?></option>
                        </select>
                                           </td>
                </tr>
                <tr>
                    <th scope="row">🔘 <?php esc_html_e( 'Button appearance', 'bandfront-player' ); ?></th>
                    <td>
                        <select name="_bfp_button_theme" id="_bfp_button_theme">
                            <option value="dark" <?php selected( $settings['_bfp_button_theme'], 'dark' ); ?>>🌙 <?php esc_html_e('Dark', 'bandfront-player'); ?></option>
                            <option value="light" <?php selected( $settings['_bfp_button_theme'], 'light' ); ?>>☀️ <?php esc_html_e('Light', 'bandfront-player'); ?></option>
                            <option value="custom" <?php selected( $settings['_bfp_button_theme'], 'custom' ); ?>>🎨 <?php esc_html_e('Custom', 'bandfront-player'); ?></option>
                        </select>
                        <p class="description"><?php esc_html_e( 'Choose the appearance theme for player control buttons', 'bandfront-player' ); ?></p>
                        <br><br>
                        <label><input aria-label="<?php esc_attr_e( 'Show fast-forward and rewind buttons', 'bandfront-player' ); ?>" type="checkbox" name="_bfp_show_navigation_buttons" <?php checked( $settings['_bfp_show_navigation_buttons'], true ); ?> /> ⏮️⏭️ <?php esc_html_e( 'Show fast-forward and rewind buttons', 'bandfront-player' ); ?></label><br>                        <label><input aria-label="<?php esc_attr_e( 'Show a single player instead of one player per audio file.', 'bandfront-player' ); ?>" name="_bfp_unified_player" type="checkbox" <?php checked( $settings['_bfp_unified_player'] ); ?> />                        <span class="bfp-single-player-label">🎭 <?php esc_html_e( 'Single player mode (one player for all tracks)', 'bandfront-player' ); ?></span></label>

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
                    <th scope="row">🎛️ <?php esc_html_e( 'Player controls', 'bandfront-player' ); ?></th>
                    <td>
                        <label><input aria-label="<?php esc_attr_e( 'Player controls', 'bandfront-player' ); ?>" type="radio" value="button" name="_bfp_player_controls" <?php checked( $settings['_bfp_player_controls'], 'button' ); ?> /> <?php esc_html_e( 'Play/pause button only', 'bandfront-player' ); ?></label><br />
                        <label><input aria-label="<?php esc_attr_e( 'Player controls', 'bandfront-player' ); ?>" type="radio" value="all" name="_bfp_player_controls" <?php checked( $settings['_bfp_player_controls'], 'all' ); ?> /> <?php esc_html_e( 'Full controls (progress bar, volume, etc.)', 'bandfront-player' ); ?></label><br />
                    </td>
                </tr>
                <tr class="bfp-player-on-cover-row">
                    <th scope="row">📍 <?php esc_html_e( 'Show on product image', 'bandfront-player' ); ?></th>
                    <td>
                        <label>
                            <input aria-label="<?php esc_attr_e( 'Show player on product image', 'bandfront-player' ); ?>" type="checkbox" id="_bfp_player_on_cover" name="_bfp_player_on_cover" <?php checked( $settings['_bfp_player_on_cover'] ); ?> />
                            <?php esc_html_e( 'Display player controls on the product image (shop pages)', 'bandfront-player' ); ?>
                        </label>
                        <p class="description"><?php esc_html_e( 'When enabled, play/pause/track buttons will appear on the product image in shop archives', 'bandfront-player' ); ?></p>
                    </td>
                </tr>
               
            </table>
        </div>
        
        <!-- Demos Tab (formerly Security) -->
        <div id="demos-panel" class="bfp-tab-panel" style="display:none;">
            <h3>🔒 <?php esc_html_e('Demo Configuration', 'bandfront-player'); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="_bfp_demos_enabled">🛡️ <?php esc_html_e( 'Enable demo files', 'bandfront-player' ); ?></label></th>
                    <td>
                        <input aria-label="<?php esc_attr_e( 'Enable demo protection', 'bandfront-player' ); ?>" type="checkbox" id="_bfp_demos_enabled" name="_bfp_demos[enabled]" <?php checked( $settings['_bfp_demos']['enabled'] ?? false ); ?> />
                        <p class="description"><?php esc_html_e( 'Create truncated demo versions to prevent unauthorized downloading of full tracks', 'bandfront-player' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="_bfp_demos_duration_percent">📊 <?php esc_html_e( 'Demo length (% of original)', 'bandfront-player' ); ?></label></th>
                    <td>
                        <input aria-label="<?php esc_attr_e( 'Percent of audio used for demo playbacks', 'bandfront-player' ); ?>" type="number" id="_bfp_demos_duration_percent" name="_bfp_demos[duration_percent]" min="1" max="100" value="<?php echo esc_attr( $settings['_bfp_demos']['duration_percent'] ?? 50 ); ?>" /> %
                        <p class="description"><?php esc_html_e( 'How much of the original track to include in demos (e.g., 30% = first 30 seconds of a 100-second track)', 'bandfront-player' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="_bfp_demos_demo_filetype">🎵 <?php esc_html_e( 'Demo file format', 'bandfront-player' ); ?></label></th>
                    <td>
                        <select aria-label="<?php esc_attr_e( 'Output format for demo files', 'bandfront-player' ); ?>" id="_bfp_demos_demo_filetype" name="_bfp_demos[demo_filetype]">
                            <option value="mp3" <?php selected( $settings['_bfp_demos']['demo_filetype'] ?? 'mp3', 'mp3' ); ?>>MP3 (Universal compatibility)</option>
                            <option value="wav" <?php selected( $settings['_bfp_demos']['demo_filetype'] ?? 'mp3', 'wav' ); ?>>WAV (Requires FFmpeg)</option>
                            <option value="ogg" <?php selected( $settings['_bfp_demos']['demo_filetype'] ?? 'mp3', 'ogg' ); ?>>OGG (Requires FFmpeg)</option>
                            <option value="m4a" <?php selected( $settings['_bfp_demos']['demo_filetype'] ?? 'mp3', 'm4a' ); ?>>M4A (Requires FFmpeg)</option>
                            <option value="flac" <?php selected( $settings['_bfp_demos']['demo_filetype'] ?? 'mp3', 'flac' ); ?>>FLAC (Requires FFmpeg)</option>
                        </select>
                        <p class="description">
                            <?php esc_html_e( 'Output format for generated demo files. Non-MP3 formats require FFmpeg to be installed and configured.', 'bandfront-player' ); ?>
                            <?php if (!$settings['_bfp_ffmpeg']): ?>
                                <br><strong style="color: #d63384;"><?php esc_html_e( 'FFmpeg is currently disabled - only MP3 format is available.', 'bandfront-player' ); ?></strong>
                            <?php endif; ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="_bfp_demos_demo_start_time">⏰ <?php esc_html_e( 'Demo start position (%)', 'bandfront-player' ); ?></label></th>
                    <td>
                        <input aria-label="<?php esc_attr_e( 'Where in the track to start the demo', 'bandfront-player' ); ?>" type="range" id="_bfp_demos_demo_start_time" name="_bfp_demos[demo_start_time]" min="0" max="50" value="<?php echo esc_attr( $settings['_bfp_demos']['demo_start_time'] ?? 0 ); ?>" class="demo-start-slider" />
                        <span class="demo-start-value"><?php echo esc_html( $settings['_bfp_demos']['demo_start_time'] ?? 0 ); ?>%</span>
                        <p class="description"><?php esc_html_e( 'Where in the track to begin the demo (0% = beginning, 25% = quarter way through)', 'bandfront-player' ); ?></p>
                        <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const slider = document.getElementById('_bfp_demos_demo_start_time');
                            const valueDisplay = document.querySelector('.demo-start-value');
                            if (slider && valueDisplay) {
                                slider.addEventListener('input', function() {
                                    valueDisplay.textContent = this.value + '%';
                                });
                            }
                        });
                        </script>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="_bfp_demos_demo_fade">🎚️ <?php esc_html_e( 'Demo fade out (seconds)', 'bandfront-player' ); ?></label></th>
                    <td>
                        <input aria-label="<?php esc_attr_e( 'Fade out duration for demos', 'bandfront-player' ); ?>" type="number" id="_bfp_demos_demo_fade" name="_bfp_demos[demo_fade]" min="0" max="10" step="0.5" value="<?php echo esc_attr( $settings['_bfp_demos']['demo_fade'] ?? 0 ); ?>" /> <?php esc_html_e( 'seconds', 'bandfront-player' ); ?>
                        <p class="description"><?php esc_html_e( 'Gradually fade out demos at the end (0 = no fade, 3 = 3 second fade)', 'bandfront-player' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="_bfp_demos_message">💬 <?php esc_html_e( 'Demo notice text', 'bandfront-player' ); ?></label></th>
                    <td>
                        <textarea aria-label="<?php esc_attr_e( 'Explaining that demos are partial versions of the original files', 'bandfront-player' ); ?>" id="_bfp_demos_message" name="_bfp_demos[message]" class="large-text" rows="4"><?php echo esc_textarea( $settings['_bfp_demos']['message'] ?? '' ); ?></textarea>
                        <p class="description"><?php esc_html_e( 'Text shown next to players to explain these are preview versions', 'bandfront-player' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">🗑️ <?php esc_html_e( 'Demo files corrupted or outdated?', 'bandfront-player' ); ?></th>
                    <td>
                        <label>
                        <input aria-label="<?php esc_attr_e( 'Delete the demo files generated previously', 'bandfront-player' ); ?>" type="checkbox" name="_bfp_delete_demos" />
                        <?php esc_html_e( 'Regenerate demo files', 'bandfront-player' ); ?>
                        </label>
                        <p class="description"><?php esc_html_e( 'Check this box to delete existing demo files and regenerate them on next demo creation', 'bandfront-player' ); ?></p>
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
        
        <?php 
        // Include cloud tools template
        include_once plugin_dir_path(__FILE__) . 'cloud-tools.php';
        
        // Include dev tools template if dev mode is enabled
        if ($settings['_bfp_dev_mode']) {
            include_once plugin_dir_path(__FILE__) . 'dev-tools.php';
        }
        
        // Include sndloop tools template if sndloop mode is enabled
        if ($settings['_bfp_sndloop_mode']) {
        ?>
        <div id="sndloop-panel" class="bfp-tab-panel" style="display:none;">
        <?php
            include_once plugin_dir_path(__FILE__) . 'sndloop.php';
        ?>
        </div>
        <?php
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
    
    // Handle sndloop mode toggle - reload page when changed
    $('#_bfp_sndloop_mode').on('change', function() {
        if ($(this).closest('form').find('input[name="action"]').val() === 'bfp_save_settings') {
            // Show a notice that page will reload after save
            if (this.checked) {
                $(this).closest('td').append('<p class="bfp-sndloop-mode-notice" style="color: #2271b1; margin-top: 5px;"><?php esc_html_e('SNDLOOP tab will appear after saving settings.', 'bandfront-player'); ?></p>');
            } else {
                $(this).closest('td').append('<p class="bfp-sndloop-mode-notice" style="color: #2271b1; margin-top: 5px;"><?php esc_html_e('SNDLOOP tab will be hidden after saving settings.', 'bandfront-player'); ?></p>');
            }
        }
    });
});
</script>