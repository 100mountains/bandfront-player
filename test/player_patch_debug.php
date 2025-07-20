<?php
// Add debug logging after the onCover check
$search = <<<'SEARCH'
        $onCover = $this->config->getState('_bfp_player_on_cover');
        if ($onCover && (is_shop() || is_product_category() || is_product_tag())) {
            // Don't render the regular player on shop pages when on_cover is enabled
            return '';
        }
SEARCH;

$replace = <<<'REPLACE'
        $onCover = $this->config->getState('_bfp_player_on_cover');
        error_log("[BFP] includeMainPlayer: onCover = " . ($onCover ? "true" : "false") . ", is_shop = " . (is_shop() ? "true" : "false"));
        if ($onCover && (is_shop() || is_product_category() || is_product_tag())) {
            // Don't render the regular player on shop pages when on_cover is enabled
            error_log("[BFP] includeMainPlayer: Returning empty because onCover is true and on shop page");
            return '';
        }
REPLACE;

$file = './src/Audio/Player.php';
$content = file_get_contents($file);
$content = str_replace($search, $replace, $content);
file_put_contents($file, $content);
