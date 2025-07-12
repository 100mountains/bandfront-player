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
        <td class="bfp-column-40">
            <label for="_bfp_audio_engine">
                <?php esc_html_e('Audio Engine', 'bandfront-player'); ?>
            </label>
        </td>
        <td>
            <select name="_bfp_audio_engine" id="_bfp_audio_engine" aria-label="<?php esc_attr_e('Select audio engine', 'bandfront-player'); ?>">
                <option value="mediaelement" <?php selected($audio_engine, 'mediaelement'); ?>>
                    <?php esc_html_e('MediaElement.js (Classic Player)', 'bandfront-player'); ?>
                </option>
                <option value="wavesurfer" <?php selected($audio_engine, 'wavesurfer'); ?>>
                    <?php esc_html_e('WaveSurfer.js (Waveform Visualization)', 'bandfront-player'); ?>
                </option>
            </select>
            <p class="description">
                <?php esc_html_e('MediaElement.js provides a traditional audio player interface. WaveSurfer.js shows audio waveforms.', 'bandfront-player'); ?>
            </p>
        </td>
    </tr>
    
    <tr class="bfp-wavesurfer-options" <?php echo ($audio_engine !== 'wavesurfer') ? 'style="display:none;"' : ''; ?>>
        <td class="bfp-column-40">
            <label for="_bfp_enable_visualizations">
                <?php esc_html_e('Enable Visualizations', 'bandfront-player'); ?>
            </label>
        </td>
        <td>
            <input type="checkbox" 
                   name="_bfp_enable_visualizations" 
                   id="_bfp_enable_visualizations" 
                   value="1" 
                   <?php checked($enable_visualizations, 1); ?> />
            <label for="_bfp_enable_visualizations">
                <?php esc_html_e('Show real-time frequency visualization while playing', 'bandfront-player'); ?>
            </label>
            <p class="description">
                <?php esc_html_e('Displays animated frequency bars during playback (WaveSurfer.js only).', 'bandfront-player'); ?>
            </p>
        </td>
    </tr>
    
    <script>
    jQuery(document).ready(function($) {
        $('#_bfp_audio_engine').on('change', function() {
            if ($(this).val() === 'wavesurfer') {
                $('.bfp-wavesurfer-options').show();
            } else {
                $('.bfp-wavesurfer-options').hide();
                $('#_bfp_enable_visualizations').prop('checked', false);
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
    global $BandfrontPlayer;
    
    // Get the actual values properly
    $product_engine = get_post_meta($product_id, '_bfp_audio_engine', true);
    $global_engine = $BandfrontPlayer->get_state('_bfp_audio_engine', 'mediaelement');
    
    // Determine display value - if empty, invalid, or 'global', show 'global'
    $display_value = 'global';
    if (!empty($product_engine) && in_array($product_engine, array('mediaelement', 'wavesurfer'))) {
        $display_value = $product_engine;
    }
    
    ?>
    <tr>
        <td colspan="2"><h3>🎚️ <?php esc_html_e('Audio Engine Override', 'bandfront-player'); ?></h3></td>
    </tr>
    <tr>
        <td class="bfp-column-30">
            <?php esc_html_e('Audio Engine for this Product', 'bandfront-player'); ?>
        </td>
        <td>
            <select name="_bfp_audio_engine" aria-label="<?php esc_attr_e('Audio engine for this product', 'bandfront-player'); ?>">
                <option value="global" <?php selected($display_value, 'global'); ?>>
                    <?php printf(esc_html__('Use Global Setting (%s)', 'bandfront-player'), ucfirst($global_engine)); ?>
                </option>
                <option value="mediaelement" <?php selected($display_value, 'mediaelement'); ?>>
                    <?php esc_html_e('MediaElement.js', 'bandfront-player'); ?>
                </option>
                <option value="wavesurfer" <?php selected($display_value, 'wavesurfer'); ?>>
                    <?php esc_html_e('WaveSurfer.js', 'bandfront-player'); ?>
                </option>
            </select>
            <p class="description"><?php esc_html_e('Override the global audio engine setting for this specific product', 'bandfront-player'); ?></p>
        </td>
    </tr>
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
