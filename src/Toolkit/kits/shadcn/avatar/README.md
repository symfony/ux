# Avatar

An image element with a fallback for representing the user.

```twig {"preview":true}
<div class="flex flex-row flex-wrap items-center gap-6 md:gap-12">
    <twig:Avatar>
        <twig:Avatar:Image
            src="https://github.com/shadcn.png"
            alt="@shadcn"
            class="grayscale"
        />
        <twig:Avatar:Fallback>CN</twig:Avatar:Fallback>
    </twig:Avatar>
    <twig:Avatar>
        <twig:Avatar:Image src="https://github.com/evilrabbit.png" alt="@evilrabbit" />
        <twig:Avatar:Fallback>ER</twig:Avatar:Fallback>
        <twig:Avatar:Badge class="bg-green-600 dark:bg-green-800" />
    </twig:Avatar>
    <twig:Avatar:Group class="grayscale">
        <twig:Avatar>
            <twig:Avatar:Image src="https://github.com/shadcn.png" alt="@shadcn" />
            <twig:Avatar:Fallback>CN</twig:Avatar:Fallback>
        </twig:Avatar>
        <twig:Avatar>
            <twig:Avatar:Image src="https://github.com/maxleiter.png" alt="@maxleiter" />
            <twig:Avatar:Fallback>LR</twig:Avatar:Fallback>
        </twig:Avatar>
        <twig:Avatar>
            <twig:Avatar:Image src="https://github.com/evilrabbit.png" alt="@evilrabbit" />
            <twig:Avatar:Fallback>ER</twig:Avatar:Fallback>
        </twig:Avatar>
        <twig:Avatar:GroupCount>+3</twig:Avatar:GroupCount>
    </twig:Avatar:Group>
</div>
```

## Installation

::: installation

## Usage

```twig
<twig:Avatar>
    <twig:Avatar:Image src="https://github.com/shadcn.png" />
    <twig:Avatar:Fallback>CN</twig:Avatar:Fallback>
</twig:Avatar>
```

## Examples

### Basic

A basic avatar component with an image and a fallback.

```twig {"preview":true}
<twig:Avatar>
    <twig:Avatar:Image
        src="https://github.com/shadcn.png"
        alt="@shadcn"
        class="grayscale"
    />
    <twig:Avatar:Fallback>CN</twig:Avatar:Fallback>
</twig:Avatar>
```

### Badge

Use the `Avatar:Badge` component to add a badge to the avatar. The badge is positioned at the bottom right of the avatar.

```twig {"preview":true}
<twig:Avatar>
    <twig:Avatar:Image src="https://github.com/shadcn.png" alt="@shadcn" />
    <twig:Avatar:Fallback>CN</twig:Avatar:Fallback>
    <twig:Avatar:Badge class="bg-green-600 dark:bg-green-800" />
</twig:Avatar>
```

### Badge with Icon

You can also use an icon inside `Avatar:Badge`.

```twig {"preview":true}
<twig:Avatar class="grayscale">
    <twig:Avatar:Image src="https://github.com/pranathip.png" alt="@pranathip" />
    <twig:Avatar:Fallback>PP</twig:Avatar:Fallback>
    <twig:Avatar:Badge>
        <twig:ux:icon name="lucide:plus" />
    </twig:Avatar:Badge>
</twig:Avatar>
```

### Avatar Group

Use the `Avatar:Group` component to add a group of avatars.

```twig {"preview":true}
<twig:Avatar:Group class="grayscale">
    <twig:Avatar>
        <twig:Avatar:Image src="https://github.com/shadcn.png" alt="@shadcn" />
        <twig:Avatar:Fallback>CN</twig:Avatar:Fallback>
    </twig:Avatar>
    <twig:Avatar>
        <twig:Avatar:Image src="https://github.com/maxleiter.png" alt="@maxleiter" />
        <twig:Avatar:Fallback>LR</twig:Avatar:Fallback>
    </twig:Avatar>
    <twig:Avatar>
        <twig:Avatar:Image src="https://github.com/evilrabbit.png" alt="@evilrabbit" />
        <twig:Avatar:Fallback>ER</twig:Avatar:Fallback>
    </twig:Avatar>
</twig:Avatar:Group>
```

### Avatar Group Count

Use `Avatar:GroupCount` to add a count to the group.

```twig {"preview":true}
<twig:Avatar:Group class="grayscale">
    <twig:Avatar>
        <twig:Avatar:Image src="https://github.com/shadcn.png" alt="@shadcn" />
        <twig:Avatar:Fallback>CN</twig:Avatar:Fallback>
    </twig:Avatar>
    <twig:Avatar>
        <twig:Avatar:Image src="https://github.com/maxleiter.png" alt="@maxleiter" />
        <twig:Avatar:Fallback>LR</twig:Avatar:Fallback>
    </twig:Avatar>
    <twig:Avatar>
        <twig:Avatar:Image src="https://github.com/evilrabbit.png" alt="@evilrabbit" />
        <twig:Avatar:Fallback>ER</twig:Avatar:Fallback>
    </twig:Avatar>
    <twig:Avatar:GroupCount>+3</twig:Avatar:GroupCount>
</twig:Avatar:Group>
```

