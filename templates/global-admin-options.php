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
wp_enqueue_style( 'bfp-admin-style', BFP_PLUGIN_URL . 'css/style-admin.css', array(), '5.0.181' );
wp_enqueue_style( 'bfp-admin-notices', BFP_PLUGIN_URL . 'css/admin-notices.css', array(), '5.0.181' );
wp_enqueue_media();
wp_enqueue_script( 'bfp-admin-js', BFP_PLUGIN_URL . 'js/admin.js', array('jquery'), '5.0.181' );
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

// For cloud settings, use bulk fetch
$cloud_settings = $config->getStates(array(
    '_bfp_cloud_active_tab',
    '_bfp_cloud_dropbox',
    '_bfp_cloud_s3',
    '_bfp_cloud_azure'
));

// Extract cloud settings
$cloud_active_tab = $cloud_settings['_bfp_cloud_active_tab'];
$cloud_dropbox = $cloud_settings['_bfp_cloud_dropbox'];
$cloud_s3 = $cloud_settings['_bfp_cloud_s3'];
$cloud_azure = $cloud_settings['_bfp_cloud_azure'];

// Handle special cases
$ffmpeg_system_path = defined( 'PHP_OS' ) && strtolower( PHP_OS ) == 'linux' && function_exists( 'shell_exec' ) ? @shell_exec( 'which ffmpeg' ) : '';

