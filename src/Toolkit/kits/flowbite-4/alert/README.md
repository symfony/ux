# Alert

The alert component can be used to provide information to your users such as success or error messages, but also highlighted information complementing the normal flow of paragraphs and headers on a page.

```twig {"preview":true}
<div class="grid w-full max-w-xl items-start gap-4">
    <twig:Alert>
        <twig:Alert:Description>
            <p><span class="font-medium">Info alert!</span> Change a few things up and try submitting again.</p>
        </twig:Alert:Description>
    </twig:Alert>

    <twig:Alert variant="danger">
        <twig:Alert:Description>
            <p><span class="font-medium">Danger alert!</span> Change a few things up and try submitting again.</p>
        </twig:Alert:Description>
    </twig:Alert>

    <twig:Alert variant="success">
        <twig:Alert:Description>
            <p><span class="font-medium">Success alert!</span> Change a few things up and try submitting again.</p>
        </twig:Alert:Description>
    </twig:Alert>

    <twig:Alert variant="warning">
        <twig:Alert:Description>
            <p><span class="font-medium">Warning alert!</span> Change a few things up and try submitting again.</p>
        </twig:Alert:Description>
    </twig:Alert>

    <twig:Alert variant="dark">
        <twig:Alert:Description>
            <p><span class="font-medium">Dark alert!</span> Change a few things up and try submitting again.</p>
        </twig:Alert:Description>
    </twig:Alert>
</div>
```

## Installation

::: installation

## Usage

```twig
<twig:Alert
    variant="brand | danger | success | warning | dark"
    border=" none | bordered | accent"
>
    <twig:block name="icon">
        <twig:ux:icon name="flowbite:info-circle-outline" class="shrink-0" aria-hidden="true" />
    </twig:block>
    <twig:Alert:Title>Info alert!</twig:Alert:Title>
    <twig:Alert:Description>
        You can add components to your app using the cli.
    </twig:Alert:Description>
    <twig:Alert:Action>
        <twig:Button size="sm">Enable</twig:Button>
    </twig:Alert:Action>
</twig:Alert>
```

## Examples

### Alerts with icon

You can also include a descriptive icon to complement the message inside the alert component with the following example.

```twig {"preview":true,"height":"600px"}
<div class="grid w-full max-w-xl items-start gap-4">
    <twig:Alert>
        <twig:block name="icon">
            <twig:ux:icon name="flowbite:info-circle-outline" class="shrink-0" aria-hidden="true" />
        </twig:block>
        <twig:Alert:Description>
            <p><span class="font-medium">Info alert!</span> Change a few things up and try submitting again.</p>
        </twig:Alert:Description>
    </twig:Alert>

    <twig:Alert variant="danger">
        <twig:block name="icon">
            <twig:ux:icon name="flowbite:info-circle-outline" class="shrink-0" aria-hidden="true" />
        </twig:block>
        <twig:Alert:Description>
            <p><span class="font-medium">Danger alert!</span> Change a few things up and try submitting again.</p>
        </twig:Alert:Description>
    </twig:Alert>

    <twig:Alert variant="success">
        <twig:block name="icon">
            <twig:ux:icon name="flowbite:info-circle-outline" class="shrink-0" aria-hidden="true" />
        </twig:block>
        <twig:Alert:Description>
            <p><span class="font-medium">Success alert!</span> Change a few things up and try submitting again.</p>
        </twig:Alert:Description>
    </twig:Alert>

    <twig:Alert variant="warning">
        <twig:block name="icon">
            <twig:ux:icon name="flowbite:info-circle-outline" class="shrink-0" aria-hidden="true" />
        </twig:block>
        <twig:Alert:Description>
            <p><span class="font-medium">Warning alert!</span> Change a few things up and try submitting again.</p>
        </twig:Alert:Description>
    </twig:Alert>

    <twig:Alert variant="dark">
        <twig:block name="icon">
            <twig:ux:icon name="flowbite:info-circle-outline" class="shrink-0" aria-hidden="true" />
        </twig:block>
        <twig:Alert:Description>
            <p><span class="font-medium">Dark alert!</span> Change a few things up and try submitting again.</p>
        </twig:Alert:Description>
    </twig:Alert>
</div>
```

### Borders

Use this example to add a border accent to the alert component instead of just a plain background.

