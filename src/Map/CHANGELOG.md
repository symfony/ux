# CHANGELOG

## 2.20

-   Deprecate `render_map` Twig function (will be removed in 2.21). Use 
    `ux_map` or the `<twig:ux:map />` Twig component instead.
-   Add `ux_map` Twig function (replaces `render_map` with a more flexible 
    interface)
-   Add `<twig:ux:map />` Twig component
-   The importmap entry `@symfony/ux-map/abstract-map-controller` can be removed
    from your importmap, it is no longer needed. 

## 2.19

-   Component added
