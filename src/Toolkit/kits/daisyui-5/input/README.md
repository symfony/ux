# Input

Text Input is a simple input field.

```twig {"preview":true}
<twig:Input type="text" placeholder="Type here" />
```

## Installation

::: installation

## Usage

```twig
<twig:Input
    color="default | neutral | primary | secondary | accent | info | success | warning | error"
    variant="default | ghost"
    size="xs | sm | md | lg | xl"
    as="input | label"
    type="text"
    placeholder="Type here"
/>
```

To add an icon or a text around the input, render the component as a `label` that wraps a plain `<input>`. The props then style the `label`, and the attributes of the field go on the inner `<input>`:

```twig
<twig:Input as="label" color="primary">
    <svg>...</svg>
    <input type="search" placeholder="Search" />
</twig:Input>
```

## Examples

### With Text Label Inside

```twig {"preview":true}
<div class="grid w-xs gap-4">
    <twig:Input as="label">
        <svg class="h-[1em] opacity-50" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g stroke-linejoin="round" stroke-linecap="round" stroke-width="2.5" fill="none" stroke="currentColor"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.3-4.3"></path></g></svg>
        <input type="search" class="grow" placeholder="Search" />
        <kbd class="kbd kbd-sm">⌘</kbd>
        <kbd class="kbd kbd-sm">K</kbd>
    </twig:Input>
    <twig:Input as="label">
        <svg class="h-[1em] opacity-50" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g stroke-linejoin="round" stroke-linecap="round" stroke-width="2.5" fill="none" stroke="currentColor"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"></path><path d="M14 2v4a2 2 0 0 0 2 2h4"></path></g></svg>
        <input type="text" class="grow" placeholder="index.php" />
    </twig:Input>
    <twig:Input as="label">
        Path
        <input type="text" class="grow" placeholder="src/app/" />
        <span class="badge badge-neutral badge-xs">Optional</span>
    </twig:Input>
</div>
```

### Ghost

```twig {"preview":true}
<twig:Input variant="ghost" type="text" placeholder="Type here" />
```

### With Fieldset And Fieldset Legend

```twig {"preview":true}
<fieldset class="fieldset w-xs">
    <legend class="fieldset-legend">What is your name?</legend>
    <twig:Input type="text" placeholder="Type here" />
    <p class="label">Optional</p>
</fieldset>
```

### With Fieldset And Label

```twig {"preview":true}
<fieldset class="fieldset w-xs">
    <label class="label" for="name">Name</label>
    <twig:Input type="text" id="name" placeholder="Name" />
</fieldset>
```

### Colors

```twig {"preview":true}
<div class="grid w-xs gap-4">
    <twig:Input color="neutral" type="text" placeholder="neutral" />
    <twig:Input color="primary" type="text" placeholder="Primary" />
    <twig:Input color="secondary" type="text" placeholder="Secondary" />
    <twig:Input color="accent" type="text" placeholder="Accent" />
    <twig:Input color="info" type="text" placeholder="Info" />
    <twig:Input color="success" type="text" placeholder="Success" />
    <twig:Input color="warning" type="text" placeholder="Warning" />
    <twig:Input color="error" type="text" placeholder="Error" />
</div>
```

### Sizes

```twig {"preview":true}
<div class="grid w-xs gap-4">
    <twig:Input size="xs" type="text" placeholder="Xsmall" />
    <twig:Input size="sm" type="text" placeholder="Small" />
    <twig:Input type="text" placeholder="Medium" />
    <twig:Input size="lg" type="text" placeholder="Large" />
    <twig:Input size="xl" type="text" placeholder="Xlarge" />
</div>
```

### Disabled

```twig {"preview":true}
<twig:Input type="text" placeholder="You can't touch this" disabled />
```

### With Data List Suggestion

