# Getting started

This kit provides ready-to-use and fully-customizable Twig components for common Symfony needs.

Unlike the other kits, the `common` kit is **design-system agnostic**: its components ship no styling of their own, so they blend into any project regardless of the CSS framework (or design system) you use.

> [!NOTE]
> The demos and examples on the component pages use a few Tailwind CSS classes purely so they look presentable. The components themselves are unstyled — **how they look is entirely up to you**. Style them with your own classes, utilities, or design system; nothing about the kit assumes Tailwind.

## Installation

The components are installed on demand, each one pulling in its own dependencies. For example:

```shell
php bin/console ux:install logout-link --kit common
```

Once installed, the components live in your project (typically under `templates/components/`) and are yours to customize.
