<?php
// Add logging after files are found
$search = <<<'SEARCH'
        error_log("[BFP] includeMainPlayer: files returned = " . (empty($files) ? "empty" : "count: " . count($files)));
        
        if (!empty($files)) {
SEARCH;

$replace = <<<'REPLACE'
        error_log("[BFP] includeMainPlayer: files returned = " . (empty($files) ? "empty" : "count: " . count($files)));
        
        if (!empty($files)) {
            error_log("[BFP] includeMainPlayer: Processing files for product");
REPLACE;

$file = './src/Audio/Player.php';
$content = file_get_contents($file);
$content = str_replace($search, $replace, $content);
file_put_contents($file, $content);
