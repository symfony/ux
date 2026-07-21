# Dropdown

The dropdown component can be used to show a list of menu items when clicking on an element such as a button and hiding it when focusing outside of the triggering element.

```twig {"preview":true}
<twig:Dropdown id="demo" open>
    <twig:Dropdown:Trigger>
        <twig:Button {{ ...dropdown_trigger_attrs }}>
            Dropdown button
            <twig:ux:icon name="flowbite:chevron-down-outline" class="size-4" aria-hidden="true" />
        </twig:Button>
    </twig:Dropdown:Trigger>

    <twig:Dropdown:Content>
        <twig:Dropdown:Group>
            <twig:Dropdown:Item href="#">Dashboard</twig:Dropdown:Item>
            <twig:Dropdown:Item href="#">Settings</twig:Dropdown:Item>
            <twig:Dropdown:Item href="#">Earnings</twig:Dropdown:Item>
            <twig:Dropdown:Item href="#">Sign out</twig:Dropdown:Item>
        </twig:Dropdown:Group>
    </twig:Dropdown:Content>
</twig:Dropdown>
```

## Installation

::: installation

## Usage

```twig
<twig:Dropdown
    id="string"
    open="true | false"
    placement="right | left | top | bottom | right-start | right-end | left-start | left-end | top-start | top-end | bottom-start | bottom-end"
    triggerType="click | hover"
    delay="300"
    offsetDistance="10"
>
    <twig:Dropdown:Trigger>
        <twig:Button {{ ...dropdown_trigger_attrs }}>
            Dropdown
            <twig:ux:icon name="flowbite:chevron-down-outline" class="size-4" aria-hidden="true" />
        </twig:Button>
    </twig:Dropdown:Trigger>

    <twig:Dropdown:Content>
        <twig:Dropdown:Header>
            <span class="block text-sm text-heading font-medium">Bonnie Green</span>
            <span class="block text-sm truncate">name@flowbite.com</span>
        </twig:Dropdown:Header>
        <twig:Dropdown:Group>
            <twig:Dropdown:Item as="a" href="#">Link item</twig:Dropdown:Item>
            <twig:Dropdown:Item as="button">Button item</twig:Dropdown:Item>
            <twig:Dropdown:Sub
                id="string"
                placement="right | left | top | bottom | right-start | right-end | left-start | left-end | top-start | top-end | bottom-start | bottom-end"
                triggerType="click | hover"
                delay="300"
                offsetDistance="10"
            >
                <twig:Dropdown:SubTrigger>
                    Sub menu
                </twig:Dropdown:SubTrigger>

                <twig:Dropdown:Content>
                    <twig:Dropdown:Group>
                        <twig:Dropdown:Item href="#">Sub item</twig:Dropdown:Item>
                        <twig:Dropdown:Item href="#">Sub item</twig:Dropdown:Item>
                    </twig:Dropdown:Group>
                </twig:Dropdown:Content>
            </twig:Dropdown:Sub>
        </twig:Dropdown:Group>
    </twig:Dropdown:Content>
</twig:Dropdown>
```

## Examples

### Dropdown hover

Use the `̀triggerType="{hover|click}"` prop options to set whether the dropdown should be shown when hovering or clicking on the trigger element (ie. button).

There’s a 300ms default delay when showing or hiding the dropdown due to UI/UX reasons and how it may affect the interaction with other components on the page. Generally, we recommend using the `click` method.

```twig {"preview":true,"height":"400px"}
<twig:Dropdown id="hover" triggerType="hover">
    <twig:Dropdown:Trigger>
        <twig:Button {{ ...dropdown_trigger_attrs }}>
            Dropdown button
            <twig:ux:icon name="flowbite:chevron-down-outline" class="size-4" aria-hidden="true" />
        </twig:Button>
    </twig:Dropdown:Trigger>

    <twig:Dropdown:Content>
        <twig:Dropdown:Group>
            <twig:Dropdown:Item href="#">Dashboard</twig:Dropdown:Item>
            <twig:Dropdown:Item href="#">Settings</twig:Dropdown:Item>
            <twig:Dropdown:Item href="#">Earnings</twig:Dropdown:Item>
            <twig:Dropdown:Item href="#">Sign out</twig:Dropdown:Item>
        </twig:Dropdown:Group>
    </twig:Dropdown:Content>
</twig:Dropdown>
```

