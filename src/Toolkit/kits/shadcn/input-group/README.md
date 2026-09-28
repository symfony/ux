# Input Group

Add addons, buttons, and helper content to inputs.

```twig {"preview":true}
<twig:InputGroup class="max-w-xs">
    <twig:InputGroup:Input placeholder="Search..." />
    <twig:InputGroup:Addon>
        <twig:ux:icon name="lucide:search" />
    </twig:InputGroup:Addon>
    <twig:InputGroup:Addon align="inline-end">12 results</twig:InputGroup:Addon>
</twig:InputGroup>
```

## Installation

::: installation

## Usage

```twig
<twig:InputGroup>
    <twig:InputGroup:Input placeholder="Search..." />
    <twig:InputGroup:Addon>
        <twig:ux:icon name="lucide:search" />
    </twig:InputGroup:Addon>
</twig:InputGroup>
```

## Examples

### Inline Start

Use `align="inline-start"` to position the addon at the start of the input. This is the default.

```twig {"preview":true}
<twig:Field class="max-w-sm">
    <twig:Field:Label for="inline-start-input">Input</twig:Field:Label>
    <twig:InputGroup>
        <twig:InputGroup:Input id="inline-start-input" placeholder="Search..." />
        <twig:InputGroup:Addon align="inline-start">
            <twig:ux:icon name="lucide:search" class="text-muted-foreground" />
        </twig:InputGroup:Addon>
    </twig:InputGroup>
    <twig:Field:Description>Icon positioned at the start.</twig:Field:Description>
</twig:Field>
```

### Inline End

Use `align="inline-end"` to position the addon at the end of the input.

```twig {"preview":true}
<twig:Field class="max-w-sm">
    <twig:Field:Label for="inline-end-input">Input</twig:Field:Label>
    <twig:InputGroup>
        <twig:InputGroup:Input id="inline-end-input" type="password" placeholder="Enter password" />
        <twig:InputGroup:Addon align="inline-end">
            <twig:ux:icon name="lucide:eye-off" />
        </twig:InputGroup:Addon>
    </twig:InputGroup>
    <twig:Field:Description>Icon positioned at the end.</twig:Field:Description>
</twig:Field>
```

### Block Start

Use `align="block-start"` to position the addon above the input.

```twig {"preview":true}
<twig:Field:Group class="max-w-sm">
    <twig:Field>
        <twig:Field:Label for="block-start-input">Input</twig:Field:Label>
        <twig:InputGroup class="h-auto">
            <twig:InputGroup:Input id="block-start-input" placeholder="Enter your name" />
            <twig:InputGroup:Addon align="block-start">
                <twig:InputGroup:Text>Full Name</twig:InputGroup:Text>
            </twig:InputGroup:Addon>
        </twig:InputGroup>
        <twig:Field:Description>Header positioned above the input.</twig:Field:Description>
    </twig:Field>
    <twig:Field>
        <twig:Field:Label for="block-start-textarea">Textarea</twig:Field:Label>
        <twig:InputGroup>
            <twig:InputGroup:Textarea
                id="block-start-textarea"
                placeholder="console.log('Hello, world!');"
                class="font-mono text-sm"
            />
            <twig:InputGroup:Addon align="block-start">
                <twig:ux:icon name="lucide:file-code" class="text-muted-foreground" />
                <twig:InputGroup:Text class="font-mono">script.js</twig:InputGroup:Text>
                <twig:InputGroup:Button size="icon-xs" class="ms-auto">
                    <twig:ux:icon name="lucide:copy" />
                    <span class="sr-only">Copy</span>
                </twig:InputGroup:Button>
            </twig:InputGroup:Addon>
        </twig:InputGroup>
        <twig:Field:Description>Header positioned above the textarea.</twig:Field:Description>
    </twig:Field>
</twig:Field:Group>
```

### Block End

Use `align="block-end"` to position the addon below the input.

