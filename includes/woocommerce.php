<?php
/**
 * WooCommerce integration functionality for Bandfront Player
 *
 * @package BandfrontPlayer
 * @since 0.1
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * BFP WooCommerce Integration Class
 * Handles all WooCommerce-specific functionality and integrations
 */
class BFP_WooCommerce {
    
    private $main_plugin;
    
    public function __construct($main_plugin) {
        $this->main_plugin = $main_plugin;
    }
    
    /**
     * Check if user has purchased a specific product
     */
    public function woocommerce_user_product($product_id) {
        $this->main_plugin->set_purchased_product_flag(false);
        if (
            !is_user_logged_in() ||
            (
                !$this->main_plugin->get_global_attr('_bfp_purchased', false) &&
                empty($this->main_plugin->get_force_purchased_flag())
            )
        ) {
            return false;
        }

        $current_user = wp_get_current_user();
        if (
            wc_customer_bought_product($current_user->user_email, $current_user->ID, $product_id) ||
            (
                class_exists('WC_Subscriptions_Manager') &&
                method_exists('WC_Subscriptions_Manager', 'wcs_user_has_subscription') &&
                WC_Subscriptions_Manager::wcs_user_has_subscription($current_user->ID, $product_id, 'active')
            ) ||
            (
                function_exists('wcs_user_has_subscription') &&
                wcs_user_has_subscription($current_user->ID, $product_id, 'active')
            ) ||
            apply_filters('bfp_purchased_product', false, $product_id)
        ) {
            $this->main_plugin->set_purchased_product_flag(true);
            return md5($current_user->user_email);
        }

        return false;
    }
    
    /**
     * Get user download links for a product
     */
    public function woocommerce_user_download($product_id) {
        $download_links = [];
        if (is_user_logged_in()) {
            if (empty($this->main_plugin->get_current_user_downloads()) && function_exists('wc_get_customer_available_downloads')) {
                $current_user = wp_get_current_user();
                $this->main_plugin->set_current_user_downloads(wc_get_customer_available_downloads($current_user->ID));
            }

            foreach ($this->main_plugin->get_current_user_downloads() as $download) {
                if ($download['product_id'] == $product_id) {
                    $download_links[$download['download_id']] = $download['download_url'];
                }
            }
        }

        $download_links = array_unique($download_links);
        if (count($download_links)) {
            $download_links = array_values($download_links);
            return '<a href="javascript:void(0);" data-download-links="' . esc_attr(json_encode($download_links)) . '" class="bfp-download-link">' . esc_html__('download', 'bandfront-player') . '</a>';
        }
        return '';
    }
    
    /**
     * Modify product title to include player
     */
    public function woocommerce_product_title($title, $product) {
        $player = '';
        if (false === stripos($title, '<audio')) {
            $player .= $this->main_plugin->get_player()->include_main_player($product, false);
        }
        return $player . $title;
    }
    
