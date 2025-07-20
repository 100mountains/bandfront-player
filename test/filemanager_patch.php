<?php
// Add more detailed logging for file checking
$search = <<<'SEARCH'
       foreach ($files as $index => $file) {
           if (!empty($file['file']) && false !== ($mediaType = $this->isAudio($file['file']))) {
SEARCH;

$replace = <<<'REPLACE'
       foreach ($files as $index => $file) {
           Debug::log('getProductFilesInternal: checking file', ['index' => $index, 'file' => substr($file['file'] ?? '', -50)]);
           if (!empty($file['file']) && false !== ($mediaType = $this->isAudio($file['file']))) {
REPLACE;

$file = './src/Storage/FileManager.php';
$content = file_get_contents($file);
$content = str_replace($search, $replace, $content);
file_put_contents($file, $content);