```twig {"preview":true}
<twig:Field:Group class="max-w-sm">
    <twig:Field>
        <twig:Field:Label for="block-end-input">Input</twig:Field:Label>
        <twig:InputGroup class="h-auto">
            <twig:InputGroup:Input id="block-end-input" placeholder="Enter amount" />
            <twig:InputGroup:Addon align="block-end">
                <twig:InputGroup:Text>USD</twig:InputGroup:Text>
            </twig:InputGroup:Addon>
        </twig:InputGroup>
        <twig:Field:Description>Footer positioned below the input.</twig:Field:Description>
    </twig:Field>
    <twig:Field>
        <twig:Field:Label for="block-end-textarea">Textarea</twig:Field:Label>
        <twig:InputGroup>
            <twig:InputGroup:Textarea id="block-end-textarea" placeholder="Write a comment..." />
            <twig:InputGroup:Addon align="block-end">
                <twig:InputGroup:Text>0/280</twig:InputGroup:Text>
                <twig:InputGroup:Button variant="default" size="sm" class="ms-auto">Post</twig:InputGroup:Button>
            </twig:InputGroup:Addon>
        </twig:InputGroup>
        <twig:Field:Description>Footer positioned below the textarea.</twig:Field:Description>
    </twig:Field>
</twig:Field:Group>
```

### Icon

```twig {"preview":true}
<div class="grid w-full max-w-sm gap-6">
    <twig:InputGroup>
        <twig:InputGroup:Input placeholder="Search..." />
        <twig:InputGroup:Addon>
            <twig:ux:icon name="lucide:search" />
        </twig:InputGroup:Addon>
    </twig:InputGroup>

    <twig:InputGroup>
        <twig:InputGroup:Input type="email" placeholder="Enter your email" />
        <twig:InputGroup:Addon>
            <twig:ux:icon name="lucide:mail" />
        </twig:InputGroup:Addon>
    </twig:InputGroup>

    <twig:InputGroup>
        <twig:InputGroup:Input placeholder="Card number" />
        <twig:InputGroup:Addon>
            <twig:ux:icon name="lucide:credit-card" />
        </twig:InputGroup:Addon>
        <twig:InputGroup:Addon align="inline-end">
            <twig:ux:icon name="lucide:check" />
        </twig:InputGroup:Addon>
    </twig:InputGroup>

    <twig:InputGroup>
        <twig:InputGroup:Input placeholder="Card number" />
        <twig:InputGroup:Addon align="inline-end">
            <twig:ux:icon name="lucide:star" />
            <twig:ux:icon name="lucide:info" />
        </twig:InputGroup:Addon>
    </twig:InputGroup>
</div>
```

### Text

```twig {"preview":true}
<div class="grid w-full max-w-sm gap-6">
    <twig:InputGroup>
        <twig:InputGroup:Addon>
            <twig:InputGroup:Text>$</twig:InputGroup:Text>
        </twig:InputGroup:Addon>
        <twig:InputGroup:Input placeholder="0.00" />
        <twig:InputGroup:Addon align="inline-end">
            <twig:InputGroup:Text>USD</twig:InputGroup:Text>
        </twig:InputGroup:Addon>
    </twig:InputGroup>

    <twig:InputGroup>
        <twig:InputGroup:Addon>
            <twig:InputGroup:Text>https://</twig:InputGroup:Text>
        </twig:InputGroup:Addon>
        <twig:InputGroup:Input placeholder="example.com" class="ps-0.5!" />
        <twig:InputGroup:Addon align="inline-end">
            <twig:InputGroup:Text>.com</twig:InputGroup:Text>
        </twig:InputGroup:Addon>
    </twig:InputGroup>

    <twig:InputGroup>
        <twig:InputGroup:Input placeholder="Enter your username" />
        <twig:InputGroup:Addon align="inline-end">
            <twig:InputGroup:Text>@company.com</twig:InputGroup:Text>
        </twig:InputGroup:Addon>
    </twig:InputGroup>

    <twig:InputGroup>
        <twig:InputGroup:Textarea placeholder="Enter your message" />
        <twig:InputGroup:Addon align="block-end">
            <twig:InputGroup:Text class="text-xs text-muted-foreground">120 characters left</twig:InputGroup:Text>
        </twig:InputGroup:Addon>
    </twig:InputGroup>
</div>
```

### Button

