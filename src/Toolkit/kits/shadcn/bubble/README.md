# Bubble

Displays conversational content in a message bubble. Supports variants, alignment, grouping, reactions, and collapsible content.

```twig {"preview":true,"height":"520px"}
<div class="flex w-full max-w-sm flex-col gap-8 py-12">
    <twig:Bubble align="end">
        <twig:Bubble:Content>Hey there! what's up?</twig:Bubble:Content>
    </twig:Bubble>
    <twig:Bubble:Group>
        <twig:Bubble variant="muted">
            <twig:Bubble:Content>Hey! Want to see chat bubbles?</twig:Bubble:Content>
        </twig:Bubble>
        <twig:Bubble variant="muted">
            <twig:Bubble:Content>
                I can group messages, switch sides, and keep the whole thread easy to scan.
            </twig:Bubble:Content>
            <twig:Bubble:Reactions role="img" aria-label="Reaction: thumbs up">
                <span>👍</span>
            </twig:Bubble:Reactions>
        </twig:Bubble>
    </twig:Bubble:Group>
    <twig:Bubble align="end">
        <twig:Bubble:Content>Sure. Hit me with your best demo.</twig:Bubble:Content>
    </twig:Bubble>
    <twig:Bubble variant="muted">
        <twig:Bubble:Content>
            Yes. You are reading a demo that is demoing itself. Very meta. Very on-brand.
        </twig:Bubble:Content>
        <twig:Bubble:Reactions role="img" aria-label="Reactions: thumbs up, fire, eyes, and 2 more">
            <span>👍</span>
            <span>🔥</span>
            <span>👀</span>
            <span>+2</span>
        </twig:Bubble:Reactions>
    </twig:Bubble>
</div>
```

## Installation

::: installation

## Usage

```twig
<twig:Bubble variant="default" align="start">
    <twig:Bubble:Content>
        I checked the registry output and removed the stale route.
    </twig:Bubble:Content>
    <twig:Bubble:Reactions side="bottom" align="end">
        <span>👍</span>
    </twig:Bubble:Reactions>
</twig:Bubble>
```

## Examples

### Variants

Use the `variant` prop to change the visual treatment of the bubble. A bubble sizes to its content, up to 80% of the container width. The `ghost` variant removes the max-width so assistant text and rich content can span the full row.

```twig {"preview":true,"height":"860px"}
<div class="flex w-full max-w-sm flex-col gap-12 py-12">
    <twig:Bubble>
        <twig:Bubble:Content>This is the default primary bubble.</twig:Bubble:Content>
    </twig:Bubble>
    <twig:Bubble variant="secondary" align="end">
        <twig:Bubble:Content>This is the secondary variant.</twig:Bubble:Content>
    </twig:Bubble>
    <twig:Bubble variant="muted">
        <twig:Bubble:Content>
            This one is muted. It uses a lower emphasis color for the chat bubble.
        </twig:Bubble:Content>
        <twig:Bubble:Reactions role="img" aria-label="Reaction: thumbs up">
            <span>👍</span>
        </twig:Bubble:Reactions>
    </twig:Bubble>
    <twig:Bubble variant="tinted" align="end">
        <twig:Bubble:Content>
            This one is tinted. The tint is a softer color derived from the primary color.
        </twig:Bubble:Content>
    </twig:Bubble>
    <twig:Bubble variant="outline">
        <twig:Bubble:Content>We can also use an outlined variant.</twig:Bubble:Content>
    </twig:Bubble>
    <twig:Bubble variant="destructive" align="end">
        <twig:Bubble:Content>Or a destructive variant with a reaction.</twig:Bubble:Content>
        <twig:Bubble:Reactions role="img" aria-label="Reaction: fire">
            <span>🔥</span>
        </twig:Bubble:Reactions>
    </twig:Bubble>
    <twig:Bubble variant="ghost">
        <twig:Bubble:Content>Ghost bubbles are unframed and span the full width of the conversation.</twig:Bubble:Content>
    </twig:Bubble>
</div>
```

### Alignment

Use the `align` prop on `Bubble` to align the bubble to the start or the end of the conversation.

