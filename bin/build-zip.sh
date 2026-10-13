#!/usr/bin/env bash
#
# Builds build/villocity-suite.zip: the exact file WordPress sites install.
# Used by the GitHub release workflow; you can also run it locally (`composer build`)
# to get a zip for a manual install.
#
# The zip is made from the last COMMIT (git archive), not from uncommitted edits, and
# leaves out everything marked export-ignore in .gitattributes. Production Composer
# dependencies (the update checker) are then installed into it.
#
# Usage: bin/build-zip.sh [expected-version]
#   If expected-version is given (e.g. 3.1.0 or v3.1.0), the build fails unless the
#   plugin header and VILLOCITY_VERSION both match it.

set -euo pipefail

SLUG="villocity-suite"
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
BUILD="$ROOT/build"
STAGE="$BUILD/$SLUG"
MAIN="$ROOT/$SLUG.php"

header_version="$(grep -m1 -E '^[[:space:]]*\*[[:space:]]*Version:' "$MAIN" | sed -E 's/.*Version:[[:space:]]*//' | tr -d '[:space:]')"
const_version="$(grep -m1 "define( 'VILLOCITY_VERSION'" "$MAIN" | sed -E "s/.*'VILLOCITY_VERSION', '([^']+)'.*/\1/")"

if [[ "$header_version" != "$const_version" ]]; then
	echo "Version mismatch: header says $header_version, VILLOCITY_VERSION says $const_version" >&2
	exit 1
fi

if [[ $# -ge 1 ]]; then
	expected="${1#v}"
	if [[ "$header_version" != "$expected" ]]; then
		echo "Version mismatch: tag is $expected but plugin says $header_version. Bump the version before tagging." >&2
		exit 1
	fi
fi

if [[ -n "$(git -C "$ROOT" status --porcelain)" ]]; then
	echo "Note: you have uncommitted changes. They are NOT included in the zip." >&2
fi

echo "Building $SLUG $header_version"

rm -rf "$BUILD"
mkdir -p "$STAGE"

git -C "$ROOT" archive --format=tar HEAD | tar -x -C "$STAGE"

# Install production dependencies only into the staged copy.
cp "$ROOT/composer.json" "$STAGE/"
if [[ -f "$ROOT/composer.lock" ]]; then cp "$ROOT/composer.lock" "$STAGE/"; fi
composer install --no-dev --no-interaction --no-progress --optimize-autoloader --working-dir="$STAGE"
rm -f "$STAGE/composer.json" "$STAGE/composer.lock"

# Syntax-check what we are about to ship.
find "$STAGE" -name '*.php' -not -path '*/vendor/*' -print0 | xargs -0 -n1 php -l > /dev/null

(cd "$BUILD" && zip -rq "$SLUG.zip" "$SLUG")

echo "Done: $BUILD/$SLUG.zip"
