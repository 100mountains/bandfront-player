#!/bin/bash

# PHP Documentation Generator for Bandfront Player
# Generates comprehensive docs from PHP files with phpDocumentor

# Get the directory where this script is located (build/)
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_DIR="$(dirname "$SCRIPT_DIR")"
DOCS_DIR="$PLUGIN_DIR/docs"
SRC_DIR="$PLUGIN_DIR/src"

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m'

# Check if phpDocumentor is available
check_phpdoc() {
   if command -v phpdoc >/dev/null 2>&1; then
       echo "phpdoc"
   elif [[ -f "$PLUGIN_DIR/vendor/bin/phpdoc" ]]; then
       echo "$PLUGIN_DIR/vendor/bin/phpdoc"
   elif [[ -f "$PLUGIN_DIR/phpDocumentor.phar" ]]; then
       echo "php $PLUGIN_DIR/phpDocumentor.phar"
   else
       return 1
   fi
}

# Install phpDocumentor if not found
install_phpdoc() {
   echo -e "${YELLOW}phpDocumentor not found. Installing...${NC}"
   
   if command -v composer >/dev/null 2>&1; then
       echo "Installing via Composer..."
       cd "$PLUGIN_DIR"
       composer require --dev phpdocumentor/phpdocumentor
       return $?
   else
       echo "Installing PHAR version..."
       cd "$PLUGIN_DIR"
       wget -q https://phpdoc.org/phpDocumentor.phar
       chmod +x phpDocumentor.phar
       return $?
   fi
}

# Generate phpdoc.xml config
generate_config() {
   local config_file="$PLUGIN_DIR/phpdoc.xml"
   
   cat > "$config_file" << EOF
<?xml version="1.0" encoding="UTF-8" ?>
<phpdocumentor
    configVersion="3"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xmlns="https://www.phpdoc.org"
>
    <paths>
        <output>$DOCS_DIR</output>
        <cache>$PLUGIN_DIR/build/cache/phpdoc</cache>
    </paths>
    <version number="3.0.0">
        <api>
            <source dsn=".">
                <path>$SRC_DIR</path>
            </source>
            <default-package-name>BandfrontPlayer</default-package-name>
            <include-source>true</include-source>
        </api>
    </version>
</phpdocumentor>
EOF

   echo -e "${GREEN}✓${NC} Generated phpdoc.xml config"
}

# Copy README if it exists
copy_docs_readme() {
   local source_readme="$SCRIPT_DIR/README_DOCS.md"
   local target_readme="$DOCS_DIR/README.md"
   
   if [[ -f "$source_readme" ]]; then
       cp "$source_readme" "$target_readme"
       echo "" >> "$target_readme"
       echo "---" >> "$target_readme"
       echo "Generated: $(date)" >> "$target_readme"
       echo -e "${GREEN}✓${NC} Copied README_DOCS.md to docs/"
   else
       echo -e "${YELLOW}No README_DOCS.md found, skipping...${NC}"
   fi
}

# Main documentation generation
generate_docs() {
   local phpdoc_cmd
   phpdoc_cmd=$(check_phpdoc)
   
   if [[ $? -ne 0 ]]; then
       install_phpdoc
       phpdoc_cmd=$(check_phpdoc)
       
       if [[ $? -ne 0 ]]; then
           echo -e "${RED}Failed to install phpDocumentor${NC}"
           exit 1
       fi
   fi
   
   echo -e "${BLUE}Using phpDocumentor: $phpdoc_cmd${NC}"
   
   # Create docs directory
   mkdir -p "$DOCS_DIR"
   mkdir -p "$PLUGIN_DIR/build/cache/phpdoc"
   
   # Generate config if it doesn't exist
   if [[ ! -f "$PLUGIN_DIR/phpdoc.xml" ]]; then
       generate_config
   fi
   
   echo -e "${YELLOW}Generating documentation...${NC}"
   
   # Count PHP files
   local php_count=$(find "$SRC_DIR" -name "*.php" -type f | wc -l)
   echo -e "${BLUE}Found $php_count PHP files in src/${NC}"
   
   # Run phpDocumentor - use absolute paths since we might be in build/ dir
   echo -e "${BLUE}Running phpDocumentor...${NC}"
   
   # Change to plugin directory for execution
   cd "$PLUGIN_DIR"
   
   # Try direct command with absolute paths
   $phpdoc_cmd run \
       --directory="$SRC_DIR" \
       --target="$DOCS_DIR" \
       --title="Bandfront Player Documentation"
   
   if [[ $? -eq 0 ]] && [[ -f "$DOCS_DIR/index.html" ]]; then
       copy_docs_readme
       echo -e "${GREEN}✓ Documentation generated successfully!${NC}"
       echo -e "Open: ${BLUE}file://$DOCS_DIR/index.html${NC}"
   else
       echo -e "${YELLOW}Direct method failed, trying with config file...${NC}"
       
       # Try with config file
       $phpdoc_cmd run --config="$PLUGIN_DIR/phpdoc.xml"
       
       if [[ -f "$DOCS_DIR/index.html" ]]; then
           copy_docs_readme
           echo -e "${GREEN}✓ Documentation generated successfully!${NC}"
           echo -e "Open: ${BLUE}file://$DOCS_DIR/index.html${NC}"
       else
           echo -e "${RED}✗ Documentation generation failed${NC}"
           echo -e "${YELLOW}Check if phpDocumentor is working correctly${NC}"
           exit 1
       fi
   fi
}

# Parse command line options
case "${1:-}" in
   --clean)
       echo -e "${YELLOW}Cleaning docs directory...${NC}"
       rm -rf "$DOCS_DIR"
       rm -rf "$PLUGIN_DIR/build/cache/phpdoc"
       rm -f "$PLUGIN_DIR/phpdoc.xml"
       echo -e "${GREEN}✓ Cleaned${NC}"
       ;;
   --config-only)
       generate_config
       echo -e "${GREEN}✓ Config generated at phpdoc.xml${NC}"
       ;;
   --help|-h)
       echo "Usage: $0 [option]"
       echo ""
       echo "Options:"
       echo "  (none)        Generate documentation"
       echo "  --clean       Clean docs directory"
       echo "  --config-only Generate phpdoc.xml config only"
       echo "  --help        Show this help"
       ;;
   "")
       generate_docs
       ;;
   *)
       echo -e "${RED}Unknown option: $1${NC}"
       echo "Use --help for usage information"
       exit 1
       ;;
esac