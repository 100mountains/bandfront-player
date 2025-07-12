<?php
/**
 * Potential Playlist Renderer for Bandfront Player
 *
 * @package BandfrontPlayer
 * @since 0.1
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * BFP Playlist Renderer Class
 * Handles all playlist HTML generation and rendering
 */
class BFP_Playlist_Renderer {
    
    private $main_plugin;
    
    public function __construct($main_plugin) {
        $this->main_plugin = $main_plugin;
    }
    
    /**
     * Render the playlist products
     */
    public function render_playlist($products, $atts, $current_post_id, $purchased_times_data = array()) {
        if (empty($products)) {
            return '';
        }
        
        // Enqueue required resources
        $this->enqueue_playlist_resources($atts);
        
        $output = '<div data-loop="' . ($atts['loop'] ? 1 : 0) . '">';
        
        $counter = 0;
        foreach ($products as $product) {
            $output .= $this->render_product_item($product, $atts, $current_post_id, $counter, $purchased_times_data);
            $counter++;
        }
        
        $output .= '</div>';
        
        // Add message if configured
        $output .= $this->render_message($atts);
        
        // Add title if provided
        if (!empty($atts['title']) && !empty($output)) {
            $output = '<div class="bfp-widget-playlist-title">' . esc_html($atts['title']) . '</div>' . $output;
        }
        
        return $output;
    }
    
    /**
     * Render a single product item
     */
    private function render_product_item($product, $atts, $current_post_id, $counter, $purchased_times_data) {
        // Skip if user doesn't have access
        if ($this->main_plugin->get_force_purchased_flag() && 
            !$this->main_plugin->get_woocommerce()->woocommerce_user_product($product->ID)) {
            return '';
        }
        
        $product_obj = wc_get_product($product->ID);
        if (!$product_obj) {
            return '';
        }
        
        // Prepare product data
        $product_data = $this->prepare_product_data($product, $product_obj, $atts, $counter, $purchased_times_data);
        
        // Get audio files using the consolidated player class
        $audio_files = $this->main_plugin->get_player()->get_product_files($product->ID);
        if (!is_array($audio_files)) {
            $audio_files = array();
        }
        
        // Get download links if needed
        $download_links = '';
        if ($atts['download_links']) {
            $download_links = $this->main_plugin->get_woocommerce()->woocommerce_user_download($product->ID);
        }
        
        // Render based on layout
        if ('new' == $atts['layout']) {
            return $this->render_new_layout($product, $product_obj, $atts, $audio_files, $download_links, 
                                           $product_data['row_class'], $current_post_id, $product_data['preload']);
        } else {
            return $this->render_classic_layout($product, $product_obj, $atts, $audio_files, $download_links, 
                                                $product_data['row_class'], $current_post_id, $product_data['preload']);
        }
    }
    
    /**
     * Prepare product data for rendering
     */
    private function prepare_product_data($product, $product_obj, $atts, $counter, $purchased_times_data) {
        $preload = $this->main_plugin->get_product_attr($product->ID, '_bfp_preload', '');
        $row_class = (1 == $counter % 2) ? 'bfp-odd-product' : 'bfp-even-product';
        
        // Get purchased times if needed
        $purchased_times = 0;
        if ($atts['purchased_times'] && !empty($purchased_times_data)) {
            foreach ($purchased_times_data as $data) {
                if ($data->product_id == $product->ID) {
                    $purchased_times = $data->times;
                    break;
                }
            }
        }
        
        return array(
            'preload' => $preload,
            'row_class' => $row_class,
            'purchased_times' => $purchased_times
        );
    }
    
    /**
     * Render new layout for a product
     */
    private function render_new_layout($product, $product_obj, $atts, $audio_files, $download_links, $row_class, $current_post_id, $preload) {
        $featured_image = $this->get_featured_image($product->ID, $atts);
        $price = $product_obj->get_price();
        
        $output = '<div class="bfp-new-layout bfp-widget-product controls-' . esc_attr($atts['controls']) . ' ' . 
                  esc_attr($atts['class']) . ' ' . esc_attr($row_class) . ' ' . 
                  esc_attr(($product->ID == $current_post_id && $atts['highlight_current_product']) ? 'bfp-current-product' : '') . '">';
        
        // Header section
        $output .= $this->render_product_header($product, $product_obj, $atts, $download_links);
        
        // Purchase button if needed
        if (0 != @floatval($price) && 0 == $atts['hide_purchase_buttons']) {
            $output .= $this->render_purchase_button($product, $product_obj);
        }
        
        $output .= '</div>'; // Close header
        
        // Files section
        $output .= '<div class="bfp-widget-product-files">';
        
        if (!empty($featured_image)) {
            $output .= $featured_image . '<div class="bfp-widget-product-files-list">';
        }
        
        // Render audio files
        $output .= $this->render_audio_files($product->ID, $audio_files, $atts, $preload);
        
        if (!empty($featured_image)) {
            $output .= '</div>';
        }
        
        $output .= '</div></div>'; // Close files and product
        
        return $output;
    }
    
