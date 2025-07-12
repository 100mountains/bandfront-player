#!/bin/bash

# Script to update WaveSurfer.js to the latest pre-built version
# Now located in build/ directory

# Get the plugin root directory (parent of build/)
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_DIR="$(dirname "$SCRIPT_DIR")"

echo "Updating WaveSurfer.js..."

# Change to plugin directory
cd "$PLUGIN_DIR" || exit 1

# Create vendors/wavesurfer directory if it doesn't exist
mkdir -p vendors/wavesurfer

# Change to the wavesurfer directory
cd vendors/wavesurfer || exit 1

# Download the latest WaveSurfer.js files
echo "Downloading WaveSurfer.js core files..."
wget -q -O wavesurfer.min.js https://unpkg.com/wavesurfer.js@7/dist/wavesurfer.min.js || {
    echo "❌ Failed to download wavesurfer.min.js"
    exit 1
}
echo "✅ Downloaded wavesurfer.min.js"

wget -q -O wavesurfer.esm.js https://unpkg.com/wavesurfer.js@7/dist/wavesurfer.esm.js || {
    echo "❌ Failed to download wavesurfer.esm.js"
    exit 1
}
echo "✅ Downloaded wavesurfer.esm.js"

# Download plugins
echo "Downloading WaveSurfer.js plugins..."
mkdir -p plugins

# Download regions plugin
echo "Downloading regions plugin..."
wget -q -O plugins/regions.min.js https://unpkg.com/wavesurfer.js@7/dist/plugins/regions.min.js || {
    echo "Failed to download regions plugin"
    exit 1
}
echo "✅ Downloaded regions.min.js"

# Download timeline plugin
echo "Downloading timeline plugin..."
wget -q -O plugins/timeline.min.js https://unpkg.com/wavesurfer.js@7/dist/plugins/timeline.min.js || {
    echo "Failed to download timeline plugin"
    exit 1
}
echo "✅ Downloaded timeline.min.js"

# Download minimap plugin
echo "Downloading minimap plugin..."
wget -q -O plugins/minimap.min.js https://unpkg.com/wavesurfer.js@7/dist/plugins/minimap.min.js || {
    echo "Failed to download minimap plugin"
    exit 1
}
echo "✅ Downloaded minimap.min.js"

# Create version file to track current version
echo "7.9.9" > version.txt
echo "Updated: $(date)" >> version.txt

echo ""
echo "✅ WaveSurfer.js updated successfully!"
echo "📁 Files downloaded to: $PLUGIN_DIR/vendors/wavesurfer/"
echo ""
echo "Available plugins:"
echo "  ✓ regions.min.js (for audio regions/selections)"
echo "  ✓ timeline.min.js (for timeline display)"
echo "  ✓ minimap.min.js (for waveform overview)"

# Return to original directory
cd "$SCRIPT_DIR" || exit 0