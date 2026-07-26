# Resizable

Accessible resizable panel groups and layouts with keyboard support.

```twig {"preview":true}
<twig:Resizable orientation="horizontal" class="max-w-sm rounded-lg border h-[200px]">
    <twig:Resizable:Panel size="50">
        <div class="flex h-full items-center justify-center p-6">
            <span class="font-semibold">One</span>
        </div>
    </twig:Resizable:Panel>
    <twig:Resizable:Handle withHandle />
    <twig:Resizable:Panel size="50">
        <twig:Resizable orientation="vertical">
            <twig:Resizable:Panel size="25">
                <div class="flex h-full items-center justify-center p-6">
                    <span class="font-semibold">Two</span>
                </div>
            </twig:Resizable:Panel>
            <twig:Resizable:Handle withHandle />
            <twig:Resizable:Panel size="75">
                <div class="flex h-full items-center justify-center p-6">
                    <span class="font-semibold">Three</span>
                </div>
            </twig:Resizable:Panel>
        </twig:Resizable>
    </twig:Resizable:Panel>
</twig:Resizable>
```

## Installation

::: installation

## Usage

```twig
<twig:Resizable class="h-40 w-64 p-4">
    <p>Drag the bottom-right corner to resize me in any direction.</p>
</twig:Resizable>
```

## Examples

### Vertical

Use `orientation="vertical"` for vertical resizing.

```twig {"preview":true}
<twig:Resizable orientation="vertical" class="min-h-[200px] max-w-sm rounded-lg border h-[200px]">
    <twig:Resizable:Panel size="25">
        <div class="flex h-full items-center justify-center p-6">
            <span class="font-semibold">Header</span>
        </div>
    </twig:Resizable:Panel>
    <twig:Resizable:Handle />
    <twig:Resizable:Panel size="75">
        <div class="flex h-full items-center justify-center p-6">
            <span class="font-semibold">Content</span>
        </div>
    </twig:Resizable:Panel>
</twig:Resizable>
```

### Handle

Use the `withHandle` prop on `Resizable:Handle` to show a visible handle.

```twig {"preview":true}
<twig:Resizable orientation="horizontal" class="min-h-[200px] max-w-md rounded-lg border md:min-w-[450px] h-[200px]">
    <twig:Resizable:Panel size="25">
        <div class="flex h-full items-center justify-center p-6">
            <span class="font-semibold">Sidebar</span>
        </div>
    </twig:Resizable:Panel>
    <twig:Resizable:Handle withHandle />
    <twig:Resizable:Panel size="75">
        <div class="flex h-full items-center justify-center p-6">
            <span class="font-semibold">Content</span>
        </div>
    </twig:Resizable:Panel>
</twig:Resizable>
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

```twig {"preview":true}
<div class="flex flex-col gap-8">
    {# Arabic #}
    <twig:Resizable dir="rtl" orientation="horizontal" class="max-w-sm rounded-lg border h-[200px]">
        <twig:Resizable:Panel size="50">
            <div class="flex h-full items-center justify-center p-6">
                <span class="font-semibold">واحد</span>
            </div>
        </twig:Resizable:Panel>
        <twig:Resizable:Handle withHandle />
        <twig:Resizable:Panel size="50">
            <twig:Resizable orientation="vertical" dir="rtl">
                <twig:Resizable:Panel size="25">
                    <div class="flex h-full items-center justify-center p-6">
                        <span class="font-semibold">اثنان</span>
                    </div>
                </twig:Resizable:Panel>
                <twig:Resizable:Handle withHandle />
                <twig:Resizable:Panel size="75">
                    <div class="flex h-full items-center justify-center p-6">
                        <span class="font-semibold">ثلاثة</span>
                    </div>
                </twig:Resizable:Panel>
            </twig:Resizable>
        </twig:Resizable:Panel>
    </twig:Resizable>

    {# Hebrew #}
    <twig:Resizable dir="rtl" orientation="horizontal" class="max-w-sm rounded-lg border h-[200px]">
        <twig:Resizable:Panel size="50">
            <div class="flex h-full items-center justify-center p-6">
                <span class="font-semibold">אחד</span>
            </div>
        </twig:Resizable:Panel>
        <twig:Resizable:Handle withHandle />
        <twig:Resizable:Panel size="50">
            <twig:Resizable orientation="vertical" dir="rtl">
                <twig:Resizable:Panel size="25">
                    <div class="flex h-full items-center justify-center p-6">
                        <span class="font-semibold">שניים</span>
                    </div>
                </twig:Resizable:Panel>
                <twig:Resizable:Handle withHandle />
                <twig:Resizable:Panel size="75">
                    <div class="flex h-full items-center justify-center p-6">
                        <span class="font-semibold">שלושה</span>
                    </div>
                </twig:Resizable:Panel>
            </twig:Resizable>
        </twig:Resizable:Panel>
    </twig:Resizable>
</div>
```

## API Reference

::: api-reference
