# Dropdown

Dropdown can open a menu or any other element when the button is clicked.

```twig {"preview":true}
<div style="min-height: 160px">
    <twig:Dropdown id="dropdown">
        <twig:Dropdown:Trigger>
            <twig:Button class="m-1" {{ ...dropdown_trigger_attrs }}>Click to open</twig:Button>
        </twig:Dropdown:Trigger>
        <twig:Dropdown:Content>
            <ul class="menu w-52 rounded-box bg-base-100 p-2 shadow-sm">
                <li><button>Item 1</button></li>
                <li><button>Item 2</button></li>
            </ul>
        </twig:Dropdown:Content>
    </twig:Dropdown>
</div>
```

## Installation

::: installation

## Usage

```twig
<twig:Dropdown
    id="string"
    side="top | bottom | left | right"
    align="start | center | end"
    hover="false | true"
    open="false | true"
>
    <twig:Dropdown:Trigger>
        <twig:Button {{ ...dropdown_trigger_attrs }}>Open</twig:Button>
    </twig:Dropdown:Trigger>
    <twig:Dropdown:Content>
        <ul class="menu w-52 rounded-box bg-base-100 p-2 shadow-sm">
            <li><a href="#">Item 1</a></li>
            <li><a href="#">Item 2</a></li>
        </ul>
    </twig:Dropdown:Content>
</twig:Dropdown>
```

## Examples

### Align Start

The content aligns to the start of the trigger horizontally.

```twig {"preview":true}
<div style="min-height: 160px">
    <twig:Dropdown id="dropdown-align-start" align="start">
        <twig:Dropdown:Trigger>
            <twig:Button class="m-1" {{ ...dropdown_trigger_attrs }}>Click ⬇️</twig:Button>
        </twig:Dropdown:Trigger>
        <twig:Dropdown:Content>
            <ul class="menu w-52 rounded-box bg-base-100 p-2 shadow-sm">
                <li><button>Item 1</button></li>
                <li><button>Item 2</button></li>
            </ul>
        </twig:Dropdown:Content>
    </twig:Dropdown>
</div>
```

### Align End

The content aligns to the end of the trigger horizontally.

```twig {"preview":true}
<div style="min-height: 160px">
    <twig:Dropdown id="dropdown-align-end" align="end">
        <twig:Dropdown:Trigger>
            <twig:Button class="m-1" {{ ...dropdown_trigger_attrs }}>Click ⬇️</twig:Button>
        </twig:Dropdown:Trigger>
        <twig:Dropdown:Content>
            <ul class="menu w-52 rounded-box bg-base-100 p-2 shadow-sm">
                <li><button>Item 1</button></li>
                <li><button>Item 2</button></li>
            </ul>
        </twig:Dropdown:Content>
    </twig:Dropdown>
</div>
```

### Align Center

The content aligns to the center of the trigger horizontally.

```twig {"preview":true}
<div style="min-height: 160px">
    <twig:Dropdown id="dropdown-align-center" align="center">
        <twig:Dropdown:Trigger>
            <twig:Button class="m-1" {{ ...dropdown_trigger_attrs }}>Click ⬇️</twig:Button>
        </twig:Dropdown:Trigger>
        <twig:Dropdown:Content>
            <ul class="menu w-52 rounded-box bg-base-100 p-2 shadow-sm">
                <li><button>Item 1</button></li>
                <li><button>Item 2</button></li>
            </ul>
        </twig:Dropdown:Content>
    </twig:Dropdown>
</div>
```

### Top

```twig {"preview":true}
<div class="flex items-end" style="min-height: 160px">
    <twig:Dropdown id="dropdown-top" side="top">
        <twig:Dropdown:Trigger>
            <twig:Button class="m-1" {{ ...dropdown_trigger_attrs }}>Click ⬆️</twig:Button>
        </twig:Dropdown:Trigger>
        <twig:Dropdown:Content>
            <ul class="menu w-52 rounded-box bg-base-100 p-2 shadow-sm">
                <li><button>Item 1</button></li>
                <li><button>Item 2</button></li>
            </ul>
        </twig:Dropdown:Content>
    </twig:Dropdown>
</div>
```

### Top Center

