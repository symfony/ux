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

before_composer_install() {
  local component=$1
  local php_version=$2

  # Install specific versions of PropertyInfo and TypeInfo based on PHP version
  # To remove in Symfony UX 4.0
  if [[ "$component" == "LiveComponent" ]]; then
    case "$php_version" in
      8.2)
        # no-op, let Composer install the best PropertyInfo version (defined in composer.json), but do not require TypeInfo
        return 0
        ;;
      8.3)
        # PropertyInfo 7.1 (experimental PropertyTypeExtractorInterface::getType) and TypeInfo 7.2 (lowest non-experimental)
        composer require symfony/property-info:7.1.* symfony/type-info:7.2.* --no-update
        return $?
        ;;
      8.4)
        # Install PropertyInfo 7.4 (deprecate PropertyTypeExtractorInterface::getTypes from 7.3) and TypeInfo 7.4, but for Symfony 8 compatibility
        composer require symfony/property-info:7.4.* symfony/type-info:7.4.* --no-update
        return $?
        ;;
    esac

    # Install the best TypeInfo version available
    composer require symfony/type-info --no-update
  fi

}
export -f before_composer_install
