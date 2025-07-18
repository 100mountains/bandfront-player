<?php
/**
 * Audio Engine Settings Template for Bandfront Player
 * 
 * This template provides:
 * - Audio engine selection UI for global settings
 * - Audio engine selection UI for product settings
 * - Integration with the state management system
 *
 * @package BandfrontPlayer
 * @subpackage Views
 * @since 0.1
 */


if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Register audio engine settings section
 */
add_action('bfp_module_audio_engine_settings', 'bfp_audio_engine_settings');

/**
 * Register product-specific audio engine settings
 */
add_action('bfp_module_audio_engine_product_settings', 'bfp_audio_engine_product_settings', 10, 2);

/**
 * Render audio engine global settings
 * 
 * @since 0.1
 * @param array $current_settings Current global settings array from state manager
 */
function bfp_audio_engine_settings($current_settings = []) {
    // Settings are already provided by the state manager
    $audio_engine = $current_settings['_bfp_audio_engine'] ?? 'html5';  // Changed default from 'mediaelement' to 'html5'
    $enable_visualizations = $current_settings['_bfp_enable_visualizations'] ?? 0;
    ?>
    <tr>
        <td colspan="2">
            <h3>🎵 <?php esc_html_e('Audio Engine Settings', 'bandfront-player'); ?></h3>
            <p class="description">
                <?php esc_html_e('Choose between MediaElement.js (traditional player), WaveSurfer.js (modern waveform visualization), or HTML 5 (native browser audio).', 'bandfront-player'); ?>
            </p>
        </td>
    </tr>
    
    <tr>
        <td class="bfp-column-30">
            <label for="_bfp_audio_engine_mediaelement">
                <?php esc_html_e('Audio Engine', 'bandfront-player'); ?>
            </label>
        </td>
        <td>
                <div style="margin-bottom: 10px;">
                <label>
                    <input type="radio" name="_bfp_audio_engine" id="_bfp_audio_engine_html5" value="html5" <?php checked($audio_engine, 'html5'); ?> />
                    <strong><?php esc_html_e('HTML 5 (Native Browser Audio)', 'bandfront-player'); ?></strong>
                </label>
                <p class="description" style="margin-left: 25px;">
                    <?php esc_html_e('Uses the browser\'s built-in audio element. Fastest, but minimal features.', 'bandfront-player'); ?>
                </p>
            </div>
            <div style="margin-bottom: 10px;">
                <label>
                    <input type="radio" name="_bfp_audio_engine" id="_bfp_audio_engine_mediaelement" value="mediaelement" <?php checked($audio_engine, 'mediaelement'); ?> />
                    <strong><?php esc_html_e('MediaElement.js (Word Press Built In)', 'bandfront-player'); ?></strong>
                </label>
                <p class="description" style="margin-left: 25px;">
                    <?php esc_html_e('Traditional audio p layer with standard controls. Best for compatibility and performance.', 'bandfront-player'); ?>
                </p>
            </div>
        
            <div>
                <label>
                    <input type="radio" name="_bfp_audio_engine" id="_bfp_audio_engine_wavesurfer" value="wavesurfer" <?php checked($audio_engine, 'wavesurfer'); ?> />
                    <strong><?php esc_html_e('WaveSurfer.js (Waveform Visualization)', 'bandfront-player'); ?></strong>
                </label>
                <p class="description" style="margin-left: 25px;">
                    <?php esc_html_e('Modern player with visual waveforms. Creates an engaging listening experience.', 'bandfront-player'); ?>
                </p>
            </div>
        </td>
    </tr>
    <tr class="bfp-wavesurfer-options" style="<?php echo $audio_engine === 'wavesurfer' ? '' : 'display: none;'; ?>">
        <td class="bfp-column-30">
            <label for="_bfp_enable_visualizations">
                <?php esc_html_e('Visualizations', 'bandfront-player'); ?>
            </label>
        </td>
        <td>
            <label>
                <input type="checkbox" name="_bfp_enable_visualizations" id="_bfp_enable_visualizations" value="1" <?php checked($enable_visualizations, 1); ?> />
                <?php esc_html_e('Enable real-time frequency visualizations', 'bandfront-player'); ?>
            </label>
            <p class="description">
                <?php esc_html_e('Shows animated frequency bars while audio plays. May impact performance on slower devices.', 'bandfront-player'); ?>
            </p>
        </td>
    </tr>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Handle audio engine radio button changes
        $('input[name="_bfp_audio_engine"]').on('change', function() {
            if ($(this).val() === 'wavesurfer') {
                $('.bfp-wavesurfer-options').show();
            } else {
                $('.bfp-wavesurfer-options').hide();
            }
        });
    });
    </script>
    <?php
}

/**
 * Render product-specific audio engine settings
 * 
 * @since 0.1
 * @param int $product_id The product ID
 * @param array $settings Current settings from state manager
 */
function bfp_audio_engine_product_settings($product_id, $settings = []) {
    // Use state manager to get the setting
    global $BandfrontPlayer;
    $audio_engine = $BandfrontPlayer->getConfig()->getState('_bfp_audio_engine', 'global', $product_id);
    ?>
    <table class="widefat bfp-table-noborder" style="margin-top: 20px;">
        <tr>
            <td>
                <table class="widefat bfp-settings-table">
                    <tr>
                        <td colspan="2"><h2>⚙️ <?php esc_html_e('Audio Engine', 'bandfront-player'); ?></h2></td>
                    </tr>
                    <tr>
                        <td width="30%"><?php esc_html_e('Player Engine', 'bandfront-player'); ?></td>
                        <td>
                            <label>
                                <input type="radio" name="_bfp_audio_engine" value="global" <?php checked($audio_engine, 'global'); ?> />
                                <?php esc_html_e('Use global setting', 'bandfront-player'); ?>
                            </label><br>
                            <label>
                                <input type="radio" name="_bfp_audio_engine" value="html5" <?php checked($audio_engine, 'html5'); ?> />
                                <?php esc_html_e('HTML 5 (Native Browser Audio)', 'bandfront-player'); ?>
                            </label><br>
                            <label>
                                <input type="radio" name="_bfp_audio_engine" value="mediaelement" <?php checked($audio_engine, 'mediaelement'); ?> />
                                <?php esc_html_e('MediaElement.js (Word Press Built In)', 'bandfront-player'); ?>
                            </label><br>

                            <label>
                                <input type="radio" name="_bfp_audio_engine" value="wavesurfer" <?php checked($audio_engine, 'wavesurfer'); ?> />
                                <?php esc_html_e('WaveSurfer.js (Waveform Visualization)', 'bandfront-player'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
    <?php
}

/**
 * Filter to modify audio engine based on context
 * Uses state management system for consistency
 * 
 * @since 0.1
 * @param string $engine Current engine setting
 * @param int|null $product_id Product ID if in product context
 * @return string Modified engine setting
 */
add_filter('bfp_audio_engine', function($engine, $product_id = null) {
    global $BandfrontPlayer;
    
    if ($product_id) {
        // State manager handles the inheritance logic
        return $BandfrontPlayer->getConfig()->getState('_bfp_audio_engine', $engine, $product_id);
    }
    
    return $engine;
}, 10, 2);

// Look for wp_enqueue_style calls and update paths from /css/ to /assets/css/
// Change any instances of:
// plugins_url('css/style-admin.css', dirname(__FILE__))
// plugins_url('css/admin-notices.css', dirname(__FILE__))

// To:
// plugins_url('assets/css/style-admin.css', dirname(__FILE__))
// plugins_url('assets/css/admin-notices.css', dirname(__FILE__))