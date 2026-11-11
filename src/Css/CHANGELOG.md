# CHANGELOG

## 3.8

- Allow a component caller to override the classes of `css()` per property, e.g. `<twig:Button class="{{ css({ px: '5' }) }}">` replaces the `px` of the component (requires `symfony/ux-twig-component` 3.8+)
- [BC BREAK] Make `css()` return a `Symfony\UX\Css\CssClassList` instead of a string; use `css(...) ~ ''` to get the string
- [BC BREAK] As a consequence, `html_classes()` refuses the result of `css()`, the `variants` of `html_cva()` silently drop it, a `css()` call is always truthy and fails `in`, `starts with`, `ends with` and `is same as` tests (use `css(...) ~ ''` first), and a sandboxed template must allow the `__toString` method of `CssClassList`
- Add the `cva()` function, to write the variants of a component with style hashes

## 3.7.0

- Add the component
