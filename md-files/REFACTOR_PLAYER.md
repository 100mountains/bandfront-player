
## Prompt: Fix Bandfront Player Audio Playback and Title Display Issues

The Bandfront Player is showing up but not playing audio files and not displaying titles properly on the product pages. The console shows errors about `groupedSelector` being undefined and MediaElement.js failing to find supported sources.

### Current Issues:
1. `groupedSelector is not defined` error in engine.js
2. Audio files not playing - "The element has no supported sources"
3. Titles not displaying on the full product player
4. Missing `id` parameter when generating players

### Files to Modify:

#### 1. `/var/www/html/wp-content/plugins/bandfront-player/js/engine.js`

- Define the missing `groupedSelector` variable: `var groupedSelector = '.merge_in_grouped_products';`
- Implement `window.generate_the_bfp()` function to initialize all players
- Implement `window.bfp_force_init()` function
- Handle both MediaElement.js and WaveSurfer initialization
- Include proper error handling and logging
- Handle single player mode (clicking titles to switch tracks)
- Handle play on cover functionality
- Ensure audio sources are properly detected from `<source>` tags

Key functions needed:
- `smoothFadeOut()` - for fade out functionality
- `initMediaElementPlayer()` - initialize MediaElement players
- `initSinglePlayerMode()` - handle single player track switching
- `pauseOtherPlayers()` - prevent simultaneous playback

#### 2. `/var/www/html/wp-content/plugins/bandfront-player/includes/player.php`

**In the `include_all_players()` method** (around line 160):
- When calling `$this->get_player()`, add the missing `'id' => $index` parameter in the args array for both single and multiple file cases

**In the `render_player_table()` method** (around line 245):
- When calling `$this->get_player()`, add the missing `'id' => $index` parameter in the args array

These changes ensure the file index is passed to the player, which is needed for generating the correct audio URLs.

### Expected Behavior After Changes:
1. No more JavaScript errors in console
2. Audio files play when clicking play button
3. Titles display correctly for all tracks
4. Single player mode works (clicking title switches track)
5. Play on cover functionality works on shop pages
6. Proper audio URL generation with file indices

### Testing:
1. Check console for any remaining errors
2. Test playing audio on both shop and product pages
3. Verify titles show for all tracks
4. Test single player mode by clicking different track titles
5. Test play on cover buttons on shop pages