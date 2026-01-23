#!/bin/bash

# This script is used to unit test assets from an UX package.
# It also handle the case where a package has multiple versions of a peerDependency defined.
#
# You can configure which peer dependency versions to test by adding a "config.tests.peerDependencyVersions"
# field in your package.json. Example:
#
# "config": {
#     "tests": {
#         "peerDependencyVersions": [
#             { "react": "^18.0", "react-dom": "^18.0" },
#             { "react": "^19.0", "react-dom": "^19.0" }
#         ]
#     }
# }
#
# If no configuration is provided, the script will fall back to testing each peerDependency
# with multiple versions (||) independently.

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
args=("$@")

# Check if jq is installed
if ! command -v jq &> /dev/null; then
    echo "jq is required but not installed. Aborting."
    exit 1
fi

runTestSuite() {
  echo -e "🧪  Running unit tests for $workspace...\n"
  # Using "cd" instead of "pnpm --filter" prevent rendering issues in GitHub Actions that does not support nested groups
  (cd "$location"; pnpm exec vitest --run "${args[@]}") || { all_tests_passed=false; }
}

# Process peer dependency versions from explicit configuration
processConfiguredVersions() {
    local config_length
    config_length=$(jq '.config.tests.peerDependencyVersions | length' "$package_json_path")

    echo " -> Found $config_length configured peer dependency version set(s)"

    for ((i=0; i<config_length; i++)); do
        echo -e "\n📦  Processing version set $((i+1))/$config_length..."

        # Get all packages and versions for this configuration set
        install_args=""
        description=""
        while IFS="=" read -r pkg version; do
            install_args="$install_args $pkg@$version"
            description="$description $pkg@$version"
        done < <(jq -r ".config.tests.peerDependencyVersions[$i] | to_entries[] | \"\(.key)=\(.value)\"" "$package_json_path")

        echo -e "  - Installing$description for $workspace\n"
        (cd "$location" || exit 1; pnpm add $install_args --save-peer --silent)
        echo -e "  - Building $workspace assets...\n"
        (cd "$location" || exit 1; pnpm run build)

        runTestSuite
    done

    echo -e "\n -> Reverting changes"
    git checkout -- "$package_json_path" "$location/dist" "$PROJECT_DIR/pnpm-lock.yaml"
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

    echo "⚙️  Checking '$package_json_path' for peer dependency test configuration"

    # Check if explicit configuration exists
    has_config=$(jq 'if has("config") and .config != null and (.config | has("tests")) and .config.tests != null and (.config.tests | has("peerDependencyVersions")) then true else false end' "$package_json_path")

    if [ "$has_config" = "true" ]; then
        processConfiguredVersions
    else
        # Fallback: process peer dependencies with multiple versions (||) independently
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
                        echo -e "  - Installing $library@$trimmed_version for $workspace\n"
                        (cd "$location" || exit 1; pnpm add "$library@$trimmed_version" --save-peer --silent)
                        echo -e "  - Building $workspace assets...\n"
                        (cd "$location" || exit 1; pnpm run build)

                        runTestSuite
                    fi
                done
            done

            echo " -> Reverting changes"
            git checkout -- "$package_json_path" "$location/dist" "$PROJECT_DIR/pnpm-lock.yaml"
        else
            echo -e " -> No peerDependencies found with multiple versions defined\n"
            runTestSuite
        fi
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
