# UX Icons

Renders local and remote SVG icons in your Twig templates.

```twig
{{ ux_icon('mdi:symfony', {class: 'w-4 h-4'}) }}
{# or #}
<twig:UX:Icon name="mdi:check" class="w-4 h-4" />

{# renders as: #}
<svg viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4"><path fill="currentColor" d="M21 7L9 19l-5.5-5.5l1.41-1.41L9 16.17L19.59 5.59z"/></svg>
```

Want a demo? Check out https://ux.symfony.com/ux-icons.

**This repository is a READ-ONLY sub-tree split**. See
https://github.com/symfony/ux to create issues or submit pull requests.

## Sponsor

The Symfony UX packages are [backed][1] by [Mercure.rocks][2].

Create real-time experiences in minutes! Mercure.rocks provides a realtime API service
that is tightly integrated with Symfony: create UIs that update in live with UX Turbo,
send notifications with the Notifier component, expose async APIs with API Platform and
create low level stuffs with the Mercure component. We maintain and scale the complex
infrastructure for you!

Help Symfony by [sponsoring][3] its development!

## Resources

-   [Documentation](https://symfony.com/bundles/ux-icons/current/index.html)
-   [Report issues](https://github.com/symfony/ux/issues) and
    [send Pull Requests](https://github.com/symfony/ux/pulls)
    in the [main Symfony UX repository](https://github.com/symfony/ux)

[1]: https://symfony.com/backers
[2]: https://mercure.rocks
[3]: https://symfony.com/sponsor