```twig {"preview":true}
<div>
    <twig:Input type="text" placeholder="Which browser do you use" list="browsers" />
    <datalist id="browsers">
        <option value="Chrome"></option>
        <option value="Firefox"></option>
        <option value="Safari"></option>
        <option value="Opera"></option>
        <option value="Edge"></option>
    </datalist>
</div>
```

### Date

```twig {"preview":true}
<twig:Input type="date" />
```

### Time

```twig {"preview":true}
<twig:Input type="time" />
```

### Datetime Local

```twig {"preview":true}
<twig:Input type="datetime-local" />
```

### Username With Icon And Validator

```twig {"preview":true}
<div class="w-full max-w-xs">
    <twig:Input as="label" class="validator">
        <svg class="h-[1em] opacity-50" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g stroke-linejoin="round" stroke-linecap="round" stroke-width="2.5" fill="none" stroke="currentColor"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></g></svg>
        <input type="text" required placeholder="Username" pattern="[A-Za-z][A-Za-z0-9\-]*" minlength="3" maxlength="30" title="Only letters, numbers or dash" />
    </twig:Input>
    <p class="validator-hint">
        Must be 3 to 30 characters
        <br />containing only letters, numbers or dash
    </p>
</div>
```

### Search With Icon

```twig {"preview":true}
<div class="w-full max-w-xs">
    <twig:Input as="label">
        <svg class="h-[1em] opacity-50" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g stroke-linejoin="round" stroke-linecap="round" stroke-width="2.5" fill="none" stroke="currentColor"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.3-4.3"></path></g></svg>
        <input type="search" required placeholder="Search" />
    </twig:Input>
</div>
```

### Email With Icon And Validator

```twig {"preview":true}
<div class="w-full max-w-xs">
    <twig:Input as="label" class="validator">
        <svg class="h-[1em] opacity-50" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g stroke-linejoin="round" stroke-linecap="round" stroke-width="2.5" fill="none" stroke="currentColor"><rect width="20" height="16" x="2" y="4" rx="2"></rect><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path></g></svg>
        <input type="email" placeholder="mail@site.com" required />
    </twig:Input>
    <div class="validator-hint hidden">Enter valid email address</div>
</div>
```

### Email With Icon, Validator, Button And Join

```twig {"preview":true}
<div class="join">
    <div>
        <twig:Input as="label" class="validator join-item">
            <svg class="h-[1em] opacity-50" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g stroke-linejoin="round" stroke-linecap="round" stroke-width="2.5" fill="none" stroke="currentColor"><rect width="20" height="16" x="2" y="4" rx="2"></rect><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path></g></svg>
            <input type="email" placeholder="mail@site.com" required />
        </twig:Input>
        <div class="validator-hint hidden">Enter valid email address</div>
    </div>
    <twig:Button color="neutral" class="join-item">Join</twig:Button>
</div>
```

### Password With Icon And Validator

```twig {"preview":true}
<div class="w-full max-w-xs">
    <twig:Input as="label" class="validator">
        <svg class="h-[1em] opacity-50" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g stroke-linejoin="round" stroke-linecap="round" stroke-width="2.5" fill="none" stroke="currentColor"><path d="M2.586 17.414A2 2 0 0 0 2 18.828V21a1 1 0 0 0 1 1h3a1 1 0 0 0 1-1v-1a1 1 0 0 1 1-1h1a1 1 0 0 0 1-1v-1a1 1 0 0 1 1-1h.172a2 2 0 0 0 1.414-.586l.814-.814a6.5 6.5 0 1 0-4-4z"></path><circle cx="16.5" cy="7.5" r=".5" fill="currentColor"></circle></g></svg>
        <input type="password" required placeholder="Password" minlength="8" pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}" title="Must be more than 8 characters, including number, lowercase letter, uppercase letter" />
    </twig:Input>
    <p class="validator-hint hidden">
        Must be more than 8 characters, including
        <br />At least one number <br />At least one lowercase letter <br />At least one uppercase letter
    </p>
</div>
```

### Number With Validator

