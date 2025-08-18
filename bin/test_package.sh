#!/bin/bash

# This script is used to test an UX package.
# It also handle the case where a package has multiple versions of a peerDependency defined.

SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )"
PROJECT_DIR=$(dirname "$SCRIPT_DIR")

# Flag to track if any test fails
all_tests_passed=true

# Check if we have at least two arguments
if [ $# -ne 2 ]; then
    echo "No arguments supplied, please provide the package's path and the test type (e.g. --unit or --browser)"
    echo "Usage: $0 <package_path> <test_type> [args...]"
fi

location="$(realpath "$PWD/$1")"
if [ ! -d "$location" ]; then
    echo "The provided package path does not exist or is not a directory: $location"
    exit 1
fi

shift
case "$1" in
  --unit) testType="unit" ;;
  --browser) testType="browser" ;;
  *) echo "Unknown test type: $2. Please use --unit or --browser."; exit 1 ;;
esac

shift
args=("$@")

# Check if jq is installed
if ! command -v jq &> /dev/null; then
    echo "jq is required but not installed. Aborting."
    exit 1
fi

runTestSuite() {
    if [ "$testType" == "unit" ]; then
      echo -e "🧪  Running unit tests for $workspace...\n"
      pnpm exec vitest --run "${args[@]}" || { all_tests_passed=false; }
    elif [ "$testType" == "browser" ]; then
      echo -e "🧪  Running browser tests for $workspace...\n"
      # TODO: to implement
    fi
}

processWorkspace() {
    if [ ! -d "$location" ]; then
        echo "⚠ No directory found at $location"
        return
    fi

    package_json_path="$location/package.json"
    if [ ! -f "$package_json_path" ]; then
        echo "⚠ No package.json found at $package_json_path"
        return
    fi

    workspace=$(jq -r '.name' "$package_json_path")
    if [ -z "$workspace" ]; then
        echo "⚠ No name found in package.json at $package_json_path"
        return
    fi

    echo -e "⏳  Processing workspace $workspace at location $location...\n"

    echo "⚙️  Checking '$package_json_path' for peerDependencies and importmap dependencies to have the same version"
    deps=$(jq -r '.peerDependencies | keys[]' "$package_json_path")
    for library in $deps; do
        version=$(jq -r ".peerDependencies.\"$library\"" "$package_json_path")
        importmap_version=$(jq -r ".symfony.importmap.\"$library\" | if type == \"string\" then . else .version end" "$package_json_path")

        if [ "$importmap_version" == null ]; then
            echo " ⚠  No importmap version found for $library in $package_json_path, skipping..."
            continue
        fi

        if [ "$version" != "$importmap_version" ]; then
            echo " ⚠  Version mismatch for $library: $version (peerDependencies) vs $importmap_version (importmap)"
            echo " ⚠  You need to match the version of the \"peerDependency\" with the version in the \"importmap\""
            exit 1
        fi
    done

    echo "⚙️  Checking '$package_json_path' for peerDependencies with multiple versions defined"
    deps_with_multiple_versions=$(jq -r '.peerDependencies | to_entries[] | select(.value | contains("||")) | .key' "$package_json_path")

    if [ -n "$deps_with_multiple_versions" ]; then
        echo " -> Multiple versions found for peerDependencies: $deps_with_multiple_versions"
        for library in $deps_with_multiple_versions; do
            versionValue=$(jq -r ".peerDependencies.\"$library\"" "$package_json_path")

            IFS="||" read -ra versions <<< "$versionValue"

            for version in "${versions[@]}"; do
                trimmed_version=$(echo "$version" | tr -d '[:space:]')
                if [ -n "$trimmed_version" ]; then
                    # Install each version of the library separately
                    echo -e "  - Install $library@$trimmed_version for $workspace\n"
                    pnpm add "$library@$trimmed_version" --save-peer --filter "$workspace"

                    runTestSuite
                fi
            done
        done

        echo " -> Reverting version changes from $package_json_path"
        git checkout -- "$package_json_path" "$PROJECT_DIR/pnpm-lock.yaml"
    else
        echo -e " -> No peerDependencies found with multiple versions defined\n"
        runTestSuite
    fi
}

processWorkspace

# Check the flag at the end and exit with code 1 if any test failed
if [ "$all_tests_passed" = false ]; then
    echo "Some tests failed."
    exit 1
else
    echo "All tests passed!"
    exit 0
fi
