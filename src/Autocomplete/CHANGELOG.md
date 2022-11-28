# CHANGELOG

## Unreleased

-   Add support for recent versions of Tom Select

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

-   Fix issue where `max_results` was not passed as a Stimulus value (#538)

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
