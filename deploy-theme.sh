#!/bin/bash

echo "🚀 SUFISM LIBRARY THEME DEPLOYMENT"
echo "=================================="
echo "Standard deployment script for production environment"
echo "Timestamp: $(date)"
echo ""

# Configuration
DEVELOPMENT_DIR="/home/fwarren/Sufism-Library"
PRODUCTION_OMEKA_PATH="/var/www/omeka-s"
THEME_NAME="sufism-library"
PRODUCTION_THEME_PATH="$PRODUCTION_OMEKA_PATH/themes/$THEME_NAME"
BACKUP_DIR="/tmp/theme-backups"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Logging function
log() {
    echo -e "${BLUE}[$(date '+%H:%M:%S')]${NC} $1"
}

success() {
    echo -e "${GREEN}✅ $1${NC}"
}

warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

error() {
    echo -e "${RED}❌ $1${NC}"
}

# Check if running as root or with sudo
if [ "$EUID" -ne 0 ]; then
    error "This script must be run with sudo privileges"
    echo "Usage: sudo ./deploy-theme.sh"
    exit 1
fi

echo "📋 DEPLOYMENT CONFIGURATION"
echo "---------------------------"
echo "Development directory: $DEVELOPMENT_DIR"
echo "Production Omeka path: $PRODUCTION_OMEKA_PATH"
echo "Theme name: $THEME_NAME"
echo "Production theme path: $PRODUCTION_THEME_PATH"
echo ""

# Step 1: Validate development directory
log "Step 1: Validating development directory"

if [ ! -d "$DEVELOPMENT_DIR" ]; then
    error "Development directory not found: $DEVELOPMENT_DIR"
    exit 1
fi

# Check for required development files
required_files=(
    "config/theme.ini"
    "view/layout/layout.phtml"
    "view/common/header-vertical.phtml"
    "asset/css/sufism-reoriented-design.css"
    "asset/js/sufism-menu.js"
)

missing_files=()
for file in "${required_files[@]}"; do
    if [ ! -f "$DEVELOPMENT_DIR/$file" ]; then
        missing_files+=("$file")
    fi
done

