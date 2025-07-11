# Bandfront Player - Codebase Map (Refactored Architecture)

## Main Plugin File

**File:** `/bfp.php`
**Purpose:** Main plugin entry point, initializes core functionality and loads all dependencies.
**WordPress Integration Points:**
- Hooks: `init`, `plugins_loaded`, `wp_privacy_personal_data_export_file`, `wp_privacy_personal_data_eraser`
- Filters: `get_post_metadata`, `the_title`, `esc_html`, `woocommerce_product_title`, `wp_kses_allowed_html`
- Shortcodes: `[bfp-playlist]`
- Actions: `bfp_main_player`, `bfp_all_players`, `bfp_delete_purchased_files` (scheduled)

### BandfrontPlayer Class (Refactored)

- **__construct()**
  - Purpose: Initializes all manager classes through init_components()
  - Inputs: None
  - Outputs: Component instances via private properties
  - WordPress Data: None directly
  - Data Flow: Creates instances of all manager classes → sets up file paths
  - Patterns/Concerns: **IMPROVED** - Better separation of concerns, dependency injection pattern

- **init_components()**
  - Purpose: Initialize all core components in proper order
  - Inputs: None
  - Outputs: Instantiated component objects
  - WordPress Data: None
  - Data Flow: Config → File Handler → Player Manager → Audio Processor → WooCommerce → Hooks → Renderers
  - Patterns/Concerns: Centralized component initialization

- **plugins_loaded()**
  - Purpose: Sets up plugin after WordPress loads, checks WooCommerce dependency
  - Inputs: None
  - Outputs: Registers hooks and loads addons
  - WordPress Data: Checks `class_exists('woocommerce')`
  - Data Flow: WP hook → load textdomain → init remaining components → load page builders
  - Patterns/Concerns: **IMPROVED** - Component initialization moved to dedicated method

- **init()**
  - Purpose: Main initialization, sets up all frontend/backend hooks
  - Inputs: None
  - Outputs: Registers AJAX handlers, shortcodes, scheduled events
  - WordPress Data: `get_current_user_id()`, `$_REQUEST['bfp-action']`
  - Data Flow: WP hook → check user permissions → register handlers
  - Patterns/Concerns: **IMPROVED** - Delegated to preview manager for play requests

---

## Core Manager Classes (Refactored Architecture)

### **File:** `/includes/class-bfp-config.php` (Enhanced State Manager)
**Purpose:** Comprehensive state management with context-aware inheritance
**WordPress Integration Points:** None directly

- **get_state()**
  - Purpose: Universal state retrieval with automatic inheritance
  - Inputs: `$key`, `$default`, `$product_id`, `$options`
  - Outputs: Resolved setting value
  - WordPress Data: `get_option()`, `get_post_meta()`
  - Data Flow: Check if global-only → Check product override → Fall back to global → Apply default
  - Patterns/Concerns: **NEW** - Context-aware state management pattern

- **get_states()**
  - Purpose: Bulk retrieve multiple settings efficiently
  - Inputs: `$keys` array, `$product_id`
  - Outputs: Associative array of settings
  - WordPress Data: Single DB query for multiple settings
  - Data Flow: Batch process keys → Return key-value pairs
  - Patterns/Concerns: **NEW** - Performance optimization for multiple settings

- **is_valid_override()**
  - Purpose: Determine if a product override should be used
  - Inputs: `$value`, `$key`
  - Outputs: Boolean
  - WordPress Data: None
  - Data Flow: Check special cases → Validate by type → Return decision
  - Patterns/Concerns: **NEW** - Smart override detection

### **File:** `/includes/class-bfp-audio-processor.php`
**Purpose:** Handles audio file processing, streaming, truncation, and analytics tracking.
**WordPress Integration Points:**
- Actions: `bfp_play_file`, `bfp_truncated_file`

- **output_file()**
  - Purpose: Streams audio file to browser with optional truncation
  - Inputs: `$args` array with url, product_id, secure_player settings
  - Outputs: HTTP redirect or file stream
  - WordPress Data: Direct file paths, `do_action()`
  - Data Flow: URL → validate → copy/truncate → stream output
  - Patterns/Concerns: **IMPROVED** - Better cloud URL handling via cloud tools

