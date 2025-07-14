
In WordPress (especially Gutenberg block development):
WordPress now supports ES modules through modern tooling like Webpack, Vite, or the built-in @wordpress/scripts. This lets you:

Use JSX (like React HTML syntax)

Import only what you need from @wordpress/* packages

Get better bundling, linting, and tree-shaking

ES module vs classic script:
Classic script: uses global variables (window.wp.blocks, etc.)

ES module: uses import { registerBlockType } from '@wordpress/blocks' and compiles via tooling

If you're writing a modern Gutenberg block, ES module is the recommended way — especially if you're using @wordpress/scripts with a build step. Want an example of your block rewritten in ES module style with JSX?

