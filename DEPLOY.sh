#!/usr/bin/env bash
set -euo pipefail

# Minimal, safe deploy script for LibraryTheme
# - Uses rsync include-only rules to deploy just the files needed in production
# - Excludes dev-only tools and directories
# - Supports DRY_RUN=1 to preview actions
# - Optional BACKUP=1 creates a timestamped tar.gz of the remote/local target before syncing
# - Ensures ownership/permissions and restarts Apache unless SKIP_RESTART=1
#
# Usage:
#   # Dry-run preview
#   #   DRY_RUN=1 ./DEPLOY.sh [/var/www/omeka-s/themes/LibraryTheme]
#   # Actual deploy
#   #   ./DEPLOY.sh [/var/www/omeka-s/themes/LibraryTheme]
#   # Example (remote):
#   #   ./DEPLOY.sh deploy@prod.example.org:/var/www/omeka-s/themes/LibraryTheme
#   # If no argument is provided, defaults to /var/www/omeka-s/themes/LibraryTheme (local)
#
# Env options:
#   DIR_MODE=755 FILE_MODE=644      # permissions applied after deploy (default)
#   BACKUP=1                        # create backup tar.gz before syncing
#   SKIP_RESTART=1                  # do not restart apache2
#   REMOTE_SUDO=sudo                # command used for privileged remote operations
#
# Note: Ownership is set with a fixed path using sudo chown -R www-data:www-data /var/www/omeka-s/themes/LibraryTheme

OWNER=${OWNER:-www-data}
GROUP=${GROUP:-www-data}
DIR_MODE=${DIR_MODE:-755}
FILE_MODE=${FILE_MODE:-644}
REMOTE_SUDO=${REMOTE_SUDO:-sudo}

TARGET=${1:-/var/www/omeka-s/themes/LibraryTheme}

# Assign production directory variable for reuse
PROD_DIR="${TARGET}"

RSYNC_FLAGS=("-av" "--delete" "--no-perms" "--no-owner" "--no-group")
# If BACKUP=1, create a timestamped archive of the destination
# Determine if target is remote (contains ':'); derive host and path
if [[ "${PROD_DIR}" == *:* ]]; then
  IS_REMOTE=1
  REMOTE_HOST="${PROD_DIR%%:*}"
  REMOTE_PATH="${PROD_DIR#*:}"
else
  IS_REMOTE=0
  REMOTE_HOST=""
  REMOTE_PATH="${PROD_DIR}"
fi

if [[ "${BACKUP:-0}" == "1" ]]; then
  ts=$(date +%Y%m%d-%H%M%S)
  if [[ ${IS_REMOTE} -eq 1 ]]; then
    # Pre-calculate dirname/basename safely to prevent command injection
    REMOTE_DIR=$(dirname "${REMOTE_PATH}")
    REMOTE_BASE=$(basename "${REMOTE_PATH}")
    echo "Creating backup ${PROD_DIR}.${ts}.tar.gz on remote..."
    ssh "${REMOTE_HOST}" "${REMOTE_SUDO} tar -czf $(printf '%q' "${PROD_DIR}.${ts}.tar.gz") -C $(printf '%q' "${REMOTE_DIR}") $(printf '%q' "${REMOTE_BASE}")"
  else
    # Pre-calculate dirname/basename safely for local backup
    LOCAL_DIR=$(dirname "${REMOTE_PATH}")
    LOCAL_BASE=$(basename "${REMOTE_PATH}")
    echo "Creating backup ${PROD_DIR}.${ts}.tar.gz locally..."
    ${REMOTE_SUDO} tar -czf "$(printf '%q' "${PROD_DIR}.${ts}.tar.gz")" -C "$(printf '%q' "${LOCAL_DIR}")" "$(printf '%q' "${LOCAL_BASE}")"
  fi
fi

if [[ "${DRY_RUN:-0}" == "1" ]]; then
  RSYNC_FLAGS+=("--dry-run")
fi

# Build rsync rules: exclude dev dirs first, then include production files
RSYNC_RULES=(
  # Exclude dev-only directories first
  "--exclude=dev-tools/"
  "--exclude=dev-archive/"
  "--exclude=scripts/"
  "--exclude=backup-*/"
  "--exclude=multilevel-accordion-menu/"
  "--exclude=library-theme/"

  # Include essential files and directories
  "--include=LICENSE"
  "--include=README.md"
  "--include=MAINTENANCE.md"
  "--include=theme.jpg"
  "--include=config/"
  "--include=config/***"
  "--include=view/"
  "--include=view/***"
  "--include=asset/"
  "--include=asset/***"
  "--include=helper/"
  "--include=helper/***"
  "--include=src/"
  "--include=src/***"

  # Exclude everything else
  "--exclude=*"
)

CMD=(rsync "${RSYNC_FLAGS[@]}" "${RSYNC_RULES[@]}" ./ "${PROD_DIR}")
echo "Running: ${CMD[*]}"
"${CMD[@]}"

