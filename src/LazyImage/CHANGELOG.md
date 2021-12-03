# CHANGELOG

## 2.0

-   Support for `stimulus` version 2 was removed and support for `@hotwired/stimulus`
    version 3 was added. See the [@symfony/stimulus-bridge CHANGELOG](https://github.com/symfony/stimulus-bridge/blob/main/CHANGELOG.md#300)
    for more details.
-   The `data-hd-src` attribute was changed to use a Stimulus value called `src`. See the
    updated README for usage.
-   For both JavaScript events - `lazy-image:connect` and `lazy-image:ready` -
    the `event.detail.hd` `Image` instance was moved to `event.detail.image`.
-   Support added for Symfony 6
