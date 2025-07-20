<?php
namespace Bandfront\UI;

use Bandfront\Core\Config;
use Bandfront\Core\Debug;

/**
 * WaveSurfer Renderer
 * Handles rendering of WaveSurfer.js audio players
 */
class WsRenderer {
    
    private $config;
    
    public function __construct(Config $config) {
        $this->config = $config;
    }
    
    /**
     * Render WaveSurfer player controls
     * 
     * @param array $file Audio file data
     * @param array $settings Player settings
     * @param int $productId Product ID
     * @param int $index File index
     * @return string HTML output
     */
    public function renderControls(array $file, array $settings, int $productId, $index): string {
        Debug::log('WsRenderer: Rendering WaveSurfer controls', [
        $index = (int)$index,
            'file' => $file['name'] ?? 'unknown',
            'productId' => $productId,
            'index' => $index
        ]);
        
        // Get audio URL
        $audioUrl = $file['url'] ?? '';
        $fileName = $file['name'] ?? 'Audio Track';
        $duration = isset($file['duration']) ? $this->formatDuration($file['duration']) : '0:00';
        
        // Generate unique IDs
        $playerId = 'ws-player-' . $productId . '-' . $index;
        $waveformId = 'waveform-' . $playerId;
        $controlsId = 'controls-' . $playerId;
        
        // Get player settings
        $volume = $settings['_bfp_player_volume'] ?? 1;
        $skin = $settings['_bfp_player_layout'] ?? 'dark';
        
        // Prepare template variables
        $templateVars = [
            'playerId' => $playerId,
            'waveformId' => $waveformId,
            'controlsId' => $controlsId,
            'audioUrl' => $audioUrl,
            'fileName' => $fileName,
            'duration' => $duration,
            'volume' => $volume,
            'skin' => $skin,
            'productId' => $productId,
            'index' => $index
        ];
        
        // Load template
        return $this->loadTemplate('wavesurfer-controls', $templateVars);
    }
    
    /**
     * Format duration from seconds to mm:ss
     */
    private function formatDuration($seconds): string {
        $minutes = floor($seconds / 60);
        $seconds = $seconds % 60;
        return sprintf('%d:%02d', $minutes, $seconds);
    }
    
    /**
     * Load template file
     */
    private function loadTemplate(string $template, array $vars): string {
        $templatePath = plugin_dir_path(dirname(dirname(__FILE__))) . 'templates/' . $template . '.php';
        
        if (!file_exists($templatePath)) {
            Debug::log('WsRenderer: Template not found', ['template' => $templatePath]);
            return '';
        }
        
        // Extract variables for template
        extract($vars);
        
        ob_start();
        include $templatePath;
        return ob_get_clean();
    }
}
