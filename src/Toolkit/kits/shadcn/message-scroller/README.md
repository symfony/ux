# Message Scroller

A chat scroll container that anchors turns, opens saved transcripts, follows streamed responses, loads history without jumping, and jumps to any message.

```twig {"preview":true,"collapseClass":true}
<twig:MessageScroller:Provider>
    <twig:Card class="mx-auto h-140 w-full max-w-sm gap-0">
        <twig:Card:Header class="gap-1 border-b">
            <twig:Card:Title>New Chat</twig:Card:Title>
            <twig:Card:Description>How can I help you today?</twig:Card:Description>
        </twig:Card:Header>
        <twig:Card:Content class="min-h-0 flex-1 overflow-hidden p-0">
            <twig:MessageScroller>
                <twig:MessageScroller:Viewport>
                    <twig:MessageScroller:Content class="p-(--card-spacing)">
                        <twig:MessageScroller:Item messageId="demo-1" scrollAnchor>
                            <twig:Message align="end">
                                <twig:Message:Content>
                                    <twig:Bubble variant="muted">
                                        <twig:Bubble:Content>I'm building a chat for our app and the scroll behavior is driving me nuts. Every time the AI streams a reply, the whole thread jumps around.</twig:Bubble:Content>
                                    </twig:Bubble>
                                </twig:Message:Content>
                            </twig:Message>
                        </twig:MessageScroller:Item>
                        <twig:MessageScroller:Item messageId="demo-2">
                            <twig:Message>
                                <twig:Message:Content>
                                    <twig:Bubble variant="ghost">
                                        <twig:Bubble:Content class="space-y-2">
                                            <p class="whitespace-pre-line">That's the classic streaming scroll problem. Wrap your message list in `MessageScroller` and turn on `autoScroll`: the viewport pins to the bottom as tokens arrive, so users always see the latest text land in place.</p>
                                            <p class="whitespace-pre-line">The important part: it only auto-scrolls while the reader is already at the bottom. The moment they scroll up to read something earlier, auto-scroll backs off and their position is preserved. You get smooth streaming without fighting the user's intent.</p>
                                        </twig:Bubble:Content>
                                    </twig:Bubble>
                                </twig:Message:Content>
                            </twig:Message>
                        </twig:MessageScroller:Item>
                        <twig:MessageScroller:Item messageId="demo-3" scrollAnchor>
                            <twig:Message align="end">
                                <twig:Message:Content>
                                    <twig:Bubble variant="muted">
                                        <twig:Bubble:Content>Okay, but when someone sends a new message the view still feels jarring, like the whole conversation reloads from the top.</twig:Bubble:Content>
                                    </twig:Bubble>
                                </twig:Message:Content>
                            </twig:Message>
                        </twig:MessageScroller:Item>
                        <twig:MessageScroller:Item messageId="demo-4">
                            <twig:Message>
                                <twig:Message:Content>
                                    <twig:Bubble variant="ghost">
                                        <twig:Bubble:Content class="space-y-2">
                                            <p class="whitespace-pre-line">`MessageScroller:Item` fixes that with turn anchoring. Set `scrollAnchor` on the turn that should settle near the top instead of blindly snapping to the document bottom.</p>
                                            <p class="whitespace-pre-line">It also leaves a small peek of the previous exchange visible above the anchor, so context isn't lost. The reply starts in view without that disorienting jump you get from a plain overflow container.</p>
                                        </twig:Bubble:Content>
                                    </twig:Bubble>
                                </twig:Message:Content>
                            </twig:Message>
                        </twig:MessageScroller:Item>
                        <twig:MessageScroller:Item messageId="demo-5" scrollAnchor>
                            <twig:Message align="end">
                                <twig:Message:Content>
                                    <twig:Bubble variant="muted">
                                        <twig:Bubble:Content>And if they've scrolled up to re-read an older answer? I don't want to yank them back down.</twig:Bubble:Content>
                                    </twig:Bubble>
                                </twig:Message:Content>
                            </twig:Message>
                        </twig:MessageScroller:Item>
                        <twig:MessageScroller:Item messageId="demo-6">
                            <twig:Message>
                                <twig:Message:Content>
                                    <twig:Bubble variant="ghost">
                                        <twig:Bubble:Content class="space-y-2">
                                            <p class="whitespace-pre-line">You won't. Auto-scroll only runs when the viewport is already pinned to the bottom, so scrolling up is a deliberate opt-out: their place in the thread stays put even as new tokens keep arriving below.</p>
                                            <p class="whitespace-pre-line">When there is content they haven't seen yet, `MessageScroller:Button` appears at the bottom of the viewport. One tap jumps them back to the newest message and re-engages auto-scroll. Same pattern as Slack or iMessage: quiet when you're caught up, helpful when you're not.</p>
                                        </twig:Bubble:Content>
                                    </twig:Bubble>
                                </twig:Message:Content>
                            </twig:Message>
                        </twig:MessageScroller:Item>
                    </twig:MessageScroller:Content>
                </twig:MessageScroller:Viewport>
                <twig:MessageScroller:Button />
            </twig:MessageScroller>
        </twig:Card:Content>
        <twig:Card:Footer class="flex-col gap-2">
            <twig:InputGroup>
                <div class="h-14 w-full px-3 py-2.5">
                    <span class="line-clamp-2">Last one: does this work with assistive tech?</span>
                </div>
                <twig:InputGroup:Addon align="block-end" class="pt-1">
                    <twig:InputGroup:Button variant="outline" size="icon-sm" aria-label="Add files">
                        <twig:ux:icon name="lucide:plus" />
                    </twig:InputGroup:Button>
                    <twig:InputGroup:Button variant="default" size="icon-sm" class="ms-auto" aria-label="Send">
                        <twig:ux:icon name="lucide:arrow-up" />
                    </twig:InputGroup:Button>
                </twig:InputGroup:Addon>
            </twig:InputGroup>
        </twig:Card:Footer>
    </twig:Card>
</twig:MessageScroller:Provider>
```

## Installation

::: installation

## Usage

```twig
<twig:MessageScroller:Provider>
    <twig:MessageScroller>
        <twig:MessageScroller:Viewport>
            <twig:MessageScroller:Content>
                {% for message in messages %}
                    <twig:MessageScroller:Item messageId="{{ message.id }}" :scrollAnchor="message.role == 'user'">
                        <twig:Message>...</twig:Message>
                    </twig:MessageScroller:Item>
                {% endfor %}
            </twig:MessageScroller:Content>
        </twig:MessageScroller:Viewport>
        <twig:MessageScroller:Button />
    </twig:MessageScroller>
</twig:MessageScroller:Provider>
```

`MessageScroller` fills its parent, so place it inside a height-constrained container.

```twig
<div class="flex h-screen flex-col">
    <twig:MessageScroller:Provider>
        <twig:MessageScroller class="flex-1">{# transcript #}</twig:MessageScroller>
    </twig:MessageScroller:Provider>
</div>
```

The scroller does not load or send messages. Add rows to `MessageScroller:Content` the way the page already updates, for instance with a Turbo Stream `append` or `prepend`: the scroller reacts to every row added or removed. Replacing `MessageScroller:Content` as a whole, for instance with a Turbo Stream `replace`, opens the new transcript at its default position.

For a transcript of hundreds of rows, let the browser skip the rendering work of the rows far from the viewport: add `[content-visibility:auto] [contain-intrinsic-size:auto_10rem]` to the `class` of `MessageScroller:Item`. The rows stay in the page, for text selection, find in page and assistive technologies.

## Examples

### Anchoring Turns

A turn is the part of the conversation that starts a new exchange. In a simple AI chat, that is usually the user's message and the assistant reply that follows.

An anchor is the row the viewport treats as the start of that turn. Mark that row with `scrollAnchor`. When a new anchor is appended, the viewport moves it near the top and keeps a peek of the previous row above it, so the new turn does not feel detached from its context. Anchors are not tied to a role: any row can start a turn.

