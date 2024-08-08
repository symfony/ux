<p align="center"><a href="https://symfony.com" target="_blank">
    <img src="https://symfony.com/logos/symfony_dynamic_01.svg" alt="Symfony Logo">
</a></p>

<h3 align="center">
    Symfony UX: a JavaScript ecosystem for Symfony
</h3>

Symfony UX is an initiative and set of libraries to seamlessly
integrate JavaScript tools into your application. For example,
want to render a chart with [Chart.js](https://www.chartjs.org/)? Use
[UX Chart.js](https://symfony.com/bundles/ux-chartjs/current/index.html)
to build the chart in PHP. The JavaScript is handled for you automatically.

**That's Symfony UX.**

Symfony UX leverages [Stimulus](https://stimulus.hotwired.dev/) for JavaScript
and can integrate with [AssetMapper](https://symfony.com/doc/current/frontend/asset_mapper.html)
or with [Webpack Encore](https://github.com/symfony/webpack-encore)
(with the help of [Stimulus Bridge](https://github.com/symfony/stimulus-bridge))

## Resources

-   [Install Symfony UX](https://symfony.com/doc/current/frontend/ux.html).
-   [List of UX Packages](https://symfony.com/bundles/StimulusBundle/current/index.html#the-ux-packages).
-   [Symfony UX Official Demo](https://ux.symfony.com).
-   Watch the [Stimulus Screencast on SymfonyCasts](https://symfonycasts.com/screencast/stimulus).

## Let's build an amazing ecosystem together

Symfony UX is an **initiative**: its aim is to build an ecosystem. To achieve this,
we need your help: what other packages could we create in Symfony UX? What about a
library that automatically adds an [input mask](https://imask.js.org/) to the text
fields of your Symfony forms? Anything you do in JavaScript could be done
streamlined as a UX package.

Let's create an amazing ecosystem together!

## Sponsor

The Symfony UX packages are [backed][1] by [Mercure.rocks][2].

Create real-time experiences in minutes! Mercure.rocks provides a realtime API service
that is tightly integrated with Symfony: create UIs that update in live with UX Turbo,
send notifications with the Notifier component, expose async APIs with API Platform and
create low level stuffs with the Mercure component. We maintain and scale the complex
infrastructure for you!

Help Symfony by [sponsoring][3] its development!

## Contributing

If you want to test your code in an existing project that uses Symfony UX packages,
you can use the `link` utility provided in this Git repository (that you have to clone).
This tool scans the `vendor/` directory of your project, finds Symfony UX packages it uses,
and replaces them by symbolic links to the ones in the Git repository.

```shell
# Install required dependencies
$ composer install

# And link Symfony UX packages to your project
$ php link /path/to/your/project
```

[1]: https://symfony.com/backers
[2]: https://mercure.rocks
[3]: https://symfony.com/sponsor
