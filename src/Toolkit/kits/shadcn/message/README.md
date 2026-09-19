# Message

Displays a message in a conversation, with optional avatar, header, footer, and alignment.

```twig {"preview":true}
<div class="mx-auto flex w-full max-w-sm flex-col gap-6 py-12">
    <twig:Message align="end">
        <twig:Message:Avatar>
            <twig:Avatar>
                <twig:Avatar:Image src="https://github.com/shadcn.png" alt="@shadcn" />
                <twig:Avatar:Fallback>ME</twig:Avatar:Fallback>
            </twig:Avatar>
        </twig:Message:Avatar>
        <twig:Message:Content>
            <twig:Bubble>
                <twig:Bubble:Content>Deploying to prod real quick.</twig:Bubble:Content>
            </twig:Bubble>
        </twig:Message:Content>
    </twig:Message>
    <twig:Message>
        <twig:Message:Avatar>
            <twig:Avatar>
                <twig:Avatar:Image src="https://github.com/evilrabbit.png" alt="@evilrabbit" />
                <twig:Avatar:Fallback>R</twig:Avatar:Fallback>
            </twig:Avatar>
        </twig:Message:Avatar>
        <twig:Message:Content>
            <twig:Bubble variant="muted">
                <twig:Bubble:Content>It's 4:55 PM. On a Friday.</twig:Bubble:Content>
            </twig:Bubble>
        </twig:Message:Content>
    </twig:Message>
    <twig:Message align="end">
        <twig:Message:Avatar>
            <twig:Avatar>
                <twig:Avatar:Image src="https://github.com/shadcn.png" alt="@shadcn" />
                <twig:Avatar:Fallback>ME</twig:Avatar:Fallback>
            </twig:Avatar>
        </twig:Message:Avatar>
        <twig:Message:Content>
            <twig:Bubble>
                <twig:Bubble:Content>It's a one-line change.</twig:Bubble:Content>
            </twig:Bubble>
            <twig:Message:Footer>Delivered</twig:Message:Footer>
        </twig:Message:Content>
    </twig:Message>
    <twig:Message>
        <twig:Message:Avatar>
            <twig:Avatar>
                <twig:Avatar:Image src="https://github.com/evilrabbit.png" alt="@evilrabbit" />
                <twig:Avatar:Fallback>R</twig:Avatar:Fallback>
            </twig:Avatar>
        </twig:Message:Avatar>
        <twig:Message:Content>
            <twig:Bubble:Group>
                <twig:Bubble variant="muted">
                    <twig:Bubble:Content>It's always a one-line change 😭.</twig:Bubble:Content>
                </twig:Bubble>
                <twig:Bubble variant="muted">
                    <twig:Bubble:Content>Alright, let me take a look.</twig:Bubble:Content>
                    <twig:Bubble:Reactions role="img" aria-label="Reaction: thumbs up">
                        <span>👍</span>
                    </twig:Bubble:Reactions>
                </twig:Bubble>
            </twig:Bubble:Group>
        </twig:Message:Content>
    </twig:Message>
    <twig:Marker role="status">
        <twig:Marker:Content class="animate-pulse">
            <span class="font-medium">Oliver</span> is typing...
        </twig:Marker:Content>
    </twig:Marker>
</div>
```

## Installation

::: installation

## Usage

```twig
<twig:Message align="start | end">
    <twig:Message:Avatar>
        <twig:Avatar>
            <twig:Avatar:Image src="https://github.com/shadcn.png" alt="@shadcn" />
            <twig:Avatar:Fallback>CN</twig:Avatar:Fallback>
        </twig:Avatar>
    </twig:Message:Avatar>
    <twig:Message:Content>
        <twig:Message:Header>Olivia</twig:Message:Header>
        <twig:Bubble variant="muted">
            <twig:Bubble:Content>How can I help you today?</twig:Bubble:Content>
        </twig:Bubble>
        <twig:Message:Footer>Delivered</twig:Message:Footer>
    </twig:Message:Content>
</twig:Message>
```

`Message` owns the row layout — avatar, alignment, header and footer. Render the visible message surface inside it with `Bubble`.

## Examples

### Avatar

Use `Message:Avatar` to render an avatar next to the message. Set `align="end"` on the message to align the avatar to the end of the conversation.