```twig {"preview":true,"collapseClass":true}
<twig:MessageScroller:Provider>
    <twig:Card class="mx-auto h-140 w-full max-w-sm gap-0">
        <twig:Card:Header class="gap-1 border-b">
            <twig:Card:Title>Anchoring Turns</twig:Card:Title>
            <twig:Card:Description>The user messages settle near the top edge.</twig:Card:Description>
        </twig:Card:Header>
        <twig:Card:Content class="min-h-0 flex-1 overflow-hidden p-0">
            <twig:MessageScroller>
                <twig:MessageScroller:Viewport>
                    <twig:MessageScroller:Content class="p-(--card-spacing)">
                        <twig:MessageScroller:Item messageId="anchor-1" scrollAnchor>
                            <twig:Message align="end">
                                <twig:Message:Content>
                                    <twig:Bubble variant="muted">
                                        <twig:Bubble:Content>Can you show me how anchoring behaves when a new prompt starts the turn?</twig:Bubble:Content>
                                    </twig:Bubble>
                                </twig:Message:Content>
                            </twig:Message>
                        </twig:MessageScroller:Item>
                        <twig:MessageScroller:Item messageId="anchor-2">
                            <twig:Message>
                                <twig:Message:Content>
                                    <twig:Bubble variant="ghost">
                                        <twig:Bubble:Content>Append the user prompt first, then append the assistant response. With the user prompt as the anchor, the prompt settles near the top and the assistant response fills in below it.</twig:Bubble:Content>
                                    </twig:Bubble>
                                </twig:Message:Content>
                            </twig:Message>
                        </twig:MessageScroller:Item>
                        <twig:MessageScroller:Item messageId="anchor-3" scrollAnchor>
                            <twig:Message align="end">
                                <twig:Message:Content>
                                    <twig:Bubble variant="muted">
                                        <twig:Bubble:Content>What changes when assistant messages are the anchor?</twig:Bubble:Content>
                                    </twig:Bubble>
                                </twig:Message:Content>
                            </twig:Message>
                        </twig:MessageScroller:Item>
                        <twig:MessageScroller:Item messageId="anchor-4">
                            <twig:Message>
                                <twig:Message:Content>
                                    <twig:Bubble variant="ghost">
                                        <twig:Bubble:Content>Then each assistant response is the item `MessageScroller` keeps in view. This is useful when the reply is the moment you want readers to land on after each turn.</twig:Bubble:Content>
                                    </twig:Bubble>
                                </twig:Message:Content>
                            </twig:Message>
                        </twig:MessageScroller:Item>
                        <twig:MessageScroller:Item messageId="anchor-5" scrollAnchor>
                            <twig:Message align="end">
                                <twig:Message:Content>
                                    <twig:Bubble variant="muted">
                                        <twig:Bubble:Content>Can I switch roles and keep adding turns?</twig:Bubble:Content>
                                    </twig:Bubble>
                                </twig:Message:Content>
                            </twig:Message>
                        </twig:MessageScroller:Item>
                        <twig:MessageScroller:Item messageId="anchor-6">
                            <twig:Message>
                                <twig:Message:Content>
                                    <twig:Bubble variant="ghost">
                                        <twig:Bubble:Content>Yes. The next appended message with the selected role becomes the anchor, so you can compare user and assistant anchoring in the same transcript.</twig:Bubble:Content>
                                    </twig:Bubble>
                                </twig:Message:Content>
                            </twig:Message>
                        </twig:MessageScroller:Item>
                    </twig:MessageScroller:Content>
                </twig:MessageScroller:Viewport>
                <twig:MessageScroller:Button />
            </twig:MessageScroller>
        </twig:Card:Content>
    </twig:Card>
</twig:MessageScroller:Provider>
```

### Group Chat

In a group chat, the turn boundary is often the message that asks the model to respond, or a marker like "Rocky has joined the chat". Typing indicators and history controls usually should not anchor. Because anchoring is not tied to a role, a marker can anchor just like a message.

```twig {"preview":true,"collapseClass":true}
<twig:MessageScroller:Provider>
    <twig:Card class="mx-auto h-140 w-full max-w-sm gap-0">
        <twig:Card:Header class="gap-1 border-b">
            <twig:Card:Title>Group Chat</twig:Card:Title>
            <twig:Card:Description>Several participants and an assistant. The marker starts a turn.</twig:Card:Description>
        </twig:Card:Header>
        <twig:Card:Content class="min-h-0 flex-1 overflow-hidden p-0">
            <twig:MessageScroller>
                <twig:MessageScroller:Viewport>
                    <twig:MessageScroller:Content class="p-(--card-spacing)">
                        <twig:MessageScroller:Item messageId="group-1">
                            <twig:Message align="end">
                                <twig:Message:Content>
                                    <twig:Bubble variant="muted">
                                        <twig:Bubble:Content>@mary, the astrophage line keeps matching Venus energy output. Can you check my math?</twig:Bubble:Content>
                                    </twig:Bubble>
                                </twig:Message:Content>
                            </twig:Message>
                        </twig:MessageScroller:Item>
                        <twig:MessageScroller:Item messageId="group-2">
                            <twig:Message>
                                <twig:Message:Content>
                                    <twig:Message:Header>Mary (Agent)</twig:Message:Header>
                                    <twig:Bubble variant="ghost">
                                        <twig:Bubble:Content>Yes. Confirmed. The curve points to a microorganism harvesting stellar energy and breeding near carbon dioxide. If @rocky agrees, this is the clue we need.</twig:Bubble:Content>
                                    </twig:Bubble>
                                </twig:Message:Content>
                            </twig:Message>
                        </twig:MessageScroller:Item>
                        <twig:MessageScroller:Item messageId="group-3" scrollAnchor>
                            <twig:Message align="end">
                                <twig:Message:Content>
                                    <twig:Bubble variant="muted">
                                        <twig:Bubble:Content>ping @rocky</twig:Bubble:Content>
                                    </twig:Bubble>
                                </twig:Message:Content>
                            </twig:Message>
                        </twig:MessageScroller:Item>
                        <twig:MessageScroller:Item scrollAnchor>
                            <twig:Marker variant="separator">
                                <twig:Marker:Content>Rocky has joined the chat</twig:Marker:Content>
                            </twig:Marker>
                        </twig:MessageScroller:Item>
                        <twig:MessageScroller:Item messageId="group-5">
                            <twig:Message>
                                <twig:Message:Content>
                                    <twig:Message:Header>Rocky</twig:Message:Header>
                                    <twig:Bubble variant="tinted">
                                        <twig:Bubble:Content>Amaze. Astrophage eats light, makes heat, goes to carbon dioxide. Rocky has fuel model. Grace is smart.</twig:Bubble:Content>
                                    </twig:Bubble>
                                </twig:Message:Content>
                            </twig:Message>
                        </twig:MessageScroller:Item>
                    </twig:MessageScroller:Content>
                </twig:MessageScroller:Viewport>
                <twig:MessageScroller:Button />
            </twig:MessageScroller>
        </twig:Card:Content>
    </twig:Card>
</twig:MessageScroller:Provider>
```

### Keeping Context Visible

When a new turn starts, it should still feel like part of the same thread. `scrollPreviousItemPeek` keeps a slice of the previous row visible above the anchor, 64 pixels by default, so the reader keeps their context instead of feeling like the conversation restarted on a blank page. Here it keeps 96 pixels, below a `scrollMargin` of 24 pixels, and the transcript opens on its last turn.

