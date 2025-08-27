# CHANGELOG

## 2.30

-  Ensure compatibility with PHP 8.5

## 2.29.0

-  Add Symfony 8 support

## 2.28.0

-   Default plugins like `clear_button` or `remove_button` can now be removed when setting their value to `false` in the `tom_select_options.plugins` option, for example:
```php
<?php
#[AsEntityAutocompleteField]
class IngredientAutocompleteType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => Ingredient::class,
            'tom_select_options' => [
                'plugins' => [
                    'clear_button' => false, // Disable the clear button
                    'remove_button' => false, // Disable the remove button
                ],
            ],
        ]);
    }

    public function getParent(): string
    {
        return BaseEntityAutocompleteType::class;
    }
}
```

## 2.25.0

-   Escape `querySelector` dynamic selector with `CSS.escape()` #2663

## 2.23.0

-   Deprecate `ExtraLazyChoiceLoader` in favor of `Symfony\Component\Form\ChoiceList\Loader\LazyChoiceLoader`
-   Reset TomSelect when updating url attribute #1505
-   Add `getAttributes()` method to define additional attributes for autocomplete results #2541

## 2.22.0

-   Take `labelField` TomSelect option into account #2382

## 2.21.0

-   Translate the `option_create` option from TomSelect with remote data setup #2279
-   Add one missing Dutch translation #2279

## 2.20.0

-   Translate the `option_create` option from TomSelect #2108

## 2.17.0

-   Allow `choice_value` option in entity autocomplete fields #1723

## 2.16.0

-   Missing translations added for many languages #1527 #1528 #1535

## 2.15.0

-   Add doctrine/orm 3 support #1468
-   Allow passing extra options to the autocomplete fields #1322
-   Fix 2 bugs where TomSelect would reset when not necessary #1502
-   Add one missing German translation #1521

## 2.14.0

-   Fixed behavior of Autocomplete when the underlying `select` or `option`
    elements were modified to hopefully, more reliably, reset the autocomplete
    instance. This is particularly important with LiveComponents.
-   Add support for the `render.loading_more` Tom Select Virtual Scroll option (`loading_more_text`)
-   Avoid losing the selected options when the Stimulus component is disconnected
    and reconnected to the DOM.
-   Added `tom-select/dist/css/tom-select.bootstrap4.css` to `autoimport` - this
    will cause this to appear in your `controllers.json` file by default, but disabled
    see.
-   Allow passing `extra_options` key in an array passed as a `3rd` argument of the `->add()` method.
    It will be used during the Ajax call to fetch results.

## 2.13.2

-   Revert "Change JavaScript package to `type: module`"

## 2.13.0

-   Add new BaseEntityAutocompleteType
-   Drop symfony 5.4 support.
-   Add Symfony 7 support.
-   Change JavaScript package to `type: module`

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