```twig {"preview":true}
<div class="mx-auto flex w-full max-w-sm flex-col gap-6 py-12">
    <twig:Message>
        <twig:Message:Avatar>
            <twig:Avatar>
                <twig:Avatar:Image src="https://github.com/evilrabbit.png" alt="@evilrabbit" />
                <twig:Avatar:Fallback>R</twig:Avatar:Fallback>
            </twig:Avatar>
        </twig:Message:Avatar>
        <twig:Message:Content>
            <twig:Bubble variant="muted">
                <twig:Bubble:Content>The build failed during dependency installation.</twig:Bubble:Content>
            </twig:Bubble>
        </twig:Message:Content>
    </twig:Message>
    <twig:Message align="end">
        <twig:Message:Avatar>
            <twig:Avatar>
                <twig:Avatar:Image src="https://github.com/shadcn.png" alt="@shadcn" />
                <twig:Avatar:Fallback>CN</twig:Avatar:Fallback>
            </twig:Avatar>
        </twig:Message:Avatar>
        <twig:Message:Content>
            <twig:Bubble>
                <twig:Bubble:Content>Can you share the exact error?</twig:Bubble:Content>
            </twig:Bubble>
        </twig:Message:Content>
    </twig:Message>
    <twig:Message>
        <twig:Message:Avatar>
            <twig:Avatar>
                <twig:Avatar:Image src="https://github.com/evilrabbit.png" alt="@evilrabbit" />
                <twig:Avatar:Fallback>R</twig:Avatar:Fallback>
            </twig:Avatar>
        </twig:Message:Avatar>
        <twig:Message:Content>
            <twig:Bubble:Group>
                <twig:Bubble variant="muted">
                    <twig:Bubble:Content>Here's the error from the logs</twig:Bubble:Content>
                </twig:Bubble>
                <twig:Bubble variant="muted">
                    <twig:Bubble:Content>
                        Something went wrong with the build. The libraries are not installed correctly. Try running the build again.
                    </twig:Bubble:Content>
                </twig:Bubble>
            </twig:Bubble:Group>
        </twig:Message:Content>
    </twig:Message>
</div>
```

### Group

Use `Message:Group` to stack consecutive messages from the same sender. Render an empty `Message:Avatar` on the earlier messages to keep them aligned with the avatar on the last one.

```twig {"preview":true}
<div class="mx-auto flex w-full max-w-sm flex-col gap-6 py-12">
    <twig:Message:Group>
        <twig:Message>
            <twig:Message:Avatar />
            <twig:Message:Content>
                <twig:Bubble variant="muted">
                    <twig:Bubble:Content>I checked the registry addresses.</twig:Bubble:Content>
                </twig:Bubble>
            </twig:Message:Content>
        </twig:Message>
        <twig:Message>
            <twig:Message:Avatar>
                <twig:Avatar>
                    <twig:Avatar:Image src="https://github.com/evilrabbit.png" alt="@evilrabbit" />
                    <twig:Avatar:Fallback>CN</twig:Avatar:Fallback>
                </twig:Avatar>
            </twig:Message:Avatar>
            <twig:Message:Content>
                <twig:Bubble variant="muted">
                    <twig:Bubble:Content>The component and example JSON now live under the UI registry.</twig:Bubble:Content>
                </twig:Bubble>
            </twig:Message:Content>
        </twig:Message>
    </twig:Message:Group>
</div>
```

### Header and Footer

Use `Message:Header` for a sender name and `Message:Footer` for metadata such as a delivery or read status.

```twig {"preview":true}
<div class="mx-auto flex w-full max-w-sm flex-col gap-8 py-12">
    <twig:Message>
        <twig:Message:Content>
            <twig:Message:Header>Olivia</twig:Message:Header>
            <twig:Bubble variant="muted">
                <twig:Bubble:Content>I already checked the logs.</twig:Bubble:Content>
            </twig:Bubble>
        </twig:Message:Content>
    </twig:Message>
    <twig:Message align="end">
        <twig:Message:Content>
            <twig:Bubble>
                <twig:Bubble:Content>Send the report to the team. Ping @shadcn if you need help.</twig:Bubble:Content>
            </twig:Bubble>
            <twig:Message:Footer>
                <div>
                    Read <span class="font-normal">Yesterday</span>
                </div>
            </twig:Message:Footer>
        </twig:Message:Content>
    </twig:Message>
</div>
```

### Actions

Place message-level actions in `Message:Footer`, such as copy, retry, or feedback buttons.