```twig {"preview":true,"collapseClass":true}
<twig:MessageScroller:Provider defaultScrollPosition="last-anchor" scrollMargin="24" scrollPreviousItemPeek="96">
    <twig:Card class="mx-auto h-140 w-full max-w-sm gap-0">
        <twig:Card:Header class="gap-1 border-b">
            <twig:Card:Title>Keeping Context Visible</twig:Card:Title>
            <twig:Card:Description>New turns keep part of the previous reply in view.</twig:Card:Description>
        </twig:Card:Header>
        <twig:Card:Content class="min-h-0 flex-1 overflow-hidden p-0">
            <twig:MessageScroller>
                <twig:MessageScroller:Viewport>
                    <twig:MessageScroller:Content class="p-(--card-spacing)">
                        <twig:MessageScroller:Item messageId="context-1" scrollAnchor>
                            <twig:Message align="end">
                                <twig:Message:Content>
                                    <twig:Bubble variant="muted">
                                        <twig:Bubble:Content>I'm building a chat for our app and the scroll behavior is driving me nuts. Every time the AI streams a reply, the whole thread jumps around.</twig:Bubble:Content>
                                    </twig:Bubble>
                                </twig:Message:Content>
                            </twig:Message>
                        </twig:MessageScroller:Item>
                        <twig:MessageScroller:Item messageId="context-2">
                            <twig:Message>
                                <twig:Message:Content>
                                    <twig:Bubble variant="ghost">
                                        <twig:Bubble:Content class="space-y-2">
                                            <p class="whitespace-pre-line">That's the classic streaming scroll problem. Wrap your message list in `MessageScroller` and turn on `autoScroll`: the viewport pins to the bottom as tokens arrive, so users always see the latest text land in place.</p>
                                            <p class="whitespace-pre-line">The important part: it only auto-scrolls while the reader is already at the bottom. The moment they scroll up to read something earlier, auto-scroll backs off and their position is preserved. You get smooth streaming without fighting the user's intent.</p>
                                        </twig:Bubble:Content>
                                    </twig:Bubble>
                                </twig:Message:Content>
                            </twig:Message>
                        </twig:MessageScroller:Item>
                        <twig:MessageScroller:Item messageId="context-3" scrollAnchor>
                            <twig:Message align="end">
                                <twig:Message:Content>
                                    <twig:Bubble variant="muted">
                                        <twig:Bubble:Content>Okay, but when someone sends a new message the view still feels jarring, like the whole conversation reloads from the top.</twig:Bubble:Content>
                                    </twig:Bubble>
                                </twig:Message:Content>
                            </twig:Message>
                        </twig:MessageScroller:Item>
                        <twig:MessageScroller:Item messageId="context-4">
                            <twig:Message>
                                <twig:Message:Content>
                                    <twig:Bubble variant="ghost">
                                        <twig:Bubble:Content class="space-y-2">
                                            <p class="whitespace-pre-line">`MessageScroller:Item` fixes that with turn anchoring. Set `scrollAnchor` on the turn that should settle near the top instead of blindly snapping to the document bottom.</p>
                                            <p class="whitespace-pre-line">It also leaves a small peek of the previous exchange visible above the anchor, so context isn't lost. The reply starts in view without that disorienting jump you get from a plain overflow container.</p>
                                        </twig:Bubble:Content>
                                    </twig:Bubble>
                                </twig:Message:Content>
                            </twig:Message>
                        </twig:MessageScroller:Item>
                        <twig:MessageScroller:Item messageId="context-5" scrollAnchor>
                            <twig:Message align="end">
                                <twig:Message:Content>
                                    <twig:Bubble variant="muted">
                                        <twig:Bubble:Content>And if they've scrolled up to re-read an older answer? I don't want to yank them back down.</twig:Bubble:Content>
                                    </twig:Bubble>
                                </twig:Message:Content>
                            </twig:Message>
                        </twig:MessageScroller:Item>
                        <twig:MessageScroller:Item messageId="context-6">
                            <twig:Message>
                                <twig:Message:Content>
                                    <twig:Bubble variant="ghost">
                                        <twig:Bubble:Content class="space-y-2">
                                            <p class="whitespace-pre-line">You won't. Auto-scroll only runs when the viewport is already pinned to the bottom, so scrolling up is a deliberate opt-out: their place in the thread stays put even as new tokens keep arriving below.</p>
                                            <p class="whitespace-pre-line">When there is content they haven't seen yet, `MessageScroller:Button` appears at the bottom of the viewport. One tap jumps them back to the newest message and re-engages auto-scroll. Same pattern as Slack or iMessage: quiet when you're caught up, helpful when you're not.</p>
                                            <p class="whitespace-pre-line">`MessageScroller:Content` sets `role="log"` and `aria-relevant="additions"` by default, so screen readers announce new messages as they stream in.</p>
                                            <p class="whitespace-pre-line">The scroll button is a real &lt;button&gt; with a visually hidden label, and it's removed from the tab order when you're already at the bottom: no ghost focus stops.</p>
                                        </twig:Bubble:Content>
                                    </twig:Bubble>
                                </twig:Message:Content>
                            </twig:Message>
                        </twig:MessageScroller:Item>
                    </twig:MessageScroller:Content>
                </twig:MessageScroller:Viewport>
                <twig:MessageScroller:Button />
            </twig:MessageScroller>
        </twig:Card:Content>
    </twig:Card>
</twig:MessageScroller:Provider>
```

### Following the Live Edge

When the reader is at the live edge, `autoScroll` keeps a streamed reply in view as it grows. Scrolling away from the live edge releases the view, whether by wheel, touch, keyboard or the scrollbar, and new chunks then arrive without moving the reader. `autoScroll` works with turn anchoring: a new turn settles near the top, and the view only starts following once the reply fills the viewport.

Pressing `MessageScroller:Button`, or calling `scrollToEnd`, follows the live edge again. The frame and the viewport have `data-autoscrolling` while that scroll runs. Set `aria-busy` on `MessageScroller:Content` while a reply streams, so screen readers wait for the complete row.

```twig {"preview":true,"collapseClass":true}
<twig:MessageScroller:Provider autoScroll>
    <twig:Card class="mx-auto h-140 w-full max-w-sm gap-0">
        <twig:Card:Header class="gap-1 border-b">
            <twig:Card:Title>Streaming Messages</twig:Card:Title>
            <twig:Card:Description>Auto-scroll follows the live edge of the conversation.</twig:Card:Description>
        </twig:Card:Header>
        <twig:Card:Content class="min-h-0 flex-1 overflow-hidden p-0">
            <twig:MessageScroller>
                <twig:MessageScroller:Viewport>
                    <twig:MessageScroller:Content class="p-(--card-spacing)" aria-busy="true">
                        <twig:MessageScroller:Item messageId="stream-1" scrollAnchor>
                            <twig:Message align="end">
                                <twig:Message:Content>
                                    <twig:Bubble variant="muted">
                                        <twig:Bubble:Content>I'm building a chat for our app and the scroll behavior is driving me nuts. Every time the AI streams a reply, the whole thread jumps around.</twig:Bubble:Content>
                                    </twig:Bubble>
                                </twig:Message:Content>
                            </twig:Message>
                        </twig:MessageScroller:Item>
                        <twig:MessageScroller:Item messageId="stream-2">
                            <twig:Message>
                                <twig:Message:Content>
                                    <twig:Bubble variant="ghost">
                                        <twig:Bubble:Content class="space-y-2">
                                            <p class="whitespace-pre-line">That's the classic streaming scroll problem. Wrap your message list in `MessageScroller` and turn on `autoScroll`: the viewport pins to the bottom as tokens arrive, so users always see the latest text land in place.</p>
                                            <p class="whitespace-pre-line">The important part: it only auto-scrolls while the reader is already at the bottom. The moment they scroll up to read something earlier, auto-scroll backs off and their position is preserved. You get smooth streaming without fighting the user's intent.</p>
                                        </twig:Bubble:Content>
                                    </twig:Bubble>
                                </twig:Message:Content>
                            </twig:Message>
                        </twig:MessageScroller:Item>
                        <twig:MessageScroller:Item messageId="stream-3" scrollAnchor>
                            <twig:Message align="end">
                                <twig:Message:Content>
                                    <twig:Bubble variant="muted">
                                        <twig:Bubble:Content>Okay, but when someone sends a new message the view still feels jarring, like the whole conversation reloads from the top.</twig:Bubble:Content>
                                    </twig:Bubble>
                                </twig:Message:Content>
                            </twig:Message>
                        </twig:MessageScroller:Item>
                        <twig:MessageScroller:Item messageId="stream-4">
                            <twig:Message>
                                <twig:Message:Content>
                                    <twig:Bubble variant="ghost">
                                        <twig:Bubble:Content>`MessageScroller:Item` fixes that with turn anchoring. Set `scrollAnchor` on the turn that should settle near the top instead of blindly snapping to the document bottom.</twig:Bubble:Content>
                                    </twig:Bubble>
                                </twig:Message:Content>
                            </twig:Message>
                        </twig:MessageScroller:Item>
                    </twig:MessageScroller:Content>
                </twig:MessageScroller:Viewport>
                <twig:MessageScroller:Button />
            </twig:MessageScroller>
        </twig:Card:Content>
    </twig:Card>
</twig:MessageScroller:Provider>
```