```twig {"preview":true,"height":"280px"}
<div class="flex w-full max-w-sm flex-col gap-8 py-12">
    <twig:Bubble variant="muted">
        <twig:Bubble:Content>
            This bubble is aligned to the start. This is the default alignment.
        </twig:Bubble:Content>
    </twig:Bubble>
    <twig:Bubble align="end">
        <twig:Bubble:Content>
            This bubble is aligned to the end. Use this for user messages.
        </twig:Bubble:Content>
    </twig:Bubble>
</div>
```

### Bubble Group

Use `Bubble:Group` to group consecutive bubbles from the same sender. Note the `align` prop should be set on the `Bubble` component itself, not on the `Bubble:Group` component.

```twig {"preview":true,"height":"480px"}
<div class="flex w-full max-w-sm flex-col gap-8 py-12">
    <twig:Bubble variant="muted">
        <twig:Bubble:Content>Can you tell me what's the issue?</twig:Bubble:Content>
    </twig:Bubble>
    <twig:Bubble:Group>
        <twig:Bubble align="end">
            <twig:Bubble:Content>You tell me!</twig:Bubble:Content>
        </twig:Bubble>
        <twig:Bubble align="end">
            <twig:Bubble:Content>It worked yesterday. You broke it!</twig:Bubble:Content>
        </twig:Bubble>
        <twig:Bubble align="end">
            <twig:Bubble:Content>Find the bug and fix it.</twig:Bubble:Content>
            <twig:Bubble:Reactions align="start" role="img" aria-label="Reactions: eyes">
                <span>👀</span>
            </twig:Bubble:Reactions>
        </twig:Bubble>
    </twig:Bubble:Group>
    <twig:Bubble variant="muted">
        <twig:Bubble:Content>
            Want me to diff yesterday's you against today's you? It's a bit embarrassing.
        </twig:Bubble:Content>
    </twig:Bubble>
</div>
```

### Links and Buttons

Turn a bubble into a link or a button with the `as` prop on `Bubble:Content`.

```twig {"preview":true,"height":"420px"}
<div class="flex w-full max-w-sm flex-col gap-8 py-12">
    <twig:Bubble variant="muted">
        <twig:Bubble:Content>How can I help you today?</twig:Bubble:Content>
    </twig:Bubble>
    <twig:Bubble:Group>
        <twig:Bubble variant="tinted" align="end">
            <twig:Bubble:Content as="button">I forgot my password</twig:Bubble:Content>
        </twig:Bubble>
        <twig:Bubble variant="tinted" align="end">
            <twig:Bubble:Content as="button">I need help with my subscription</twig:Bubble:Content>
        </twig:Bubble>
        <twig:Bubble variant="tinted" align="end">
            <twig:Bubble:Content as="a" href="#">Something else. Talk to a human.</twig:Bubble:Content>
        </twig:Bubble>
    </twig:Bubble:Group>
</div>
```

### Reactions

Use `Bubble:Reactions` to display reactions or quick action buttons. Use the `side` and `align` props to position the row — `side="top"` anchors it to the upper edge. Reactions overlap the bubble edge, so leave vertical space between rows — the example below uses a larger `gap` for this reason.

```twig {"preview":true,"height":"560px"}
<div class="flex w-full max-w-sm flex-col gap-12 py-12">
    <twig:Bubble variant="muted" align="end">
        <twig:Bubble:Content>I don't need tests, I know my code works.</twig:Bubble:Content>
        <twig:Bubble:Reactions align="start" role="img" aria-label="Reactions: thumbs up, surprised">
            <span>👍</span>
            <span>😮</span>
        </twig:Bubble:Reactions>
    </twig:Bubble>
    <twig:Bubble variant="muted">
        <twig:Bubble:Content>
            Bold. Fine I'll add some tests. I'll let you know when they're done.
        </twig:Bubble:Content>
        <twig:Bubble:Reactions role="img" aria-label="Reactions: eyes, rocket, and 2 more">
            <span>👀</span>
            <span>🚀</span>
            <span>+2</span>
        </twig:Bubble:Reactions>
    </twig:Bubble>
    <twig:Bubble variant="default" align="end">
        <twig:Bubble:Content>
            Tests passed on the first try. All 142 of them. Looking good!
        </twig:Bubble:Content>
        <twig:Bubble:Reactions side="top" align="start" role="img" aria-label="Reactions: party popper, clapping hands">
            <span>🎉</span>
            <span>👏</span>
        </twig:Bubble:Reactions>
    </twig:Bubble>
    <twig:Bubble variant="destructive">
        <twig:Bubble:Content>Are you sure I can run this command?</twig:Bubble:Content>
        <twig:Bubble:Reactions>
            <twig:Button variant="ghost" size="xs">Yes, run it</twig:Button>
        </twig:Bubble:Reactions>
    </twig:Bubble>
</div>
```

