# Input

The input field is an important part of the form element that can be used to create interactive controls to accept data from the user based on multiple input types, such as text, email, number, password, URL, phone number, and more.

```twig {"preview":true}
<form>
    <div class="grid gap-6 mb-6 md:grid-cols-2">
        <div>
            <twig:Label for="first_name">First name</twig:Label>
            <twig:Input id="first_name" type="text" placeholder="John" required />
        </div>
        <div>
            <twig:Label for="last_name">Last name</twig:Label>
            <twig:Input id="last_name" type="text" placeholder="Doe" required />
        </div>
        <div>
            <twig:Label for="company">Company</twig:Label>
            <twig:Input id="company" type="text" placeholder="Flowbite" required />
        </div>
        <div>
            <twig:Label for="phone">Phone number</twig:Label>
            <twig:Input id="phone" type="tel" placeholder="123-45-678" pattern="[0-9]{3}-[0-9]{2}-[0-9]{3}" required />
        </div>
        <div>
            <twig:Label for="website">Website URL</twig:Label>
            <twig:Input id="website" type="url" placeholder="flowbite.com" required />
        </div>
        <div>
            <twig:Label for="visitors">Unique visitors (per month)</twig:Label>
            <twig:Input id="visitors" type="number" required />
        </div>
    </div>
    <div class="mb-6">
        <twig:Label for="email">Email address</twig:Label>
        <twig:Input type="email" id="email" placeholder="john.doe@company.com" required />
    </div>
    <div class="mb-6">
        <twig:Label for="password">Password</twig:Label>
        <twig:Input type="password" id="password" placeholder="•••••••••" required />
    </div>
    <div class="mb-6">
        <twig:Label for="confirm_password">Confirm password</twig:Label>
        <twig:Input type="password" id="confirm_password" placeholder="•••••••••" required />
    </div>
    <twig:Button type="submit">Submit</twig:Button>
</form>
```

## Installation

::: installation

## Usage

```twig
<twig:Input />
```

## Examples

### Disabled

Get started with this example if you want to apply the disabled state to an input field.

```twig {"preview":true}
<div class="space-y-6">
    <twig:Input type="text" value="Disabled input" disabled class="max-w-sm" />
    <twig:Input type="text" value="Disabled readonly input" disabled readonly class="max-w-sm" />
</div>
```

### Invalid

Use the following example to apply validation styles for error messages.

```twig {"preview":true}
<div class="grid w-full max-w-sm items-center gap-1.5">
    <twig:Label for="text" variant="invalid">Your name</twig:Label>
    <twig:Input type="text" id="text" aria-invalid="true" placeholder="Error input" />
    <p class="text-sm text-fg-danger-strong"><span class="font-medium">Oh, snapp!</span> Some error message.</p>
</div>
```

### File

Get started with a simple file input component to let users upload one single file.

```twig {"preview":true}
<div class="grid w-full max-w-sm items-center gap-1.5">
    <twig:Label for="picture">Picture</twig:Label>
    <twig:Input type="file" id="picture" />
</div>
```

### With Button

```twig {"preview":true}
<div class="relative inline-flex">
    <twig:Input type="text" id="text" placeholder="Search..." class="rounded-e-none" />
    <twig:Button type="button" size="sm" class="rounded-s-0">
        <twig:ux:icon name="tabler:search" class="size-4 me-1" aria-hidden="true" />
        Search
    </twig:Button>
</div>
```

## API Reference

::: api-reference
