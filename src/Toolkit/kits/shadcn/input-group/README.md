# Input Group

Add addons, buttons, and helper content to inputs.

```twig {"preview":true,"height":"200px"}
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

### Icon

```twig {"preview":true,"height":"300px"}
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

```twig {"preview":true,"height":"400px"}
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
        <twig:InputGroup:Input placeholder="example.com" class="pl-0.5!" />
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

```twig {"preview":true,"height":"300px"}
<div class="grid w-full max-w-sm gap-6">
    <twig:InputGroup>
        <twig:InputGroup:Input placeholder="https://x.com/symfony" readonly />
        <twig:InputGroup:Addon align="inline-end">
            <twig:InputGroup:Button aria-label="Copy" title="Copy" size="icon-xs">
                <twig:ux:icon name="lucide:copy" />
            </twig:InputGroup:Button>
        </twig:InputGroup:Addon>
    </twig:InputGroup>

    <twig:InputGroup class="[--radius:9999px]">
        <twig:InputGroup:Addon>
            <twig:InputGroup:Button variant="secondary" size="icon-xs">
                <twig:ux:icon name="lucide:info" />
            </twig:InputGroup:Button>
        </twig:InputGroup:Addon>
        <twig:InputGroup:Addon class="pl-1.5 text-muted-foreground">
            https://
        </twig:InputGroup:Addon>
        <twig:InputGroup:Input />
        <twig:InputGroup:Addon align="inline-end">
            <twig:InputGroup:Button size="icon-xs">
                <twig:ux:icon name="lucide:star" />
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

### Spinner

```twig {"preview":true,"height":"300px"}
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

```twig {"preview":true,"height":"400px"}
<div class="grid w-full max-w-md gap-4">
    <twig:InputGroup>
        <twig:InputGroup:Textarea
            id="textarea-code-32"
            placeholder="console.log('Hello, world!');"
            class="min-h-[200px]"
        />
        <twig:InputGroup:Addon align="block-end" class="border-t">
            <twig:InputGroup:Text>Line 1, Column 1</twig:InputGroup:Text>
            <twig:InputGroup:Button size="sm" class="ml-auto" variant="default">
                Run <twig:ux:icon name="lucide:corner-down-left" />
            </twig:InputGroup:Button>
        </twig:InputGroup:Addon>
        <twig:InputGroup:Addon align="block-start" class="border-b">
            <twig:InputGroup:Text class="font-mono font-medium">
                <twig:ux:icon name="ri:javascript-fill" />
                script.js
            </twig:InputGroup:Text>
            <twig:InputGroup:Button class="ml-auto" size="icon-xs">
                <twig:ux:icon name="lucide:refresh-ccw" />
            </twig:InputGroup:Button>
            <twig:InputGroup:Button variant="ghost" size="icon-xs">
                <twig:ux:icon name="lucide:copy" />
            </twig:InputGroup:Button>
        </twig:InputGroup:Addon>
    </twig:InputGroup>
</div>
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

```twig {"preview":true,"height":"850px"}
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
            <twig:InputGroup:Input placeholder="הקלד..." />
            <twig:InputGroup:Addon>
                <twig:ux:icon name="lucide:search" />
            </twig:InputGroup:Addon>
            <twig:InputGroup:Addon align="inline-end">12 תוצאות</twig:InputGroup:Addon>
        </twig:InputGroup>

        <twig:InputGroup>
            <twig:InputGroup:Input placeholder="טוען..." />
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
                        <twig:InputGroup:Button variant="default" size="sm" class="ms-auto">שלח</twig:InputGroup:Button>
                    </twig:InputGroup:Addon>
                </twig:InputGroup>
                <twig:Field:Description>כותרת תחתונה ממוקמת מתחת לאזור הטקסט.</twig:Field:Description>
            </twig:Field>
        </twig:Field:Group>
    </div>
</div>
```

## API Reference

::: api-reference