// Cloud Storage Settings from legacy options
$bfp_cloud_settings = get_option('_bfp_cloud_drive_addon', array());
$bfp_drive = isset($bfp_cloud_settings['_bfp_drive']) ? $bfp_cloud_settings['_bfp_drive'] : false;
$bfp_drive_key = isset($bfp_cloud_settings['_bfp_drive_key']) ? $bfp_cloud_settings['_bfp_drive_key'] : '';
$bfp_drive_api_key = get_option('_bfp_drive_api_key', '');

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
    </h2>
    
    <!-- Tab Content -->
    <div class="bfp-tab-content">
        
        <!-- General Tab -->
        <div id="general-panel" class="bfp-tab-panel active">
            <h3>⚙️ <?php esc_html_e('General Settings', 'bandfront-player'); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="_bfp_registered_only">👤 <?php esc_html_e( 'Registered users only', 'bandfront-player' ); ?></label></th>
                    <td>
                        <input aria-label="<?php esc_attr_e( 'Include the players only for registered users', 'bandfront-player' ); ?>" type="checkbox" id="_bfp_registered_only" name="_bfp_registered_only" <?php checked( $settings['_bfp_registered_only'] ); ?> />
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
                    <th scope="row"><label for="_bfp_merge_in_grouped">📦 <?php esc_html_e( 'Merge grouped products', 'bandfront-player' ); ?></label></th>
                    <td>
                        <input aria-label="<?php esc_attr_e( 'Merge in grouped products', 'bandfront-player' ); ?>" type="checkbox" id="_bfp_merge_in_grouped" name="_bfp_merge_in_grouped" <?php checked( $settings['_bfp_merge_in_grouped'] ); ?> />
                        <p class="description"><?php esc_html_e( 'Show "Add to cart" buttons and quantity fields within player rows for grouped products', 'bandfront-player' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">🎨 <?php esc_html_e( 'Player appearance', 'bandfront-player' ); ?></th>
                    <td>
                        <label><input type="radio" name="_bfp_player_layout" value="dark" <?php checked( $settings['_bfp_player_layout'], 'dark' ); ?>> 🌙 <?php esc_html_e('Dark', 'bandfront-player'); ?></label><br>
                        <label><input type="radio" name="_bfp_player_layout" value="light" <?php checked( $settings['_bfp_player_layout'], 'light' ); ?>> ☀️ <?php esc_html_e('Light', 'bandfront-player'); ?></label><br>
                        <label><input type="radio" name="_bfp_player_layout" value="custom" <?php checked( $settings['_bfp_player_layout'], 'custom' ); ?>> 🎨 <?php esc_html_e('Custom', 'bandfront-player'); ?></label><br><br>
                        <label><input aria-label="<?php esc_attr_e( 'Show a single player instead of one player per audio file.', 'bandfront-player' ); ?>" name="_bfp_single_player" type="checkbox" <?php checked( $settings['_bfp_single_player'] ); ?> />
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
                <tr>
                    <th scope="row"><label for="_bfp_on_cover">🛍️ <?php esc_html_e( 'Show players on shop pages', 'bandfront-player' ); ?></label></th>
                    <td>
                        <input aria-label="<?php esc_attr_e( 'Show players on shop/archive pages', 'bandfront-player' ); ?>" type="checkbox" id="_bfp_on_cover" name="_bfp_on_cover" <?php checked( $settings['_bfp_on_cover'] ); ?> />
                        <p class="description"><?php esc_html_e( 'Enable to display compact players on category and shop pages', 'bandfront-player' ); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Demos Tab (formerly Security) -->
        <div id="demos-panel" class="bfp-tab-panel" style="display:none;">
            <h3>🔒 <?php esc_html_e('Create Demo Files', 'bandfront-player'); ?></h3>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="_bfp_secure_player">🛡️ <?php esc_html_e( 'Enable demo files', 'bandfront-player' ); ?></label></th>
                    <td>
                        <input aria-label="<?php esc_attr_e( 'Protect the file', 'bandfront-player' ); ?>" type="checkbox" id="_bfp_secure_player" name="_bfp_secure_player" <?php checked( $settings['_bfp_secure_player'] ); ?> />
                        <p class="description"><?php esc_html_e( 'Create truncated demo versions to prevent unauthorized downloading of full tracks', 'bandfront-player' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="_bfp_file_percent">📊 <?php esc_html_e( 'Demo length (% of original)', 'bandfront-player' ); ?></label></th>
                    <td>
                        <input aria-label="<?php esc_attr_e( 'Percent of audio used for protected playbacks', 'bandfront-player' ); ?>" type="number" id="_bfp_file_percent" name="_bfp_file_percent" min="0" max="100" value="<?php echo esc_attr( $settings['_bfp_file_percent'] ); ?>" /> %
                        <p class="description"><?php esc_html_e( 'How much of the original track to include in demos (e.g., 30% = first 30 seconds of a 100-second track)', 'bandfront-player' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="_bfp_message">💬 <?php esc_html_e( 'Demo notice text', 'bandfront-player' ); ?></label></th>
                    <td>
                        <textarea aria-label="<?php esc_attr_e( 'Explaining that demos are partial versions of the original files', 'bandfront-player' ); ?>" id="_bfp_message" name="_bfp_message" class="large-text" rows="4"><?php echo esc_textarea( $settings['_bfp_message'] ); ?></textarea>
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
        
        <!-- Cloud Storage Tab -->
        <div id="cloud-storage-panel" class="bfp-tab-panel" style="display:none;">
            <h3>☁️ <?php esc_html_e('Cloud Storage', 'bandfront-player'); ?></h3>
            <p><?php esc_html_e( 'Automatically upload demo files to cloud storage to save server storage and bandwidth. Files are streamed directly from the cloud.', 'bandfront-player' ); ?></p>
            
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
                        <table class="form-table">
                            <tr>
                                <th scope="row"><label for="_bfp_drive"><?php esc_html_e( 'Store demo files on Google Drive', 'bandfront-player' ); ?></label></th>
                                <td><input aria-label="<?php esc_attr_e( 'Store demo files on Google Drive', 'bandfront-player' ); ?>" type="checkbox" id="_bfp_drive" name="_bfp_drive" <?php checked( $bfp_drive ); ?> /></td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <?php esc_html_e( 'Import OAuth Client JSON File', 'bandfront-player' ); ?><br>
                                    (<?php esc_html_e( 'Required to upload demo files to Google Drive', 'bandfront-player' ); ?>)
                                </th>
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
                                <th scope="row"><label for="_bfp_drive_api_key"><?php esc_html_e( 'API Key', 'bandfront-player' ); ?></label><br>
                                (<?php esc_html_e( 'Required to read audio files from players', 'bandfront-player' ); ?>)
                                </th>
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
});
</script>