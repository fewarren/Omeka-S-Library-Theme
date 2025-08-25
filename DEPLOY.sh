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

RSYNC_FLAGS=("-avz" "--delete" )
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
    echo "Creating backup ${PROD_DIR}.${ts}.tar.gz on remote..."
    ssh "${REMOTE_HOST}" "${REMOTE_SUDO} tar -czf '${PROD_DIR}.${ts}.tar.gz' -C '$(dirname "${REMOTE_PATH}")' '$(basename "${REMOTE_PATH}")'"
  else
    echo "Creating backup ${PROD_DIR}.${ts}.tar.gz locally..."
    ${REMOTE_SUDO} tar -czf "${PROD_DIR}.${ts}.tar.gz" -C "$(dirname "${REMOTE_PATH}")" "$(basename "${REMOTE_PATH}")"
  fi
fi

if [[ "${DRY_RUN:-0}" == "1" ]]; then
  RSYNC_FLAGS+=("--dry-run")
fi

# Build include/exclude rules: include essential files and dirs, exclude everything else
RSYNC_INCLUDE=(
  "--include=Module.php"
  "--include=LICENSE"
  "--include=README.md"
  "--include=MAINTENANCE.md"
  "--include=theme.jpg"
  "--include=config/***"
  "--include=view/***"
  "--include=asset/***"
  "--include=helper/***"
  "--include=src/***"
  # Exclude everything else by default
  "--exclude=*"
)

# Exclude dev-only directories explicitly (defense in depth)
RSYNC_EXCLUDE=(
  "--exclude=dev-tools/"
  "--exclude=dev-archive/"
  "--exclude=scripts/"
  "--exclude=backup-*/"

  "--exclude=multilevel-accordion-menu/"
  "--exclude=library-theme/"
)

CMD=(rsync "${RSYNC_FLAGS[@]}" "${RSYNC_INCLUDE[@]}" "${RSYNC_EXCLUDE[@]}" ./ "${PROD_DIR}")
echo "Running: ${CMD[*]}"
"${CMD[@]}"
# Remote verification, ownership, permissions, and restart
if [[ "${DRY_RUN:-0}" != "1" ]]; then
  REMOTE_HOST="${PROD_DIR%%:*}"
  REMOTE_PATH="${PROD_DIR#*:}"

  if [[ ${IS_REMOTE} -eq 1 ]]; then
    echo "Verifying Module.php on remote..."
    ssh "${REMOTE_HOST}" "test -f '${REMOTE_PATH}/Module.php'" || { echo "ERROR: Module.php not found on remote"; exit 2; }

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
    echo "Verifying Module.php locally..."
    test -f "${REMOTE_PATH}/Module.php" || { echo "ERROR: Module.php not found locally"; exit 2; }

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

