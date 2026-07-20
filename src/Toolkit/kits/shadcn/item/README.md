# Item

A versatile component for displaying content with media, title, description, and actions.

```twig {"preview":true,"height":"250px"}
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

## Installation

::: installation

## Usage

```twig
<twig:Item>
    <twig:Item:Media variant="icon">
        <twig:ux:icon name="lucide:inbox" />
    </twig:Item:Media>
    <twig:Item:Content>
        <twig:Item:Title>Title</twig:Item:Title>
        <twig:Item:Description>Description</twig:Item:Description>
    </twig:Item:Content>
    <twig:Item:Actions>
        <twig:Button>Action</twig:Button>
    </twig:Item:Actions>
</twig:Item>
```

## Examples

### Variant

Use the `variant` prop to change the visual style of the item.

```twig {"preview":true,"height":"400px"}
<div class="flex w-full max-w-md flex-col gap-6">
    <twig:Item>
        <twig:Item:Media variant="icon">
            <twig:ux:icon name="lucide:inbox" />
        </twig:Item:Media>
        <twig:Item:Content>
            <twig:Item:Title>Default Variant</twig:Item:Title>
            <twig:Item:Description>
                Transparent background with no border.
            </twig:Item:Description>
        </twig:Item:Content>
    </twig:Item>
    <twig:Item variant="outline">
        <twig:Item:Media variant="icon">
            <twig:ux:icon name="lucide:inbox" />
        </twig:Item:Media>
        <twig:Item:Content>
            <twig:Item:Title>Outline Variant</twig:Item:Title>
            <twig:Item:Description>
                Outlined style with a visible border.
            </twig:Item:Description>
        </twig:Item:Content>
    </twig:Item>
    <twig:Item variant="muted">
        <twig:Item:Media variant="icon">
            <twig:ux:icon name="lucide:inbox" />
        </twig:Item:Media>
        <twig:Item:Content>
            <twig:Item:Title>Muted Variant</twig:Item:Title>
            <twig:Item:Description>
                Muted background for secondary content.
            </twig:Item:Description>
        </twig:Item:Content>
    </twig:Item>
</div>
```

### Size

Use the `size` prop to change the size of the item. Available sizes are `default`, `sm`, and `xs`.

```twig {"preview":true,"height":"400px"}
<div class="flex w-full max-w-md flex-col gap-6">
    <twig:Item variant="outline">
        <twig:Item:Media variant="icon">
            <twig:ux:icon name="lucide:inbox" />
        </twig:Item:Media>
        <twig:Item:Content>
            <twig:Item:Title>Default Size</twig:Item:Title>
            <twig:Item:Description>
                The standard size for most use cases.
            </twig:Item:Description>
        </twig:Item:Content>
    </twig:Item>
    <twig:Item variant="outline" size="sm">
        <twig:Item:Media variant="icon">
            <twig:ux:icon name="lucide:inbox" />
        </twig:Item:Media>
        <twig:Item:Content>
            <twig:Item:Title>Small Size</twig:Item:Title>
            <twig:Item:Description>A compact size for dense layouts.</twig:Item:Description>
        </twig:Item:Content>
    </twig:Item>
    <twig:Item variant="outline" size="xs">
        <twig:Item:Media variant="icon">
            <twig:ux:icon name="lucide:inbox" />
        </twig:Item:Media>
        <twig:Item:Content>
            <twig:Item:Title>Extra Small Size</twig:Item:Title>
            <twig:Item:Description>The most compact size available.</twig:Item:Description>
        </twig:Item:Content>
    </twig:Item>
</div>
```

### Icon

Use `Item:Media` with `variant="icon"` to display an icon.

```twig {"preview":true}
<div class="flex w-full max-w-lg flex-col gap-6">
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

### Avatar

Use `Item:Media` to display an avatar.

