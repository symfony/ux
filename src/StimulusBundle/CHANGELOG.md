# CHANGELOG

## 2.13.2

-   Revert "Change JavaScript package to `type: module`"

## 2.13.0

-   Normalize parameters names given to twig helper 'stimulus_action()'.
    **BC Break**: previously, parameters given in camelCase (eg.
    `bigCrocodile`) were incorrectly registered by the controller as
    flatcase (`event.params.bigcrocodile`). This was fixed, which means
    they are now correctly registered as camelCase
    (`event.params.bigCrocodile`).
-   Added AssetMapper 6.4 support.
-   Add Symfony 7 support.
-   Fix missing double dash in namespaced Stimulus outlets.
-   Change JavaScript package to `type: module`

## 2.10.0

-   Handle Stimulus outlets

## 2.9.0

-   Introduce the bundle