```twig {"preview":true}
<div class="grid w-full max-w-sm gap-6" data-controller="input-group-display">
    <twig:InputGroup>
        <twig:InputGroup:Input placeholder="https://x.com/symfony" readonly />
        <twig:InputGroup:Addon align="inline-end">
            <twig:InputGroup:Button
                aria-label="Copy"
                title="Copy"
                size="icon-xs"
                data-action="input-group-display#copy"
                data-input-group-display-text-param="https://x.com/symfony"
            >
                <twig:ux:icon name="tabler:copy" data-input-group-display-target="copyIcon" />
                <twig:ux:icon name="tabler:check" data-input-group-display-target="copiedIcon" hidden />
            </twig:InputGroup:Button>
        </twig:InputGroup:Addon>
    </twig:InputGroup>

    <twig:InputGroup class="[--radius:9999px]">
        <twig:Popover class="order-first">
            <twig:Popover:Trigger>
                <twig:InputGroup:Addon>
                    <twig:InputGroup:Button variant="secondary" size="icon-xs" aria-label="Connection info" {{ ...popover_trigger_attrs }}>
                        <twig:ux:icon name="tabler:info-circle" />
                    </twig:InputGroup:Button>
                </twig:InputGroup:Addon>
            </twig:Popover:Trigger>
            <twig:Popover:Content align="start" class="flex flex-col gap-1 rounded-xl text-sm [--radius:0.625rem]">
                <p class="font-medium">Your connection is not secure.</p>
                <p>You should not enter any sensitive information on this site.</p>
            </twig:Popover:Content>
        </twig:Popover>
        <twig:InputGroup:Addon class="ps-1.5 text-muted-foreground">
            https://
        </twig:InputGroup:Addon>
        <twig:InputGroup:Input id="input-secure-19" />
        <twig:InputGroup:Addon align="inline-end">
            <twig:InputGroup:Button size="icon-xs" aria-label="Favorite" data-action="input-group-display#toggleFavorite">
                <twig:ux:icon
                    name="tabler:star"
                    data-favorite="false"
                    data-input-group-display-target="favoriteIcon"
                    class="data-[favorite=true]:*:fill-blue-600 data-[favorite=true]:*:stroke-blue-600"
                />
            </twig:InputGroup:Button>
        </twig:InputGroup:Addon>
    </twig:InputGroup>

    <twig:InputGroup>
        <twig:InputGroup:Input placeholder="Type to search..." />
        <twig:InputGroup:Addon align="inline-end">
            <twig:InputGroup:Button variant="secondary">Search</twig:InputGroup:Button>
        </twig:InputGroup:Addon>
    </twig:InputGroup>
</div>
```

### Kbd

```twig {"preview":true}
<twig:InputGroup class="max-w-sm">
    <twig:InputGroup:Input placeholder="Search..." />
    <twig:InputGroup:Addon>
        <twig:ux:icon name="lucide:search" class="text-muted-foreground" />
    </twig:InputGroup:Addon>
    <twig:InputGroup:Addon align="inline-end">
        <twig:Kbd>⌘K</twig:Kbd>
    </twig:InputGroup:Addon>
</twig:InputGroup>
```

### Dropdown

```twig {"preview":true}
<div class="grid w-full max-w-sm content-start gap-4" style="min-height: 220px">
    <twig:InputGroup>
        <twig:InputGroup:Input placeholder="Enter file name" />
        <twig:InputGroup:Addon align="inline-end">
            <twig:DropdownMenu id="input-group-file" align="end">
                <twig:DropdownMenu:Trigger>
                    <twig:InputGroup:Button variant="ghost" aria-label="More" size="icon-xs" {{ ...dropdown_menu_trigger_attrs }}>
                        <twig:ux:icon name="lucide:ellipsis" />
                    </twig:InputGroup:Button>
                </twig:DropdownMenu:Trigger>
                <twig:DropdownMenu:Content>
                    <twig:DropdownMenu:Group>
                        <twig:DropdownMenu:Item>Settings</twig:DropdownMenu:Item>
                        <twig:DropdownMenu:Item>Copy path</twig:DropdownMenu:Item>
                        <twig:DropdownMenu:Item>Open location</twig:DropdownMenu:Item>
                    </twig:DropdownMenu:Group>
                </twig:DropdownMenu:Content>
            </twig:DropdownMenu>
        </twig:InputGroup:Addon>
    </twig:InputGroup>

    <twig:InputGroup class="[--radius:1rem]">
        <twig:InputGroup:Input placeholder="Enter search query" />
        <twig:InputGroup:Addon align="inline-end">
            <twig:DropdownMenu id="input-group-search" align="end">
                <twig:DropdownMenu:Trigger>
                    <twig:InputGroup:Button variant="ghost" class="pe-1.5! text-xs" {{ ...dropdown_menu_trigger_attrs }}>
                        Search In... <twig:ux:icon name="lucide:chevron-down" class="size-3" />
                    </twig:InputGroup:Button>
                </twig:DropdownMenu:Trigger>
                <twig:DropdownMenu:Content class="[--radius:0.95rem]">
                    <twig:DropdownMenu:Group>
                        <twig:DropdownMenu:Item>Documentation</twig:DropdownMenu:Item>
                        <twig:DropdownMenu:Item>Blog Posts</twig:DropdownMenu:Item>
                        <twig:DropdownMenu:Item>Changelog</twig:DropdownMenu:Item>
                    </twig:DropdownMenu:Group>
                </twig:DropdownMenu:Content>
            </twig:DropdownMenu>
        </twig:InputGroup:Addon>
    </twig:InputGroup>
</div>
```

