<?php
namespace Elementor;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Register the categories
Plugin::$instance->elements_manager->add_category(
	'bandfront-player-cat',
	array(
		'title' => 'Bandfront Player',
		'icon'  => 'fa fa-plug',
	),
	2 // position
);
