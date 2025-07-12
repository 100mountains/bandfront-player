# Bandfront Player - Architecture Overview

## Core Purpose
WordPress plugin that adds audio players to WooCommerce products with support for demos, playlists, and multiple audio engines (MediaElement.js/WaveSurfer.js).

## Architecture Pattern
Component-based architecture with centralized state management and context-aware rendering.

## Main Components

### 1. **Main Plugin (`bfp.php`)**
- Entry point and component orchestrator
- Initializes all managers in order: Config → File Handler → Audio Engine → WooCommerce → Player → Hooks → Admin
- Provides component access via getters

### 2. **State Management (`state-manager.php`)**
- Central configuration with inheritance: Product Settings → Global Settings → Defaults
- Context-aware state resolution
- Bulk retrieval for performance

### 3. **Core Components** (`/includes/`)

**Player** (`player.php`)
- Generates player HTML
- Handles all player rendering (single, multiple, table layouts)
- Manages script/style enqueuing based on audio engine
- Handles player configuration

**Audio Engine** (`audio.php`)
- Streams audio files with optional truncation
- Creates demo versions
- Tracks analytics

**Hooks** (`hooks.php`)
- Dynamic hook registration based on page context
- Prevents duplicate players
- Manages player insertion points

### 4. **Renderers** (`/includes/`)

**Cover Renderer** (`cover-renderer.php`)
- Play button overlays on product images
- Shop page integration

**WooCommerce** (`woocommerce.php`)
- Product integration
- Playlist shortcode handling and rendering
- Purchase verification

### 5. **Utilities** (`/includes/utils/`)

**Files** (`files.php`)
- Demo file management
- Secure directory creation
- Cleanup operations

**Cloud** (`cloud.php`)
- Cloud storage URL processing
- Google Drive integration

**Cache** (`cache.php`)
- Cross-plugin cache clearing
- Performance optimization

**Analytics** (`analytics.php`)
- Playback tracking
- Google Analytics integration

**Preview** (`preview.php`)
- Handle preview requests
- Security validation

**Admin** (`admin.php`)
- Settings pages
- Product metaboxes
- Module loading system

## Data Flow

1. **Page Load**
   ```
   WordPress Init → Plugin Init → Component Loading → Hook Registration
   ```

2. **Player Rendering**
   ```
   Hook Triggered → Context Check → State Retrieval → Player Generation → HTML Output
   ```

3. **Audio Playback**
   ```
   Play Request → File Validation → Analytics → Stream/Redirect
   ```

## Key Features

### Context Awareness
- Players adapt controls based on page type (product/shop/single)
- Hooks registered only where needed
- Smart state inheritance

### Module System
- Audio engine selection (MediaElement.js/WaveSurfer.js)
- Cloud storage support (Google Drive, future: S3, Azure)
- Extensible via action hooks

### Security
- File truncation for demos
- Secure streaming
- Protected upload directories
- Nonce verification

## Integration Points

### WordPress Hooks
- `init`, `plugins_loaded` - Initialization
- `the_content`, `woocommerce_*` - Player insertion
- Custom actions: `bfp_main_player`, `bfp_all_players`

### Shortcodes
- `[bfp-playlist]` - Render product playlists

### JavaScript
- `engine.js` - Player controls and interactions
- `wavesurfer.js` - Waveform visualizations
- `admin.js` - Backend UI

## Performance Optimizations
- Bulk settings retrieval
- Lazy component loading
- Smart script enqueuing
- Cache management support

## File Structure (Simplified)
```
/bfp.php                    # Main plugin file
/includes/                  # Core classes
  *.php                    # Component classes (no class- prefix)
  /utils/                  # Utility classes
    *.php                  # Helper utilities
/modules/                   # Feature modules
/js/                       # Frontend scripts
/css/                      # Styles and skins
  /skins/                  # Theme variations
/views/                    # Admin UI templates
/builders/                 # Page builder integrations
```

## State Hierarchy
1. **Product-specific settings** (highest priority)
2. **Global plugin settings**
3. **Default values** (fallback)

## Extension Points
- Filter: `bfp_player_html` - Modify player output
- Action: `bfp_play_file` - Track playback
- Action: `bfp_module_*_settings` - Add settings
- Multiple component-specific hooks

## Consolidated Architecture
- **Cleaner structure**: Player functionality consolidated into `player.php`
- **Better organization**: Utilities grouped in `/utils/` subfolder
- **Improved readability**: `state-manager.php` instead of `class-bfp-config.php`
- **Maintained stability**: All class names unchanged for backward compatibility
- **Enhanced modularity**: Clear separation of concerns

## Quick Start for Developers
1. Main logic flows through `bfp.php`
2. Player rendering happens in `player.php` (includes table layouts)
3. Audio processing in `audio.php`
4. Settings managed by `state-manager.php`
5. Add new features via the module system in `/modules/`