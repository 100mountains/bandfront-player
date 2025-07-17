<?php
// Admin Settings Page for Bandfront Player

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

use Bandfront\Core\Bootstrap;

$bootstrap = Bootstrap::getInstance();
$config = $bootstrap->getConfig();

?>

<div class="wrap">
    <h1><?php esc_html_e('Bandfront Player Settings', 'bandfront-player'); ?></h1>
    
    <form method="post" action="options.php">
        <?php
        settings_fields('bandfront_player_options_group');
        do_settings_sections('bandfront_player_options_group');
        ?>
        
        <table class="form-table">
            <tr valign="top">
                <th scope="row"><?php esc_html_e('Enable Player', 'bandfront-player'); ?></th>
                <td>
                    <input type="checkbox" name="_bfp_enable_player" value="1" <?php checked($config->getState('_bfp_enable_player', true)); ?> />
                    <label for="_bfp_enable_player"><?php esc_html_e('Enable the audio player on the site', 'bandfront-player'); ?></label>
                </td>
            </tr>
            
            <tr valign="top">
                <th scope="row"><?php esc_html_e('Audio Engine', 'bandfront-player'); ?></th>
                <td>
                    <select name="_bfp_audio_engine">
                        <option value="html5" <?php selected($config->getState('_bfp_audio_engine', 'html5')); ?>><?php esc_html_e('HTML5', 'bandfront-player'); ?></option>
                        <option value="mediaelement" <?php selected($config->getState('_bfp_audio_engine', 'mediaelement')); ?>><?php esc_html_e('MediaElement.js', 'bandfront-player'); ?></option>
                    </select>
                    <p class="description"><?php esc_html_e('Choose the audio engine to use for playback.', 'bandfront-player'); ?></p>
                </td>
            </tr>
            
            <tr valign="top">
                <th scope="row"><?php esc_html_e('Player Layout', 'bandfront-player'); ?></th>
                <td>
                    <select name="_bfp_player_layout">
                        <option value="dark" <?php selected($config->getState('_bfp_player_layout', 'dark')); ?>><?php esc_html_e('Dark', 'bandfront-player'); ?></option>
                        <option value="light" <?php selected($config->getState('_bfp_player_layout', 'light')); ?>><?php esc_html_e('Light', 'bandfront-player'); ?></option>
                        <option value="custom" <?php selected($config->getState('_bfp_player_layout', 'custom')); ?>><?php esc_html_e('Custom', 'bandfront-player'); ?></option>
                    </select>
                    <p class="description"><?php esc_html_e('Select the layout for the audio player.', 'bandfront-player'); ?></p>
                </td>
            </tr>
        </table>
        
        <?php submit_button(); ?>
    </form>
</div>