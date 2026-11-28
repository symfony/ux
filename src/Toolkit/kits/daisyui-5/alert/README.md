# Alert

Alert informs users about important events.

```twig {"preview":true}
<twig:Alert class="w-full">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="h-6 w-6 shrink-0 stroke-current text-info"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
    <span>12 unread messages. Tap to see.</span>
</twig:Alert>
```

## Installation

::: installation

## Usage

```twig
<twig:Alert
    color="default | info | success | warning | error"
    variant="default | outline | dash | soft"
    direction="default | vertical | horizontal"
>
    <span>12 unread messages. Tap to see.</span>
</twig:Alert>
```

## Examples

### Info Color

```twig {"preview":true}
<twig:Alert color="info" class="w-full">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="h-6 w-6 shrink-0 stroke-current"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
    <span>New software update available.</span>
</twig:Alert>
```

### Success Color

```twig {"preview":true}
<twig:Alert color="success" class="w-full">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="h-6 w-6 shrink-0 stroke-current"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
    <span>Your purchase has been confirmed!</span>
</twig:Alert>
```

### Warning Color

```twig {"preview":true}
<twig:Alert color="warning" class="w-full">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="h-6 w-6 shrink-0 stroke-current"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
    <span>Warning: Invalid email address!</span>
</twig:Alert>
```

### Error Color

```twig {"preview":true}
<twig:Alert color="error" class="w-full">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="h-6 w-6 shrink-0 stroke-current"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
    <span>Error! Task failed successfully.</span>
</twig:Alert>
```

### Soft Style

```twig {"preview":true}
<div class="grid w-full gap-4">
    <twig:Alert color="info" variant="soft">
        <span>12 unread messages. Tap to see.</span>
    </twig:Alert>
    <twig:Alert color="success" variant="soft">
        <span>Your purchase has been confirmed!</span>
    </twig:Alert>
    <twig:Alert color="warning" variant="soft">
        <span>Warning: Invalid email address!</span>
    </twig:Alert>
    <twig:Alert color="error" variant="soft">
        <span>Error! Task failed successfully.</span>
    </twig:Alert>
</div>
```

### Outline Style

```twig {"preview":true}
<div class="grid w-full gap-4">
    <twig:Alert color="info" variant="outline">
        <span>12 unread messages. Tap to see.</span>
    </twig:Alert>
    <twig:Alert color="success" variant="outline">
        <span>Your purchase has been confirmed!</span>
    </twig:Alert>
    <twig:Alert color="warning" variant="outline">
        <span>Warning: Invalid email address!</span>
    </twig:Alert>
    <twig:Alert color="error" variant="outline">
        <span>Error! Task failed successfully.</span>
    </twig:Alert>
</div>
```

### Dash Style

```twig {"preview":true}
<div class="grid w-full gap-4">
    <twig:Alert color="info" variant="dash">
        <span>12 unread messages. Tap to see.</span>
    </twig:Alert>
    <twig:Alert color="success" variant="dash">
        <span>Your purchase has been confirmed!</span>
    </twig:Alert>
    <twig:Alert color="warning" variant="dash">
        <span>Warning: Invalid email address!</span>
    </twig:Alert>
    <twig:Alert color="error" variant="dash">
        <span>Error! Task failed successfully.</span>
    </twig:Alert>
</div>
```

### With Buttons And Responsive

This alert is vertical on small screens and horizontal from the `sm` breakpoint.

```twig {"preview":true}
<twig:Alert direction="vertical" class="w-full sm:alert-horizontal">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="h-6 w-6 shrink-0 stroke-current text-info"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
    <span>we use cookies for no reason.</span>
    <div>
        <twig:Button size="sm">Deny</twig:Button>
        <twig:Button size="sm" color="primary">Accept</twig:Button>
    </div>
</twig:Alert>
```

### With Title And Description

```twig {"preview":true}
<twig:Alert direction="vertical" class="w-full sm:alert-horizontal">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="h-6 w-6 shrink-0 stroke-current text-info"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
    <div>
        <h3 class="font-bold">New message!</h3>
        <div class="text-xs">You have 1 unread message</div>
    </div>
    <twig:Button size="sm">See</twig:Button>
</twig:Alert>
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

```twig {"preview":true}
<div class="flex w-full flex-col gap-8">
    <div class="grid gap-4" dir="rtl">
        <twig:Alert>
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="h-6 w-6 shrink-0 stroke-current text-info"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span>12 رسالة غير مقروءة. اضغط للعرض.</span>
        </twig:Alert>
        <twig:Alert color="success" variant="soft">
            <span>تم تأكيد عملية الشراء!</span>
        </twig:Alert>
        <twig:Alert direction="vertical" class="sm:alert-horizontal">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="h-6 w-6 shrink-0 stroke-current text-info"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <div>
                <h3 class="font-bold">رسالة جديدة!</h3>
                <div class="text-xs">لديك رسالة واحدة غير مقروءة</div>
            </div>
            <twig:Button size="sm">عرض</twig:Button>
        </twig:Alert>
    </div>
    <div class="grid gap-4" dir="rtl">
        <twig:Alert>
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="h-6 w-6 shrink-0 stroke-current text-info"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span>12 הודעות שלא נקראו. הקש לצפייה.</span>
        </twig:Alert>
        <twig:Alert color="success" variant="soft">
            <span>הרכישה שלך אושרה!</span>
        </twig:Alert>
        <twig:Alert direction="vertical" class="sm:alert-horizontal">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="h-6 w-6 shrink-0 stroke-current text-info"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <div>
                <h3 class="font-bold">הודעה חדשה!</h3>
                <div class="text-xs">יש לך הודעה אחת שלא נקראה</div>
            </div>
            <twig:Button size="sm">צפה</twig:Button>
        </twig:Alert>
    </div>
</div>
```

## API Reference

::: api-reference
