# 🎵 Bandfront Player

> A powerful WordPress plugin that integrates music players into WooCommerce product pages, making Bandcamp irrelevant for your music store.

[![WordPress](https://img.shields.io/badge/WordPress-6.8%2B-blue.svg)](https://wordpress.org)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-Compatible-purple.svg)](https://woocommerce.com)
[![License](https://img.shields.io/badge/License-ANARCHY%20LICENCE-red.svg)](LICENSE)

## 🚀 Features

### 🎧 Core Player Features
- **Context-Aware Controls**: Smart player behavior - minimal controls on shop pages for quick previews, full controls on product pages for detailed listening
- **Multiple Player Skins**: Choose from classic, modern, and custom designs
- **Format Support**: OGA, MP3, WAV, WMA, M3U, M3U8 playlists
- **Mobile Optimized**: Works seamlessly on all devices including iPhone, iPad, and Android
- **File Protection**: Secure playback mode to prevent unauthorized downloads

### 🛡️ Security & Protection
- **Demo File Generation**: Automatically create truncated preview versions of audio files
- **FFmpeg Integration**: Advanced audio processing for higher quality demos
- **Watermark Support**: Add audio watermarks to demo files
- **Purchase-Based Access**: Full tracks for buyers, demos for visitors

### 🎨 Page Builder Integration
- **Gutenberg Blocks**: Native block for playlist insertion
- **Elementor Widget**: Drag-and-drop playlist widget

### 📊 Analytics & Insights
- **Google Analytics Integration**: Track play events with Universal Analytics or GA4
- **Playback Counter**: Monitor how often tracks are played
- **Purchase Tracking**: See which products generate the most interest

## 📦 Installation

1. **Download and Extract**
   ```bash
   # Extract the plugin to your WordPress plugins directory
   unzip bandfront-player.zip -d /wp-content/plugins/
   ```

2. **Activate Plugin**
   - Go to `WordPress Admin > Plugins`
   - Find "Bandfront Player" and click "Activate"

3. **Configure Settings**
   - Navigate to `Settings > Bandfront Player`
   - Configure global player settings
   - Set up file protection if needed

4. **Setup Products**
   - Edit your WooCommerce products
   - Enable the music player in the product settings
   - Upload audio files or configure custom demo files

## 🎛️ Configuration

### Global Settings

Access global settings via `WordPress Admin > Bandfront Player`:

#### Player Behavior
- **Context-Aware Controls**: Automatically adjusts controls based on page type
- **Single Player Mode**: One player for all tracks vs individual players
- **Auto-play Next**: Automatically play next track when current ends
- **Loop Tracks**: Endless playback loop
- **Preload Behavior**: None, metadata, or auto

#### File Protection
- **Secure Mode**: Create demo versions of audio files
- **Demo Length**: Percentage of original file to include in demos (e.g., 30%)
- **FFmpeg Processing**: Use FFmpeg for higher quality demo generation
- **Audio Watermarks**: Add watermarks to demo files

#### Analytics
- **Google Analytics**: Track play events
- **Playback Counters**: Monitor track popularity
- **Purchase Analytics**: Track conversion metrics

### Product-Level Settings

Each product can override global settings:

- **Enable Player**: Turn player on/off for specific products
- **Custom Demo Files**: Upload separate demo versions
- **Volume Control**: Set default volume (0.0 to 1.0)
- **Player Behavior**: Product-specific playback settings

## 🎵 Usage Examples

### Basic Playlist Shortcode
```shortcode
[bfp-playlist products_ids="*"]
```

### Filtered by Categories
```shortcode
[bfp-playlist products_ids="*" product_categories="rock,jazz" title="Featured Albums"]
```

### Custom Styling
```shortcode
[bfp-playlist products_ids="123,456,789" layout="new" player_style="mejs-wmp" cover="1"]
```

### Purchased Products Only
```shortcode
[bfp-playlist purchased_products="1" title="Your Music Library"]
```

## 🏗️ Architecture

### Modular Structure
The plugin follows a clean, modular architecture:

````

