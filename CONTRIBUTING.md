# Contributing

Thank you for considering contributing to Symfony UX!

Symfony UX is an open source, community-driven project, and we are happy to receive contributions from the community!

> [!TIP]
> It's a good idea to read the [Symfony's Contribution Guide](https://symfony.com/doc/current/contributing/index.html) first, even if not all of it applies to Symfony UX and should be adapted to this project (e.g.: Symfony UX has only one base branch, `2.x`).

## Reporting an issue

If you either find a bug, have a feature request, or need help/have a question, please [open an issue](https://github.com/symfony/ux/issues/new/choose).

Please provide as much information as possible,
and remember to follow our [Code of Conduct](https://symfony.com/doc/current/contributing/code_of_conduct/index.html)
as well, to ensure a friendly environment for all contributors.

## Contributing to the code and documentation

Thanks for your interest in contributing to Symfony UX! Here are some guidelines to help you get started.

### Forking the repository

To contribute to Symfony UX, you need to [fork the **symfony/ux** repository](https://github.com/symfony/ux/fork) on GitHub.
This will give you a copy of the code under your GitHub user account, read [the documentation "How to fork a repository"](https://docs.github.com/en/pull-requests/collaborating-with-pull-requests/working-with-forks/fork-a-repo).

After forking the repository, you can clone it to your local machine:

```shell
$ git clone git@github.com:<USERNAME>/symfony-ux.git symfony-ux
$ cd symfony-ux
# Add the upstream repository, to keep your fork up-to-date
$ git remote add upstream git@github.com:symfony/ux.git
```

### Setting up the development environment

To set up the development environment, you need the following tools:

- [PHP](https://www.php.net/downloads.php) 8.1 or higher
- [Composer](https://getcomposer.org/download/)
- [Node.js](https://nodejs.org/en/download/package-manager) 22.18 or higher
- [Corepack](https://github.com/nodejs/corepack)
- [PNPM](https://pnpm.io/) 10.13 or higher

With these tools installed, you can install the project dependencies:

```shell
$ composer install
$ corepack enable && pnpm install
```

### Linking Symfony UX packages to your project

If you want to test your code in an existing project that uses Symfony UX packages,
you can use the `link` utility provided in this Git repository (that you have to clone).

This tool scans the `vendor/` directory of your project, finds Symfony UX packages it uses,
and replaces them by symbolic links to the ones in the Git repository.

```shell
$ php link /path/to/your/project
```

### Working with PHP code

Symfony UX follows Symfony [PHP coding standards](https://symfony.com/doc/current/contributing/code/standards.html)
and [the Backward Compatibility Promise](https://symfony.com/doc/current/contributing/code/bc.html).

When contributing, please make sure to follow these standards and to write tests for your code,
runnable with `php vendor/bin/simple-phpunit`.

### Working with assets

Assets are specific to each Symfony UX package:
  - They are located in the `assets/` directory of each package, and can be either TypeScript or CSS files, respectively compiled through Rollup and PostCSS,
  - Assets are mentioned in the `package.json` file of each package,
  - Assets **must be** compiled before committing changes,
  - Assets **must be** compatible with the [Symfony AssetMapper](https://symfony.com/doc/current/frontend/asset_mapper.html) and [Symfony Webpack Encore](https://symfony.com/doc/current/frontend/encore/index.html).

To help you with assets, you can run the following commands in a specific package directory (e.g., `src/Map/assets/`):
  - `pnpm run build`: build (compile) assets from the package,
  - `pnpm run watch`: watch for modifications and rebuild assets from the package,
  - `pnpm run test`: run the tests from the package,
  - `pnpm run test:unit`: run the Unit tests from the package,
  - `pnpm run test:browser`: run the Browser tests from the package, in a headless browser
  - `pnpm run test:browser:ui`: run the Browser tests from the package in interactive mode, allowing you to see the tests running in a browser window and debug them if needed
  - `pnpm run check`: run the formatter, linter, and sort imports, and fails if any modifications
  - `pnpm run check --write`: run the formatter, linter, imports sorting, and write modifications

Thanks to [PNPM Workspaces](https://pnpm.io/workspaces), you can also run these commands from the root directory of the project:
  - `pnpm run build`: build (compile) assets from **all** packages,
  - `pnpm run test`: run the tests from **all** packages,
  - `pnpm run test:unit`: run the Unit tests from **all** packages,
  - `pnpm run test:browser`: run the Browser tests from **all** packages, in a headless browser
  - `pnpm run check`: run the formatter, linter, and sort imports for **all** packages, and fails if any modifications
  - `pnpm run check --write`: run the formatter, linter, imports sorting for **all** packages, and write modifications

#### Working with Unit tests

We use [Vitest](https://vitest.dev/) for unit testing of the assets,
and tests files are located in the `assets/test/unit/` directory of each UX package,
for example: `src/Vue/assets/test/unit/render_controller.test.ts`.

**Running tests:**
- At the project's root, you can run the following commands:
  - `pnpm run test:unit`: runs the unit tests for **all** UX packages
- Inside the `assets/` directory of each UX package, you can run the following commands:
  - `pnpm run test:unit`: runs the unit tests for the package

> [!IMPORTANT]
> The command `pnpm run test:unit` ensure that each possible combination of dependencies is tested
> (e.g., `"chart.js": "^3.4.1 || ^4.0"` for UX Chartjs).
> Thus it may take some time to run, and it may be not recommended to use watch mode.

#### Working with End-to-End (E2E) tests

> [!NOTE]
> E2E tests simulate real user interactions in a browser, to ensure that the
> UX packages work as expected in a real-world scenario.

Symfony UX use [Playwright](https://playwright.dev/) for browser automation and testing,
and a dedicated Symfony application located in the [`apps/e2e/`](./apps/e2e/) directory,
which contains many examples of Symfony UX packages usage.

Tests files are located in the `assets/test/browser/` directory of each UX package,
for example: `src/Vue/assets/test/browser/vue.test.ts`.

**Setup:**
1. Ensure to have followed the steps in the [Setting up the development environment](#setting-up-the-development-environment) section
2. Read and follow the instructions in the [`apps/e2e/README.md`](./apps/e2e/README.md) file,

**Running tests:**
- At the project's root, you can run the following commands:
  - `pnpm run test:browser`: runs the browser tests for **all** UX packages, using a headless browser
- Inside the `assets/` directory of each UX package, you can run the following commands:
  - `pnpm run test:browser`: runs browser tests for the package, using a headless browser
  - `pnpm run test:browser:ui`: runs the browser tests in interactive mode, allowing you to see the tests running in a browser window and debug them if needed

> [!IMPORTANT]
> Due to their nature, E2E tests may be slower to run than unit tests.
> During the development, we recommend to run `pnpm run test:browser` or `pnpm run test:browser:ui` for a specific UX package.

### Working on documentation

Symfony UX documentation is written in ReStructuredText (`.rst`) and is located in the `docs/` directory
of each package.

When contributing to the documentation, please make sure to follow the Symfony
[documentation guidelines](https://symfony.com/doc/current/contributing/documentation/index.html).

To verify your changes locally, you can use the `oskarstark/doctor-rst` Docker image. Run the following
command from the root directory of the project:

```shell
docker run --rm -it -e DOCS_DIR='/docs' -v ${PWD}:/docs  oskarstark/doctor-rst -vvv
```

## Useful Git commands

1. To keep your fork up-to-date with the upstream repository and `2.x` branch, you can run the following commands:
```shell
$ git checkout 2.x && \
  git fetch upstream && \
  git reset --hard upstream/2.x && \
  git push origin 2.x
```

2. To rebase your branch on top of the `2.x` branch, you can run the following commands:
```shell
$ git checkout my-feature-branch && \
  git rebase upstream/2.x && \
  git push -u origin my-feature-branch
```
