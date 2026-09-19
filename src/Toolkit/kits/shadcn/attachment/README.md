# Attachment

Displays a file or image attachment with media, metadata, upload state, and actions.

```twig {"preview":true}
<div class="mx-auto flex w-full max-w-sm flex-col gap-3">
    <twig:Attachment:Group>
        <twig:Attachment orientation="vertical">
            <twig:Attachment:Media variant="image">
                <img src="https://images.unsplash.com/photo-1497366754035-f200968a6e72?w=900&auto=format&fit=crop&q=80" alt="Workspace" />
            </twig:Attachment:Media>
            <twig:Attachment:Content>
                <twig:Attachment:Title>workspace.png</twig:Attachment:Title>
                <twig:Attachment:Description>PNG · 820 KB</twig:Attachment:Description>
            </twig:Attachment:Content>
        </twig:Attachment>
        <twig:Attachment orientation="vertical">
            <twig:Attachment:Media variant="image">
                <img src="https://images.unsplash.com/photo-1497215728101-856f4ea42174?w=900&auto=format&fit=crop&q=80" alt="Desk" />
            </twig:Attachment:Media>
            <twig:Attachment:Content>
                <twig:Attachment:Title>desk-reference.jpg</twig:Attachment:Title>
                <twig:Attachment:Description>JPG · 1.1 MB</twig:Attachment:Description>
            </twig:Attachment:Content>
        </twig:Attachment>
        <twig:Attachment orientation="vertical">
            <twig:Attachment:Media variant="image">
                <img src="https://images.unsplash.com/photo-1497366811353-6870744d04b2?w=900&auto=format&fit=crop&q=80" alt="Office" />
            </twig:Attachment:Media>
            <twig:Attachment:Content>
                <twig:Attachment:Title>office-reference.jpg</twig:Attachment:Title>
                <twig:Attachment:Description>JPG · 940 KB</twig:Attachment:Description>
            </twig:Attachment:Content>
        </twig:Attachment>
    </twig:Attachment:Group>
    <twig:Attachment state="uploading" class="w-full">
        <twig:Attachment:Media>
            <twig:Spinner />
        </twig:Attachment:Media>
        <twig:Attachment:Content>
            <twig:Attachment:Title>sales-dashboard.pdf</twig:Attachment:Title>
            <twig:Attachment:Description>Uploading · 64%</twig:Attachment:Description>
        </twig:Attachment:Content>
        <twig:Attachment:Actions>
            <twig:Attachment:Action aria-label="Cancel upload">
                <twig:ux:icon name="lucide:x" />
            </twig:Attachment:Action>
        </twig:Attachment:Actions>
    </twig:Attachment>
    <twig:Attachment class="w-full">
        <twig:Attachment:Media>
            <twig:ux:icon name="lucide:file-code" />
        </twig:Attachment:Media>
        <twig:Attachment:Content>
            <twig:Attachment:Title>message-renderer.tsx</twig:Attachment:Title>
            <twig:Attachment:Description>TypeScript · 12 KB</twig:Attachment:Description>
        </twig:Attachment:Content>
        <twig:Attachment:Actions>
            <twig:Attachment:Action aria-label="Remove message-renderer.tsx">
                <twig:ux:icon name="lucide:x" />
            </twig:Attachment:Action>
        </twig:Attachment:Actions>
    </twig:Attachment>
</div>
```

## Installation

::: installation

## Usage

```twig
<twig:Attachment>
    <twig:Attachment:Media>
        <twig:ux:icon name="lucide:file-text" />
    </twig:Attachment:Media>
    <twig:Attachment:Content>
        <twig:Attachment:Title>sales-dashboard.pdf</twig:Attachment:Title>
        <twig:Attachment:Description>PDF · 2.4 MB</twig:Attachment:Description>
    </twig:Attachment:Content>
    <twig:Attachment:Actions>
        <twig:Attachment:Action aria-label="Remove sales-dashboard.pdf">
            <twig:ux:icon name="lucide:x" />
        </twig:Attachment:Action>
    </twig:Attachment:Actions>
</twig:Attachment>
```

## Examples

### Image

Set `variant="image"` on `Attachment:Media` and render an `img` tag inside it. Use `orientation="vertical"` to stack the media above the content.

