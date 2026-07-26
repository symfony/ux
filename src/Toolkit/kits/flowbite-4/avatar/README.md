# Avatar

Use the avatar component to show a visual representation of a user profile using an image element or SVG object based on multiple styles and sizes

```twig {"preview":true}
<div class="flex items-center justify-center gap-4">
    <twig:Avatar>
        <twig:Avatar:Image src="https://flowbite.com/docs/images/people/profile-picture-5.jpg" alt="Default avatar" />
        <twig:Avatar:Fallback>JL</twig:Avatar:Fallback>
    </twig:Avatar>
    <twig:Avatar shape="rounded">
        <twig:Avatar:Image src="https://flowbite.com/docs/images/people/profile-picture-5.jpg" alt="Rounded avatar" />
        <twig:Avatar:Fallback>JL</twig:Avatar:Fallback>
    </twig:Avatar>
</div>
```

## Installation

::: installation

## Usage

```twig
<twig:Avatar
    size="xs | sm | md | lg | xl | 2xl"
    shape="circle | rounded"
    border="none | bordered"
>
    <twig:Avatar:Image src="https://example.com/avatar.jpg" alt="User avatar" />
    <twig:Avatar:Fallback>JL</twig:Avatar:Fallback>
</twig:Avatar>
```

## Examples

### Bordered

Apply a border around the avatar component you can use the `ring-{color}` class from Tailwind CSS.

```twig {"preview":true}
<div class="flex items-center justify-center gap-4">
    <twig:Avatar border="bordered">
        <twig:Avatar:Image src="https://flowbite.com/docs/images/people/profile-picture-5.jpg" alt="Bordered avatar" />
        <twig:Avatar:Fallback>JL</twig:Avatar:Fallback>
    </twig:Avatar>
    <twig:Avatar border="bordered" shape="rounded">
        <twig:Avatar:Image src="https://flowbite.com/docs/images/people/profile-picture-5.jpg" alt="Bordered rounded avatar" />
        <twig:Avatar:Fallback>JL</twig:Avatar:Fallback>
    </twig:Avatar>
</div>
```

### Placeholder

This example can be used to show an icon placeholder or the initials of the user’s first and last name as a placeholder when no profile picture is available.

```twig {"preview":true}
<div class="flex items-center justify-center gap-4">
    <twig:Avatar>
        <twig:Avatar:Fallback />
    </twig:Avatar>
    <twig:Avatar>
        <twig:Avatar:Fallback>JL</twig:Avatar:Fallback>
    </twig:Avatar>
    <twig:Avatar shape="rounded">
        <twig:Avatar:Fallback>JL</twig:Avatar:Fallback>
    </twig:Avatar>
</div>
```

### Dot indicator

Use the `Indicator` component relative to the avatar component as an indicator for the user (eg. online or offline status).

```twig {"preview":true}
<div class="flex items-center justify-center gap-6">
    <div class="relative">
        <twig:Avatar>
            <twig:Avatar:Image src="https://flowbite.com/docs/images/people/profile-picture-5.jpg" alt="Online user" />
            <twig:Avatar:Fallback>JL</twig:Avatar:Fallback>
        </twig:Avatar>
        <twig:Indicator class="absolute top-0 right-0 border-2 border-buffer" variant="success"/>
    </div>
    <div class="relative">
        <twig:Avatar shape="rounded">
            <twig:Avatar:Image src="https://flowbite.com/docs/images/people/profile-picture-5.jpg" alt="Offline user" />
            <twig:Avatar:Fallback>JL</twig:Avatar:Fallback>
        </twig:Avatar>
        <twig:Indicator class="absolute -top-1 -right-1 border-2 border-buffer" variant="danger"/>
    </div>
    <div class="relative">
        <twig:Avatar>
            <twig:Avatar:Image src="https://flowbite.com/docs/images/people/profile-picture-5.jpg" alt="Online user" />
            <twig:Avatar:Fallback>JL</twig:Avatar:Fallback>
        </twig:Avatar>
        <twig:Indicator class="absolute bottom-0 right-0 border-2 border-buffer" variant="success"/>
    </div>
    <div class="relative">
        <twig:Avatar shape="rounded">
            <twig:Avatar:Image src="https://flowbite.com/docs/images/people/profile-picture-5.jpg" alt="Online user" />
            <twig:Avatar:Fallback>JL</twig:Avatar:Fallback>
        </twig:Avatar>
        <twig:Indicator class="absolute -bottom-1 -right-1 border-2 border-buffer" variant="success"/>
    </div>
</div>
```

### Stacked

Use `Avatar:Group` if you want to stack a group of users by overlapping the avatar components.

