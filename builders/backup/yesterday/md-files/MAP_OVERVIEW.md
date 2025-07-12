# Bandfront Player - Architecture Overview

## Core Purpose
WordPress plugin that adds audio players to WooCommerce products with support for demos, playlists, and multiple audio engines (MediaElement.js/WaveSurfer.js).

## Architecture Pattern
Component-based architecture with centralized state management and context-aware rendering.

## Main Components

### 1. **Main Plugin (`bfp.php`)**
- Entry point and component orchestrator
- Initializes all managers in order: Config → File Handler → Player Manager → Audio Processor → WooCommerce → Hooks → Renderers
- Provides component access via getters

### 2. **State Management (`class-bfp-config.php`)**
- Central configuration with inheritance: Product Settings → Global Settings → Defaults
- Context-aware state resolution
- Bulk retrieval for performance

### 3. **Core Managers**

**Player Manager** (`class-bfp-player-manager.php`)
- Generates player HTML
- Manages script/style enqueuing based on audio engine
- Handles player configuration

**Audio Processor** (`class-bfp-audio-processor.php`)
- Streams audio files with optional truncation
- Creates demo versions
- Tracks analytics

**Hooks Manager** (`class-bfp-hooks-manager.php`)
- Dynamic hook registration based on page context
- Prevents duplicate players
- Manages player insertion points

### 4. **Renderers**

**Player Renderer** (`class-bfp-player-renderer.php`)
- Context-aware player generation
- Single vs. multiple player modes
- Play button overlays on product images

**WooCommerce Integration** (`class-bfp-woocommerce.php`)
- Product integration
- Playlist shortcode handling
- Purchase verification

### 5. **Support Systems**

**File Handler** (`class-bfp-file-handler.php`)
- Demo file management
- Secure directory creation
- Cleanup operations

**Admin Interface** (`class-bfp-admin.php`)
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
- Cloud storage support
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

## File Structure
```
/bfp.php                    # Main plugin file
/includes/                  # Core classes
  class-bfp-*.php          # Component classes
/modules/                   # Feature modules
/js/                       # Frontend scripts
/css/                      # Styles and skins
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

## Future-Ready
- Modular architecture supports new features
- Clean API for third-party integration
- Prepared for REST API implementation