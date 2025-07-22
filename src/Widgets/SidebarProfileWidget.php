<?php
namespace Bandfront\Widgets;

use WP_Widget;
use WP_Query;

if (!defined('ABSPATH')) exit;

class SidebarProfileWidget extends WP_Widget {
    public function __construct() {
        parent::__construct(
            'bandfront_sidebar_profile',
            __('Sidebar Profile Widget', 'bandfront'),
            [ 'description' => __('Modern profile widget with avatar, products, and links.', 'bandfront') ]
        );
    }

    public function widget($args, $instance) {
        $avatar_url = !empty($instance['avatar_url']) ? esc_url($instance['avatar_url']) : '';
        $name = !empty($instance['name']) ? esc_html($instance['name']) : '';
        $location = !empty($instance['location']) ? esc_html($instance['location']) : '';
        $links = !empty($instance['links']) ? $instance['links'] : [];

        echo $args['before_widget'];
        echo '<div class="bfp-sidebar-profile-widget">';
        if ($avatar_url) {
            echo '<div class="bfp-avatar"><img src="' . $avatar_url . '" alt="Avatar" /></div>';
        }
        if ($name) {
            echo '<div class="bfp-profile-name">' . $name . '</div>';
        }
        if ($location) {
            echo '<div class="bfp-profile-location">' . $location . '</div>';
        }
        // Custom links
        if (!empty($links) && is_array($links)) {
            echo '<div class="bfp-profile-links">';
            foreach ($links as $link) {
                $url = esc_url($link['url'] ?? '#');
                $label = esc_html($link['label'] ?? 'Link');
                echo '<a href="' . $url . '" class="bfp-profile-link" target="_blank" rel="noopener">' . $label . '</a>';
            }
            echo '</div>';
        }
        // WooCommerce products
        if (class_exists('WooCommerce')) {
            $query = new WP_Query([
                'post_type' => 'product',
                'posts_per_page' => 6,
                'post_status' => 'publish',
                'orderby' => 'date',
                'order' => 'DESC',
            ]);
            if ($query->have_posts()) {
                echo '<div class="bfp-profile-products">';
                while ($query->have_posts()) {
                    $query->the_post();
                    global $product;
                    $cover = get_the_post_thumbnail_url(get_the_ID(), 'thumbnail');
                    $title = get_the_title();
                    $date = get_the_date('Y');
                    $permalink = get_permalink();
                    echo '<a href="' . esc_url($permalink) . '" class="bfp-profile-product">';
                    if ($cover) {
                        echo '<img src="' . esc_url($cover) . '" alt="' . esc_attr($title) . '" class="bfp-product-cover" />';
                    }
                    echo '<div class="bfp-product-info">';
                    echo '<span class="bfp-product-title">' . esc_html($title) . '</span>';
                    echo '<span class="bfp-product-date">' . esc_html($date) . '</span>';
                    echo '</div>';
                    echo '</a>';
                }
                wp_reset_postdata();
                echo '</div>';
            }
        }
        echo '</div>';
        echo $args['after_widget'];
    }

    public function form($instance) {
        $avatar_url = $instance['avatar_url'] ?? '';
        $name = $instance['name'] ?? '';
        $location = $instance['location'] ?? '';
        $links = $instance['links'] ?? [];
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('avatar_url'); ?>"><?php _e('Avatar URL:', 'bandfront'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('avatar_url'); ?>" name="<?php echo $this->get_field_name('avatar_url'); ?>" type="text" value="<?php echo esc_attr($avatar_url); ?>" />
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('name'); ?>"><?php _e('Name:', 'bandfront'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('name'); ?>" name="<?php echo $this->get_field_name('name'); ?>" type="text" value="<?php echo esc_attr($name); ?>" />
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('location'); ?>"><?php _e('Location:', 'bandfront'); ?></label>
            <input class="widefat" id="<?php echo $this->get_field_id('location'); ?>" name="<?php echo $this->get_field_name('location'); ?>" type="text" value="<?php echo esc_attr($location); ?>" />
        </p>
        <p>
            <label><?php _e('Custom Links (label|url, one per line):', 'bandfront'); ?></label>
            <textarea class="widefat" name="<?php echo $this->get_field_name('links'); ?>" rows="3"><?php
                if (!empty($links) && is_array($links)) {
                    foreach ($links as $link) {
                        echo esc_attr(($link['label'] ?? '') . '|' . ($link['url'] ?? '')) . "\n";
                    }
                }
            ?></textarea>
        </p>
        <?php
    }

    public function update($new_instance, $old_instance) {
        $instance = [];
        $instance['avatar_url'] = sanitize_text_field($new_instance['avatar_url'] ?? '');
        $instance['name'] = sanitize_text_field($new_instance['name'] ?? '');
        $instance['location'] = sanitize_text_field($new_instance['location'] ?? '');
        // Parse links
        $links_raw = $new_instance['links'] ?? '';
        $links = [];
        foreach (explode("\n", $links_raw) as $line) {
            $parts = array_map('trim', explode('|', $line, 2));
            if (count($parts) === 2 && $parts[0] && $parts[1]) {
                $links[] = ['label' => $parts[0], 'url' => $parts[1]];
            }
        }
        $instance['links'] = $links;
        return $instance;
    }
} 