# Examples

## Default

```twig {"preview":true,"height":"400px"}
<div class="flex w-full max-w-md flex-col gap-6">
    <twig:Item variant="outline">
        <twig:Item:Content>
            <twig:Item:Title>Basic Item</twig:Item:Title>
            <twig:Item:Description>
                A simple item with title and description.
            </twig:Item:Description>
        </twig:Item:Content>
        <twig:Item:Actions>
            <twig:Button variant="outline" size="sm">
                Action
            </twig:Button>
        </twig:Item:Actions>
    </twig:Item>

    <twig:Item variant="outline" size="sm" as="a" href="#">
        <twig:Item:Media>
            <twig:ux:icon name="lucide:badge-check" class="size-5" />
        </twig:Item:Media>
        <twig:Item:Content>
            <twig:Item:Title>Your profile has been verified.</twig:Item:Title>
        </twig:Item:Content>
        <twig:Item:Actions>
            <twig:ux:icon name="lucide:chevron-right" class="size-4" />
        </twig:Item:Actions>
    </twig:Item>
</div>
```

## Variants

```twig {"preview":true,"height":"400px"}
<div class="flex flex-col gap-6">
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

## Size

```twig {"preview":true,"height":"400px"}
<div class="flex w-full max-w-md flex-col gap-6">
    <twig:Item variant="outline">
        <twig:Item:Content>
            <twig:Item:Title>Basic Item</twig:Item:Title>
            <twig:Item:Description>
                A simple item with title and description.
            </twig:Item:Description>
        </twig:Item:Content>
        <twig:Item:Actions>
            <twig:Button variant="outline" size="sm">
                Action
            </twig:Button>
        </twig:Item:Actions>
    </twig:Item>

    <twig:Item variant="outline" size="sm" as="a" href="#">
        <twig:Item:Media>
            <twig:ux:icon name="lucide:badge-check" class="size-5" />
        </twig:Item:Media>
        <twig:Item:Content>
            <twig:Item:Title>Your profile has been verified.</twig:Item:Title>
        </twig:Item:Content>
        <twig:Item:Actions>
            <twig:ux:icon name="lucide:chevron-right" class="size-4" />
        </twig:Item:Actions>
    </twig:Item>
</div>
```

## Icon

```twig {"preview":true}
<div class="flex w-full max-w-lg flex-col gap-6">
    <twig:Item variant="outline">
        <twig:Item:Media variant="icon">
            <twig:ux:icon name="lucide:shield-alert" class="size-4" />
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

## Avatar

```twig {"preview":true,"height":"400px"}
<div class="flex w-full max-w-lg flex-col gap-6">
    <twig:Item variant="outline">
        <twig:Item:Media variant="icon">
            <twig:Avatar>
                <twig:Avatar:Image src="https://github.com/evilrabbit.png" alt="ER" />
            </twig:Avatar>
        </twig:Item:Media>
        <twig:Item:Content>
            <twig:Item:Title>Evil Rabbit</twig:Item:Title>
            <twig:Item:Description>
                Last seen 5 months ago
            </twig:Item:Description>
        </twig:Item:Content>
        <twig:Item:Actions>
            <twig:Button size="icon-sm" variant="outline" class="rounded-full" aria-label="Invite">
                <twig:ux:icon name="lucide:plus" class="size-4" />
            </twig:Button>
        </twig:Item:Actions>
    </twig:Item>

    <twig:Item variant="outline">
        <twig:Item:Media>
          <div class="*:data-[slot=avatar]:ring-background flex -space-x-2 *:data-[slot=avatar]:ring-2 *:data-[slot=avatar]:grayscale">
            <twig:Avatar class="hidden sm:flex">
              <twig:Avatar:Image src="https://github.com/shadcn.png" alt="@shadcn" />
            </twig:Avatar>
            <twig:Avatar class="hidden sm:flex">
              <twig:Avatar:Image src="https://github.com/maxleiter.png" alt="@maxleiter" />
            </twig:Avatar>
            <twig:Avatar>
              <twig:Avatar:Image src="https://github.com/evilrabbit.png" alt="@evilrabbit" />
            </twig:Avatar>
          </div>
        </twig:Item:Media>
        <twig:Item:Content>
            <twig:Item:Title>No Team Members</twig:Item:Title>
            <twig:Item:Description>
                Invite your team to collaborate on this project.
            </twig:Item:Description>
        </twig:Item:Content>
        <twig:Item:Actions>
            <twig:Button size="sm" variant="outline">
                Invite
            </twig:Button>
        </twig:Item:Actions>
    </twig:Item>
</div>
```

## Link

```twig {"preview":true,"height":"400px"}
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
