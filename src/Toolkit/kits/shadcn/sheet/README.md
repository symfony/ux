# Sheet

Extends the Dialog component to display content that complements the main content of the screen.

```twig {"preview":true}
<div style="min-height: 480px">
    <twig:Sheet id="sheet-demo">
        <twig:Sheet:Trigger>
            <twig:Button variant="outline" {{ ...sheet_trigger_attrs }}>Open</twig:Button>
        </twig:Sheet:Trigger>
        <twig:Sheet:Content>
            <twig:Sheet:Header>
                <twig:Sheet:Title>Edit profile</twig:Sheet:Title>
                <twig:Sheet:Description>Make changes to your profile here. Click save when you're done.</twig:Sheet:Description>
            </twig:Sheet:Header>
            <div class="grid flex-1 auto-rows-min gap-6 px-4">
                <div class="grid gap-3">
                    <twig:Label for="sheet-demo-name">Name</twig:Label>
                    <twig:Input id="sheet-demo-name" value="Pedro Duarte" />
                </div>
                <div class="grid gap-3">
                    <twig:Label for="sheet-demo-username">Username</twig:Label>
                    <twig:Input id="sheet-demo-username" value="@peduarte" />
                </div>
            </div>
            <twig:Sheet:Footer>
                <twig:Button type="submit">Save changes</twig:Button>
                <twig:Sheet:Close>
                    <twig:Button variant="outline" {{ ...sheet_close_attrs }}>Close</twig:Button>
                </twig:Sheet:Close>
            </twig:Sheet:Footer>
        </twig:Sheet:Content>
    </twig:Sheet>
</div>
```

## Installation

::: installation

## Usage

```twig
<twig:Sheet id="sheet">
    <twig:Sheet:Trigger>
        <twig:Button variant="outline" {{ ...sheet_trigger_attrs }}>Open</twig:Button>
    </twig:Sheet:Trigger>
    <twig:Sheet:Content>
        <twig:Sheet:Header>
            <twig:Sheet:Title>Are you absolutely sure?</twig:Sheet:Title>
            <twig:Sheet:Description>This action cannot be undone.</twig:Sheet:Description>
        </twig:Sheet:Header>
    </twig:Sheet:Content>
</twig:Sheet>
```

## Examples

### Sides

Use the `side` prop to set the edge the sheet slides in from: `top`, `right`, `bottom` or `left`.

```twig {"preview":true}
<div class="flex flex-wrap gap-2" style="min-height: 480px">
    {% for side in ['top', 'right', 'bottom', 'left'] %}
        <twig:Sheet id="sheet-side-{{ side }}" side="{{ side }}">
            <twig:Sheet:Trigger>
                <twig:Button variant="outline" class="capitalize" {{ ...sheet_trigger_attrs }}>{{ side }}</twig:Button>
            </twig:Sheet:Trigger>
            <twig:Sheet:Content class="data-[side=bottom]:max-h-[50vh] data-[side=top]:max-h-[50vh]">
                <twig:Sheet:Header>
                    <twig:Sheet:Title>Edit profile</twig:Sheet:Title>
                    <twig:Sheet:Description>Make changes to your profile here. Click save when you're done.</twig:Sheet:Description>
                </twig:Sheet:Header>
                <div class="min-h-0 flex-1 overflow-y-auto px-4">
                    {% for i in 1..8 %}
                        <p class="mb-2 leading-relaxed">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
                    {% endfor %}
                </div>
                <twig:Sheet:Footer>
                    <twig:Button type="submit">Save changes</twig:Button>
                    <twig:Sheet:Close>
                        <twig:Button variant="outline" {{ ...sheet_close_attrs }}>Cancel</twig:Button>
                    </twig:Sheet:Close>
                </twig:Sheet:Footer>
            </twig:Sheet:Content>
        </twig:Sheet>
    {% endfor %}
</div>
```

### No Close Button

Set `showCloseButton` to `false` on `Sheet:Content` to hide the close button in the top-right corner.

```twig {"preview":true}
<div style="min-height: 480px">
    <twig:Sheet id="sheet-no-close">
        <twig:Sheet:Trigger>
            <twig:Button variant="outline" {{ ...sheet_trigger_attrs }}>Open Sheet</twig:Button>
        </twig:Sheet:Trigger>
        <twig:Sheet:Content :showCloseButton="false">
            <twig:Sheet:Header>
                <twig:Sheet:Title>No Close Button</twig:Sheet:Title>
                <twig:Sheet:Description>This sheet doesn't have a close button in the top-right corner. Click outside to close.</twig:Sheet:Description>
            </twig:Sheet:Header>
        </twig:Sheet:Content>
    </twig:Sheet>
</div>
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

```twig {"preview":true}
<div style="min-height: 480px">
    <twig:Sheet id="sheet-rtl" dir="rtl">
        <twig:Sheet:Trigger>
            <twig:Button variant="outline" {{ ...sheet_trigger_attrs }}>فتح</twig:Button>
        </twig:Sheet:Trigger>
        <twig:Sheet:Content>
            <twig:Sheet:Header>
                <twig:Sheet:Title>تعديل الملف الشخصي</twig:Sheet:Title>
                <twig:Sheet:Description>قم بإجراء تغييرات على ملفك الشخصي هنا. انقر على حفظ عند الانتهاء.</twig:Sheet:Description>
            </twig:Sheet:Header>
        </twig:Sheet:Content>
    </twig:Sheet>
</div>
```

## API Reference

::: api-reference
