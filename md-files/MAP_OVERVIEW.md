# Bandfront Player - Architecture Overview

## Core Purpose
WordPress plugin that adds audio players to WooCommerce products with support for demos, playlists, and multiple audio engines (MediaElement.js/WaveSurfer.js).

## Architecture Pattern
Component-based architecture with centralized state management and context-aware rendering.

## System Architecture

### Component Initialization Flow
```
WordPress Init
    ↓
BandfrontPlayer (Main Orchestrator)
    ↓
Components initialized in order:
Config → File Handler → Player Manager → Audio Engine → 
WooCommerce → Hooks → Renderers → Utilities → Admin
```

### State Management Hierarchy
```
Product-Specific Settings (Highest Priority)
    ↓ (fallback)
Global Plugin Settings
    ↓ (fallback)
Default Values (Lowest Priority)
```

## Core Subsystems

### 1. **State Management System**
- Centralized configuration with intelligent inheritance
- Context-aware resolution (product vs global settings)
- Bulk retrieval optimization for performance
- Module enable/disable management

### 2. **Player Rendering System**
- Multiple player types (single, playlist, table)
- Context-aware controls (full vs button-only)
- Audio engine abstraction (MediaElement.js/WaveSurfer.js)
- Responsive layouts with skin support

### 3. **Audio Processing System**
- Secure file streaming with optional truncation
- Demo file generation (PHP-MP3 or FFmpeg)
- Cloud storage URL processing
- Analytics tracking integration

### 4. **Hook Management System**
- Dynamic registration based on page context
- Prevents duplicate players
- Integration with WooCommerce hooks
- Cover overlay functionality

### 5. **Admin Interface System**
- Modular settings architecture
- Product-specific overrides
- Bulk operations support
- AJAX-powered saves

## Data Flow Patterns

### Player Rendering Flow
```
Hook Triggered → Context Detection → State Resolution → 
File Validation → Engine Selection → HTML Generation → Output
```

### Audio Streaming Flow
```
Play Request → Security Check → Analytics Tracking → 
File Processing → Demo Generation (if needed) → Stream/Redirect
```

### Settings Save Flow
```
Form Submission → Validation → State Update → 
Cache Clear → Module Reload → Success Response
```

## Key Features

### Context Awareness
- Shop pages: Button-only players with cover overlays
- Product pages: Full control players below price
- Playlist shortcodes: Table or list layouts
- Admin detection: Load only necessary components

### Module System
Extensible architecture supporting:
- **Core Modules**: Audio engine selection
- **Optional Modules**: Cloud storage integration
- **Future Modules**: Easy to add via module interface

### Security Layers
1. **File Security**: Protected upload directories
2. **Demo Security**: Truncation for non-purchasers
3. **Access Control**: Purchase/registration verification
4. **Input Validation**: Sanitization at all entry points

### Performance Optimizations
- Lazy component loading
- Bulk state retrieval
- Conditional resource enqueuing
- Memory caching within request
- Smart defaults to reduce queries

## Integration Points

### WordPress Core
- Action hooks for initialization
- Filter system for extensibility
- Shortcode API for playlists
- Admin menu system
- AJAX handlers

### WooCommerce
- Product meta integration
- Purchase verification
- Order tracking
- Product table support
- Cart/checkout compatibility

### Third-Party
- Cache plugin compatibility
- Page builder support
- Analytics platforms
- Cloud storage services

## File Organization

```
/bandfront-player/
├── bfp.php                 # Main plugin file
├── includes/               # Core functionality
│   ├── *.php              # Core components
│   └── utils/             # Utility classes
├── modules/               # Feature modules
├── js/                    # Frontend scripts
├── css/                   # Styles and skins
├── views/                 # Admin templates
├── builders/              # Page builder integrations
└── md-files/              # Documentation
```

## Extension Architecture

### Adding New Features
1. Create module in `/modules/`
2. Register with module system
3. Add settings UI components
4. Hook into existing systems

### Custom Integrations
- Filter: `bfp_player_html` - Modify player output
- Action: `bfp_play_file` - Track custom events
- Filter: `bfp_state_value` - Override settings
- Action: `bfp_module_loaded` - Extend modules

## Development Workflow

### Quick Start
1. Main logic flows through `bfp.php`
2. State always via `get_config()->get_state()`
3. Players rendered by player manager
4. Audio handled by audio engine
5. Settings in state manager

### Best Practices
- Always use bulk state retrieval when possible
- Check context before registering hooks
- Sanitize all inputs, escape all outputs
- Use component getters, never direct instantiation
- Follow the established patterns

## For Detailed Information

- **Class Reference**: See `STATE-MANAGEMENT.md` for all classes, methods, and properties
- **Code Details**: See `MAP.md` for line-by-line code documentation
- **Configuration**: See `MAP_STATE_CONFIG_&_VARIABLES.md` for settings reference

This architecture ensures maintainability, extensibility, and performance while providing a robust audio player solution for WooCommerce.