<?php
// Add logging around output generation
$search = <<<'SEARCH'
            $output = '<div class="bfp-player-container product-' . esc_attr($file['product']) . '" ';
            $output .= 'data-product="' . esc_attr($id) . '" ';
            $output .= 'data-file-index="' . esc_attr($index) . '" ';
            $output .= 'data-nonce="' . esc_attr($nonce) . '">';
            $output .= $audioTag . '</div>';
SEARCH;

$replace = <<<'REPLACE'
            $output = '<div class="bfp-player-container product-' . esc_attr($file['product']) . '" ';
            $output .= 'data-product="' . esc_attr($id) . '" ';
            $output .= 'data-file-index="' . esc_attr($index) . '" ';
            $output .= 'data-nonce="' . esc_attr($nonce) . '">';
            $output .= $audioTag . '</div>';
            
            error_log("[BFP] includeMainPlayer: Generated output length = " . strlen($output));
            error_log("[BFP] includeMainPlayer: audioTag length = " . strlen($audioTag));
REPLACE;

$file = './src/Audio/Player.php';
$content = file_get_contents($file);
$content = str_replace($search, $replace, $content);
file_put_contents($file, $content);
