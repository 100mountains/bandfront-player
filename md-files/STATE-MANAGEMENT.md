# Bandfront Player - State Management Architecture

# State Retrieval System in BFP_Config

state management class implements a hierarchical settings system with multiple ways to access settings:

## Main Access Methods

1. **`get_state()`** - The primary method that implements inheritance:
   - Product-specific settings (if applicable)
   - Global settings (fallback)
   - Default values (final fallback)

2. **`get_states()`** - Bulk fetches multiple settings at once for efficiency

3. **`get_global_attr()`** - Direct access to global settings only

4. **`get_product_attr()`** - Legacy/compatibility method for product-specific settings

5. **`get_all_settings()`** - Retrieves all possible settings for a given context

6. **Special-purpose getters**:
   - `get_admin_form_settings()` - Formatted for admin UI with type casting
   - `get_player_state()` - Optimized subset for frontend/runtime

The system follows a clear inheritance pattern as documented: "Product Setting → Global Setting → Default Value", ensuring context-appropriate values while maintaining fallbacks.

Settings are categorized as either "overridable" (can be set per-product) or "global only" (apply site-wide), with validation to ensure proper values.

## Core Classes Overview

### 1. **Main Plugin Class**
- **Class:** `BandfrontPlayer` (bfp.php)
- **Purpose:** Main orchestrator and component manager
- **Key Methods:**
  - `get_config()` - Returns BFP_Config instance
  - `get_audio_core()` - Returns BFP_Audio_Engine instance
  - `get_player_manager()` - Returns BFP_Player_Manager instance
  - `get_player_renderer()` - Returns BFP_Player_Renderer instance
  - `get_woocommerce()` - Returns BFP_WooCommerce instance
  - `get_file_handler()` - Returns BFP_File_Handler instance
  - `get_hooks_manager()` - Returns BFP_Hooks instance
  - `get_admin()` - Returns BFP_Admin instance

### 2. **State Manager**
- **Class:** `BFP_Config` (state-manager.php)
- **Purpose:** Centralized state management with context-aware inheritance
- **Key Methods:**
  - `get_state($key, $default, $product_id, $options)` - Get single state value
  - `get_states($keys, $product_id)` - Bulk get multiple state values
  - `update_state($key, $value, $product_id)` - Update state value
  - `delete_state($key, $product_id)` - Remove product override
  - `is_module_enabled($module_name)` - Check module status
  - `get_player_state($product_id)` - Get minimal player runtime state

### 3. **Core Manager Classes**

#### Player Manager
- **Class:** `BFP_Player_Manager` (player.php)
- **Purpose:** Player HTML generation and resource management
- **Key Methods:**
  - `get_player($audio_url, $args)` - Generate player HTML
  - `enqueue_resources()` - Load CSS/JS assets
  - `include_main_player($product, $_echo)` - Render main player
  - `include_all_players($product)` - Render all players
  - `get_product_files($product_id)` - Retrieve product audio files

#### Audio Engine
- **Class:** `BFP_Audio_Engine` (audio.php)
- **Purpose:** Audio file processing, streaming, and manipulation
- **Key Methods:**
  - `output_file($args)` - Stream/output audio file
  - `generate_audio_url($product_id, $file_index, $file_data)` - Create playback URL
  - `get_duration_by_url($url)` - Get audio duration
  - `is_audio($file_path)` - Check if file is audio
  - `preload($preload, $audio_url)` - Handle preload logic

#### Hooks Manager
- **Class:** `BFP_Hooks` (hooks.php)
- **Purpose:** WordPress hook registration and management
- **Key Methods:**
  - `get_hooks_config()` - Get context-aware hook configuration
  - `register_dynamic_hooks()` - Register hooks based on page context
  - `enqueue_on_cover_assets()` - Load cover overlay assets
  - `add_play_button_on_cover()` - Render play button overlay

#### Admin Interface
- **Class:** `BFP_Admin` (admin.php)
- **Purpose:** Admin interface and settings management
- **Key Methods:**
  - `settings_page()` - Render settings page
  - `save_global_settings()` - Save global settings
  - `save_post($post_id, $post, $update)` - Save product settings
  - `ajax_save_settings()` - AJAX handler for settings
  - `get_admin_modules()` - Get available admin modules

