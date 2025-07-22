<?php
/**
 * Sndloop Tools Template
 * 
 * This template provides troubleshooting and sndloop tools
 * Only shown when sndloop mode is enabled
 *
 * @package BandfrontPlayer
 * @subpackage Views
 * @since 2.3.6
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check if sndloop mode is enabled
$sndloopMode = $config->getState('_bfp_sndloop_mode', 0);

if (!$sndloopMode) {
    return; // Don't render anything if sndloop mode is off
}

?>

<div class="bfp-sndloop-tools">
    <h3>🔧 <?php esc_html_e('Sndloop Troubleshooting Tools', 'bandfront-player'); ?></h3>
    
    <div class="bfp-section">
        <h4><?php esc_html_e('Audio Processing Troubleshooting', 'bandfront-player'); ?></h4>
        
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e('Load players on page load', 'bandfront-player'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="_bfp_onload" <?php checked($settings['_bfp_onload'] ?? false); ?> />
                        <?php esc_html_e('Enable onload troubleshooting', 'bandfront-player'); ?>
                    </label>
                    <p class="description">
                        <?php esc_html_e('Load all audio players immediately when the page loads (helps with some theme compatibility issues)', 'bandfront-player'); ?>
                    </p>
                </td>
            </tr>
        </table>
        
        <div class="bfp-info-box">
            <h4><?php esc_html_e('About Sndloop Mode', 'bandfront-player'); ?></h4>
            <p><?php esc_html_e('Sndloop mode provides advanced troubleshooting tools for audio processing and player functionality. This mode is designed for developers and advanced users who need to diagnose player issues.', 'bandfront-player'); ?></p>
            
            <h5><?php esc_html_e('Troubleshooting Tips:', 'bandfront-player'); ?></h5>
            <ul>
                <li><?php esc_html_e('Enable "Load players on page load" if players don\'t appear on certain themes', 'bandfront-player'); ?></li>
                <li><?php esc_html_e('Clear browser cache after making changes', 'bandfront-player'); ?></li>
                <li><?php esc_html_e('Check browser console for JavaScript errors', 'bandfront-player'); ?></li>
                <li><?php esc_html_e('Test with different audio engines in the Audio Engine tab', 'bandfront-player'); ?></li>
            </ul>
        </div>
        
        <div class="bfp-warning-box">
            <h4><?php esc_html_e('Performance Note', 'bandfront-player'); ?></h4>
            <p><?php esc_html_e('Some sndloop troubleshooting options may impact page loading performance. Disable them once issues are resolved.', 'bandfront-player'); ?></p>
        </div>
    </div>
</div>

<style>
.bfp-sndloop-tools .bfp-section {
    margin-bottom: 30px;
}

.bfp-info-box, .bfp-warning-box {
    padding: 15px;
    margin: 20px 0;
    border-radius: 5px;
}

.bfp-info-box {
    background: #e7f3ff;
    border-left: 4px solid #0073aa;
}

.bfp-warning-box {
    background: #fff8e1;
    border-left: 4px solid #ffb300;
}

.bfp-info-box h4, .bfp-warning-box h4 {
    margin-top: 0;
    color: #333;
}

.bfp-info-box h5 {
    margin: 15px 0 10px 0;
    color: #0073aa;
}

.bfp-info-box ul {
    margin: 10px 0;
    padding-left: 20px;
}

.bfp-info-box li {
    margin-bottom: 8px;
}
</style>
