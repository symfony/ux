# Pagination

Use the Tailwind CSS pagination element to indicate a series of content across various pages based on multiple styles and sizes

```twig {"preview":true}
<twig:Pagination>
    <twig:Pagination:Content>
        <twig:Pagination:Item>
            <twig:Pagination:Previous href="#" />
        </twig:Pagination:Item>
        <twig:Pagination:Item>
            <twig:Pagination:Link href="#">1</twig:Pagination:Link>
        </twig:Pagination:Item>
        <twig:Pagination:Item>
            <twig:Pagination:Link href="#" active>2</twig:Pagination:Link>
        </twig:Pagination:Item>
        <twig:Pagination:Item>
            <twig:Pagination:Link href="#">3</twig:Pagination:Link>
        </twig:Pagination:Item>
        <twig:Pagination:Item>
            <twig:Pagination:Next href="#" />
        </twig:Pagination:Item>
    </twig:Pagination:Content>
</twig:Pagination>
```

## Installation

::: installation

## Usage

```twig
<twig:Pagination>
    <twig:Pagination:Content>
        <twig:Pagination:Item>
            <twig:Pagination:Previous href="#" />
        </twig:Pagination:Item>
        <twig:Pagination:Item>
            <twig:Pagination:Link href="#">1</twig:Pagination:Link>
        </twig:Pagination:Item>
        <twig:Pagination:Item>
            <twig:Pagination:Link href="#" active>2</twig:Pagination:Link>
        </twig:Pagination:Item>
        <twig:Pagination:Item>
            <twig:Pagination:Link href="#">3</twig:Pagination:Link>
        </twig:Pagination:Item>
        <twig:Pagination:Item>
            <twig:Pagination:Next href="#" />
        </twig:Pagination:Item>
    </twig:Pagination:Content>
</twig:Pagination>
```

## Examples

### As icon

The following pagination component example shows how you can use [SVG icons](https://ux.symfony.com/icons) instead of text to show the previous and next pages.

```twig {"preview":true}
<twig:Pagination>
    <twig:Pagination:Content>
        <twig:Pagination:Item>
            <twig:Pagination:Previous asIcon href="#" />
        </twig:Pagination:Item>
        <twig:Pagination:Item>
            <twig:Pagination:Link href="#">1</twig:Pagination:Link>
        </twig:Pagination:Item>
        <twig:Pagination:Item>
            <twig:Pagination:Link href="#" active>2</twig:Pagination:Link>
        </twig:Pagination:Item>
        <twig:Pagination:Item>
            <twig:Pagination:Link href="#">3</twig:Pagination:Link>
        </twig:Pagination:Item>
        <twig:Pagination:Item>
            <twig:Pagination:Next asIcon href="#" />
        </twig:Pagination:Item>
    </twig:Pagination:Content>
</twig:Pagination>
```

## Server Rendered

The examples above render static links. To paginate real data, pair the components with [Symfony UX Pagination](https://symfony.com/bundles/ux-pagination/current/index.html): it reads the current page from the request, fetches the matching slice of your query and generates the page URLs, while an application theme built with the `Pagination` components renders the navigation.

Install the bundle:

```bash
composer require symfony/ux-pagination
```

Paginate a query in your controller:

```php
// src/Controller/ProductController.php
use Symfony\UX\Pagination\PaginatorInterface;

#[Route('/products', name: 'product_index')]
public function index(ProductRepository $products, PaginatorInterface $paginator): Response
{
    $pagination = $paginator
        ->query($products->createQueryBuilder('product')->orderBy('product.name'))
        ->perPage(20)
        ->paginate();

    return $this->render('product/index.html.twig', [
        'products' => $pagination,
    ]);
}
```

Create a theme that renders the pagination result with the `Pagination` components:

```twig
{# templates/pagination/flowbite-4.html.twig #}
{% if pagination.hasPrevious() or pagination.hasNext() %}
    <twig:Pagination aria-label="{{ 'Pagination'|trans({}, 'UXPaginationBundle') }}" {{ ...attributes }}>
        <twig:Pagination:Content {{ ...navigation_attributes }}>
            <twig:Pagination:Item>
                {% if pagination.hasPrevious() %}
                    <twig:Pagination:Previous href="{{ pagination.previousUrl }}" rel="prev" {{ ...link_attributes.previous }} />
                {% else %}
                    <twig:Button as="span" variant="secondary" aria-disabled="true" class="h-10 cursor-not-allowed opacity-50">Previous</twig:Button>
                {% endif %}
            </twig:Pagination:Item>
            {% for link in numbered ? pagination.pages : [] %}
                <twig:Pagination:Item>
                    {% if link.isGap %}
                        <twig:Button as="span" variant="secondary" size="icon" aria-hidden="true">&hellip;</twig:Button>
                    {% elseif link.isCurrent %}
                        <twig:Pagination:Link href="{{ link.url }}" active>{{ link.page }}</twig:Pagination:Link>
                    {% else %}
                        <twig:Pagination:Link
                            href="{{ link.url }}"
                            aria-label="{{ 'Go to page %page%'|trans({'%page%': link.page}, 'UXPaginationBundle') }}"
                            {{ ...link_attributes.pages[link.page]|default({}) }}
                        >{{ link.page }}</twig:Pagination:Link>
                    {% endif %}
                </twig:Pagination:Item>
            {% endfor %}
            <twig:Pagination:Item>
                {% if pagination.hasNext() %}
                    <twig:Pagination:Next href="{{ pagination.nextUrl }}" rel="next" {{ ...link_attributes.next }} />
                {% else %}
                    <twig:Button as="span" variant="secondary" aria-disabled="true" class="h-10 cursor-not-allowed opacity-50">Next</twig:Button>
                {% endif %}
            </twig:Pagination:Item>
        </twig:Pagination:Content>
    </twig:Pagination>
{% endif %}
```

Select it as the default theme:

```yaml
# config/packages/ux_pagination.yaml
ux_pagination:
    theme: 'pagination/flowbite-4.html.twig'
```

Then render the items and the navigation:

```twig
{# templates/product/index.html.twig #}
{% for product in products %}
    <article>{{ product.name }}</article>
{% endfor %}

{{ ux_pagination(products) }}
```

The theme receives the pagination result as `pagination`. Numbered results expose their pages through `pagination.pages`, where each entry is a page link (`page`, `url`, `isCurrent`) or a gap (`isGap`). Lookahead and cursor results set `numbered` to `false`, so the same theme renders only their previous and next links.

The `attributes`, `navigation_attributes` and `link_attributes` options of `ux_pagination()` are spread onto `Pagination`, `Pagination:Content` and the links, which keeps the [LiveComponent integration](https://symfony.com/bundles/ux-pagination/current/live-component.html) working. Unavailable previous and next controls and gaps render as a non-interactive `Button`, so they keep the look of the other items without being focusable.

## API Reference

::: api-reference
