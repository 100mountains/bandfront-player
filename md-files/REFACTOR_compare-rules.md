transferring player guts from old codebase

** what we like and want to have back **

- allowed selection of tracks on full playlist, selection of track hightlighted it, and pressing the track played it.

- widget rendering worked ! 

- block rendered a playlist as soon as you selected it! 

## Key Changes

### 1. **URL Conversion System (NEW)**
- **Added `getAudioSource()` function** - Detects and converts action URLs to streaming URLs
- **Added `convertToStreamingUrl()` function** - Converts `bfp-action=play` URLs to `bfp-stream=1` URLs
- **Real-time source updating** - Updates DOM attributes with streaming URLs to prevent redirects

### 2. **Enhanced Error Handling**
- **MediaElement.js error recovery** - Tries to convert URLs when initialization fails
- **Source validation** - Checks for valid sources before player initialization
- **Fallback mechanisms** - Falls back to native HTML5 audio if MediaElement.js fails
- **Better logging** - Added comprehensive console logging for debugging

### 3. **Fixed Grouped Products Issue**
- **Simplified DOM manipulation** - Used the working version's approach that doesn't aggressively move elements
- **Preserved layout** - Prevented player controls from moving around the page
- **Multiple selector fallbacks** - Tries different selectors to find grouped products

### 4. **Improved Player Initialization**
- **Better engine detection** - Proper WaveSurfer/MediaElement.js detection with fallbacks
- **Duplicate prevention** - Prevents multiple initializations
- **State management** - Proper tracking of initialized players
- **Source verification** - Validates audio sources before creating players

### 5. **Enhanced Single Player Mode**
- **Proper event handling** - Uses namespaced events to prevent conflicts
- **Title clicking** - Fixed track selection by clicking titles
- **Player switching** - Proper hiding/showing of player containers
- **Audio control** - Pauses other tracks when switching

### 6. **Play on Cover Functionality**
- **Multiple selector attempts** - Tries different ways to find associated audio
- **Event management** - Prevents duplicate event handlers
- **State synchronization** - Button state matches audio playback state

### 7. **Audio Processing Improvements**
- **Fade out support** - Enhanced fade functionality for both engines
- **Volume management** - Better volume handling and restoration
- **Duration handling** - Support for estimated and fixed durations
- **Loop support** - Proper looping functionality

### 8. **AJAX and Dynamic Content**
- **Re-initialization triggers** - Handles various AJAX events from other plugins
- **Content detection** - Checks for uninitialized players after page changes
- **Event cleanup** - Removes old event handlers before re-initialization

### 9. **iOS Compatibility**
- **Native controls option** - Support for iOS native controls
- **Touch handling** - Proper touch event management
- **Audio preprocessing** - iOS-specific audio preparation

### 10. **Structure and Organization**
- **Modular functions** - Broke down functionality into smaller, manageable functions
- **Better variable scoping** - Proper global variable management
- **Consistent naming** - Used consistent function and variable naming
- **Documentation** - Added comprehensive function documentation

## What This Fixes

✅ **Audio playback** - No more redirects to product pages  
✅ **Track selection** - Clicking titles now switches tracks properly  
✅ **Layout stability** - Player controls stay in their correct positions  
✅ **Error recovery** - Handles failed initializations gracefully  
✅ **Multiple engines** - Works with both MediaElement.js and WaveSurfer  
✅ **Dynamic content** - Handles AJAX-loaded content properly  
✅ **Play on cover** - Cover buttons work on shop pages  
✅ **Mobile compatibility** - Better iOS/mobile support  

## Core Architecture Changes

1. **From direct DOM manipulation** → **State-managed initialization**
2. **From single initialization** → **Re-initialization capability**
3. **From action URLs** → **Streaming URLs**
4. **From aggressive DOM changes** → **Minimal layout disruption**
5. **From basic error handling** → **Comprehensive error recovery**

The result is a much more robust, maintainable, and reliable audio player system that preserves all the functionality you wanted while fixing the core issues that were preventing proper playback.