### Show More / Collapsible

Long bubble content can be composed with `Collapsible` to allow for a show more or show less interaction.

```twig {"preview":true,"height":"520px"}
<div class="flex w-full max-w-sm flex-col gap-8 py-12">
    <twig:Bubble variant="muted">
        <twig:Bubble:Content>How can I help you today?</twig:Bubble:Content>
    </twig:Bubble>
    <twig:Bubble variant="muted" align="end">
        <twig:Bubble:Content>
            <twig:Collapsible class="flex flex-col gap-2">
                <div>
                    The accessibility review found two focus states that were visually too subtle in dark mode.
                </div>
                <twig:Collapsible:Content class="flex flex-col gap-4">
                    <div>
                        I checked the dialog, menu, and drawer paths because each one renders focusable controls inside a layered surface.
                    </div>
                    <div>
                        The dialog and drawer are fine. The menu needs the hover and focus tokens split so keyboard focus stays visible when the pointer is not involved.
                    </div>
                    <div>
                        I also recommend keeping the change in the style file instead of the primitive so the other themes can choose their own focus treatment later.
                    </div>
                </twig:Collapsible:Content>
                <twig:Collapsible:Trigger>
                    <twig:Button variant="link" class="group w-fit gap-1 p-0 text-muted-foreground" {{ ...collapsible_trigger_attrs }}>
                        <span class="group-data-[state=open]:hidden">Show more</span>
                        <span class="hidden group-data-[state=open]:inline">Show less</span>
                        <twig:ux:icon name="lucide:chevron-down" class="size-4 transition-transform group-data-[state=open]:rotate-180" />
                    </twig:Button>
                </twig:Collapsible:Trigger>
            </twig:Collapsible>
        </twig:Bubble:Content>
    </twig:Bubble>
</div>
```

### Tooltip

Pair a bubble with a `Tooltip` to reveal metadata on hover, such as when a message was read.

```twig {"preview":true,"height":"260px"}
<div class="flex w-full max-w-sm flex-col gap-4 py-12">
    <twig:Bubble variant="secondary">
        <twig:Bubble:Content>Did you remove the stale route?</twig:Bubble:Content>
    </twig:Bubble>
    <twig:Bubble align="end">
        <twig:Bubble:Content>Yes, removed it from the registry.</twig:Bubble:Content>
        <twig:Bubble:Reactions>
            <twig:Tooltip id="bubble-read-receipt">
                <twig:Tooltip:Trigger>
                    <twig:Button variant="ghost" size="icon-xs" aria-label="Read receipt" {{ ...tooltip_trigger_attrs }}>
                        <twig:ux:icon name="lucide:check" />
                    </twig:Button>
                </twig:Tooltip:Trigger>
                <twig:Tooltip:Content>Read on Jan 5, 2026 at 4:32 PM</twig:Tooltip:Content>
            </twig:Tooltip>
        </twig:Bubble:Reactions>
    </twig:Bubble>
</div>
```

### Popover

Pair a bubble with a `Popover` to surface more information on demand, such as the full error message for a failed action.

```twig {"preview":true,"height":"420px"}
<div class="flex w-full max-w-sm flex-col gap-4 py-12">
    <twig:Bubble align="end">
        <twig:Bubble:Content>Run the build script.</twig:Bubble:Content>
    </twig:Bubble>
    <twig:Bubble variant="destructive">
        <twig:Bubble:Content>Failed to run the command.</twig:Bubble:Content>
        <twig:Bubble:Reactions>
            <twig:Popover>
                <twig:Popover:Trigger>
                    <twig:Button variant="ghost" size="icon-xs" aria-label="Show error details" class="aria-expanded:text-destructive" {{ ...popover_trigger_attrs }}>
                        <twig:ux:icon name="lucide:info" />
                    </twig:Button>
                </twig:Popover:Trigger>
                <twig:Popover:Content align="end">
                    <div class="flex flex-col gap-1.5">
                        <p class="text-sm font-medium">Command failed with exit code 1</p>
                        <p class="text-sm text-muted-foreground">ENOENT: no such file or directory, open pnpm-lock.yaml</p>
                    </div>
                </twig:Popover:Content>
            </twig:Popover>
        </twig:Bubble:Reactions>
    </twig:Bubble>
</div>
```

