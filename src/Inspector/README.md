# Symfony UX Inspector

Inspect Stimulus controllers, LiveComponents, and Turbo elements on the page:
component state, relationships, and activity.

**EXPERIMENTAL**: features and API may change. Use only in `dev`.

## Installation

Requires PHP 8.4+, Symfony 7.4 or 8.x, and Chrome 145+ or Firefox 146+.

```bash
composer require --dev symfony/ux-inspector
```

Follow the [installation steps](doc/index.rst#installation) to register the
bundle and import its route.

## Usage

Click the small pull tab on the right edge of the screen to open the panel.
It expands to show **UX** when the pointer approaches or the tab receives focus.
On touch devices, **UX** is visible without hovering. The tab hides while the
panel is open and returns when you close it.

You can also type `ux` outside an editable field to open the panel.

- **Components**: list and filter components.
- **Inspect page components**: select a component on the page to view its data.
- **Show activity**: trace events and view event details.

## Configuration

The pull tab is visible by default. Hide it while keeping the `ux` shortcut:

```yaml
# config/packages/ux_inspector.yaml
when@dev:
    ux_inspector:
        pull_tab: false
```

See the [configuration reference](doc/index.rst#configuration) for all options.

## Resources

- [Documentation](doc/index.rst)
- [Report issues](https://github.com/symfony/ux/issues) and
  [send Pull Requests](https://github.com/symfony/ux/pulls)
  in the [main Symfony UX repository](https://github.com/symfony/ux)

This repository is a read-only subtree split.