### Opening Saved Threads

Reopening a saved thread at the very end often drops the reader into the conversation without enough context. `defaultScrollPosition="last-anchor"` shows the last meaningful turn instead, like the user's latest message, with the reply below it. When there is no anchor, or the last turn already fits in the viewport, it opens at the end. Use `start` to resume at the beginning of a conversation, or `end`, the default, to land on the latest message.

A scroll container always opens at the top, so the viewport stays hidden until the controller applies an `end` or `last-anchor` position: you see the frame instead of a jump.

```twig {"preview":true,"collapseClass":true}
<twig:Card class="mx-auto h-140 w-full max-w-sm gap-0">
    <twig:Card:Header class="gap-1 border-b">
        <twig:Card:Title>Opening Position</twig:Card:Title>
        <twig:Card:Description>Where the transcript starts when you open the thread.</twig:Card:Description>
    </twig:Card:Header>
    <twig:Tabs defaultValue="last-anchor" class="min-h-0 flex-1 gap-0">
        {%- for position in ['start', 'end', 'last-anchor'] %}
            <twig:Tabs:Content :value="position" class="min-h-0 flex-1">
                <twig:MessageScroller:Provider :defaultScrollPosition="position">
                    <twig:MessageScroller>
                        <twig:MessageScroller:Viewport>
                            <twig:MessageScroller:Content class="p-(--card-spacing)">
                                <twig:MessageScroller:Item messageId="open-1" scrollAnchor>
                                    <twig:Message align="end">
                                        <twig:Message:Content>
                                            <twig:Bubble variant="muted">
                                                <twig:Bubble:Content>This is the first message the user sent in the conversation.</twig:Bubble:Content>
                                            </twig:Bubble>
                                        </twig:Message:Content>
                                    </twig:Message>
                                </twig:MessageScroller:Item>
                                <twig:MessageScroller:Item messageId="open-2">
                                    <twig:Message>
                                        <twig:Message:Content>
                                            <twig:Bubble variant="ghost">
                                                <twig:Bubble:Content>Workspace creation rose 8%, but first invite completion only rose 2%.</twig:Bubble:Content>
                                            </twig:Bubble>
                                        </twig:Message:Content>
                                    </twig:Message>
                                </twig:MessageScroller:Item>
                                <twig:MessageScroller:Item messageId="open-3" scrollAnchor>
                                    <twig:Message align="end">
                                        <twig:Message:Content>
                                            <twig:Bubble variant="muted">
                                                <twig:Bubble:Content>This is the last message the user sent in the conversation.</twig:Bubble:Content>
                                            </twig:Bubble>
                                        </twig:Message:Content>
                                    </twig:Message>
                                </twig:MessageScroller:Item>
                                <twig:MessageScroller:Item messageId="open-4">
                                    <twig:Message>
                                        <twig:Message:Content>
                                            <twig:Bubble variant="ghost">
                                                <twig:Bubble:Content class="space-y-2">
                                                    <p class="whitespace-pre-line">Start with the invite step. Teams are creating workspaces but waiting to add collaborators.</p>
                                                    <p class="whitespace-pre-line">Recommended follow-up:</p>
                                                    <p class="whitespace-pre-line">1. Compare invite drop-off by account size.
                                                2. Check whether users who skip invites still return within 24 hours.
                                                3. Review the empty-state copy on the first project screen.
                                                4. Segment activation by template, since template users may not need invites right away.</p>
                                                    <p class="whitespace-pre-line">If that pattern holds, the next experiment should make collaboration useful earlier instead of prompting for invites harder.</p>
                                                </twig:Bubble:Content>
                                            </twig:Bubble>
                                        </twig:Message:Content>
                                    </twig:Message>
                                </twig:MessageScroller:Item>
                            </twig:MessageScroller:Content>
                        </twig:MessageScroller:Viewport>
                        <twig:MessageScroller:Button />
                    </twig:MessageScroller>
                </twig:MessageScroller:Provider>
            </twig:Tabs:Content>
        {%- endfor %}
        <twig:Card:Footer class="border-t">
            <twig:Tabs:List class="w-full">
                <twig:Tabs:Trigger value="start">start</twig:Tabs:Trigger>
                <twig:Tabs:Trigger value="end">end</twig:Tabs:Trigger>
                <twig:Tabs:Trigger value="last-anchor">last-anchor</twig:Tabs:Trigger>
            </twig:Tabs:List>
        </twig:Card:Footer>
    </twig:Tabs>
</twig:Card>
```

### Loading Earlier Messages

Loading earlier messages should not move the conversation the reader is looking at. When rows are prepended to `MessageScroller:Content`, the viewport keeps the first visible row in place while the history loads above it. Turn it off with `preserveScrollOnPrepend="false"` on `MessageScroller:Viewport`. Give message rows a stable `messageId`: the scroller holds a specific row instead of whatever happens to sit at the viewport edge.

```twig {"preview":true,"collapseClass":true}
<twig:MessageScroller:Provider>
    <twig:Card class="mx-auto h-140 w-full max-w-sm gap-0">
        <twig:Card:Header class="gap-1 border-b">
            <twig:Card:Title>Load History</twig:Card:Title>
            <twig:Card:Description>Older messages load above without moving the reader.</twig:Card:Description>
        </twig:Card:Header>
        <twig:Card:Content class="min-h-0 flex-1 overflow-hidden p-0">
            <twig:MessageScroller>
                <twig:MessageScroller:Viewport>
                    <twig:MessageScroller:Content class="p-(--card-spacing)">
                        <twig:MessageScroller:Item>
                            <div class="flex justify-center">
                                <twig:Button variant="outline" size="sm">Load earlier messages</twig:Button>
                            </div>
                        </twig:MessageScroller:Item>
                        <twig:MessageScroller:Item messageId="history-1" scrollAnchor>
                            <twig:Message align="end">
                                <twig:Message:Content>
                                    <twig:Bubble variant="muted">
                                        <twig:Bubble:Content>Can you summarize where the launch checklist stands?</twig:Bubble:Content>
                                    </twig:Bubble>
                                </twig:Message:Content>
                            </twig:Message>
                        </twig:MessageScroller:Item>
                        <twig:MessageScroller:Item messageId="history-2">
                            <twig:Message>
                                <twig:Message:Content>
                                    <twig:Bubble variant="ghost">
                                        <twig:Bubble:Content>Design review is done, the pricing page is in copy review, and the migration guide still needs an owner.</twig:Bubble:Content>
                                    </twig:Bubble>
                                </twig:Message:Content>
                            </twig:Message>
                        </twig:MessageScroller:Item>
                        <twig:MessageScroller:Item messageId="history-3" scrollAnchor>
                            <twig:Message align="end">
                                <twig:Message:Content>
                                    <twig:Bubble variant="muted">
                                        <twig:Bubble:Content>Who should own the migration guide?</twig:Bubble:Content>
                                    </twig:Bubble>
                                </twig:Message:Content>
                            </twig:Message>
                        </twig:MessageScroller:Item>
                        <twig:MessageScroller:Item messageId="history-4">
                            <twig:Message>
                                <twig:Message:Content>
                                    <twig:Bubble variant="ghost">
                                        <twig:Bubble:Content>Jordan wrote the previous one and knows the edge cases. If Jordan is busy, Priya has reviewed every migration this quarter.</twig:Bubble:Content>
                                    </twig:Bubble>
                                </twig:Message:Content>
                            </twig:Message>
                        </twig:MessageScroller:Item>
                        <twig:MessageScroller:Item messageId="history-5" scrollAnchor>
                            <twig:Message align="end">
                                <twig:Message:Content>
                                    <twig:Bubble variant="muted">
                                        <twig:Bubble:Content>Let's ask Jordan first. What's left after that?</twig:Bubble:Content>
                                    </twig:Bubble>
                                </twig:Message:Content>
                            </twig:Message>
                        </twig:MessageScroller:Item>
                        <twig:MessageScroller:Item messageId="history-6">
                            <twig:Message>
                                <twig:Message:Content>
                                    <twig:Bubble variant="ghost">
                                        <twig:Bubble:Content>Only the announcement email. It depends on the final pricing copy, so it can start as soon as copy review signs off.</twig:Bubble:Content>
                                    </twig:Bubble>
                                </twig:Message:Content>
                            </twig:Message>
                        </twig:MessageScroller:Item>
                    </twig:MessageScroller:Content>
                </twig:MessageScroller:Viewport>
                <twig:MessageScroller:Button />
            </twig:MessageScroller>
        </twig:Card:Content>
    </twig:Card>
</twig:MessageScroller:Provider>
```

