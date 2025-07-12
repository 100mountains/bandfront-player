<?php
/**
 * Audio Engine Module for Bandfront Player
 * Handles MediaElement.js vs WaveSurfer.js selection
 *
 * @package BandfrontPlayer
 * @subpackage Modules
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Hook into the global settings to display audio engine options
add_action('bfp_module_general_settings', 'bfp_audio_engine_settings');
add_action('bfp_module_product_settings', 'bfp_audio_engine_product_settings');

/**
 * Renders audio engine options on the general settings page
 * 
 * Allows selection between MediaElement.js and WaveSurfer.js
 * with additional visualization options for WaveSurfer
 *
 * @since 1.0.0
 * @global object $BandfrontPlayer Main plugin instance
 * @return void
 */
function bfp_audio_engine_settings() {
    global $BandfrontPlayer;
    
    // Use the new state handler
    $audio_engine = $BandfrontPlayer->get_config()->get_state('_bfp_audio_engine');
    $enable_visualizations = $BandfrontPlayer->get_config()->get_state('_bfp_enable_visualizations');
    ?>
    <tr>
        <td colspan="2">
            <h3>🎵 <?php esc_html_e('Audio Engine Selection', 'bandfront-player'); ?></h3>
        </td>
    </tr>
    <tr>
        <td class="bfp-column-30">
            <?php esc_html_e('Audio Engine', 'bandfront-player'); ?>
        </td>
        <td>
            <fieldset>
                <legend class="screen-reader-text"><?php esc_html_e('Audio Engine Selection', 'bandfront-player'); ?></legend>
                
                <label style="display: block; margin-bottom: 10px;">
                    <input type="radio" 
                           name="_bfp_audio_engine" 
                           value="mediaelement" 
                           <?php checked($audio_engine, 'mediaelement'); ?>
                           aria-describedby="mediaelement-desc" />
                    <?php esc_html_e('MediaElement.js (Default)', 'bandfront-player'); ?>
                </label>
                <p id="mediaelement-desc" class="description" style="margin-left: 25px; margin-bottom: 15px; color: #666; font-style: italic;">
                    <?php esc_html_e('Lightweight HTML5 player with broad browser support', 'bandfront-player'); ?>
                </p>
                
                <label style="display: block;">
                    <input type="radio" 
                           name="_bfp_audio_engine" 
                           value="wavesurfer" 
                           <?php checked($audio_engine, 'wavesurfer'); ?>
                           aria-describedby="wavesurfer-desc" />
                    <?php esc_html_e('WaveSurfer.js (Experimental)', 'bandfront-player'); ?>
                </label>
                <p id="wavesurfer-desc" class="description" style="margin-left: 25px; color: #666; font-style: italic;">
                    <?php esc_html_e('Web Audio API with waveforms and enhanced effects', 'bandfront-player'); ?>
                </p>
                
                <div id="wavesurfer-options" style="margin-left: 25px; margin-top: 15px; display: <?php echo ($audio_engine === 'wavesurfer') ? 'block' : 'none'; ?>;">
                    <label>
                        <input type="checkbox" 
                               name="_bfp_enable_visualizations"
                               value="1"
                               <?php checked($enable_visualizations, 1); ?> />
                        <?php esc_html_e('Enable waveform visualizations', 'bandfront-player'); ?>
                    </label>
                    <p class="description" style="margin-top: 5px; color: #666; font-style: italic;">
                        <?php esc_html_e('Show visual waveforms for audio tracks (may impact performance)', 'bandfront-player'); ?>
                    </p>
                </div>
            </fieldset>
        </td>
    </tr>
    
    <script>
    jQuery(document).ready(function($) {
        $('input[name="_bfp_audio_engine"]').change(function() {
            var engine = $(this).val();
            $('#wavesurfer-options').toggle(engine === 'wavesurfer');
        });
    });
    </script>
    <?php
}

/**
 * Renders product-specific audio engine settings
 * 
 * Allows overriding the global audio engine setting for individual products
 *
 * @since 1.0.0
 * @param int $product_id WooCommerce product ID
 * @global object $BandfrontPlayer Main plugin instance
 * @return void
 */

function bfp_audio_engine_product_settings($product_id) {
    global $BandfrontPlayer;
    
    // Use the new state handler with context
    $product_engine = $BandfrontPlayer->get_config()->get_state('_bfp_audio_engine', null, $product_id);
    $global_engine = $BandfrontPlayer->get_config()->get_state('_bfp_audio_engine');
    
    // Check if this is actually a product override
    $has_override = metadata_exists('post', $product_id, '_bfp_audio_engine');
    $display_value = $has_override ? $product_engine : 'global';
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
