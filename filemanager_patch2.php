<?php
// Add logging before the foreach loop
$search = <<<'SEARCH'
       $audioFiles = [];
       foreach ($files as $index => $file) {
SEARCH;

$replace = <<<'REPLACE'
       $audioFiles = [];
       Debug::log('getProductFilesInternal: about to check files', ['count' => count($files), 'first_key' => array_key_first($files)]);
       foreach ($files as $index => $file) {
REPLACE;

$file = './src/Storage/FileManager.php';
$content = file_get_contents($file);
$content = str_replace($search, $replace, $content);
file_put_contents($file, $content);
