# CHANGELOG

## NEXT

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