```twig {"preview":true}
<div class="flex items-end" style="min-height: 160px">
    <twig:Dropdown id="dropdown-top-center" side="top" align="center">
        <twig:Dropdown:Trigger>
            <twig:Button class="m-1" {{ ...dropdown_trigger_attrs }}>Click ⬆️</twig:Button>
        </twig:Dropdown:Trigger>
        <twig:Dropdown:Content>
            <ul class="menu w-52 rounded-box bg-base-100 p-2 shadow-sm">
                <li><button>Item 1</button></li>
                <li><button>Item 2</button></li>
            </ul>
        </twig:Dropdown:Content>
    </twig:Dropdown>
</div>
```

### Top End

```twig {"preview":true}
<div class="flex items-end" style="min-height: 160px">
    <twig:Dropdown id="dropdown-top-end" side="top" align="end">
        <twig:Dropdown:Trigger>
            <twig:Button class="m-1" {{ ...dropdown_trigger_attrs }}>Click ⬆️</twig:Button>
        </twig:Dropdown:Trigger>
        <twig:Dropdown:Content>
            <ul class="menu w-52 rounded-box bg-base-100 p-2 shadow-sm">
                <li><button>Item 1</button></li>
                <li><button>Item 2</button></li>
            </ul>
        </twig:Dropdown:Content>
    </twig:Dropdown>
</div>
```

### Bottom

The content opens below the trigger by default.

```twig {"preview":true}
<div style="min-height: 160px">
    <twig:Dropdown id="dropdown-bottom" side="bottom">
        <twig:Dropdown:Trigger>
            <twig:Button class="m-1" {{ ...dropdown_trigger_attrs }}>Click ⬇️</twig:Button>
        </twig:Dropdown:Trigger>
        <twig:Dropdown:Content>
            <ul class="menu w-52 rounded-box bg-base-100 p-2 shadow-sm">
                <li><button>Item 1</button></li>
                <li><button>Item 2</button></li>
            </ul>
        </twig:Dropdown:Content>
    </twig:Dropdown>
</div>
```

### Bottom Center

```twig {"preview":true}
<div style="min-height: 160px">
    <twig:Dropdown id="dropdown-bottom-center" side="bottom" align="center">
        <twig:Dropdown:Trigger>
            <twig:Button class="m-1" {{ ...dropdown_trigger_attrs }}>Click ⬇️</twig:Button>
        </twig:Dropdown:Trigger>
        <twig:Dropdown:Content>
            <ul class="menu w-52 rounded-box bg-base-100 p-2 shadow-sm">
                <li><button>Item 1</button></li>
                <li><button>Item 2</button></li>
            </ul>
        </twig:Dropdown:Content>
    </twig:Dropdown>
</div>
```

### Bottom End

```twig {"preview":true}
<div style="min-height: 160px">
    <twig:Dropdown id="dropdown-bottom-end" side="bottom" align="end">
        <twig:Dropdown:Trigger>
            <twig:Button class="m-1" {{ ...dropdown_trigger_attrs }}>Click ⬇️</twig:Button>
        </twig:Dropdown:Trigger>
        <twig:Dropdown:Content>
            <ul class="menu w-52 rounded-box bg-base-100 p-2 shadow-sm">
                <li><button>Item 1</button></li>
                <li><button>Item 2</button></li>
            </ul>
        </twig:Dropdown:Content>
    </twig:Dropdown>
</div>
```

### Left

```twig {"preview":true}
<div class="flex items-center" style="min-height: 160px">
    <twig:Dropdown id="dropdown-left" side="left">
        <twig:Dropdown:Trigger>
            <twig:Button class="m-1" {{ ...dropdown_trigger_attrs }}>Click ⬅️</twig:Button>
        </twig:Dropdown:Trigger>
        <twig:Dropdown:Content>
            <ul class="menu w-52 rounded-box bg-base-100 p-2 shadow-sm">
                <li><button>Item 1</button></li>
                <li><button>Item 2</button></li>
            </ul>
        </twig:Dropdown:Content>
    </twig:Dropdown>
</div>
```

### Left Center

The content aligns to the center of the trigger vertically.

