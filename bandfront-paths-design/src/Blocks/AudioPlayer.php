<?php
declare(strict_types=1);

namespace Bandfront\Blocks;

use Bandfront\Core\Bootstrap;

class AudioPlayer {
    
    public function __construct() {
        add_action('init', [$this, 'registerBlock']);
    }
    
    public function registerBlock(): void {
        // Register the audio player block
        register_block_type('bandfront/audio-player', [
            'render_callback' => [$this, 'renderBlock'],
            'attributes'      => [
                'audioUrl' => [
                    'type' => 'string',
                ],
                'title'    => [
                    'type' => 'string',
                ],
            ],
        ]);
    }
    
    public function renderBlock(array $attributes): string {
        $audioUrl = esc_url($attributes['audioUrl'] ?? '');
        $title    = esc_html($attributes['title'] ?? 'Audio Player');

        ob_start();
        ?>
        <div class="bandfront-audio-player">
            <h3><?php echo $title; ?></h3>
            <audio controls>
                <source src="<?php echo $audioUrl; ?>" type="audio/mpeg">
                Your browser does not support the audio element.
            </audio>
        </div>
        <?php
        return ob_get_clean();
    }
}