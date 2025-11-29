# Create a non-exiting version of _run_task for sequential execution
# This is used to run the tests sequentially on Windows
# because parallel is not available on Windows.
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

# Install specific versions of PropertyInfo and TypeInfo based on PHP and Symfony versions
# To remove in Symfony UX 4.0
live_component_post_install() {
  local php_version=$1

  case "$php_version" in
    8.1)
      # no-op, let Composer install the best PropertyInfo version (defined in composer.json), but do not require TypeInfo
      return 0
      ;;
    8.2)
      # PropertyInfo 7.1 (experimental PropertyTypeExtractorInterface::getType) and TypeInfo 7.2 (lowest non-experimental)
      composer require symfony/property-info:7.1.* symfony/type-info:7.2.*
      return $?
      ;;
    8.3)
      # Install PropertyInfo 7.3 (deprecate PropertyTypeExtractorInterface::getTypes) and TypeInfo 7.3 (new features and deprecations)
      composer require symfony/property-info:7.3.* symfony/type-info:7.3.*
      return $?
      ;;
  esac

  # Install the best TypeInfo version available
  composer require symfony/type-info
}
export -f live_component_post_install
