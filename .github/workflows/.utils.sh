# Runs a single task ($1 = title, $2 = command) and buffers its output into one
# grouped block. Returns the command's exit code instead of exiting, so it can be
# reused both by the parallel pool (_run_package_tests) and by _run_task.
_run_task_sequential() {
    local ok=0
    local title="$1"
    local start=$(date -u +%s)
    OUTPUT=$(bash -xc "$2" 2>&1) || ok=$?
    local end=$(date -u +%s)

    if [[ $ok -ne 0 ]]; then
      printf "\n%-70s%10s\n" $title $(($end-$start))s
      echo "$OUTPUT"
      echo "Job exited with: $ok"
      echo -e "\n::error::KO $title\\n"
    else
      printf "::group::%-68s%10s\n" $title $(($end-$start))s
      echo "$OUTPUT"
      echo -e "\n\\e[32mOK\\e[0m $title\\n\\n::endgroup::"
    fi

    return $ok
}
export -f _run_task_sequential

_run_task() {
    _run_task_sequential "$1" "$2"
    exit $?
}
export -f _run_task

# Run every package in $PACKAGES through its test command with bounded parallelism.
# Output is buffered per package and printed once all have finished, so grouped logs
# stay readable. Relies only on Bash job control, so it behaves the same on Linux and
# on Windows (Git Bash) — unlike GNU parallel, which is missing from Windows runners.
_run_package_tests() {
    # Default matches the previous "parallel -j +3": as many jobs as CPUs, plus 3.
    local max_jobs="${1:-$(( $(nproc 2>/dev/null || echo 4) + 3 ))}"
    local logs
    logs="$(mktemp -d)"
    local pkg safe rc=0

    for pkg in $PACKAGES; do
        # Throttle: wait for a running job to finish before starting the next package.
        # "|| :" keeps errexit (bash -e) from aborting on a failed job's exit code;
        # failures are collected from the .rc files during replay instead.
        while [ "$(jobs -rp | wc -l)" -ge "$max_jobs" ]; do wait -n || :; done
        # Bridge packages carry slashes (e.g. Map/src/Bridge/Google), so flatten
        # them into a single path segment for the temp file names.
        safe="${pkg//\//_}"
        # Run in the background, capturing the package's output and exit code to files
        # so the parallel logs can be replayed in a stable order below. "|| code=$?"
        # is required: under errexit a failing task would otherwise abort the subshell
        # before its exit code is recorded.
        {
            code=0
            _run_task_sequential "$pkg" \
                "(cd src/$pkg && $COMPOSER_MIN_STAB && $COMPOSER_UP && $PHPUNIT)" || code=$?
            echo "$code" > "$logs/$safe.rc"
        } > "$logs/$safe.log" 2>&1 &
    done
    wait

    # Replay each package's log in order, failing the step if any package exited non-zero.
    for pkg in $PACKAGES; do
        safe="${pkg//\//_}"
        cat "$logs/$safe.log"
        [ "$(cat "$logs/$safe.rc" 2>/dev/null || echo 1)" = 0 ] || rc=1
    done

    rm -rf "$logs"
    return "$rc"
}
export -f _run_package_tests