    /**
     * Render classic layout for a product
     */
    private function render_classic_layout($product, $product_obj, $atts, $audio_files, $download_links, $row_class, $current_post_id, $preload) {
        $featured_image = $this->get_featured_image($product->ID, $atts);
        
        $output = '<ul class="bfp-widget-playlist bfp-classic-layout controls-' . esc_attr($atts['controls']) . ' ' . 
                  esc_attr($atts['class']) . ' ' . esc_attr($row_class) . ' ' . 
                  esc_attr(($product->ID == $current_post_id && $atts['highlight_current_product']) ? 'bfp-current-product' : '') . '">';
        
        if (!empty($featured_image)) {
            $output .= '<li style="display:table-row;">' . $featured_image . '<div class="bfp-widget-product-files-list"><ul>';
        }
        
        // Render audio files
        $output .= $this->render_audio_files($product->ID, $audio_files, $atts, $preload);
        
        if (!empty($featured_image)) {
            $output .= '</ul></div></li>';
        }
        
        $output .= '</ul>';
        
        return $output;
    }
    
    /**
     * Render product header section
     */
    private function render_product_header($product, $product_obj, $atts, $download_links) {
        $output = '<div class="bfp-widget-product-header">';
        $output .= '<div class="bfp-widget-product-title">';
        $output .= '<a href="' . esc_url(get_permalink($product->ID)) . '">' . esc_html($product_obj->get_name()) . '</a>';
        
        if ($atts['purchased_times']) {
            $output .= '<span class="bfp-purchased-times">' .
                      sprintf(
                          __($this->main_plugin->get_global_attr('_bfp_purchased_times_text', '- purchased %d time(s)'), 'bandfront-player'),
                          $atts['purchased_times']
                      ) . '</span>';
        }
        
        $output .= $download_links;
        $output .= '</div>';
        
        return $output;
    }
    
    /**
     * Render purchase button
     */
    private function render_purchase_button($product, $product_obj) {
        $product_id_for_add_to_cart = $product->ID;
        
        if ($product_obj->is_type('variable')) {
            $variations = $product_obj->get_available_variations();
            $variations_id = wp_list_pluck($variations, 'variation_id');
            if (!empty($variations_id)) {
                $product_id_for_add_to_cart = $variations_id[0];
            }
        } elseif ($product_obj->is_type('grouped')) {
            $children = $product_obj->get_children();
            if (!empty($children)) {
                $product_id_for_add_to_cart = $children[0];
            }
        }
        
        return '<div class="bfp-widget-product-purchase">' . 
               wc_price($product_obj->get_price(), '') . 
               ' <a href="?add-to-cart=' . $product_id_for_add_to_cart . '"></a>' .
               '</div>';
    }
    
    /**
     * Render audio files
     */
    private function render_audio_files($product_id, $audio_files, $atts, $preload) {
        $output = '';
        
        foreach ($audio_files as $index => $file) {
            $audio_url = $this->main_plugin->generate_audio_url($product_id, $index, $file);
            $duration = $this->main_plugin->get_duration_by_url($file['file']);
            
            $audio_tag = apply_filters(
                'bfp_widget_audio_tag',
                $this->main_plugin->get_player(
                    $audio_url,
                    array(
                        'product_id'      => $product_id,
                        'player_controls' => $atts['controls'],
                        'player_style'    => $atts['player_style'],
                        'media_type'      => $file['media_type'],
                        'id'              => $index,
                        'duration'        => $duration,
                        'preload'         => $preload,
                        'volume'          => $atts['volume'],
                    )
                ),
                $product_id,
                $index,
                $audio_url
            );
            
            $file_title = esc_html(apply_filters('bfp_widget_file_name', $file['name'], $product_id, $index));
            
            $output .= '<div class="bfp-widget-product-file">';
            $output .= $audio_tag;
            $output .= '<span class="bfp-file-name">' . $file_title . '</span>';
            
            if (!isset($atts['duration']) || $atts['duration'] == 1) {
                $output .= '<span class="bfp-file-duration">' . esc_html($duration) . '</span>';
            }
            
            $output .= '<div style="clear:both;"></div></div>';
        }
        
        return $output;
    }
    
    /**
     * Get featured image HTML
     */
    private function get_featured_image($product_id, $atts) {
        if (!$atts['cover']) {
            return '';
        }
        
        $thumbnail = get_the_post_thumbnail($product_id, array(60, 60));
        if ($thumbnail) {
            return '<img src="' . esc_attr($thumbnail) . '" class="bfp-widget-feature-image" />';
        }
        
        return '';
    }
    
    /**
     * Render message section
     */
    private function render_message($atts) {
        $message = $this->main_plugin->get_global_attr('_bfp_message', '');
        
        if (!empty($message) && empty($atts['hide_message'])) {
            return '<div class="bfp-message">' . wp_kses_post(__($message, 'bandfront-player')) . '</div>';
        }
        
        return '';
    }
    
    /**
     * Enqueue playlist resources
     */
    private function enqueue_playlist_resources($atts) {
        $this->main_plugin->enqueue_resources();
        
        wp_enqueue_style(
            'bfp-playlist-widget-style', 
            plugin_dir_url(dirname(__FILE__)) . 'widgets/playlist_widget/css/style.css', 
            array(), 
            BFP_VERSION
        );
        
        wp_enqueue_script(
            'bfp-playlist-widget-script', 
            plugin_dir_url(dirname(__FILE__)) . 'widgets/playlist_widget/js/public.js', 
            array(), 
            BFP_VERSION
        );
        
        wp_localize_script(
            'bfp-playlist-widget-script',
            'bfp_widget_settings',
            array('continue_playing' => $atts['continue_playing'])
        );
    }
}
