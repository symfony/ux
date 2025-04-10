# Alert

Displays a callout for user attention.

```twig {"preview":true}
<twig:Alert class="max-w-lg">
    <twig:ux:icon name="tabler:terminal" class="h-4 w-4" />
    <twig:Alert:Title>Heads up!</twig:Alert:Title>
    <twig:Alert:Description>
        You can add components to your app using the cli.
    </twig:Alert:Description>
</twig:Alert>
```

## Installation

<!-- Placeholder: Installation -->

## Usage

<!-- Placeholder: Usage -->

## Examples

### Default

```twig {"preview":true}
<twig:Alert class="max-w-lg">
    <twig:ux:icon name="tabler:terminal" class="h-4 w-4" />
    <twig:Alert:Title>Heads up!</twig:Alert:Title>
    <twig:Alert:Description>
        You can add components to your app using the cli.
    </twig:Alert:Description>
</twig:Alert>
```

### Destructive

```twig {"preview":true}
<twig:Alert variant="destructive" class="max-w-lg">
    <twig:ux:icon name="tabler:alert-circle" class="h-4 w-4" />
    <twig:Alert:Title>Error</twig:Alert:Title>
    <twig:Alert:Description>
        Your session has expired. Please log in again.
    </twig:Alert:Description>
</twig:Alert>
``` 