```twig {"preview":true,"height":"300px"}
<div class="flex w-full max-w-lg flex-col gap-6">
    <twig:Item variant="outline">
        <twig:Item:Media>
            <twig:Avatar class="size-10">
                <twig:Avatar:Image src="https://github.com/evilrabbit.png" alt="ER" />
                <twig:Avatar:Fallback>ER</twig:Avatar:Fallback>
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
                <twig:ux:icon name="lucide:plus" />
            </twig:Button>
        </twig:Item:Actions>
    </twig:Item>

    <twig:Item variant="outline">
        <twig:Item:Media>
            <div class="flex -space-x-2 *:data-[slot=avatar]:ring-2 *:data-[slot=avatar]:ring-background *:data-[slot=avatar]:grayscale">
                <twig:Avatar class="hidden sm:flex">
                    <twig:Avatar:Image src="https://github.com/shadcn.png" alt="@shadcn" />
                    <twig:Avatar:Fallback>CN</twig:Avatar:Fallback>
                </twig:Avatar>
                <twig:Avatar class="hidden sm:flex">
                    <twig:Avatar:Image src="https://github.com/maxleiter.png" alt="@maxleiter" />
                    <twig:Avatar:Fallback>LR</twig:Avatar:Fallback>
                </twig:Avatar>
                <twig:Avatar>
                    <twig:Avatar:Image src="https://github.com/evilrabbit.png" alt="@evilrabbit" />
                    <twig:Avatar:Fallback>ER</twig:Avatar:Fallback>
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

### Image

Use `Item:Media` with `variant="image"` to display an image.

```twig {"preview":true,"height":"350px"}
<div class="flex w-full max-w-md flex-col gap-6">
    <twig:Item:Group class="gap-4">
        <twig:Item variant="outline" as="a" href="#" role="listitem">
            <twig:Item:Media variant="image">
                <img src="https://avatar.vercel.sh/Midnight+City+Lights" alt="Midnight City Lights" class="object-cover grayscale" />
            </twig:Item:Media>
            <twig:Item:Content>
                <twig:Item:Title>Midnight City Lights - <span class="text-muted-foreground">Electric Nights</span></twig:Item:Title>
                <twig:Item:Description>Neon Dreams</twig:Item:Description>
            </twig:Item:Content>
            <twig:Item:Content class="flex-none text-center">
                <twig:Item:Description>3:45</twig:Item:Description>
            </twig:Item:Content>
        </twig:Item>
        <twig:Item variant="outline" as="a" href="#" role="listitem">
            <twig:Item:Media variant="image">
                <img src="https://avatar.vercel.sh/Coffee+Shop" alt="Coffee Shop Conversations" class="object-cover grayscale" />
            </twig:Item:Media>
            <twig:Item:Content>
                <twig:Item:Title>Coffee Shop Conversations - <span class="text-muted-foreground">Urban Stories</span></twig:Item:Title>
                <twig:Item:Description>The Morning Brew</twig:Item:Description>
            </twig:Item:Content>
            <twig:Item:Content class="flex-none text-center">
                <twig:Item:Description>4:05</twig:Item:Description>
            </twig:Item:Content>
        </twig:Item>
        <twig:Item variant="outline" as="a" href="#" role="listitem">
            <twig:Item:Media variant="image">
                <img src="https://avatar.vercel.sh/Digital+Rain" alt="Digital Rain" class="object-cover grayscale" />
            </twig:Item:Media>
            <twig:Item:Content>
                <twig:Item:Title>Digital Rain - <span class="text-muted-foreground">Binary Beats</span></twig:Item:Title>
                <twig:Item:Description>Cyber Symphony</twig:Item:Description>
            </twig:Item:Content>
            <twig:Item:Content class="flex-none text-center">
                <twig:Item:Description>3:30</twig:Item:Description>
            </twig:Item:Content>
        </twig:Item>
    </twig:Item:Group>
</div>
```

### Group

Use `Item:Group` to group related items together.

```twig {"preview":true,"height":"350px"}
<twig:Item:Group class="max-w-sm">
    <twig:Item variant="outline">
        <twig:Item:Media>
            <twig:Avatar>
                <twig:Avatar:Image src="https://github.com/shadcn.png" alt="@shadcn" class="grayscale" />
                <twig:Avatar:Fallback>S</twig:Avatar:Fallback>
            </twig:Avatar>
        </twig:Item:Media>
        <twig:Item:Content class="gap-1">
            <twig:Item:Title>shadcn</twig:Item:Title>
            <twig:Item:Description>shadcn@vercel.com</twig:Item:Description>
        </twig:Item:Content>
        <twig:Item:Actions>
            <twig:Button variant="ghost" size="icon" class="rounded-full">
                <twig:ux:icon name="lucide:plus" />
            </twig:Button>
        </twig:Item:Actions>
    </twig:Item>
    <twig:Item variant="outline">
        <twig:Item:Media>
            <twig:Avatar>
                <twig:Avatar:Image src="https://github.com/maxleiter.png" alt="@maxleiter" class="grayscale" />
                <twig:Avatar:Fallback>M</twig:Avatar:Fallback>
            </twig:Avatar>
        </twig:Item:Media>
        <twig:Item:Content class="gap-1">
            <twig:Item:Title>maxleiter</twig:Item:Title>
            <twig:Item:Description>maxleiter@vercel.com</twig:Item:Description>
        </twig:Item:Content>
        <twig:Item:Actions>
            <twig:Button variant="ghost" size="icon" class="rounded-full">
                <twig:ux:icon name="lucide:plus" />
            </twig:Button>
        </twig:Item:Actions>
    </twig:Item>
    <twig:Item variant="outline">
        <twig:Item:Media>
            <twig:Avatar>
                <twig:Avatar:Image src="https://github.com/evilrabbit.png" alt="@evilrabbit" class="grayscale" />
                <twig:Avatar:Fallback>E</twig:Avatar:Fallback>
            </twig:Avatar>
        </twig:Item:Media>
        <twig:Item:Content class="gap-1">
            <twig:Item:Title>evilrabbit</twig:Item:Title>
            <twig:Item:Description>evilrabbit@vercel.com</twig:Item:Description>
        </twig:Item:Content>
        <twig:Item:Actions>
            <twig:Button variant="ghost" size="icon" class="rounded-full">
                <twig:ux:icon name="lucide:plus" />
            </twig:Button>
        </twig:Item:Actions>
    </twig:Item>