### Avatar Group with Icon

You can also use an icon inside `Avatar:GroupCount`.

```twig {"preview":true}
<twig:Avatar:Group class="grayscale">
    <twig:Avatar>
        <twig:Avatar:Image src="https://github.com/shadcn.png" alt="@shadcn" />
        <twig:Avatar:Fallback>CN</twig:Avatar:Fallback>
    </twig:Avatar>
    <twig:Avatar>
        <twig:Avatar:Image src="https://github.com/maxleiter.png" alt="@maxleiter" />
        <twig:Avatar:Fallback>LR</twig:Avatar:Fallback>
    </twig:Avatar>
    <twig:Avatar>
        <twig:Avatar:Image src="https://github.com/evilrabbit.png" alt="@evilrabbit" />
        <twig:Avatar:Fallback>ER</twig:Avatar:Fallback>
    </twig:Avatar>
    <twig:Avatar:GroupCount>
        <twig:ux:icon name="lucide:plus" />
    </twig:Avatar:GroupCount>
</twig:Avatar:Group>
```

### Sizes

Use the `size` prop to change the size of the avatar.

```twig {"preview":true}
<div class="flex flex-wrap items-center gap-2 grayscale">
    <twig:Avatar size="sm">
        <twig:Avatar:Image src="https://github.com/shadcn.png" alt="@shadcn" />
        <twig:Avatar:Fallback>CN</twig:Avatar:Fallback>
    </twig:Avatar>
    <twig:Avatar>
        <twig:Avatar:Image src="https://github.com/shadcn.png" alt="@shadcn" />
        <twig:Avatar:Fallback>CN</twig:Avatar:Fallback>
    </twig:Avatar>
    <twig:Avatar size="lg">
        <twig:Avatar:Image src="https://github.com/shadcn.png" alt="@shadcn" />
        <twig:Avatar:Fallback>CN</twig:Avatar:Fallback>
    </twig:Avatar>
</div>
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

```twig {"preview":true}
<div class="flex flex-col gap-8">
    <div class="flex flex-row flex-wrap items-center gap-6 md:gap-12" dir="rtl">
        <twig:Avatar>
            <twig:Avatar:Image src="https://github.com/shadcn.png" alt="@shadcn" class="grayscale" />
            <twig:Avatar:Fallback>CN</twig:Avatar:Fallback>
        </twig:Avatar>
        <twig:Avatar>
            <twig:Avatar:Image src="https://github.com/evilrabbit.png" alt="@evilrabbit" />
            <twig:Avatar:Fallback>ER</twig:Avatar:Fallback>
            <twig:Avatar:Badge class="bg-green-600 dark:bg-green-800" />
        </twig:Avatar>
        <twig:Avatar:Group class="grayscale">
            <twig:Avatar>
                <twig:Avatar:Image src="https://github.com/shadcn.png" alt="@shadcn" />
                <twig:Avatar:Fallback>CN</twig:Avatar:Fallback>
            </twig:Avatar>
            <twig:Avatar>
                <twig:Avatar:Image src="https://github.com/maxleiter.png" alt="@maxleiter" />
                <twig:Avatar:Fallback>LR</twig:Avatar:Fallback>
            </twig:Avatar>
            <twig:Avatar>
                <twig:Avatar:Image src="https://github.com/evilrabbit.png" alt="@evilrabbit" />
                <twig:Avatar:Fallback>ER</twig:Avatar:Fallback>
            </twig:Avatar>
            <twig:Avatar:GroupCount>+٣</twig:Avatar:GroupCount>
        </twig:Avatar:Group>
    </div>
    <div class="flex flex-row flex-wrap items-center gap-6 md:gap-12" dir="rtl">
        <twig:Avatar>
            <twig:Avatar:Image src="https://github.com/shadcn.png" alt="@shadcn" class="grayscale" />
            <twig:Avatar:Fallback>CN</twig:Avatar:Fallback>
        </twig:Avatar>
        <twig:Avatar>
            <twig:Avatar:Image src="https://github.com/evilrabbit.png" alt="@evilrabbit" />
            <twig:Avatar:Fallback>ER</twig:Avatar:Fallback>
            <twig:Avatar:Badge class="bg-green-600 dark:bg-green-800" />
        </twig:Avatar>
        <twig:Avatar:Group class="grayscale">
            <twig:Avatar>
                <twig:Avatar:Image src="https://github.com/shadcn.png" alt="@shadcn" />
                <twig:Avatar:Fallback>CN</twig:Avatar:Fallback>
            </twig:Avatar>
            <twig:Avatar>
                <twig:Avatar:Image src="https://github.com/maxleiter.png" alt="@maxleiter" />
                <twig:Avatar:Fallback>LR</twig:Avatar:Fallback>
            </twig:Avatar>
            <twig:Avatar>
                <twig:Avatar:Image src="https://github.com/evilrabbit.png" alt="@evilrabbit" />
                <twig:Avatar:Fallback>ER</twig:Avatar:Fallback>
            </twig:Avatar>
            <twig:Avatar:GroupCount>+3</twig:Avatar:GroupCount>
        </twig:Avatar:Group>
    </div>
</div>
```

## API Reference

::: api-reference
