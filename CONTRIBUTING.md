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
- [Node.js](https://nodejs.org/en/download/package-manager) 22 or higher
- [Corepack](https://github.com/nodejs/corepack)
- [Yarn](https://yarnpkg.com/) 4 or higher

With these tools installed, you can install the project dependencies:

```shell
$ composer install
$ corepack enable && yarn install
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
  - `yarn run build`: build (compile) assets from the package,
  - `yarn run watch`: watch for modifications and rebuild assets from the package,
  - `yarn run test`: run the tests from the package,
  - `yarn run check`: run the formatter, linter, and sort imports, and fails if any modifications 
  - `yarn run check --write`: run the formatter, linter, imports sorting, and write modifications 

Thanks to [Yarn Workspaces](https://yarnpkg.com/features/workspaces), you can also run these commands from the root directory of the project:
  - `yarn run build`: build (compile) assets from **all** packages,
  - `yarn run test`: run the tests from **all** packages,
  - `yarn run check`: run the formatter, linter, and sort imports for **all** packages, and fails if any modifications
  - `yarn run check --write`: run the formatter, linter, imports sorting for **all** packages, and write modifications

### Working on documentation

Symfony UX documentation is written in ReStructuredText (`.rst`) and is located in the `docs/` directory
of each package.

When contributing to the documentation, please make sure to follow the Symfony
[documentation guidelines](https://symfony.com/doc/current/contributing/documentation/index.html).

To verify your changes locally, you can use the `oskarstark/doctor-rst` Docker image. Run the following
command from the root directory of the projet:

```shell
docker run --rm -it -e DOCS_DIR='/docs' -v ${PWD}:/docs  oskarstark/doctor-rst -vvv
```

## Useful Git commands

1. To keep your fork up-to-date with the upstream repository and `2.x` branch, you can run the following commands:
```shell
$ git checkout 2.x && \
  git fetch upstream && \
  git rebase upstream/2.x && \
  git push origin 2.x
```

2. To rebase your branch on top of the `2.x` branch, you can run the following commands:
```shell
$ git checkout my-feature-branch && \
  git rebase upstream/2.x && \
  git push -u origin my-feature-branch
```
