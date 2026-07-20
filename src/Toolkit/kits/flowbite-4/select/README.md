# Select

Get started with the select component to allow the user to choose from one or more options from a dropdown list based on multiple styles, sizes, and variants

```twig {"preview":true}
<form class="w-full max-w-sm">
    <twig:Label for="countries" class="mb-2.5">Select an option</twig:Label>
    <twig:Select id="countries">
        <option selected>Choose a country</option>
        <option value="US">United States</option>
        <option value="CA">Canada</option>
        <option value="FR">France</option>
        <option value="DE">Germany</option>
    </twig:Select>
</form>
```

## Installation

::: installation

## Usage

```twig
<twig:Select>
    <option value="1">1</option>
    <option value="2">2</option>
    <option value="3">3</option>
</twig:Select>
```

## Examples

### Multiple

Apply the `multiple` attribute to the select component to allow users to select one or more options.

```twig {"preview":true}
<form class="w-full max-w-sm">
    <twig:Label for="countries" class="mb-2.5">Select an option</twig:Label>
    <twig:Select id="countries" multiple>
        <option selected>Choose a country</option>
        <option value="US">United States</option>
        <option value="CA">Canada</option>
        <option value="FR">France</option>
        <option value="DE">Germany</option>
    </twig:Select>
</form>
```

### Disabled state

Apply the `disabled` attribute to the select component to prevent users from interacting with it.

```twig {"preview":true}
<form class="w-full max-w-sm">
    <twig:Label for="countries" class="mb-2.5">Select an option</twig:Label>
    <twig:Select id="countries" disabled>
        <option selected>Choose a country</option>
        <option value="US">United States</option>
        <option value="CA">Canada</option>
        <option value="FR">France</option>
        <option value="DE">Germany</option>
    </twig:Select>
</form>
```

## API Reference

::: api-reference
