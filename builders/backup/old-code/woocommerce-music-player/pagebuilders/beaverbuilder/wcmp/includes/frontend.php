<?php
if ( ! empty( $settings->products_ids ) ) {
	$products_ids = preg_replace( '/[^\d\,]/', '', $settings->products_ids );
	$products_ids = trim( $products_ids, ',' );
}
if ( ! empty( $products_ids ) ) {
	$products_ids = ' products_ids="' . sanitize_text_field( $products_ids ) . '"';
}

$output = '[wcmp-playlist' . $products_ids;

if ( ! empty( $settings->attributes ) ) {
	$output .= ' ' . sanitize_text_field( $settings->attributes );
}

$output .= ']';
echo $output; // phpcs:ignore WordPress.Security.EscapeOutput
