# CHANGELOG

## 2.5

-   The `PreRenderEvent` namespace was changed from `Symfony\UX\TwigComponent\EventListener`
    to `Symfony\UX\TwigComponent\Event`.

-   Add new autowireable `ComponentRendererInterface`

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
