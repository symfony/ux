# Drawer

A panel that slides in from the edge of the screen to display supplementary content.

```twig {"preview":true}
<div style="min-height: 400px">
    <twig:Drawer id="drawer-demo">
        <twig:Drawer:Trigger>
            <twig:Button variant="outline" {{ ...drawer_trigger_attrs }}>Open Drawer</twig:Button>
        </twig:Drawer:Trigger>
        <twig:Drawer:Content>
            <div class="mx-auto w-full max-w-sm">
                <twig:Drawer:Header>
                    <twig:Drawer:Title>Move Goal</twig:Drawer:Title>
                    <twig:Drawer:Description>Set your daily activity goal.</twig:Drawer:Description>
                </twig:Drawer:Header>
                <div class="p-4 pb-0">
                    <p class="text-muted-foreground text-sm">Adjust the target you want to reach each day.</p>
                </div>
                <twig:Drawer:Footer>
                    <twig:Button>Submit</twig:Button>
                    <twig:Drawer:Close>
                        <twig:Button variant="outline" {{ ...drawer_close_attrs }}>Cancel</twig:Button>
                    </twig:Drawer:Close>
                </twig:Drawer:Footer>
            </div>
        </twig:Drawer:Content>
    </twig:Drawer>
</div>
```

## Installation

::: installation

## Usage

```twig
<twig:Drawer id="drawer">
    <twig:Drawer:Trigger>
        <twig:Button variant="outline" {{ ...drawer_trigger_attrs }}>Open</twig:Button>
    </twig:Drawer:Trigger>
    <twig:Drawer:Content>
        <twig:Drawer:Header>
            <twig:Drawer:Title>Are you absolutely sure?</twig:Drawer:Title>
            <twig:Drawer:Description>This action cannot be undone.</twig:Drawer:Description>
        </twig:Drawer:Header>
        <twig:Drawer:Footer>
            <twig:Button>Submit</twig:Button>
            <twig:Drawer:Close>
                <twig:Button variant="outline" {{ ...drawer_close_attrs }}>Cancel</twig:Button>
            </twig:Drawer:Close>
        </twig:Drawer:Footer>
    </twig:Drawer:Content>
</twig:Drawer>
```

## Examples

### Directions

Use the `direction` prop to set the edge the drawer slides in from: `top`, `right`, `bottom` (default) or `left`.

```twig {"preview":true}
<div class="flex flex-wrap gap-2" style="min-height: 400px">
    {% for direction in ['top', 'right', 'bottom', 'left'] %}
        <twig:Drawer id="drawer-direction-{{ direction }}" direction="{{ direction }}">
            <twig:Drawer:Trigger>
                <twig:Button variant="outline" class="capitalize" {{ ...drawer_trigger_attrs }}>{{ direction }}</twig:Button>
            </twig:Drawer:Trigger>
            <twig:Drawer:Content class="data-[direction=bottom]:max-h-[50vh] data-[direction=top]:max-h-[50vh]">
                <twig:Drawer:Header>
                    <twig:Drawer:Title>Move Goal</twig:Drawer:Title>
                    <twig:Drawer:Description>Set your daily activity goal.</twig:Drawer:Description>
                </twig:Drawer:Header>
                <div class="min-h-0 flex-1 overflow-y-auto px-4">
                    {% for i in 1..8 %}
                        <p class="mb-2 leading-relaxed">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
                    {% endfor %}
                </div>
                <twig:Drawer:Footer>
                    <twig:Button>Submit</twig:Button>
                    <twig:Drawer:Close>
                        <twig:Button variant="outline" {{ ...drawer_close_attrs }}>Cancel</twig:Button>
                    </twig:Drawer:Close>
                </twig:Drawer:Footer>
            </twig:Drawer:Content>
        </twig:Drawer>
    {% endfor %}
</div>
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

```twig {"preview":true}
<div style="min-height: 400px">
    <twig:Drawer id="drawer-rtl" dir="rtl">
        <twig:Drawer:Trigger>
            <twig:Button variant="outline" {{ ...drawer_trigger_attrs }}>فتح</twig:Button>
        </twig:Drawer:Trigger>
        <twig:Drawer:Content>
            <div class="mx-auto w-full max-w-sm">
                <twig:Drawer:Header>
                    <twig:Drawer:Title>تعديل الهدف</twig:Drawer:Title>
                    <twig:Drawer:Description>حدد هدف نشاطك اليومي.</twig:Drawer:Description>
                </twig:Drawer:Header>
                <twig:Drawer:Footer>
                    <twig:Button>إرسال</twig:Button>
                    <twig:Drawer:Close>
                        <twig:Button variant="outline" {{ ...drawer_close_attrs }}>إلغاء</twig:Button>
                    </twig:Drawer:Close>
                </twig:Drawer:Footer>
            </div>
        </twig:Drawer:Content>
    </twig:Drawer>
</div>
```

## API Reference

::: api-reference
