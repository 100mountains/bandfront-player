#!/bin/bash

# Script to remove debug logs from PHP files
# Usage: 
#   remove-debug.sh              # Remove from all files
#   remove-debug.sh /path/file   # Remove from specific file

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_DIR="$(dirname "$SCRIPT_DIR")"

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to process a single file
process_file() {
    local file="$1"
    local backup_file="${file}.debug-backup"
    
    # Check if file exists and is readable
    if [[ ! -f "$file" ]]; then
        echo -e "${RED}Error: File '$file' does not exist${NC}"
        return 1
    fi
    
    if [[ ! -r "$file" ]]; then
        echo -e "${RED}Error: Cannot read file '$file'${NC}"
        return 1
    fi
    
    # Create backup
    if ! cp "$file" "$backup_file" 2>/dev/null; then
        echo -e "${RED}Error: Cannot create backup for '$file'${NC}"
        return 1
    fi
    
    # Count debug lines before removal
    local debug_lines_before=0
    if grep -q '// DEBUG-REMOVE$' "$file" 2>/dev/null; then
        debug_lines_before=$(grep -c '// DEBUG-REMOVE$' "$file")
    fi
    
    # Remove lines ending with // DEBUG-REMOVE
    if sed -i '/\/\/ DEBUG-REMOVE$/d' "$file" 2>/dev/null; then
        # Check if file was actually modified by comparing with backup
        if ! cmp -s "$file" "$backup_file"; then
            # File was modified - lines were removed
            echo -e "  ${GREEN}✓${NC} Cleaned: $(basename "$file") (removed $debug_lines_before debug lines)"
            rm "$backup_file"
            return 0
        else
            # File was not modified - no debug lines found
            echo -e "  ${BLUE}•${NC} No changes: $(basename "$file")"
            rm "$backup_file"
            return 2  # No changes made
        fi
    else
        echo -e "${RED}Error: Failed to process '$file'${NC}"
        rm "$backup_file" 2>/dev/null || true
        return 1
    fi
}

# Function to process all files
process_all_files() {
    local src_dir="$PLUGIN_DIR/src"
    
    if [[ ! -d "$src_dir" ]]; then
        echo -e "${RED}Error: Source directory '$src_dir' does not exist${NC}"
        exit 1
    fi
    
    echo -e "${YELLOW}Removing debug logs from all PHP files in $src_dir...${NC}"
    
    # Initialize counters
    local total_files=0
    local modified_files=0
    local error_files=0
    local unchanged_files=0
    
    # Process each PHP file
    while IFS= read -r -d '' file; do
        ((total_files++))
        process_file "$file"
        local result=$?
        case $result in
            0) ((modified_files++)) ;;
            1) ((error_files++)) ;;
            2) ((unchanged_files++)) ;;
        esac
    done < <(find "$src_dir" -name "*.php" -type f -print0)
    
    echo
    echo -e "${GREEN}Debug removal complete!${NC}"
    echo "Total files processed: $total_files"
    echo "Files modified: $modified_files"
    echo "Files unchanged: $unchanged_files"
    echo "Files with errors: $error_files"
}

# Main execution
if [[ $# -eq 0 ]]; then
    # No arguments - process all files
    process_all_files
elif [[ $# -eq 1 ]]; then
    # Single file argument
    target_file="$1"
    
    # Convert relative path to absolute if needed
    if [[ ! "$target_file" = /* ]]; then
        target_file="$(pwd)/$target_file"
    fi
    
    echo -e "${YELLOW}Removing debug logs from: $target_file${NC}"
    
    process_file "$target_file"
    result=$?
    case $result in
        0) 
            echo -e "${GREEN}Successfully cleaned debug logs from file${NC}"
            exit 0
            ;;
        1) 
            echo -e "${RED}Failed to process file${NC}"
            exit 1
            ;;
        2) 
            echo -e "${BLUE}No debug logs found in file${NC}"
            exit 0
            ;;
    esac
else
    echo -e "${RED}Usage: $0 [file_path]${NC}"
    echo "  $0                    # Remove debug logs from all PHP files"
    echo "  $0 /path/to/file.php  # Remove debug logs from specific file"
    exit 1
fi