```twig {"preview":true}
<div class="w-full max-w-xs">
    <twig:Input type="number" class="validator" required placeholder="Type a number between 1 to 10" min="1" max="10" title="Must be between be 1 to 10" />
    <p class="validator-hint">Must be between be 1 to 10</p>
</div>
```

### Telephone With Icon And Validator

```twig {"preview":true}
<div class="w-full max-w-xs">
    <twig:Input as="label" class="validator">
        <svg class="h-[1em] opacity-50" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><g fill="none"><path d="M7.25 11.5C6.83579 11.5 6.5 11.8358 6.5 12.25C6.5 12.6642 6.83579 13 7.25 13H8.75C9.16421 13 9.5 12.6642 9.5 12.25C9.5 11.8358 9.16421 11.5 8.75 11.5H7.25Z" fill="currentColor"></path><path fill-rule="evenodd" clip-rule="evenodd" d="M6 1C4.61929 1 3.5 2.11929 3.5 3.5V12.5C3.5 13.8807 4.61929 15 6 15H10C11.3807 15 12.5 13.8807 12.5 12.5V3.5C12.5 2.11929 11.3807 1 10 1H6ZM10 2.5H9.5V3C9.5 3.27614 9.27614 3.5 9 3.5H7C6.72386 3.5 6.5 3.27614 6.5 3V2.5H6C5.44771 2.5 5 2.94772 5 3.5V12.5C5 13.0523 5.44772 13.5 6 13.5H10C10.5523 13.5 11 13.0523 11 12.5V3.5C11 2.94772 10.5523 2.5 10 2.5Z" fill="currentColor"></path></g></svg>
        <input type="tel" class="tabular-nums" required placeholder="Phone" pattern="[0-9]*" minlength="10" maxlength="10" title="Must be 10 digits" />
    </twig:Input>
    <p class="validator-hint">Must be 10 digits</p>
</div>
```

### URL With Icon And Validator

```twig {"preview":true}
<div class="w-full max-w-xs">
    <twig:Input as="label" class="validator">
        <svg class="h-[1em] opacity-50" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g stroke-linejoin="round" stroke-linecap="round" stroke-width="2.5" fill="none" stroke="currentColor"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"></path><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"></path></g></svg>
        <input type="url" required placeholder="https://" value="https://" pattern="^(https?://)?([a-zA-Z0-9]([a-zA-Z0-9\-].*[a-zA-Z0-9])?\.)+[a-zA-Z].*$" title="Must be valid URL" />
    </twig:Input>
    <p class="validator-hint">Must be valid URL</p>
</div>
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

```twig {"preview":true}
<div class="flex flex-col gap-8">
    <div class="grid w-xs gap-4" dir="rtl">
        <twig:Input type="text" placeholder="اكتب هنا" />
        <twig:Input color="primary" type="text" placeholder="أساسي" />
        <twig:Input as="label">
            <svg class="h-[1em] opacity-50" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g stroke-linejoin="round" stroke-linecap="round" stroke-width="2.5" fill="none" stroke="currentColor"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.3-4.3"></path></g></svg>
            <input type="search" class="grow" placeholder="بحث" />
            <kbd class="kbd kbd-sm">⌘</kbd>
            <kbd class="kbd kbd-sm">K</kbd>
        </twig:Input>
    </div>
    <div class="grid w-xs gap-4" dir="rtl">
        <twig:Input type="text" placeholder="הקלד כאן" />
        <twig:Input color="primary" type="text" placeholder="ראשי" />
        <twig:Input as="label">
            <svg class="h-[1em] opacity-50" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g stroke-linejoin="round" stroke-linecap="round" stroke-width="2.5" fill="none" stroke="currentColor"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.3-4.3"></path></g></svg>
            <input type="search" class="grow" placeholder="חיפוש" />
            <kbd class="kbd kbd-sm">⌘</kbd>
            <kbd class="kbd kbd-sm">K</kbd>
        </twig:Input>
    </div>
</div>
```

## API Reference

::: api-reference