```twig {"preview":true}
<div class="mx-auto flex w-full max-w-sm flex-col gap-8 py-12">
    <twig:Message>
        <twig:Message:Content>
            <twig:Bubble variant="muted">
                <twig:Bubble:Content>The install failure is coming from the workspace package.</twig:Bubble:Content>
            </twig:Bubble>
            <twig:Message:Footer>
                <twig:Button variant="ghost" size="icon" aria-label="Copy" title="Copy">
                    <twig:ux:icon name="lucide:copy" />
                </twig:Button>
                <twig:Button variant="ghost" size="icon" aria-label="Like" title="Like">
                    <twig:ux:icon name="lucide:thumbs-up" />
                </twig:Button>
                <twig:Button variant="ghost" size="icon" aria-label="Dislike" title="Dislike">
                    <twig:ux:icon name="lucide:thumbs-down" />
                </twig:Button>
            </twig:Message:Footer>
        </twig:Message:Content>
    </twig:Message>
    <twig:Message align="end">
        <twig:Message:Content>
            <twig:Bubble>
                <twig:Bubble:Content>Okay drop me a link. Taking a look...</twig:Bubble:Content>
            </twig:Bubble>
            <twig:Message:Footer class="gap-2">
                <span class="font-normal text-destructive">Failed to send</span>
                <twig:Button variant="ghost" size="icon-xs" aria-label="Retry" title="Retry">
                    <twig:ux:icon name="lucide:refresh-ccw" />
                </twig:Button>
            </twig:Message:Footer>
        </twig:Message:Content>
    </twig:Message>
</div>
```

### Attachment

Render an `Attachment` inside `Message:Content` to send a file or an image alongside the message.

```twig {"preview":true}
<div class="mx-auto flex w-full max-w-sm flex-col gap-8 py-12">
    <twig:Message align="end">
        <twig:Message:Content>
            <twig:Attachment orientation="vertical">
                <twig:Attachment:Media variant="image">
                    <img src="https://images.unsplash.com/photo-1497366754035-f200968a6e72?w=900&auto=format&fit=crop&q=80" alt="Workspace" />
                </twig:Attachment:Media>
            </twig:Attachment>
            <twig:Bubble>
                <twig:Bubble:Content>Here's the image. Can you add it to the PDF? Use it for the cover page.</twig:Bubble:Content>
            </twig:Bubble>
        </twig:Message:Content>
    </twig:Message>
    <twig:Message>
        <twig:Message:Content>
            <twig:Bubble variant="muted">
                <twig:Bubble:Content>Done. Here's the PDF with the image added as the cover page.</twig:Bubble:Content>
            </twig:Bubble>
            <twig:Attachment>
                <twig:Attachment:Media>
                    <twig:ux:icon name="lucide:file-text" />
                </twig:Attachment:Media>
                <twig:Attachment:Content>
                    <twig:Attachment:Title>sales-dashboard.pdf</twig:Attachment:Title>
                    <twig:Attachment:Description>PDF · 2.4 MB</twig:Attachment:Description>
                </twig:Attachment:Content>
                <twig:Attachment:Actions>
                    <twig:Attachment:Action variant="secondary" size="icon-sm" aria-label="Download" title="Download">
                        <twig:ux:icon name="lucide:download" />
                    </twig:Attachment:Action>
                </twig:Attachment:Actions>
            </twig:Attachment>
        </twig:Message:Content>
    </twig:Message>
    <twig:Message align="end">
        <twig:Message:Content>
            <twig:Bubble>
                <twig:Bubble:Content>Thanks. Looks good.</twig:Bubble:Content>
            </twig:Bubble>
        </twig:Message:Content>
    </twig:Message>
</div>
```

### Markdown

Render assistant text through a `ghost` bubble, so the response is unframed and can span the full width of the conversation.