#### WooCommerce Integration
- **Class:** `BFP_WooCommerce` (woocommerce.php)
- **Purpose:** WooCommerce-specific functionality
- **Key Methods:**
  - `woocommerce_user_product($product_id)` - Check purchase status
  - `replace_playlist_shortcode($atts)` - Handle [bfp-playlist] shortcode
  - `woocommerce_product_title($title, $product)` - Filter product titles

### 4. **Renderer Classes**

#### Player Renderer
- **Class:** `BFP_Player_Renderer` (player-html.php)
- **Purpose:** Context-aware player HTML rendering
- **Note:** Currently empty - functionality may be in BFP_Player_Manager

#### Cover Renderer
- **Class:** `BFP_Cover_Renderer` (cover-renderer.php)
- **Purpose:** Play button overlay on product images
- **Key Methods:**
  - `should_render()` - Check if overlay should display
  - `enqueue_assets()` - Load overlay CSS
  - `render($product)` - Render overlay HTML

### 5. **Utility Classes**

#### File Handler
- **Class:** `BFP_File_Handler` (utils/files.php)
- **Purpose:** Demo file management and directory operations
- **Key Methods:**
  - `_createDir()` - Create upload directories
  - `_clearDir($dirPath)` - Clear directory contents
  - `delete_post($post_id, $demos_only, $force)` - Delete post files
  - `delete_truncated_files($product_id)` - Remove demo files

#### Cloud Tools
- **Class:** `BFP_Cloud_Tools` (utils/cloud.php)
- **Purpose:** Cloud storage URL processing
- **Key Methods:**
  - `get_google_drive_download_url($url)` - Convert Drive URLs
  - `get_google_drive_file_name($url)` - Extract file names

#### Cache Manager
- **Class:** `BFP_Cache` (utils/cache.php)
- **Purpose:** Cross-plugin cache clearing
- **Key Methods:**
  - `clear_all_caches()` - Clear all known cache plugins

#### Analytics
- **Class:** `BFP_Analytics` (utils/analytics.php)
- **Purpose:** Playback tracking and analytics
- **Key Methods:**
  - `track_play_event($product_id, $file_url)` - Track plays
  - `increment_playback_counter($product_id)` - Update counter

#### Preview Manager
- **Class:** `BFP_Preview` (utils/preview.php)
- **Purpose:** Handle preview/play requests
- **Key Methods:**
  - `handle_preview_request()` - Process play URLs
  - `process_play_request($product_id, $file_index)` - Stream file

#### Utilities
- **Class:** `BFP_Utils` (utils/utils.php)
- **Purpose:** General helper functions
- **Key Methods:**
  - `get_post_types($string)` - Get supported post types
  - `add_class($html, $class, $tag)` - Add CSS class to element

#### Auto Updater
- **Class:** `BFP_Updater` (utils/update.php)
- **Purpose:** Plugin auto-update functionality
- **Note:** Currently placeholder implementation

## State Management Flow

### 1. **State Retrieval Hierarchy**
```
Product Override → Global Setting → Default Value
```

### 2. **Context-Aware Settings**

**Global-Only Settings** (no product overrides):
- `_bfp_show_in`
- `_bfp_player_layout`
- `_bfp_player_controls`
- `_bfp_player_title`
- `_bfp_on_cover`
- `_bfp_force_main_player_in_title`
- `_bfp_players_in_cart`
- `_bfp_play_simultaneously`
- `_bfp_registered_only`
- `_bfp_purchased`
- `_bfp_fade_out`
- `_bfp_message`
- `_bfp_ffmpeg`
- `_bfp_analytics_*`
- `_bfp_modules_enabled`
- `_bfp_cloud_*`