#### Delay duration

You can use the `delay={milliseconds}` prop options to set the delay on when to show or hide the dropdown menu when using hover. You may want to use this depending on how the users interact with your interface.
In this example we add 500 milliseconds instead of the default 300.

```twig {"preview":true,"height":"400px"}
<twig:Dropdown id="delay" triggerType="hover" delay="500">
    <twig:Dropdown:Trigger>
        <twig:Button {{ ...dropdown_trigger_attrs }}>
            Dropdown button
            <twig:ux:icon name="flowbite:chevron-down-outline" class="size-4" aria-hidden="true" />
        </twig:Button>
    </twig:Dropdown:Trigger>

    <twig:Dropdown:Content>
        <twig:Dropdown:Group>
            <twig:Dropdown:Item href="#">Dashboard</twig:Dropdown:Item>
            <twig:Dropdown:Item href="#">Settings</twig:Dropdown:Item>
            <twig:Dropdown:Item href="#">Earnings</twig:Dropdown:Item>
            <twig:Dropdown:Item href="#">Sign out</twig:Dropdown:Item>
        </twig:Dropdown:Group>
    </twig:Dropdown:Content>
</twig:Dropdown>
```

### Dropdown divider

You can use multiple `Dropdown:Group`, the `Dropdown:Content` add a divider between the groups.

```twig {"preview":true,"height":"600px"}
<twig:Dropdown id="divider">
    <twig:Dropdown:Trigger>
        <twig:Button {{ ...dropdown_trigger_attrs }}>
            Dropdown button
            <twig:ux:icon name="flowbite:chevron-down-outline" class="size-4" aria-hidden="true" />
        </twig:Button>
    </twig:Dropdown:Trigger>

    <twig:Dropdown:Content>
        <twig:Dropdown:Group>
            <twig:Dropdown:Item href="#">Dashboard</twig:Dropdown:Item>
            <twig:Dropdown:Item href="#">Settings</twig:Dropdown:Item>
            <twig:Dropdown:Item href="#">Earnings</twig:Dropdown:Item>
            <twig:Dropdown:Item href="#">Sign out</twig:Dropdown:Item>
        </twig:Dropdown:Group>
        <twig:Dropdown:Group>
            <twig:Dropdown:Item href="#">Separated link</twig:Dropdown:Item>
        </twig:Dropdown:Group>
    </twig:Dropdown:Content>
</twig:Dropdown>
```

### With header

Use this example to show extra information outside of the list of menu items inside the dropdown.

```twig {"preview":true,"height":"600px"}
<twig:Dropdown id="header">
    <twig:Dropdown:Trigger>
        <twig:Button {{ ...dropdown_trigger_attrs }}>
            Dropdown button
            <twig:ux:icon name="flowbite:chevron-down-outline" class="size-4" aria-hidden="true" />
        </twig:Button>
    </twig:Dropdown:Trigger>

    <twig:Dropdown:Content>
        <twig:Dropdown:Header class="flex items-center space-x-1.5">
            <twig:Avatar>
                <twig:Avatar:Image src="https://flowbite.com/docs/images/people/profile-picture-5.jpg" alt="Bonnie image" />
                <twig:Avatar:Fallback>BG</twig:Avatar:Fallback>
            </twig:Avatar>
            <div class="text-sm">
                <div class="font-medium text-heading">Bonnie Green</div>
                <div class="truncate text-body">name@flowbite.com</div>
            </div>
            <twig:Badge border="bordered">PRO</twig:Badge>
        </twig:Dropdown:Header>
        <twig:Dropdown:Group>
            <twig:Dropdown:Item href="#">
                <twig:ux:icon name="flowbite:user-outline" class="size-4 me-2" aria-hidden="true" />
                Dashboard
            </twig:Dropdown:Item>
            <twig:Dropdown:Item href="#">
                <twig:ux:icon name="flowbite:adjustments-horizontal-outline" class="size-4 me-2" aria-hidden="true" />
                Settings
            </twig:Dropdown:Item>
            <twig:Dropdown:Item href="#">
                <twig:ux:icon name="flowbite:lock-outline" class="size-4 me-2" aria-hidden="true" />
                Privacy
            </twig:Dropdown:Item>
        </twig:Dropdown:Group>
        <twig:Dropdown:Group>
            <twig:Dropdown:Item class="text-fg-danger" href="#">
                <twig:ux:icon name="flowbite:arrow-left-to-bracket-outline" class="size-4 me-2" aria-hidden="true" />
                Sign out
            </twig:Dropdown:Item>
        </twig:Dropdown:Group>
    </twig:Dropdown:Content>
</twig:Dropdown>
```

