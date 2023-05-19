# CHANGELOG

## 2.9.0

-   Add support for symfony/asset-mapper

## 2.8.0

-   The autocomplete now watches for update to any `option` elements inside of
    it change, including the empty / placeholder element. Additionally, if the
    `select` or `input` element's `disabled` attribute changes, the autocomplete
    instance will update accordingly. This makes Autocomplete work correctly inside
    of a LiveComponent. This functionality does _not_ work for `multiple` selects.

-   Added support for using [OptionGroups](https://tom-select.js.org/examples/optgroups/).

## 2.7.0

-   Add `assets/src` to `.gitattributes` to exclude them from the installation

-   Fix minCharacters option default value handling when using a falsy value like 0.

-   Fix TypeScript types

-   Add a new `route` parameter to `AsEntityAutocompleteField`, which allows to choose another route for Ajax calls.

-   Fix minCharacters option default value handling when using a falsy value like 0.

-   Fix TypeScript types

-   Add a way to detect if a field is an "autocomplete" field in form themes - #608

## 2.6.0

-   [BC BREAK]: The path to `routes.php` changed and you should update your
    route import accordingly:

```diff
# config/routes/ux_autocomplete.yaml
ux_autocomplete:
-    resource: '@AutocompleteBundle/Resources/routes.php'
+    resource: '@AutocompleteBundle/config/routes.php'
    prefix: '/autocomplete'
```

-   Add support for `tom-select` version `2.2.2` and made this the minimum-supported
    version.
-   Added support for the `preload` TomSelect option.
-   Fix don't add WHERE IN criteria without params (#561).
-   Fix issue where `max_results` was not passed as a Stimulus value (#538).
-   Add all possible stylesheets for tom-select to the autoimport to choose from.

## 2.5.0

-   Automatic pagination support added: if the query would return more results
    than your limit, when the user scrolls to the bottom of the options, it will
    make a second Ajax call to load more.

-   Added `max_results` option to limit the number of results returned by the
    Ajax endpoint - #478.

-   Support added for setting the required minimum search query length (defaults to 3) (#492)

-   Fix support for more complex ids, like UUIDs - #494.

-   Fixed bug where sometimes an error could occur in the Ajax call related to
    the label - #520.

## 2.4.0

-   Component added!