**Product-Overridable Settings**:
- `_bfp_enable_player`
- `_bfp_audio_engine`
- `_bfp_single_player`
- `_bfp_merge_in_grouped`
- `_bfp_play_all`
- `_bfp_loop`
- `_bfp_preload`
- `_bfp_player_volume`
- `_bfp_secure_player`
- `_bfp_file_percent`
- `_bfp_own_demos`
- `_bfp_direct_own_demos`
- `_bfp_demos_list`

### 3. **State Access Patterns**

**Single Value Retrieval:**
```php
$value = $this->main_plugin->get_config()->get_state('_bfp_audio_engine', 'mediaelement', $product_id);
```

**Bulk Retrieval (Recommended for Performance):**
```php
$settings = $this->main_plugin->get_config()->get_states(array(
    '_bfp_audio_engine',
    '_bfp_play_simultaneously',
    '_bfp_fade_out'
), $product_id);
```

**Module Status Check:**
```php
if ($this->main_plugin->get_config()->is_module_enabled('cloud-engine')) {
    // Cloud engine is enabled
}
```

## Component Dependencies

### Initialization Order (Critical)
1. `BFP_Config` - Must be first
2. `BFP_File_Handler` - Creates directories
3. `BFP_Player_Manager` - Player functionality
4. `BFP_Audio_Engine` - Audio processing
5. `BFP_WooCommerce` - WooCommerce integration
6. `BFP_Hooks` - Hook registration
7. `BFP_Player_Renderer` - Player rendering
8. `BFP_Cover_Renderer` - Cover overlays
9. `BFP_Analytics` - Analytics tracking
10. `BFP_Preview` - Preview handling
11. `BFP_Admin` - Admin interface (only if is_admin())

### Component Access
All components are accessed through the main plugin instance:
```php
$config = $this->main_plugin->get_config();
$audio = $this->main_plugin->get_audio_core();
$player = $this->main_plugin->get_player_manager();
```

## Best Practices

1. **Always use state manager** for settings retrieval
2. **Use bulk fetch** (`get_states()`) when retrieving multiple values
3. **Check module status** before using optional features
4. **Access components** through main plugin getters
5. **Never access** post meta or options directly
6. **Context matters** - some settings are global-only
7. **Cache in memory** - state manager caches values during request

## Module System

### Available Modules
- `audio-engine` - Audio engine selector (always enabled)
- `cloud-engine` - Cloud storage integration

### Module State Management
```php
// Check if enabled
$enabled = $config->is_module_enabled('cloud-engine');

// Enable/disable module
$config->set_module_state('cloud-engine', true);
```

## Performance Optimizations

1. **Bulk Operations**: Use `get_states()` for multiple values
2. **Memory Caching**: State manager caches retrieved values
3. **Lazy Loading**: Components initialized only when needed
4. **Context Awareness**: Hooks registered only where required
5. **Smart Defaults**: Efficient fallback to defaults

## Security Considerations

1. All user input sanitized through state manager
2. Nonce verification in admin operations
3. Capability checks for settings changes
4. Escaped output in all renderers
5. File operations restricted to plugin directories

# State Management Architecture

## Global State Structure

```javascript
const PlayerState = {
    // Player Registry
    players: new Map([
        [playerId, {
            instance: MediaElementPlayer,
            element: HTMLAudioElement,
            config: Object,
            state: 'idle' | 'playing' | 'paused' | 'ended'
        }]
    ]),
    
    // Active Player Tracking
    activePlayers: new Set([playerId]),
    
    // UI State
    ui: {
        playingElements: new Set([element]),
        overlayPositions: new Map([[productId, {x, y}]]),
        singlePlayerMode: new Map([[containerId, activeTrack]])
    },
    
    // Playlist State
    playlists: new Map([
        [playlistId, {
            tracks: [playerId],
            currentIndex: 0,
            loop: boolean,
            loopBoundary: element
        }]
    ]),
    
    // User Permissions
    permissions: {
        isRegistered: boolean,
        purchasedProducts: new Set([productId]),
        lastChecked: timestamp
    },
    
    // Configuration
    config: {
        playAll: boolean,
        simultaneousPlay: boolean,
        fadeOut: boolean,
        fadeOutDuration: 4,
        volume: 0.7,
        preload: 'none' | 'metadata' | 'auto'
    }
};
```

