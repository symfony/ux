# Twig Components

**EXPERIMENTAL** This component is currently experimental and is
likely to change, or even change drastically.

Twig components give you the power to bind an object to a template, making
it easier to render and re-use small template "units" - like an "alert",
markup for a modal, or a category sidebar:

Every component consists of (1) a class:

```php
// src/Components/AlertComponent.php
namespace App\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('alert')]
class AlertComponent
{
    public string $type = 'success';
    public string $message;
}
```

And (2) a corresponding template:

```twig
{# templates/components/alert.html.twig #}
<div class="alert alert-{{ this.type }}">
    {{ this.message }}
</div>
```

Done! Now render it wherever you want:

```twig
{{ component('alert', { message: 'Hello Twig Components!' }) }}
```

Enjoy your new component!

![Example of the AlertComponent](https://github.com/symfony/ux/blob/2.x/src/TwigComponent/alert-example.png?raw=true)

This brings the familiar "component" system from client-side frameworks
into Symfony. Combine this with [Live Components](https://github.com/symfony/ux-live-component),
to create an interactive frontend with automatic, Ajax-powered rendering.

Want a demo? Check out https://github.com/weaverryan/live-demo.

## Installation

Let's get this thing installed! Run:

```
composer require symfony/ux-twig-component
```

That's it! We're ready to go!

## Creating a Basic Component

Let's create a reusable "alert" element that we can use to show
success or error messages across our site. Step 1 is always to create
a component that has an `AsTwigComponent` class attribute. Let's start as
simple as possible:

```php
// src/Components/AlertComponent.php
namespace App\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('alert')]
class AlertComponent
{
}
```

**Note:** If this class is auto-configured, _and_ you're using Symfony 5.3+,
then you're all set. Otherwise, register the service and tag it with
`twig.component` and with a `key` tag attribute for the component's name
(`alert`).

Step 2 is to create a template for this component. By default,
templates live in `templates/components/{Component Name}.html.twig`,
where `{Component Name}` is whatever you passed as the first argument
to the `AsTwigComponent` class attribute:

```twig
{# templates/components/alert.html.twig #}
<div class="alert alert-success">
    Success! You've created a Twig component!
</div>
```

This isn't very interesting yet... since the message is hardcoded
into the template. But it's enough! Celebrate by rendering your
component from any other Twig template:

```twig
{{ component('alert') }}
```

Done! You've just rendered your first Twig Component! Take a moment
to fist pump - then come back!

## Passing Data into your Component

Good start: but this isn't very interesting yet! To make our
`alert` component reusable, we need to make the message and
type (e.g. `success`, `danger`, etc) configurable. To do
that, create a public property for each:

```diff
// src/Components/AlertComponent.php
// ...

#[AsTwigComponent('alert')]
class AlertComponent
{
+    public string $message;

+    public string $type = 'success';

    // ...
}
```

In the template, the `AlertComponent` instance is available via
the `this` variable. Use it to render the two new properties:

```twig
<div class="alert alert-{{ this.type }}">
    {{ this.message }}
</div>
```

How can we populate the `message` and `type` properties? By passing them
as a 2nd argument to the `component()` function when rendering:

```twig
{{ component('alert', { message: 'Successfully created!' }) }}

{{ component('alert', {
    type: 'danger',
    message: 'Danger Will Robinson!'
}) }}
```

Behind the scenes, a new `AlertComponent` will be instantiated and
the `message` key (and `type` if passed) will be set onto the `$message`
property of the object. Then, the component is rendered! If a
property has a setter method (e.g. `setMessage()`), that will
be called instead of setting the property directly.

### Customize the Twig Template

You can customize the template used to render the template by
passing it as the second argument to the `AsTwigComponent` attribute:

```diff
// src/Components/AlertComponent.php
// ...

-#[AsTwigComponent('alert')]
+#[AsTwigComponent('alert', 'my/custom/template.html.twig')]
class AlertComponent
{
    // ...
}
```

**Note:** If this class is auto-configured, _and_ you're using Symfony 5.3+,
then you're all set. Otherwise, register the service and tag it with
`twig.component` and with a `key` tag attribute for the component's name
(`alert`) and a `template` tag attribute (`my/custom/template.html.twig`).

### The mount() Method

If, for some reason, you don't want an option to the `component()`
function to be set directly onto a property, you can, instead, create
a `mount()` method in your component:

```php
// src/Components/AlertComponent.php
// ...

#[AsTwigComponent('alert')]
class AlertComponent
{
    public string $message;
    public string $type = 'success';

    public function mount(bool $isSuccess = true)
    {
        $this->type = $isSuccess ? 'success' : 'danger';
    }

    // ...
}
```

The `mount()` method is called just one time immediately after your
component is instantiated. Because the method has an `$isSuccess`
argument, we can pass an `isSuccess` option when rendering the
component:

```twig
{{ component('alert', {
    isSuccess: false,
    message: 'Danger Will Robinson!'
}) }}
```

If an option name matches an argument name in `mount()`, the
option is passed as that argument and the component system
will _not_ try to set it directly on a property.

### PreMount Hook

If you need to modify/validate data before it's _mounted_ on the
component use a `PreMount` hook:

```php
// src/Components/AlertComponent.php

use Symfony\UX\TwigComponent\Attribute\PreMount;
// ...

#[AsTwigComponent('alert')]
class AlertComponent
{
    public string $message;
    public string $type = 'success';

    #[PreMount]
    public function preMount(array $data): array
    {
        // validate data
        $resolver = new OptionsResolver();
        $resolver->setDefaults(['type' => 'success']);
        $resolver->setAllowedValues('type', ['success', 'danger']);
        $resolver->setRequired('message');
        $resolver->setAllowedTypes('message', 'string');

        return $resolver->resolve($data)
    }

    // ...
}
```

## Fetching Services

Let's create a more complex example: a "featured products" component.
You _could_ choose to pass an array of Product objects into the
`component()` function and set those on a `$products` property. But
instead, let's allow the component to do the work of executing the query.

How? Components are _services_, which means autowiring
works like normal. This example assumes you have a `Product`
Doctrine entity and `ProductRepository`:

```php
// src/Components/FeaturedProductsComponent.php
namespace App\Components;

use App\Repository\ProductRepository;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('featured_products')]
class FeaturedProductsComponent
{
    private ProductRepository $productRepository;

    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
    }

    public function getProducts(): array
    {
        // an example method that returns an array of Products
        return $this->productRepository->findFeatured();
    }
}
```

In the template, the `getProducts()` method can be accessed via
`this.products`:

```twig
{# templates/components/featured_products.html.twig #}

<div>
    <h3>Featured Products</h3>

    {% for product in this.products %}
        ...
    {% endfor %}
</div>
```

And because this component doesn't have any public properties that
we need to populate, you can render it with:

```twig
{{ component('featured_products') }}
```

**NOTE**
Because components are services, normal dependency injection
can be used. However, each component service is registered with
`shared: false`. That means that you can safely render the same
component multiple times with different data because each
component will be an independent instance.

### Computed Properties

In the previous example, instead of querying for the featured products
immediately (e.g. in `__construct()`), we created a `getProducts()`
method and called that from the template via `this.products`.

This was done because, as a general rule, you should make your components
as _lazy_ as possible and store only the information you need on its
properties (this also helps if you convert your component to a
[live component](https://github.com/symfony/ux-live-component) later). With this setup, the
query is only executed if and when the `getProducts()` method
is actually called. This is very similar to the idea of
"computed properties" in frameworks like [Vue](https://v3.vuejs.org/guide/computed.html).

But there's no magic with the `getProducts()` method: if you
call `this.products` multiple times in your template, the query
would be executed multiple times.

To make your `getProducts()` method act like a true computed property
(where its value is only evaluated the first time you call the
method), you can store its result on a private property:

```diff
// src/Components/FeaturedProductsComponent.php
namespace App\Components;
// ...

#[AsTwigComponent('featured_products')]
class FeaturedProductsComponent
{
    private ProductRepository $productRepository;

+    private ?array $products = null;

    // ...

    public function getProducts(): array
    {
+        if ($this->products === null) {
+            $this->products = $this->productRepository->findFeatured();
+        }

-        return $this->productRepository->findFeatured();
+        return $this->products;
    }
}
```

## Embedded Components

It's totally possible to embed one component into another. When you do
this, there's nothing special to know: both components render independently.
If you're using [Live Components](https://github.com/symfony/ux-live-component),
then there _are_ some guidelines related to how the re-rendering of parent
and child components works. Read [Live Embedded Components](https://github.com/symfony/ux-live-component#embedded-components).

## Contributing

Interested in contributing? Visit the main source for this repository:
https://github.com/symfony/ux/tree/main/src/TwigComponent.

Have fun!