# Check if critical files were actually updated
if [[ "${DRY_RUN:-0}" != "1" ]]; then
  echo "Verifying critical file updates..."
  LOCAL_CSS_SIZE=$(wc -c < view/common/theme-setting-css.phtml)
  PROD_CSS_SIZE=$(wc -c < "${REMOTE_PATH}/view/common/theme-setting-css.phtml" 2>/dev/null || echo "0")
  LOCAL_CONFIG_SIZE=$(wc -c < config/module.config.php)
  PROD_CONFIG_SIZE=$(wc -c < "${REMOTE_PATH}/config/module.config.php" 2>/dev/null || echo "0")

  echo "Local CSS file size: ${LOCAL_CSS_SIZE} bytes"
  echo "Production CSS file size: ${PROD_CSS_SIZE} bytes"
  echo "Local config file size: ${LOCAL_CONFIG_SIZE} bytes"
  echo "Production config file size: ${PROD_CONFIG_SIZE} bytes"

  if [[ "${LOCAL_CSS_SIZE}" != "${PROD_CSS_SIZE}" ]]; then
    echo "WARNING: CSS file sizes don't match - attempting multiple copy methods..."

    # Try multiple approaches to copy the file
    echo "Attempting direct copy..."
    cp view/common/theme-setting-css.phtml "${REMOTE_PATH}/view/common/theme-setting-css.phtml" 2>/dev/null && echo "Direct copy successful" || {
      echo "Direct copy failed, trying with cat..."
      cat view/common/theme-setting-css.phtml > "${REMOTE_PATH}/view/common/theme-setting-css.phtml" 2>/dev/null && echo "Cat copy successful" || {
        echo "Cat copy failed, trying temp file approach..."
        TEMP_FILE="/tmp/theme-css-$(date +%s).phtml"
        cp view/common/theme-setting-css.phtml "${TEMP_FILE}" &&
        mv "${TEMP_FILE}" "${REMOTE_PATH}/view/common/theme-setting-css.phtml" && echo "Temp file copy successful" || {
          echo "ERROR: All copy methods failed. Manual intervention required."
          echo "Please run: cp $(pwd)/view/common/theme-setting-css.phtml ${REMOTE_PATH}/view/common/theme-setting-css.phtml"
          exit 3
        }
      }
    }

    # Verify the copy worked
    NEW_PROD_SIZE=$(wc -c < "${REMOTE_PATH}/view/common/theme-setting-css.phtml" 2>/dev/null || echo "0")
    if [[ "${LOCAL_CSS_SIZE}" == "${NEW_PROD_SIZE}" ]]; then
      echo "File copy verified successful"
    else
      echo "WARNING: File copy verification failed (${LOCAL_CSS_SIZE} != ${NEW_PROD_SIZE})"
    fi
  else
    echo "CSS file sizes match - update successful"
  fi

  # Check config file
  if [[ "${LOCAL_CONFIG_SIZE}" != "${PROD_CONFIG_SIZE}" ]]; then
    echo "WARNING: Config file sizes don't match - attempting copy..."
    cp config/module.config.php "${REMOTE_PATH}/config/module.config.php" 2>/dev/null && echo "Config copy successful" || {
      echo "Config copy failed - manual intervention may be required"
    }
  else
    echo "Config file sizes match - update successful"
  fi
fi
# Remote verification, ownership, permissions, and restart
if [[ "${DRY_RUN:-0}" != "1" ]]; then
  REMOTE_HOST="${PROD_DIR%%:*}"
  REMOTE_PATH="${PROD_DIR#*:}"

  if [[ ${IS_REMOTE} -eq 1 ]]; then
    echo "Verifying theme files on remote..."
    ssh "${REMOTE_HOST}" "test -f '${REMOTE_PATH}/config/theme.ini' && test -d '${REMOTE_PATH}/view' && test -d '${REMOTE_PATH}/asset'" || { echo "ERROR: Theme files not found on remote"; exit 2; }

    echo "Setting ownership (www-data:www-data) on ${REMOTE_PATH}..."
    ssh "${REMOTE_HOST}" "${REMOTE_SUDO} chown -R 'www-data:www-data' '${REMOTE_PATH}'"

    echo "Setting permissions (dirs ${DIR_MODE}, files ${FILE_MODE})..."
    ssh "${REMOTE_HOST}" "${REMOTE_SUDO} find '${REMOTE_PATH}' -type d -exec chmod ${DIR_MODE} {} +"
    ssh "${REMOTE_HOST}" "${REMOTE_SUDO} find '${REMOTE_PATH}' -type f -exec chmod ${FILE_MODE} {} +"

    if [[ "${SKIP_RESTART:-0}" != "1" ]]; then
      echo "Restarting apache2 on remote..."
      ssh "${REMOTE_HOST}" "${REMOTE_SUDO} systemctl restart apache2"
    else
      echo "Skipping apache2 restart (SKIP_RESTART=1)"
    fi
  else
    echo "Verifying theme files locally..."
    test -f "${REMOTE_PATH}/config/theme.ini" && test -d "${REMOTE_PATH}/view" && test -d "${REMOTE_PATH}/asset" || { echo "ERROR: Theme files not found locally"; exit 2; }

    echo "Setting ownership (www-data:www-data) on ${REMOTE_PATH}..."
    ${REMOTE_SUDO} chown -R 'www-data:www-data' "${REMOTE_PATH}"

    echo "Setting permissions (dirs ${DIR_MODE}, files ${FILE_MODE})..."
    find "${REMOTE_PATH}" -type d -exec ${REMOTE_SUDO} chmod ${DIR_MODE} {} +
    find "${REMOTE_PATH}" -type f -exec ${REMOTE_SUDO} chmod ${FILE_MODE} {} +

    if [[ "${SKIP_RESTART:-0}" != "1" ]]; then
      echo "Restarting apache2 locally..."
      ${REMOTE_SUDO} systemctl restart apache2
    else
      echo "Skipping apache2 restart (SKIP_RESTART=1)"
    fi
  fi
fi


echo "Deploy complete."