    /**
     * Replace the shortcode to display a playlist with all songs
     */
    public function replace_playlist_shortcode($atts) {
        if (!class_exists('woocommerce') || is_admin()) {
            return '';
        }

        $get_times = function($product_id, $products_list) {
            if (!empty($products_list)) {
                foreach ($products_list as $product) {
                    if ($product->product_id == $product_id) {
                        return $product->times;
                    }
                }
            }
            return 0;
        };

        global $post;

        $output = '';
        if (!$this->main_plugin->get_insert_player()) {
            return $output;
        }

        if (!is_array($atts)) {
            $atts = array();
        }
        
        $post_types = $this->main_plugin->_get_post_types();
        if (
            empty($atts['products_ids']) &&
            empty($atts['purchased_products']) &&
            empty($atts['product_categories']) &&
            empty($atts['product_tags']) &&
            !empty($post) &&
            in_array($post->post_type, $post_types)
        ) {
            try {
                ob_start();
                $this->main_plugin->get_player()->include_all_players($post->ID);
                $output = ob_get_contents();
                ob_end_clean();

                $class = esc_attr(isset($atts['class']) ? $atts['class'] : '');

                return strpos($output, 'bfp-player-list') !== false ?
                       str_replace('bfp-player-container', $class . ' bfp-player-container', $output) : $output;
            } catch (Exception $err) {
                $atts['products_ids'] = $post->ID;
            }
        }

        $atts = shortcode_atts(
            array(
                'title'                     => '',
                'products_ids'              => '*',
                'purchased_products'        => 0,
                'highlight_current_product' => 0,
                'continue_playing'          => 0,
                // Use state manager for default player style
                'player_style'              => $this->main_plugin->get_state('_bfp_player_layout'),
                'controls'                  => 'track',
                'layout'                    => 'new',
                'cover'                     => 0,
                'volume'                    => 1,
                'purchased_only'            => 0,
                'hide_purchase_buttons'     => 0,
                'class'                     => '',
                'loop'                      => 0,
                'purchased_times'           => 0,
                'hide_message'              => 0,
                'download_links'            => 0,
                'duration'                  => 1,
                'product_categories'        => '',
                'product_tags'              => '',
            ),
            $atts
        );

        $playlist_title            = trim($atts['title']);
        $products_ids              = $atts['products_ids'];
        $product_categories        = $atts['product_categories'];
        $product_tags              = $atts['product_tags'];
        $purchased_products        = $atts['purchased_products'];
        $highlight_current_product = $atts['highlight_current_product'];
        $continue_playing          = $atts['continue_playing'];
        $player_style              = $atts['player_style'];
        $controls                  = $atts['controls'];
        $layout                    = $atts['layout'];
        $cover                     = $atts['cover'];
        $volume                    = $atts['volume'];
        $purchased_only            = $atts['purchased_only'];
        $hide_purchase_buttons     = $atts['hide_purchase_buttons'];
        $class                     = $atts['class'];
        $loop                      = $atts['loop'];
        $purchased_times           = $atts['purchased_times'];
        $download_links_flag       = $atts['download_links'];

        // Typecasting variables
        $cover                     = is_numeric($cover) ? intval($cover) : 0;
        $volume                    = is_numeric($volume) ? floatval($volume) : 0;
        $purchased_products        = is_numeric($purchased_products) ? intval($purchased_products) : 0;
        $highlight_current_product = is_numeric($highlight_current_product) ? intval($highlight_current_product) : 0;
        $continue_playing          = is_numeric($continue_playing) ? intval($continue_playing) : 0;
        $purchased_only            = is_numeric($purchased_only) ? intval($purchased_only) : 0;
        $hide_purchase_buttons     = is_numeric($hide_purchase_buttons) ? intval($hide_purchase_buttons) : 0;
        $loop                      = is_numeric($loop) ? intval($loop) : 0;
        $purchased_times           = is_numeric($purchased_times) ? intval($purchased_times) : 0;

        // Load the purchased products only
        $this->main_plugin->set_force_purchased_flag($purchased_only);

        // get the products ids
        $products_ids = preg_replace('/[^\d\,\*]/', '', $products_ids);
        $products_ids = preg_replace('/\,+/', ',', $products_ids);
        $products_ids = trim($products_ids, ',');

        // get the product categories
        $product_categories = preg_replace('/\s*\,\s*/', ',', $product_categories);
        $product_categories = preg_replace('/\,+/', ',', $product_categories);
        $product_categories = trim($product_categories, ',');

        // get the product tags
        $product_tags = preg_replace('/\s*\,\s*/', ',', $product_tags);
        $product_tags = preg_replace('/\,+/', ',', $product_tags);
        $product_tags = trim($product_tags, ',');

        if (
            strlen($products_ids) == 0 &&
            strlen($product_categories) == 0 &&
            strlen($product_tags) == 0
        ) {
            return $output;
        }

        return $this->build_playlist_output($products_ids, $product_categories, $product_tags, $purchased_products, $atts, $output);
    }
    
