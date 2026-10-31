# Symfony UX Breadcrumb

**EXPERIMENTAL** This bundle is currently experimental and is likely to change,
possibly significantly, before its first stable release.

Declare the breadcrumb trail of a page on its controller, and render it in Twig.
Nothing is translated or turned into a URL until a template asks for it.

```php
#[Route('/products', name: 'product_')]
final class ProductController extends AbstractController
{
    #[Route('', name: 'index')]
    #[Breadcrumb(label: 'Products', route: 'product_index')]
    public function index(): Response
    {
        return $this->render('product/index.html.twig');
    }

    #[Route('/{slug}', name: 'view')]
    #[Breadcrumb(
        label: '{name:product}',
        route: 'product_view',
        parameters: ['slug'],
        parent: [self::class, 'index'],
    )]
    public function view(Product $product): Response
    {
        return $this->render('product/view.html.twig');
    }

    #[Route('/{slug}/edit', name: 'edit')]
    #[Breadcrumb(label: 'Edit', parent: [self::class, 'view'])]
    public function edit(Product $product): Response
    {
        return $this->render('product/edit.html.twig');
    }
}
```

On the edit page, the trail reads Products, then the product, then Edit.

```twig
{# templates/base.html.twig #}
{{ ux_breadcrumb() }}
```

## Sponsor

The Symfony UX packages are [backed][1] by [Mercure.rocks][2].

Create real-time experiences in minutes! Mercure.rocks provides a realtime API service
that is tightly integrated with Symfony: create UIs that update in live with UX Turbo,
send notifications with the Notifier component, expose async APIs with API Platform and
create low level stuffs with the Mercure component. We maintain and scale the complex
infrastructure for you!

Help Symfony by [sponsoring][3] its development!

> [!IMPORTANT]
> **This repository is a READ-ONLY sub-tree split**.\
> See https://github.com/symfony/ux to create issues or submit pull requests.

## Resources

- [Documentation](doc/index.rst)
- [Report issues](https://github.com/symfony/ux/issues) and
  [send Pull Requests](https://github.com/symfony/ux/pulls)
  in the [main Symfony UX repository](https://github.com/symfony/ux)

[1]: https://symfony.com/backers
[2]: https://mercure.rocks
[3]: https://symfony.com/sponsor
