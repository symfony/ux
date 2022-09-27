# CHANGELOG

## 2.5.0

-   [BEHAVIOR CHANGE] Previously, Ajax calls could happen in parallel (if
    you changed a model then triggered an action before the model update Ajax
    call finished, the action Ajax call would being in parallel). Now, if
    an Ajax call is currently happening, any future requests will wait until
    it finishes. Then, all queued changes (potentially multiple model updates
    or actions) will be sent all at once on the next request.

## 2.4.0

-   [BC BREAK] Previously, the `id` attribute was used with `morphdom` as the
    "node id" when updating the DOM after a render. This has changed to
    `data-live-id`. This is useful when maintaining the correct order of a list
    of elements.

-   [BC BREAK] If using `LiveCollectionType`, the name of the remove field changed
    from `button_delete_prototype` to `button_delete` and the add field changed
    from `button_add_prototype` to `button_add`. Additionally, the `allow_add`
    and `allow_delete` default values were changed from `false` to `true`.

-   [BEHAVIOR CHANGE] If an action Ajax call is still processing and a
    model update occurs, the component will _no_ longer re-render. The
    model will be updated internally, but not re-rendered (so, any
    model updates would effectively have the `|norender` modifier). See #419.

## 2.3.0

-   [BC BREAK] The `data-action="live#update"` attribute must now be
    removed from **nearly** all elements. This is because LiveComponents
    now automatically listens to the `input` event on all elements
    with a `data-model` attribute and updates the data. If you previously
    used `data-action="change->live#update"` to list on the `change`
    event, now you should use the `on(change)` modifier inside `data-model`.

```twig
<!-- BEFORE -->
<input
    data-model="max"
    data-action="change->live#update"
>

<!-- AFTER -->
<input
    data-model="on(change)|max"
>
```

-   [BC BREAK] The `live#updateDefer` action was removed entirely.
    Now, to update a model without triggering a re-render, use the
    `norender` modifier for `data-model`:

```twig
<!-- BEFORE -->
<input
    data-model="max"
    data-action="live#updateDefer"
>

<!-- AFTER -->
<input
    data-model="norender|max"
>
```

-   [BC BREAK] The `name` attribute is no longer automatically used to
    update a model when a parent component has `data-action="change->live#update"`.
    To make a form's fields behave like "model" fields (but using the
    `name` attribute instead of `data-model`) you need to add a `data-model`
    attribute to the `<form>` element around your fields (NOTE: the
    new attribute is automatically added to your `form` element when
    using `ComponentWithFormTrait`):

```twig
<!-- BEFORE -->
<form data-action="change->live#update">
    <input
        name="max"
    >
</form>

<!-- AFTER -->
<form data-model="on(change)|*">
    <input
        name="max"
    >
</form>
```

## 2.2.0

-   The bundle now properly exposes a `live` controller, which can be
    imported via your `assets/controllers.json` file (like any other
    UX package). Previously, the controller needed to be imported and
    registered with Stimulus directly (usually in your `assets/bootstrap.js`
    file). That is no longer needed.

-   Add a generic `LiveCollectionType` and `LiveCollectionTrait`
-   Allow to disable CSRF per component

## 2.1.0

-   Your component's live "data" is now send over Ajax as a JSON string.
    Previously data was sent as pure query parameters or as pure POST data.
    However, this made it impossible to keep certain data types, like
    distinguishing between `null` and `''`. This has no impact on end-users.

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

-   [BC BREAK] Replace property hydration system with `symfony/serializer` normalizers. This
    is a BC break if you've created custom hydrators. They'll need to be converted to
    normalizers.

-   [BC BREAK] Rename `BeforeReRender` attribute to `PreReRender`.

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