### Animating New Messages

A `MessageScroller:Item` can be animated directly. A common chat pattern animates the user's message when it is sent, and lets the reply stream into a regular row below it. Animate `transform` and `opacity` only: an entrance that animates the height, the margin or the padding fights the positioning of the scroller. Skip the animation when the reader prefers reduced motion.

```twig {"preview":true,"collapseClass":true}
<twig:MessageScroller:Provider>
    <twig:Card class="mx-auto h-140 w-full max-w-sm gap-0">
        <twig:Card:Header class="gap-1 border-b">
            <twig:Card:Title>Animation</twig:Card:Title>
            <twig:Card:Description>User messages rise from the live edge as they are sent.</twig:Card:Description>
        </twig:Card:Header>
        <twig:Card:Content class="min-h-0 flex-1 overflow-hidden p-0">
            <twig:MessageScroller>
                <twig:MessageScroller:Viewport>
                    <twig:MessageScroller:Content class="p-(--card-spacing)">
                        <twig:MessageScroller:Item messageId="animation-1" scrollAnchor class="animate-in duration-300 fade-in slide-in-from-bottom-4 motion-reduce:animate-none">
                            <twig:Message align="end">
                                <twig:Message:Content>
                                    <twig:Bubble variant="muted">
                                        <twig:Bubble:Content>I'm building a chat for our app and the scroll behavior is driving me nuts. Every time the AI streams a reply, the whole thread jumps around.</twig:Bubble:Content>
                                    </twig:Bubble>
                                </twig:Message:Content>
                            </twig:Message>
                        </twig:MessageScroller:Item>
                        <twig:MessageScroller:Item messageId="animation-2">
                            <twig:Message>
                                <twig:Message:Content>
                                    <twig:Bubble variant="ghost">
                                        <twig:Bubble:Content class="space-y-2">
                                            <p class="whitespace-pre-line">That's the classic streaming scroll problem. Wrap your message list in `MessageScroller` and turn on `autoScroll`: the viewport pins to the bottom as tokens arrive, so users always see the latest text land in place.</p>
                                            <p class="whitespace-pre-line">The important part: it only auto-scrolls while the reader is already at the bottom. The moment they scroll up to read something earlier, auto-scroll backs off and their position is preserved. You get smooth streaming without fighting the user's intent.</p>
                                        </twig:Bubble:Content>
                                    </twig:Bubble>
                                </twig:Message:Content>
                            </twig:Message>
                        </twig:MessageScroller:Item>
                        <twig:MessageScroller:Item messageId="animation-3" scrollAnchor class="animate-in duration-300 fade-in slide-in-from-bottom-4 motion-reduce:animate-none">
                            <twig:Message align="end">
                                <twig:Message:Content>
                                    <twig:Bubble variant="muted">
                                        <twig:Bubble:Content>Okay, but when someone sends a new message the view still feels jarring, like the whole conversation reloads from the top.</twig:Bubble:Content>
                                    </twig:Bubble>
                                </twig:Message:Content>
                            </twig:Message>
                        </twig:MessageScroller:Item>
                        <twig:MessageScroller:Item messageId="animation-4">
                            <twig:Message>
                                <twig:Message:Content>
                                    <twig:Bubble variant="ghost">
                                        <twig:Bubble:Content class="space-y-2">
                                            <p class="whitespace-pre-line">`MessageScroller:Item` fixes that with turn anchoring. Set `scrollAnchor` on the turn that should settle near the top instead of blindly snapping to the document bottom.</p>
                                            <p class="whitespace-pre-line">It also leaves a small peek of the previous exchange visible above the anchor, so context isn't lost. The reply starts in view without that disorienting jump you get from a plain overflow container.</p>
                                        </twig:Bubble:Content>
                                    </twig:Bubble>
                                </twig:Message:Content>
                            </twig:Message>
                        </twig:MessageScroller:Item>
                    </twig:MessageScroller:Content>
                </twig:MessageScroller:Viewport>
                <twig:MessageScroller:Button />
            </twig:MessageScroller>
        </twig:Card:Content>
    </twig:Card>
</twig:MessageScroller:Provider>
```

### Jumping to Messages

Search results, permalinks, outlines and toolbar buttons often drive the transcript from outside the list. The `message-scroller` controller has three actions for them: `scrollToMessage`, `scrollToStart` and `scrollToEnd`. They work from any element inside `MessageScroller:Provider`, including controls outside the `MessageScroller` frame.

`scrollToMessage` takes the `messageId` of the row in its `id` param, and optional `align` (`start`, `center`, `end` or `nearest`) and `behavior` (`auto` or `smooth`) params. When no row is rendered yet, the jump waits for the transcript to arrive.

