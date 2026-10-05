#!/bin/bash

set -euo pipefail

usage() {
    cat <<'EOF'
Updates the Toolkit rendering snapshots and screenshots of every kit, of one kit or of one recipe.

Usage:
  bin/update_toolkit_tests.sh                   Every kit
  bin/update_toolkit_tests.sh <kit>             One kit, e.g. shadcn
  bin/update_toolkit_tests.sh <kit>/<recipe>    One recipe, e.g. shadcn/popover
  bin/update_toolkit_tests.sh -h|--help         Shows this help

A screenshot is only rewritten when it no longer matches, with the tolerance of Playwright's
comparison. Snapshots and screenshots that no test uses anymore, like the ones of a removed or
renamed example, are deleted.

It runs from anywhere in the repository. Docker must be running: the screenshots are taken
by a browser running in Docker.
EOF
}

scope="${1:-}"
if [[ "$scope" == "-h" || "$scope" == "--help" ]]; then
    usage
    exit 0
fi
if [[ $# -gt 1 || ( -n "$scope" && ! "$scope" =~ ^[a-z0-9-]+(/[a-z0-9-]+)?$ ) ]]; then
    usage >&2
    exit 1
fi

kit="${scope%%/*}"
recipe=""
if [[ "$scope" == */* ]]; then
    recipe="${scope#*/}"
fi

cd "$(dirname "$0")/.."

if [[ -n "$scope" && ! -d "src/Toolkit/kits/$scope" ]]; then
    echo "Unknown kit or recipe \"$scope\"." >&2
    exit 1
fi

if ! docker info > /dev/null 2>&1; then
    echo "Docker is not running: the screenshots are taken by a browser running in Docker." >&2
    exit 1
fi

echo "Updating the rendering snapshots of ${scope:-every kit}..."
# PHPUnit data sets are named "Kit <kit>, component <recipe>, example #<index>".
find src/Toolkit/tests/Functional/__snapshots__ -name "ComponentsRenderingTest__* Kit ${kit:-*}, component ${recipe:-*}, example *" -delete
phpunit_args=(tests/Functional/ComponentsRenderingTest.php)
if [[ -n "$kit" ]]; then
    phpunit_args+=(--filter "Kit $kit, component ${recipe:-[^,]+}, example")
fi
(cd src/Toolkit && UPDATE_SNAPSHOTS=true php vendor/bin/phpunit "${phpunit_args[@]}")

echo "Updating the screenshots of ${scope:-every kit}..."
playwright_args=(-c src/Toolkit/assets)
if [[ -n "$kit" ]]; then
    # Test titles start with "<kit>/<recipe>/" in the generic spec and "<kit>/<recipe> " in a recipe spec.
    playwright_args+=(--grep "$kit/${recipe:-[a-z0-9-]+}[/ ]")
fi

# Each screenshot test declares the file it compares in a "screenshot" annotation.
# Read from a file, not stdout: pnpm prints its install report there when the lockfile changed.
test_list=$(mktemp)
trap 'rm -f "$test_list"' EXIT
PLAYWRIGHT_JSON_OUTPUT_FILE="$test_list" pnpm exec playwright test "${playwright_args[@]}" --list --reporter=json
expected=$(jq -r '.. | objects | select(has("annotations")) | .annotations[] | select(.type == "screenshot") | "src/Toolkit/kits/" + .description' "$test_list" \
    | sort -u)
if [[ -z "$expected" ]]; then
    echo "No screenshot test found for ${scope:-every kit}." >&2
    exit 1
fi

pnpm exec playwright test "${playwright_args[@]}" --update-snapshots changed

find src/Toolkit/kits -path "src/Toolkit/kits/${kit:-*}/${recipe:-*}/tests/screenshots/*.png" | sort \
    | comm -23 - <(echo "$expected") \
    | while read -r orphan; do
        echo "Deleting $orphan, no test uses it anymore."
        rm "$orphan"
    done

git status --short src/Toolkit/tests/Functional/__snapshots__ src/Toolkit/kits
