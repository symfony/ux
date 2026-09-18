# Progress

Displays an indicator showing the completion progress of a task, typically displayed as a progress bar.

```twig {"preview":true}
<twig:Progress value="56" class="w-[60%]" />
```

## Installation

::: installation

## Usage

```twig
<twig:Progress value="33" />
```

## Examples

### Label

Use a `Field` component to add a label to the progress bar.

```twig {"preview":true}
<twig:Field class="w-full max-w-sm">
    <twig:Field:Label for="progress-upload">
        <span>Upload progress</span>
        <span class="ml-auto">56%</span>
    </twig:Field:Label>
    <twig:Progress value="56" id="progress-upload" />
</twig:Field>
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

```twig {"preview":true}
<div class="flex flex-col gap-8 w-full items-center">
    {# Arabic #}
    <twig:Field class="w-full max-w-sm" dir="rtl">
        <twig:Field:Label for="progress-upload-ar">
            <span>تقدم الرفع</span>
            <span class="ms-auto">٥٦%</span>
        </twig:Field:Label>
        <twig:Progress value="56" id="progress-upload-ar" class="rtl:rotate-180" />
    </twig:Field>
    {# Hebrew #}
    <twig:Field class="w-full max-w-sm" dir="rtl">
        <twig:Field:Label for="progress-upload-he">
            <span>התקדמות העלאה</span>
            <span class="ms-auto">56%</span>
        </twig:Field:Label>
        <twig:Progress value="56" id="progress-upload-he" class="rtl:rotate-180" />
    </twig:Field>
</div>
```

## Accessibility

- `Progress` renders `role="progressbar"` with `aria-valuemin="0"`, `aria-valuemax="100"` and an `aria-valuenow` reflecting the `value` prop, so assistive tech announces how far along the task is.
- The component has no name of its own. Give it an `aria-label`, or an `aria-labelledby` pointing at the text that describes the task.
- The progress bar is not focusable and does not update on its own. Re-render it, or update `aria-valuenow` from your own code, as the task advances.
- `aria-valuenow` is always rendered, so `Progress` is never announced as indeterminate. Use `Spinner` for a task whose progress cannot be measured.

## API Reference

::: api-reference