```twig {"preview":true}
<div class="flex items-center" style="min-height: 160px">
    <twig:Dropdown id="dropdown-left-center" side="left" align="center">
        <twig:Dropdown:Trigger>
            <twig:Button class="m-1" {{ ...dropdown_trigger_attrs }}>Click ⬅️</twig:Button>
        </twig:Dropdown:Trigger>
        <twig:Dropdown:Content>
            <ul class="menu w-52 rounded-box bg-base-100 p-2 shadow-sm">
                <li><button>Item 1</button></li>
                <li><button>Item 2</button></li>
            </ul>
        </twig:Dropdown:Content>
    </twig:Dropdown>
</div>
```

### Left End

The content aligns to the end of the trigger vertically.

```twig {"preview":true}
<div class="flex items-center" style="min-height: 160px">
    <twig:Dropdown id="dropdown-left-end" side="left" align="end">
        <twig:Dropdown:Trigger>
            <twig:Button class="m-1" {{ ...dropdown_trigger_attrs }}>Click ⬅️</twig:Button>
        </twig:Dropdown:Trigger>
        <twig:Dropdown:Content>
            <ul class="menu w-52 rounded-box bg-base-100 p-2 shadow-sm">
                <li><button>Item 1</button></li>
                <li><button>Item 2</button></li>
            </ul>
        </twig:Dropdown:Content>
    </twig:Dropdown>
</div>
```

### Right

```twig {"preview":true}
<div class="flex items-center" style="min-height: 160px">
    <twig:Dropdown id="dropdown-right" side="right">
        <twig:Dropdown:Trigger>
            <twig:Button class="m-1" {{ ...dropdown_trigger_attrs }}>Click ➡️</twig:Button>
        </twig:Dropdown:Trigger>
        <twig:Dropdown:Content>
            <ul class="menu w-52 rounded-box bg-base-100 p-2 shadow-sm">
                <li><button>Item 1</button></li>
                <li><button>Item 2</button></li>
            </ul>
        </twig:Dropdown:Content>
    </twig:Dropdown>
</div>
```

### Right End

The content aligns to the end of the trigger vertically.

```twig {"preview":true}
<div class="flex items-center" style="min-height: 160px">
    <twig:Dropdown id="dropdown-right-end" side="right" align="end">
        <twig:Dropdown:Trigger>
            <twig:Button class="m-1" {{ ...dropdown_trigger_attrs }}>Click ➡️</twig:Button>
        </twig:Dropdown:Trigger>
        <twig:Dropdown:Content>
            <ul class="menu w-52 rounded-box bg-base-100 p-2 shadow-sm">
                <li><button>Item 1</button></li>
                <li><button>Item 2</button></li>
            </ul>
        </twig:Dropdown:Content>
    </twig:Dropdown>
</div>
```

### Right Center

The content aligns to the center of the trigger vertically.

```twig {"preview":true}
<div class="flex items-center" style="min-height: 160px">
    <twig:Dropdown id="dropdown-right-center" side="right" align="center">
        <twig:Dropdown:Trigger>
            <twig:Button class="m-1" {{ ...dropdown_trigger_attrs }}>Click ➡️</twig:Button>
        </twig:Dropdown:Trigger>
        <twig:Dropdown:Content>
            <ul class="menu w-52 rounded-box bg-base-100 p-2 shadow-sm">
                <li><button>Item 1</button></li>
                <li><button>Item 2</button></li>
            </ul>
        </twig:Dropdown:Content>
    </twig:Dropdown>
</div>
```

### Hover

The content also opens when the pointer is over the dropdown, and closes when it leaves.

```twig {"preview":true}
<div style="min-height: 160px">
    <twig:Dropdown id="dropdown-hover" hover>
        <twig:Dropdown:Trigger>
            <twig:Button class="m-1" {{ ...dropdown_trigger_attrs }}>Hover</twig:Button>
        </twig:Dropdown:Trigger>
        <twig:Dropdown:Content>
            <ul class="menu w-52 rounded-box bg-base-100 p-2 shadow-sm">
                <li><button>Item 1</button></li>
                <li><button>Item 2</button></li>
            </ul>
        </twig:Dropdown:Content>
    </twig:Dropdown>
</div>
```

### Open

The dropdown is open on initial render.

