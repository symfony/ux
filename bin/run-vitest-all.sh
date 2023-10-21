#!/bin/bash

# Get all workspace names
workspaces=$(yarn workspaces --json info | jq -r 'if .type == "log" then .data | fromjson | keys[] else empty end')

# Flag to track if any test fails
all_tests_passed=true

for workspace in $workspaces; do
  echo "Running tests in $workspace..."

  # Run the tests and if they fail, set the flag to false
  yarn workspace $workspace run vitest --run || { echo "$workspace failed"; all_tests_passed=false; }
done

# Check the flag at the end and exit with code 1 if any test failed
if [ "$all_tests_passed" = false ]; then
  echo "Some tests failed."
  exit 1
else
  echo "All tests passed!"
  exit 0
fi
