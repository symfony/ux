# Switch

A toggle control that switches between on and off states.

```twig {"preview":true}
<div class="flex items-center space-x-2">
    <twig:Switch id="airplane-mode" />
    <twig:Label for="airplane-mode">Airplane Mode</twig:Label>
</div>
```

## Installation

<!-- Placeholder: Installation -->

## Usage

<!-- Placeholder: Usage -->

## Examples

### Default

```twig {"preview":true}
<div class="flex items-center space-x-2">
    <twig:Switch id="airplane-mode" />
    <twig:Label for="airplane-mode">Airplane Mode</twig:Label>
</div>
```

### Form

```twig {"preview":true,"height":"300px"}
<form action="/" method="post">
    <h3 class="mb-4 text-lg font-medium">Email Notifications</h3>
    <div class="flex flex-col gap-3">
        <div class="gap-2 flex flex-row items-center justify-between rounded-lg border p-4">
            <div class="space-y-0.5">
                <twig:Label class="text-base" for="marketing-emails">Marketing emails</twig:Label>
                <p class="text-sm text-muted-foreground">Receive emails about new products, features, and more.</p>
            </div>
            <twig:Switch id="marketing-emails" />
        </div>
        <div class="gap-2 flex flex-row items-center justify-between rounded-lg border p-4">
            <div class="space-y-0.5">
                <twig:Label class="text-base" for="security-emails">Security emails</twig:Label>
                <p class="text-sm text-muted-foreground">Receive emails about your account security.</p>
            </div>
            <twig:Switch id="security-emails" checked disabled />
        </div>
    </div>
</form>
```
