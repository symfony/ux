# CHANGELOG

## 2.23.0

-   Listen to Stimulus `disconnect` event to destroy the chart #1944

## 2.18.0

-   Replace `chartjs/auto` import with explicit `Chart.register()` call #1263

## 2.17.0

-   Add `chartjs:view-value-change` event #1605

## 2.15.0

-   Remove restriction that prevented Chart.js 3.9 #1518

## 2.14.0

-   Add support for Chart.js version 4

## 2.13.2

-   Change "module" key back to "main" in package.json

## 2.13.1

-   Revert "Change JavaScript package to `type: module`"

## 2.13.0

-   Add Symfony 7 support.
-   Change JavaScript package to `type: module`

## 2.9.0

-   Add support for symfony/asset-mapper

-   Add dependency on symfony/stimulus-bundle

-   Minimum required PHP version is now 8.1.

-   Minimum required Symfony version is now 5.4.

## 2.8.0

-   The chart will now automatically re-render if the `view` Stimulus value
    (i.e. the `data-symfony--ux-chartjs--chart-view-value` attribute) changes.
    This makes Chart.js work perfectly inside of a LiveComponent.

## 2.7.0

-   The `chartjs:connect` JavaScript event now bubbles up.

-   Add `assets/src` to `.gitattributes` to exclude source TypeScript files from
    installing.

-   TypeScript types are now included.

## 2.6.0

-   [BC BREAK] The `assets/` directory was moved from `Resources/assets/` to `assets/`. Make
    sure the path in your `package.json` file is updated accordingly.

-   The directory structure of the bundle was updated to match modern best-practices.

## 2.0

-   Support for `stimulus` version 2 was removed and support for `@hotwired/stimulus`
    version 3 was added. See the [@symfony/stimulus-bridge CHANGELOG](https://github.com/symfony/stimulus-bridge/blob/main/CHANGELOG.md#300)
    for more details.
-   Support added for Symfony 6
-   Upgrade Chart.js to version 3

## 1.3

-   [DEPENDENCY CHANGE] `chart.js` is no longer included automatically (#93)
    but `symfony/flex` will automatically add it to your `package.json` file
    when upgrading. Additionally `symfony/flex` 1.13 or higher is now required
    if installed.

-   Chart.js: add horizontalBar type to chart model #87 - @duboiss