```twig {"preview":true,"height":"600px"}
<div class="grid w-full max-w-xl items-start gap-4">
    <twig:Alert border="bordered">
        <twig:block name="icon">
            <twig:ux:icon name="flowbite:info-circle-outline" class="shrink-0" aria-hidden="true" />
        </twig:block>
        <twig:Alert:Description>
            <p><span class="font-medium">Info alert!</span> Change a few things up and try submitting again.</p>
        </twig:Alert:Description>
    </twig:Alert>

    <twig:Alert variant="danger" border="bordered">
        <twig:block name="icon">
            <twig:ux:icon name="flowbite:info-circle-outline" class="shrink-0" aria-hidden="true" />
        </twig:block>
        <twig:Alert:Description>
            <p><span class="font-medium">Danger alert!</span> Change a few things up and try submitting again.</p>
        </twig:Alert:Description>
    </twig:Alert>

    <twig:Alert variant="success" border="bordered">
        <twig:block name="icon">
            <twig:ux:icon name="flowbite:info-circle-outline" class="shrink-0" aria-hidden="true" />
        </twig:block>
        <twig:Alert:Description>
            <p><span class="font-medium">Success alert!</span> Change a few things up and try submitting again.</p>
        </twig:Alert:Description>
    </twig:Alert>

    <twig:Alert variant="warning" border="bordered">
        <twig:block name="icon">
            <twig:ux:icon name="flowbite:info-circle-outline" class="shrink-0" aria-hidden="true" />
        </twig:block>
        <twig:Alert:Description>
            <p><span class="font-medium">Warning alert!</span> Change a few things up and try submitting again.</p>
        </twig:Alert:Description>
    </twig:Alert>

    <twig:Alert variant="dark" border="bordered">
        <twig:block name="icon">
            <twig:ux:icon name="flowbite:info-circle-outline" class="shrink-0" aria-hidden="true" />
        </twig:block>
        <twig:Alert:Description>
            <p><span class="font-medium">Dark alert!</span> Change a few things up and try submitting again.</p>
        </twig:Alert:Description>
    </twig:Alert>
</div>
```

### With List

Use this example to show a list and a description inside an alert component.

```twig {"preview":true,"height":"600px"}
<div class="grid w-full max-w-xl items-start gap-4">
    <twig:Alert variant="success">
        <twig:block name="icon">
            <twig:ux:icon name="flowbite:info-circle-outline" class="shrink-0" aria-hidden="true" />
        </twig:block>
        <twig:Alert:Title>Ensure that these requirements are met:</twig:Alert:Title>
        <twig:Alert:Description>
            <ul class="my-2 list-disc list-outside space-y-1 ps-2.5">
                <li>At least 10 characters (and up to 100 characters)</li>
                <li>At least one lowercase character</li>
                <li>Inclusion of at least one special character, e.g., ! @ # ?</li>
            </ul>
        </twig:Alert:Description>
        <twig:Alert:Action>
            <a href="#">See recommendation here</a>
        </twig:Alert:Action>
    </twig:Alert>

    <twig:Alert variant="warning">
        <twig:block name="icon">
            <twig:ux:icon name="flowbite:info-circle-outline" class="shrink-0" aria-hidden="true" />
        </twig:block>
        <twig:Alert:Title>Ensure that these requirements are met:</twig:Alert:Title>
        <twig:Alert:Description>
            <ul class="my-2 list-disc list-outside space-y-1 ps-2.5">
                <li>At least 10 characters (and up to 100 characters)</li>
                <li>At least one lowercase character</li>
                <li>Inclusion of at least one special character, e.g., ! @ # ?</li>
            </ul>
        </twig:Alert:Description>
        <twig:Alert:Action>
            <a href="#">See recommendation here</a>
        </twig:Alert:Action>
    </twig:Alert>

    <twig:Alert variant="danger">
        <twig:block name="icon">
            <twig:ux:icon name="flowbite:info-circle-outline" class="shrink-0" aria-hidden="true" />
        </twig:block>
        <twig:Alert:Title>Ensure that these requirements are met:</twig:Alert:Title>
        <twig:Alert:Description>
            <ul class="my-2 list-disc list-outside space-y-1 ps-2.5">
                <li>At least 10 characters (and up to 100 characters)</li>
                <li>At least one lowercase character</li>
                <li>Inclusion of at least one special character, e.g., ! @ # ?</li>
            </ul>
        </twig:Alert:Description>
        <twig:Alert:Action>
            <a href="#">See recommendation here</a>
        </twig:Alert:Action>
    </twig:Alert>
</div>
```

### Dismissing

**Requires Flowbite JS**<br>
Use the following alert elements that are also dismissible.