</twig:Item:Group>
```

### Header

Use `Item:Header` to add a header above the item content.

```twig {"preview":true,"height":"400px"}
<div class="flex w-full max-w-xl flex-col gap-6">
    <twig:Item:Group class="grid grid-cols-3 gap-4">
        <twig:Item variant="outline">
            <twig:Item:Header>
                <img
                    src="https://images.unsplash.com/photo-1650804068570-7fb2e3dbf888?q=80&w=640&auto=format&fit=crop"
                    alt="v0-1.5-sm"
                    class="aspect-square w-full rounded-sm object-cover"
                />
            </twig:Item:Header>
            <twig:Item:Content>
                <twig:Item:Title>v0-1.5-sm</twig:Item:Title>
                <twig:Item:Description>Everyday tasks and UI generation.</twig:Item:Description>
            </twig:Item:Content>
        </twig:Item>
        <twig:Item variant="outline">
            <twig:Item:Header>
                <img
                    src="https://images.unsplash.com/photo-1610280777472-54133d004c8c?q=80&w=640&auto=format&fit=crop"
                    alt="v0-1.5-lg"
                    class="aspect-square w-full rounded-sm object-cover"
                />
            </twig:Item:Header>
            <twig:Item:Content>
                <twig:Item:Title>v0-1.5-lg</twig:Item:Title>
                <twig:Item:Description>Advanced thinking or reasoning.</twig:Item:Description>
            </twig:Item:Content>
        </twig:Item>
        <twig:Item variant="outline">
            <twig:Item:Header>
                <img
                    src="https://images.unsplash.com/photo-1602146057681-08560aee8cde?q=80&w=640&auto=format&fit=crop"
                    alt="v0-2.0-mini"
                    class="aspect-square w-full rounded-sm object-cover"
                />
            </twig:Item:Header>
            <twig:Item:Content>
                <twig:Item:Title>v0-2.0-mini</twig:Item:Title>
                <twig:Item:Description>Open Source model for everyone.</twig:Item:Description>
            </twig:Item:Content>
        </twig:Item>
    </twig:Item:Group>
</div>
```

### Link

Use the `as` prop to render the item as a link. The hover and focus states will be applied to the anchor element.

```twig {"preview":true,"height":"300px"}
<div class="flex w-full max-w-md flex-col gap-4">
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

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

```twig {"preview":true,"height":"400px"}
<div class="flex flex-col gap-8 w-full items-center">
    {# Arabic #}
    <div class="flex w-full max-w-md flex-col gap-6" dir="rtl">
        <twig:Item variant="outline" dir="rtl">
            <twig:Item:Content>
                <twig:Item:Title>عنصر أساسي</twig:Item:Title>
                <twig:Item:Description>عنصر بسيط يحتوي على عنوان ووصف.</twig:Item:Description>
            </twig:Item:Content>
            <twig:Item:Actions>
                <twig:Button variant="outline" size="sm">إجراء</twig:Button>
            </twig:Item:Actions>
        </twig:Item>
        <twig:Item variant="outline" size="sm" as="a" href="#" dir="rtl">
            <twig:Item:Media>
                <twig:ux:icon name="lucide:badge-check" class="size-5" />
            </twig:Item:Media>
            <twig:Item:Content>
                <twig:Item:Title>تم التحقق من ملفك الشخصي.</twig:Item:Title>
            </twig:Item:Content>
            <twig:Item:Actions>
                <twig:ux:icon name="lucide:chevron-right" class="size-4" />
            </twig:Item:Actions>
        </twig:Item>
    </div>

    {# Hebrew #}
    <div class="flex w-full max-w-md flex-col gap-6" dir="rtl">
        <twig:Item variant="outline" dir="rtl">
            <twig:Item:Content>
                <twig:Item:Title>ערך בסיסי</twig:Item:Title>
                <twig:Item:Description>ערך עם כותרת ותיאור.</twig:Item:Description>
            </twig:Item:Content>
            <twig:Item:Actions>
                <twig:Button variant="outline" size="sm">בצע</twig:Button>
            </twig:Item:Actions>
        </twig:Item>
        <twig:Item variant="outline" size="sm" as="a" href="#" dir="rtl">
            <twig:Item:Media>
                <twig:ux:icon name="lucide:badge-check" class="size-5" />
            </twig:Item:Media>
            <twig:Item:Content>
                <twig:Item:Title>החשבון שלך אומת.</twig:Item:Title>
            </twig:Item:Content>
            <twig:Item:Actions>
                <twig:ux:icon name="lucide:chevron-right" class="size-4" />
            </twig:Item:Actions>
        </twig:Item>
    </div>
</div>
```

## API Reference

::: api-reference
