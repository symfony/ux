<?php

/*
 * Lists the Symfony UX packages a test suite must run for a set of changed files.
 *
 * A package is selected when one of its files changed, or when it depends on a selected
 * package, directly or not. A changed file outside the packages that the suite may depend on
 * selects every package. The changed files are read from the standard input, one per line,
 * and the result is printed in the format of $GITHUB_OUTPUT.
 *
 * Usage: git diff --name-only HEAD^1 HEAD | php .github/affected-packages.php <unit|browser>
 *        php .github/affected-packages.php <unit|browser> --all
 */

if ('cli' !== \PHP_SAPI) {
    echo "This script can only be run from the command line.\n";
    exit(1);
}

$suite = $argv[1] ?? null;
if (!in_array($suite, ['unit', 'browser'], true)) {
    fwrite(\STDERR, "Usage: php .github/affected-packages.php <unit|browser> [--all]\n");
    exit(1);
}

$rootDir = dirname(__DIR__);

/*
 * Changed files outside the packages, matched with fnmatch() in this order:
 * "none" selects nothing, "php" every PHP package, "js" every JS package, "all" both.
 * A file that matches no pattern selects every package.
 */
$globalFiles = [
    'unit' => [
        'apps/*' => 'none',
        '.github/affected-packages.php' => 'all',
        '.github/build-packages.php' => 'php',
        '.github/workflows/unit-tests.yaml' => 'all',
        '.github/workflows/.utils.sh' => 'php',
        '.github/*' => 'none',
        'composer.json' => 'php',
        '.php-cs-fixer.dist.php' => 'none',
        '.twig-cs-fixer.dist.php' => 'none',
        'package.json' => 'js',
        'pnpm-lock.yaml' => 'js',
        'pnpm-workspace.yaml' => 'js',
        'vitest.config.base.mjs' => 'js',
        'playwright.config.base.ts' => 'none',
        'test/*' => 'js',
        'bin/*' => 'js',
        '.oxfmtrc.json' => 'none',
        '.oxlintrc.json' => 'none',
    ],
    'browser' => [
        'apps/e2e/*' => 'all',
        'apps/*' => 'none',
        '.github/affected-packages.php' => 'all',
        '.github/build-packages.php' => 'all',
        '.github/workflows/browser-tests.yml' => 'all',
        '.github/*' => 'none',
        '.php-cs-fixer.dist.php' => 'none',
        '.twig-cs-fixer.dist.php' => 'none',
        '.oxfmtrc.json' => 'none',
        '.oxlintrc.json' => 'none',
    ],
];

/*
 * Packages whose browser tests use a package they do not require, through a page of apps/e2e.
 */
$browserTestDependencies = [
    'Autocomplete' => ['LiveComponent'],
];

// apps/e2e imports these packages on every page, so a change to one of them can break any browser test
preg_match_all('{[\'"](@symfony/[^\'"/]+)}', implode("\n", array_map('file_get_contents', glob($rootDir.'/apps/e2e/assets/*.js'))), $matches);
$browserTestGlobalPackages = array_unique($matches[1]);

// 1. Find the packages, keyed by their directory relative to src/
$packages = [];
foreach ([...glob($rootDir.'/src/*/composer.json'), ...glob($rootDir.'/src/*/src/Bridge/*/composer.json')] as $composerFile) {
    $composer = json_decode(file_get_contents($composerFile), true, flags: \JSON_THROW_ON_ERROR);
    $packageJsonFile = dirname($composerFile).'/assets/package.json';
    $packageJson = is_file($packageJsonFile) ? json_decode(file_get_contents($packageJsonFile), true, flags: \JSON_THROW_ON_ERROR) : null;

    $packages[substr(dirname($composerFile), strlen($rootDir.'/src/'))] = [
        'name' => $composer['name'],
        'requires' => array_keys(($composer['require'] ?? []) + ($composer['require-dev'] ?? [])),
        'jsName' => $packageJson['name'] ?? null,
        'jsScripts' => array_keys($packageJson['scripts'] ?? []),
    ];
}
ksort($packages);

