# Bandfront Player - Class Refactoring Guide

## Overview
This guide focuses on renaming classes for consistency while maintaining the existing folder structure. The current organization is already well-structured - we just need to standardize naming conventions.

## Current Structure Analysis
```
/
bfp.php                            # Main plugin file ✓

/includes/                         # Core classes
  class-bfp-admin.php             # ✓ Already well-named
  class-bfp-analytics.php         # ✓ Already well-named
  class-bfp-audio-processor.php   # → Rename to class-bfp-audio-engine.php
  class-bfp-auto-updater.php      # → Move to /utils/class-bfp-updater.php
  class-bfp-cache-manager.php     # → Move to /utils/class-bfp-cache.php
  class-bfp-cloud-tools.php       # → Move to /utils/
  class-bfp-config.php            # ✓ Already well-named
  class-bfp-cover-overlay-renderer.php # → Rename to class-bfp-cover-renderer.php
  class-bfp-file-handler.php      # → Move to /utils/
  class-bfp-hooks-manager.php     # → Rename to class-bfp-hooks.php
  class-bfp-player-manager.php    # → Rename to class-bfp-player.php
  class-bfp-player-renderer.php   # ✓ Already well-named
  class-bfp-playlist-renderer.php # ✓ Already well-named
  class-bfp-preview-manager.php   # → Move to /utils/class-bfp-preview.php
  class-bfp-utils.php             # → Move to /utils/
  class-bfp-woocommerce.php       # ✓ Already well-named

/modules/                          # Feature modules ✓
  audio-engine.php                # ✓ Module for audio engine settings
  cloud-engine.php                # ✓ Module for cloud storage settings

/views/                           # UI templates ✓
  global-admin-options.php        # ✓ Global settings page
  product-options.php             # ✓ Product metabox

/js/                              # JavaScript files ✓
  admin.js                        # ✓ Admin functionality
  engine.js                       # ✓ Player engine
  wavesurfer.js                   # ✓ WaveSurfer integration
```

## Refactoring Plan

### Phase 1: Create Utils Directory
```bash
mkdir -p /var/www/html/wp-content/plugins/bandfront-player/includes/utils
```

### Phase 2: Move Utility Files
```bash
# Move existing utility files to utils folder
mv includes/class-bfp-utils.php includes/utils/
mv includes/class-bfp-file-handler.php includes/utils/
mv includes/class-bfp-cloud-tools.php includes/utils/
mv includes/class-bfp-analytics.php includes/utils/

# Move and rename files
mv includes/class-bfp-cache-manager.php includes/utils/class-bfp-cache.php
mv includes/class-bfp-auto-updater.php includes/utils/class-bfp-updater.php
mv includes/class-bfp-preview-manager.php includes/utils/class-bfp-preview.php
```

### Phase 3: Rename Core Files
```bash
# Rename files in includes directory
mv includes/class-bfp-audio-processor.php includes/class-bfp-audio-engine.php
mv includes/class-bfp-hooks-manager.php includes/class-bfp-hooks.php
mv includes/class-bfp-player-manager.php includes/class-bfp-player.php
mv includes/class-bfp-cover-overlay-renderer.php includes/class-bfp-cover-renderer.php
```

## Class Name Changes

### 1. Core Classes (in /includes/)
| Current Class Name | New Class Name | File Name Change |
|-------------------|----------------|-------------------|
| BandfrontPlayer | BandfrontPlayer | Keep as is (main class) |
| BFP_Audio_Processor | BFP_Audio_Engine | class-bfp-audio-processor.php → class-bfp-audio-engine.php |
| BFP_Hooks_Manager | BFP_Hooks | class-bfp-hooks-manager.php → class-bfp-hooks.php |
| BFP_Player_Manager | BFP_Player | class-bfp-player-manager.php → class-bfp-player.php |
| BFP_Cover_Overlay_Renderer | BFP_Cover_Renderer | class-bfp-cover-overlay-renderer.php → class-bfp-cover-renderer.php |

### 2. Utility Classes (move to /includes/utils/)
| Current Class Name | New Class Name | New Location |
|-------------------|----------------|--------------|
| BFP_Cache_Manager | BFP_Cache | /includes/utils/class-bfp-cache.php |
| BFP_Auto_Updater | BFP_Updater | /includes/utils/class-bfp-updater.php |
| BFP_Preview_Manager | BFP_Preview | /includes/utils/class-bfp-preview.php |
| BFP_Utils | BFP_Utils | /includes/utils/class-bfp-utils.php |
| BFP_File_Handler | BFP_File_Handler | /includes/utils/class-bfp-file-handler.php |
| BFP_Cloud_Tools | BFP_Cloud_Tools | /includes/utils/class-bfp-cloud-tools.php |
| BFP_Analytics | BFP_Analytics | /includes/utils/class-bfp-analytics.php |

## Update Main Plugin File (bfp.php)

### Update require_once statements:
```php
// OLD structure
require_once 'includes/class-bfp-hooks-manager.php';
require_once 'includes/class-bfp-audio-processor.php';
require_once 'includes/class-bfp-player-manager.php';
require_once 'includes/class-bfp-cache-manager.php';
require_once 'includes/class-bfp-auto-updater.php';
require_once 'includes/class-bfp-preview-manager.php';
require_once 'includes/class-bfp-utils.php';
require_once 'includes/class-bfp-file-handler.php';
require_once 'includes/class-bfp-cloud-tools.php';
require_once 'includes/class-bfp-analytics.php';

// NEW structure
require_once 'includes/class-bfp-hooks.php';
require_once 'includes/class-bfp-audio-engine.php';
require_once 'includes/class-bfp-player.php';
require_once 'includes/utils/class-bfp-cache.php';
require_once 'includes/utils/class-bfp-updater.php';
require_once 'includes/utils/class-bfp-preview.php';
require_once 'includes/utils/class-bfp-utils.php';
require_once 'includes/utils/class-bfp-file-handler.php';
require_once 'includes/utils/class-bfp-cloud-tools.php';
require_once 'includes/utils/class-bfp-analytics.php';
```

