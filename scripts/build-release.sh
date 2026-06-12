#!/usr/bin/env bash
#
# Build a release zip from .distignore rules (no WP-CLI required).
#
# Usage (from repo root):
#   ./scripts/build-release.sh
#   ./scripts/build-release.sh ../birdsend-email-marketing.zip
# The script prints the full absolute path of the created zip.
#
set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
PLUGIN_SLUG="birdsend-email-marketing"
DISTIGNORE="$ROOT/.distignore"
OUTPUT="${1:-$(dirname "$ROOT")/$PLUGIN_SLUG.zip}"

# Relative output paths must not be resolved from the temp build directory.
if [[ "$OUTPUT" != /* ]]; then
	OUTPUT="$(pwd)/$OUTPUT"
fi
mkdir -p "$(dirname "$OUTPUT")"
OUTPUT="$(cd "$(dirname "$OUTPUT")" && pwd)/$(basename "$OUTPUT")"

BUILD_DIR="$(mktemp -d)"

cleanup() {
	rm -rf "$BUILD_DIR"
}
trap cleanup EXIT

if [[ ! -f "$DISTIGNORE" ]]; then
	echo "Error: .distignore not found at $DISTIGNORE" >&2
	exit 1
fi

EXCLUDES=()
while IFS= read -r line || [[ -n "$line" ]]; do
	line="${line%%#*}"
	line="${line#"${line%%[![:space:]]*}"}"
	line="${line%"${line##*[![:space:]]}"}"
	[[ -z "$line" ]] && continue
	line="${line#/}"
	EXCLUDES+=(--exclude "$line")
done < "$DISTIGNORE"

EXCLUDES+=(--exclude "$PLUGIN_SLUG.zip")
EXCLUDES+=(--exclude "*.zip")

mkdir -p "$BUILD_DIR/$PLUGIN_SLUG"

rsync -a "${EXCLUDES[@]}" "$ROOT/" "$BUILD_DIR/$PLUGIN_SLUG/"

rm -f "$OUTPUT"
(
	cd "$BUILD_DIR"
	zip -rq "$OUTPUT" "$PLUGIN_SLUG"
)

echo "Created $OUTPUT"
