<?php
/**
 * Playlist Template
 * 
 * This template is used to render the audio playlist.
 */

defined('ABSPATH') || exit;

// Fetch the playlist data (this could be from a database, API, etc.)
$playlist = []; // Example: Fetch your playlist data here

if (!empty($playlist)) {
    echo '<div class="bandfront-playlist">';
    foreach ($playlist as $track) {
        echo '<div class="track">';
        echo '<h3>' . esc_html($track['title']) . '</h3>';
        echo '<audio controls>';
        echo '<source src="' . esc_url($track['url']) . '" type="audio/mpeg">';
        echo 'Your browser does not support the audio element.';
        echo '</audio>';
        echo '</div>';
    }
    echo '</div>';
} else {
    echo '<p>No tracks available in the playlist.</p>';
}