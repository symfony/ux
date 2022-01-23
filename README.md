<p align="center"><a href="https://symfony.com" target="_blank">
    <img src="https://symfony.com/logos/symfony_black_02.svg">
</a></p>

<h3 align="center">
    Symfony UX: a new JavaScript ecosystem for Symfony
</h3>

Symfony UX is an initiative and set of libraries to seamlessly
integrate JavaScript tools into your application. For example,
want to render a chart with [Chart.js](https://www.chartjs.org/)? Use
[UX Chart.js](https://symfony.com/bundles/ux-chartjs/current/index.html)
to build the chart in PHP. The JavaScript is handled for you automatically.

**That's Symfony UX.**

Symfony UX leverages [Stimulus](https://stimulus.hotwired.dev/) for JavaScript
and the [Stimulus Bridge](https://github.com/symfony/stimulus-bridge) for
integrating it into [Webpack Encore](https://github.com/symfony/webpack-encore).

## Resources

-   [Install Symfony UX](https://symfony.com/doc/current/frontend/ux.html).
-   [List of UX Packages](https://symfony.com/doc/current/frontend/ux.html#ux-packages-list).
-   Watch the [Stimulus Screencast on SymfonyCasts](https://symfonycasts.com/screencast/stimulus).

## Let's build an amazing ecosystem together

Symfony UX is an **initiative**: its aim is to build an ecosystem. To achieve this,
we need your help: what other packages could we create in Symfony UX? What about a
library that automatically adds an [input mask](https://imask.js.org/) to the text
fields of your Symfony forms? Or the ability to make the `EntityType` render with
[AJAX auto-completion](https://tarekraafat.github.io/autoComplete.js)? Anything you
do in JavaScript could be done streamlined as a UX package.

We have some ideas, and we will release more packages in the coming days. The rest
is on you: let's create an amazing ecosystem together!

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
