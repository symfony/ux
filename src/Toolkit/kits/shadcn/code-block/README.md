# Code Block

Displays code, terminal output and diffs with an optional header, line numbers and a copy button.

```twig {"preview":true}
{% set lines = [
    'import { streamText } from "ai"',
    '',
    'export async function POST(request: Request) {',
    '  const { messages } = await request.json()',
    '',
    '  const result = streamText({',
    '    model: "anthropic/claude-sonnet-4.5",',
    '    system: "You are a helpful assistant. Answer in one short paragraph.",',
    '    messages,',
    '  })',
    '',
    '  return result.toUIMessageStreamResponse()',
    '}',
] %}
<div class="w-full max-w-lg">
    <twig:CodeBlock>
        <twig:CodeBlock:Header>
            <twig:CodeBlock:Title>
                <twig:ux:icon name="lucide:file-code" />
                app/api/chat/route.ts
            </twig:CodeBlock:Title>
            <twig:CodeBlock:Actions>
                <twig:Button variant="ghost" size="icon-xs" aria-label="Wrap lines" aria-pressed="false" class="aria-pressed:text-foreground" data-action="code-block#toggleWrap">
                    <twig:ux:icon name="lucide:wrap-text" />
                </twig:Button>
                <twig:Button variant="ghost" size="icon-xs" aria-label="Download" data-action="code-block#download" data-code-block-text="{{ lines|join('\n') }}" data-code-block-filename-param="route.ts">
                    <twig:ux:icon name="lucide:download" />
                </twig:Button>
                <twig:CodeBlock:CopyButton :value="lines|join('\n')" />
            </twig:CodeBlock:Actions>
        </twig:CodeBlock:Header>
        <twig:CodeBlock:Content lineNumbers>
            {%- for line in lines -%}
                <twig:CodeBlock:Line variant="{{ loop.index0 >= 5 and loop.index0 <= 9 ? 'highlighted' : 'default' }}">{{ line }}</twig:CodeBlock:Line>
            {%- endfor -%}
        </twig:CodeBlock:Content>
    </twig:CodeBlock>
</div>
```

## Installation

::: installation

## Usage

```twig
<twig:CodeBlock>
    <twig:CodeBlock:Header>
        <twig:CodeBlock:Title>Terminal</twig:CodeBlock:Title>
        <twig:CodeBlock:Actions>
            <twig:CodeBlock:CopyButton :value="output" />
        </twig:CodeBlock:Actions>
    </twig:CodeBlock:Header>
    <twig:CodeBlock:Content :code="output" />
</twig:CodeBlock>
```

`CodeBlock:Content` takes a `code` string, or a list of `CodeBlock:Line`. Use lines when you need line variants or highlighted tokens.

## Syntax Highlighting

This recipe does not highlight code. To color it, highlight the code in your application, then pass each highlighted line to a `CodeBlock:Line`.

```twig
<twig:CodeBlock>
    <twig:CodeBlock:Content>
        {% for line in highlighted_lines %}
            <twig:CodeBlock:Line>{{ line|raw }}</twig:CodeBlock:Line>
        {% endfor %}
    </twig:CodeBlock:Content>
</twig:CodeBlock>
```

Here, `highlighted_lines` is a list of HTML strings, one per line, built by your application. Only print a line with `raw` when it comes from a highlighter that escapes the code.