    /**
     * Build the playlist output HTML
     */
    private function build_playlist_output($products_ids, $product_categories, $product_tags, $purchased_products, $atts, $output) {
        global $wpdb, $post;

        $current_post_id = !empty($post) ? (is_int($post) ? $post : $post->ID) : -1;

        $query = 'SELECT posts.ID, posts.post_title FROM ' . $wpdb->posts . ' AS posts, ' . $wpdb->postmeta . ' as postmeta WHERE posts.post_status="publish" AND posts.post_type IN (' . $this->main_plugin->_get_post_types(true) . ') AND posts.ID = postmeta.post_id AND postmeta.meta_key="_bfp_enable_player" AND (postmeta.meta_value="yes" OR postmeta.meta_value="1")';

        if (!empty($purchased_products)) {
            $hide_purchase_buttons = 1;
            $_current_user_id = get_current_user_id();
            if (0 == $_current_user_id) {
                return $output;
            }

            $customer_orders = get_posts(
                array(
                    'meta_key'    => '_customer_user',
                    'meta_value'  => $_current_user_id,
                    'post_type'   => 'shop_order',
                    'post_status' => array('wc-completed', 'wc-processing'),
                    'numberposts' => -1
                )
            );

            if (empty($customer_orders)) {
                return $output;
            }

            $products_ids = array();
            foreach ($customer_orders as $customer_order) {
                $order = wc_get_order($customer_order->ID);
                $items = $order->get_items();
                foreach ($items as $item) {
                    $products_ids[] = $item->get_product_id();
                }
            }
            $products_ids = array_unique($products_ids);
            $products_ids_str = implode(',', $products_ids);

            $query .= ' AND posts.ID IN (' . $products_ids_str . ')';
            $query .= ' ORDER BY FIELD(posts.ID,' . $products_ids_str . ')';
        } else {
            if (strpos('*', $products_ids) === false) {
                $query .= ' AND posts.ID IN (' . $products_ids . ')';
                $query .= ' ORDER BY FIELD(posts.ID,' . $products_ids . ')';
            } else {
                $tax_query = [];

                if ('' != $product_categories) {
                    $categories = explode(',', $product_categories);
                    $tax_query[] = array(
                        'taxonomy' => 'product_cat',
                        'field' => 'slug',
                        'terms' => $categories,
                        'include_children' => true,
                        'operator' => 'IN'
                    );
                }

                if ('' != $product_tags) {
                    $tags = explode(',', $product_tags);
                    $tax_query[] = array(
                        'taxonomy' => 'product_tag',
                        'field' => 'slug',
                        'terms' => $tags,
                        'operator' => 'IN'
                    );
                }

                if (!empty($tax_query)) {
                    $tax_query['relation'] = 'OR';
                    $tax_query_sql = get_tax_sql($tax_query, 'posts', 'ID');
                    if (!empty($tax_query_sql['join'])) {
                        $query .= ' ' . $tax_query_sql['join'];
                    }
                    if (!empty($tax_query_sql['where'])) {
                        $query .= ' ' . $tax_query_sql['where'];
                    }
                }

                $query .= ' ORDER BY posts.post_title ASC';
            }
        }

        $products = $wpdb->get_results($query);

        if (!empty($products)) {
            return $this->render_playlist_products($products, $atts, $current_post_id, $output);
        }

        $this->main_plugin->set_force_purchased_flag(0);
        return $output;
    }
    