### Spinner

```twig {"preview":true}
<div class="grid w-full max-w-sm gap-4">
    <twig:InputGroup>
        <twig:InputGroup:Input placeholder="Searching..." />
        <twig:InputGroup:Addon align="inline-end">
            <twig:Spinner />
        </twig:InputGroup:Addon>
    </twig:InputGroup>

    <twig:InputGroup>
        <twig:InputGroup:Input placeholder="Processing..." />
        <twig:InputGroup:Addon>
            <twig:Spinner />
        </twig:InputGroup:Addon>
    </twig:InputGroup>

    <twig:InputGroup>
        <twig:InputGroup:Input placeholder="Saving changes..." />
        <twig:InputGroup:Addon align="inline-end">
            <twig:InputGroup:Text>Saving...</twig:InputGroup:Text>
            <twig:Spinner />
        </twig:InputGroup:Addon>
    </twig:InputGroup>

    <twig:InputGroup>
        <twig:InputGroup:Input placeholder="Refreshing data..." />
        <twig:InputGroup:Addon>
            <twig:ux:icon name="lucide:loader" class="animate-spin" />
        </twig:InputGroup:Addon>
        <twig:InputGroup:Addon align="inline-end">
            <twig:InputGroup:Text class="text-muted-foreground">Please wait...</twig:InputGroup:Text>
        </twig:InputGroup:Addon>
    </twig:InputGroup>
</div>
```

### Textarea

```twig {"preview":true}
<div class="grid w-full max-w-md gap-4">
    <twig:InputGroup>
        <twig:InputGroup:Textarea
            id="textarea-code-32"
            placeholder="console.log('Hello, world!');"
            class="min-h-[200px]"
        />
        <twig:InputGroup:Addon align="block-end" class="border-t">
            <twig:InputGroup:Text>Line 1, Column 1</twig:InputGroup:Text>
            <twig:InputGroup:Button size="sm" class="ms-auto" variant="default">
                Run <twig:ux:icon name="tabler:corner-down-left" />
            </twig:InputGroup:Button>
        </twig:InputGroup:Addon>
        <twig:InputGroup:Addon align="block-start" class="border-b">
            <twig:InputGroup:Text class="font-mono font-medium">
                <twig:ux:icon name="tabler:brand-javascript" />
                script.js
            </twig:InputGroup:Text>
            <twig:InputGroup:Button class="ms-auto" size="icon-xs" aria-label="Refresh">
                <twig:ux:icon name="tabler:refresh" />
            </twig:InputGroup:Button>
            <twig:InputGroup:Button variant="ghost" size="icon-xs" aria-label="Copy">
                <twig:ux:icon name="tabler:copy" />
            </twig:InputGroup:Button>
        </twig:InputGroup:Addon>
    </twig:InputGroup>
</div>
```

### Custom Input

Add the `data-slot="input-group-control"` attribute to your custom input for automatic focus state handling.

Here's an example of a custom textarea that grows with its content.