```twig {"preview":true,"collapseClass":true}
<twig:MessageScroller:Provider>
    <twig:Card class="mx-auto h-140 w-full max-w-sm gap-0">
        <twig:Card:Header class="gap-1 border-b">
            <twig:Card:Title>Commands</twig:Card:Title>
            <twig:Card:Description>Drive the transcript from outside.</twig:Card:Description>
            <twig:Card:Action>
                <twig:DropdownMenu id="message-scroller-jump-menu" side="bottom" align="end">
                    <twig:DropdownMenu:Trigger>
                        <twig:Button variant="secondary" {{ ...dropdown_menu_trigger_attrs }}>Jump to...</twig:Button>
                    </twig:DropdownMenu:Trigger>
                    <twig:DropdownMenu:Content class="w-64">
                        <twig:DropdownMenu:Group>
                            <twig:DropdownMenu:Label>Conversations</twig:DropdownMenu:Label>
                            <twig:DropdownMenu:Item
                                data-action="message-scroller#scrollToMessage"
                                data-message-scroller-id-param="command-activation"
                                data-message-scroller-behavior-param="smooth"
                            >
                                <span class="line-clamp-1 min-w-0">We're seeing activation dip after works...</span>
                            </twig:DropdownMenu:Item>
                            <twig:DropdownMenu:Item
                                data-action="message-scroller#scrollToMessage"
                                data-message-scroller-id-param="command-compare"
                                data-message-scroller-behavior-param="smooth"
                            >
                                <span class="line-clamp-1 min-w-0">What should I compare before we change ...</span>
                            </twig:DropdownMenu:Item>
                            <twig:DropdownMenu:Item
                                data-action="message-scroller#scrollToMessage"
                                data-message-scroller-id-param="command-experiment"
                                data-message-scroller-behavior-param="smooth"
                            >
                                <span class="line-clamp-1 min-w-0">Can you turn that into an experiment?</span>
                            </twig:DropdownMenu:Item>
                            <twig:DropdownMenu:Item
                                data-action="message-scroller#scrollToMessage"
                                data-message-scroller-id-param="command-risk"
                                data-message-scroller-behavior-param="smooth"
                            >
                                <span class="line-clamp-1 min-w-0">What's the risk if we delay the invite ...</span>
                            </twig:DropdownMenu:Item>
                        </twig:DropdownMenu:Group>
                    </twig:DropdownMenu:Content>
                </twig:DropdownMenu>
            </twig:Card:Action>
        </twig:Card:Header>
        <twig:Card:Content class="min-h-0 flex-1 overflow-hidden p-0">
            <twig:MessageScroller>
                <twig:MessageScroller:Viewport>
                    <twig:MessageScroller:Content class="p-(--card-spacing)">
                        <twig:MessageScroller:Item messageId="command-activation" scrollAnchor>
                            <twig:Message align="end">
                                <twig:Message:Content>
                                    <twig:Bubble variant="muted">
                                        <twig:Bubble:Content>We're seeing activation dip after workspace creation. Can you help me find the likely step?</twig:Bubble:Content>
                                    </twig:Bubble>
                                </twig:Message:Content>
                            </twig:Message>
                        </twig:MessageScroller:Item>
                        <twig:MessageScroller:Item messageId="command-reply-1">
                            <twig:Message>
                                <twig:Message:Content>
                                    <twig:Bubble variant="ghost">
                                        <twig:Bubble:Content class="space-y-2">
                                            <p class="whitespace-pre-line">The sharpest drop is between creating the workspace and inviting the first teammate.</p>
                                            <p class="whitespace-pre-line">Workspace creation is still healthy, but the invite step is where users pause. That suggests the product is asking for collaboration before the user has enough confidence in the workspace.</p>
                                        </twig:Bubble:Content>
                                    </twig:Bubble>
                                </twig:Message:Content>
                            </twig:Message>
                        </twig:MessageScroller:Item>
                        <twig:MessageScroller:Item messageId="command-compare" scrollAnchor>
                            <twig:Message align="end">
                                <twig:Message:Content>
                                    <twig:Bubble variant="muted">
                                        <twig:Bubble:Content>What should I compare before we change the onboarding flow?</twig:Bubble:Content>
                                    </twig:Bubble>
                                </twig:Message:Content>
                            </twig:Message>
                        </twig:MessageScroller:Item>
                        <twig:MessageScroller:Item messageId="command-reply-3">
                            <twig:Message>
                                <twig:Message:Content>
                                    <twig:Bubble variant="ghost">
                                        <twig:Bubble:Content class="space-y-2">
                                            <p class="whitespace-pre-line">Compare three cohorts:</p>
                                            <p class="whitespace-pre-line">1. Users who choose a template before inviting teammates.
                                        2. Users who start from a blank workspace.
                                        3. Users who skip invites and return within 24 hours.</p>
                                            <p class="whitespace-pre-line">If template users invite faster, the fix is probably better first-run guidance rather than a louder invite prompt.</p>
                                        </twig:Bubble:Content>
                                    </twig:Bubble>
                                </twig:Message:Content>
                            </twig:Message>
                        </twig:MessageScroller:Item>
                        <twig:MessageScroller:Item messageId="command-experiment" scrollAnchor>
                            <twig:Message align="end">
                                <twig:Message:Content>
                                    <twig:Bubble variant="muted">
                                        <twig:Bubble:Content>Can you turn that into an experiment?</twig:Bubble:Content>
                                    </twig:Bubble>
                                </twig:Message:Content>
                            </twig:Message>
                        </twig:MessageScroller:Item>
                        <twig:MessageScroller:Item messageId="command-reply-5">
                            <twig:Message>
                                <twig:Message:Content>
                                    <twig:Bubble variant="ghost">
                                        <twig:Bubble:Content class="space-y-2">
                                            <p class="whitespace-pre-line">Yes. Create a variant that shows a short checklist after workspace creation:</p>
                                            <p class="whitespace-pre-line">- Pick a template.
                                        - Add one project detail.
                                        - Invite a teammate when the workspace has context.</p>
                                            <p class="whitespace-pre-line">Measure first invite completion, 24-hour return rate, and whether teams create a second project.</p>
                                        </twig:Bubble:Content>
                                    </twig:Bubble>
                                </twig:Message:Content>
                            </twig:Message>
                        </twig:MessageScroller:Item>
                        <twig:MessageScroller:Item messageId="command-risk" scrollAnchor>
                            <twig:Message align="end">
                                <twig:Message:Content>
                                    <twig:Bubble variant="muted">
                                        <twig:Bubble:Content>What's the risk if we delay the invite prompt?</twig:Bubble:Content>
                                    </twig:Bubble>
                                </twig:Message:Content>
                            </twig:Message>
                        </twig:MessageScroller:Item>
                        <twig:MessageScroller:Item messageId="command-reply-7">
                            <twig:Message>
                                <twig:Message:Content>
                                    <twig:Bubble variant="ghost">
                                        <twig:Bubble:Content class="space-y-2">
                                            <p class="whitespace-pre-line">The main risk is reducing team creation for accounts that already know who they want to invite.</p>
                                            <p class="whitespace-pre-line">To protect that path, keep the invite action visible in the header and only change the primary empty-state guidance. That gives confident teams a direct route without forcing uncertain users through the invite step too early.</p>
                                        </twig:Bubble:Content>
                                    </twig:Bubble>
                                </twig:Message:Content>
                            </twig:Message>
                        </twig:MessageScroller:Item>
                    </twig:MessageScroller:Content>
                </twig:MessageScroller:Viewport>
                <twig:MessageScroller:Button />
            </twig:MessageScroller>
        </twig:Card:Content>
    </twig:Card>
</twig:MessageScroller:Provider>
```

### Tracking the Reader's Position

An outline or a jump menu often highlights the turn the reader is in. Mark its controls as `anchorLink` targets of the controller: the one whose `id` param is the current turn gets `aria-current="location"`, the others `aria-current="false"`. The current turn is the last anchor to reach the line where anchored turns settle, and it stays current after its top scrolls out of view. The tracking only runs while the provider holds an `anchorLink` target.