- [Phiki](https://phiki.dev): a PHP port of Shiki, the highlighter Shadcn uses. It uses the same TextMate grammars and VS Code themes. It returns tokens line by line, and accepts a light and a dark theme.
- [tempest/highlight](https://github.com/tempestphp/highlight): a fast PHP highlighter. It returns the whole block as one HTML string. A token that spans several lines (a block comment, a multi-line string) has to be split by hand.
- [Shiki](https://shiki.style) in a Stimulus controller: the same engine as Shadcn, running in the browser. Its grammars and themes add a lot of JavaScript to load.

## Examples

### Basic

Pass a string to the `code` prop of `CodeBlock:Content`. It renders as one text node.

```twig {"preview":true}
{% set output = [
    '$ pnpm build',
    '> next build',
    '',
    ' ✓ Compiled successfully in 4.2s',
    ' ✓ Generating static pages (12/12)',
    '',
    'Route (app)                Size  First Load JS',
    '┌ ○ /                    1.9 kB         112 kB',
    '├ ○ /_not-found           993 B         103 kB',
    '└ ƒ /api/chat             141 B         102 kB',
]|join('\n') %}
<div class="w-full max-w-md">
    <twig:CodeBlock>
        <twig:CodeBlock:Content :code="output" />
    </twig:CodeBlock>
</div>
```

### Header

`CodeBlock:Header` holds a `CodeBlock:Title` and `CodeBlock:Actions`. `CodeBlock:CopyButton` copies its `value`. A long title truncates.

```twig {"preview":true}
{% set command = 'pnpm add ai @ai-sdk/react zod' %}
{% set output = [
    '$ ' ~ command,
    'Packages: +14',
    'Progress: resolved 412, reused 398, downloaded 14, added 14, done',
    'Done in 2.8s using pnpm v10.18.0',
]|join('\n') %}
<div class="w-full max-w-md">
    <twig:CodeBlock>
        <twig:CodeBlock:Header>
            <twig:CodeBlock:Title>
                <twig:ux:icon name="lucide:terminal" />
                Terminal
            </twig:CodeBlock:Title>
            <twig:CodeBlock:Actions>
                <twig:CodeBlock:CopyButton :value="command" />
            </twig:CodeBlock:Actions>
        </twig:CodeBlock:Header>
        <twig:CodeBlock:Content :code="output" />
    </twig:CodeBlock>
</div>
```

### Actions

`CodeBlock:Actions` holds any number of buttons. The `code-block` controller provides a `toggleWrap` action for a wrap toggle, and a `download` action that saves the `data-code-block-text` of the button.

```twig {"preview":true}
{% set output = [
    '$ pnpm lint',
    'apps/web/components/message-list.tsx',
    '  18:22  error  \'messages\' is possibly \'undefined\'. Add a guard before calling map  @typescript-eslint/no-unsafe-call',
    '  42:7   warning  React Hook React.useEffect has a missing dependency: \'scrollToBottom\'  react-hooks/exhaustive-deps',
    '',
    '✖ 2 problems (1 error, 1 warning)',
]|join('\n') %}
<div class="w-full max-w-md">
    <twig:CodeBlock>
        <twig:CodeBlock:Header>
            <twig:CodeBlock:Title>
                <twig:ux:icon name="lucide:terminal" />
                Terminal
            </twig:CodeBlock:Title>
            <twig:CodeBlock:Actions>
                <twig:Button variant="ghost" size="icon-xs" aria-label="Wrap lines" aria-pressed="true" class="aria-pressed:text-foreground" data-action="code-block#toggleWrap">
                    <twig:ux:icon name="lucide:wrap-text" />
                </twig:Button>
                <twig:Button variant="ghost" size="icon-xs" aria-label="Download" data-action="code-block#download" data-code-block-text="{{ output }}" data-code-block-filename-param="lint.log">
                    <twig:ux:icon name="lucide:download" />
                </twig:Button>
                <twig:CodeBlock:CopyButton :value="output" />
            </twig:CodeBlock:Actions>
        </twig:CodeBlock:Header>
        <twig:CodeBlock:Content wrap :code="output" />
    </twig:CodeBlock>
</div>
```

### Floating Actions

Without a header, put `CodeBlock:Actions` directly inside `CodeBlock`. The actions float at the top end and stay hidden until the block is hovered or focused. They always show on touch screens.

```twig {"preview":true}
{% set output = [
    '$ pnpm add ai @ai-sdk/react zod',
    'Progress: resolved 412, reused 398, downloaded 14, added 14, done',
    'dependencies:',
    '+ @ai-sdk/react 3.0.12',
    '+ ai 6.0.12',
    '+ zod 4.1.12',
    'Done in 2.8s using pnpm v10.18.0',
]|join('\n') %}
<div class="w-full max-w-md">
    <twig:CodeBlock>
        <twig:CodeBlock:Actions>
            <twig:CodeBlock:CopyButton :value="output" />
        </twig:CodeBlock:Actions>
        <twig:CodeBlock:Content :code="output" />
    </twig:CodeBlock>
</div>
```

### Line Numbers

Add `lineNumbers` to `CodeBlock:Content`. A `code` string is split into lines, and the numbers are not copied with a selection.

```twig {"preview":true}
{% set code = [
    '{',
    '  "name": "chat",',
    '  "private": true,',
    '  "scripts": {',
    '    "dev": "next dev",',
    '    "build": "next build"',
    '  }',
    '}',
]|join('\n') %}
<div class="w-full max-w-md">
    <twig:CodeBlock>
        <twig:CodeBlock:Content lineNumbers :code="code" />
    </twig:CodeBlock>
</div>
```

### Start Line

Set `startLine` to number lines from a given line, for a snippet from the middle of a file. The gutter grows to fit the widest number.

```twig {"preview":true}
{% set code = [
    '  if (!session) {',
    '    redirect("/login")',
    '  }',
    '',
    '  const projects = await db.project.findMany({',
    '    where: { ownerId: session.user.id },',
    '    orderBy: { updatedAt: "desc" },',
    '  })',
    '',
    '  return <ProjectList projects={projects} />',
    '}',
]|join('\n') %}
<div class="w-full max-w-md">
    <twig:CodeBlock>
        <twig:CodeBlock:Header>
            <twig:CodeBlock:Title>app/dashboard/page.tsx</twig:CodeBlock:Title>
        </twig:CodeBlock:Header>
        <twig:CodeBlock:Content lineNumbers :startLine="995" :code="code" />
    </twig:CodeBlock>
</div>
```

### Wrap

Long lines scroll by default. Add `wrap` to wrap them, which suits tool output and error messages. To let the reader toggle it, see [Actions](#actions).

```twig {"preview":true}
{% set output = 'Error: The model "anthropic/claude-sonnet-4.5" returned an error: Request too large. The prompt is 212,431 tokens and the context window is 200,000 tokens. Remove some messages or summarize the conversation and try again.' %}
<div class="flex w-full max-w-md flex-col gap-4">
    <twig:CodeBlock>
        <twig:CodeBlock:Content :code="output" />
    </twig:CodeBlock>
    <twig:CodeBlock>
        <twig:CodeBlock:Content wrap :code="output" />
    </twig:CodeBlock>
</div>
```

### Highlighted Lines

Render each line as a `CodeBlock:Line` and set `variant="highlighted"` on the lines to call out.

```twig {"preview":true}
{% set lines = [
    'export default {',
    '  experimental: {',
    '    reactCompiler: true,',
    '    viewTransition: true,',
    '  },',
    '  images: {',
    '    remotePatterns: [{ hostname: "avatars.githubusercontent.com" }],',
    '  },',
    '}',
] %}
<div class="w-full max-w-md">
    <twig:CodeBlock>
        <twig:CodeBlock:Content lineNumbers>
            {%- for line in lines -%}
                <twig:CodeBlock:Line variant="{{ loop.index0 in [2, 3] ? 'highlighted' : 'default' }}">{{ line }}</twig:CodeBlock:Line>
            {%- endfor -%}
        </twig:CodeBlock:Content>
    </twig:CodeBlock>
</div>
```

### Diff

Set `variant="added"` or `variant="removed"` on a `CodeBlock:Line`. Each one adds a `+` or `−` marker.

```twig {"preview":true}
{% set patch = [
    ' export function Greeting({ name }: { name: string }) {',
    '-  return <p>Hello, {name}</p>',
    '+  return <p className="text-sm text-muted-foreground">',
    '+    Hello, {name}!',
    '+  </p>',
    ' }',
] %}
<div class="w-full max-w-md">
    <twig:CodeBlock>
        <twig:CodeBlock:Header>
            <twig:CodeBlock:Title>components/greeting.tsx</twig:CodeBlock:Title>
            <twig:CodeBlock:Actions>
                <twig:CodeBlock:CopyButton :value="patch|join('\n')" />
            </twig:CodeBlock:Actions>
        </twig:CodeBlock:Header>
        <twig:CodeBlock:Content>
            {%- for line in patch -%}
                <twig:CodeBlock:Line variant="{{ line starts with '+' ? 'added' : (line starts with '-' ? 'removed' : 'default') }}">{{ line|slice(1) }}</twig:CodeBlock:Line>
            {%- endfor -%}
        </twig:CodeBlock:Content>
    </twig:CodeBlock>
</div>
```

### Height

Cap the height with a `max-h-*` class on `CodeBlock:Content`. The content scrolls inside the block. It fades its edges with `scroll-fade` as it scrolls: left and right by default, top and bottom when it wraps. Add `scroll-fade-y` to a capped block that does not wrap.

```twig {"preview":true}
{% set logs = (1..40)|map(index => '[' ~ ('%02d'|format(index)) ~ '] GET /api/chat 200 in ' ~ (12 + ((index - 1) * 7) % 40) ~ 'ms')|join('\n') %}
<div class="w-full max-w-md">
    <twig:CodeBlock>
        <twig:CodeBlock:Header>
            <twig:CodeBlock:Title>Logs</twig:CodeBlock:Title>
            <twig:CodeBlock:Actions>
                <twig:CodeBlock:CopyButton :value="logs" />
            </twig:CodeBlock:Actions>
        </twig:CodeBlock:Header>
        <twig:CodeBlock:Content class="max-h-48 scroll-fade-y" :code="logs" />
    </twig:CodeBlock>
</div>
```

### Show More

`CodeBlock:Footer` is a slot below the content. For Show More, cap the content while it is collapsed with `not-data-expanded:*` classes, and put a ghost `xs` `Button` in the footer. Its `toggleExpanded` action toggles `data-expanded` on the content its `aria-controls` points at.

```twig {"preview":true}
{% set code = [
    '"use client"',
    '',
    'import * as React from "react"',
    'import { useChat } from "@ai-sdk/react"',
    '',
    'import { Button } from "@/components/ui/button"',
    'import { Input } from "@/components/ui/input"',
    '',
    'export function Chat() {',
    '  const [input, setInput] = React.useState("")',
    '  const { messages, sendMessage, status } = useChat()',
    '',
    '  return (',
    '    <div className="flex flex-col gap-4">',
    '      {messages.map((message) => (',
    '        <div key={message.id}>',
    '          {message.parts.map((part, index) =>',
    '            part.type === "text" ? <p key={index}>{part.text}</p> : null',
    '          )}',
    '        </div>',
    '      ))}',
    '      <form',
    '        onSubmit={(event) => {',
    '          event.preventDefault()',
    '          sendMessage({ text: input })',
    '          setInput("")',
    '        }}',
    '      >',
    '        <Input value={input} onChange={(event) => setInput(event.target.value)} />',
    '        <Button disabled={status !== "ready"}>Send</Button>',
    '      </form>',
    '    </div>',
    '  )',
    '}',
]|join('\n') %}
<div class="w-full max-w-md">
    <twig:CodeBlock>
        <twig:CodeBlock:Header>
            <twig:CodeBlock:Title>components/chat.tsx</twig:CodeBlock:Title>
            <twig:CodeBlock:Actions>
                <twig:CodeBlock:CopyButton :value="code" />
            </twig:CodeBlock:Actions>
        </twig:CodeBlock:Header>
        <twig:CodeBlock:Content id="code-block-show-more" lineNumbers class="not-data-expanded:max-h-64 not-data-expanded:overflow-y-hidden not-data-expanded:scroll-fade-y" :code="code" />
        <twig:CodeBlock:Footer class="justify-center">
            <twig:Button variant="ghost" size="xs" aria-expanded="false" aria-controls="code-block-show-more" data-action="code-block#toggleExpanded">
                <twig:ux:icon name="lucide:chevron-down" class="transition-transform in-aria-expanded:rotate-180" />
                <span class="group-aria-expanded/button:hidden">Show more</span>
                <span class="hidden group-aria-expanded/button:inline">Show less</span>
            </twig:Button>
        </twig:CodeBlock:Footer>
    </twig:CodeBlock>
</div>
```

### Max Height

To keep a long block from taking over the page, raise the cap instead of removing it. The expanded content scrolls, and the edges fade as you scroll.

```twig {"preview":true}
{% set code = [
    '"use client"',
    '',
    'import * as React from "react"',
    'import { useChat } from "@ai-sdk/react"',
    '',
    'import { Button } from "@/components/ui/button"',
    'import { Input } from "@/components/ui/input"',
    '',
    'export function Chat() {',
    '  const [input, setInput] = React.useState("")',
    '  const { messages, sendMessage, status } = useChat()',
    '',
    '  return (',
    '    <div className="flex flex-col gap-4">',
    '      {messages.map((message) => (',
    '        <div key={message.id}>',
    '          {message.parts.map((part, index) =>',
    '            part.type === "text" ? <p key={index}>{part.text}</p> : null',
    '          )}',
    '        </div>',
    '      ))}',
    '      <form',
    '        onSubmit={(event) => {',
    '          event.preventDefault()',
    '          sendMessage({ text: input })',
    '          setInput("")',
    '        }}',
    '      >',
    '        <Input value={input} onChange={(event) => setInput(event.target.value)} />',
    '        <Button disabled={status !== "ready"}>Send</Button>',
    '      </form>',
    '    </div>',
    '  )',
    '}',
]|join('\n') %}
<div class="w-full max-w-md">
    <twig:CodeBlock>
        <twig:CodeBlock:Header>
            <twig:CodeBlock:Title>components/chat.tsx</twig:CodeBlock:Title>
            <twig:CodeBlock:Actions>
                <twig:CodeBlock:CopyButton :value="code" />
            </twig:CodeBlock:Actions>
        </twig:CodeBlock:Header>
        <twig:CodeBlock:Content id="code-block-max-height" lineNumbers class="max-h-96 scroll-fade-y not-data-expanded:max-h-64 not-data-expanded:overflow-y-hidden" :code="code" />
        <twig:CodeBlock:Footer class="justify-center">
            <twig:Button variant="ghost" size="xs" aria-expanded="false" aria-controls="code-block-max-height" data-action="code-block#toggleExpanded">
                <twig:ux:icon name="lucide:chevron-down" class="transition-transform in-aria-expanded:rotate-180" />
                <span class="group-aria-expanded/button:hidden">Show more</span>
                <span class="hidden group-aria-expanded/button:inline">Show less</span>
            </twig:Button>
        </twig:CodeBlock:Footer>
    </twig:CodeBlock>
</div>
```

### Size

Use `size="sm"` for tool and [Trace](#trace) output. It has tighter padding and `text-xs` text.

```twig {"preview":true}
{% set output = [
    '$ pnpm add ai @ai-sdk/react zod',
    'Progress: resolved 412, reused 398, downloaded 14, added 14, done',
    'Done in 2.8s using pnpm v10.18.0',
]|join('\n') %}
<div class="flex w-full max-w-md flex-col gap-4">
    {% for size in ['default', 'sm'] %}
        <twig:CodeBlock size="{{ size }}">
            <twig:CodeBlock:Header>
                <twig:CodeBlock:Title>
                    <twig:ux:icon name="lucide:terminal" />
                    Terminal
                </twig:CodeBlock:Title>
                <twig:CodeBlock:Actions>
                    <twig:CodeBlock:CopyButton :value="output" />
                </twig:CodeBlock:Actions>
            </twig:CodeBlock:Header>
            <twig:CodeBlock:Content wrap :code="output" />
        </twig:CodeBlock>
    {% endfor %}
</div>
```

### Destructive

Use `variant="destructive"` for errors and failed output.

```twig {"preview":true}
{% set error = [
    'TypeError: Cannot read properties of undefined (reading \'map\')',
    '    at MessageList (components/message-list.tsx:18:22)',
    '    at renderWithHooks (react-dom.development.js:16305:18)',
]|join('\n') %}
<div class="w-full max-w-md">
    <twig:CodeBlock variant="destructive">
        <twig:CodeBlock:Header>
            <twig:CodeBlock:Title>Error</twig:CodeBlock:Title>
            <twig:CodeBlock:Actions>
                <twig:CodeBlock:CopyButton :value="error" />
            </twig:CodeBlock:Actions>
        </twig:CodeBlock:Header>
        <twig:CodeBlock:Content wrap :code="error" />
    </twig:CodeBlock>
</div>
```

### Trace

Put a `CodeBlock` with `size="sm"` in `Trace:Collapsible:Content` for tool and terminal output. Add `wrap` and a `max-h-64` class to `CodeBlock:Content` so long output wraps and scrolls inside the trace. The block sets its own text color, so `Trace:Collapsible:Content` does not mute it.

```twig {"preview":true}
{% set output = [
    '$ node --version',
    'v24.18.0',
]|join('\n') %}
{% set error = [
    '$ pnpm dev',
    'Error: listen EADDRINUSE: address already in use :::3000',
    '    at Server.setupListenHandle [as _listen2] (node:net:1908:16)',
]|join('\n') %}
<div class="w-full max-w-md">
    <twig:Trace>
        <twig:Trace:Collapsible open>
            <twig:Trace:Collapsible:Trigger>
                <twig:Trace:Line>
                    <twig:Trace:Icon>
                        <twig:ux:icon name="lucide:terminal" />
                    </twig:Trace:Icon>
                    <twig:Trace:Title>Ran node --version</twig:Trace:Title>
                </twig:Trace:Line>
            </twig:Trace:Collapsible:Trigger>
            <twig:Trace:Collapsible:Content>
                <twig:CodeBlock size="sm">
                    <twig:CodeBlock:Content wrap class="max-h-64" :code="output" />
                </twig:CodeBlock>
            </twig:Trace:Collapsible:Content>
        </twig:Trace:Collapsible>
        <twig:Trace:Collapsible open>
            <twig:Trace:Collapsible:Trigger>
                <twig:Trace:Line status="error">
                    <twig:Trace:Icon>
                        <twig:ux:icon name="lucide:terminal" />
                    </twig:Trace:Icon>
                    <twig:Trace:Title>Failed to run pnpm dev</twig:Trace:Title>
                </twig:Trace:Line>
            </twig:Trace:Collapsible:Trigger>
            <twig:Trace:Collapsible:Content>
                <twig:CodeBlock size="sm" variant="destructive">
                    <twig:CodeBlock:Content wrap class="max-h-64" :code="error" />
                </twig:CodeBlock>
            </twig:Trace:Collapsible:Content>
        </twig:Trace:Collapsible>
    </twig:Trace>
</div>
```

### Tabs

Show several files with [Tabs](https://ux.symfony.com/toolkit/kits/shadcn/components/tabs), one `CodeBlock` in each `Tabs:Content`.

```twig {"preview":true}
{% set files = [
    {
        id: 'route',
        title: 'app/api/chat/route.ts',
        code: [
            'import { streamText } from "ai"',
            '',
            'export async function POST(request: Request) {',
            '  const { messages } = await request.json()',
            '  const result = streamText({ model: "anthropic/claude-sonnet-4.5", messages })',
            '',
            '  return result.toUIMessageStreamResponse()',
            '}',
        ]|join('\n'),
    },
    {
        id: 'chat',
        title: 'components/chat.tsx',
        code: [
            '"use client"',
            '',
            'import { useChat } from "@ai-sdk/react"',
            '',
            'export function Chat() {',
            '  const { messages } = useChat()',
            '',
            '  return <MessageList messages={messages} />',
            '}',
        ]|join('\n'),
    },
    {
        id: 'env',
        title: '.env.local',
        code: 'AI_GATEWAY_API_KEY=your-api-key',
    },
] %}
<twig:Tabs defaultValue="route" class="w-full max-w-md">
    <twig:Tabs:List>
        {% for file in files %}
            <twig:Tabs:Trigger value="{{ file.id }}">{{ file.title|split('/')|last }}</twig:Tabs:Trigger>
        {% endfor %}
    </twig:Tabs:List>
    {% for file in files %}
        <twig:Tabs:Content value="{{ file.id }}">
            <twig:CodeBlock>
                <twig:CodeBlock:Header>
                    <twig:CodeBlock:Title>{{ file.title }}</twig:CodeBlock:Title>
                    <twig:CodeBlock:Actions>
                        <twig:CodeBlock:CopyButton :value="file.code" />
                    </twig:CodeBlock:Actions>
                </twig:CodeBlock:Header>
                <twig:CodeBlock:Content lineNumbers :code="file.code" />
            </twig:CodeBlock>
        </twig:Tabs:Content>
    {% endfor %}
</twig:Tabs>
```

### Message

Put a `CodeBlock` between paragraphs inside a [Message](https://ux.symfony.com/toolkit/kits/shadcn/components/message).

```twig {"preview":true}
{% set code = [
    'import { streamText } from "ai"',
    '',
    'export async function POST(request: Request) {',
    '  const { messages } = await request.json()',
    '',
    '  const result = streamText({',
    '    model: "anthropic/claude-sonnet-4.5",',
    '    messages,',
    '  })',
    '',
    '  return result.toUIMessageStreamResponse()',
    '}',
]|join('\n') %}
<div class="flex w-full max-w-md flex-col gap-4 text-sm">
    <twig:Message align="end">
        <twig:Message:Content>
            <twig:Bubble variant="muted">
                <twig:Bubble:Content>How do I stream a response?</twig:Bubble:Content>
            </twig:Bubble>
        </twig:Message:Content>
    </twig:Message>
    <twig:Message>
        <twig:Message:Content class="gap-3">
            <p>Create a route handler that returns the stream:</p>
            <twig:CodeBlock>
                <twig:CodeBlock:Header>
                    <twig:CodeBlock:Title>app/api/chat/route.ts</twig:CodeBlock:Title>
                    <twig:CodeBlock:Actions>
                        <twig:CodeBlock:CopyButton :value="code" />
                    </twig:CodeBlock:Actions>
                </twig:CodeBlock:Header>
                <twig:CodeBlock:Content :code="code" />
            </twig:CodeBlock>
            <p>The client reads it with useChat.</p>
        </twig:Message:Content>
    </twig:Message>
</div>
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

Code always reads left to right. The header follows the page direction.

```twig {"preview":true}
{% set code = [
    'pnpm add ai @ai-sdk/react zod',
    'pnpm dev',
]|join('\n') %}
<div class="flex w-full max-w-md flex-col gap-8">
    {% for translation in [
        {description: 'ثبّت الحزم، ثم شغّل خادم التطوير.', title: 'الطرفية'},
        {description: 'התקן את החבילות, ואז הפעל את שרת הפיתוח.', title: 'מסוף'},
    ] %}
        <div dir="rtl" class="flex flex-col gap-3 text-sm">
            <p>{{ translation.description }}</p>
            <twig:CodeBlock>
                <twig:CodeBlock:Header>
                    <twig:CodeBlock:Title>
                        <twig:ux:icon name="lucide:terminal" />
                        {{ translation.title }}
                    </twig:CodeBlock:Title>
                    <twig:CodeBlock:Actions>
                        <twig:CodeBlock:CopyButton :value="code" />
                    </twig:CodeBlock:Actions>
                </twig:CodeBlock:Header>
                <twig:CodeBlock:Content lineNumbers :code="code" />
            </twig:CodeBlock>
        </div>
    {% endfor %}
</div>
```

## Accessibility

- `CodeBlock:Content` is a `pre` with a `code` element and always sets `dir="ltr"`, so code reads left to right on an RTL page.
- `CodeBlock:Content` has `tabindex="0"`, so keyboard users can focus it and scroll it. Pass `tabindex="-1"` to remove the tab stop.
- Floating actions show when focus is inside the block, not only on hover, so keyboard users can reach them.
- Line numbers and diff markers are generated content, left out of a copied selection, and blank lines survive the copy.
- Diff lines carry a `+` or `−` marker as well as a tint, so the change does not rely on color.
- `CodeBlock:CopyButton` is labelled "Copy", and a polite live region announces "Copied" after a successful copy.
- Give a wrap toggle `aria-pressed` so its state is announced; the `toggleWrap` action keeps it in sync.
- Give a Show More button `aria-expanded` and point `aria-controls` at the `id` of `CodeBlock:Content`; the `toggleExpanded` action keeps it in sync.

## API Reference

::: api-reference
