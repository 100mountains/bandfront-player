<?php
// Script to fix player visibility checks

$file = __DIR__ . '/src/Audio/Player.php';
$content = file_get_contents($file);

// Count replacements
$count = 0;

// Pattern 1: Replace smartPlayContext checks in includeMainPlayer
$pattern1 = '/if\s*\(\s*!\s*\$this->config->smartPlayContext\s*\(\s*\$id\s*\)\s*\)\s*{\s*return;\s*}/';
$replacement1 = 'if (!$this->config->getState(\'_bfp_player_on_cover\', false, $id)) {
                return;
            }';
$content = preg_replace($pattern1, $replacement1, $content, -1, $replaced1);
$count += $replaced1;

// Pattern 2: Replace smartPlayContext checks with productId
$pattern2 = '/if\s*\(\s*!\s*\$this->config->smartPlayContext\s*\(\s*\$productId\s*\)\s*\)\s*{\s*return\s+\$content;\s*}/';
$replacement2 = 'if (!$this->config->getState(\'_bfp_player_on_cover\', false, $productId)) {
            return $content;
        }';
$content = preg_replace($pattern2, $replacement2, $content, -1, $replaced2);
$count += $replaced2;

// Pattern 3: Remove comment lines about smart context
$pattern3 = '/\s*\/\/\s*Use smart context.*\n/';
$content = preg_replace($pattern3, '', $content, -1, $replaced3);
$count += $replaced3;

// Save the updated content
if ($count > 0) {
    file_put_contents($file, $content);
    echo "Fixed $count smartPlayContext references in Player.php\n";
} else {
    echo "No smartPlayContext references found to fix\n";
}

// Show the lines where changes were made
echo "\nChecking for remaining smartPlayContext references:\n";
exec("grep -n 'smartPlayContext' '$file'", $remaining);
if (empty($remaining)) {
    echo "✓ All smartPlayContext references removed\n";
} else {
    echo "⚠ Found remaining references:\n";
    foreach ($remaining as $line) {
        echo "  $line\n";
    }
}

echo "\nChecking _bfp_player_on_cover usage:\n";
exec("grep -n '_bfp_player_on_cover' '$file'", $cover_checks);
foreach ($cover_checks as $line) {
    echo "  $line\n";
}