## State Management Patterns

### 1. Observer Pattern for State Changes
```javascript
class StateObserver {
    constructor() {
        this.observers = new Map();
    }
    
    subscribe(stateKey, callback) {
        if (!this.observers.has(stateKey)) {
            this.observers.set(stateKey, new Set());
        }
        this.observers.get(stateKey).add(callback);
    }
    
    notify(stateKey, newValue, oldValue) {
        if (this.observers.has(stateKey)) {
            this.observers.get(stateKey).forEach(callback => {
                callback(newValue, oldValue);
            });
        }
    }
}
```

### 2. State Mutations
```javascript
class PlayerStateMutations {
    static ADD_PLAYER(state, { playerId, instance, element }) {
        state.players.set(playerId, {
            instance,
            element,
            config: {},
            state: 'idle'
        });
    }
    
    static UPDATE_PLAYER_STATE(state, { playerId, playerState }) {
        const player = state.players.get(playerId);
        if (player) {
            player.state = playerState;
            
            if (playerState === 'playing') {
                state.activePlayers.add(playerId);
            } else {
                state.activePlayers.delete(playerId);
            }
        }
    }
    
    static SET_ACTIVE_TRACK(state, { containerId, trackId }) {
        state.ui.singlePlayerMode.set(containerId, trackId);
    }
}
```

### 3. State Actions
```javascript
class PlayerStateActions {
    static async playTrack({ commit, state }, { playerId }) {
        // Check permissions
        if (!state.permissions.isRegistered && state.config.registeredOnly) {
            return false;
        }
        
        // Pause others if needed
        if (!state.config.simultaneousPlay) {
            state.activePlayers.forEach(activeId => {
                if (activeId !== playerId) {
                    commit('UPDATE_PLAYER_STATE', {
                        playerId: activeId,
                        playerState: 'paused'
                    });
                }
            });
        }
        
        // Play the track
        commit('UPDATE_PLAYER_STATE', {
            playerId,
            playerState: 'playing'
        });
        
        return true;
    }
}
```

## State Persistence

### Local Storage Schema
```javascript
const PersistentState = {
    // User preferences
    'wcmp_user_prefs': {
        volume: number,
        lastPlayedTrack: string,
        playbackPosition: Map<trackId, seconds>
    },
    
    // Cache
    'wcmp_cache': {
        filePermissions: Map<productId, boolean>,
        demoFiles: Map<fileUrl, demoUrl>,
        cacheExpiry: timestamp
    }
};
```

### Session Storage Schema
```javascript
const SessionState = {
    // Temporary UI state
    'wcmp_session': {
        activePlayerId: string,
        scrollPosition: number,
        expandedPlaylists: Set<playlistId>
    }
};
```

## State Synchronization

### Cross-Tab Communication
```javascript
class StateSynchronizer {
    constructor() {
        this.channel = new BroadcastChannel('wcmp_state_sync');
        this.channel.onmessage = this.handleMessage.bind(this);
    }
    
    broadcast(action, payload) {
        this.channel.postMessage({ action, payload });
    }
    
    handleMessage({ data }) {
        const { action, payload } = data;
        // Apply state changes from other tabs
    }
}
```

### AJAX State Updates
```javascript
class RemoteStateSync {
    static async syncPurchasedProducts() {
        const response = await fetch('/wp-admin/admin-ajax.php', {
            method: 'POST',
            body: new URLSearchParams({
                action: 'wcmp_get_purchased_products'
            })
        });
        
        const products = await response.json();
        PlayerState.permissions.purchasedProducts = new Set(products);
    }
}
```

## State Debugging

### Development Tools
```javascript
if (process.env.NODE_ENV === 'development') {
    window.__WCMP_STATE__ = PlayerState;
    
    window.__WCMP_STATE_HISTORY__ = [];
    
    // Log all state changes
    StateObserver.subscribe('*', (newValue, oldValue) => {
        window.__WCMP_STATE_HISTORY__.push({
            timestamp: Date.now(),
            change: { newValue, oldValue },
            stack: new Error().stack
        });
    });
}
```
