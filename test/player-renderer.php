<?php
/**
 * Player Renderer for Bandfront Player
 *
 * @package BandfrontPlayer
 * @since 0.1
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * BFP Player Renderer Class
 * Handles complex player layout rendering and HTML generation
 */
class BFP_Player_Renderer {
    
    private $main_plugin;
    
    public function __construct($main_plugin) {
        $this->main_plugin = $main_plugin;
    }
    
    /**
     * Render player table layout for multiple files
     * This is a helper method for complex layouts only
     */
    public function render_player_table($files, $product_id, $settings) {
        if (empty($files) || count($files) < 2) {
            return '';
        }
        
        $output = '';
        $merge_grouped_clss = ($settings['_bfp_merge_in_grouped']) ? 'merge_in_grouped_products' : '';
        $single_player = intval($this->main_plugin->get_config()->get_state('_bfp_single_player', 0, $product_id));
        
        $output .= '<table class="bfp-player-list ' . $merge_grouped_clss . ($single_player ? ' bfp-single-player ' : '') . '" ' . 
                   ($settings['_bfp_loop'] ? 'data-loop="1"' : '') . '>';
        
        $counter = count($files);
        $first_player_class = 'bfp-first-player';
        
        foreach ($files as $index => $file) {
            $evenOdd = (1 == $counter % 2) ? 'bfp-odd-row' : 'bfp-even-row';
            $counter--;
            
            $audio_url = $this->main_plugin->get_audio_core()->generate_audio_url($product_id, $index, $file);
            $duration = $this->main_plugin->get_audio_core()->get_duration_by_url($file['file']);
            
            $audio_tag = apply_filters(
                'bfp_audio_tag',
                $this->main_plugin->get_player()->get_player(
                    $audio_url,
                    array(
                        'product_id'      => $product_id,
                        'player_style'    => $settings['_bfp_player_layout'],
                        'player_controls' => $settings['player_controls'],
                        'media_type'      => $file['media_type'],
                        'duration'        => $duration,
                        'preload'         => $settings['_bfp_preload'],
                        'volume'          => $settings['_bfp_player_volume'],
                    )
                ),
                $product_id,
                $index,
                $audio_url
            );
            
            $title = esc_html(($settings['_bfp_player_title']) ? 
                     apply_filters('bfp_file_name', $file['name'], $product_id, $index) : '');
            
            $output .= $this->render_player_row($audio_tag, $title, $duration, $evenOdd, 
                                               $file['product'], $first_player_class, 
                                               $counter, $settings, $single_player);
            
            $first_player_class = '';
        }
        
        $output .= '</table>';
        
        return $output;
    }
    
    /**
     * Render a single player row
     */
    private function render_player_row($audio_tag, $title, $duration, $evenOdd, 
                                      $product_id, $first_player_class, $counter, 
                                      $settings, $single_player) {
        $output = '<tr class="' . esc_attr($evenOdd) . ' product-' . esc_attr($product_id) . '">';
        
        if ('all' != $settings['player_controls']) {
            $output .= '<td class="bfp-column-player-' . esc_attr($settings['_bfp_player_layout']) . '">';
            $output .= '<div class="bfp-player-container ' . $first_player_class . '" data-player-id="' . esc_attr($counter) . '">';
            $output .= $audio_tag;
            $output .= '</div></td>';
            $output .= '<td class="bfp-player-title bfp-column-player-title" data-player-id="' . esc_attr($counter) . '">';
            $output .= wp_kses_post($title);
            $output .= '</td>';
            $output .= '<td class="bfp-file-duration" style="text-align:right;font-size:16px;">';
            $output .= esc_html($duration);
            $output .= '</td>';
        } else {
            $output .= '<td>';
            $output .= '<div class="bfp-player-container ' . $first_player_class . '" data-player-id="' . esc_attr($counter) . '">';
            $output .= $audio_tag;
            $output .= '</div>';
            $output .= '<div class="bfp-player-title bfp-column-player-title" data-player-id="' . esc_attr($counter) . '">';
            $output .= wp_kses_post($title);
            if ($single_player) {
                $output .= '<span class="bfp-file-duration">' . esc_html($duration) . '</span>';
            }
            $output .= '</div>';
            $output .= '</td>';
        }
        
        $output .= '</tr>';
        
        return $output;
    }
    
    /**
     * Render custom player layouts
     * This method can be extended for specific layout needs
     */
    public function render_custom_layout($files, $product_id, $layout_type = 'default') {
        $output = '';
        
        switch ($layout_type) {
            case 'grid':
                $output = $this->render_grid_layout($files, $product_id);
                break;
            case 'carousel':
                $output = $this->render_carousel_layout($files, $product_id);
                break;
            default:
                // Use player table for default
                $settings = $this->main_plugin->get_config()->get_states(array(
                    '_bfp_preload',
                    '_bfp_player_layout',
                    '_bfp_player_volume',
                    '_bfp_player_title',
                    '_bfp_loop',
                    '_bfp_merge_in_grouped'
                ), $product_id);
                $settings['player_controls'] = 'all';
                $output = $this->render_player_table($files, $product_id, $settings);
                break;
        }
        
        return apply_filters('bfp_custom_layout_output', $output, $files, $product_id, $layout_type);
    }
    
    /**
     * Render grid layout (placeholder for future implementation)
     */
    private function render_grid_layout($files, $product_id) {
        // Future implementation for grid layout
        return '';
    }
    
    /**
     * Render carousel layout (placeholder for future implementation)
     */
    private function render_carousel_layout($files, $product_id) {
        // Future implementation for carousel layout
        return '';
    }
}