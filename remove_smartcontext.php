<?php
// Remove the smartPlayContext check
$search = <<<'SEARCH'
            // Use smart context instead of _bfp_show_in
            $smartContext = $this->config->smartPlayContext($id);
            error_log("[BFP] includeMainPlayer: smartPlayContext($id) = " . ($smartContext ? "true" : "false"));
            if (!$smartContext) {
                error_log("[BFP] includeMainPlayer: Returning empty because smartPlayContext is false");
                return $output;
            }
SEARCH;

$replace = <<<'REPLACE'
            // Check if player is enabled for this product
            $playerEnabled = $this->config->getState('_bfp_player_enabled', $id);
            error_log("[BFP] includeMainPlayer: player enabled for product $id = " . ($playerEnabled ? "true" : "false"));
            if (!$playerEnabled) {
                error_log("[BFP] includeMainPlayer: Returning empty because player is disabled");
                return $output;
            }
REPLACE;

$file = './src/Audio/Player.php';
$content = file_get_contents($file);
$content = str_replace($search, $replace, $content);
file_put_contents($file, $content);
