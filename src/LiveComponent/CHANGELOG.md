# CHANGELOG

## 2.7.0

-   [BC BREAK]: The "key" used to load the controller in your `assets/controllers.json`
    file changed from `typed` to `live`. Update your `assets/controllers.json`
    file to change this key.

-   Add a strategy for adding a Stimulus controller to a Twig component - #589.

-   Added a new `getCompontent()` function in JavaScript as the best way to find
    a Component object for a given element.

-   Fixed various bugs related to child component handling - #596

-   Added a new `route` parameter to `AsLiveComponent`, which allows to choose
    another route for Ajax calls.

-   Add `assets/src` to `.gitattributes` to exclude source TypeScript files from
    installing.

-   TypeScript types are now included.

-   Added new `response:error` JavaScript component hook for custom handling Ajax errors - #587.

## 2.6.0

-   [BC BREAK]: The path to `live_component.xml` changed _and_ the import now
    MUST have a `prefix`: you should update your route import accordingly (the
    name of the route also changed to `ux_live_component`):

```diff
# config/routes/ux_live_component.yaml
live_component:
-    resource: '@LiveComponentBundle/Resources/config/routing/live_component.xml'
+    resource: '@LiveComponentBundle/config/routes.php'
+    prefix: /_components
```

-   Removed `Content-Type` header when returning the empty response redirect.
-   Fixed bug when re-rendering SVG's (.#557)

## 2.5.0

-   [BEHAVIOR CHANGE] Previously, Ajax calls could happen in parallel (if
    you changed a model then triggered an action before the model update Ajax
    call finished, the action Ajax call would being in parallel). Now, if
    an Ajax call is currently happening, any future requests will wait until
    it finishes. Then, all queued changes (potentially multiple model updates
    or actions) will be sent all at once on the next request.

-   [BEHAVIOR CHANGE] Fields with `data-model` will now have their `value` set
    automatically when the component initially loads and re-renders. For example,
    previously you needed to manually set the value in your component template:

    ```twig
    <!-- BEFORE -->
    <input data-model="firstName" value="{{ firstName }}">
    ```

    This is no longer necessary: Live Components will now set the value on load,
    which allows you to simply have the following in your template:

    ```twig
    <!-- AFTER -->
    <input data-model="firstName">
    ```

-   [BEHAVIOR CHANGE] The way that child components re-render when a parent re-renders
    has changed, but shouldn't be drastically different. Child components will now
    avoid re-rendering if no "input" to the component changed _and_ will maintain
    any writable `LiveProp` values after the re-render. Also, the re-render happens
    in a separate Ajax call after the parent has finished re-rendering.

-   [BEHAVIOR CHANGE] If a model is updated, but the new value is equal to the old
    one, a re-render will now be avoided.

-   [BEHAVIOR CHANGE] Priority of `DoctrineObjectNormalizer` changed from 100 to -100
    so that any custom normalizers are used before trying `DoctrineObjectNormalizer`.

-   [BC BREAK] The `live:update-model` and `live:render` events are not longer
    dispatched. You can now use the "hook" system directly on the `Component` object/

-   [BC BREAK] The `LiveComponentHydrator::dehydrate()` method now returns a
    `DehydratedComponent` object.

-   Added a new JavaScript `Component` object, which is attached to the `__component`
    property of all root component elements.

-   the ability to add `data-loading` behavior, which is only activated
    when a specific **action** is triggered - e.g. `<span data-loading="action(save)|show">Loading</span>`.

-   Added the ability to add `data-loading` behavior, which is only activated
    when a specific **model** has been updated - e.g. `<span data-loading="model(firstName)|show">Loading</span>`.

-   Unexpected Ajax errors are now displayed in a modal to ease debugging! #467.

-   Fixed bug where sometimes a live component was broken after hitting "Back:
    in your browser - #436.

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
