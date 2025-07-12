<?php
/**
 * Server-side rendering of the `bfp/bandfront-player-playlist` block.
 *
 * @package Bandfront_Player
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Extract attributes
$shortcode = isset( $attributes['shortcode'] ) ? $attributes['shortcode'] : '[bfp-playlist products_ids="*" controls="track"]';

// Ensure shortcode is properly formatted
$shortcode = wp_unslash( $shortcode );

// Process the shortcode
$output = do_shortcode( $shortcode );

// Wrap in block container with proper attributes
$wrapper_attributes = get_block_wrapper_attributes( array(
    'class' => 'bfp-playlist-block'
) );

?>
<div <?php echo $wrapper_attributes; ?>>
    <?php echo $output; ?>
</div>
