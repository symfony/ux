# Create a non-exiting version of _run_task for sequential execution
# This is used to run the tests sequentially on Windows
# because parallel is not available on Windows.
_run_task() {
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

    return $?
}
export -f _run_task

install_property_info_for_version() {
  local php_version="$1"
  local min_stability="$2"

  if [ "$php_version" = "8.2" ]; then
    composer require symfony/property-info:7.1.* symfony/type-info:7.2.*
  elif [ "$php_version" = "8.3" ]; then
    composer require symfony/property-info:7.2.* symfony/type-info:7.2.*
  elif [ "$php_version" = "8.4" ] && [ "$min_stability" = "stable" ]; then
    composer require symfony/property-info:7.3.* symfony/type-info:7.3.*
  elif [ "$php_version" = "8.4" ] && [ "$min_stability" = "dev" ]; then
    composer require symfony/property-info:>=7.3 symfony/type-info:>=7.3
  fi
}
export -f install_property_info_for_version