```twig {"preview":true}
<div class="grid w-full max-w-sm gap-6">
    <twig:InputGroup>
        <textarea
            data-slot="input-group-control"
            class="flex field-sizing-content min-h-16 w-full resize-none rounded-md bg-transparent px-3 py-2.5 text-base transition-[color,box-shadow] outline-none md:text-sm"
            placeholder="Autoresize textarea..."
        ></textarea>
        <twig:InputGroup:Addon align="block-end">
            <twig:InputGroup:Button class="ms-auto" size="sm" variant="default">Submit</twig:InputGroup:Button>
        </twig:InputGroup:Addon>
    </twig:InputGroup>
</div>
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

```twig {"preview":true}
<div class="w-full flex flex-col items-center gap-16">
    <div dir="rtl" class="grid w-full max-w-sm gap-6">
        <twig:InputGroup class="max-w-xs">
            <twig:InputGroup:Input placeholder="بحث..." />
            <twig:InputGroup:Addon>
                <twig:ux:icon name="lucide:search" />
            </twig:InputGroup:Addon>
            <twig:InputGroup:Addon align="inline-end">١٢ نتيجة</twig:InputGroup:Addon>
        </twig:InputGroup>

        <twig:InputGroup>
            <twig:InputGroup:Input placeholder="جاري البحث..." />
            <twig:InputGroup:Addon align="inline-end">
                <twig:Spinner />
            </twig:InputGroup:Addon>
        </twig:InputGroup>

        <twig:InputGroup>
            <twig:InputGroup:Input placeholder="جاري حفظ التغييرات..." />
            <twig:InputGroup:Addon align="inline-end">
                <twig:InputGroup:Text>جاري الحفظ...</twig:InputGroup:Text>
                <twig:Spinner />
            </twig:InputGroup:Addon>
        </twig:InputGroup>

        <twig:Field:Group class="max-w-sm">
            <twig:Field>
                <twig:Field:Label for="rtl-ar-textarea">منطقة النص</twig:Field:Label>
                <twig:InputGroup>
                    <twig:InputGroup:Textarea id="rtl-ar-textarea" placeholder="اكتب تعليقًا..." />
                    <twig:InputGroup:Addon align="block-end">
                        <twig:InputGroup:Text>٠/٢٨٠</twig:InputGroup:Text>
                        <twig:InputGroup:Button variant="default" size="sm" class="ms-auto">نشر</twig:InputGroup:Button>
                    </twig:InputGroup:Addon>
                </twig:InputGroup>
                <twig:Field:Description>تذييل موضع أسفل منطقة النص.</twig:Field:Description>
            </twig:Field>
        </twig:Field:Group>
    </div>

    <div dir="rtl" class="grid w-full max-w-sm gap-6">
        <twig:InputGroup class="max-w-xs">
            <twig:InputGroup:Input placeholder="חפש..." />
            <twig:InputGroup:Addon>
                <twig:ux:icon name="lucide:search" />
            </twig:InputGroup:Addon>
            <twig:InputGroup:Addon align="inline-end">12 תוצאות</twig:InputGroup:Addon>
        </twig:InputGroup>

        <twig:InputGroup>
            <twig:InputGroup:Input placeholder="מחפש..." />
            <twig:InputGroup:Addon align="inline-end">
                <twig:Spinner />
            </twig:InputGroup:Addon>
        </twig:InputGroup>

        <twig:InputGroup>
            <twig:InputGroup:Input placeholder="שומר שינויים..." />
            <twig:InputGroup:Addon align="inline-end">
                <twig:InputGroup:Text>שומר...</twig:InputGroup:Text>
                <twig:Spinner />
            </twig:InputGroup:Addon>
        </twig:InputGroup>

        <twig:Field:Group class="max-w-sm">
            <twig:Field>
                <twig:Field:Label for="rtl-he-textarea">אזור טקסט</twig:Field:Label>
                <twig:InputGroup>
                    <twig:InputGroup:Textarea id="rtl-he-textarea" placeholder="כתוב תגובה..." />
                    <twig:InputGroup:Addon align="block-end">
                        <twig:InputGroup:Text>0/280</twig:InputGroup:Text>
                        <twig:InputGroup:Button variant="default" size="sm" class="ms-auto">פרסם</twig:InputGroup:Button>
                    </twig:InputGroup:Addon>
                </twig:InputGroup>
                <twig:Field:Description>כותרת תחתונה ממוקמת מתחת לאזור הטקסט.</twig:Field:Description>
            </twig:Field>
        </twig:Field:Group>
    </div>
</div>
```

## Accessibility

- `InputGroup` renders `role="group"`, so the control and its addons are announced as one unit. Give it an `aria-label` when the group needs a name of its own.
- An addon is not a label. Keep a real `Label` bound to the control with `for` and `id`, even when the addon already shows a unit or a prefix.
- Place each `InputGroup:Addon` after the control in the markup. Position it with `align`. The Tab order follows the markup, so the control is reached before the buttons of its addons.
- An icon-only `InputGroup:Button` needs an `aria-label`, since `<twig:ux:icon>` renders `aria-hidden="true"`.
- Purely decorative text in an `InputGroup:Addon`, such as a currency symbol already stated in the label, is still announced. Move it out or hide it with `aria-hidden="true"` when it only repeats what the label says.
- Set `aria-invalid="true"` on the control rather than on the group, so the invalid state lands on the element the user is editing.

## API Reference

::: api-reference