### Multi-level dropdown

Use this example to enable multi-level dropdown menus by adding stacked elements inside of each other.

```twig {"preview":true,"height":"600px"}
<twig:Dropdown id="demo" open>
    <twig:Dropdown:Trigger>
        <twig:Button {{ ...dropdown_trigger_attrs }}>
            Dropdown button
            <twig:ux:icon name="flowbite:chevron-down-outline" class="size-4" aria-hidden="true" />
        </twig:Button>
    </twig:Dropdown:Trigger>

    <twig:Dropdown:Content>
        <twig:Dropdown:Group>
            <twig:Dropdown:Item href="#">Dashboard</twig:Dropdown:Item>
            <twig:Dropdown:Sub id="level1">
                <twig:Dropdown:SubTrigger>
                    Settings
                </twig:Dropdown:SubTrigger>

                <twig:Dropdown:Content>
                    <twig:Dropdown:Group>
                        <twig:Dropdown:Item href="#">Submenu item</twig:Dropdown:Item>
                        <twig:Dropdown:Item href="#">Submenu item</twig:Dropdown:Item>
                    </twig:Dropdown:Group>
                </twig:Dropdown:Content>
            </twig:Dropdown:Sub>
            <twig:Dropdown:Item href="#">Earnings</twig:Dropdown:Item>
            <twig:Dropdown:Item href="#">Sign out</twig:Dropdown:Item>
        </twig:Dropdown:Group>
    </twig:Dropdown:Content>
</twig:Dropdown>
```

### With icon

Use the menu icon trigger element on components such as cards as an alternative element to the button.

```twig {"preview":true,"height":"400px"}
<div class="flex justify-center space-x-4 rtl:space-x-reverse">
    <twig:Dropdown id="icon-vt">
        <twig:Dropdown:Trigger>
            <twig:Button variant="ghost" size="icon" {{ ...dropdown_trigger_attrs }}>
                <twig:ux:icon name="flowbite:dots-vertical-outline" class="size-6" aria-hidden="true"/>
            </twig:Button>
        </twig:Dropdown:Trigger>

        <twig:Dropdown:Content>
            <twig:Dropdown:Group>
                <twig:Dropdown:Item href="#">
                    Dashboard
                </twig:Dropdown:Item>
                <twig:Dropdown:Item href="#">
                    Settings
                </twig:Dropdown:Item>
                <twig:Dropdown:Item href="#">
                    Earnings
                </twig:Dropdown:Item>
                <twig:Dropdown:Item href="#">
                    Sign out
                </twig:Dropdown:Item>
            </twig:Dropdown:Group>
        </twig:Dropdown:Content>
    </twig:Dropdown>

    <twig:Dropdown id="icon-hz">
        <twig:Dropdown:Trigger>
            <twig:Button variant="ghost" size="icon" {{ ...dropdown_trigger_attrs }}>
                <twig:ux:icon name="flowbite:dots-horizontal-outline" class="size-6" aria-hidden="true"/>
            </twig:Button>
        </twig:Dropdown:Trigger>

        <twig:Dropdown:Content>
            <twig:Dropdown:Group>
                <twig:Dropdown:Item href="#">
                    Dashboard
                </twig:Dropdown:Item>
                <twig:Dropdown:Item href="#">
                    Settings
                </twig:Dropdown:Item>
                <twig:Dropdown:Item href="#">
                    Earnings
                </twig:Dropdown:Item>
                <twig:Dropdown:Item href="#">
                    Sign out
                </twig:Dropdown:Item>
            </twig:Dropdown:Group>
        </twig:Dropdown:Content>
    </twig:Dropdown>
</div>
```