// 2. Select the packages changed by the files, documentation aside
$allSelected = array_fill_keys(array_keys($packages), true);
$selectsAll = in_array('--all', $argv, true);
$phpSelected = $jsSelected = $selectsAll ? $allSelected : [];

foreach ($selectsAll ? [] : array_filter(array_map('trim', file('php://stdin'))) as $file) {
    if (fnmatch('*.md', $file) || fnmatch('src/*/doc/*', $file)) {
        continue;
    }

    if (null !== $package = packageOf($file, array_keys($packages))) {
        $phpSelected[$package] = true;
        if (str_starts_with($file, 'src/'.$package.'/assets/')) {
            $jsSelected[$package] = true;
        }

        continue;
    }

    $scope = 'all';
    foreach ($globalFiles[$suite] as $pattern => $patternScope) {
        if (fnmatch($pattern, $file)) {
            $scope = $patternScope;
            break;
        }
    }

    if (in_array($scope, ['php', 'all'], true)) {
        $phpSelected = $allSelected;
    }
    if (in_array($scope, ['js', 'all'], true)) {
        $jsSelected = $allSelected;
    }
}

// 3. Add the packages depending on a selected package
$dirsByName = array_combine(array_column($packages, 'name'), array_keys($packages));
$dependents = [];
foreach ($packages as $dir => $package) {
    foreach ($package['requires'] as $requiredName) {
        if (isset($dirsByName[$requiredName])) {
            $dependents[$dirsByName[$requiredName]][] = $dir;
        }
    }
}
if ('browser' === $suite) {
    foreach ($browserTestDependencies as $dir => $requiredDirs) {
        foreach ($requiredDirs as $requiredDir) {
            $dependents[$requiredDir][] = $dir;
        }
    }
    foreach ($packages as $dir => $package) {
        if (in_array($package['jsName'], $browserTestGlobalPackages, true)) {
            $dependents[$dir] = array_keys($packages);
        }
    }
}

$phpSelected = withDependents($phpSelected, $dependents);
$jsSelected = withDependents($jsSelected, $dependents);

// 4. Print the selection, for the packages that have tests of this suite
if ('unit' === $suite) {
    echo 'php='.implode(' ', array_keys($phpSelected))."\n";
    echo 'js='.pnpmFilters($packages, $jsSelected, 'test:unit')."\n";
} else {
    // The browser tests render the PHP side of a package as well as its JS side
    echo 'js='.pnpmFilters($packages, $phpSelected + $jsSelected, 'test:browser')."\n";
}

/**
 * Finds the package that contains a file, the innermost one for a bridge.
 *
 * @param list<string> $dirs
 */
function packageOf(string $file, array $dirs): ?string
{
    $found = null;
    foreach ($dirs as $dir) {
        if (str_starts_with($file, 'src/'.$dir.'/') && strlen($dir) > strlen($found ?? '')) {
            $found = $dir;
        }
    }

    return $found;
}

/**
 * @param array<string, true>         $selected
 * @param array<string, list<string>> $dependents
 *
 * @return array<string, true>
 */
function withDependents(array $selected, array $dependents): array
{
    $queue = array_keys($selected);
    while (null !== $dir = array_shift($queue)) {
        foreach ($dependents[$dir] ?? [] as $dependent) {
            if (!isset($selected[$dependent])) {
                $selected[$dependent] = true;
                $queue[] = $dependent;
            }
        }
    }
    ksort($selected);

    return $selected;
}

/**
 * @param array<string, array{jsName: ?string, jsScripts: list<string>}> $packages
 * @param array<string, true>                                            $selected
 */
function pnpmFilters(array $packages, array $selected, string $script): string
{
    $filters = [];
    foreach (array_keys($selected) as $dir) {
        if (null !== $packages[$dir]['jsName'] && in_array($script, $packages[$dir]['jsScripts'], true)) {
            $filters[] = '--filter='.$packages[$dir]['jsName'];
        }
    }

    return implode(' ', $filters);
}