    /**
     * Render the playlist products
     */
    private function render_playlist_products($products, $atts, $current_post_id, $output) {
        global $wpdb;
        
        $product_purchased_times = array();
        if ($atts['purchased_times']) {
            $products_ids_str = (is_array($atts['products_ids'])) ? implode(',', $atts['products_ids']) : $atts['products_ids'];
            $product_purchased_times = $wpdb->get_results('SELECT order_itemmeta.meta_value product_id, COUNT(order_itemmeta.meta_value) as times FROM ' . $wpdb->prefix . 'posts as orders INNER JOIN ' . $wpdb->prefix . 'woocommerce_order_items as order_items ON (orders.ID=order_items.order_id) INNER JOIN ' . $wpdb->prefix . 'woocommerce_order_itemmeta as order_itemmeta ON (order_items.order_item_id=order_itemmeta.order_item_id) WHERE orders.post_type="shop_order" AND orders.post_status="wc-completed" AND order_itemmeta.meta_key="_product_id" ' . (strlen($products_ids_str) && false === strpos('*', $products_ids_str) ? ' AND order_itemmeta.meta_value IN (' . $products_ids_str . ')' : '') . ' GROUP BY order_itemmeta.meta_value');
        }

        $this->main_plugin->enqueue_resources();
        wp_enqueue_style('bfp-playlist-widget-style', plugin_dir_url(dirname(__FILE__)) . 'widgets/playlist_widget/css/style.css', array(), BFP_VERSION);
        wp_enqueue_script('bfp-playlist-widget-script', plugin_dir_url(dirname(__FILE__)) . 'widgets/playlist_widget/js/widget.js', array(), BFP_VERSION);
        wp_localize_script(
            'bfp-playlist-widget-script',
            'bfp_widget_settings',
            array('continue_playing' => $atts['continue_playing'])
        );
        
        $counter = 0;
        $output .= '<div data-loop="' . ($atts['loop'] ? 1 : 0) . '">';
        
        foreach ($products as $product) {
            if ($this->main_plugin->get_force_purchased_flag() && !$this->woocommerce_user_product($product->ID)) {
                continue;
            }

            $product_obj = wc_get_product($product->ID);
            $counter++;
            $preload = $this->main_plugin->get_product_attr($product->ID, '_bfp_preload', '');
            $row_class = 'bfp-even-product';
            if (1 == $counter % 2) {
                $row_class = 'bfp-odd-product';
            }

            $audio_files = $this->main_plugin->get_product_files($product->ID);
            if (!is_array($audio_files)) {
                $audio_files = array();
            }

            $download_links = '';
            if ($atts['download_links']) {
                $download_links = $this->woocommerce_user_download($product->ID);
            }

            // Get purchased times for this product
            $purchased_times = 0;
            if ($atts['purchased_times']) {
                foreach ($product_purchased_times as $pt) {
                    if ($pt->product_id == $product->ID) {
                        $purchased_times = $pt->times;
                        break;
                    }
                }
            }
            $atts['purchased_times'] = $purchased_times;

            $output .= $this->render_single_product($product, $product_obj, $atts, $audio_files, $download_links, $row_class, $current_post_id, $preload, $counter);
        }
        
        $output .= '</div>';
        
        $message = $this->main_plugin->get_global_attr('_bfp_message', '');
        if (!empty($message) && empty($atts['hide_message'])) {
            $output .= '<div class="bfp-message">' . wp_kses_post(__($message, 'bandfront-player')) . '</div>';
        }

        $this->main_plugin->set_force_purchased_flag(0);

        if (!empty($atts['title']) && !empty($output)) {
            $output = '<div class="bfp-widget-playlist-title">' . esc_html($atts['title']) . '</div>' . $output;
        }

        return $output;
    }
    
