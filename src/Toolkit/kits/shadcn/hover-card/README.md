# Hover Card

For sighted users to preview content available behind a link.

```twig {"preview":true}
<div class="flex items-start justify-center" style="min-height: 120px">
    <twig:HoverCard openDelay="10" closeDelay="100">
        <twig:HoverCard:Trigger>
            <twig:Button variant="link" {{ ...hover_card_trigger_attrs }}>Hover Here</twig:Button>
        </twig:HoverCard:Trigger>
        <twig:HoverCard:Content class="flex w-64 flex-col gap-0.5">
            <div class="font-semibold">@symfony</div>
            <div>The PHP framework for web applications — created by @fabpot.</div>
            <div class="mt-1 text-xs text-muted-foreground">Joined October 2010</div>
        </twig:HoverCard:Content>
    </twig:HoverCard>
</div>
```

## Installation

::: installation

## Usage

```twig
<twig:HoverCard>
    <twig:HoverCard:Trigger>
        <a href="#" class="underline" {{ ...hover_card_trigger_attrs }}>@symfony</a>
    </twig:HoverCard:Trigger>
    <twig:HoverCard:Content>
        The Symfony PHP framework — official organization on GitHub.
    </twig:HoverCard:Content>
</twig:HoverCard>
```

## Examples

### Basic

```twig {"preview":true}
<div class="flex items-start justify-center" style="min-height: 120px">
    <twig:HoverCard openDelay="10" closeDelay="100">
        <twig:HoverCard:Trigger>
            <twig:Button variant="link" {{ ...hover_card_trigger_attrs }}>Hover Here</twig:Button>
        </twig:HoverCard:Trigger>
        <twig:HoverCard:Content class="flex w-64 flex-col gap-0.5">
            <div class="font-semibold">@symfony</div>
            <div>The PHP framework for web applications — created by @fabpot.</div>
            <div class="mt-1 text-xs text-muted-foreground">Joined October 2010</div>
        </twig:HoverCard:Content>
    </twig:HoverCard>
</div>
```

### Sides

```twig {"preview":true}
<div class="flex flex-wrap items-center justify-center gap-2" style="min-height: 204px">
    {% for side in ['left', 'top', 'bottom', 'right'] %}
        <twig:HoverCard openDelay="100" closeDelay="100">
            <twig:HoverCard:Trigger>
                <twig:Button variant="outline" class="capitalize" {{ ...hover_card_trigger_attrs }}>{{ side }}</twig:Button>
            </twig:HoverCard:Trigger>
            <twig:HoverCard:Content side="{{ side }}">
                <div class="flex flex-col gap-1">
                    <h4 class="font-medium">Hover Card</h4>
                    <p>This hover card appears on the {{ side }} side of the trigger.</p>
                </div>
            </twig:HoverCard:Content>
        </twig:HoverCard>
    {% endfor %}
</div>
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

```twig {"preview":true}
<div class="flex flex-col items-center justify-center gap-24 py-12" style="min-height: 304px">
    {# Arabic #}
    <div class="flex flex-wrap justify-center gap-2" dir="rtl">
        {% for side, label in {left: 'يسار', top: 'أعلى', bottom: 'أسفل', right: 'يمين'} %}
            <twig:HoverCard openDelay="10" closeDelay="100">
                <twig:HoverCard:Trigger>
                    <twig:Button variant="outline" {{ ...hover_card_trigger_attrs }}>{{ label }}</twig:Button>
                </twig:HoverCard:Trigger>
                <twig:HoverCard:Content side="{{ side }}" class="flex w-64 flex-col gap-1">
                    <div class="font-semibold">سماعات لاسلكية</div>
                    <div class="text-sm text-muted-foreground">٩٩.٩٩ $</div>
                </twig:HoverCard:Content>
            </twig:HoverCard>
        {% endfor %}
    </div>

    {# Hebrew #}
    <div class="flex flex-wrap justify-center gap-2" dir="rtl">
        {% for side, label in {left: 'שמאל', top: 'למעלה', bottom: 'למטה', right: 'ימין'} %}
            <twig:HoverCard openDelay="10" closeDelay="100">
                <twig:HoverCard:Trigger>
                    <twig:Button variant="outline" {{ ...hover_card_trigger_attrs }}>{{ label }}</twig:Button>
                </twig:HoverCard:Trigger>
                <twig:HoverCard:Content side="{{ side }}" class="flex w-64 flex-col gap-1">
                    <div class="font-semibold">אוזניות אלחוטיות</div>
                    <div class="text-sm text-muted-foreground">99.99 $</div>
                </twig:HoverCard:Content>
            </twig:HoverCard>
        {% endfor %}
    </div>
</div>
```

## API Reference

::: api-reference
