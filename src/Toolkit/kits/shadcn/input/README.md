# Input

A text input component for forms and user data entry with built-in styling and accessibility features.

```twig {"preview":true}
<div class="*:max-w-xs w-full justify-center flex">
    <twig:Field>
        <twig:Field:Label for="input-demo-api-key">API Key</twig:Field:Label>
        <twig:Input id="input-demo-api-key" type="password" placeholder="sk-..." />
        <twig:Field:Description>Your API key is encrypted and stored securely.</twig:Field:Description>
    </twig:Field>
</div>
```

## Installation

::: installation

## Usage

```twig
<twig:Input />
```

## Examples

### Basic

```twig {"preview":true}
<div class="*:max-w-xs w-full justify-center flex">
    <twig:Input placeholder="Enter text" />
</div>
```

### Field

Use `Field`, `Field:Label`, and `Field:Description` to create an input with a label and description.

```twig {"preview":true}
<div class="*:max-w-xs">
    <twig:Field>
        <twig:Field:Label for="input-field-username">Username</twig:Field:Label>
        <twig:Input id="input-field-username" type="text" placeholder="Enter your username" />
        <twig:Field:Description>Choose a unique username for your account.</twig:Field:Description>
    </twig:Field>
</div>
```

### Field Group

Use `Field:Group` to show multiple `Field` blocks and to build forms.

```twig {"preview":true}
<div class="*:max-w-xs w-full justify-center flex">
    <twig:Field:Group>
        <twig:Field>
            <twig:Field:Label for="fieldgroup-name">Name</twig:Field:Label>
            <twig:Input id="fieldgroup-name" placeholder="Jordan Lee" />
        </twig:Field>
        <twig:Field>
            <twig:Field:Label for="fieldgroup-email">Email</twig:Field:Label>
            <twig:Input id="fieldgroup-email" type="email" placeholder="name@example.com" />
            <twig:Field:Description>We'll send updates to this address.</twig:Field:Description>
        </twig:Field>
        <twig:Field orientation="horizontal">
            <twig:Button type="reset" variant="outline">Reset</twig:Button>
            <twig:Button type="submit">Submit</twig:Button>
        </twig:Field>
    </twig:Field:Group>
</div>
```

### Disabled

Use the `disabled` prop to disable the input. To style the disabled state, add the `data-disabled` attribute to the `Field` component.

```twig {"preview":true}
<div class="*:max-w-xs w-full justify-center flex">
    <twig:Field data-disabled="true">
        <twig:Field:Label for="input-demo-disabled">Email</twig:Field:Label>
        <twig:Input id="input-demo-disabled" type="email" placeholder="Email" disabled />
        <twig:Field:Description>This field is currently disabled.</twig:Field:Description>
    </twig:Field>
</div>
```

### Invalid

Use the `aria-invalid` prop to mark the input as invalid. To style the invalid state, add the `data-invalid` attribute to the `Field` component.

```twig {"preview":true}
<div class="*:max-w-xs w-full justify-center flex">
    <twig:Field data-invalid="true">
        <twig:Field:Label for="input-invalid">Invalid Input</twig:Field:Label>
        <twig:Input id="input-invalid" placeholder="Error" aria-invalid="true" />
        <twig:Field:Description>This field contains validation errors.</twig:Field:Description>
    </twig:Field>
</div>
```

### File

Use the `type="file"` prop to create a file input.

```twig {"preview":true}
<div class="*:max-w-xs w-full justify-center flex">
    <twig:Field>
        <twig:Field:Label for="picture">Picture</twig:Field:Label>
        <twig:Input id="picture" type="file" />
        <twig:Field:Description>Select a picture to upload.</twig:Field:Description>
    </twig:Field>
</div>
```

### Inline

Use `Field` with `orientation="horizontal"` to create an inline input. Pair with `Button` to create a search input with a button.

```twig {"preview":true}
<div class="*:max-w-xs w-full justify-center flex">
    <twig:Field orientation="horizontal">
        <twig:Input type="search" placeholder="Search..." />
        <twig:Button>Search</twig:Button>
    </twig:Field>
</div>
```

### Grid

Use a grid layout to place multiple inputs side by side.

```twig {"preview":true}
<twig:Field:Group class="grid max-w-sm grid-cols-2">
    <twig:Field>
        <twig:Field:Label for="first-name">First Name</twig:Field:Label>
        <twig:Input id="first-name" placeholder="Jordan" />
    </twig:Field>
    <twig:Field>
        <twig:Field:Label for="last-name">Last Name</twig:Field:Label>
        <twig:Input id="last-name" placeholder="Lee" />
    </twig:Field>
</twig:Field:Group>
```

### Required

Use the `required` attribute to indicate required inputs.