```twig {"preview":true}
{% set images = [
    { src: 'https://images.unsplash.com/photo-1497366754035-f200968a6e72?w=900&auto=format&fit=crop&q=80', alt: 'Workspace', name: 'workspace.png', meta: 'PNG · 820 KB' },
    { src: 'https://images.unsplash.com/photo-1497215728101-856f4ea42174?w=900&auto=format&fit=crop&q=80', alt: 'Desk', name: 'desk-reference.jpg', meta: 'JPG · 1.1 MB' },
    { src: 'https://images.unsplash.com/photo-1497366811353-6870744d04b2?w=900&auto=format&fit=crop&q=80', alt: 'Office', name: 'office-reference.jpg', meta: 'JPG · 940 KB' },
] %}
<div class="mx-auto w-full max-w-sm">
    <twig:Attachment:Group class="w-full">
        {% for image in images %}
            <twig:Attachment orientation="vertical">
                <twig:Attachment:Media variant="image">
                    <img src="{{ image.src }}" alt="{{ image.alt }}" />
                </twig:Attachment:Media>
                <twig:Attachment:Content>
                    <twig:Attachment:Title>{{ image.name }}</twig:Attachment:Title>
                    <twig:Attachment:Description>{{ image.meta }}</twig:Attachment:Description>
                </twig:Attachment:Content>
                <twig:Attachment:Actions>
                    <twig:Attachment:Action aria-label="Remove {{ image.name }}">
                        <twig:ux:icon name="lucide:x" />
                    </twig:Attachment:Action>
                </twig:Attachment:Actions>
                <twig:Attachment:Trigger
                    as="a"
                    href="{{ image.src }}"
                    target="_blank"
                    rel="noreferrer"
                    aria-label="Open {{ image.name }}"
                />
            </twig:Attachment>
        {% endfor %}
    </twig:Attachment:Group>
</div>
```

### States

Set `state` to reflect the upload lifecycle. `uploading` and `processing` animate the title, and `error` switches to a destructive treatment.

```twig {"preview":true}
<div class="mx-auto flex w-full max-w-sm flex-col gap-2">
    <twig:Attachment state="idle" class="w-full">
        <twig:Attachment:Media>
            <twig:ux:icon name="lucide:clock" />
        </twig:Attachment:Media>
        <twig:Attachment:Content>
            <twig:Attachment:Title>selected-file.pdf</twig:Attachment:Title>
            <twig:Attachment:Description>Ready to upload</twig:Attachment:Description>
        </twig:Attachment:Content>
        <twig:Attachment:Actions>
            <twig:Attachment:Action aria-label="Remove selected-file.pdf">
                <twig:ux:icon name="lucide:x" />
            </twig:Attachment:Action>
        </twig:Attachment:Actions>
    </twig:Attachment>
    <twig:Attachment state="uploading" class="w-full">
        <twig:Attachment:Media>
            <twig:Spinner />
        </twig:Attachment:Media>
        <twig:Attachment:Content>
            <twig:Attachment:Title>design-system.zip</twig:Attachment:Title>
            <twig:Attachment:Description>Uploading · 64%</twig:Attachment:Description>
        </twig:Attachment:Content>
        <twig:Attachment:Actions>
            <twig:Attachment:Action aria-label="Cancel upload">
                <twig:ux:icon name="lucide:x" />
            </twig:Attachment:Action>
        </twig:Attachment:Actions>
    </twig:Attachment>
    <twig:Attachment state="processing" class="w-full">
        <twig:Attachment:Media>
            <twig:ux:icon name="lucide:file-text" />
        </twig:Attachment:Media>
        <twig:Attachment:Content>
            <twig:Attachment:Title>market-research.pdf</twig:Attachment:Title>
            <twig:Attachment:Description>Processing document</twig:Attachment:Description>
        </twig:Attachment:Content>
        <twig:Attachment:Actions>
            <twig:Attachment:Action aria-label="Remove market-research.pdf">
                <twig:ux:icon name="lucide:x" />
            </twig:Attachment:Action>
        </twig:Attachment:Actions>
    </twig:Attachment>
    <twig:Attachment state="error" class="w-full">
        <twig:Attachment:Media>
            <twig:ux:icon name="lucide:file-warning" />
        </twig:Attachment:Media>
        <twig:Attachment:Content>
            <twig:Attachment:Title>financial-model.xlsx</twig:Attachment:Title>
            <twig:Attachment:Description>Upload failed. Try again.</twig:Attachment:Description>
        </twig:Attachment:Content>
        <twig:Attachment:Actions>
            <twig:Attachment:Action aria-label="Retry upload">
                <twig:ux:icon name="lucide:refresh-cw" />
            </twig:Attachment:Action>
            <twig:Attachment:Action aria-label="Remove financial-model.xlsx">
                <twig:ux:icon name="lucide:x" />
            </twig:Attachment:Action>
        </twig:Attachment:Actions>
    </twig:Attachment>
    <twig:Attachment state="done" class="w-full">
        <twig:Attachment:Media>
            <twig:ux:icon name="lucide:check" />
        </twig:Attachment:Media>
        <twig:Attachment:Content>
            <twig:Attachment:Title>uploaded-report.pdf</twig:Attachment:Title>
            <twig:Attachment:Description>Uploaded · 1.8 MB</twig:Attachment:Description>
        </twig:Attachment:Content>
        <twig:Attachment:Actions>
            <twig:Attachment:Action aria-label="Remove uploaded-report.pdf">
                <twig:ux:icon name="lucide:x" />
            </twig:Attachment:Action>
        </twig:Attachment:Actions>
    </twig:Attachment>
</div>
```

