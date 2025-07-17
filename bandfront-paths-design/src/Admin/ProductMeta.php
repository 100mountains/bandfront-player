<?php
namespace Bandfront\Admin;

use Bandfront\Core\Config;

class ProductMeta {
    
    private Config $config;

    public function __construct(Config $config) {
        $this->config = $config;
        add_action('add_meta_boxes', [$this, 'registerMetabox']);
        add_action('save_post', [$this, 'saveProductMeta'], 10, 2);
    }

    public function registerMetabox(): void {
        add_meta_box(
            'bandfront_product_meta',
            __('Audio Settings', 'bandfront-player'),
            [$this, 'renderMetabox'],
            'product',
            'side',
            'default'
        );
    }

    public function renderMetabox($post): void {
        // Render the metabox content
        $audioSettings = get_post_meta($post->ID, '_bandfront_audio_settings', true);
        ?>
        <label for="bandfront_audio_settings"><?php _e('Audio Settings', 'bandfront-player'); ?></label>
        <input type="text" id="bandfront_audio_settings" name="bandfront_audio_settings" value="<?php echo esc_attr($audioSettings); ?>" />
        <?php
    }

    public function saveProductMeta(int $postId, \WP_Post $post): void {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (isset($_POST['bandfront_audio_settings'])) {
            update_post_meta($postId, '_bandfront_audio_settings', sanitize_text_field($_POST['bandfront_audio_settings']));
        }
    }
}