# Examples

## Default

```twig {"preview":true}
<div class="flex items-center space-x-2">
    <twig:Item>
        <twig:Item:Content>
            <twig:Item:Title>Default Variant</twig:Item:Title>
            <twig:Item:Description>
                Standard styling with subtle background and borders.
            </twig:Item:Description>
        </twig:Item:Content>
        <twig:Item:Actions>
            <twig:Button variant="outline" size="sm">
                Open
            </twig:Button>
        </twig:Item:Actions>
    </twig:Item>
    <twig:Item variant="outline">
        <twig:Item:Content>
            <twig:Item:Title>Outline Variant</twig:Item:Title>
            <twig:Item:Description>
                Outlined style with clear borders and transparent background.
            </twig:Item:Description>
        </twig:Item:Content>
        <twig:Item:Actions>
            <twig:Button variant="outline" size="sm">
                Open
            </twig:Button>
        </twig:Item:Actions>
    </twig:Item>
    <twig:Item variant="muted">
        <twig:Item:Content>
            <twig:Item:Title>Muted Variant</twig:Item:Title>
            <twig:Item:Description>
                Subdued appearance with muted colors for secondary content.
            </twig:Item:Description>
        </twig:Item:Content>
        <twig:Item:Actions>
            <twig:Button variant="outline" size="sm">
                Open
            </twig:Button>
        </twig:Item:Actions>
    </twig:Item>
</div>
```

## With Icon

```twig {"preview":true}
<div class="grid w-full max-w-sm items-center gap-1.5">
    <twig:Item variant="outline">
        <twig:Item:Media variant="icon">
            <twig:ux:icon name="lucide:shield-alert" />
        </twig:Item:Media>
        <twig:Item:Content>
            <twig:Item:Title>Security Alert</twig:Item:Title>
            <twig:Item:Description>
                New login detected from unknown device.
            </twig:Item:Description>
        </twig:Item:Content>
        <twig:Item:Actions>
            <twig:Button size="sm" variant="outline">
                Review
            </twig:Button>
        </twig:Item:Actions>
    </twig:Item>
</div>
```

## Link

```twig {"preview":true}
<div class="grid w-full max-w-sm items-center gap-1.5">
    <twig:Item as="a" href="#">
        <twig:Item:Content>
            <twig:Item:Title>Visit our documentation</twig:Item:Title>
            <twig:Item:Description>
                Learn how to get started with our components.
            </twig:Item:Description>
        </twig:Item:Content>
        <twig:Item:Actions>
            <twig:ux:icon name="lucide:chevron-right" class="size-4" />
        </twig:Item:Actions>
    </twig:Item>
    <twig:Item variant="outline" as="a" href="#" target="_blank" rel="noopener noreferrer">
        <twig:Item:Content>
            <twig:Item:Title>External resource</twig:Item:Title>
            <twig:Item:Description>
                Opens in a new tab with security attributes.
            </twig:Item:Description>
        </twig:Item:Content>
        <twig:Item:Actions>
            <twig:ux:icon name="lucide:external-link" class="size-4" />
        </twig:Item:Actions>
    </twig:Item>
</div>
```
