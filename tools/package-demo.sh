#!/usr/bin/env bash
set -Eeuo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
ARCHIVE="$ROOT_DIR/../reward-gate-demo.zip"
BUILD_DIR="$(mktemp -d)"

cleanup() {
    rm -rf "$BUILD_DIR"
}
trap cleanup EXIT

for command in composer zip unzip; do
    if ! command -v "$command" >/dev/null 2>&1; then
        printf 'Error: required command not found: %s\n' "$command" >&2
        exit 1
    fi
done

if [[ ! -f "$ROOT_DIR/config/database.test.php" ]]; then
    printf 'Error: demo database configuration not found.\n' >&2
    exit 1
fi

printf '%s\n' 'Preparing deployment files...'

mkdir -p "$BUILD_DIR/config"

# Application entry point and bootstrap.
cp "$ROOT_DIR/public/index.php" "$BUILD_DIR/index.php"
cp "$ROOT_DIR/public/.htaccess" "$BUILD_DIR/.htaccess"
cp "$ROOT_DIR/bootstrap.php" "$BUILD_DIR/bootstrap.php"

# Public assets.
cp -a "$ROOT_DIR/public/assets" "$BUILD_DIR/assets"

# Application code, including directory protection files.
cp -a "$ROOT_DIR/src" "$BUILD_DIR/src"
cp -a "$ROOT_DIR/views" "$BUILD_DIR/views"

# Runtime configuration only.
cp "$ROOT_DIR/config/app.php" "$BUILD_DIR/config/app.php"
cp "$ROOT_DIR/config/routes.php" "$BUILD_DIR/config/routes.php"
cp "$ROOT_DIR/config/.htaccess" "$BUILD_DIR/config/.htaccess"

# Use demo server credentials, never the local database configuration.
cp "$ROOT_DIR/config/database.test.php" \
    "$BUILD_DIR/config/database.php"

# Install production dependencies in the temporary build directory.
cp "$ROOT_DIR/composer.json" "$BUILD_DIR/composer.json"
cp "$ROOT_DIR/composer.lock" "$BUILD_DIR/composer.lock"

printf '%s\n' 'Installing production dependencies...'

(
    cd "$BUILD_DIR"
    composer install \
        --no-dev \
        --optimize-autoloader \
        --no-interaction \
        --prefer-dist
)

# Composer manifests are not required at application runtime.
rm "$BUILD_DIR/composer.json" "$BUILD_DIR/composer.lock"

# Verify excluded files and directories are absent.
for path in \
    private \
    .git \
    tests \
    docs \
    tools \
    config/database.test.php
do
    if [[ -e "$BUILD_DIR/$path" ]]; then
        printf 'Error: excluded path found in build: %s\n' "$path" >&2
        exit 1
    fi
done

# Verify the deployed database configuration came from the intended source.
if ! cmp -s \
    "$ROOT_DIR/config/database.test.php" \
    "$BUILD_DIR/config/database.php"
then
    printf '%s\n' 'Error: database configuration mismatch.' >&2
    exit 1
fi

# Build the archive with files at its root, ready for /public_html/demo/.
rm -f "$ARCHIVE"

(
    cd "$BUILD_DIR"
    zip -qr "$ARCHIVE" .
)

# Inspect archive contents for forbidden top-level paths and configuration files.
if unzip -Z1 "$ARCHIVE" | grep -Eq \
    '^(private|\.git|tests|docs|tools)/|^database\.test\.php$|^composer\.(json|lock)$'
then
    printf '%s\n' 'Error: forbidden top-level files found in archive.' >&2
    rm -f "$ARCHIVE"
    exit 1
fi

printf '\n%s\n' 'Deployment archive created successfully.'
printf 'Archive: %s\n' "$ARCHIVE"
printf 'Size: '
du -h "$ARCHIVE" | cut -f1

printf '\n%s\n' 'Archive contents:'
unzip -Z1 "$ARCHIVE" | sort
