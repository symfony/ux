# Spinner

An indicator that can be used to show a loading state.

```twig {"preview":true}
<div class="flex w-full max-w-xs flex-col gap-4 [--radius:1rem]">
    <twig:Item variant="muted">
        <twig:Item:Media>
            <twig:Spinner />
        </twig:Item:Media>
        <twig:Item:Content>
            <twig:Item:Title class="line-clamp-1">Processing payment...</twig:Item:Title>
        </twig:Item:Content>
        <twig:Item:Content class="flex-none justify-end">
            <span class="text-sm tabular-nums">$100.00</span>
        </twig:Item:Content>
    </twig:Item>
</div>
```

## Installation

::: installation

## Usage

```twig
<twig:Button disabled>
    <twig:Spinner /> Please wait
</twig:Button>
```

## Examples

### Size

Use the `size-*` utility class to change the size of the spinner.

```twig {"preview":true}
<div class="flex items-center gap-6">
    <twig:Spinner class="size-3" />
    <twig:Spinner class="size-4" />
    <twig:Spinner class="size-6" />
    <twig:Spinner class="size-8" />
</div>
```

### Button

Add a spinner to a button to indicate a loading state. Place the `Spinner` before the label with `data-icon="inline-start"` for a start position, or after the label with `data-icon="inline-end"` for an end position.

```twig {"preview":true}
<div class="flex flex-col items-center gap-4">
    <twig:Button disabled size="sm">
        <twig:Spinner data-icon="inline-start" />
        Loading...
    </twig:Button>
    <twig:Button variant="outline" disabled size="sm">
        <twig:Spinner data-icon="inline-start" />
        Please wait
    </twig:Button>
    <twig:Button variant="secondary" disabled size="sm">
        <twig:Spinner data-icon="inline-start" />
        Processing
    </twig:Button>
</div>
```

### Badge

Add a spinner to a badge to indicate a loading state. Place the `Spinner` before the label with `data-icon="inline-start"` for a start position, or after the label with `data-icon="inline-end"` for an end position.

```twig {"preview":true}
<div class="flex items-center gap-4 [--radius:1.2rem]">
    <twig:Badge>
        <twig:Spinner data-icon="inline-start" />
        Syncing
    </twig:Badge>
    <twig:Badge variant="secondary">
        <twig:Spinner data-icon="inline-start" />
        Updating
    </twig:Badge>
    <twig:Badge variant="outline">
        <twig:Spinner data-icon="inline-start" />
        Processing
    </twig:Badge>
</div>
```

### Input Group

```twig {"preview":true}
<div class="flex w-full max-w-md flex-col gap-4">
    <twig:InputGroup>
        <twig:InputGroup:Input placeholder="Send a message..." disabled />
        <twig:InputGroup:Addon align="inline-end">
            <twig:Spinner />
        </twig:InputGroup:Addon>
    </twig:InputGroup>
    <twig:InputGroup>
        <twig:InputGroup:Textarea placeholder="Send a message..." disabled />
        <twig:InputGroup:Addon align="block-end">
            <twig:Spinner /> Validating...
            <twig:InputGroup:Button class="ml-auto" variant="default">
                <twig:ux:icon name="lucide:arrow-up" />
                <span class="sr-only">Send</span>
            </twig:InputGroup:Button>
        </twig:InputGroup:Addon>
    </twig:InputGroup>
</div>
```

### Empty

```twig {"preview":true}
<twig:Empty class="w-full">
    <twig:Empty:Header>
        <twig:Empty:Media variant="icon">
            <twig:Spinner />
        </twig:Empty:Media>
        <twig:Empty:Title>Processing your request</twig:Empty:Title>
        <twig:Empty:Description>Please wait while we process your request. Do not refresh the page.</twig:Empty:Description>
    </twig:Empty:Header>
    <twig:Empty:Content>
        <twig:Button variant="outline" size="sm">Cancel</twig:Button>
    </twig:Empty:Content>
</twig:Empty>
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

```twig {"preview":true}
<div class="flex flex-col gap-8">
    {# Arabic #}
    <div class="flex w-full max-w-xs flex-col gap-4 [--radius:1rem]" dir="rtl">
        <twig:Item variant="muted" dir="rtl">
            <twig:Item:Media>
                <twig:Spinner />
            </twig:Item:Media>
            <twig:Item:Content>
                <twig:Item:Title class="line-clamp-1">جاري معالجة الدفع...</twig:Item:Title>
            </twig:Item:Content>
            <twig:Item:Content class="flex-none justify-end">
                <span class="text-sm tabular-nums">١٠٠.٠٠ دولار</span>
            </twig:Item:Content>
        </twig:Item>
    </div>
    {# Hebrew #}
    <div class="flex w-full max-w-xs flex-col gap-4 [--radius:1rem]" dir="rtl">
        <twig:Item variant="muted" dir="rtl">
            <twig:Item:Media>
                <twig:Spinner />
            </twig:Item:Media>
            <twig:Item:Content>
                <twig:Item:Title class="line-clamp-1">מעבד תשלום...</twig:Item:Title>
            </twig:Item:Content>
            <twig:Item:Content class="flex-none justify-end">
                <span class="text-sm tabular-nums">$100.00</span>
            </twig:Item:Content>
        </twig:Item>
    </div>
</div>
```

## API Reference

::: api-reference