- **process_cloud_url()**
  - Purpose: Process cloud storage URLs
  - Inputs: `$url`
  - Outputs: Processed URL
  - WordPress Data: None
  - Data Flow: Detect provider → Process URL → Return download URL
  - Patterns/Concerns: **NEW** - Delegated to cloud tools class

### **File:** `/includes/class-bfp-admin.php` (Modularized)
**Purpose:** Manages all WordPress admin functionality with module support
**WordPress Integration Points:**
- Hooks: `admin_menu`, `admin_init`, `save_post`, `after_delete_post`
- Filters: `manage_product_posts_columns`, `manage_product_posts_custom_column`

- **load_admin_modules()**
  - Purpose: Dynamically load admin modules based on configuration
  - Inputs: None
  - Outputs: Loaded module files
  - WordPress Data: Module enable/disable states
  - Data Flow: Check module states → Load enabled modules → Fire loaded action
  - Patterns/Concerns: **NEW** - Modular architecture for extensibility

- **save_global_settings()**
  - Purpose: Processes and saves plugin global settings including modules
  - Inputs: `$_REQUEST` data
  - Outputs: Updates options, clears cache
  - WordPress Data: `update_option()`, `$_REQUEST`
  - Data Flow: Sanitize input → Handle modules → Build settings → Save → Clear cache
  - Patterns/Concerns: **IMPROVED** - Includes module state management

### **File:** `/includes/class-bfp-player-renderer.php` (Context-Aware)
**Purpose:** Renders audio players with smart context detection
**WordPress Integration Points:** None directly

- **include_main_player()**
  - Purpose: Renders single main player with context-aware controls
  - Inputs: `$product`, `$_echo`
  - Outputs: Player HTML
  - WordPress Data: Product data, page context
  - Data Flow: Check context → Determine controls → Check on_cover → Render appropriately
  - Patterns/Concerns: **IMPROVED** - Smart control selection based on page type

- **include_all_players()**
  - Purpose: Renders all players with context awareness
  - Inputs: `$product`
  - Outputs: Multiple player HTML
  - WordPress Data: Product files, settings via state handler
  - Data Flow: Get files → Check context → Apply settings → Render players
  - Patterns/Concerns: **IMPROVED** - Uses bulk state retrieval

### **File:** `/includes/class-bfp-woocommerce.php`
**Purpose:** Handles WooCommerce-specific integrations and playlist rendering
**WordPress Integration Points:**
- Filters: Applied through main class

- **render_single_product()**
  - Purpose: Renders individual product in playlist
  - Inputs: Multiple parameters including product, atts, audio files
  - Outputs: Product HTML
  - WordPress Data: Product metadata
  - Data Flow: Prepare data → Check layout → Render appropriate HTML
  - Patterns/Concerns: **NEW** - Extracted from monolithic method

- **render_playlist_products()**
  - Purpose: Orchestrates playlist rendering
  - Inputs: `$products`, `$atts`, context data
  - Outputs: Complete playlist HTML
  - WordPress Data: Product purchased times
  - Data Flow: Enqueue resources → Loop products → Apply settings → Return HTML
  - Patterns/Concerns: **IMPROVED** - Better separation from main method

### **File:** `/includes/class-bfp-file-handler.php`
**Purpose:** Manages file operations, demo files, and cleanup tasks.
**WordPress Integration Points:**
- Scheduled events: `bfp_delete_purchased_files`

- **_createDir()**
  - Purpose: Creates and secures upload directories
  - Inputs: None
  - Outputs: Directory structure
  - WordPress Data: `wp_upload_dir()`
  - Data Flow: Get upload dir → Create folders → Add .htaccess
  - Patterns/Concerns: **IMPROVED** - Better error handling

### **File:** `/includes/class-bfp-player-manager.php` (Enhanced)
**Purpose:** Manages player generation and resource enqueuing
**WordPress Integration Points:** Script/style enqueuing

- **enqueue_resources()**
  - Purpose: Intelligently loads resources based on audio engine
  - Inputs: None
  - Outputs: Enqueued scripts and styles
  - WordPress Data: `wp_enqueue_script()`, `wp_localize_script()`
  - Data Flow: Check engine → Load appropriate scripts → Handle skins → Localize settings
  - Patterns/Concerns: **IMPROVED** - Dynamic script loading, bulk settings fetch

