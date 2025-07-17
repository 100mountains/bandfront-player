<?php
namespace Bandfront\Admin;

use Bandfront\Core\Config;

class Settings {
    private Config $config;

    public function __construct(Config $config) {
        $this->config = $config;
    }

    public function registerSettings(): void {
        // Register settings with WordPress
        register_setting('bandfront_player_options', 'bandfront_player_options', [$this, 'sanitize']);
    }

    public function addSettingsPage(): void {
        add_options_page(
            'Bandfront Player Settings',
            'Bandfront Player',
            'manage_options',
            'bandfront-player',
            [$this, 'renderSettingsPage']
        );
    }

    public function renderSettingsPage(): void {
        ?>
        <div class="wrap">
            <h1>Bandfront Player Settings</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('bandfront_player_options');
                do_settings_sections('bandfront_player_options');
                ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">Enable Player</th>
                        <td>
                            <input type="checkbox" name="bandfront_player_options[enable_player]" value="1" <?php checked(1, $this->config->getState('enable_player', 0)); ?> />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Audio Engine</th>
                        <td>
                            <select name="bandfront_player_options[audio_engine]">
                                <option value="html5" <?php selected($this->config->getState('audio_engine', 'html5'), 'html5'); ?>>HTML5</option>
                                <option value="mediaelement" <?php selected($this->config->getState('audio_engine', 'html5'), 'mediaelement'); ?>>MediaElement</option>
                            </select>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function sanitize(array $options): array {
        // Sanitize input options
        $options['enable_player'] = isset($options['enable_player']) ? 1 : 0;
        $options['audio_engine'] = in_array($options['audio_engine'], ['html5', 'mediaelement']) ? $options['audio_engine'] : 'html5';
        return $options;
    }
}