```twig {"preview":true}
<div style="min-height: 160px">
    <twig:Dropdown id="dropdown-open" open>
        <twig:Dropdown:Trigger>
            <twig:Button class="m-1" {{ ...dropdown_trigger_attrs }}>Button</twig:Button>
        </twig:Dropdown:Trigger>
        <twig:Dropdown:Content>
            <ul class="menu w-52 rounded-box bg-base-100 p-2 shadow-sm">
                <li><button>Item 1</button></li>
                <li><button>Item 2</button></li>
            </ul>
        </twig:Dropdown:Content>
    </twig:Dropdown>
</div>
```

### Card As Dropdown

You can use any element as the content of a dropdown.

```twig {"preview":true}
<div style="min-height: 180px">
    <twig:Dropdown id="dropdown-card">
        <twig:Dropdown:Trigger>
            <twig:Button class="m-1" {{ ...dropdown_trigger_attrs }}>Click</twig:Button>
        </twig:Dropdown:Trigger>
        <twig:Dropdown:Content class="card card-sm w-64 bg-base-100 shadow-md">
            <div class="card-body">
                <p>This is a card. You can use any element as a dropdown.</p>
            </div>
        </twig:Dropdown:Content>
    </twig:Dropdown>
</div>
```

### Dropdown In Navbar

```twig {"preview":true}
<div class="w-full" style="min-height: 200px">
    <div class="navbar w-full bg-base-200">
        <div class="ps-4">
            <a class="text-lg font-bold">daisyUI</a>
        </div>
        <div class="flex grow justify-end px-2">
            <div class="flex items-stretch">
                <twig:Button variant="ghost" class="rounded-field">Button</twig:Button>
                <twig:Dropdown id="dropdown-navbar" align="end">
                    <twig:Dropdown:Trigger>
                        <twig:Button variant="ghost" class="rounded-field" {{ ...dropdown_trigger_attrs }}>Dropdown</twig:Button>
                    </twig:Dropdown:Trigger>
                    <twig:Dropdown:Content class="mt-4">
                        <ul class="menu w-52 rounded-box bg-base-200 p-2 shadow-sm">
                            <li><button>Item 1</button></li>
                            <li><button>Item 2</button></li>
                        </ul>
                    </twig:Dropdown:Content>
                </twig:Dropdown>
            </div>
        </div>
    </div>
</div>
```

### Helper Dropdown

```twig {"preview":true}
<div class="flex items-start gap-1" style="min-height: 180px">
    A normal text and a helper dropdown
    <twig:Dropdown id="dropdown-helper" align="end">
        <twig:Dropdown:Trigger>
            <twig:Button variant="ghost" shape="circle" size="xs" class="text-info" aria-label="More info" {{ ...dropdown_trigger_attrs }}>
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="h-4 w-4 stroke-current"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </twig:Button>
        </twig:Dropdown:Trigger>
        <twig:Dropdown:Content class="card card-sm w-64 rounded-box bg-base-100 shadow-sm">
            <div class="card-body">
                <h2 class="card-title">You needed more info?</h2>
                <p>Here is a description!</p>
            </div>
        </twig:Dropdown:Content>
    </twig:Dropdown>
</div>
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

```twig {"preview":true}
<div class="flex flex-col gap-4">
    <div dir="rtl" style="min-height: 160px">
        <twig:Dropdown id="dropdown-rtl-ar" open>
            <twig:Dropdown:Trigger>
                <twig:Button class="m-1" {{ ...dropdown_trigger_attrs }}>انقر للفتح</twig:Button>
            </twig:Dropdown:Trigger>
            <twig:Dropdown:Content>
                <ul class="menu w-52 rounded-box bg-base-100 p-2 shadow-sm">
                    <li><button>العنصر 1</button></li>
                    <li><button>العنصر 2</button></li>
                </ul>
            </twig:Dropdown:Content>
        </twig:Dropdown>
    </div>
    <div dir="rtl" style="min-height: 160px">
        <twig:Dropdown id="dropdown-rtl-he" open>
            <twig:Dropdown:Trigger>
                <twig:Button class="m-1" {{ ...dropdown_trigger_attrs }}>לחץ לפתיחה</twig:Button>
            </twig:Dropdown:Trigger>
            <twig:Dropdown:Content>
                <ul class="menu w-52 rounded-box bg-base-100 p-2 shadow-sm">
                    <li><button>פריט 1</button></li>
                    <li><button>פריט 2</button></li>
                </ul>
            </twig:Dropdown:Content>
        </twig:Dropdown>
    </div>
</div>
```

## API Reference

::: api-reference