- **get_player()**
  - Purpose: Generates player HTML with proper attributes
  - Inputs: `$audio_url`, `$args`
  - Outputs: Audio element HTML
  - WordPress Data: None
  - Data Flow: Parse args → Build attributes → Generate HTML → Apply filters
  - Patterns/Concerns: **NEW** - Centralized player HTML generation

### **File:** `/includes/class-bfp-hooks-manager.php` (Context-Aware)
**Purpose:** Manages WordPress hooks with smart context detection
**WordPress Integration Points:** Dynamic hook registration

- **get_hooks_config()**
  - Purpose: Returns context-aware hook configuration
  - Inputs: None
  - Outputs: Hook configuration array
  - WordPress Data: Page context checks
  - Data Flow: Check page type → Determine appropriate hooks → Return config
  - Patterns/Concerns: **IMPROVED** - Prevents duplicate players via context

- **register_dynamic_hooks()**
  - Purpose: Registers hooks based on current page context
  - Inputs: None
  - Outputs: Registered hooks
  - WordPress Data: Current page context
  - Data Flow: Get config → Register main/all player hooks
  - Patterns/Concerns: **NEW** - Dynamic hook registration pattern

- **add_play_button_on_cover()**
  - Purpose: Adds play button overlay on product images
  - Inputs: None
  - Outputs: HTML overlay
  - WordPress Data: Product data
  - Data Flow: Check setting → Get files → Render button → Add hidden player
  - Patterns/Concerns: **NEW** - Clean implementation of on_cover feature

### **File:** `/includes/class-bfp-playlist-renderer.php`
**Purpose:** Dedicated playlist rendering with proper separation of concerns
**WordPress Integration Points:** None directly
**Status:** Minimal stub implementation to prevent errors

### **File:** `/includes/class-bfp-preview-manager.php`
**Purpose:** Handles preview/play request processing
**WordPress Integration Points:** 
- Actions: `init` for request handling

- **handle_preview_request()**
  - Purpose: Process play requests from URLs
  - Inputs: `$_REQUEST` parameters
  - Outputs: Audio stream
  - WordPress Data: Product files
  - Data Flow: Validate request → Get file → Track analytics → Stream audio
  - Patterns/Concerns: **NEW** - Centralized preview handling

### **File:** `/includes/class-bfp-analytics.php`
**Purpose:** Analytics tracking and integration
**WordPress Integration Points:**
- Actions: `bfp_play_file`

- **increment_playback_counter()**
  - Purpose: Track play counts for products
  - Inputs: `$product_id`
  - Outputs: Updated counter
  - WordPress Data: `update_post_meta()`
  - Data Flow: Get current count → Increment → Save
  - Patterns/Concerns: Clean separation of analytics logic

### **File:** `/includes/class-bfp-cloud-tools.php`
**Purpose:** Cloud storage URL processing utilities
**WordPress Integration Points:** None

- **get_google_drive_download_url()**
  - Purpose: Convert various Google Drive URLs to direct download
  - Inputs: `$url`
  - Outputs: Direct download URL
  - WordPress Data: None
  - Data Flow: Match patterns → Extract ID → Build download URL
  - Patterns/Concerns: **IMPROVED** - Robust pattern matching

### **File:** `/includes/class-bfp-cache-manager.php`
**Purpose:** Centralized cache clearing for multiple plugins
**WordPress Integration Points:** Various cache plugin APIs

- **clear_all_caches()**
  - Purpose: Clear all known WordPress caches
  - Inputs: None
  - Outputs: Cache cleared
  - WordPress Data: Various cache APIs
  - Data Flow: Check each cache plugin → Clear if available
  - Patterns/Concerns: Comprehensive cache support

### **File:** `/includes/class-bfp-utils.php`
**Purpose:** General utility functions
**WordPress Integration Points:** Filters

- **get_post_types()**
  - Purpose: Get supported post types with filter
  - Inputs: `$string` flag
  - Outputs: Array or SQL string
  - WordPress Data: None
  - Data Flow: Define types → Apply filter → Format output
  - Patterns/Concerns: Extensible via filter

---

## Module System (New Architecture)

### **File:** `/modules/audio-engine.php`
**Purpose:** Audio engine selection module (MediaElement.js vs WaveSurfer.js)
**WordPress Integration Points:**
- Actions: `bfp_module_general_settings`, `bfp_module_product_settings`

