# Popover

A click-triggered popup that displays rich content, anchored to its trigger.

```twig {"preview":true}
<div class="flex items-start justify-center pt-6" style="min-height: 400px">
    <twig:Popover>
        <twig:Popover:Trigger>
            <twig:Button variant="outline" {{ ...popover_trigger_attrs }}>Open Popover</twig:Button>
        </twig:Popover:Trigger>
        <twig:Popover:Content align="start" class="w-80">
            <div class="grid gap-4">
                <div class="space-y-1">
                    <h4 class="text-sm font-medium leading-none">Dimensions</h4>
                    <p class="text-xs text-muted-foreground">Set the dimensions for the layer.</p>
                </div>
                <div class="grid gap-2">
                    <div class="grid grid-cols-3 items-center gap-4">
                        <twig:Label for="demo-width">Width</twig:Label>
                        <input id="demo-width" class="col-span-2 h-8 rounded-md border px-2 text-sm" value="100%">
                    </div>
                    <div class="grid grid-cols-3 items-center gap-4">
                        <twig:Label for="demo-max-width">Max. width</twig:Label>
                        <input id="demo-max-width" class="col-span-2 h-8 rounded-md border px-2 text-sm" value="300px">
                    </div>
                    <div class="grid grid-cols-3 items-center gap-4">
                        <twig:Label for="demo-height">Height</twig:Label>
                        <input id="demo-height" class="col-span-2 h-8 rounded-md border px-2 text-sm" value="25px">
                    </div>
                    <div class="grid grid-cols-3 items-center gap-4">
                        <twig:Label for="demo-max-height">Max. height</twig:Label>
                        <input id="demo-max-height" class="col-span-2 h-8 rounded-md border px-2 text-sm" value="none">
                    </div>
                </div>
            </div>
        </twig:Popover:Content>
    </twig:Popover>
</div>
```

## Installation

::: installation

## Usage

```twig
<twig:Popover name="dimensions" open="false">
    <twig:Popover:Trigger>
        <twig:Button variant="outline" {{ ...popover_trigger_attrs }}>Open popover</twig:Button>
    </twig:Popover:Trigger>
    <twig:Popover:Content side="bottom" align="center">
        Popover content goes here.
    </twig:Popover:Content>
</twig:Popover>
```

## Examples

### Basic

```twig {"preview":true}
<div class="flex items-start justify-center pt-6" style="min-height: 260px">
    <twig:Popover>
        <twig:Popover:Trigger>
            <twig:Button variant="outline" {{ ...popover_trigger_attrs }}>Open Popover</twig:Button>
        </twig:Popover:Trigger>
        <twig:Popover:Content align="start">
            <div class="space-y-1">
                <h4 class="text-sm font-medium leading-none">Dimensions</h4>
                <p class="text-xs text-muted-foreground">Set the dimensions for the layer.</p>
            </div>
        </twig:Popover:Content>
    </twig:Popover>
</div>
```

### Alignments

Use the `align` prop to align the content to the `start`, `center` or `end` of the trigger.

```twig {"preview":true}
<div class="flex items-start justify-center pt-6" style="min-height: 260px">
    <div class="flex gap-6">
        <twig:Popover name="alignments-demo">
            <twig:Popover:Trigger>
                <twig:Button variant="outline" size="sm" {{ ...popover_trigger_attrs }}>Start</twig:Button>
            </twig:Popover:Trigger>
            <twig:Popover:Content align="start" class="w-40">
                Aligned to start
            </twig:Popover:Content>
        </twig:Popover>
        <twig:Popover name="alignments-demo">
            <twig:Popover:Trigger>
                <twig:Button variant="outline" size="sm" {{ ...popover_trigger_attrs }}>Center</twig:Button>
            </twig:Popover:Trigger>
            <twig:Popover:Content align="center" class="w-40">
                Aligned to center
            </twig:Popover:Content>
        </twig:Popover>
        <twig:Popover name="alignments-demo">
            <twig:Popover:Trigger>
                <twig:Button variant="outline" size="sm" {{ ...popover_trigger_attrs }}>End</twig:Button>
            </twig:Popover:Trigger>
            <twig:Popover:Content align="end" class="w-40">
                Aligned to end
            </twig:Popover:Content>
        </twig:Popover>
    </div>
</div>
```

### With Form

