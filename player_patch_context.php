<?php
// Add debug logging for smartPlayContext
$search = <<<'SEARCH'
            // Use smart context instead of _bfp_show_in
            if (!$this->config->smartPlayContext($id)) {
                return $output;
            }
SEARCH;

$replace = <<<'REPLACE'
            // Use smart context instead of _bfp_show_in
            $smartContext = $this->config->smartPlayContext($id);
            error_log("[BFP] includeMainPlayer: smartPlayContext($id) = " . ($smartContext ? "true" : "false"));
            if (!$smartContext) {
                error_log("[BFP] includeMainPlayer: Returning empty because smartPlayContext is false");
                return $output;
            }
REPLACE;

$file = './src/Audio/Player.php';
$content = file_get_contents($file);
$content = str_replace($search, $replace, $content);
file_put_contents($file, $content);
