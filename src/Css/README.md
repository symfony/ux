# Symfony UX CSS

**EXPERIMENTAL** This bundle is currently experimental and is likely to change, possibly significantly, before its first stable release.

Symfony UX CSS provides a `css()` Twig function in the spirit of [Panda CSS](https://panda-css.com/): it turns a hash of style properties into atomic class names, built from your design tokens and validated when templates compile.

## Installation

```bash
composer require symfony/ux-css
```

## Usage

```twig
<div class="{{ css({ p: 'md', color: 'fg', _hover: { color: 'primary' }, md: { p: 'lg' } }) }}">
```

The attribute becomes `class="p_md c_fg hover:c_primary md:p_lg"`, and the bundle writes the matching rules to `var/ux_css/styles.css`.

## Resources

- [Documentation](doc/index.rst)
- [Report issues](https://github.com/symfony/ux/issues) and [send Pull Requests](https://github.com/symfony/ux/pulls) in the [main Symfony UX repository](https://github.com/symfony/ux)
