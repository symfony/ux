# UPGRADE FROM `2.x` TO `3.0`

Symfony UX follows Symfony's release process, 2.x and 3.0 have the same
features, but Symfony UX 3.0 doesn't include any deprecated features. To
upgrade, make sure to resolve all deprecation notices. Read more about this in
the [Symfony documentation](https://symfony.com/doc/6.4/setup/upgrade_major.html#upgrade-major-symfony-deprecations).

> [!NOTE]
> Requires PHP `8.2` or higher.
>
> Requires Symfony `6.4` or higher.

## LazyImage

* The package has been removed, see the [previous README](https://raw.githubusercontent.com/symfony/ux/refs/heads/2.x/src/LazyImage/README.md)
  for migration steps

## LiveComponent

* Remove `csrf` argument from `AsLiveComponent` in favor of same-origin/CORS:
```diff
- #[AsLiveComponent(csrf: true)]
+ #[AsLiveComponent]
class MyLiveComponent {
    // ...
}
```

## Map

*  The Twig function `render_map()` has been removed, use `ux_map()` instead
*  The option `title` from `Polygon`, `Polyline`, `Rectangle` and `Circle`, has been removed, use option `infoWindow` instead
*  The property `rawOptions` from `ux:map:*:before-create` events has been removed, use `bridgeOptions` instead

## Swup

* The package has been removed, see the [previous README](https://raw.githubusercontent.com/symfony/ux/refs/heads/2.x/src/Turbo/README.md)
  for migration steps

## TogglePassword

* The package has been removed, see the [previous README](https://raw.githubusercontent.com/symfony/ux/refs/heads/2.x/src/TogglePassword/README.md)
  for migration steps

## TwigComponent

* The configuration `twig_component.defaults` is now mandatory and must contain at least one namespace/directory pair:
```diff
# config/packages/twig_component.yaml
twig_component:
    anonymous_template_directory: 'components/'
+   defaults:
+       # Namespace & directory for components
+       App\Twig\Components\: 'components/'
```

* Remove method `PreCreateForRenderEvent::getProps()` in favor of `PreCreateForRenderEvent::getInputProps()`
```diff
class HookIntoTwigPreCreateForRenderSubscriber implements EventSubscriberInterface
{
    public function onPreCreateForRender(PreCreateForRenderEvent $event): void
    {
-        $event->getProps();
+        $event->getInputProps();
    }

    public static function getSubscribedEvents(): array
    {
        return [PreCreateForRenderEvent::class => 'onPreCreateForRender'];
    }
}
```

* Remove `cva` Twig function in favor of [`html_cva` Twig function from `twig/html-extra:^3.12`](https://twig.symfony.com/html_cva)

**Before:**
```twig
{% set alert = cva({
    base: 'alert',
    variants: {
        color: { blue: 'bg-blue', red: 'bg-red', green: 'bg-green' },
        size: { sm: 'text-sm', md: 'text-md', lg: 'text-lg' }
    },
    defaultVariants: { size: 'md', color: 'blue' },
    compoundVariants: [{
        color: ['red'],
        size: ['lg'],
        class: 'font-bold'
    }],
    defaultVariants: {
        rounded: 'md'
    }
}) %}

<div class="{{ alert.apply({color, size}, attributes.render('class')) }}">
     {% block content %}{% endblock %}
</div>
```
**After:**
```twig
{% set alert = html_cva(
    base: 'alert',
    variants: {
        color: { blue: 'bg-blue', red: 'bg-red', green: 'bg-green' },
        size: { sm: 'text-sm', md: 'text-md', lg: 'text-lg' }
    },
    compound_variants: [{
        color: ['red'],
        size: ['lg'],
        class: 'font-bold'
    }],
    default_variants: {
        rounded: 'md'
    }
) %}

<div class="{{ alert.apply({color, size}, attributes.render('class')) }}">
     {% block content %}{% endblock %}
</div>
```

## Typed

* The package has been removed, see the [previous README](https://raw.githubusercontent.com/symfony/ux/refs/heads/2.x/src/Typed/README.md)
  for migration steps
