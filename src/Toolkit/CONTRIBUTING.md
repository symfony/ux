# Contributing

Due to its nature, the Symfony UX Toolkit requires a specific setup to develop and test it properly. Please follow the instructions below to set up your development environment.

## Setting up the development environment

First, ensure you have followed the [Symfony UX's Contribution Guide](https://github.com/symfony/ux/blob/3.x/CONTRIBUTING.md) to set up your fork of the main repository, install dependencies, etc.

Then, install the UX Toolkit dependencies:

```shell
# src/Toolkit
composer install
```

## Previewing kits

Currently, kits can only be previewed through the Symfony UX Website. Installation instructions can be found in the [Symfony UX Website's `README.md`](https://github.com/symfony/ux/tree/3.x/ux.symfony.com).

Then, run the following commands from the `ux.symfony.com/` directory:

```shell
# Link local UX packages
php ../link

# Run the local web server
symfony serve -d
```

When the server is running, you can access:

- The UX Toolkit homepage at https://127.0.0.1:9044/toolkit
- The list of available kits at https://127.0.0.1:9044/toolkit#kits
- A dedicated section for each kit, e.g., https://127.0.0.1:9044/toolkit/kits/shadcn for the Shadcn UI Kit

## Kit structure

A kit is composed of several recipes, each providing Twig components, styles, and JavaScript.

1. Each kit is located in the `src/Toolkit/kits/` directory
2. Each kit has its own directory named after the kit, e.g., `shadcn/` for the Shadcn UI Kit
3. Each kit directory contains:
    - An `INSTALL.md` file with installation instructions (used by the UX Website)
    - A `manifest.json` file containing metadata about the kit: its name, description, license, homepage, etc.
    - A folder for each recipe provided by the kit, e.g., `button/` for the Button recipe
4. Each recipe directory contains:
    - A `manifest.json` file containing metadata about the recipe: its type, name, description, files to copy, dependencies, etc.
    - A folder `examples/` containing Twig files, it is used for Toolkit tests and previews on UX website
    - Based on the "files to copy" setting, the kit may contain subdirectories such as:
        - `templates/components/` for Twig components
        - `assets/controllers/` for Stimulus controllers
    - The "files to copy" structure is flexible, but we recommend following the above conventions for consistency across kits and Symfony apps

## Working with kits

After setting up your development environment and the UX Website locally, you can start modifying the kits and testing them.

Adding new recipes or modifying existing ones will be automatically reflected in the UX Website, thanks to the local linking done with the `php link` command.
You can then preview your changes by navigating to the relevant sections in the UX Website.

### Running tests & snapshots

Tests use snapshots and screenshots to check that kits and recipes work as expected. This also helps prevent regressions.

Every `{"preview": true}` example of a recipe `README.md` is rendered in an isolated environment. It is compared with a stored HTML snapshot. It is also screenshotted in a browser, in light and dark mode. Each screenshot is compared with a stored screenshot.

To update snapshots and screenshots after a change, run this command from the repository root. Docker must be running:

```shell
# One recipe, one kit (bin/update_toolkit_tests.sh shadcn), or every kit (no argument)
bin/update_toolkit_tests.sh shadcn/popover

# Add the updated files to git
git add src/Toolkit/tests/Functional/__snapshots__ src/Toolkit/kits
```
