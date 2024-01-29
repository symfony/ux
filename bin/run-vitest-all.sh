#!/bin/bash

# Flag to track if any test fails
all_tests_passed=true

# Check if jq is installed
if ! command -v jq &> /dev/null; then
    echo "jq is required but not installed. Aborting."
    exit 1
fi

runTestSuite() {
    echo -e "Running tests for $workspace...\n"
    yarn workspace $workspace run vitest --run || { all_tests_passed=false; }
}

processWorkspace() {
    local workspace="$1"
    local location="$2"

    echo -e "Processing workspace $workspace at location $location...\n"

    package_json_path="$location/package.json"
    echo "Checking '$package_json_path' for peerDependencies with multiple versions defined"
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
                    yarn workspace "$workspace" add "$library@$trimmed_version" --peer

                    runTestSuite
                fi
            done
        done
    else
        echo -e " -> No peerDependencies found with multiple versions defined\n"
        runTestSuite
    fi
}

# Get all workspace names
workspaces_info=$(yarn workspaces info)

# Iterate over each workspace using process substitution
while IFS= read -r workspace_info; do
    # Split the workspace_info into workspace and location
    workspace=$(echo "$workspace_info" | awk '{print $1}')
    location=$(echo "$workspace_info" | awk '{print $2}')

    # Call the function to process the workspace
    processWorkspace "$workspace" "$location"

done < <(echo "$workspaces_info" | jq -r 'to_entries[0:] | .[] | "\(.key) \(.value.location)"')

# Check the flag at the end and exit with code 1 if any test failed
if [ "$all_tests_passed" = false ]; then
    echo "Some tests failed."
    exit 1
else
    echo "All tests passed!"
    exit 0
fi
