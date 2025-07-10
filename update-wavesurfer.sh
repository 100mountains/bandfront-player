#!/bin/bash
# filepath: /var/www/html/wp-content/plugins/bandfront-player/update-wavesurfer.sh

# Script to update WaveSurfer.js to the latest pre-built version

echo "Updating WaveSurfer.js..."

# Create vendors/wavesurfer directory if it doesn't exist
mkdir -p vendors/wavesurfer

# Change to the wavesurfer directory
cd vendors/wavesurfer

# Download the latest WaveSurfer.js files
echo "Downloading WaveSurfer.js core files..."
wget -O wavesurfer.min.js https://unpkg.com/wavesurfer.js@7/dist/wavesurfer.min.js
wget -O wavesurfer.esm.js https://unpkg.com/wavesurfer.js@7/dist/wavesurfer.esm.js

# Download plugins
echo "Downloading WaveSurfer.js plugins..."
mkdir -p plugins

# Download regions plugin
echo "Downloading regions plugin..."
wget -O plugins/regions.min.js https://unpkg.com/wavesurfer.js@7/dist/plugins/regions.min.js

# Download timeline plugin
echo "Downloading timeline plugin..."
wget -O plugins/timeline.min.js https://unpkg.com/wavesurfer.js@7/dist/plugins/timeline.min.js

# Download minimap plugin
echo "Downloading minimap plugin..."
wget -O plugins/minimap.min.js https://unpkg.com/wavesurfer.js@7/dist/plugins/minimap.min.js

# Create version file to track current version
echo "7.9.9" > version.txt
echo "Updated: $(date)" >> version.txt

echo "WaveSurfer.js updated successfully!"
echo "Files downloaded to: vendors/wavesurfer/"
echo ""
echo "Available plugins:"
echo "- regions.min.js (for audio regions/selections)"
echo "- timeline.min.js (for timeline display)"
echo "- minimap.min.js (for waveform overview)"

# Return to plugin root
cd ../..