```twig {"preview":true}
<div class="flex flex-col items-center gap-6">
    <twig:Avatar:Group>
        <twig:Avatar>
            <twig:Avatar:Image src="https://flowbite.com/docs/images/people/profile-picture-5.jpg" alt="" />
            <twig:Avatar:Fallback>JL</twig:Avatar:Fallback>
        </twig:Avatar>
        <twig:Avatar>
            <twig:Avatar:Image src="https://flowbite.com/docs/images/people/profile-picture-2.jpg" alt="" />
            <twig:Avatar:Fallback>BG</twig:Avatar:Fallback>
        </twig:Avatar>
        <twig:Avatar>
            <twig:Avatar:Image src="https://flowbite.com/docs/images/people/profile-picture-3.jpg" alt="" />
            <twig:Avatar:Fallback>TL</twig:Avatar:Fallback>
        </twig:Avatar>
        <twig:Avatar>
            <twig:Avatar:Image src="https://flowbite.com/docs/images/people/profile-picture-4.jpg" alt="" />
            <twig:Avatar:Fallback>RL</twig:Avatar:Fallback>
        </twig:Avatar>
    </twig:Avatar:Group>

    <twig:Avatar:Group>
        <twig:Avatar>
            <twig:Avatar:Image src="https://flowbite.com/docs/images/people/profile-picture-5.jpg" alt="" />
            <twig:Avatar:Fallback>JL</twig:Avatar:Fallback>
        </twig:Avatar>
        <twig:Avatar>
            <twig:Avatar:Image src="https://flowbite.com/docs/images/people/profile-picture-2.jpg" alt="" />
            <twig:Avatar:Fallback>BG</twig:Avatar:Fallback>
        </twig:Avatar>
        <twig:Avatar>
            <twig:Avatar:Image src="https://flowbite.com/docs/images/people/profile-picture-3.jpg" alt="" />
            <twig:Avatar:Fallback>TL</twig:Avatar:Fallback>
        </twig:Avatar>
        <twig:Avatar:GroupCount as="a" href="#">+99</twig:Avatar:GroupCount>
    </twig:Avatar:Group>
</div>
```

### Avatar with text

This example can be used if you want to show additional information in the form of text elements such as the user’s name and join date

```twig {"preview":true}
<div class="flex items-center justify-center gap-2.5">
    <twig:Avatar>
        <twig:Avatar:Image src="https://flowbite.com/docs/images/people/profile-picture-5.jpg" alt="Jese Leos" />
        <twig:Avatar:Fallback>JL</twig:Avatar:Fallback>
    </twig:Avatar>
    <div class="font-medium text-heading">
        <div>Jese Leos</div>
        <div class="text-sm font-normal text-body">Joined in August 2014</div>
    </div>
</div>
```

### User dropdown

Use this example if you want to show a dropdown menu when clicking on the avatar component.

```twig {"preview":true}
<twig:Dropdown id="user-dropdown" placement="bottom-start">
    <twig:Dropdown:Trigger>
        <twig:Avatar class="cursor-pointer" {{ ...dropdown_trigger_attrs }}>
            <twig:Avatar:Image src="https://flowbite.com/docs/images/people/profile-picture-5.jpg" alt="Default avatar" />
            <twig:Avatar:Fallback>JL</twig:Avatar:Fallback>
        </twig:Avatar>
    </twig:Dropdown:Trigger>

    <twig:Dropdown:Content>
        <twig:Dropdown:Header>
            <div class="font-medium">Bonnie Green</div>
            <div class="truncate">name@flowbite.com</div>
        </twig:Dropdown:Header>
        <twig:Dropdown:Group>
            <twig:Dropdown:Item>Dashboard</twig:Dropdown:Item>
            <twig:Dropdown:Item>Settings</twig:Dropdown:Item>
            <twig:Dropdown:Item>Earnings</twig:Dropdown:Item>
            <twig:Dropdown:Item class="text-fg-danger">Sign out</twig:Dropdown:Item>
        </twig:Dropdown:Group>
    </twig:Dropdown:Content>
</twig:Dropdown>
```

### Sizes

Use the `size` prop to change the avatar dimensions.

```twig {"preview":true}
<div class="flex items-center justify-center gap-4">
    <twig:Avatar shape="rounded" size="xs">
        <twig:Avatar:Image src="https://flowbite.com/docs/images/people/profile-picture-5.jpg" alt="Extra small" />
        <twig:Avatar:Fallback>JL</twig:Avatar:Fallback>
    </twig:Avatar>
    <twig:Avatar shape="rounded" size="sm">
        <twig:Avatar:Image src="https://flowbite.com/docs/images/people/profile-picture-5.jpg" alt="Small" />
        <twig:Avatar:Fallback>JL</twig:Avatar:Fallback>
    </twig:Avatar>
    <twig:Avatar shape="rounded" size="md">
        <twig:Avatar:Image src="https://flowbite.com/docs/images/people/profile-picture-5.jpg" alt="Medium" />
        <twig:Avatar:Fallback>JL</twig:Avatar:Fallback>
    </twig:Avatar>
    <twig:Avatar shape="rounded" size="lg">
        <twig:Avatar:Image src="https://flowbite.com/docs/images/people/profile-picture-5.jpg" alt="Large" />
        <twig:Avatar:Fallback>JL</twig:Avatar:Fallback>
    </twig:Avatar>
    <twig:Avatar shape="rounded" size="xl">
        <twig:Avatar:Image src="https://flowbite.com/docs/images/people/profile-picture-5.jpg" alt="Extra large" />
        <twig:Avatar:Fallback>JL</twig:Avatar:Fallback>
    </twig:Avatar>
    <twig:Avatar shape="rounded" size="2xl">
        <twig:Avatar:Image src="https://flowbite.com/docs/images/people/profile-picture-5.jpg" alt="Big" />
        <twig:Avatar:Fallback>JL</twig:Avatar:Fallback>
    </twig:Avatar>
</div>
```

## API Reference

::: api-reference
