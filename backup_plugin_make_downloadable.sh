#!/bin/bash

# Backup script for bandfront-player plugin
# Must be run as root to access /root directory
# Usage: sudo ./backup_plugin.sh [backup_name]

BACKUP_DIR="/root"
PLUGIN_DIR="/var/www/html/wp-content/plugins/bandfront-player"

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    echo "❌ Error: This script must be run as root"
    echo "Usage: sudo ./backup_plugin.sh"
    exit 1
fi

# Check if we're in the right directory
if [ "$(pwd)" != "$PLUGIN_DIR" ]; then
    echo "❌ Error: Must be run from $PLUGIN_DIR"
    exit 1
fi

# Get the next backup number by finding the highest existing number
HIGHEST_NUM=$(ls -1 $BACKUP_DIR/minimised-bandfront-worky*.zip 2>/dev/null | grep -oE 'worky([0-9]+)\.zip$' | grep -oE '[0-9]+' | sort -n | tail -1)

if [ -z "$HIGHEST_NUM" ]; then
    NEXT_NUM=1
else
    NEXT_NUM=$((HIGHEST_NUM + 1))
fi

# Use provided name or default naming
if [ -n "$1" ]; then
    BACKUP_NAME="minimised-bandfront-$1.zip"
else
    BACKUP_NAME="minimised-bandfront-worky$NEXT_NUM.zip"
fi

BACKUP_PATH="$BACKUP_DIR/$BACKUP_NAME"

# Check if backup already exists
if [ -f "$BACKUP_PATH" ]; then
    echo "❌ Error: Backup already exists: $BACKUP_PATH"
    echo "Will not overwrite existing backup."
    exit 1
fi

echo "Creating backup: $BACKUP_NAME"
zip -r "$BACKUP_PATH" . > /dev/null 2>&1

if [ $? -eq 0 ]; then
    echo "✅ Backup created successfully: $BACKUP_PATH"
    echo "📁 Backup size: $(ls -lh $BACKUP_PATH | awk '{print $5}')"
    echo "📊 Next backup will be: minimised-bandfront-worky$((NEXT_NUM + 1)).zip"
else
    echo "❌ Backup failed!"
    exit 1
fi

# Make backup publicly accessible
echo "🌐 Making backup publicly downloadable..."

PUBLIC_DIR="/var/www/html"
PUBLIC_BACKUP_NAME="bandfront-player-latest.zip"
PUBLIC_BACKUP_PATH="$PUBLIC_DIR/$PUBLIC_BACKUP_NAME"

# Remove any existing public backup
if [ -f "$PUBLIC_BACKUP_PATH" ]; then
    echo "🗑️  Removing previous public backup: $PUBLIC_BACKUP_NAME"
    rm -f "$PUBLIC_BACKUP_PATH"
fi

# Copy the latest backup to public directory
echo "📋 Copying backup to public directory..."
cp "$BACKUP_PATH" "$PUBLIC_BACKUP_PATH"

if [ $? -eq 0 ]; then
    # Change ownership to allow public access
    echo "🔓 Setting permissions for public access..."
    chown www-data:www-data "$PUBLIC_BACKUP_PATH"
    chmod 644 "$PUBLIC_BACKUP_PATH"
    
    echo "✅ Backup is now publicly downloadable!"
    echo "🔗 Download URL: https://therob.lol/$PUBLIC_BACKUP_NAME"
    echo "📁 Public backup size: $(ls -lh $PUBLIC_BACKUP_PATH | awk '{print $5}')"
else
    echo "❌ Failed to copy backup to public directory!"
    exit 1
fi

echo ""
echo "https://therob.lol/bandfront-player-latest.zip"