```twig {"preview":true,"collapseClass":true}
<twig:MessageScroller:Provider scrollMargin="12">
    <div class="relative mx-auto w-full max-w-sm">
        <twig:Card class="mx-auto h-140 w-full max-w-sm gap-0">
            <twig:Card:Header class="gap-1 border-b">
                <twig:Card:Title>Transcript Outline</twig:Card:Title>
                <twig:Card:Description>Track the current anchored turn.</twig:Card:Description>
            </twig:Card:Header>
            <twig:Card:Content class="min-h-0 flex-1 overflow-hidden p-0">
                <twig:MessageScroller>
                    <twig:MessageScroller:Viewport>
                        <twig:MessageScroller:Content class="p-(--card-spacing)">
                            <twig:MessageScroller:Item messageId="command-activation" scrollAnchor>
                                <twig:Message align="end">
                                    <twig:Message:Content>
                                        <twig:Bubble variant="muted">
                                            <twig:Bubble:Content>We're seeing activation dip after workspace creation. Can you help me find the likely step?</twig:Bubble:Content>
                                        </twig:Bubble>
                                    </twig:Message:Content>
                                </twig:Message>
                            </twig:MessageScroller:Item>
                            <twig:MessageScroller:Item messageId="command-reply-1">
                                <twig:Message>
                                    <twig:Message:Content>
                                        <twig:Bubble variant="ghost">
                                            <twig:Bubble:Content class="space-y-2">
                                                <p class="whitespace-pre-line">The sharpest drop is between creating the workspace and inviting the first teammate.</p>
                                                <p class="whitespace-pre-line">Workspace creation is still healthy, but the invite step is where users pause. That suggests the product is asking for collaboration before the user has enough confidence in the workspace.</p>
                                            </twig:Bubble:Content>
                                        </twig:Bubble>
                                    </twig:Message:Content>
                                </twig:Message>
                            </twig:MessageScroller:Item>
                            <twig:MessageScroller:Item messageId="command-compare" scrollAnchor>
                                <twig:Message align="end">
                                    <twig:Message:Content>
                                        <twig:Bubble variant="muted">
                                            <twig:Bubble:Content>What should I compare before we change the onboarding flow?</twig:Bubble:Content>
                                        </twig:Bubble>
                                    </twig:Message:Content>
                                </twig:Message>
                            </twig:MessageScroller:Item>
                            <twig:MessageScroller:Item messageId="command-reply-3">
                                <twig:Message>
                                    <twig:Message:Content>
                                        <twig:Bubble variant="ghost">
                                            <twig:Bubble:Content class="space-y-2">
                                                <p class="whitespace-pre-line">Compare three cohorts:</p>
                                                <p class="whitespace-pre-line">1. Users who choose a template before inviting teammates.
                                            2. Users who start from a blank workspace.
                                            3. Users who skip invites and return within 24 hours.</p>
                                                <p class="whitespace-pre-line">If template users invite faster, the fix is probably better first-run guidance rather than a louder invite prompt.</p>
                                            </twig:Bubble:Content>
                                        </twig:Bubble>
                                    </twig:Message:Content>
                                </twig:Message>
                            </twig:MessageScroller:Item>
                            <twig:MessageScroller:Item messageId="command-experiment" scrollAnchor>
                                <twig:Message align="end">
                                    <twig:Message:Content>
                                        <twig:Bubble variant="muted">
                                            <twig:Bubble:Content>Can you turn that into an experiment?</twig:Bubble:Content>
                                        </twig:Bubble>
                                    </twig:Message:Content>
                                </twig:Message>
                            </twig:MessageScroller:Item>
                            <twig:MessageScroller:Item messageId="command-reply-5">
                                <twig:Message>
                                    <twig:Message:Content>
                                        <twig:Bubble variant="ghost">
                                            <twig:Bubble:Content class="space-y-2">
                                                <p class="whitespace-pre-line">Yes. Create a variant that shows a short checklist after workspace creation:</p>
                                                <p class="whitespace-pre-line">- Pick a template.
                                            - Add one project detail.
                                            - Invite a teammate when the workspace has context.</p>
                                                <p class="whitespace-pre-line">Measure first invite completion, 24-hour return rate, and whether teams create a second project.</p>
                                            </twig:Bubble:Content>
                                        </twig:Bubble>
                                    </twig:Message:Content>
                                </twig:Message>
                            </twig:MessageScroller:Item>
                            <twig:MessageScroller:Item messageId="command-risk" scrollAnchor>
                                <twig:Message align="end">
                                    <twig:Message:Content>
                                        <twig:Bubble variant="muted">
                                            <twig:Bubble:Content>What's the risk if we delay the invite prompt?</twig:Bubble:Content>
                                        </twig:Bubble>
                                    </twig:Message:Content>
                                </twig:Message>
                            </twig:MessageScroller:Item>
                            <twig:MessageScroller:Item messageId="command-reply-7">
                                <twig:Message>
                                    <twig:Message:Content>
                                        <twig:Bubble variant="ghost">
                                            <twig:Bubble:Content class="space-y-2">
                                                <p class="whitespace-pre-line">The main risk is reducing team creation for accounts that already know who they want to invite.</p>
                                                <p class="whitespace-pre-line">To protect that path, keep the invite action visible in the header and only change the primary empty-state guidance. That gives confident teams a direct route without forcing uncertain users through the invite step too early.</p>
                                            </twig:Bubble:Content>
                                        </twig:Bubble>
                                    </twig:Message:Content>
                                </twig:Message>
                            </twig:MessageScroller:Item>
                        </twig:MessageScroller:Content>
                    </twig:MessageScroller:Viewport>
                    <twig:MessageScroller:Button />
                </twig:MessageScroller>
            </twig:Card:Content>
        </twig:Card>
        <div class="absolute top-1/2 -end-12 -translate-y-1/2">
            <twig:HoverCard openDelay="10" closeDelay="100">
                <twig:HoverCard:Trigger>
                    <twig:Button variant="ghost" size="icon" aria-label="Open transcript outline" {{ ...hover_card_trigger_attrs }}>
                        <twig:ux:icon name="lucide:list" />
                    </twig:Button>
                </twig:HoverCard:Trigger>
                <twig:HoverCard:Content side="left" class="flex w-64 flex-col gap-1 rounded-2xl p-1">
                    <button
                        type="button"
                        class="flex min-h-7 items-center rounded-xl px-2 py-1.5 text-start text-sm outline-none transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:bg-accent focus-visible:text-accent-foreground aria-[current=location]:bg-accent aria-[current=location]:text-accent-foreground"
                        aria-current="false"
                        data-message-scroller-target="anchorLink"
                        data-action="message-scroller#scrollToMessage"
                        data-message-scroller-id-param="command-activation"
                        data-message-scroller-behavior-param="smooth"
                    >
                        <span class="line-clamp-1 min-w-0">We're seeing activation dip after works...</span>
                    </button>
                    <button
                        type="button"
                        class="flex min-h-7 items-center rounded-xl px-2 py-1.5 text-start text-sm outline-none transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:bg-accent focus-visible:text-accent-foreground aria-[current=location]:bg-accent aria-[current=location]:text-accent-foreground"
                        aria-current="false"
                        data-message-scroller-target="anchorLink"
                        data-action="message-scroller#scrollToMessage"
                        data-message-scroller-id-param="command-compare"
                        data-message-scroller-behavior-param="smooth"
                    >
                        <span class="line-clamp-1 min-w-0">What should I compare before we change ...</span>
                    </button>
                    <button
                        type="button"
                        class="flex min-h-7 items-center rounded-xl px-2 py-1.5 text-start text-sm outline-none transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:bg-accent focus-visible:text-accent-foreground aria-[current=location]:bg-accent aria-[current=location]:text-accent-foreground"
                        aria-current="false"
                        data-message-scroller-target="anchorLink"
                        data-action="message-scroller#scrollToMessage"
                        data-message-scroller-id-param="command-experiment"
                        data-message-scroller-behavior-param="smooth"
                    >
                        <span class="line-clamp-1 min-w-0">Can you turn that into an experiment?</span>
                    </button>
                    <button
                        type="button"
                        class="flex min-h-7 items-center rounded-xl px-2 py-1.5 text-start text-sm outline-none transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:bg-accent focus-visible:text-accent-foreground aria-[current=location]:bg-accent aria-[current=location]:text-accent-foreground"
                        aria-current="false"
                        data-message-scroller-target="anchorLink"
                        data-action="message-scroller#scrollToMessage"
                        data-message-scroller-id-param="command-risk"
                        data-message-scroller-behavior-param="smooth"
                    >
                        <span class="line-clamp-1 min-w-0">What's the risk if we delay the invite ...</span>
                    </button>
                </twig:HoverCard:Content>
            </twig:HoverCard>
        </div>
    </div>
</twig:MessageScroller:Provider>
```

### Reading Scroll State

The provider, the frame and the viewport have a `data-scrollable` attribute listing the edges the viewport can still scroll toward: `start`, `end`, or both. It is absent when everything fits. Style anything inside the provider from it, with the `group/message-scroller-provider` group, or the frame with `group/message-scroller`.

