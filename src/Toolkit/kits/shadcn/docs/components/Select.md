# Select

A dropdown control that allows users to choose from a list of options.

```twig {"preview":true}
<twig:Select class="max-w-sm">
    <option value="1">1</option>
    <option value="2">2</option>
    <option value="3">3</option>
</twig:Select>
```

## Installation

<!-- Placeholder: Installation -->

## Usage

<!-- Placeholder: Usage -->

## Examples

### Default

```twig {"preview":true}
<twig:Select class="max-w-sm">
    <option value="1">1</option>
    <option value="2">2</option>
    <option value="3">3</option>
</twig:Select>
```

### With Label

```twig {"preview":true}
<div class="grid w-full max-w-sm items-center gap-1.5">
    <twig:Label for="framework">Framework</twig:Label>
    <twig:Select id="framework">
        <option value="symfony">Symfony</option>
        <option value="laravel">Laravel</option>
        <option value="tempest">Tempest</option>
    </twig:Select>
</div>
```

### Disabled

```twig {"preview":true}
<twig:Select disabled class="max-w-sm">
    <option value="1">1</option>
    <option value="2">2</option>
    <option value="3">3</option>
</twig:Select>
```