### Sizes

Use `size` to switch between `default`, `sm`, and `xs`.

```twig {"preview":true}
<div class="mx-auto flex w-full max-w-sm flex-col gap-3">
    <twig:Attachment size="default" class="w-full">
        <twig:Attachment:Media>
            <twig:ux:icon name="lucide:file-text" />
        </twig:Attachment:Media>
        <twig:Attachment:Content>
            <twig:Attachment:Title>Default attachment</twig:Attachment:Title>
            <twig:Attachment:Description>PDF · 2.4 MB</twig:Attachment:Description>
        </twig:Attachment:Content>
    </twig:Attachment>
    <twig:Attachment size="sm" class="w-full">
        <twig:Attachment:Media>
            <twig:ux:icon name="lucide:file-text" />
        </twig:Attachment:Media>
        <twig:Attachment:Content>
            <twig:Attachment:Title>Small attachment</twig:Attachment:Title>
            <twig:Attachment:Description>PDF · 2.4 MB</twig:Attachment:Description>
        </twig:Attachment:Content>
    </twig:Attachment>
    <twig:Attachment size="xs" class="w-full">
        <twig:Attachment:Media>
            <twig:ux:icon name="lucide:file-text" />
        </twig:Attachment:Media>
        <twig:Attachment:Content>
            <twig:Attachment:Title>Extra small attachment</twig:Attachment:Title>
        </twig:Attachment:Content>
    </twig:Attachment>
</div>
```

### Group

Wrap attachments in `Attachment:Group` to lay them out in a horizontally scrollable, snapping row.

```twig {"preview":true}
{% set files = [
    { icon: 'lucide:file-text', name: 'briefing-notes.pdf', meta: 'PDF · 1.4 MB' },
    { src: 'https://images.unsplash.com/photo-1497366754035-f200968a6e72?w=900&auto=format&fit=crop&q=80', name: 'workspace.png', meta: 'PNG · 820 KB' },
    { icon: 'lucide:table', name: 'customers.csv', meta: 'CSV · 18 KB' },
    { icon: 'lucide:file-code', name: 'renderer.tsx', meta: 'TSX · 12 KB' },
] %}
<div class="mx-auto w-full max-w-sm">
    <twig:Attachment:Group class="w-full">
        {% for file in files %}
            <twig:Attachment class="w-64">
                {% if file.src is defined %}
                    <twig:Attachment:Media variant="image">
                        <img src="{{ file.src }}" alt="{{ file.name }}" />
                    </twig:Attachment:Media>
                {% else %}
                    <twig:Attachment:Media>
                        <twig:ux:icon name="{{ file.icon }}" />
                    </twig:Attachment:Media>
                {% endif %}
                <twig:Attachment:Content>
                    <twig:Attachment:Title>{{ file.name }}</twig:Attachment:Title>
                    <twig:Attachment:Description>{{ file.meta }}</twig:Attachment:Description>
                </twig:Attachment:Content>
                <twig:Attachment:Actions>
                    <twig:Attachment:Action aria-label="Remove {{ file.name }}">
                        <twig:ux:icon name="lucide:x" />
                    </twig:Attachment:Action>
                </twig:Attachment:Actions>
            </twig:Attachment>
        {% endfor %}
    </twig:Attachment:Group>
</div>
```

### Trigger

Add an `Attachment:Trigger` to make the whole card open a link or a dialog. It fills the card behind the actions, so the actions stay clickable.