```twig {"preview":true}
<div class="flex items-start justify-center pt-6" style="min-height: 340px">
    <twig:Popover>
        <twig:Popover:Trigger>
            <twig:Button variant="outline" {{ ...popover_trigger_attrs }}>Open Popover</twig:Button>
        </twig:Popover:Trigger>
        <twig:Popover:Content align="start" class="w-64">
            <div class="grid gap-4">
                <div class="space-y-1">
                    <h4 class="text-sm font-medium leading-none">Dimensions</h4>
                    <p class="text-xs text-muted-foreground">Set the dimensions for the layer.</p>
                </div>
                <div class="grid gap-3">
                    <div class="flex items-center gap-3">
                        <twig:Label for="form-width" class="w-1/2">Width</twig:Label>
                        <input id="form-width" class="h-8 w-full rounded-md border px-2 text-sm" value="100%">
                    </div>
                    <div class="flex items-center gap-3">
                        <twig:Label for="form-height" class="w-1/2">Height</twig:Label>
                        <input id="form-height" class="h-8 w-full rounded-md border px-2 text-sm" value="25px">
                    </div>
                </div>
            </div>
        </twig:Popover:Content>
    </twig:Popover>
</div>
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

```twig {"preview":true}
<div class="flex flex-col items-center gap-12 py-12" style="min-height: 520px">
    {# Arabic #}
    <div dir="rtl" class="flex flex-wrap justify-center gap-2">
        <twig:Popover name="rtl-ar">
            <twig:Popover:Trigger>
                <twig:Button variant="outline" {{ ...popover_trigger_attrs }}>يسار</twig:Button>
            </twig:Popover:Trigger>
            <twig:Popover:Content side="left">
                <div class="space-y-1">
                    <h4 class="text-sm font-medium leading-none">الأبعاد</h4>
                    <p class="text-xs text-muted-foreground">تعيين الأبعاد للطبقة.</p>
                </div>
            </twig:Popover:Content>
        </twig:Popover>
        <twig:Popover name="rtl-ar">
            <twig:Popover:Trigger>
                <twig:Button variant="outline" {{ ...popover_trigger_attrs }}>أعلى</twig:Button>
            </twig:Popover:Trigger>
            <twig:Popover:Content side="top">
                <div class="space-y-1">
                    <h4 class="text-sm font-medium leading-none">الأبعاد</h4>
                    <p class="text-xs text-muted-foreground">تعيين الأبعاد للطبقة.</p>
                </div>
            </twig:Popover:Content>
        </twig:Popover>
        <twig:Popover name="rtl-ar">
            <twig:Popover:Trigger>
                <twig:Button variant="outline" {{ ...popover_trigger_attrs }}>أسفل</twig:Button>
            </twig:Popover:Trigger>
            <twig:Popover:Content side="bottom">
                <div class="space-y-1">
                    <h4 class="text-sm font-medium leading-none">الأبعاد</h4>
                    <p class="text-xs text-muted-foreground">تعيين الأبعاد للطبقة.</p>
                </div>
            </twig:Popover:Content>
        </twig:Popover>
        <twig:Popover name="rtl-ar">
            <twig:Popover:Trigger>
                <twig:Button variant="outline" {{ ...popover_trigger_attrs }}>يمين</twig:Button>
            </twig:Popover:Trigger>
            <twig:Popover:Content side="right">
                <div class="space-y-1">
                    <h4 class="text-sm font-medium leading-none">الأبعاد</h4>
                    <p class="text-xs text-muted-foreground">تعيين الأبعاد للطبقة.</p>
                </div>
            </twig:Popover:Content>
        </twig:Popover>
    </div>

    {# Hebrew #}
    <div dir="rtl" class="flex flex-wrap justify-center gap-2">
        <twig:Popover name="rtl-he">
            <twig:Popover:Trigger>
                <twig:Button variant="outline" {{ ...popover_trigger_attrs }}>שמאל</twig:Button>
            </twig:Popover:Trigger>
            <twig:Popover:Content side="left">
                <div class="space-y-1">
                    <h4 class="text-sm font-medium leading-none">מימדים</h4>
                    <p class="text-xs text-muted-foreground">הגדר את המימדים לשכבה.</p>
                </div>
            </twig:Popover:Content>
        </twig:Popover>
        <twig:Popover name="rtl-he">
            <twig:Popover:Trigger>
                <twig:Button variant="outline" {{ ...popover_trigger_attrs }}>למעלה</twig:Button>
            </twig:Popover:Trigger>
            <twig:Popover:Content side="top">
                <div class="space-y-1">
                    <h4 class="text-sm font-medium leading-none">מימדים</h4>
                    <p class="text-xs text-muted-foreground">הגדר את המימדים לשכבה.</p>
                </div>
            </twig:Popover:Content>
        </twig:Popover>
        <twig:Popover name="rtl-he">
            <twig:Popover:Trigger>
                <twig:Button variant="outline" {{ ...popover_trigger_attrs }}>למטה</twig:Button>
            </twig:Popover:Trigger>
            <twig:Popover:Content side="bottom">
                <div class="space-y-1">
                    <h4 class="text-sm font-medium leading-none">מימדים</h4>
                    <p class="text-xs text-muted-foreground">הגדר את המימדים לשכבה.</p>
                </div>
            </twig:Popover:Content>
        </twig:Popover>
        <twig:Popover name="rtl-he">
            <twig:Popover:Trigger>
                <twig:Button variant="outline" {{ ...popover_trigger_attrs }}>ימין</twig:Button>
            </twig:Popover:Trigger>
            <twig:Popover:Content side="right">
                <div class="space-y-1">
                    <h4 class="text-sm font-medium leading-none">מימדים</h4>
                    <p class="text-xs text-muted-foreground">הגדר את המימדים לשכבה.</p>
                </div>
            </twig:Popover:Content>
        </twig:Popover>
    </div>
</div>
```

## API Reference

::: api-reference
