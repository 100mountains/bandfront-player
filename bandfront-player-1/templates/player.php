<?php
/**
 * Player Template
 * 
 * This template is used to render the audio player on the frontend.
 */

// Check if the player should be displayed
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Get the player instance
$player = Bandfront\Core\Bootstrap::getInstance()->getComponent('player');

// Render the player HTML
if ($player) {
    echo $player->render();
} else {
    echo '<p>Audio player is not available.</p>';
}
?>