```twig {"preview":true}
<div class="mx-auto w-full max-w-sm">
    <twig:Dialog id="attachment-preview">
        <twig:Attachment class="w-full">
            <twig:Attachment:Media>
                <twig:ux:icon name="lucide:file-search" />
            </twig:Attachment:Media>
            <twig:Attachment:Content>
                <twig:Attachment:Title>research-summary.pdf</twig:Attachment:Title>
                <twig:Attachment:Description>Open preview dialog</twig:Attachment:Description>
            </twig:Attachment:Content>
            <twig:Attachment:Actions>
                <twig:Attachment:Action aria-label="Copy link">
                    <twig:ux:icon name="lucide:copy" />
                </twig:Attachment:Action>
                <twig:Attachment:Action aria-label="Remove research-summary.pdf">
                    <twig:ux:icon name="lucide:x" />
                </twig:Attachment:Action>
            </twig:Attachment:Actions>
            <twig:Dialog:Trigger>
                <twig:Attachment:Trigger {{ ...dialog_trigger_attrs }} aria-label="Preview research-summary.pdf" />
            </twig:Dialog:Trigger>
        </twig:Attachment>
        <twig:Dialog:Content class="sm:max-w-md">
            <twig:Dialog:Header>
                <twig:Dialog:Title>research-summary.pdf</twig:Dialog:Title>
                <twig:Dialog:Description>
                    The attachment trigger fills the card and opens the dialog, while the actions stay independently clickable above it.
                </twig:Dialog:Description>
            </twig:Dialog:Header>
        </twig:Dialog:Content>
    </twig:Dialog>
</div>
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

```twig {"preview":true}
<div class="flex w-full flex-col items-center gap-8">
    {# Arabic #}
    <div class="flex w-full max-w-sm flex-col gap-3" dir="rtl">
        <twig:Attachment state="uploading" class="w-full">
            <twig:Attachment:Media>
                <twig:Spinner />
            </twig:Attachment:Media>
            <twig:Attachment:Content>
                <twig:Attachment:Title>تقرير-المبيعات.pdf</twig:Attachment:Title>
                <twig:Attachment:Description>جارٍ الرفع · ٦٤٪</twig:Attachment:Description>
            </twig:Attachment:Content>
            <twig:Attachment:Actions>
                <twig:Attachment:Action aria-label="إلغاء الرفع">
                    <twig:ux:icon name="lucide:x" />
                </twig:Attachment:Action>
            </twig:Attachment:Actions>
        </twig:Attachment>
        <twig:Attachment state="error" class="w-full">
            <twig:Attachment:Media>
                <twig:ux:icon name="lucide:file-warning" />
            </twig:Attachment:Media>
            <twig:Attachment:Content>
                <twig:Attachment:Title>النموذج-المالي.xlsx</twig:Attachment:Title>
                <twig:Attachment:Description>فشل الرفع. حاول مرة أخرى.</twig:Attachment:Description>
            </twig:Attachment:Content>
            <twig:Attachment:Actions>
                <twig:Attachment:Action aria-label="إعادة المحاولة">
                    <twig:ux:icon name="lucide:refresh-cw" />
                </twig:Attachment:Action>
            </twig:Attachment:Actions>
        </twig:Attachment>
    </div>

    {# Hebrew #}
    <div class="flex w-full max-w-sm flex-col gap-3" dir="rtl">
        <twig:Attachment orientation="vertical" class="w-full">
            <twig:Attachment:Media variant="image">
                <img src="https://images.unsplash.com/photo-1497366754035-f200968a6e72?w=900&auto=format&fit=crop&q=80" alt="שולחן עבודה" />
            </twig:Attachment:Media>
            <twig:Attachment:Content>
                <twig:Attachment:Title>שולחן-עבודה.png</twig:Attachment:Title>
                <twig:Attachment:Description>PNG · 820 ק״ב</twig:Attachment:Description>
            </twig:Attachment:Content>
            <twig:Attachment:Actions>
                <twig:Attachment:Action aria-label="הסר את שולחן-עבודה.png">
                    <twig:ux:icon name="lucide:x" />
                </twig:Attachment:Action>
            </twig:Attachment:Actions>
        </twig:Attachment>
    </div>
</div>
```

## Accessibility

- `Attachment:Action` is usually icon-only, so give each one an `aria-label` naming the action and its target, such as `aria-label="Remove sales-dashboard.pdf"`.
- `Attachment:Trigger` covers the whole card and carries no text of its own. Give it an `aria-label` describing what activating it does. It sits behind the actions in the stacking order, so an action and the trigger never trap each other.
- `Attachment:Group` scrolls horizontally. Keyboard users reach off-screen attachments by tabbing to their trigger or actions. For a row of purely presentational attachments, make the group itself reachable with `tabindex="0"`, `role="group"` and an `aria-label`.
- The `error` state is shown with a destructive colour. Keep the reason for the failure in `Attachment:Description` so the state is not conveyed by colour alone.
- An icon inside `Attachment:Media` is decorative and `<twig:ux:icon>` hides it from assistive tech, so the name of the file belongs in `Attachment:Title`.

## API Reference

::: api-reference
