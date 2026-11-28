# Modal

Modal is used to show a dialog or a box when you click a button.

```twig {"preview":true}
<div style="min-height: 280px">
    <twig:Modal id="default">
        <twig:Modal:Trigger>
            <twig:Button {{ ...modal_trigger_attrs }}>open modal</twig:Button>
        </twig:Modal:Trigger>
        <twig:Modal:Content>
            <twig:Modal:Box>
                <twig:Modal:Title>Hello!</twig:Modal:Title>
                <p class="py-4">Press ESC key or click the button below to close</p>
                <twig:Modal:Action>
                    <twig:Modal:Close>
                        <twig:Button {{ ...modal_close_attrs }}>Close</twig:Button>
                    </twig:Modal:Close>
                </twig:Modal:Action>
            </twig:Modal:Box>
        </twig:Modal:Content>
    </twig:Modal>
</div>
```

## Installation

::: installation

## Usage

```twig
<twig:Modal id="my_modal" open="false | true">
    <twig:Modal:Trigger>
        <twig:Button {{ ...modal_trigger_attrs }}>open modal</twig:Button>
    </twig:Modal:Trigger>
    <twig:Modal:Content
        placement="default | top | middle | bottom | start | end"
        backdrop="false | true"
    >
        <twig:Modal:Box>
            <twig:Modal:Title>Hello!</twig:Modal:Title>
            <p class="py-4">Press ESC key or click the button below to close</p>
            <twig:Modal:Action>
                <twig:Modal:Close>
                    <twig:Button {{ ...modal_close_attrs }}>Close</twig:Button>
                </twig:Modal:Close>
            </twig:Modal:Action>
        </twig:Modal:Box>
    </twig:Modal:Content>
</twig:Modal>
```

## Examples

### Closes When Clicked Outside

Set `backdrop` to render a backdrop that covers the screen, so a click outside the box closes the modal.

```twig {"preview":true}
<div style="min-height: 240px">
    <twig:Modal id="backdrop">
        <twig:Modal:Trigger>
            <twig:Button {{ ...modal_trigger_attrs }}>open modal</twig:Button>
        </twig:Modal:Trigger>
        <twig:Modal:Content backdrop>
            <twig:Modal:Box>
                <twig:Modal:Title>Hello!</twig:Modal:Title>
                <p class="py-4">Press ESC key or click outside to close</p>
            </twig:Modal:Box>
        </twig:Modal:Content>
    </twig:Modal>
</div>
```

### Close Button At Corner

```twig {"preview":true}
<div style="min-height: 240px">
    <twig:Modal id="corner">
        <twig:Modal:Trigger>
            <twig:Button {{ ...modal_trigger_attrs }}>open modal</twig:Button>
        </twig:Modal:Trigger>
        <twig:Modal:Content>
            <twig:Modal:Box>
                <twig:Modal:Close>
                    <twig:Button size="sm" shape="circle" variant="ghost" class="absolute end-2 top-2" aria-label="Close" {{ ...modal_close_attrs }}>✕</twig:Button>
                </twig:Modal:Close>
                <twig:Modal:Title>Hello!</twig:Modal:Title>
                <p class="py-4">Press ESC key or click on ✕ button to close</p>
            </twig:Modal:Box>
        </twig:Modal:Content>
    </twig:Modal>
</div>
```

### Custom Width

You can use any `w-*` and `max-w-*` utility class to customize the width.

```twig {"preview":true}
<div style="min-height: 280px">
    <twig:Modal id="custom_width">
        <twig:Modal:Trigger>
            <twig:Button {{ ...modal_trigger_attrs }}>open modal</twig:Button>
        </twig:Modal:Trigger>
        <twig:Modal:Content>
            <twig:Modal:Box class="w-11/12 max-w-5xl">
                <twig:Modal:Title>Hello!</twig:Modal:Title>
                <p class="py-4">Click the button below to close</p>
                <twig:Modal:Action>
                    <twig:Modal:Close>
                        <twig:Button {{ ...modal_close_attrs }}>Close</twig:Button>
                    </twig:Modal:Close>
                </twig:Modal:Action>
            </twig:Modal:Box>
        </twig:Modal:Content>
    </twig:Modal>
</div>
```

### Responsive

The modal goes to the bottom on small screens, and to the middle from the `sm` breakpoint.

