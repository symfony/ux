# CHANGELOG

## 2.13.0

-   Normalize parameters names given to twig helper 'stimulus_action()'.
    **BC Break**: previously, parameters given in camelCase (eg.
    `bigCrocodile`) were incorrectly registered by the controller as
    flatcase (`event.params.bigcrocodile`). This was fixed, which means
    they are now correctly registered as camelCase
    (`event.params.bigCrocodile`).

## 2.10.0

-   Handle Stimulus outlets

## 2.9.0

-   Introduce the bundle