### Markdown

Ghost bubbles are a good fit for rendered markdown, since they are unframed and span the full width of the conversation.

```twig {"preview":true,"height":"420px"}
<div class="flex w-full max-w-sm flex-col gap-8 py-12">
    <twig:Bubble align="end" variant="muted">
        <twig:Bubble:Content>Hello! Are you actually <strong>thinking</strong>?</twig:Bubble:Content>
    </twig:Bubble>
    <twig:Bubble variant="ghost">
        <twig:Bubble:Content class="flex flex-col gap-4">
            <p>Ghost bubbles work for assistant text, <strong>markdown</strong>, and other content that should not be framed.</p>
            <p>This is perfect for assistant messages that should not have a frame and can take the full width of the container. You can also render <code class="rounded bg-muted px-1 py-0.5 text-xs">code</code> in it.</p>
            <p>Ghost bubbles are full width and can take the full width of the container.</p>
        </twig:Bubble:Content>
    </twig:Bubble>
</div>
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

```twig {"preview":true,"height":"620px"}
<div class="flex w-full flex-col items-center gap-8">
    {# Arabic #}
    <div class="flex w-full max-w-sm flex-col gap-10 py-10" dir="rtl">
        <twig:Bubble variant="muted">
            <twig:Bubble:Content>هل يمكنك إخباري بما هي المشكلة؟</twig:Bubble:Content>
        </twig:Bubble>
        <twig:Bubble align="end">
            <twig:Bubble:Content>لقد نجحت بالأمس. أنت من عطّلها!</twig:Bubble:Content>
            <twig:Bubble:Reactions align="start" role="img" aria-label="التفاعلات: عيون">
                <span>👀</span>
            </twig:Bubble:Reactions>
        </twig:Bubble>
    </div>

    {# Hebrew #}
    <div class="flex w-full max-w-sm flex-col gap-10 py-10" dir="rtl">
        <twig:Bubble variant="muted">
            <twig:Bubble:Content>תוכל להגיד לי מה הבעיה?</twig:Bubble:Content>
        </twig:Bubble>
        <twig:Bubble align="end">
            <twig:Bubble:Content>זה עבד אתמול. אתה שברת את זה!</twig:Bubble:Content>
            <twig:Bubble:Reactions align="start" role="img" aria-label="תגובות: עיניים">
                <span>👀</span>
            </twig:Bubble:Reactions>
        </twig:Bubble>
    </div>
</div>
```

## Accessibility

- `Bubble` and `Bubble:Group` render plain `<div>`s and add no role, so they are announced as ordinary content: who sent a message must be clear from the text or surrounding markup, not from the bubble's colour or alignment.
- `Bubble:Content` renders a `<div>` by default. Use `as="button"` or `as="a"` to make a bubble interactive, which keeps it focusable and keyboard-operable; the component sets `type="button"` itself when `as="button"`.
- A `Bubble:Reactions` holding emoji needs `role="img"` together with an `aria-label` that names the reactions, otherwise screen readers announce the raw emoji characters one by one, e.g. `aria-label="Reactions: thumbs up, fire, eyes, and 2 more"`.
- Do not put `role="img"` on a `Bubble:Reactions` that contains a button, such as a `Tooltip` or `Popover` trigger: `role="img"` makes its descendants presentational, hiding the button from assistive tech and making it unreachable. The Tooltip and Popover examples in this README deliberately omit the role for that reason.
- The `data-variant`, `data-align` and `data-side` attributes are styling hooks only and carry no semantics.
- For a conversation that receives messages after load, put `role="log"` on the container that wraps the bubbles so new messages are announced as they arrive. That container belongs to the consumer, not to this recipe.

## API Reference

::: api-reference
