# Sortable

A Stimulus behavior that reorders a list with drag-and-drop and, optionally, saves the new position of each moved item to your server. Powered by [SortableJS](https://sortablejs.github.io/Sortable/). Put it on any container, like a list, a table body or a grid, and its children become draggable.

```twig {"preview":true,"collapseClass":true}
<ul data-controller="sortable" class="flex w-72 flex-col gap-2">
    <li class="cursor-grab rounded-md border border-violet-200 bg-violet-50 px-4 py-2 text-sm text-violet-900 dark:border-violet-900 dark:bg-violet-950 dark:text-violet-100">Pet the cat</li>
    <li class="cursor-grab rounded-md border border-violet-200 bg-violet-50 px-4 py-2 text-sm text-violet-900 dark:border-violet-900 dark:bg-violet-950 dark:text-violet-100">Get the groceries</li>
    <li class="cursor-grab rounded-md border border-violet-200 bg-violet-50 px-4 py-2 text-sm text-violet-900 dark:border-violet-900 dark:bg-violet-950 dark:text-violet-100">Water the plants</li>
    <li class="cursor-grab rounded-md border border-violet-200 bg-violet-50 px-4 py-2 text-sm text-violet-900 dark:border-violet-900 dark:bg-violet-950 dark:text-violet-100">Call the plumber</li>
</ul>
```

## Installation

::: installation

## Usage

Add `data-controller="sortable"` to the element whose children you want to reorder:

```twig
<ul data-controller="sortable">
    <li>Pet the cat</li>
    <li>Get the groceries</li>
</ul>
```

The other items slide out of the way as you drag. Set `animation` to change how long this takes, in milliseconds (`150` by default). Set it to `0` to disable the animation.

## Examples

### Drag Handle

Set `handle` to a CSS selector to drag the items by a handle only. The rest of each item stays free, so you can select its text or click its links:

```twig {"preview":true,"collapseClass":true}
<ul data-controller="sortable" data-sortable-handle-value="[data-handle]" class="flex w-72 flex-col gap-2">
    <li class="flex items-center gap-3 rounded-md border border-violet-200 bg-violet-50 px-3 py-2 text-sm text-violet-900 dark:border-violet-900 dark:bg-violet-950 dark:text-violet-100">
        <span data-handle class="cursor-grab text-violet-400 hover:text-violet-600 dark:text-violet-500 dark:hover:text-violet-300"><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" class="size-4"><circle cx="9" cy="5" r="1.5"/><circle cx="15" cy="5" r="1.5"/><circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/><circle cx="9" cy="19" r="1.5"/><circle cx="15" cy="19" r="1.5"/></svg></span>
        Pet the cat
    </li>
    <li class="flex items-center gap-3 rounded-md border border-violet-200 bg-violet-50 px-3 py-2 text-sm text-violet-900 dark:border-violet-900 dark:bg-violet-950 dark:text-violet-100">
        <span data-handle class="cursor-grab text-violet-400 hover:text-violet-600 dark:text-violet-500 dark:hover:text-violet-300"><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" class="size-4"><circle cx="9" cy="5" r="1.5"/><circle cx="15" cy="5" r="1.5"/><circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/><circle cx="9" cy="19" r="1.5"/><circle cx="15" cy="19" r="1.5"/></svg></span>
        Get the groceries
    </li>
    <li class="flex items-center gap-3 rounded-md border border-violet-200 bg-violet-50 px-3 py-2 text-sm text-violet-900 dark:border-violet-900 dark:bg-violet-950 dark:text-violet-100">
        <span data-handle class="cursor-grab text-violet-400 hover:text-violet-600 dark:text-violet-500 dark:hover:text-violet-300"><svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" class="size-4"><circle cx="9" cy="5" r="1.5"/><circle cx="15" cy="5" r="1.5"/><circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/><circle cx="9" cy="19" r="1.5"/><circle cx="15" cy="19" r="1.5"/></svg></span>
        Water the plants
    </li>
</ul>
```

### Fixed Items

Set `draggable` to a CSS selector to choose which items can be dragged. By default, these are the `li` children of a list, or all the children of any other element. The other items stay where they are, like the slot to add a photo at the end of this grid. Start the selector with `>` to match the children of the controller element only, and not the elements inside them. When you [save the new position](#content-save-the-new-position), only the draggable items are counted.

```twig {"preview":true,"collapseClass":true}
<div data-controller="sortable" data-sortable-draggable-value=">:not([data-upload])" class="grid w-72 grid-cols-3 gap-2">
    <div class="flex aspect-square cursor-grab items-center justify-center rounded-md bg-violet-100 text-lg font-semibold text-violet-900 dark:bg-violet-900 dark:text-violet-100">A</div>
    <div class="flex aspect-square cursor-grab items-center justify-center rounded-md bg-violet-100 text-lg font-semibold text-violet-900 dark:bg-violet-900 dark:text-violet-100">B</div>
    <div class="flex aspect-square cursor-grab items-center justify-center rounded-md bg-violet-100 text-lg font-semibold text-violet-900 dark:bg-violet-900 dark:text-violet-100">C</div>
    <div class="flex aspect-square cursor-grab items-center justify-center rounded-md bg-violet-100 text-lg font-semibold text-violet-900 dark:bg-violet-900 dark:text-violet-100">D</div>
    <div class="flex aspect-square cursor-grab items-center justify-center rounded-md bg-violet-100 text-lg font-semibold text-violet-900 dark:bg-violet-900 dark:text-violet-100">E</div>
    <div data-upload class="relative flex aspect-square flex-col items-center justify-center gap-1 rounded-md border-2 border-dashed border-violet-300 text-xs font-medium text-violet-600 hover:border-violet-500 dark:border-violet-800 dark:text-violet-300 dark:hover:border-violet-600">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true" class="size-5"><path d="M12 5v14M5 12h14"/></svg>
        Add a photo
        <input type="file" accept="image/*" aria-label="Add a photo" class="absolute inset-0 cursor-pointer opacity-0">
    </div>
</div>
```

### Ignored Elements

Set `filter` to a CSS selector of the elements that do not start a drag, like the buttons inside the items. Pressing one of them does not pick up its item.

```twig {"preview":true,"collapseClass":true}
<ul data-controller="sortable" data-sortable-filter-value="button" class="flex w-72 flex-col gap-2">
    <li class="flex cursor-grab items-center justify-between rounded-md border border-violet-200 bg-violet-50 px-4 py-2 text-sm text-violet-900 dark:border-violet-900 dark:bg-violet-950 dark:text-violet-100">
        Pet the cat
        <button type="button" class="cursor-pointer rounded px-1.5 py-0.5 text-xs font-medium text-violet-700 hover:bg-violet-100 dark:text-violet-200 dark:hover:bg-violet-900">Edit</button>
    </li>
    <li class="flex cursor-grab items-center justify-between rounded-md border border-violet-200 bg-violet-50 px-4 py-2 text-sm text-violet-900 dark:border-violet-900 dark:bg-violet-950 dark:text-violet-100">
        Get the groceries
        <button type="button" class="cursor-pointer rounded px-1.5 py-0.5 text-xs font-medium text-violet-700 hover:bg-violet-100 dark:text-violet-200 dark:hover:bg-violet-900">Edit</button>
    </li>
    <li class="flex cursor-grab items-center justify-between rounded-md border border-violet-200 bg-violet-50 px-4 py-2 text-sm text-violet-900 dark:border-violet-900 dark:bg-violet-950 dark:text-violet-100">
        Water the plants
        <button type="button" class="cursor-pointer rounded px-1.5 py-0.5 text-xs font-medium text-violet-700 hover:bg-violet-100 dark:text-violet-200 dark:hover:bg-violet-900">Edit</button>
    </li>
</ul>
```

### Grid

The items do not have to be stacked. In a grid, they move along both axes.

```twig {"preview":true,"collapseClass":true}
<div data-controller="sortable" class="grid w-60 grid-cols-3 gap-2">
    <div class="flex aspect-square cursor-grab items-center justify-center rounded-md bg-violet-100 text-lg font-semibold text-violet-900 dark:bg-violet-900 dark:text-violet-100">A</div>
    <div class="flex aspect-square cursor-grab items-center justify-center rounded-md bg-violet-100 text-lg font-semibold text-violet-900 dark:bg-violet-900 dark:text-violet-100">B</div>
    <div class="flex aspect-square cursor-grab items-center justify-center rounded-md bg-violet-100 text-lg font-semibold text-violet-900 dark:bg-violet-900 dark:text-violet-100">C</div>
    <div class="flex aspect-square cursor-grab items-center justify-center rounded-md bg-violet-100 text-lg font-semibold text-violet-900 dark:bg-violet-900 dark:text-violet-100">D</div>
    <div class="flex aspect-square cursor-grab items-center justify-center rounded-md bg-violet-100 text-lg font-semibold text-violet-900 dark:bg-violet-900 dark:text-violet-100">E</div>
    <div class="flex aspect-square cursor-grab items-center justify-center rounded-md bg-violet-100 text-lg font-semibold text-violet-900 dark:bg-violet-900 dark:text-violet-100">F</div>
</div>
```

### Save the New Position

Give each item a `data-sortable-update-url`. When the item is dropped at a new index, the controller sends a `POST` request to that URL. Its `position` parameter holds the new position of the item, starting at 1. Set `csrfToken` to also send a CSRF token, as a `_token` parameter:

```twig {"preview":true,"collapseClass":true}
<ul data-controller="sortable" data-sortable-csrf-token-value="{{ csrf_token('reorder-tasks') }}" class="flex w-72 flex-col gap-2">
    <li data-sortable-update-url="/link/tasks/1/position" class="cursor-grab rounded-md border border-violet-200 bg-violet-50 px-4 py-2 text-sm text-violet-900 dark:border-violet-900 dark:bg-violet-950 dark:text-violet-100">Write the release notes</li>
    <li data-sortable-update-url="/link/tasks/2/position" class="cursor-grab rounded-md border border-violet-200 bg-violet-50 px-4 py-2 text-sm text-violet-900 dark:border-violet-900 dark:bg-violet-950 dark:text-violet-100">Review the pull requests</li>
    <li data-sortable-update-url="/link/tasks/3/position" class="cursor-grab rounded-md border border-violet-200 bg-violet-50 px-4 py-2 text-sm text-violet-900 dark:border-violet-900 dark:bg-violet-950 dark:text-violet-100">Tag the new version</li>
</ul>
```

In your app, generate each URL with `path()`, and update the position in the matching controller:

```twig
<ul data-controller="sortable" data-sortable-csrf-token-value="{{ csrf_token('reorder-tasks') }}">
    {% for task in tasks %}
        <li data-sortable-update-url="{{ path('app_task_position', {id: task.id}) }}">{{ task.title }}</li>
    {% endfor %}
</ul>
```

```php
#[Route('/tasks/{id}/position', name: 'app_task_position', methods: ['POST'])]
#[IsCsrfTokenValid('reorder-tasks')]
public function position(Task $task, Request $request, EntityManagerInterface $entityManager): Response
{
    $task->setPosition($request->getPayload()->getInt('position'));
    $entityManager->flush();

    return new Response('', Response::HTTP_NO_CONTENT);
}
```

This controller only changes the position of the moved task, so two tasks can end up with the same position. Your app must also shift the positions of the other tasks, or save the whole order with a form, as shown in the next section. The position counts only the items rendered in the list, so on a paginated list you must add the number of items on the previous pages.

Set `paramName` to rename the `position` field. A value like `task[position]` nests the field the way a Symfony form names its fields.

When Turbo is loaded on the page, the request also accepts a Turbo Stream response, and Turbo renders it.

### Submit the Order with a Form

To save the whole order at once instead of each move, put the list in a form. The browser submits the fields in the order they appear on the page, so a hidden field in each item sends the ids in their new order.

```twig {"preview":true,"collapseClass":true}
<form action="/link/tasks/order" method="post" class="flex w-72 flex-col gap-3">
    <input type="hidden" name="_token" value="{{ csrf_token('reorder-tasks') }}">
    <ul data-controller="sortable" class="flex flex-col gap-2">
        <li class="cursor-grab rounded-md border border-violet-200 bg-violet-50 px-4 py-2 text-sm text-violet-900 dark:border-violet-900 dark:bg-violet-950 dark:text-violet-100">
            <input type="hidden" name="tasks[]" value="1">
            Write the release notes
        </li>
        <li class="cursor-grab rounded-md border border-violet-200 bg-violet-50 px-4 py-2 text-sm text-violet-900 dark:border-violet-900 dark:bg-violet-950 dark:text-violet-100">
            <input type="hidden" name="tasks[]" value="2">
            Review the pull requests
        </li>
        <li class="cursor-grab rounded-md border border-violet-200 bg-violet-50 px-4 py-2 text-sm text-violet-900 dark:border-violet-900 dark:bg-violet-950 dark:text-violet-100">
            <input type="hidden" name="tasks[]" value="3">
            Tag the new version
        </li>
    </ul>
    <button type="submit" class="self-start rounded-md bg-violet-600 px-4 py-2 text-sm font-medium text-white hover:bg-violet-500">Save the order</button>
</form>
```

The payload class receives the submitted ids through `#[MapRequestPayload]`, and its constraints reject an empty list, duplicate ids, and ids that are not strings of digits (the ids arrive as strings, because the Serializer does not convert the items of an array).

```php
use Symfony\Component\Validator\Constraints as Assert;

final class TaskOrder
{
    /**
     * @param list<string> $tasks
     */
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Unique]
        #[Assert\All(new Assert\Type('digit'))]
        public array $tasks,
    ) {
    }
}
```

The controller checks the CSRF token, receives the validated ids from first to last, and passes them to the repository.

```php
#[Route('/tasks/order', name: 'app_task_order', methods: ['POST'])]
#[IsCsrfTokenValid('reorder-tasks')]
public function order(#[MapRequestPayload] TaskOrder $order, TaskRepository $taskRepository): Response
{
    $taskRepository->reorder($order->tasks);

    return $this->redirectToRoute('app_task_index');
}
```

## API Reference

::: api-reference
