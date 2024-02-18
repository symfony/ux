# UPGRADE

## FROM 2.16 to 2.17

-   **Live Components**: Change `defer` attribute to `loading="defer"` #1515.

## FROM 2.15 to 2.16

-   **Live Components**: Change `data-action-name` attribute to `data-live-action-param`
    and change action arguments to be passed as individual `data-live-` attributes #1418.
    See [LiveComponents CHANGELOG](https://github.com/symfony/ux/blob/2.x/src/LiveComponent/CHANGELOG.md#2160)
    for more details.

-   **Live Components**: Remove the `|prevent` modifier and place it on the `data-action`
    instead - e.g. `data-action="live#action:prevent"`.

-   **Live Components**: Change `data-event` attributes to `data-live-event-param` #1418.

## FROM 2.14 to 2.15

-   **Live Components**: Change `data-live-id` attributes to `id` #1484.
