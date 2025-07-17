<?php
namespace Bandfront\Admin;

use Bandfront\Core\Config;

class Columns {
    
    private Config $config;

    public function __construct(Config $config) {
        $this->config = $config;
        add_filter('manage_edit-product_columns', [$this, 'addCustomColumns']);
        add_action('manage_product_posts_custom_column', [$this, 'renderCustomColumns'], 10, 2);
    }

    public function addCustomColumns(array $columns): array {
        $columns['bfp_audio'] = __('Audio', 'bandfront-player');
        return $columns;
    }

    public function renderCustomColumns(string $column, int $postId): void {
        if ($column === 'bfp_audio') {
            // Logic to display audio information for the product
            $audioFiles = $this->config->getState('audio_files', [], $postId);
            echo !empty($audioFiles) ? implode(', ', $audioFiles) : __('No audio files', 'bandfront-player');
        }
    }
}