### Placement

You can also use the `placement={top|right|bottom|left}` prop options to choose the placement of the dropdown menu.
By default the positioning is set to the bottom side of the button.

```twig {"preview":true,"height":"400px"}
<div class="flex flex-wrap items-center gap-4">
    <twig:Dropdown id="placement-top" placement="top">
        <twig:Dropdown:Trigger>
            <twig:Button {{ ...dropdown_trigger_attrs }}>
                Dropdown top
                <twig:ux:icon name="flowbite:chevron-up-outline" class="size-6" aria-hidden="true" />
            </twig:Button>
        </twig:Dropdown:Trigger>
        <twig:Dropdown:Content>
            <twig:Dropdown:Group>
                <twig:Dropdown:Item href="#">Dashboard</twig:Dropdown:Item>
                <twig:Dropdown:Item href="#">Settings</twig:Dropdown:Item>
                <twig:Dropdown:Item href="#">Earnings</twig:Dropdown:Item>
            </twig:Dropdown:Group>
        </twig:Dropdown:Content>
    </twig:Dropdown>

    <twig:Dropdown id="placement-right" placement="right">
        <twig:Dropdown:Trigger>
            <twig:Button {{ ...dropdown_trigger_attrs }}>
                Dropdown right
                <twig:ux:icon name="flowbite:chevron-right-outline" class="size-6" aria-hidden="true" />
            </twig:Button>
        </twig:Dropdown:Trigger>
        <twig:Dropdown:Content>
            <twig:Dropdown:Group>
                <twig:Dropdown:Item href="#">Dashboard</twig:Dropdown:Item>
                <twig:Dropdown:Item href="#">Settings</twig:Dropdown:Item>
                <twig:Dropdown:Item href="#">Earnings</twig:Dropdown:Item>
            </twig:Dropdown:Group>
        </twig:Dropdown:Content>
    </twig:Dropdown>

    <twig:Dropdown id="placement-bottom" placement="bottom">
        <twig:Dropdown:Trigger>
            <twig:Button {{ ...dropdown_trigger_attrs }}>
                Dropdown bottom
                <twig:ux:icon name="flowbite:chevron-down-outline" class="size-6" aria-hidden="true" />
            </twig:Button>
        </twig:Dropdown:Trigger>
        <twig:Dropdown:Content>
            <twig:Dropdown:Group>
                <twig:Dropdown:Item href="#">Dashboard</twig:Dropdown:Item>
                <twig:Dropdown:Item href="#">Settings</twig:Dropdown:Item>
                <twig:Dropdown:Item href="#">Earnings</twig:Dropdown:Item>
            </twig:Dropdown:Group>
        </twig:Dropdown:Content>
    </twig:Dropdown>

    <twig:Dropdown id="placement-left" placement="left">
        <twig:Dropdown:Trigger>
            <twig:Button {{ ...dropdown_trigger_attrs }}>
                <twig:ux:icon name="flowbite:chevron-left-outline" class="size-6" aria-hidden="true" />
                Dropdown left
            </twig:Button>
        </twig:Dropdown:Trigger>
        <twig:Dropdown:Content>
            <twig:Dropdown:Group>
                <twig:Dropdown:Item href="#">Dashboard</twig:Dropdown:Item>
                <twig:Dropdown:Item href="#">Settings</twig:Dropdown:Item>
                <twig:Dropdown:Item href="#">Earnings</twig:Dropdown:Item>
            </twig:Dropdown:Group>
        </twig:Dropdown:Content>
    </twig:Dropdown>
</div>
```

## API Reference

::: api-reference
