#!/bin/bash

# Translation update script for Bandfront Player
# Generates POT files and updates translations

# Get the plugin root directory (parent of build/)
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_DIR="$(dirname "$SCRIPT_DIR")"
LANGUAGES_DIR="$PLUGIN_DIR/languages"

# Change to plugin directory for WP-CLI to work correctly
cd "$PLUGIN_DIR" || {
    echo "❌ Error: Failed to change to plugin directory"
    exit 1
}

# Check if WP-CLI is available
if ! command -v wp &> /dev/null; then
    echo "❌ Error: WP-CLI is required for translation updates"
    echo "Install WP-CLI: https://wp-cli.org/"
    exit 1
fi

echo "🔄 Updating translations for Bandfront Player..."

# Create languages directory if it doesn't exist
mkdir -p "$LANGUAGES_DIR"

# Generate POT file with minimal parameters for compatibility
echo "📝 Generating POT file..."
wp i18n make-pot . "$LANGUAGES_DIR/bandfront-player.pot" \
    --domain="bandfront-player" \
    --exclude="vendor,node_modules,tests,build" 2>/dev/null

if [ $? -eq 0 ]; then
    echo "✅ POT file generated successfully"
else
    echo "⚠️  Warning: Failed to generate POT file (WP-CLI version may be incompatible)"
    # Try without exclude parameter
    echo "📝 Trying alternative POT generation..."
    wp i18n make-pot . "$LANGUAGES_DIR/bandfront-player.pot" --domain="bandfront-player" 2>/dev/null
    
    if [ $? -ne 0 ]; then
        echo "❌ Failed to generate POT file with both methods"
        exit 1
    fi
fi

# Update existing PO files
if [ -f "$LANGUAGES_DIR/bandfront-player.pot" ]; then
    echo "🔄 Updating existing PO files..."
    for po_file in "$LANGUAGES_DIR"/*.po; do
        if [ -f "$po_file" ]; then
            echo "   Updating $(basename "$po_file")"
            wp i18n update-po "$LANGUAGES_DIR/bandfront-player.pot" "$po_file" 2>/dev/null || {
                echo "   ⚠️  Failed to update $(basename "$po_file")"
            }
        fi
    done
fi

# Generate MO files from PO files
echo "🔧 Generating MO files..."
for po_file in "$LANGUAGES_DIR"/*.po; do
    if [ -f "$po_file" ]; then
        mo_file="${po_file%.po}.mo"
        echo "   Generating $(basename "$mo_file")"
        wp i18n make-mo "$po_file" "$mo_file" 2>/dev/null || {
            echo "   ⚠️  Failed to generate $(basename "$mo_file")"
        }
    fi
done

echo "📊 Translation update summary:"
echo "   - POT file: $LANGUAGES_DIR/bandfront-player.pot"
echo "   - PO files found: $(ls -1 "$LANGUAGES_DIR"/*.po 2>/dev/null | wc -l)"
echo "   - MO files generated: $(ls -1 "$LANGUAGES_DIR"/*.mo 2>/dev/null | wc -l)"

# Return to original directory
cd "$SCRIPT_DIR" || exit 0
