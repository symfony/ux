# Tooltip

A popup that displays information related to an element when the element receives keyboard focus or the mouse hovers over it.

```twig {"preview":true,"height":"200px"}
<twig:Tooltip id="tooltip-demo">
    <twig:Tooltip:Trigger>
        <twig:Button {{ ...tooltip_trigger_attrs }} variant="outline">Hover</twig:Button>
    </twig:Tooltip:Trigger>
    <twig:Tooltip:Content>
        <p>Add to library</p>
    </twig:Tooltip:Content>
</twig:Tooltip>
```

## Installation

::: installation

## Usage

```twig
<twig:Tooltip id="my-tooltip">
    <twig:Tooltip:Trigger>
        <twig:Button {{ ...tooltip_trigger_attrs }}>Hover</twig:Button>
    </twig:Tooltip:Trigger>
    <twig:Tooltip:Content>
        <p>Add to library</p>
    </twig:Tooltip:Content>
</twig:Tooltip>
```

## Examples

### Side

Use the `side` prop to change the position of the tooltip.

```twig {"preview":true}
<div class="flex flex-wrap gap-2">
    {% for side in ['left', 'top', 'bottom', 'right'] %}
        <twig:Tooltip id="tooltip-side-{{ side }}">
            <twig:Tooltip:Trigger>
                <twig:Button {{ ...tooltip_trigger_attrs }} variant="outline" class="w-fit">
                    {{ side|capitalize }}
                </twig:Button>
            </twig:Tooltip:Trigger>
            <twig:Tooltip:Content side="{{ side }}">
                <p>Add to library</p>
            </twig:Tooltip:Content>
        </twig:Tooltip>
    {% endfor %}
</div>
```

### With Keyboard Shortcut

```twig {"preview":true}
<twig:Tooltip id="tooltip-with-keyboard-shortcut">
    <twig:Tooltip:Trigger>
        <twig:Button {{ ...tooltip_trigger_attrs }} variant="outline" size="icon-sm">
            <twig:ux:icon name="lucide:save" />
        </twig:Button>
    </twig:Tooltip:Trigger>
    <twig:Tooltip:Content>
        Save Changes <twig:Kbd>S</twig:Kbd>
    </twig:Tooltip:Content>
</twig:Tooltip>
```

### Disabled Button

Show a tooltip on a disabled button by wrapping it with a span.

```twig {"preview":true}
<twig:Tooltip id="tooltip-disabled-button">
    <twig:Tooltip:Trigger>
        <span class="inline-block w-fit">
            <twig:Button {{ ...tooltip_trigger_attrs }} variant="outline" disabled class="pointer-events-auto">Disabled</twig:Button>
        </span>
    </twig:Tooltip:Trigger>
    <twig:Tooltip:Content>
        <p>This feature is currently unavailable</p>
    </twig:Tooltip:Content>
</twig:Tooltip>
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

```twig {"preview":true}
<div class="flex flex-col gap-8">
    {# Arabic #}
    <div class="flex flex-wrap gap-2" dir="rtl">
        {% for side, label in {left: 'يسار', top: 'أعلى', bottom: 'أسفل', right: 'يمين'} %}
            <twig:Tooltip id="tooltip-rtl-ar-{{ side }}">
                <twig:Tooltip:Trigger>
                    <twig:Button {{ ...tooltip_trigger_attrs }} variant="outline" class="w-fit">
                        {{ label }}
                    </twig:Button>
                </twig:Tooltip:Trigger>
                <twig:Tooltip:Content side="{{ side }}">إضافة إلى المكتبة</twig:Tooltip:Content>
            </twig:Tooltip>
        {% endfor %}
    </div>

    {# Hebrew #}
    <div class="flex flex-wrap gap-2" dir="rtl">
        {% for side, label in {left: 'שמאל', top: 'למעלה', bottom: 'למטה', right: 'ימין'} %}
            <twig:Tooltip id="tooltip-rtl-he-{{ side }}">
                <twig:Tooltip:Trigger>
                    <twig:Button {{ ...tooltip_trigger_attrs }} variant="outline" class="w-fit">
                        {{ label }}
                    </twig:Button>
                </twig:Tooltip:Trigger>
                <twig:Tooltip:Content side="{{ side }}">הוסף לרשימה</twig:Tooltip:Content>
            </twig:Tooltip>
        {% endfor %}
    </div>
</div>
```

## API Reference

::: api-reference