```twig {"preview":true}
<div class="*:max-w-xs w-full justify-center flex">
    <twig:Field>
        <twig:Field:Label for="input-required">
            Required Field <span class="text-destructive">*</span>
        </twig:Field:Label>
        <twig:Input id="input-required" placeholder="This field is required" required />
        <twig:Field:Description>This field must be filled out.</twig:Field:Description>
    </twig:Field>
</div>
```

### Badge

Use `Badge` in the label to highlight a recommended field.

```twig {"preview":true}
<div class="*:max-w-xs w-full justify-center flex">
    <twig:Field>
        <twig:Field:Label for="input-badge">
            Webhook URL
            <twig:Badge variant="secondary" class="ml-auto">Beta</twig:Badge>
        </twig:Field:Label>
        <twig:Input id="input-badge" type="url" placeholder="https://api.example.com/webhook" />
    </twig:Field>
</div>
```

### Input Group

To add icons, text, or buttons inside an input, use the `InputGroup` component.

```twig {"preview":true}
<div class="*:max-w-xs w-full justify-center flex">
    <twig:Field>
        <twig:Field:Label for="input-group-url">Website URL</twig:Field:Label>
        <twig:InputGroup>
            <twig:InputGroup:Input id="input-group-url" placeholder="example.com" />
            <twig:InputGroup:Addon>
                <twig:InputGroup:Text>https://</twig:InputGroup:Text>
            </twig:InputGroup:Addon>
            <twig:InputGroup:Addon align="inline-end">
                <twig:ux:icon name="lucide:info" />
            </twig:InputGroup:Addon>
        </twig:InputGroup>
    </twig:Field>
</div>
```

### Button Group

To add buttons to an input, use the `ButtonGroup` component.

```twig {"preview":true}
<div class="*:max-w-xs w-full justify-center flex">
    <twig:Field>
        <twig:Field:Label for="input-button-group">Search</twig:Field:Label>
        <twig:ButtonGroup>
            <twig:Input id="input-button-group" placeholder="Type to search..." />
            <twig:Button variant="outline">Search</twig:Button>
        </twig:ButtonGroup>
    </twig:Field>
</div>
```

### Form

A full form example with multiple inputs, a select, and a button.

```twig {"preview":true}
<form class="w-full max-w-sm">
    <twig:Field:Group>
        <twig:Field>
            <twig:Field:Label for="form-name">Name</twig:Field:Label>
            <twig:Input id="form-name" type="text" placeholder="Evil Rabbit" required />
        </twig:Field>
        <twig:Field>
            <twig:Field:Label for="form-email">Email</twig:Field:Label>
            <twig:Input id="form-email" type="email" placeholder="john@example.com" />
            <twig:Field:Description>We'll never share your email with anyone.</twig:Field:Description>
        </twig:Field>
        <div class="grid grid-cols-2 gap-4">
            <twig:Field>
                <twig:Field:Label for="form-phone">Phone</twig:Field:Label>
                <twig:Input id="form-phone" type="tel" placeholder="+1 (555) 123-4567" />
            </twig:Field>
            <twig:Field>
                <twig:Field:Label for="form-country">Country</twig:Field:Label>
                <twig:NativeSelect id="form-country">
                    <option value="us">United States</option>
                    <option value="uk">United Kingdom</option>
                    <option value="ca">Canada</option>
                </twig:NativeSelect>
            </twig:Field>
        </div>
        <twig:Field>
            <twig:Field:Label for="form-address">Address</twig:Field:Label>
            <twig:Input id="form-address" type="text" placeholder="123 Main St" />
        </twig:Field>
        <twig:Field orientation="horizontal">
            <twig:Button type="button" variant="outline">Cancel</twig:Button>
            <twig:Button type="submit">Submit</twig:Button>
        </twig:Field>
    </twig:Field:Group>
</form>
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

```twig {"preview":true}
<div class="w-full flex flex-col items-center gap-8">
    <div dir="rtl" class="max-w-xs w-full">
        <twig:Field>
            <twig:Field:Label for="input-rtl-ar">مفتاح API</twig:Field:Label>
            <twig:Input id="input-rtl-ar" type="password" placeholder="sk-..." />
            <twig:Field:Description>مفتاح API الخاص بك مشفر ومخزن بأمان.</twig:Field:Description>
        </twig:Field>
    </div>
    <div dir="rtl" class="max-w-xs w-full">
        <twig:Field>
            <twig:Field:Label for="input-rtl-he">קוד API</twig:Field:Label>
            <twig:Input id="input-rtl-he" type="password" placeholder="sk-..." />
            <twig:Field:Description>קוד ה-API שלך מוצן ומאוחסן בצורה מאובטחת.</twig:Field:Description>
        </twig:Field>
    </div>
</div>
```

## API Reference

::: api-reference
