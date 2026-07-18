# Alert

Provides contextual feedback messages for typical user actions.

```twig {"preview":true,"height":"210px"}
<twig:Alert color="success" heading="Well done!" dismissible>
    Your changes were saved successfully. <a href="#" class="alert-link">Review them now</a>.
</twig:Alert>
```

## Installation

::: installation

## Usage

```twig
<twig:Alert>A simple primary alert - check it out!</twig:Alert>
```

## Accessibility

Do not rely on color alone to communicate the alert's meaning. Include clear visible text or additional context for assistive technologies.

Dismissal removes the alert from the document. When appropriate, listen for Bootstrap's `closed.bs.alert` event and move focus to a logical destination.

## Examples

### Contextual variants

Use one of Bootstrap's eight contextual colors to match the message's purpose.

```twig {"preview":true,"height":"620px"}
<div class="d-flex flex-column gap-2">
    <twig:Alert class="mb-0">A simple primary alert - check it out!</twig:Alert>
    <twig:Alert color="secondary" class="mb-0">A simple secondary alert - check it out!</twig:Alert>
    <twig:Alert color="success" class="mb-0">A simple success alert - check it out!</twig:Alert>
    <twig:Alert color="danger" class="mb-0">A simple danger alert - check it out!</twig:Alert>
    <twig:Alert color="warning" class="mb-0">A simple warning alert - check it out!</twig:Alert>
    <twig:Alert color="info" class="mb-0">A simple info alert - check it out!</twig:Alert>
    <twig:Alert color="light" class="mb-0">A simple light alert - check it out!</twig:Alert>
    <twig:Alert color="dark" class="mb-0">A simple dark alert - check it out!</twig:Alert>
</div>
```

### Link color

Use `alert-link` for links that inherit a suitable color from their alert variant.

```twig {"preview":true,"height":"620px"}
<div class="d-flex flex-column gap-2">
    <twig:Alert class="mb-0">A simple primary alert with <a href="#" class="alert-link">an example link</a>. Give it a click if you like.</twig:Alert>
    <twig:Alert color="secondary" class="mb-0">A simple secondary alert with <a href="#" class="alert-link">an example link</a>. Give it a click if you like.</twig:Alert>
    <twig:Alert color="success" class="mb-0">A simple success alert with <a href="#" class="alert-link">an example link</a>. Give it a click if you like.</twig:Alert>
    <twig:Alert color="danger" class="mb-0">A simple danger alert with <a href="#" class="alert-link">an example link</a>. Give it a click if you like.</twig:Alert>
    <twig:Alert color="warning" class="mb-0">A simple warning alert with <a href="#" class="alert-link">an example link</a>. Give it a click if you like.</twig:Alert>
    <twig:Alert color="info" class="mb-0">A simple info alert with <a href="#" class="alert-link">an example link</a>. Give it a click if you like.</twig:Alert>
    <twig:Alert color="light" class="mb-0">A simple light alert with <a href="#" class="alert-link">an example link</a>. Give it a click if you like.</twig:Alert>
    <twig:Alert color="dark" class="mb-0">A simple dark alert with <a href="#" class="alert-link">an example link</a>. Give it a click if you like.</twig:Alert>
</div>
```

### Additional content

Alerts can contain headings, paragraphs, dividers, and other structured content.

```twig {"preview":true,"height":"300px"}
<twig:Alert color="success" heading="Well done!">
    <p>Aww yeah, you successfully read this important alert message. This example text is long enough to show how spacing works with additional content.</p>
    <hr>
    <p class="mb-0">Whenever you need to, use margin utilities to keep things nice and tidy.</p>
</twig:Alert>
```

### Icons

Combine alerts with flex utilities and accessible inline icons.

```twig {"preview":true,"height":"420px"}
<div class="d-flex flex-column gap-2">
    <twig:Alert class="d-flex align-items-center mb-0">
        <svg xmlns="http://www.w3.org/2000/svg" class="bi flex-shrink-0 me-2" width="16" height="16" viewBox="0 0 16 16" role="img" aria-label="Warning:">
            <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z" />
        </svg>
        <div>An example alert with an icon</div>
    </twig:Alert>

    <svg xmlns="http://www.w3.org/2000/svg" class="d-none">
        <symbol id="check-circle-fill" viewBox="0 0 16 16">
            <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z" />
        </symbol>
        <symbol id="info-fill" viewBox="0 0 16 16">
            <path d="M8 16A8 8 0 1 0 8 0a8 8 0 0 0 0 16zm.93-9.412-1 4.705c-.07.34.029.533.304.533.194 0 .487-.07.686-.246l-.088.416c-.287.346-.92.598-1.465.598-.703 0-1.002-.422-.808-1.319l.738-3.468c.064-.293.006-.399-.287-.47l-.451-.081.082-.381 2.29-.287zM8 5.5a1 1 0 1 1 0-2 1 1 0 0 1 0 2z" />
        </symbol>
        <symbol id="exclamation-triangle-fill" viewBox="0 0 16 16">
            <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z" />
        </symbol>
    </svg>

    <twig:Alert class="d-flex align-items-center mb-0">
        <svg class="bi flex-shrink-0 me-2" width="16" height="16" role="img" aria-label="Info:"><use href="#info-fill" /></svg>
        <div>An example alert with an icon</div>
    </twig:Alert>
    <twig:Alert color="success" class="d-flex align-items-center mb-0">
        <svg class="bi flex-shrink-0 me-2" width="16" height="16" role="img" aria-label="Success:"><use href="#check-circle-fill" /></svg>
        <div>An example success alert with an icon</div>
    </twig:Alert>
    <twig:Alert color="warning" class="d-flex align-items-center mb-0">
        <svg class="bi flex-shrink-0 me-2" width="16" height="16" role="img" aria-label="Warning:"><use href="#exclamation-triangle-fill" /></svg>
        <div>An example warning alert with an icon</div>
    </twig:Alert>
    <twig:Alert color="danger" class="d-flex align-items-center mb-0">
        <svg class="bi flex-shrink-0 me-2" width="16" height="16" role="img" aria-label="Danger:"><use href="#exclamation-triangle-fill" /></svg>
        <div>An example danger alert with an icon</div>
    </twig:Alert>
</div>
```

### Dismissing

Enable Bootstrap's alert plugin with a close button, dismissal classes, and transition classes.

```twig {"preview":true,"height":"180px"}
<twig:Alert color="warning" dismissible>
    <strong>Holy guacamole!</strong> You should check in on some of those fields below.
</twig:Alert>
```

## API Reference

::: api-reference
