

ANALYSIS PROGRAMS

PHP-STAN

etc

Prompt for Refactoring Constants and Defaults in Bandfront Player

Refactor the codebase so that:

All user-configurable defaults (such as default player style, controls, volume, demo length, etc.) are managed exclusively via the state manager (options/settings), not as PHP constants. These should be accessible and changeable via the admin UI and stored in the database.
Technical/plugin constants (such as plugin version, file paths, plugin base name, plugin URL, and remote timeouts) remain as PHP constants, since they are not user-configurable and are required for plugin bootstrapping or internal logic.
Remove any redundant PHP constants that duplicate values now managed by the state manager. For example, if BFP_DEFAULT_PLAYER_LAYOUT is now a default in the state manager, remove the constant and update all usages to fetch from the state manager.
Update all usages throughout the codebase (including in player.php, audio.php, woocommerce.php, etc.) to retrieve user-facing defaults from the state manager, not from constants.
Keep backward compatibility where possible by providing fallback defaults in the state manager for legacy installs.
Summary:

Do not move everything to state management.
Do move only user-facing defaults to state/options.
Keep technical/plugin constants as PHP constants.
Remove any constants that are now redundant due to state management.



Refactor the codebase so that:

**User-configurable defaults** (such as default player style, controls, volume, demo length, etc.) are managed exclusively via the state manager (options/settings), not as PHP constants. These should be accessible and changeable via the admin UI and stored in the database.

**Technical/plugin constants** (such as plugin version, file paths, plugin base name, plugin URL, and remote timeouts) remain as PHP constants, since they are not user-configurable and are required for plugin bootstrapping or internal logic.

**Remove redundant PHP constants** that duplicate values now managed by the state manager. For example, if `BFP_DEFAULT_PLAYER_LAYOUT` is now a default in the state manager, remove the constant and update all usages to fetch from the state manager.

**Update all usages** throughout the codebase (including in `player.php`, `audio.php`, `woocommerce.php`, `global-admin-options.php`, etc.) to retrieve user-facing defaults from the state manager using `$this->main_plugin->get_state()` or similar methods.

**Constants to keep as PHP constants:**
- `BFP_VERSION`, `BFP_PLUGIN_PATH`, `BFP_PLUGIN_BASE_NAME`, `BFP_PLUGIN_URL`
- `BFP_WEBSITE_URL`, `BFP_REMOTE_TIMEOUT`

**Constants to move to state manager:**
- `BFP_FILE_PERCENT`, `BFP_DEFAULT_PLAYER_LAYOUT`, `BFP_DEFAULT_PLAYER_CONTROLS`, `BFP_DEFAULT_PLAYER_VOLUME`

**Summary:**
- Keep technical constants as PHP constants
- Move user-facing defaults to state management  
- Remove redundant constants
- Update all usages to use state manager








-----


In WordPress (especially Gutenberg block development):
WordPress now supports ES modules through modern tooling like Webpack, Vite, or the built-in @wordpress/scripts. This lets you:

Use JSX (like React HTML syntax)

Import only what you need from @wordpress/* packages

Get better bundling, linting, and tree-shaking

ES module vs classic script:
Classic script: uses global variables (window.wp.blocks, etc.)

ES module: uses import { registerBlockType } from '@wordpress/blocks' and compiles via tooling

If you're writing a modern Gutenberg block, ES module is the recommended way — especially if you're using @wordpress/scripts with a build step. Want an example of your block rewritten in ES module style with JSX?




FIREFOX ISSUES ON PLAYBACK - LATER TRACKS NOT PLAYING
CHECK PRELOAD ATTRIBS

<audio preload="metadata">  <!-- Firefox might interpret this aggressively -->
<audio preload="none">      <!-- Better for limiting preload -->

