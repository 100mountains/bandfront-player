# File Path and Namespace Update Task

## Objective
Update all file references in PHP files to match the new plugin structure and fix namespaces.

## Task Instructions

Check each PHP file in the plugin and update the following:

### 1. CSS/JS Asset Paths
Update all asset references to use the new structure:
- CSS files: Should point to `assets/css/` in plugin root
- JS files: Should point to `assets/js/` in plugin root
- Widget assets: Should point to `widgets/[widget_name]/css/` or `widgets/[widget_name]/js/`

**Find patterns like:**
```php
plugin_dir_url(__FILE__) . 'css/style.css'
plugin_dir_url(dirname(__FILE__)) . '/css/style.css'
plugin_dir_url(dirname(dirname(__FILE__))) . 'css/style.css'
plugins_url('css/style.css', __FILE__)
```

**Replace with:**
```php
plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/css/style.css'
// Or for widget assets:
plugin_dir_url(dirname(dirname(__FILE__))) . 'widgets/playlist_widget/css/style.css'
```

### 2. Template/View Paths
Update all template references:
- Old path: `Views/` or `views/`
- New path: `templates/`

**Find patterns like:**
```php
include_once plugin_dir_path(__FILE__) . 'Views/template.php'
include_once plugin_dir_path(dirname(__FILE__)) . 'Views/template.php'
require_once BFP_PLUGIN_DIR . 'Views/template.php'
```

**Replace with:**
```php
include_once plugin_dir_path(dirname(dirname(__FILE__))) . 'templates/template.php'
```

### 3. Namespace Updates
Fix any remaining old namespaces:
- Old: `namespace bfp\`
- New: `namespace Bandfront\`

**Namespace mapping:**
- `bfp\` → `Bandfront\Core\`
- `bfp\Admin\` → `Bandfront\Admin\`
- `bfp\Audio\` → `Bandfront\Audio\`
- `bfp\Utils\` → `Bandfront\Utils\`
- `bfp\Storage\` → `Bandfront\Storage\`
- `bfp\WooCommerce\` → `Bandfront\WooCommerce\`

### 4. Use Statement Updates
Update all use statements to match new namespaces:

**Find:**
```php
use bfp\Config;
use bfp\Utils\Debug;
use bfp\Plugin;
```

**Replace:**
```php
use Bandfront\Core\Config;
use Bandfront\Utils\Debug;
// Remove Plugin references - components are injected
```

### 5. Class References
Update any hardcoded class references:
- `\bfp\ClassName` → `\Bandfront\Namespace\ClassName`

## Important Rules

1. **DO NOT** change the actual filenames (e.g., `style.css` stays `style.css`)
2. **DO NOT** modify any other code logic
3. **ONLY** update paths and namespaces
4. Make sure paths are relative to plugin root using `plugin_dir_url()` or `plugin_dir_path()`
5. For files deep in subdirectories, count the `dirname()` calls needed to reach plugin root

## Path Examples

From a file in `src/Audio/Player.php`:
- To reach `assets/css/`: `plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/css/style.css'`
- To reach `templates/`: `plugin_dir_path(dirname(dirname(__FILE__))) . 'templates/template.php'`

From a file in `src/Admin/Settings.php`:
- Same pattern: two `dirname()` calls to reach plugin root

## Process

1. Scan the file for any asset references (css, js)
2. Check if they're already pointing to `assets/` or `widgets/`
3. If not, update them
4. Check for any `Views/` or `views/` references
5. Update them to `templates/`
6. Check namespace declarations
7. Update any old `bfp\` namespaces
8. Update use statements
9. Report what was changed

## Output Format

For each file processed, report:
```
File: [filepath]
✓ CSS paths updated: [count] references
✓ JS paths updated: [count] references
✓ Template paths updated: [count] references
✓ Namespace updated: [old] → [new]
✓ Use statements updated: [count]
❌ No changes needed

Then show the specific changes made.
```

Say "BREAK" after each file, wait for "CONTINUE" before processing the next file.