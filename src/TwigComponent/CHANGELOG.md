# CHANGELOG

## 2.20.0

-  Add Anonymous Component support for 3rd-party bundles #2019

## 2.17.0

-   Add nested attribute support #1405

## 2.16.0

-   Introduce CVA to style TwigComponent #1416
-   Drop Twig 2 support #1436
-   Fix full context is stored in profiler #1552
-   Add CVA (Class variant authority) integration #1416

## 2.15.0

-   Add the ability to render specific attributes from the `attributes` variable #1442
-   Restrict Twig 3.9 for now #1486
-   Build reproducible TemplateMap to fix possible post-deploy breakage #1497

## 2.14.0

-   Make `ComponentAttributes` traversable/countable
-   Fixed lexing some `{# twig comments #}` with HTML Twig syntax
-   Fix various usages of deprecated Twig code

## 2.13.0

-   [BC BREAK] Add component metadata to `PreMountEvent` and `PostMountEvent`
-   Added configuration to separate your components into different "namespaces"
-   Add `outerScope` variable reach variables from the parent template of an
    "embedded" component.
-   Deprecate calling `ComponentTemplateFinder` constructor without `directory` argument.
-   Add profiler integration: `TwigComponentDataCollector` and debug toolbar templates
-   Add search feature in `debug:twig-component` command.
-   Fix inconsistencies with how `{% component %}`/`<twig:component>` syntaxes are
    rendered vs `component()`: `PostRenderEvent` is now dispatched & the template
    resolution happens at runtime.
-   Fix priority of passed in props vs default props with anonymous components.
-   Add Symfony 7 support.
-   TwigPreLexer: improve performance.
-   Fix twig:lint bug with anonymous component tag.

## 2.12.0

-   Added a `debug:twig-component` command.
-   Fixed bad exception when the error comes from a Twig template.
-   Fixed deprecation with `TemplateCacheWarmer` return type.

## 2.11.0

-   Support ...spread operator with html syntax (requires Twig 3.7.0 or higher)
-   Add support for anonymous Twig components.
-   Add `RenderedComponent::crawler()` and `toString()` methods.
-   Allow a block outside a Twig component to be available inside via `outerBlocks`.
-   Fix `<twig:component>` syntax where an attribute is set to an empty value.
-   Add component debug command for TwigComponent and LiveComponent.

## 2.9.0

-   The `ComponentAttributes::defaults()` method now accepts any iterable argument.
    The `ComponentAttributes::add()` method has been deprecated. To add a Stimulus
    controller to the attributes, use `{{ attributes.defaults(stimulus_controller('...')) }}`.

## 2.8.0

-   Add new HTML syntax for rendering components: `<twig:ComponentName>`
-   `true` attribute values now render just the attribute name, `false` excludes it entirely.
-   Add helpers for testing components.
-   The first argument to `AsTwigComponent` is now optional and defaults to the class name.
-   Allow passing a FQCN to `ComponentFactory` methods.

## 2.7.0

-   `PreMount` and `PostMount` hooks can now return nothing.

## 2.5

-   [BC BREAK] The `PreRenderEvent` namespace was changed from `Symfony\UX\TwigComponent\EventListener`
    to `Symfony\UX\TwigComponent\Event`.

-   Add new autowireable `ComponentRendererInterface`

-   Added `PostRenderEvent` and `PreCreateForRenderEvent` which are dispatched just
    before or after a component is rendered.

-   Added `PostMountEvent` and `PreMountEvent` which are dispatched just before
    or after the component's data is mounted.

-   Added Twig template namespaces - #460.

## 2.2

-   Allow to pass stringable object as non mapped component attribute.
-   Add _embedded_ components.
-   Allow `ExposeInTemplate` to be used on public component methods.

## 2.1

-   Make public component properties available directly in the template (`{{ prop }}` vs `{{ this.prop }}`).

-   Add `PreMount` priority parameter.

-   Add `PostMount` hook component hook to intercept extra props.

-   Add attributes system that takes extra props passed to `component()` and converts them
    into a `ComponentAttributes` object available in your template as `attributes`.

-   Add `PreRenderEvent` to intercept/manipulate twig template/variables before rendering.

-   Add `ExposeInTemplate` attribute to make non-public properties available in component
    templates directly.

-   Add _Computed Properties_ system.

## 2.0.0

-   Support for `stimulus` version 2 was removed and support for `@hotwired/stimulus`
    version 3 was added. See the [@symfony/stimulus-bridge CHANGELOG](https://github.com/symfony/stimulus-bridge/blob/main/CHANGELOG.md#300)
    for more details.

-   Minimum PHP version was bumped to 8.0 so that PHP 8 attributes could be used.

-   The `ComponentInterface` was dropped and replaced by the `AsTwigComponent` attribute.
    The `getComponentName()` was replaced by a `name` argument to the attribute.

Before:

```php
use Symfony\UX\TwigComponent\ComponentInterface;

class AlertComponent implements ComponentInterface
{
    public string $type = 'success';
    public string $message;

    public static function getComponentName(): string
    {
        return 'alert';
    }
}
```

After:

```php
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('alert')]
class AlertComponent
{
    public string $type = 'success';
    public string $message;
}
```

-   If you're using a version _lower_ than Symfony 5.3, you will need
    to manually tag all component services with `twig.component`. This is
    because Symfony 5.3 introduces autoconfiguration for PHP 8 attributes,
    which this library leverages.

-   The template for a component can now be controlled via the `template` argument
    to the `AsTwigComponent` attribute:

```php
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('alert', template: 'other/path/alert.html.twig')]
class AlertComponent
{
    // ...
}
```

## Pre-Release

-   The TwigComponent library was introduced!
