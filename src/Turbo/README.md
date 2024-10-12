# Symfony UX Turbo

Symfony UX Turbo is a Symfony bundle integrating the [Hotwire Turbo](https://turbo.hotwired.dev)
library in Symfony applications. It is part of [the Symfony UX initiative](https://ux.symfony.com/).

Symfony UX Turbo allows having the same user experience as with [Single Page Apps](https://en.wikipedia.org/wiki/Single-page_application)
but without having to write a single line of JavaScript!

Symfony UX Turbo also integrates with [Symfony Mercure](https://symfony.com/doc/current/mercure.html)
or any other transports to broadcast DOM changes to all currently connected users!

You're in a hurry? Take a look at [the chat example](https://symfony.com/bundles/ux-turbo/current/index.html#chat-example)
to discover the full potential of Symfony UX Turbo.

Or watch the [Turbo Screencast on SymfonyCasts](https://symfonycasts.com/screencast/turbo).

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

## Running the Tests

Configure test environment (working directory: `src/Turbo`):

```bash
composer update
docker compose up -d
cd tests/app
php public/index.php doctrine:schema:create
```

Run tests (working directory: `src/Turbo`):

```bash
vendor/bin/simple-phpunit
```

## Resources

-   [Documentation](https://symfony.com/bundles/ux-turbo/current/index.html)
-   [Report issues](https://github.com/symfony/ux/issues) and
    [send Pull Requests](https://github.com/symfony/ux/pulls)
    in the [main Symfony UX repository](https://github.com/symfony/ux)

[1]: https://symfony.com/backers
[2]: https://mercure.rocks
[3]: https://symfony.com/sponsor