```twig {"preview":true,"collapseClass":true}
<twig:Card class="mx-auto h-140 w-full max-w-sm gap-0">
    <twig:Card:Header class="gap-1 border-b">
        <twig:Card:Title>Scroll Status</twig:Card:Title>
        <twig:Card:Description>Where the reader can scroll to from the current position.</twig:Card:Description>
    </twig:Card:Header>
    <twig:MessageScroller:Provider defaultScrollPosition="start">
        <twig:Card:Content class="min-h-0 flex-1 overflow-hidden p-0">
            <twig:MessageScroller>
                <twig:MessageScroller:Viewport>
                    <twig:MessageScroller:Content class="gap-4 p-(--card-spacing)">
                        {%- for checkpoint in [1, 3, 5, 7, 9, 11] %}
                            <twig:MessageScroller:Item :messageId="'scrollable-' ~ checkpoint" scrollAnchor>
                                <twig:Message align="end">
                                    <twig:Message:Content>
                                        <twig:Bubble variant="muted">
                                            <twig:Bubble:Content>Review scroll checkpoint {{ checkpoint }}.</twig:Bubble:Content>
                                        </twig:Bubble>
                                    </twig:Message:Content>
                                </twig:Message>
                            </twig:MessageScroller:Item>
                            <twig:MessageScroller:Item :messageId="'scrollable-' ~ (checkpoint + 1)">
                                <twig:Message>
                                    <twig:Message:Content>
                                        <twig:Bubble variant="ghost">
                                            <twig:Bubble:Content class="space-y-2">
                                                <p class="whitespace-pre-line">Checkpoint {{ checkpoint + 1 }} is synced. The scroll state updates as the viewport moves.</p>
                                                <p class="whitespace-pre-line">When the reader is at the first message, the footer should only point them down. Once they move into the middle of the transcript, it should explain that both directions are available.</p>
                                                <p class="whitespace-pre-line">At the latest message, the footer should switch again and only point them back up.</p>
                                            </twig:Bubble:Content>
                                        </twig:Bubble>
                                    </twig:Message:Content>
                                </twig:Message>
                            </twig:MessageScroller:Item>
                        {%- endfor %}
                    </twig:MessageScroller:Content>
                </twig:MessageScroller:Viewport>
                <twig:MessageScroller:Button />
            </twig:MessageScroller>
        </twig:Card:Content>
        <twig:Card:Footer class="justify-center border-t text-center text-sm text-muted-foreground">
            <p class="hidden group-data-[scrollable='start_end']/message-scroller-provider:block">You can scroll both ways.</p>
            <p class="hidden group-data-[scrollable=end]/message-scroller-provider:block">You are at the top. You can only scroll down.</p>
            <p class="hidden group-data-[scrollable=start]/message-scroller-provider:block">You are at the bottom. You can only scroll up.</p>
            <p class="group-data-scrollable/message-scroller-provider:hidden">All messages fit in the viewport.</p>
        </twig:Card:Footer>
    </twig:MessageScroller:Provider>
</twig:Card>
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

```twig {"preview":true,"collapseClass":true}
<div class="flex w-full flex-col items-center gap-8">
    {# Arabic #}
    <div dir="rtl" class="w-full">
        <twig:MessageScroller:Provider>
            <twig:Card class="mx-auto h-96 w-full max-w-sm gap-0">
                <twig:Card:Header class="gap-1 border-b">
                    <twig:Card:Title>محادثة جديدة</twig:Card:Title>
                    <twig:Card:Description>كيف يمكنني مساعدتك اليوم؟</twig:Card:Description>
                </twig:Card:Header>
                <twig:Card:Content class="min-h-0 flex-1 overflow-hidden p-0">
                    <twig:MessageScroller>
                        <twig:MessageScroller:Viewport>
                            <twig:MessageScroller:Content class="p-(--card-spacing)">
                                <twig:MessageScroller:Item messageId="rtl-ar-1" scrollAnchor>
                                    <twig:Message align="end">
                                        <twig:Message:Content>
                                            <twig:Bubble variant="muted">
                                                <twig:Bubble:Content>لماذا يقفز المحادثة كلما وصل رد جديد؟</twig:Bubble:Content>
                                            </twig:Bubble>
                                        </twig:Message:Content>
                                    </twig:Message>
                                </twig:MessageScroller:Item>
                                <twig:MessageScroller:Item messageId="rtl-ar-2">
                                    <twig:Message>
                                        <twig:Message:Content>
                                            <twig:Bubble variant="ghost">
                                                <twig:Bubble:Content>لأن الحاوية تنتقل إلى الأسفل مع كل جزء جديد. يثبّت MessageScroller الدور الجديد قرب الأعلى، ولا يتابع الرد إلا إذا كان القارئ في النهاية.</twig:Bubble:Content>
                                            </twig:Bubble>
                                        </twig:Message:Content>
                                    </twig:Message>
                                </twig:MessageScroller:Item>
                                <twig:MessageScroller:Item messageId="rtl-ar-3" scrollAnchor>
                                    <twig:Message align="end">
                                        <twig:Message:Content>
                                            <twig:Bubble variant="muted">
                                                <twig:Bubble:Content>وإذا صعدتُ لقراءة رسالة قديمة؟</twig:Bubble:Content>
                                            </twig:Bubble>
                                        </twig:Message:Content>
                                    </twig:Message>
                                </twig:MessageScroller:Item>
                                <twig:MessageScroller:Item messageId="rtl-ar-4">
                                    <twig:Message>
                                        <twig:Message:Content>
                                            <twig:Bubble variant="ghost">
                                                <twig:Bubble:Content>يبقى مكانك كما هو. يظهر زر في أسفل العرض للعودة إلى آخر رسالة عندما تريد.</twig:Bubble:Content>
                                            </twig:Bubble>
                                        </twig:Message:Content>
                                    </twig:Message>
                                </twig:MessageScroller:Item>
                            </twig:MessageScroller:Content>
                        </twig:MessageScroller:Viewport>
                        <twig:MessageScroller:Button />
                    </twig:MessageScroller>
                </twig:Card:Content>
            </twig:Card>
        </twig:MessageScroller:Provider>
    </div>

    {# Hebrew #}
    <div dir="rtl" class="w-full">
        <twig:MessageScroller:Provider>
            <twig:Card class="mx-auto h-96 w-full max-w-sm gap-0">
                <twig:Card:Header class="gap-1 border-b">
                    <twig:Card:Title>שיחה חדשה</twig:Card:Title>
                    <twig:Card:Description>איך אפשר לעזור היום?</twig:Card:Description>
                </twig:Card:Header>
                <twig:Card:Content class="min-h-0 flex-1 overflow-hidden p-0">
                    <twig:MessageScroller>
                        <twig:MessageScroller:Viewport>
                            <twig:MessageScroller:Content class="p-(--card-spacing)">
                                <twig:MessageScroller:Item messageId="rtl-he-1" scrollAnchor>
                                    <twig:Message align="end">
                                        <twig:Message:Content>
                                            <twig:Bubble variant="muted">
                                                <twig:Bubble:Content>למה השיחה קופצת בכל פעם שמגיעה תשובה חדשה?</twig:Bubble:Content>
                                            </twig:Bubble>
                                        </twig:Message:Content>
                                    </twig:Message>
                                </twig:MessageScroller:Item>
                                <twig:MessageScroller:Item messageId="rtl-he-2">
                                    <twig:Message>
                                        <twig:Message:Content>
                                            <twig:Bubble variant="ghost">
                                                <twig:Bubble:Content>כי המכל גולל למטה עם כל חלק חדש. MessageScroller מצמיד את התור החדש קרוב לראש, ועוקב אחרי התשובה רק כשהקורא נמצא בסוף.</twig:Bubble:Content>
                                            </twig:Bubble>
                                        </twig:Message:Content>
                                    </twig:Message>
                                </twig:MessageScroller:Item>
                                <twig:MessageScroller:Item messageId="rtl-he-3" scrollAnchor>
                                    <twig:Message align="end">
                                        <twig:Message:Content>
                                            <twig:Bubble variant="muted">
                                                <twig:Bubble:Content>ואם גללתי למעלה כדי לקרוא הודעה ישנה?</twig:Bubble:Content>
                                            </twig:Bubble>
                                        </twig:Message:Content>
                                    </twig:Message>
                                </twig:MessageScroller:Item>
                                <twig:MessageScroller:Item messageId="rtl-he-4">
                                    <twig:Message>
                                        <twig:Message:Content>
                                            <twig:Bubble variant="ghost">
                                                <twig:Bubble:Content>המקום שלך נשמר. כפתור מופיע בתחתית התצוגה כדי לחזור להודעה האחרונה מתי שתרצה.</twig:Bubble:Content>
                                            </twig:Bubble>
                                        </twig:Message:Content>
                                    </twig:Message>
                                </twig:MessageScroller:Item>
                            </twig:MessageScroller:Content>
                        </twig:MessageScroller:Viewport>
                        <twig:MessageScroller:Button />
                    </twig:MessageScroller>
                </twig:Card:Content>
            </twig:Card>
        </twig:MessageScroller:Provider>
    </div>
</div>
```

## Accessibility

- `MessageScroller:Viewport` is a labelled region the keyboard can reach: it renders `role="region"`, `aria-label="Messages"` and `tabindex="0"`, so keyboard users can focus the transcript and scroll it. Pass another `aria-label` to name it after the conversation.
- `MessageScroller:Content` is a live region, with `role="log"` and `aria-relevant="additions"`: new rows are announced, but streamed text is not read out chunk by chunk. Set `aria-busy="true"` while a reply streams, so the announcement waits for the complete row.
- `MessageScroller:Button` renders a real `<button>` with a visually hidden label. While there is nothing to scroll toward, it is `inert`, has `tabindex="-1"` and `data-active="false"`, so it adds no focus stop.
- Scrolling with the wheel, a touch gesture, the scrollbar or the keys of the viewport (arrows, Page Up, Page Down, Home, End and Space) stops the following, so the transcript never moves against the reader.

## API Reference

::: api-reference