```twig {"preview":true}
<div class="mx-auto flex w-full max-w-sm flex-col gap-8 py-12">
    <twig:Message align="end">
        <twig:Message:Content>
            <twig:Bubble>
                <twig:Bubble:Content>How do I render markdown in a message?</twig:Bubble:Content>
            </twig:Bubble>
        </twig:Message:Content>
    </twig:Message>
    <twig:Message>
        <twig:Message:Content>
            <twig:Bubble variant="ghost">
                <twig:Bubble:Content class="flex flex-col gap-4">
                    <p>Here's how to render markdown in a message:</p>
                    <ol class="flex list-decimal flex-col gap-2 ps-5">
                        <li>Render assistant text through <strong>Markdown</strong>.</li>
                        <li>Keep user messages as plain text.</li>
                        <li>Use a <code class="rounded bg-muted px-1 py-0.5 text-xs">ghost</code> bubble so the response is unframed.</li>
                    </ol>
                </twig:Bubble:Content>
            </twig:Bubble>
        </twig:Message:Content>
    </twig:Message>
</div>
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

```twig {"preview":true}
<div class="flex w-full flex-col items-center gap-8">
    {# Arabic #}
    <div class="flex w-full max-w-sm flex-col gap-6 py-10" dir="rtl">
        <twig:Message>
            <twig:Message:Avatar>
                <twig:Avatar>
                    <twig:Avatar:Image src="https://github.com/evilrabbit.png" alt="@evilrabbit" />
                    <twig:Avatar:Fallback>R</twig:Avatar:Fallback>
                </twig:Avatar>
            </twig:Message:Avatar>
            <twig:Message:Content>
                <twig:Message:Header>أوليفيا</twig:Message:Header>
                <twig:Bubble variant="muted">
                    <twig:Bubble:Content>فشل البناء أثناء تثبيت الاعتماديات.</twig:Bubble:Content>
                </twig:Bubble>
            </twig:Message:Content>
        </twig:Message>
        <twig:Message align="end">
            <twig:Message:Avatar>
                <twig:Avatar>
                    <twig:Avatar:Image src="https://github.com/shadcn.png" alt="@shadcn" />
                    <twig:Avatar:Fallback>CN</twig:Avatar:Fallback>
                </twig:Avatar>
            </twig:Message:Avatar>
            <twig:Message:Content>
                <twig:Bubble>
                    <twig:Bubble:Content>هل يمكنك مشاركة رسالة الخطأ بالضبط؟</twig:Bubble:Content>
                </twig:Bubble>
                <twig:Message:Footer>تم التسليم</twig:Message:Footer>
            </twig:Message:Content>
        </twig:Message>
    </div>

    {# Hebrew #}
    <div class="flex w-full max-w-sm flex-col gap-6 py-10" dir="rtl">
        <twig:Message>
            <twig:Message:Avatar>
                <twig:Avatar>
                    <twig:Avatar:Image src="https://github.com/evilrabbit.png" alt="@evilrabbit" />
                    <twig:Avatar:Fallback>R</twig:Avatar:Fallback>
                </twig:Avatar>
            </twig:Message:Avatar>
            <twig:Message:Content>
                <twig:Message:Header>אוליביה</twig:Message:Header>
                <twig:Bubble variant="muted">
                    <twig:Bubble:Content>הבנייה נכשלה במהלך התקנת התלויות.</twig:Bubble:Content>
                </twig:Bubble>
            </twig:Message:Content>
        </twig:Message>
        <twig:Message align="end">
            <twig:Message:Avatar>
                <twig:Avatar>
                    <twig:Avatar:Image src="https://github.com/shadcn.png" alt="@shadcn" />
                    <twig:Avatar:Fallback>CN</twig:Avatar:Fallback>
                </twig:Avatar>
            </twig:Message:Avatar>
            <twig:Message:Content>
                <twig:Bubble>
                    <twig:Bubble:Content>תוכל לשתף את הודעת השגיאה המדויקת?</twig:Bubble:Content>
                </twig:Bubble>
                <twig:Message:Footer>נמסר</twig:Message:Footer>
            </twig:Message:Content>
        </twig:Message>
    </div>
</div>
```

## Accessibility

- `Message`, `Message:Group` and every other part of this recipe render plain `<div>`s and add no role, so a message is announced as ordinary content: who sent it must be clear from `Message:Header` or the surrounding text, not from the alignment or the bubble colour.
- The `data-align` attribute is a styling hook only and carries no semantics. `align="end"` reverses the visual row order but not the DOM order, so the reading order stays avatar-then-message in both directions.
- Action buttons in `Message:Footer` are usually icon-only, so give each one an `aria-label`, e.g. `<twig:Button variant="ghost" size="icon" aria-label="Copy">`.
- An empty `Message:Avatar` used as a spacer in a `Message:Group` holds no text and needs no label: it is announced as nothing, which is the intent.
- For an in-progress message such as "Oliver is typing...", use a `Marker` with `role="status"` so assistive tech announces the update as it appears.
- For a conversation that receives messages after load, put `role="log"` on the container that wraps the messages so new ones are announced as they arrive. That container belongs to the consumer, not to this recipe.

## API Reference

::: api-reference
