# Toast

A succinct message that is displayed temporarily.

```twig {"preview":true}
<div style="min-height: 240px">
    <twig:Toaster>
        <twig:Button
            variant="outline"
            data-action="click->toast#add"
            data-toast-title-param="Event created"
            data-toast-description-param="Sunday, December 3 at 9:00 AM"
            data-toast-action-label-param="Undo"
        >
            Show Toast
        </twig:Button>
    </twig:Toaster>
</div>
```

## Installation

::: installation

## Usage

Render `Toaster` once, where the toasts should appear — usually in your base layout — then fire toasts
from any element with a `toast#add` action, configured with `data-toast-<name>-param` attributes:
`type`, `title`, `description`, `duration`, `id` and `actionLabel`.

```twig
<twig:Toaster duration="5000" limit="3">
    <twig:Button
        data-action="click->toast#add"
        data-toast-type-param="default | success | info | warning | error | loading"
        data-toast-title-param="Event created"
        data-toast-description-param="Sunday, December 3 at 9:00 AM"
        data-toast-action-label-param="Undo"
    >
        Show Toast
    </twig:Button>
</twig:Toaster>
```

The controller also exports a `toast()` helper, so application code can raise a toast without a trigger
element: `toast.success('Event created')`, `toast.error('Could not create event.')`, or
`toast.promise(request, { loading: 'Creating event…', success: 'Event created.' })`.

## Examples

### Types

Set `type` to render a status icon. The built-in renderer recognizes `success`, `info`, `warning`,
`error` and `loading`.

```twig {"preview":true}
<div style="min-height: 320px">
    <twig:Toaster>
        <div class="flex flex-wrap gap-2">
            <twig:Button
                variant="outline"
                data-action="click->toast#add"
                data-toast-description-param="Event has been created."
            >Default</twig:Button>
            <twig:Button
                variant="outline"
                data-action="click->toast#add"
                data-toast-type-param="success"
                data-toast-description-param="Event has been created."
            >Success</twig:Button>
            <twig:Button
                variant="outline"
                data-action="click->toast#add"
                data-toast-type-param="info"
                data-toast-description-param="Arrive 10 minutes before the event."
            >Info</twig:Button>
            <twig:Button
                variant="outline"
                data-action="click->toast#add"
                data-toast-type-param="warning"
                data-toast-description-param="The event cannot start before 8:00 AM."
            >Warning</twig:Button>
            <twig:Button
                variant="outline"
                data-action="click->toast#add"
                data-toast-type-param="error"
                data-toast-description-param="The event could not be created."
            >Error</twig:Button>
        </div>
    </twig:Toaster>
</div>
```

### With Action

Pass `actionLabel` to render an action button. Clicking it dismisses the toast and emits a
`toast:close` event carrying the toast id.

```twig {"preview":true}
<div style="min-height: 240px">
    <twig:Toaster>
        <twig:Button
            variant="outline"
            data-action="click->toast#add"
            data-toast-title-param="Event created"
            data-toast-description-param="You can undo this action."
            data-toast-action-label-param="Undo"
        >
            Show Toast
        </twig:Button>
    </twig:Toaster>
</div>
```

### Promise

Use the `toast#promise` action to update one toast as an asynchronous task moves through loading,
success and error states.

```twig {"preview":true}
<div style="min-height: 280px">
    <twig:Toaster>
        <div class="flex flex-wrap gap-2">
            <twig:Button
                variant="outline"
                data-action="click->toast#promise"
                data-toast-loading-param="Creating event…"
                data-toast-success-param="Event created."
            >Create Event</twig:Button>
            <twig:Button
                variant="outline"
                data-action="click->toast#promise"
                data-toast-loading-param="Creating event…"
                data-toast-error-param="Could not create event."
                data-toast-reject-param="true"
            >Create Event (Failing)</twig:Button>
        </div>
    </twig:Toaster>
</div>
```

### Server Rendered

Write `Toast` items inside `Toaster` to show them on page load, for instance by looping over
`app.flashes`. Give them `duration="0"` to keep them until they are dismissed.

```twig {"preview":true}
<div style="min-height: 240px">
    <twig:Toaster>
        <twig:Toast type="success" duration="0">
            <twig:Toast:Content>
                <twig:Toast:Icon type="success" />
                <div class="flex min-w-0 flex-1 flex-col gap-1">
                    <twig:Toast:Title>Profile saved</twig:Toast:Title>
                    <twig:Toast:Description>Your changes have been applied.</twig:Toast:Description>
                </div>
                <twig:Toast:Close />
            </twig:Toast:Content>
        </twig:Toast>
        <twig:Toast type="info" duration="0">
            <twig:Toast:Content>
                <twig:Toast:Icon type="info" />
                <div class="flex min-w-0 flex-1 flex-col gap-1">
                    <twig:Toast:Title>Welcome back, Alice!</twig:Toast:Title>
                </div>
                <twig:Toast:Close />
            </twig:Toast:Content>
        </twig:Toast>
    </twig:Toaster>
</div>
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

```twig {"preview":true}
<div style="min-height: 300px">
    <twig:Toaster dir="rtl">
        <twig:Toast type="success" duration="0">
            <twig:Toast:Content>
                <twig:Toast:Icon type="success" />
                <div class="flex min-w-0 flex-1 flex-col gap-1">
                    <twig:Toast:Title>تم إنشاء الحدث</twig:Toast:Title>
                    <twig:Toast:Description>الأحد ٣ ديسمبر الساعة ٩:٠٠ صباحًا</twig:Toast:Description>
                </div>
                <twig:Toast:Close label="إغلاق الإشعار" />
            </twig:Toast:Content>
        </twig:Toast>
    </twig:Toaster>
    <twig:Toaster dir="rtl" class="bottom-36">
        <twig:Toast type="info" duration="0">
            <twig:Toast:Content>
                <twig:Toast:Icon type="info" />
                <div class="flex min-w-0 flex-1 flex-col gap-1">
                    <twig:Toast:Title>האירוע נוצר</twig:Toast:Title>
                    <twig:Toast:Description>יום ראשון, 3 בדצמבר בשעה 9:00</twig:Toast:Description>
                </div>
                <twig:Toast:Close label="סגור התראה" />
            </twig:Toast:Content>
        </twig:Toast>
    </twig:Toaster>
</div>
```

## Accessibility

- `Toaster` renders the toast region as a fixed container whose items announce themselves individually,
  so a toast is read out without stealing focus from whatever the user is doing.
- A `Toast` renders `role="status"` with `aria-live="polite"`, or `role="alert"` with
  `aria-live="assertive"` when `type` is `error`, so a failure interrupts while ordinary confirmations
  wait their turn. Each toast is `aria-atomic="true"`, so its title and description are announced
  together rather than piecemeal.
- The status icon is `aria-hidden="true"`: it repeats what the text already says. Never rely on it alone
  to carry the meaning of a toast — write the type into the copy.
- `Toast:Close` carries a translatable `label` prop, rendered as its `aria-label`. Translate it when your
  interface is not in English.
- Auto-dismiss timers pause while the region is hovered **or** holds keyboard focus, so a toast cannot
  disappear from under someone tabbing into its action button.
- Give `duration` enough time to read the toast, and do not put the only copy of important information in
  one. A toast disappears and cannot be recalled — an action inside it is reachable by tabbing, but only
  while it is on screen, so keep the same action available elsewhere in the page.

## API Reference

::: api-reference
