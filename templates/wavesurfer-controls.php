<?php
/**
 * WaveSurfer Controls Template
 * 
 * Available variables:
 * - $playerId: Unique player ID
 * - $waveformId: Waveform container ID
 * - $controlsId: Controls container ID
 * - $audioUrl: Audio file URL
 * - $fileName: Audio file name
 * - $duration: Formatted duration
 * - $volume: Volume level (0-1)
 * - $skin: Player skin (dark/light/custom)
 * - $productId: Product ID
 * - $index: File index
 */

if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="bfp-ws-player-container <?php echo esc_attr($skin); ?>" data-player-id="<?php echo esc_attr($playerId); ?>">
    
    <!-- Hidden audio element for WaveSurfer -->
    <audio id="<?php echo esc_attr($playerId); ?>" 
           class="bfp-ws-audio" 
           data-product="<?php echo esc_attr($productId); ?>" 
           data-file-index="<?php echo esc_attr($index); ?>"
           data-volume="<?php echo esc_attr($volume); ?>"
           style="display: none;">
        <source src="<?php echo esc_url($audioUrl); ?>" type="audio/mpeg" />
    </audio>
    
    <!-- Waveform container -->
    <div id="<?php echo esc_attr($waveformId); ?>" class="bfp-waveform"></div>
    
    <!-- Player controls -->
    <div id="<?php echo esc_attr($controlsId); ?>" class="bfp-ws-controls">
        <div class="bfp-ws-controls-inner">
            
            <!-- Play/Pause button -->
            <button class="bfp-ws-play-pause" data-player-id="<?php echo esc_attr($playerId); ?>" aria-label="<?php esc_attr_e('Play/Pause', 'bandfront-player'); ?>">
                <span class="bfp-ws-play-icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M8 5v14l11-7z"/>
                    </svg>
                </span>
                <span class="bfp-ws-pause-icon" style="display:none;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M6 19h4V5H6v14zm8-14v14h4V5h-4z"/>
                    </svg>
                </span>
            </button>
            
            <!-- Time display -->
            <div class="bfp-ws-time">
                <span class="bfp-ws-current-time">0:00</span>
                <span class="bfp-ws-separator">/</span>
                <span class="bfp-ws-total-time"><?php echo esc_html($duration); ?></span>
            </div>
            
            <!-- Track title -->
            <div class="bfp-ws-title">
                <?php echo esc_html($fileName); ?>
            </div>
            
            <!-- Volume control -->
            <div class="bfp-ws-volume-container">
                <button class="bfp-ws-volume-button" aria-label="<?php esc_attr_e('Volume', 'bandfront-player'); ?>">
                    <svg class="bfp-ws-volume-icon" width="24" height="24" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M3 9v6h4l5 5V4L7 9H3zm13.5 3c0-1.77-1.02-3.29-2.5-4.03v8.05c1.48-.73 2.5-2.25 2.5-4.02zM14 3.23v2.06c2.89.86 5 3.54 5 6.71s-2.11 5.85-5 6.71v2.06c4.01-.91 7-4.49 7-8.77s-2.99-7.86-7-8.77z"/>
                    </svg>
                    <svg class="bfp-ws-volume-mute-icon" width="24" height="24" viewBox="0 0 24 24" fill="currentColor" style="display:none;">
                        <path d="M16.5 12c0-1.77-1.02-3.29-2.5-4.03v2.21l2.45 2.45c.03-.2.05-.41.05-.63zm2.5 0c0 .94-.2 1.82-.54 2.64l1.51 1.51C20.63 14.91 21 13.5 21 12c0-4.28-2.99-7.86-7-8.77v2.06c2.89.86 5 3.54 5 6.71zM4.27 3L3 4.27 7.73 9H3v6h4l5 5v-6.73l4.25 4.25c-.67.52-1.42.93-2.25 1.18v2.06c1.38-.31 2.63-.95 3.69-1.81L19.73 21 21 19.73l-9-9L4.27 3zM12 4L9.91 6.09 12 8.18V4z"/>
                    </svg>
                </button>
                <input type="range" 
                       class="bfp-ws-volume-slider" 
                       min="0" 
                       max="100" 
                       value="<?php echo esc_attr($volume * 100); ?>" 
                       aria-label="<?php esc_attr_e('Volume slider', 'bandfront-player'); ?>">
            </div>
            
        </div>
    </div>
    
</div>