- **bfp_audio_engine_settings()**
  - Purpose: Render audio engine selection UI
  - Inputs: None
  - Outputs: Settings HTML
  - WordPress Data: Engine settings via state handler
  - Data Flow: Get current engine → Render options → Handle visualizations
  - Patterns/Concerns: **NEW** - Modular settings integration

- **bfp_audio_engine_product_settings()**
  - Purpose: Product-specific engine override UI
  - Inputs: `$product_id`
  - Outputs: Override dropdown HTML
  - WordPress Data: Product and global engine settings
  - Data Flow: Check for override → Show appropriate options
  - Patterns/Concerns: **NEW** - Follows global/product override pattern

### **File:** `/modules/cloud-engine.php`
**Purpose:** Cloud storage integration module placeholder
**WordPress Integration Points:**
- Actions: `bfp_module_general_settings`
**Status:** Stub implementation for future cloud features

---

## JavaScript Files (Enhanced)

### **File:** `/js/engine.js`
**Purpose:** Main frontend player functionality with multi-engine support
**Key Features:**
- **NEW** - Dynamic audio engine detection and initialization
- **NEW** - WaveSurfer.js integration with fallback
- **IMPROVED** - Context-aware player controls
- **IMPROVED** - Play button on cover functionality
- **NEW** - Smooth fade out for both engines

### **File:** `/js/wavesurfer.js`
**Purpose:** WaveSurfer.js specific integration
**Key Features:**
- **NEW** - Waveform visualization
- **NEW** - MediaElement-compatible API wrapper
- **NEW** - Smooth audio fading
- **NEW** - Responsive waveform rendering

### **File:** `/js/admin.js`
**Purpose:** Admin interface functionality
**Key Features:**
- Demo file management
- Settings UI interactions
- Media library integration

---

## Page Builder Integrations

### **File:** `/builders/builders.php`
**Purpose:** Centralized page builder support
**Supported Builders:**
- Gutenberg (block editor)
- Elementor
**Key Features:**
- **IMPROVED** - Server-side block rendering
- Dynamic shortcode processing
- Category registration

---

## State Management Flow (New Architecture)

1. **Configuration Layer** (`BFP_Config`)
   - Central state repository
   - Context-aware inheritance (Product → Global → Default)
   - Bulk retrieval optimization
   - Smart override detection

2. **Component Layer**
   - Each component manages its own state
   - Components request state via config
   - No direct access to WordPress options/meta

3. **Rendering Layer**
   - Renderers use bulk state fetching
   - Context detection for appropriate output
   - Separation of data and presentation

4. **Hook Management**
   - Dynamic registration based on context
   - Prevents duplicate players
   - Handles on_cover functionality cleanly

---

## Key Architectural Improvements

1. **Separation of Concerns**
   - Each class has a single, well-defined responsibility
   - Components are loosely coupled via main plugin class
   - Rendering logic separated from data processing

2. **State Management**
   - Centralized configuration with smart inheritance
   - Bulk operations for performance
   - Context-aware settings resolution

3. **Modular Architecture**
   - Dynamic module loading system
   - Easy to add new features via modules
   - Settings integration via action hooks

4. **Performance Optimizations**
   - Bulk settings retrieval
   - Lazy loading of components
   - Smart script/style enqueuing

5. **Context Awareness**
   - Players adapt to page context
   - Hooks registered dynamically
   - Controls change based on location

6. **Extensibility**
   - Multiple filter/action points
   - Module system for features
   - Clean APIs for third-party integration

---

## Security Improvements

1. **Input Validation**
   - Centralized nonce verification
   - Proper sanitization in all user inputs
   - Capability checks before operations

2. **File Operations**
   - Secure file paths
   - Protected upload directories
   - Safe file streaming

3. **Data Access**
   - No direct superglobal access in components
   - Proper escaping in all outputs
   - SQL injection prevention

---

## Future Considerations

1. **API Development**
   - REST API endpoints for player operations
   - AJAX handlers modernization
   - GraphQL support potential

2. **Performance**
   - Object caching integration
   - Lazy loading improvements
   - Database query optimization

3. **Features**
   - Enhanced cloud storage
   - Advanced analytics
   - AI-powered recommendations