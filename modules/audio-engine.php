<?php
/**
 * Audio Engine Module for Bandfront Player
 * 
 * Provides settings for selecting between MediaElement.js and WaveSurfer.js
 * audio engines, both globally and per-product.
 *
 * @package BandfrontPlayer
 * @subpackage Modules
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
add_action('bfp_module_audio_engine_product_settings', 'bfp_audio_engine_product_settings', 10, 1);

/**
 * Render audio engine global settings
 * 
 * @since 0.1
 * @param array $current_settings Current global settings array
 */
function bfp_audio_engine_settings($current_settings = array()) {
    // Get current audio engine setting with proper fallback
    $audio_engine = isset($current_settings['_bfp_audio_engine']) ? 
                    $current_settings['_bfp_audio_engine'] : 'mediaelement';
    
    $enable_visualizations = isset($current_settings['_bfp_enable_visualizations']) ? 
                             $current_settings['_bfp_enable_visualizations'] : 0;
    ?>
    <tr>
        <td colspan="2">
            <h3>🎵 <?php esc_html_e('Audio Engine Settings', 'bandfront-player'); ?></h3>
            <p class="description">
                <?php esc_html_e('Choose between MediaElement.js (traditional player) or WaveSurfer.js (modern waveform visualization).', 'bandfront-player'); ?>
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
                    <input type="radio" name="_bfp_audio_engine" id="_bfp_audio_engine_mediaelement" value="mediaelement" <?php checked($audio_engine, 'mediaelement'); ?> />
                    <strong><?php esc_html_e('MediaElement.js (Classic Player)', 'bandfront-player'); ?></strong>
                </label>
                <p class="description" style="margin-left: 25px;">
                    <?php esc_html_e('Traditional audio player with standard controls. Best for compatibility and performance.', 'bandfront-player'); ?>
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
 */
function bfp_audio_engine_product_settings($product_id) {
    $audio_engine = get_post_meta($product_id, '_bfp_audio_engine', true);
    if (empty($audio_engine)) {
        $audio_engine = 'global';
    }
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
                                <input type="radio" name="_bfp_audio_engine" value="mediaelement" <?php checked($audio_engine, 'mediaelement'); ?> />
                                <?php esc_html_e('MediaElement.js (Classic Player)', 'bandfront-player'); ?>
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
 * 
 * @since 0.1
 * @param string $engine Current engine setting
 * @param int|null $product_id Product ID if in product context
 * @return string Modified engine setting
 */
add_filter('bfp_audio_engine', function($engine, $product_id = null) {
    if ($product_id) {
        $product_engine = get_post_meta($product_id, '_bfp_audio_engine', true);
        if (!empty($product_engine) && $product_engine !== 'global') {
            return $product_engine;
        }
    }
    return $engine;
}, 10, 2);