    /**
     * Render a single product in the playlist
     */
    private function render_single_product($product, $product_obj, $atts, $audio_files, $download_links, $row_class, $current_post_id, $preload, $counter) {
        $output = '';
        
        // Define featured_image if cover is enabled
        $featured_image = '';
        if ($atts['cover']) {
            $featured_image = get_the_post_thumbnail($product->ID, array(60, 60));
        }
        
        if ('new' == $atts['layout']) {
            $price = $product_obj->get_price();
            $output .= '<div class="bfp-new-layout bfp-widget-product controls-' . esc_attr($atts['controls']) . ' ' . 
                      esc_attr($atts['class']) . ' ' . esc_attr($row_class) . ' ' . 
                      esc_attr(($product->ID == $current_post_id && $atts['highlight_current_product']) ? 'bfp-current-product' : '') . '">';
            
            // Header section
            $output .= '<div class="bfp-widget-product-header">';
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
            
            if (0 != @floatval($price) && 0 == $atts['hide_purchase_buttons']) {
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
                
                $output .= '<div class="bfp-widget-product-purchase">' . 
                           wc_price($product_obj->get_price(), '') . 
                           ' <a href="?add-to-cart=' . $product_id_for_add_to_cart . '"></a>' .
                           '</div>';
            }
            $output .= '</div>'; // Close header
            
            $output .= '<div class="bfp-widget-product-files">';
            
            if (!empty($featured_image)) {
                $output .= $featured_image . '<div class="bfp-widget-product-files-list">';
            }

            // Render audio files
            foreach ($audio_files as $index => $file) {
                $audio_url = $this->main_plugin->generate_audio_url($product->ID, $index, $file);
                $duration = $this->main_plugin->get_duration_by_url($file['file']);
                
                $audio_tag = apply_filters(
                    'bfp_widget_audio_tag',
                    $this->main_plugin->get_player(
                        $audio_url,
                        array(
                            'product_id'      => $product->ID,
                            'player_controls' => $atts['controls'],
                            'player_style'    => $atts['player_style'],
                            'media_type'      => $file['media_type'],
                            'id'              => $index,
                            'duration'        => $duration,
                            'preload'         => $preload,
                            'volume'          => $atts['volume'],
                        )
                    ),
                    $product->ID,
                    $index,
                    $audio_url
                );
                
                $file_title = esc_html(apply_filters('bfp_widget_file_name', $file['name'], $product->ID, $index));
                
                $output .= '<div class="bfp-widget-product-file">';
                $output .= $audio_tag;
                $output .= '<span class="bfp-file-name">' . $file_title . '</span>';
                
                if (!isset($atts['duration']) || $atts['duration'] == 1) {
                    $output .= '<span class="bfp-file-duration">' . esc_html($duration) . '</span>';
                }
                
                $output .= '<div style="clear:both;"></div></div>';
            }

            if (!empty($featured_image)) {
                $output .= '</div>';
            }

            $output .= '</div></div>'; // Close files and product
        } else {
            // Classic layout
            $output .= '<ul class="bfp-widget-playlist bfp-classic-layout controls-' . esc_attr($atts['controls']) . ' ' . esc_attr($atts['class']) . ' ' . esc_attr($row_class) . ' ' . esc_attr(($product->ID == $current_post_id && $atts['highlight_current_product']) ? 'bfp-current-product' : '') . '">';

            if (!empty($featured_image)) {
                $output .= '<li style="display:table-row;">' . $featured_image . '<div class="bfp-widget-product-files-list"><ul>';
            }

            foreach ($audio_files as $index => $file) {
                $audio_url = $this->main_plugin->generate_audio_url($product->ID, $index, $file);
                $duration = $this->main_plugin->get_duration_by_url($file['file']);
                
                $audio_tag = apply_filters(
                    'bfp_widget_audio_tag',
                    $this->main_plugin->get_player(
                        $audio_url,
                        array(
                            'product_id'      => $product->ID,
                            'player_controls' => $atts['controls'],
                            'player_style'    => $atts['player_style'],
                            'media_type'      => $file['media_type'],
                            'id'              => $index,
                            'duration'        => $duration,
                            'preload'         => $preload,
                            'volume'          => $atts['volume'],
                        )
                    ),
                    $product->ID,
                    $index,
                    $audio_url
                );
                
                $file_title = esc_html(apply_filters('bfp_widget_file_name', $file['name'], $product->ID, $index));
                
                $output .= '<li style="display:table-row;">';
                $output .= '<div class="bfp-player-col bfp-widget-product-file" style="display:table-cell;">' . $audio_tag . '</div>';
                $output .= '<div class="bfp-title-col" style="display:table-cell;"><span class="bfp-player-song-title">' . $file_title . '</span>';
                
                if (!empty($atts['duration']) && $atts['duration'] == 1) {
                    $output .= ' <span class="bfp-player-song-duration">' . esc_html($duration) . '</span>';
                }
                
                $output .= '</div></li>';
            }

            if (!empty($featured_image)) {
                $output .= '</ul></div></li>';
            }

            $output .= '</ul>';
        }
        
        return $output;
    }
}
