# CHANGELOG

## 2.1.0

-   Added `data-live-ignore` attribute. If included in an element, that element
    will not be updated on re-render.

-   `ComponentWithFormTrait` no longer has a `setForm()` method. But there
    is also no need to call it anymore. To pass an already-built form to
    your component, pass it as a `form` var to `component()`. If you have
    a custom `mount()`, you no longer need to call `setForm()` or anything else.

-   The Live Component AJAX endpoints now return HTML in all situations
    instead of JSON.

-   Ability to send live action arguments to backend

-   [BC BREAK] Remove `init_live_component()` twig function, use `{{ attributes }}` instead:
    ```diff
    - <div {{ init_live_component() }}>
    + <div {{ attributes }}>
    ```

## 2.0.0

-   Support for `stimulus` version 2 was removed and support for `@hotwired/stimulus`
    version 3 was added. See the [@symfony/stimulus-bridge CHANGELOG](https://github.com/symfony/stimulus-bridge/blob/main/CHANGELOG.md#300)
    for more details.

-   Require live components have a default action (`__invoke()` by default) to enable
    controller annotations/attributes (ie `@Security/@Cache`). Added `DefaultActionTrait`
    helper.

-   When a model is updated, a new `live:update-model` event is dispatched. Parent
    components (in a parent-child component setup) listen to this and automatically
    try to update any model with a matching name. A `data-model-map` was also added
    to map child component model names to a parent - see #113.

-   Child components are now re-rendered if the parent components passes new data
    to the child when rendering - see #113.

-   Minimum PHP version was bumped to 8.0 so that PHP 8 attributes could be used.

-   The `LiveComponentInterface` was dropped and replaced by the `AsLiveComponent` attribute,
    which extends the new `AsTwigComponent` from the TwigComponent library. All
    other annotations (e.g. `@LiveProp` and `@LiveAction`) were also replaced by
    PHP 8 attributes.

Before:

```php
use App\Entity\Notification;
use App\Repository\NotificationRepository;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\LiveComponentInterface;

final class NotificationComponent implements LiveComponentInterface
{
    private NotificationRepository $repo;

    /** @LiveProp */
    public bool $expanded = false;

    public function __construct(NotificationRepository $repo)
    {
        $this->repo = $repo;
    }

    /** @LiveAction */
    public function toggle(): void
    {
        $this->expanded = !$this->expanded;
    }

    public function getNotifications(): array
    {
        return $this->repo->findAll();
    }

    public static function getComponentName(): string
    {
        return 'notification';
    }
}
```

After:

```php
use App\Entity\Notification;
use App\Repository\NotificationRepository;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;

#[AsLiveComponent('notification')]
final class NotificationComponent
{
    private NotificationRepository $repo;

    #[LiveProp]
    public bool $expanded = false;

    public function __construct(NotificationRepository $repo)
    {
        $this->repo = $repo;
    }

    #[LiveAction]
    public function toggle(): void
    {
        $this->expanded = !$this->expanded;
    }

    public function getNotifications(): array
    {
        return $this->repo->findAll();
    }
}
```

## Pre-Release

-   The LiveComponent library was introduced!