### Update class instantiation:
```php
// OLD
$this->hooks_manager = new BFP_Hooks_Manager($this);
$this->audio_processor = new BFP_Audio_Processor($this);
$this->player_manager = new BFP_Player_Manager($this);
$this->preview_manager = new BFP_Preview_Manager($this);

// NEW
$this->hooks = new BFP_Hooks($this);
$this->audio_engine = new BFP_Audio_Engine($this);
$this->player = new BFP_Player($this);
$this->preview = new BFP_Preview($this);
```

### Update getter methods:
```php
// OLD
public function get_audio_processor() { return $this->audio_processor; }
public function get_player_manager() { return $this->player_manager; }
public function get_preview_manager() { return $this->preview_manager; }
public function get_hooks_manager() { return $this->hooks_manager; }

// NEW
public function get_audio_engine() { return $this->audio_engine; }
public function get_player() { return $this->player; }
public function get_preview() { return $this->preview; }
public function get_hooks() { return $this->hooks; }
```

## Search & Replace Operations

### 1. Class Names
```
BFP_Audio_Processor → BFP_Audio_Engine
BFP_Hooks_Manager → BFP_Hooks
BFP_Player_Manager → BFP_Player
BFP_Cover_Overlay_Renderer → BFP_Cover_Renderer
BFP_Cache_Manager → BFP_Cache
BFP_Auto_Updater → BFP_Updater
BFP_Preview_Manager → BFP_Preview
```

### 2. Method Names
```
get_audio_processor → get_audio_engine
get_player_manager → get_player
get_preview_manager → get_preview
get_hooks_manager → get_hooks
get_cover_overlay_renderer → get_cover_renderer
```

### 3. Property Names
```
->audio_processor → ->audio_engine
->player_manager → ->player
->preview_manager → ->preview
->hooks_manager → ->hooks
->cover_overlay_renderer → ->cover_renderer
```

### 4. File Path Updates
```
includes/class-bfp-utils.php → includes/utils/class-bfp-utils.php
includes/class-bfp-file-handler.php → includes/utils/class-bfp-file-handler.php
includes/class-bfp-cloud-tools.php → includes/utils/class-bfp-cloud-tools.php
includes/class-bfp-analytics.php → includes/utils/class-bfp-analytics.php
includes/class-bfp-cache-manager.php → includes/utils/class-bfp-cache.php
includes/class-bfp-auto-updater.php → includes/utils/class-bfp-updater.php
includes/class-bfp-preview-manager.php → includes/utils/class-bfp-preview.php
```

## Static Method Updates

### Cache Operations
```php
// OLD
BFP_Cache_Manager::clear_all_caches()

// NEW
BFP_Cache::clear_all_caches()
```

### Cloud Tools (No change needed)
```php
BFP_Cloud_Tools::get_google_drive_download_url() // Stays the same
```

## Module Integration Points

The modules in `/modules/` directory interact with the main plugin through:
- `audio-engine.php` - Adds settings UI via `bfp_module_general_settings` hook
- `cloud-engine.php` - Adds cloud storage UI via `bfp_module_general_settings` hook

These don't need any structural changes, just update any class references inside them.

## View Files Updates

The view files in `/views/` directory will need updates for:
- Any references to old class names
- Any method calls that have changed

Example in `global-admin-options.php`:
```php
// OLD
BFP_Cache_Manager::clear_all_caches();

// NEW
BFP_Cache::clear_all_caches();
```

## Benefits of This Approach

1. **Minimal disruption** - Keeps existing structure mostly intact
2. **Better organization** - Utilities grouped together
3. **Cleaner names** - Removes redundant suffixes like "Manager"
4. **Easier navigation** - Clear separation between core and utility classes
5. **Future-ready** - Easy to add PSR-4 autoloading later if needed

## PSR-4 Autoloading (Future Enhancement)

PSR-4 autoloading would eliminate the need for `require_once` statements by automatically loading classes based on their namespace and file path. For example:

```php
// composer.json
{
    "autoload": {
        "psr-4": {
            "BandfrontPlayer\\": "includes/",
            "BandfrontPlayer\\Utils\\": "includes/utils/"
        }
    }
}

// Then classes would use namespaces:
namespace BandfrontPlayer;
class BFP_Config { ... }

namespace BandfrontPlayer\Utils;
class BFP_Cache { ... }
```

This is a future enhancement that can be added after the basic refactoring is complete.

## Testing After Refactoring

1. **Activation Test** - Ensure plugin activates without errors
2. **Admin Pages** - Check all settings pages load correctly
3. **Player Rendering** - Verify players appear on frontend
4. **Audio Playback** - Test file streaming works
5. **Utility Functions** - Test cache clearing, file operations, etc.
6. **Module Loading** - Ensure audio-engine and cloud-engine modules work

## Rollback Plan

Before starting:
1. Backup entire `/includes/` directory
2. Export database
3. Note current git commit

If issues arise:
1. Restore `/includes/` from backup
2. Clear all caches
3. Deactivate and reactivate plugin
