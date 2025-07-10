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

# Generate POT file
echo "📝 Generating POT file..."
wp i18n make-pot . "$LANGUAGES_DIR/bandfront-player.pot" \
    --domain="bandfront-player" \
    --package-name="Bandfront Player" \
    --package-version="0.1" \
    --headers='{"Report-Msgid-Bugs-To":"https://wordpress.org/support/plugin/bandfront-player","Language-Team":"LANGUAGE <LL@li.org>"}' \
    --file-comment="Copyright (C) 2025 Bleep\nThis file is distributed under the same license as the Bandfront Player plugin."

if [ $? -eq 0 ]; then
    echo "✅ POT file generated successfully"
else
    echo "❌ Failed to generate POT file"
    exit 1
fi

# Update existing PO files
echo "🔄 Updating existing PO files..."
for po_file in "$LANGUAGES_DIR"/*.po; do
    if [ -f "$po_file" ]; then
        echo "   Updating $(basename "$po_file")"
        wp i18n update-po "$LANGUAGES_DIR/bandfront-player.pot" "$po_file"
    fi
done

# Generate MO files from PO files
echo "🔧 Generating MO files..."
for po_file in "$LANGUAGES_DIR"/*.po; do
    if [ -f "$po_file" ]; then
        mo_file="${po_file%.po}.mo"
        echo "   Generating $(basename "$mo_file")"
        wp i18n make-mo "$po_file" "$mo_file"
    fi
done

echo "✅ Translation update completed successfully!"
echo "📊 Summary:"
echo "   - POT file: $LANGUAGES_DIR/bandfront-player.pot"
echo "   - PO files updated: $(ls -1 "$LANGUAGES_DIR"/*.po 2>/dev/null | wc -l)"
echo "   - MO files generated: $(ls -1 "$LANGUAGES_DIR"/*.mo 2>/dev/null | wc -l)"

# Return to original directory
cd "$SCRIPT_DIR" || exit 0
