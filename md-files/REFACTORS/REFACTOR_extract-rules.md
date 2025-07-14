# Refactoring Extraction Rules

## Core Functionality to Extract

Focus on extracting, explaining, and documenting code related to:

1. **Player Logic**  
   - Context-aware player (main shop page, product page)
   - Single player or full playlist selection
   - Track highlighting and playback
   - Renderer logic for product cover on front page

2. **Widget Rendering**

3. **Block Playlist Rendering**  
   - Playlist renders immediately upon selection

4. **File Storage & Retrieval**  
   - Logic for storing, retrieving, loading, and determining file URLs

5. **Relevant State Management**  
   - Any code handling the above, especially if related to variables in `MAP_STATE_CONFIG_&_VARIABLES.md`  
   - Note: Variable names may have changed (e.g., `bfp_` or `wcmp_` prefixes)

## New Codebase Requirements

- Transfer all relevant logic, icons, and assets as per `AI-CODE-RULES`
- Maintain original functionality within the new system
- Use default CSS players (PNG/icon support may be added later)
- Remove overrides; use `state-manager.php`
- Use a class-based, separated-concerns architecture

## Extraction Guidelines

To extract code, ensure:

1. It is relevant to points 1–4 above
2. Only logic is extracted—avoid duplication
3. Each extraction clearly lists required variables (using new naming from `MAP_STATE_CONFIG_&_VARIABLES.md`)
4. Context of use is shown (e.g., widget, block)
5. Inputs, outputs, and function types are documented (PHP/JS as needed)


### 1. Player Initialization
- **Current**: Monolithic `generate_the_wcmp` function
- **Extract to**: `PlayerInitializer` class with methods:
  - `init()`: Main initialization
  - `preventDuplicateInit()`: Check for existing instances
  - `checkLoadTiming()`: Handle onload settings
  - `registerPlayers()`: Add players to global registry

### 2. Player Configuration
- **Current**: Inline configuration objects
- **Extract to**: `PlayerConfig` class:
  - `getDefaultConfig()`: Base MediaElement settings
  - `getTrackConfig()`: Track-specific settings
  - `applyUserSettings()`: Merge with global settings

### 3. Event Management
- **Current**: Inline event handlers in success callback
- **Extract to**: `PlayerEventManager`:
  - `attachPlayingHandler()`: Analytics and UI updates
  - `attachTimeUpdateHandler()`: Fade effects and volume
  - `attachEndedHandler()`: Loop and playlist logic
  - `attachVolumeHandler()`: Volume state management

### 4. UI State Controller
- **Current**: jQuery DOM manipulation throughout
- **Extract to**: `PlayerUIController`:
  - `updatePlayingState()`: Manage .wcmp-playing class
  - `positionOverlayPlayer()`: Calculate overlay positions
  - `togglePlayerVisibility()`: Show/hide logic
  - `handleResponsive()`: Resize event handling

### 5. Playlist Manager
- **Current**: `_playNext` function with complex logic
- **Extract to**: `PlaylistManager`:
  - `getNextTrack()`: Determine next track
  - `handleLoop()`: Loop boundary detection
  - `playTrack()`: Unified play interface
  - `isInSameLoop()`: Loop container checking

### 6. Integration Adapters
- **Current**: Direct jQuery selectors for different product types
- **Extract to**: Integration adapter pattern:
  - `WooCommerceAdapter`: Standard products
  - `GroupedProductAdapter`: Grouped products
  - `AjaxProductAdapter`: AJAX-loaded products

### 7. File Protection Handler
- **Current**: Server-side only
- **Extract to**: Client-side awareness:
  - `ProtectedFileHandler`: Demo vs full file logic
  - `UserPermissionChecker`: Access validation
  - `FileURLProcessor`: URL transformation

## Extraction Patterns

### Singleton Services
```javascript
class PlayerService {
    static instance = null;
    
    static getInstance() {
        if (!this.instance) {
            this.instance = new PlayerService();
        }
        return this.instance;
    }
}
```

### Event Emitter Pattern
```javascript
class PlayerEventEmitter {
    constructor() {
        this.events = {};
    }
    
    on(event, callback) {
        if (!this.events[event]) {
            this.events[event] = [];
        }
        this.events[event].push(callback);
    }
    
    emit(event, data) {
        if (this.events[event]) {
            this.events[event].forEach(cb => cb(data));
        }
    }
}
```

### Factory Pattern for Players
```javascript
class PlayerFactory {
    static create(element, type) {
        switch(type) {
            case 'track':
                return new TrackPlayer(element);
            case 'full':
                return new FullPlayer(element);
            case 'single':
                return new SinglePlayer(element);
        }
    }
}
```

## Refactoring Steps

1. **Phase 1**: Extract utility functions
   - DOM helpers
   - URL processors
   - State validators

2. **Phase 2**: Create service classes
   - Configuration management
   - Event handling
   - State management

3. **Phase 3**: Implement managers
   - Player lifecycle
   - Playlist control
   - UI synchronization

4. **Phase 4**: Integration layer
   - WooCommerce hooks
   - AJAX handlers
   - Third-party plugins

5. **Phase 5**: Modern syntax
   - ES6 modules
   - Async/await
   - Promise-based APIs