if [ ${#missing_files[@]} -gt 0 ]; then
    error "Missing required files in development directory:"
    for file in "${missing_files[@]}"; do
        echo "  - $file"
    done
    exit 1
fi

success "Development directory validated"

# Step 2: Validate production environment
log "Step 2: Validating production environment"

if [ ! -d "$PRODUCTION_OMEKA_PATH" ]; then
    error "Production Omeka directory not found: $PRODUCTION_OMEKA_PATH"
    exit 1
fi

if [ ! -d "$PRODUCTION_OMEKA_PATH/themes" ]; then
    error "Themes directory not found: $PRODUCTION_OMEKA_PATH/themes"
    exit 1
fi

success "Production environment validated"

# Step 3: Create backup if theme exists
log "Step 3: Handling existing theme"

if [ -d "$PRODUCTION_THEME_PATH" ]; then
    warning "Existing theme found at: $PRODUCTION_THEME_PATH"
    
    # Create backup directory
    mkdir -p "$BACKUP_DIR"
    
    # Create timestamped backup
    backup_name="$THEME_NAME-backup-$(date +%Y%m%d-%H%M%S)"
    backup_path="$BACKUP_DIR/$backup_name"
    
    log "Creating backup: $backup_path"
    cp -r "$PRODUCTION_THEME_PATH" "$backup_path"
    
    if [ $? -eq 0 ]; then
        success "Backup created: $backup_path"
    else
        error "Failed to create backup"
        exit 1
    fi
    
    # Remove existing theme
    log "Removing existing theme directory"
    rm -rf "$PRODUCTION_THEME_PATH"
    
    if [ $? -eq 0 ]; then
        success "Existing theme removed"
    else
        error "Failed to remove existing theme"
        exit 1
    fi
else
    log "No existing theme found - fresh installation"
fi

# Step 4: Deploy new theme
log "Step 4: Deploying new theme"

# Create theme directory
mkdir -p "$PRODUCTION_THEME_PATH"

if [ $? -eq 0 ]; then
    success "Theme directory created: $PRODUCTION_THEME_PATH"
else
    error "Failed to create theme directory"
    exit 1
fi

# Copy all files from development to production
log "Copying theme files from development to production"

# Copy directory structure
cp -r "$DEVELOPMENT_DIR"/* "$PRODUCTION_THEME_PATH/"

if [ $? -eq 0 ]; then
    success "Theme files copied successfully"
else
    error "Failed to copy theme files"
    exit 1
fi

# Step 5: Set proper permissions
log "Step 5: Setting proper permissions"

# Set ownership to web server user
chown -R www-data:www-data "$PRODUCTION_THEME_PATH"

if [ $? -eq 0 ]; then
    success "Ownership set to www-data:www-data"
else
    error "Failed to set ownership"
    exit 1
fi

# Set proper file permissions
find "$PRODUCTION_THEME_PATH" -type d -exec chmod 755 {} \;
find "$PRODUCTION_THEME_PATH" -type f -exec chmod 644 {} \;

if [ $? -eq 0 ]; then
    success "File permissions set (directories: 755, files: 644)"
else
    error "Failed to set file permissions"
    exit 1
fi

# Step 6: Verify deployment
log "Step 6: Verifying deployment"

deployment_errors=()

# Check theme directory exists
if [ ! -d "$PRODUCTION_THEME_PATH" ]; then
    deployment_errors+=("Theme directory not found")
fi

# Check required files exist
for file in "${required_files[@]}"; do
    if [ ! -f "$PRODUCTION_THEME_PATH/$file" ]; then
        deployment_errors+=("Missing file: $file")
    fi
done

# Check permissions
theme_owner=$(stat -c%U "$PRODUCTION_THEME_PATH" 2>/dev/null)
if [ "$theme_owner" != "www-data" ]; then
    deployment_errors+=("Incorrect ownership: $theme_owner (should be www-data)")
fi

# Report verification results
if [ ${#deployment_errors[@]} -gt 0 ]; then
    error "Deployment verification failed:"
    for err in "${deployment_errors[@]}"; do
        echo "  - $err"
    done
    exit 1
else
    success "Deployment verification passed"
fi

# Step 7: Clean up old backups (keep last 5)
log "Step 7: Cleaning up old backups"

if [ -d "$BACKUP_DIR" ]; then
    # Use find to safely locate backup files and count them
    # This avoids parsing ls output and handles unusual filenames safely
    backup_files=()
    while IFS= read -r -d '' backup_file; do
        backup_files+=("$backup_file")
    done < <(find "$BACKUP_DIR" -maxdepth 1 -name "${THEME_NAME}-backup-*" -type d -print0)

    backup_count=${#backup_files[@]}

    if [ "$backup_count" -gt 5 ]; then
        log "Found $backup_count backups, removing old ones (keeping 5 most recent)"

        # Sort backup files by modification time (newest first) and get files to remove
        files_to_remove=()
        while IFS= read -r -d '' old_backup; do
            files_to_remove+=("$old_backup")
        done < <(find "$BACKUP_DIR" -maxdepth 1 -name "${THEME_NAME}-backup-*" -type d -printf '%T@ %p\0' | \
                 sort -rz -n | \
                 tail -z -n +"$((5 + 1))" | \
                 cut -z -d' ' -f2-)

        # Safely remove old backup files
        for old_backup in "${files_to_remove[@]}"; do
            if [ -d "$old_backup" ]; then
                rm -rf "$old_backup"
                log "Removed old backup: $(basename "$old_backup")"
            fi
        done

        log "Removed $((backup_count - 5)) old backup(s)"
    else
        log "Found $backup_count backup(s), no cleanup needed"
    fi

    success "Backup cleanup completed"
fi

# Step 8: Display deployment summary
echo ""
echo "🎉 DEPLOYMENT COMPLETED SUCCESSFULLY"
echo "===================================="
echo ""
echo "📊 DEPLOYMENT SUMMARY:"
echo "• Theme: $THEME_NAME"
echo "• Location: $PRODUCTION_THEME_PATH"
echo "• Files deployed: $(find "$PRODUCTION_THEME_PATH" -type f | wc -l)"
echo "• Directories created: $(find "$PRODUCTION_THEME_PATH" -type d | wc -l)"
echo "• Ownership: www-data:www-data"
echo "• Permissions: 755 (dirs), 644 (files)"

if [ -n "$backup_path" ]; then
    echo "• Backup created: $backup_path"
fi

echo ""
echo "🎯 NEXT STEPS:"
echo "1. Activate theme in Omeka S admin:"
echo "   → http://localhost/omeka-s/admin"
echo "   → Sites → [Your Site] → Theme"
echo "   → Select: 'Sufism Library'"
echo "   → Save"
echo ""
echo "2. Test the website:"
echo "   → http://localhost/omeka-s/"
echo ""
echo "3. Monitor for issues:"
echo "   → tail -f /tmp/sufism-debug.log"
echo "   → Check browser console (F12)"
echo ""

echo "📋 THEME FEATURES:"
echo "• Professional design matching sufismreoriented.org"
echo "• Responsive sidebar navigation"
echo "• Working hamburger menu"
echo "• Search functionality"
echo "• Accessible markup"
echo "• Error handling and fallbacks"
echo ""

echo "🔧 TROUBLESHOOTING:"
echo "If issues occur:"
echo "• Check debug log: tail -f /tmp/sufism-debug.log"
echo "• Check web server logs: sudo tail -f /var/log/apache2/error.log"
echo "• Verify Omeka S is running: systemctl status apache2"
echo "• Clear browser cache and hard refresh (Ctrl+F5)"
echo ""

echo "✅ Deployment completed at: $(date)"
echo "Theme is ready for use!"
