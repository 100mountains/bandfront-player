<?php
/**
 * SNDLOOP Network Integration Template
 * 
 * This template provides SNDLOOP decentralized music discovery platform settings
 * Only shown when SNDLOOP mode is enabled
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
    <h3>🎵 <?php esc_html_e('SNDLOOP Network Integration', 'bandfront-player'); ?></h3>
    
    <div class="bfp-section">
        <div class="bfp-info-box">
            <h4><?php esc_html_e('About SNDLOOP', 'bandfront-player'); ?></h4>
            <p><?php esc_html_e('SNDLOOP is a decentralized music discovery platform that aggregates independent music websites into a unified network. Artists maintain their own websites/stores but gain network-wide discovery. Users can browse, stream previews, and purchase music across the entire network through a single interface.', 'bandfront-player'); ?></p>
        </div>
        
        <h4><?php esc_html_e('Network Integration Settings', 'bandfront-player'); ?></h4>
        
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e('SNDLOOP Discovery', 'bandfront-player'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="_bfp_sndloop_discovery" <?php checked($settings['_bfp_sndloop_discovery'] ?? false); ?> />
                        <?php esc_html_e('Enable SNDLOOP discovery', 'bandfront-player'); ?>
                    </label>
                    <p class="description">
                        <?php esc_html_e('Allow your music to be discovered through the SNDLOOP network', 'bandfront-player'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Send Products', 'bandfront-player'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="_bfp_sndloop_send_products" <?php checked($settings['_bfp_sndloop_send_products'] ?? false); ?> />
                        <?php esc_html_e('Send products to SNDLOOP', 'bandfront-player'); ?>
                    </label>
                    <p class="description">
                        <?php esc_html_e('Share your music products with the SNDLOOP network for discovery', 'bandfront-player'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e('Send Merch', 'bandfront-player'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="_bfp_sndloop_send_merch" <?php checked($settings['_bfp_sndloop_send_merch'] ?? false); ?> />
                        <?php esc_html_e('Send merch to SNDLOOP', 'bandfront-player'); ?>
                    </label>
                    <p class="description">
                        <?php esc_html_e('Share your merchandise with the SNDLOOP network for discovery', 'bandfront-player'); ?>
                    </p>
                </td>
            </tr>
        </table>
        
        <div class="bfp-network-info">
            <h4><?php esc_html_e('How SNDLOOP Works:', 'bandfront-player'); ?></h4>
            <ul>
                <li><?php esc_html_e('🌐 Decentralized network of independent music websites', 'bandfront-player'); ?></li>
                <li><?php esc_html_e('🎵 Artists keep full control of their own sites and stores', 'bandfront-player'); ?></li>
                <li><?php esc_html_e('🔍 Network-wide discovery without losing independence', 'bandfront-player'); ?></li>
                <li><?php esc_html_e('🛒 Users can browse and purchase across the entire network', 'bandfront-player'); ?></li>
                <li><?php esc_html_e('💰 Revenue stays with the original artist/store', 'bandfront-player'); ?></li>
            </ul>
        </div>
        
        <div class="bfp-troubleshooting-section">
            <h4><?php esc_html_e('Troubleshooting Settings', 'bandfront-player'); ?></h4>
            
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
        </div>
    </div>
</div>

<style>
.bfp-sndloop-tools .bfp-section {
    margin-bottom: 30px;
}

.bfp-info-box, .bfp-network-info, .bfp-troubleshooting-section {
    padding: 15px;
    margin: 20px 0;
    border-radius: 5px;
}

.bfp-info-box {
    background: #e7f3ff;
    border-left: 4px solid #0073aa;
}

.bfp-network-info {
    background: #f0f9ff;
    border-left: 4px solid #1e40af;
}

.bfp-troubleshooting-section {
    background: #fff8e1;
    border-left: 4px solid #ffb300;
    margin-top: 30px;
}

.bfp-info-box h4, .bfp-network-info h4, .bfp-troubleshooting-section h4 {
    margin-top: 0;
    color: #333;
}

.bfp-network-info ul {
    margin: 10px 0;
    padding-left: 20px;
}

.bfp-network-info li {
    margin-bottom: 8px;
}

.bfp-sndloop-tools .form-table th {
    width: 200px;
    font-weight: 600;
}
</style>
