# Bandfront Player

A professional WordPress audio player plugin for WooCommerce that transforms product pages into interactive music stores with secure preview capabilities and advanced analytics.

[![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-0073aa.svg)](https://wordpress.org)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-7.0%2B-96588a.svg)](https://woocommerce.com)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777bb4.svg)](https://php.net)
[![License](https://img.shields.io/badge/License-ANARCHY%20LICENCE-red.svg)](LICENSE)

## 🚀 overview yo

Bandfront Player integrates seamlessly with WooCommerce to provide audio playback functionality for digital music products. The plugin features context-aware player controls, secure file protection, and analytics tracking.

### 🎧 Core Player Features

### Audio Engine Support
- **MediaElement.js**: Cross-browser HTML5 audio player with fallback support
- **WaveSurfer.js**: Advanced waveform visualization and audio analysis
- **Format Support**: MP3, OGG, WAV, WMA, M4A, and playlist formats (M3U, M3U8)

### Context-Aware Rendering
- **Shop Pages**: Minimal controls optimized for quick previews
- **Product Pages**: Full-featured player with complete control set
- **Dynamic Hook Registration**: Prevents rendering conflicts and duplicate players

### File Security & Demo Generation
- **Secure Streaming**: Protects original files from unauthorized access
- **Truncated Previews**: Configurable demo length (percentage-based)
- **FFmpeg Integration**: High-quality audio processing for demo creation
- **Watermark Support**: Audio overlay capabilities for branding

### Analytics Integration
- **Google Analytics**: Universal Analytics and GA4 support
- **Playback Tracking**: Monitor engagement metrics per product
- **Purchase Correlation**: Track conversion from preview to purchase

## Technical Architecture

### Component Structure
```
BandfrontPlayer (Main Class)
├── BFP_Config (State Management)
├── BFP_Audio_Engine (File Processing)
├── BFP_Player (HTML Generation)
├── BFP_Hooks (WordPress Integration)
├── Renderers/
│   ├── BFP_Player_Renderer
│   ├── BFP_Playlist_Renderer
│   └── BFP_Cover_Renderer
└── Utilities/
    ├── BFP_File_Handler
    ├── BFP_Cloud_Tools
    ├── BFP_Cache
    └── BFP_Analytics
```

### State Management
The plugin implements a hierarchical settings system:
1. Product-specific overrides (highest priority)
2. Global plugin settings
3. Default fallback values

### Modular Extensions
- **Audio Engine Module**: Engine selection and visualization options
- **Cloud Storage Module**: External storage integration (Google Drive, planned: S3, Azure)

## Installation & Configuration

### Requirements
- WordPress 6.0 or higher
- WooCommerce 7.0 or higher
- PHP 7.4 or higher
- Optional: FFmpeg for advanced audio processing

### Setup Process
1. Upload plugin files to `/wp-content/plugins/bandfront-player/`
2. Activate through WordPress admin interface
3. Configure global settings: `Admin → Bandfront Player`
4. Enable players on individual products as needed

### Global Settings

**Player Configuration**
- Audio engine selection (MediaElement.js/WaveSurfer.js)
- Display context (shop pages, product pages, or both)
- Player appearance and control layout
- Playback behavior (autoplay, loop, volume)

**Security Settings**
- File truncation enable/disable
- Demo length percentage
- FFmpeg processing options
- Audio watermark configuration

**Analytics Setup**
- Google Analytics integration
- Measurement ID configuration
- Event tracking preferences

## Usage

### Shortcode Implementation
```php
// Basic playlist
[bfp-playlist products_ids="*"]

// Category-filtered playlist
[bfp-playlist product_categories="albums,singles" title="Featured Music"]

// Purchased products only
[bfp-playlist purchased_products="1"]

// Custom styling
[bfp-playlist layout="new" player_style="dark" cover="1"]
```

### Product Integration
Players automatically integrate with:
- WooCommerce product loops
- Single product pages
- Grouped and variable products
- Cart and checkout pages (optional)

### Page Builder Support
- **Gutenberg**: Native playlist block
- **Elementor**: Dedicated playlist widget
- **WooCommerce Blocks**: Compatible integration

## Developer API

### Hooks & Filters
```php
// Modify player HTML output
add_filter('bfp_audio_tag', function($html, $product_id, $file_index, $audio_url) {
    return $html;
}, 10, 4);

// Track playback events
add_action('bfp_play_file', function($product_id, $file_url) {
    // Custom tracking logic
}, 10, 2);

// Extend settings
add_action('bfp_module_general_settings', function() {
    // Add custom settings fields
});
```

### State Management
```php
// Access plugin instance
global $BandfrontPlayer;

// Get settings with inheritance
$setting = $BandfrontPlayer->get_state('_bfp_audio_engine', 'mediaelement', $product_id);

// Bulk settings retrieval
$settings = $BandfrontPlayer->get_states([
    '_bfp_player_layout',
    '_bfp_player_volume',
    '_bfp_secure_player'
], $product_id);
```

## Performance Considerations

### Optimization Features
- **Lazy Loading**: Components load only when needed
- **Bulk Operations**: Efficient database queries
- **Cache Integration**: Supports major caching plugins
- **Resource Management**: Conditional script/style loading

## File Structure

```
bandfront-player/
├── bfp.php                 # Main plugin file
├── includes/               # Core classes
│   ├── state-manager.php   # Configuration management
│   ├── audio.php          # Audio processing
│   ├── player.php         # Player generation
│   ├── hooks.php          # WordPress integration
│   └── utils/             # Utility classes
├── modules/               # Feature modules
├── views/                 # Admin templates
├── js/                    # Frontend scripts
├── css/                   # Stylesheets
│   └── skins/            # Theme variations
└── vendors/              # Third-party libraries
```

## Changelog

### Version 5.0.181
- Refactored architecture with improved component separation
- Enhanced state management system
- Added WaveSurfer.js engine support
- Improved context-aware rendering
- Enhanced security and performance optimizations

## License

This project is 

## Support

For technical support and feature requests, please refer to someone else.

