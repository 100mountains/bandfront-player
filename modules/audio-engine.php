<?php
/**
 * Audio Engine Module for Bandfront Player
 * Handles MediaElement.js vs WaveSurfer.js selection
 */

if (!defined('ABSPATH')) {
    exit;
}

// Hook into the global settings to display audio engine options
add_action('bfp_module_general_settings', 'bfp_audio_engine_settings');
add_action('bfp_module_product_settings', 'bfp_audio_engine_product_settings');

function bfp_audio_engine_settings() {
    global $BandfrontPlayer;
    
    // Get current audio engine setting
    $audio_engine = $BandfrontPlayer->get_global_attr('_bfp_audio_engine', 'mediaelement');
    $enable_visualizations = $BandfrontPlayer->get_global_attr('_bfp_enable_visualizations', 0);
    ?>
    <tr>
        <td colspan="2">
            <p class="bfp-engine-info"><?php esc_html_e('Choose the audio processing engine for your players. WaveSurfer.js provides enhanced features like waveforms and smoother fades.', 'bandfront-player'); ?></p>
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
                    <?php esc_html_e('Reliable HTML5 audio with broad browser compatibility', 'bandfront-player'); ?>
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

function bfp_audio_engine_product_settings($product_id) {
    global $BandfrontPlayer;
    
    // Get product-specific override if it exists
    $product_engine = get_post_meta($product_id, '_bfp_audio_engine', true);
    $global_engine = $BandfrontPlayer->get_global_attr('_bfp_audio_engine', 'mediaelement');
    
    if (empty($product_engine)) {
        $product_engine = 'global'; // Use global setting
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
                <option value="global" <?php selected($product_engine, 'global'); ?>>
                    <?php printf(esc_html__('Use Global Setting (%s)', 'bandfront-player'), ucfirst($global_engine)); ?>
                </option>
                <option value="mediaelement" <?php selected($product_engine, 'mediaelement'); ?>>
                    <?php esc_html_e('MediaElement.js', 'bandfront-player'); ?>
                </option>
                <option value="wavesurfer" <?php selected($product_engine, 'wavesurfer'); ?>>
                    <?php esc_html_e('WaveSurfer.js', 'bandfront-player'); ?>
                </option>
            </select>
            <p class="description"><?php esc_html_e('Override the global audio engine setting for this specific product', 'bandfront-player'); ?></p>
        </td>
    </tr>
    <?php
}
