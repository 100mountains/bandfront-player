<?php
// Add debug logging for files check
$search = <<<'SEARCH'
        $files = $this->fileManager->getProductFilesInternal([
            'product' => $product,
            'first'   => true,
        ]);
        
        if (!empty($files)) {
SEARCH;

$replace = <<<'REPLACE'
        $files = $this->fileManager->getProductFilesInternal([
            'product' => $product,
            'first'   => true,
        ]);
        
        error_log("[BFP] includeMainPlayer: files returned = " . (empty($files) ? "empty" : "count: " . count($files)));
        
        if (!empty($files)) {
REPLACE;

$file = './src/Audio/Player.php';
$content = file_get_contents($file);
$content = str_replace($search, $replace, $content);
file_put_contents($file, $content);