```twig {"preview":true}
<div style="min-height: 280px">
    <twig:Modal id="responsive">
        <twig:Modal:Trigger>
            <twig:Button {{ ...modal_trigger_attrs }}>open modal</twig:Button>
        </twig:Modal:Trigger>
        <twig:Modal:Content placement="bottom" class="sm:modal-middle">
            <twig:Modal:Box>
                <twig:Modal:Title>Hello!</twig:Modal:Title>
                <p class="py-4">Press ESC key or click the button below to close</p>
                <twig:Modal:Action>
                    <twig:Modal:Close>
                        <twig:Button {{ ...modal_close_attrs }}>Close</twig:Button>
                    </twig:Modal:Close>
                </twig:Modal:Action>
            </twig:Modal:Box>
        </twig:Modal:Content>
    </twig:Modal>
</div>
```

### Placement

Use the `placement` prop to move the modal to the top, the bottom, the start or the end of the screen.

```twig {"preview":true}
<div class="flex flex-wrap items-center justify-center gap-2" style="min-height: 280px">
    {% for placement in ['top', 'bottom', 'start', 'end'] %}
        <twig:Modal :id="'placement_' ~ placement">
            <twig:Modal:Trigger>
                <twig:Button {{ ...modal_trigger_attrs }}>{{ placement|capitalize }}</twig:Button>
            </twig:Modal:Trigger>
            <twig:Modal:Content :placement="placement" backdrop>
                <twig:Modal:Box>
                    <twig:Modal:Title>Hello!</twig:Modal:Title>
                    <p class="py-4">Press ESC key or click outside to close</p>
                </twig:Modal:Box>
            </twig:Modal:Content>
        </twig:Modal>
    {% endfor %}
</div>
```

### Open By Default

Set `open` to open the modal when the page loads.

```twig {"preview":true}
<div style="min-height: 280px">
    <twig:Modal id="open_by_default" open>
        <twig:Modal:Trigger>
            <twig:Button {{ ...modal_trigger_attrs }}>open modal</twig:Button>
        </twig:Modal:Trigger>
        <twig:Modal:Content>
            <twig:Modal:Box>
                <twig:Modal:Title>Hello!</twig:Modal:Title>
                <p class="py-4">Press ESC key or click the button below to close</p>
                <twig:Modal:Action>
                    <twig:Modal:Close>
                        <twig:Button {{ ...modal_close_attrs }}>Close</twig:Button>
                    </twig:Modal:Close>
                </twig:Modal:Action>
            </twig:Modal:Box>
        </twig:Modal:Content>
    </twig:Modal>
</div>
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

```twig {"preview":true}
<div class="flex flex-col items-center gap-4" style="min-height: 280px">
    <twig:Modal id="rtl_ar" dir="rtl" lang="ar">
        <twig:Modal:Trigger>
            <twig:Button {{ ...modal_trigger_attrs }}>افتح النافذة</twig:Button>
        </twig:Modal:Trigger>
        <twig:Modal:Content>
            <twig:Modal:Box>
                <twig:Modal:Close>
                    <twig:Button size="sm" shape="circle" variant="ghost" class="absolute end-2 top-2" aria-label="إغلاق" {{ ...modal_close_attrs }}>✕</twig:Button>
                </twig:Modal:Close>
                <twig:Modal:Title>مرحبًا!</twig:Modal:Title>
                <p class="py-4">اضغط على مفتاح ESC أو على الزر أدناه للإغلاق</p>
                <twig:Modal:Action>
                    <twig:Modal:Close>
                        <twig:Button {{ ...modal_close_attrs }}>إغلاق</twig:Button>
                    </twig:Modal:Close>
                </twig:Modal:Action>
            </twig:Modal:Box>
        </twig:Modal:Content>
    </twig:Modal>
    <twig:Modal id="rtl_he" dir="rtl" lang="he">
        <twig:Modal:Trigger>
            <twig:Button {{ ...modal_trigger_attrs }}>פתח חלון</twig:Button>
        </twig:Modal:Trigger>
        <twig:Modal:Content>
            <twig:Modal:Box>
                <twig:Modal:Close>
                    <twig:Button size="sm" shape="circle" variant="ghost" class="absolute end-2 top-2" aria-label="סגירה" {{ ...modal_close_attrs }}>✕</twig:Button>
                </twig:Modal:Close>
                <twig:Modal:Title>שלום!</twig:Modal:Title>
                <p class="py-4">לחצו על מקש ESC או על הכפתור למטה כדי לסגור</p>
                <twig:Modal:Action>
                    <twig:Modal:Close>
                        <twig:Button {{ ...modal_close_attrs }}>סגירה</twig:Button>
                    </twig:Modal:Close>
                </twig:Modal:Action>
            </twig:Modal:Box>
        </twig:Modal:Content>
    </twig:Modal>
</div>
```

## API Reference

::: api-reference
