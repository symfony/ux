# CHANGELOG

## 2.30

-  Ensure compatibility with PHP 8.5

## 2.29.0

-  Add Symfony 8 support

## 2.25.0

-   Improve DX when `symfony/http-client` is not installed.

## 2.24.0

-    Add `xmlns` attribute to icons downloaded with Iconify, to correctly render icons browser as an external file, in SVG editors, and in files explorers or text editors previews.
It **may breaks your pipeline** if you assert on `ux_icon()` or `<twig:ux:icon>` output in your tests, and forgot [to lock your icons](https://symfony.com/bundles/ux-icons/current/index.html#locking-on-demand-icons).
We recommend you to **lock** your icons **before** upgrading to UX Icons 2.24. We also suggest you to to **force-lock** your icons **after** upgrading to UX Icons 2.24, to add the attribute `xmlns` to your icons already downloaded from Iconify.

## 2.20.0

-   Add `aliases` configuration option to define icon alternative names.
-   Add support for `int` and `float` attribute values in `<twig:ux:icon />`.
-   Add support for Icon sets, configurable with `icon_sets` option.

## 2.19.0

-   Add `ignore_not_found` option to silence error during rendering if the
    icon is not found.

## 2.17.0

-   Add component
