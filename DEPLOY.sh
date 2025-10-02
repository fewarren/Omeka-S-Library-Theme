#!/usr/bin/env bash
set -euo pipefail

# Safe deploy script for the LibraryTheme (Omeka S theme)
# - Deploys ONLY the theme, not modules
# - Uses rsync include rules to sync theme files from ./library-theme (if present) or current dir
# - Excludes dev-only content
# - Supports DRY_RUN=1 to preview actions
# - Optional BACKUP=1 creates a timestamped tar.gz of the target before syncing
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
OWNER=${OWNER:-www-data}
GROUP=${GROUP:-www-data}
DIR_MODE=${DIR_MODE:-755}
FILE_MODE=${FILE_MODE:-644}
REMOTE_SUDO=${REMOTE_SUDO:-sudo}

TARGET=${1:-/var/www/omeka-s/themes/LibraryTheme}

# Choose source: prefer ./library-theme subdirectory if present (theme sources), else current dir
if [[ -d ./library-theme/config && -d ./library-theme/view ]]; then
  SOURCE_DIR=./library-theme
else
  SOURCE_DIR=.
fi

# Assign production directory variable for reuse
PROD_DIR="${TARGET}"

RSYNC_FLAGS=("-av" "--no-perms" "--no-owner" "--no-group")

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

# Optional: create a timestamped archive of the destination
if [[ "${BACKUP:-0}" == "1" ]]; then
  ts=$(date +%Y%m%d-%H%M%S)
  if [[ ${IS_REMOTE} -eq 1 ]]; then
    REMOTE_DIR=$(dirname "${REMOTE_PATH}")
    REMOTE_BASE=$(basename "${REMOTE_PATH}")
    echo "Creating backup ${PROD_DIR}.${ts}.tar.gz on remote..."
    ssh "${REMOTE_HOST}" "${REMOTE_SUDO} tar -czf $(printf '%q' "${PROD_DIR}.${ts}.tar.gz") -C $(printf '%q' "${REMOTE_DIR}") $(printf '%q' "${REMOTE_BASE}")"
  else
    LOCAL_DIR=$(dirname "${REMOTE_PATH}")
    LOCAL_BASE=$(basename "${REMOTE_PATH}")
    echo "Creating backup ${PROD_DIR}.${ts}.tar.gz locally..."
    ${REMOTE_SUDO} tar -czf "$(printf '%q' "${PROD_DIR}.${ts}.tar.gz")" -C "$(printf '%q' "${LOCAL_DIR}")" "$(printf '%q' "${LOCAL_BASE}")"
  fi
fi

if [[ "${DRY_RUN:-0}" == "1" ]]; then
  RSYNC_FLAGS+=("--dry-run")
fi

# Build rsync rules: exclude dev dirs first, then include theme files only
RSYNC_RULES=(
  # Exclude dev-only directories first
  "--exclude=dev-tools/"
  "--exclude=dev-archive/"
  "--exclude=scripts/"
  "--exclude=backup-*/"
  "--exclude=multilevel-accordion-menu/"

  # Include essential theme files and directories
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

  # Exclude everything else (e.g., src/, modules, Module.php, etc.)
  "--exclude=*"
)

CMD=(rsync "${RSYNC_FLAGS[@]}" "${RSYNC_RULES[@]}" "${SOURCE_DIR}/" "${PROD_DIR}")
echo "Running: ${CMD[*]}"
"${CMD[@]}"

# Post-sync cleanup: remove accidental module files from the theme target
if [[ "${DRY_RUN:-0}" != "1" ]]; then
  echo "Cleaning up non-theme files from ${REMOTE_PATH}..."
  if [[ ${IS_REMOTE} -eq 1 ]]; then
    ssh "${REMOTE_HOST}" "${REMOTE_SUDO} rm -f '${REMOTE_PATH}/config/module.config.php' '${REMOTE_PATH}/Module.php' 2>/dev/null || true"
  else
    ${REMOTE_SUDO} rm -f "${REMOTE_PATH}/config/module.config.php" "${REMOTE_PATH}/Module.php" 2>/dev/null || true
  fi
fi

# Verify theme structure and set ownership/permissions; restart web server
if [[ "${DRY_RUN:-0}" != "1" ]]; then
  REMOTE_HOST="${PROD_DIR%%:*}"
  REMOTE_PATH="${PROD_DIR#*:}"

  if [[ ${IS_REMOTE} -eq 1 ]]; then
    echo "Verifying theme files on remote..."
    ssh "${REMOTE_HOST}" "test -f '${REMOTE_PATH}/config/theme.ini' && test -d '${REMOTE_PATH}/view' && test -d '${REMOTE_PATH}/asset'" || { echo "ERROR: Theme files not found on remote"; exit 2; }

    echo "Setting ownership (${OWNER}:${GROUP}) on ${REMOTE_PATH}..."
    ssh "${REMOTE_HOST}" "${REMOTE_SUDO} chown -R '${OWNER}:${GROUP}' '${REMOTE_PATH}'"

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

    echo "Setting ownership (${OWNER}:${GROUP}) on ${REMOTE_PATH}..."
    ${REMOTE_SUDO} chown -R "${OWNER}:${GROUP}" "${REMOTE_PATH}"

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
