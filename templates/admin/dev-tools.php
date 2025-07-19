<?php
/**
 * Developer Tools Template
 * 
 * @package Bandfront\Templates\Admin
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get current debug settings
$debugConfig = $this->config->getDebugConfig();
$debugEnabled = $debugConfig['enabled'];
$debugDomains = $debugConfig['domains'];

// Define domains with labels
$availableDomains = [
    'admin' => __('Admin Operations', 'bandfront-player'),
    'bootstrap' => __('Plugin Bootstrap', 'bandfront-player'),
    'ui' => __('UI Rendering', 'bandfront-player'),
    'filemanager' => __('File Manager', 'bandfront-player'),
    'audio' => __('Audio Processing', 'bandfront-player'),
    'api' => __('REST API', 'bandfront-player'),
];
?>

<div class="wrap">
    <h1><?php _e('Developer Tools', 'bandfront-player'); ?></h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('bfp_dev_tools', 'bfp_dev_nonce'); ?>
        
        <!-- Debug Settings Section -->
        <div class="bfp-dev-section">
            <h2><?php _e('Debug Settings', 'bandfront-player'); ?></h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="debug_enabled"><?php _e('Enable Debug Mode', 'bandfront-player'); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" 
                                   id="debug_enabled" 
                                   name="debug_enabled" 
                                   value="1" 
                                   <?php checked($debugEnabled, true); ?> />
                            <?php _e('Enable debug logging', 'bandfront-player'); ?>
                        </label>
                        <p class="description">
                            <?php _e('Master switch for all debug logging. Individual domains can be enabled below.', 'bandfront-player'); ?>
                        </p>
                    </td>
                </tr>
            </table>
            
            <div id="debug-domains" style="<?php echo $debugEnabled ? '' : 'display:none;'; ?>">
                <h3><?php _e('Debug Domains', 'bandfront-player'); ?></h3>
                <p class="description"><?php _e('Enable logging for specific areas of the plugin:', 'bandfront-player'); ?></p>
                
                <div class="debug-domains-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; margin-top: 15px;">
                    <?php foreach ($availableDomains as $domain => $label): ?>
                    <label style="display: flex; align-items: center; padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                        <input type="checkbox" 
                               name="debug_domains[<?php echo esc_attr($domain); ?>]" 
                               value="1" 
                               <?php checked(!empty($debugDomains[$domain]), true); ?> />
                        <span style="margin-left: 8px;">
                            <strong><?php echo esc_html($label); ?></strong>
                            <br>
                            <code style="font-size: 11px;">Debug::<?php echo esc_html($domain); ?>()</code>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </div>
                
                <div style="margin-top: 20px; padding: 15px; background: #f0f0f1; border-radius: 4px;">
                    <h4 style="margin-top: 0;"><?php _e('Usage Examples:', 'bandfront-player'); ?></h4>
                    <pre style="background: #fff; padding: 10px; border-radius: 4px; overflow-x: auto;">
// At the top of your file, set the domain:
Debug::domain('admin');

// Then use simple logging:
Debug::log('Processing admin request', ['action' => $action]);

// Or use domain-specific methods:
Debug::admin('Processing admin request', ['action' => $action]);
Debug::ui('Rendering player', ['product_id' => $productId]);
Debug::filemanager('Uploading file', ['filename' => $filename]);</pre>
                </div>
            </div>
        </div>
        
        <!-- Existing dev tools content... -->
        
        <p class="submit">
            <input type="submit" 
                   name="save_dev_settings" 
                   class="button-primary" 
                   value="<?php esc_attr_e('Save Dev Settings', 'bandfront-player'); ?>" />
        </p>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // Toggle debug domains visibility
    $('#debug_enabled').on('change', function() {
        if ($(this).is(':checked')) {
            $('#debug-domains').slideDown();
        } else {
            $('#debug-domains').slideUp();
        }
    });
    
    // Quick enable/disable all domains
    $('<div style="margin-top: 10px;"><button type="button" class="button" id="enable-all-domains">Enable All</button> <button type="button" class="button" id="disable-all-domains">Disable All</button></div>')
        .insertAfter('#debug-domains h3');
    
    $('#enable-all-domains').on('click', function() {
        $('input[name^="debug_domains"]').prop('checked', true);
    });
    
    $('#disable-all-domains').on('click', function() {
        $('input[name^="debug_domains"]').prop('checked', false);
    });
});
</script>