```twig {"preview":true,"height":"600px"}
<div class="grid w-full max-w-xl items-start gap-4">
    <twig:Alert dismissible>
        <twig:block name="icon">
            <twig:ux:icon name="flowbite:info-circle-outline" class="shrink-0" aria-hidden="true" />
        </twig:block>
        <twig:Alert:Description>
            <p>A simple info alert with an <a href="#" class="font-medium">example link</a>. Give it a click if you like.</p>
        </twig:Alert:Description>
    </twig:Alert>

    <twig:Alert variant="danger" dismissible>
        <twig:block name="icon">
            <twig:ux:icon name="flowbite:info-circle-outline" class="shrink-0" aria-hidden="true" />
        </twig:block>
        <twig:Alert:Description>
            <p>A simple danger alert with an <a href="#" class="font-medium">example link</a>. Give it a click if you like.</p>
        </twig:Alert:Description>
    </twig:Alert>

    <twig:Alert variant="success" dismissible>
        <twig:block name="icon">
            <twig:ux:icon name="flowbite:info-circle-outline" class="shrink-0" aria-hidden="true" />
        </twig:block>
        <twig:Alert:Description>
            <p>A simple success alert with an <a href="#" class="font-medium">example link</a>. Give it a click if you like.</p>
        </twig:Alert:Description>
    </twig:Alert>

    <twig:Alert variant="warning" dismissible>
        <twig:block name="icon">
            <twig:ux:icon name="flowbite:info-circle-outline" class="shrink-0" aria-hidden="true" />
        </twig:block>
        <twig:Alert:Description>
            <p>A simple warning alert with an <a href="#" class="font-medium">example link</a>. Give it a click if you like.</p>
        </twig:Alert:Description>
    </twig:Alert>

    <twig:Alert variant="dark" dismissible>
        <twig:block name="icon">
            <twig:ux:icon name="flowbite:info-circle-outline" class="shrink-0" aria-hidden="true" />
        </twig:block>
        <twig:Alert:Description>
            <p>A simple dark alert with an <a href="#" class="font-medium">example link</a>. Give it a click if you like.</p>
        </twig:Alert:Description>
    </twig:Alert>
</div>
```

### Action

Use `Alert:Action` to add a button or other action element to the alert.

```twig {"preview":true}
<twig:Alert class="max-w-xl">
    <twig:block name="icon">
        <twig:ux:icon name="flowbite:info-circle-outline" class="shrink-0" aria-hidden="true" />
    </twig:block>
    <twig:Alert:Title>This is an info alert</twig:Alert:Title>
    <twig:Alert:Description>
        More info about this info alert goes here. This example text is going to run a bit longer so that you can see how spacing within an alert works with this kind of content.
    </twig:Alert:Description>
    <twig:Alert:Action>
        <twig:Button size="sm">
            <twig:ux:icon name="flowbite:eye-outline" class="size-3.5 me-1.5" aria-hidden="true" />
            View more
        </twig:Button>
    </twig:Alert:Action>
</twig:Alert>
```

### Border Accent

Use this example to add a border accent on top of the alert component for further visual distinction.

```twig {"preview":true,"height":"600px"}
<div class="grid w-full max-w-xl items-start gap-4">
    <twig:Alert border="accent" class="rounded-none">
        <twig:block name="icon">
            <twig:ux:icon name="flowbite:info-circle-outline" class="shrink-0" aria-hidden="true" />
        </twig:block>
        <twig:Alert:Description>
            <p><span class="font-medium">Info alert!</span> Change a few things up and try submitting again.</p>
        </twig:Alert:Description>
    </twig:Alert>

    <twig:Alert variant="danger" border="accent" class="rounded-none">
        <twig:block name="icon">
            <twig:ux:icon name="flowbite:info-circle-outline" class="shrink-0" aria-hidden="true" />
        </twig:block>
        <twig:Alert:Description>
            <p><span class="font-medium">Danger alert!</span> Change a few things up and try submitting again.</p>
        </twig:Alert:Description>
    </twig:Alert>

    <twig:Alert variant="success" border="accent" class="rounded-none">
        <twig:block name="icon">
            <twig:ux:icon name="flowbite:info-circle-outline" class="shrink-0" aria-hidden="true" />
        </twig:block>
        <twig:Alert:Description>
            <p><span class="font-medium">Success alert!</span> Change a few things up and try submitting again.</p>
        </twig:Alert:Description>
    </twig:Alert>

    <twig:Alert variant="warning" border="accent" class="rounded-none">
        <twig:block name="icon">
            <twig:ux:icon name="flowbite:info-circle-outline" class="shrink-0" aria-hidden="true" />
        </twig:block>
        <twig:Alert:Description>
            <p><span class="font-medium">Warning alert!</span> Change a few things up and try submitting again.</p>
        </twig:Alert:Description>
    </twig:Alert>

    <twig:Alert variant="dark" border="accent" class="rounded-none">
        <twig:block name="icon">
            <twig:ux:icon name="flowbite:info-circle-outline" class="shrink-0" aria-hidden="true" />
        </twig:block>
        <twig:Alert:Description>
            <p><span class="font-medium">Dark alert!</span> Change a few things up and try submitting again.</p>
        </twig:Alert:Description>
    </twig:Alert>
</div>
```

## API Reference

::: api-reference
