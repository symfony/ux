# Trace

The building block for reasoning, tool calls, sources and more.

```twig {"preview":true,"collapseClass":true}
<div class="flex w-full max-w-sm flex-col gap-4 py-6 text-sm">
    <twig:Message align="end">
        <twig:Message:Content>
            <twig:Bubble align="end" variant="muted">
                <twig:Bubble:Content>Why does pnpm install fail in CI?</twig:Bubble:Content>
            </twig:Bubble>
        </twig:Message:Content>
    </twig:Message>
    <twig:Message>
        <twig:Message:Content class="gap-4">
            <twig:Trace>
                <twig:Trace:Collapsible>
                    <twig:Trace:Collapsible:Trigger>
                        <twig:Trace:Line>
                            <twig:Trace:Icon>
                                <twig:ux:icon name="lucide:circle-check" />
                            </twig:Trace:Icon>
                            <twig:Trace:Title>Thought for 5s</twig:Trace:Title>
                        </twig:Trace:Line>
                    </twig:Trace:Collapsible:Trigger>
                    <twig:Trace:Collapsible:Content class="whitespace-pre-wrap">
                        CI installs with a frozen lockfile, so the install stops when package.json and pnpm-lock.yaml disagree. I should check what the last commit changed, then reproduce the install.
                    </twig:Trace:Collapsible:Content>
                </twig:Trace:Collapsible>
                <twig:Trace:Collapsible>
                    <twig:Trace:Collapsible:Trigger>
                        <twig:Trace:Line>
                            <twig:Trace:Icon>
                                <twig:ux:icon name="lucide:circle-check" />
                            </twig:Trace:Icon>
                            <twig:Trace:Title>Ran pnpm --version</twig:Trace:Title>
                        </twig:Trace:Line>
                    </twig:Trace:Collapsible:Trigger>
                    <twig:Trace:Collapsible:Content>
                        <pre class="max-h-64 overflow-auto rounded-lg border border-input px-2.5 py-2.5 font-mono text-xs leading-relaxed whitespace-pre-wrap wrap-break-word text-foreground dark:bg-input/30">10.18.0</pre>
                    </twig:Trace:Collapsible:Content>
                </twig:Trace:Collapsible>
                <twig:Trace:Collapsible>
                    <twig:Trace:Collapsible:Trigger>
                        <twig:Trace:Line>
                            <twig:Trace:Icon>
                                <twig:ux:icon name="lucide:circle-check" />
                            </twig:Trace:Icon>
                            <twig:Trace:Title>Ran git diff --stat HEAD~1 -- package.json pnpm-lock.yaml</twig:Trace:Title>
                        </twig:Trace:Line>
                    </twig:Trace:Collapsible:Trigger>
                    <twig:Trace:Collapsible:Content>
                        <pre class="max-h-64 overflow-auto rounded-lg border border-input px-2.5 py-2.5 font-mono text-xs leading-relaxed whitespace-pre-wrap wrap-break-word text-foreground dark:bg-input/30"> package.json | 2 +-
 1 file changed, 1 insertion(+), 1 deletion(-)</pre>
                    </twig:Trace:Collapsible:Content>
                </twig:Trace:Collapsible>
                <twig:Trace:Collapsible open>
                    <twig:Trace:Collapsible:Trigger>
                        <twig:Trace:Line status="error">
                            <twig:Trace:Icon>
                                <twig:ux:icon name="lucide:circle-alert" />
                            </twig:Trace:Icon>
                            <twig:Trace:Title>Failed to run pnpm install --frozen-lockfile</twig:Trace:Title>
                        </twig:Trace:Line>
                    </twig:Trace:Collapsible:Trigger>
                    <twig:Trace:Collapsible:Content>
                        <pre class="max-h-64 overflow-auto rounded-lg border border-destructive/30 px-2.5 py-2.5 font-mono text-xs leading-relaxed whitespace-pre-wrap wrap-break-word text-destructive dark:border-destructive/40">ERR_PNPM_OUTDATED_LOCKFILE  Cannot install with "frozen-lockfile" because pnpm-lock.yaml is not up to date with package.json</pre>
                    </twig:Trace:Collapsible:Content>
                </twig:Trace:Collapsible>
                <twig:Trace:Collapsible>
                    <twig:Trace:Collapsible:Trigger>
                        <twig:Trace:Line>
                            <twig:Trace:Icon>
                                <twig:ux:icon name="lucide:globe" />
                            </twig:Trace:Icon>
                            <twig:Trace:Title>Used 2 sources</twig:Trace:Title>
                        </twig:Trace:Line>
                    </twig:Trace:Collapsible:Trigger>
                    <twig:Trace:Collapsible:Content>
                        <twig:Trace>
                            {% for source in [
                                {title: 'pnpm install | pnpm.io', url: 'https://pnpm.io/cli/install#--frozen-lockfile'},
                                {title: 'Continuous Integration | pnpm.io', url: 'https://pnpm.io/continuous-integration'},
                            ] %}
                                <a href="{{ source.url }}" target="_blank" rel="noreferrer">
                                    <twig:Trace:Line>
                                        <twig:Trace:Icon>
                                            <twig:ux:icon name="lucide:globe" />
                                        </twig:Trace:Icon>
                                        <twig:Trace:Title>
                                            {{ source.title }}
                                            <span class="sr-only"> (opens in a new tab)</span>
                                        </twig:Trace:Title>
                                        <twig:Trace:Icon>
                                            <twig:ux:icon name="lucide:arrow-up-right" />
                                        </twig:Trace:Icon>
                                    </twig:Trace:Line>
                                </a>
                            {% endfor %}
                        </twig:Trace>
                    </twig:Trace:Collapsible:Content>
                </twig:Trace:Collapsible>
            </twig:Trace>
            <p>The last commit bumped a version in package.json without updating pnpm-lock.yaml, so the frozen install fails. Run pnpm install locally and commit the lockfile.</p>
        </twig:Message:Content>
    </twig:Message>
</div>
```

## Installation

::: installation

## Usage

Use a `Trace:Line` for a single step:

```twig
<twig:Trace>
    <twig:Trace:Line>
        <twig:Trace:Icon>
            <twig:ux:icon name="lucide:search" />
        </twig:Trace:Icon>
        <twig:Trace:Title>Searched for minimumReleaseAge</twig:Trace:Title>
    </twig:Trace:Line>
</twig:Trace>
```

Wrap the line in a `Trace:Collapsible` to show more detail when it expands:

```twig
<twig:Trace>
    <twig:Trace:Collapsible>
        <twig:Trace:Collapsible:Trigger>
            <twig:Trace:Line>
                <twig:Trace:Icon>
                    <twig:ux:icon name="lucide:terminal" />
                </twig:Trace:Icon>
                <twig:Trace:Title>Ran node --version</twig:Trace:Title>
            </twig:Trace:Line>
        </twig:Trace:Collapsible:Trigger>
        <twig:Trace:Collapsible:Content>v24.18.0</twig:Trace:Collapsible:Content>
    </twig:Trace:Collapsible>
</twig:Trace>
```

## Examples

### Trace Line

Use a `Trace:Line` on its own for a step with no detail to show. It reads as plain text.

```twig {"preview":true}
<twig:Trace class="max-w-sm">
    <twig:Trace:Line>
        <twig:Trace:Icon>
            <twig:ux:icon name="lucide:git-branch" />
        </twig:Trace:Icon>
        <twig:Trace:Title>Created branch fix/frozen-lockfile</twig:Trace:Title>
    </twig:Trace:Line>
</twig:Trace>
```

### Status

Set `status` on `Trace:Line` when a step is running, has failed or hasn't started yet.

```twig {"preview":true}
<twig:Trace class="max-w-sm">
    <twig:Trace:Line>
        <twig:Trace:Icon>
            <twig:ux:icon name="lucide:circle-check" />
        </twig:Trace:Icon>
        <twig:Trace:Title>Ran pnpm install</twig:Trace:Title>
    </twig:Trace:Line>
    <twig:Trace:Line status="error">
        <twig:Trace:Icon>
            <twig:ux:icon name="lucide:circle-alert" />
        </twig:Trace:Icon>
        <twig:Trace:Title>Failed to run pnpm lint</twig:Trace:Title>
    </twig:Trace:Line>
    <twig:Trace:Line status="running">
        <twig:Trace:Icon>
            <twig:ux:icon name="lucide:loader-circle" class="animate-spin" />
        </twig:Trace:Icon>
        <twig:Trace:Title>Running pnpm build</twig:Trace:Title>
    </twig:Trace:Line>
    <twig:Trace:Line status="pending">
        <twig:Trace:Icon>
            <twig:ux:icon name="lucide:circle" />
        </twig:Trace:Icon>
        <twig:Trace:Title>Run pnpm test</twig:Trace:Title>
    </twig:Trace:Line>
</twig:Trace>
```

### Description

Put secondary text such as a diff stat, a duration or a count in a `Trace:Description` after the `Trace:Title`. It does not truncate.

```twig {"preview":true}
<twig:Trace class="max-w-sm">
    <twig:Trace:Line>
        <twig:Trace:Icon>
            <twig:ux:icon name="lucide:file-pen" />
        </twig:Trace:Icon>
        <twig:Trace:Title>Edited components/settings/notification-preferences-form.tsx</twig:Trace:Title>
        <twig:Trace:Description class="font-mono">+48 −12</twig:Trace:Description>
    </twig:Trace:Line>
    <twig:Trace:Line>
        <twig:Trace:Icon>
            <twig:ux:icon name="lucide:terminal" />
        </twig:Trace:Icon>
        <twig:Trace:Title>Ran pnpm test</twig:Trace:Title>
        <twig:Trace:Description>14s</twig:Trace:Description>
    </twig:Trace:Line>
    <twig:Trace:Line>
        <twig:Trace:Icon>
            <twig:ux:icon name="lucide:search" />
        </twig:Trace:Icon>
        <twig:Trace:Title>Searched for useChat</twig:Trace:Title>
        <twig:Trace:Description>8 results</twig:Trace:Description>
    </twig:Trace:Line>
</twig:Trace>
```

### Collapsible

Wrap the line in a `Trace:Collapsible` to show more detail when it expands. Put the `Trace:Line` inside `Trace:Collapsible:Trigger` and the detail in `Trace:Collapsible:Content`.

```twig {"preview":true,"collapseClass":true}
<div class="w-full max-w-sm">
    <twig:Trace:Collapsible>
        <twig:Trace:Collapsible:Trigger>
            <twig:Trace:Line>
                <twig:Trace:Icon>
                    <twig:ux:icon name="lucide:terminal" />
                </twig:Trace:Icon>
                <twig:Trace:Title>Ran node --version</twig:Trace:Title>
            </twig:Trace:Line>
        </twig:Trace:Collapsible:Trigger>
        <twig:Trace:Collapsible:Content>
            <pre class="max-h-64 overflow-auto rounded-lg border border-input px-2.5 py-2.5 font-mono text-xs leading-relaxed whitespace-pre-wrap wrap-break-word text-foreground dark:bg-input/30">$ node --version
v24.18.0</pre>
        </twig:Trace:Collapsible:Content>
    </twig:Trace:Collapsible>
</div>
```

### Open State

Rows start closed. Set `open` on `Trace:Collapsible` to start open. To toggle a row from another button inside it, wrap that button in `Collapsible:Trigger` and spread `collapsible_trigger_attrs` on it. That button then also reflects the open state, through its `aria-expanded` and `data-state` attributes.

```twig {"preview":true,"collapseClass":true}
<div class="flex w-full max-w-sm flex-col items-start gap-4">
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
            <pre class="max-h-64 overflow-auto rounded-lg border border-input px-2.5 py-2.5 font-mono text-xs leading-relaxed whitespace-pre-wrap wrap-break-word text-foreground dark:bg-input/30">$ node --version
v24.18.0</pre>
        </twig:Trace:Collapsible:Content>
    </twig:Trace:Collapsible>
    <twig:Trace:Collapsible>
        <twig:Trace:Collapsible:Trigger>
            <twig:Trace:Line>
                <twig:Trace:Icon>
                    <twig:ux:icon name="lucide:terminal" />
                </twig:Trace:Icon>
                <twig:Trace:Title>Ran pnpm --version</twig:Trace:Title>
            </twig:Trace:Line>
        </twig:Trace:Collapsible:Trigger>
        <twig:Trace:Collapsible:Content>
            <pre class="max-h-64 overflow-auto rounded-lg border border-input px-2.5 py-2.5 font-mono text-xs leading-relaxed whitespace-pre-wrap wrap-break-word text-foreground dark:bg-input/30">$ pnpm --version
10.33.4</pre>
        </twig:Trace:Collapsible:Content>
        <twig:Collapsible:Trigger>
            <twig:Button variant="outline" size="sm" class="group/toggle mt-4 self-start" {{ ...collapsible_trigger_attrs }}>
                <span class="group-data-[state=open]/toggle:hidden">Show</span>
                <span class="hidden group-data-[state=open]/toggle:inline">Hide</span>
                the pnpm output
            </twig:Button>
        </twig:Collapsible:Trigger>
    </twig:Trace:Collapsible>
</div>
```

### Link

Wrap the `Trace:Line` in an anchor. For a new tab, use an arrow-up-right icon and sr-only "(opens in a new tab)".

```twig {"preview":true}
<twig:Trace class="max-w-sm">
    <a href="#link">
        <twig:Trace:Line>
            <twig:Trace:Icon>
                <twig:ux:icon name="lucide:file-text" />
            </twig:Trace:Icon>
            <twig:Trace:Title>Read apps/v4/lib/docs.ts</twig:Trace:Title>
            <twig:Trace:Icon>
                <twig:ux:icon name="lucide:chevron-right" class="rtl:rotate-180" />
            </twig:Trace:Icon>
        </twig:Trace:Line>
    </a>
    <a href="https://docs.astro.build/en/install-and-setup/" target="_blank" rel="noreferrer">
        <twig:Trace:Line>
            <twig:Trace:Icon>
                <twig:ux:icon name="lucide:globe" />
            </twig:Trace:Icon>
            <twig:Trace:Title>
                Install Astro | docs.astro.build
                <span class="sr-only"> (opens in a new tab)</span>
            </twig:Trace:Title>
            <twig:Trace:Icon>
                <twig:ux:icon name="lucide:arrow-up-right" />
            </twig:Trace:Icon>
        </twig:Trace:Line>
    </a>
</twig:Trace>
```

### Dialog

Wrap the `Trace:Line` in the dialog's trigger. The dialog owns its open state, Escape and focus return.

```twig {"preview":true}
<div class="w-full max-w-sm" style="min-height: 320px">
    <twig:Trace>
        <twig:Dialog id="trace-dialog">
            <twig:Dialog:Trigger>
                <button type="button" {{ html_attr(dialog_trigger_attrs) }}>
                    <twig:Trace:Line>
                        <twig:Trace:Icon>
                            <twig:ux:icon name="lucide:globe" />
                        </twig:Trace:Icon>
                        <twig:Trace:Title>Searched the web for cache keys</twig:Trace:Title>
                        <twig:Trace:Icon>
                            <twig:ux:icon name="lucide:chevron-right" class="rtl:rotate-180" />
                        </twig:Trace:Icon>
                    </twig:Trace:Line>
                </button>
            </twig:Dialog:Trigger>
            <twig:Dialog:Content>
                <twig:Dialog:Header>
                    <twig:Dialog:Title>Searched the web for cache keys</twig:Dialog:Title>
                    <twig:Dialog:Description>Three results were used in the response.</twig:Dialog:Description>
                </twig:Dialog:Header>
                <twig:Trace>
                    {% for result in [
                        {title: 'Caching build outputs', url: 'https://nextjs.org/docs/app/guides/caching'},
                        {title: 'Image optimization reference', url: 'https://nextjs.org/docs/app/api-reference/components/image'},
                        {title: 'Deploy hooks and cache keys', url: 'https://vercel.com/docs/deploy-hooks'},
                    ] %}
                        <a href="{{ result.url }}" target="_blank" rel="noreferrer">
                            <twig:Trace:Line>
                                <twig:Trace:Icon>
                                    <twig:ux:icon name="lucide:globe" />
                                </twig:Trace:Icon>
                                <twig:Trace:Title>
                                    {{ result.title }}
                                    <span class="sr-only"> (opens in a new tab)</span>
                                </twig:Trace:Title>
                                <twig:Trace:Icon>
                                    <twig:ux:icon name="lucide:arrow-up-right" />
                                </twig:Trace:Icon>
                            </twig:Trace:Line>
                        </a>
                    {% endfor %}
                </twig:Trace>
            </twig:Dialog:Content>
        </twig:Dialog>
    </twig:Trace>
</div>
```

### Sheet

The same shape as the dialog. Drawer and Popover work the same way.

```twig {"preview":true}
<div class="w-full max-w-sm" style="min-height: 400px">
    <twig:Trace>
        <twig:Sheet id="trace-sheet">
            <twig:Sheet:Trigger>
                <button type="button" {{ html_attr(sheet_trigger_attrs) }}>
                    <twig:Trace:Line>
                        <twig:Trace:Icon>
                            <twig:ux:icon name="lucide:folder-search" />
                        </twig:Trace:Icon>
                        <twig:Trace:Title>Explored 4 files</twig:Trace:Title>
                        <twig:Trace:Icon>
                            <twig:ux:icon name="lucide:chevron-right" class="rtl:rotate-180" />
                        </twig:Trace:Icon>
                    </twig:Trace:Line>
                </button>
            </twig:Sheet:Trigger>
            <twig:Sheet:Content>
                <twig:Sheet:Header>
                    <twig:Sheet:Title>Explored 4 files</twig:Sheet:Title>
                    <twig:Sheet:Description>Files read while preparing the response.</twig:Sheet:Description>
                </twig:Sheet:Header>
                <ul class="flex flex-col gap-2 px-6 font-mono text-xs">
                    <li>apps/v4/lib/docs.ts</li>
                    <li>apps/v4/lib/components.ts</li>
                    <li>apps/v4/next.config.mjs</li>
                    <li>apps/v4/package.json</li>
                </ul>
            </twig:Sheet:Content>
        </twig:Sheet>
    </twig:Trace>
</div>
```

### Group

Put rows that sit next to each other in a `Trace`. It also styles any trigger or link that wraps a `Trace:Line`.

```twig {"preview":true}
<twig:Trace class="max-w-sm">
    <twig:Trace:Line>
        <twig:Trace:Icon>
            <twig:ux:icon name="lucide:search" />
        </twig:Trace:Icon>
        <twig:Trace:Title>Searched for minimumReleaseAge</twig:Trace:Title>
    </twig:Trace:Line>
    <twig:Trace:Line>
        <twig:Trace:Icon>
            <twig:ux:icon name="lucide:file-text" />
        </twig:Trace:Icon>
        <twig:Trace:Title>Read pnpm-workspace.yaml</twig:Trace:Title>
    </twig:Trace:Line>
    <twig:Trace:Line>
        <twig:Trace:Icon>
            <twig:ux:icon name="lucide:terminal" />
        </twig:Trace:Icon>
        <twig:Trace:Title>Ran pnpm install</twig:Trace:Title>
    </twig:Trace:Line>
</twig:Trace>
```

### Sources

A collapsible row whose detail is a `Trace` of link rows.

```twig {"preview":true}
<div class="w-full max-w-sm">
    <twig:Trace:Collapsible open>
        <twig:Trace:Collapsible:Trigger>
            <twig:Trace:Line>
                <twig:Trace:Icon>
                    <twig:ux:icon name="lucide:globe" />
                </twig:Trace:Icon>
                <twig:Trace:Title>Used 3 sources</twig:Trace:Title>
            </twig:Trace:Line>
        </twig:Trace:Collapsible:Trigger>
        <twig:Trace:Collapsible:Content>
            <twig:Trace>
                {% for source in [
                    {title: 'Install Astro | docs.astro.build', href: 'https://docs.astro.build/en/install-and-setup/'},
                    {title: 'create-astro | npmjs.com', href: 'https://www.npmjs.com/package/create-astro'},
                    {title: 'Settings: minimumReleaseAge | pnpm.io', href: 'https://pnpm.io/settings#minimumreleaseage'},
                ] %}
                    <a href="{{ source.href }}" target="_blank" rel="noreferrer">
                        <twig:Trace:Line>
                            <twig:Trace:Icon>
                                <twig:ux:icon name="lucide:globe" />
                            </twig:Trace:Icon>
                            <twig:Trace:Title>
                                {{ source.title }}
                                <span class="sr-only"> (opens in a new tab)</span>
                            </twig:Trace:Title>
                            <twig:Trace:Icon>
                                <twig:ux:icon name="lucide:arrow-up-right" />
                            </twig:Trace:Icon>
                        </twig:Trace:Line>
                    </a>
                {% endfor %}
            </twig:Trace>
        </twig:Trace:Collapsible:Content>
    </twig:Trace:Collapsible>
</div>
```

### Chain of Thought

A chain of thought is a collapsible row with step rows inside. Set the parent's status and title from its children.

```twig {"preview":true,"collapseClass":true}
<div class="w-full max-w-sm">
    <twig:Trace:Collapsible open>
        <twig:Trace:Collapsible:Trigger>
            <twig:Trace:Line status="running">
                <twig:Trace:Title>Looking into the slow deploy</twig:Trace:Title>
            </twig:Trace:Line>
        </twig:Trace:Collapsible:Trigger>
        <twig:Trace:Collapsible:Content>
            <twig:Trace>
                <twig:Trace:Collapsible>
                    <twig:Trace:Collapsible:Trigger>
                        <twig:Trace:Line>
                            <twig:Trace:Icon>
                                <twig:ux:icon name="lucide:search" />
                            </twig:Trace:Icon>
                            <twig:Trace:Title>Searched for the checkout build log</twig:Trace:Title>
                        </twig:Trace:Line>
                    </twig:Trace:Collapsible:Trigger>
                    <twig:Trace:Collapsible:Content class="flex flex-wrap gap-1.5">
                        <twig:Badge variant="secondary">vercel.com</twig:Badge>
                        <twig:Badge variant="secondary">github.com</twig:Badge>
                    </twig:Trace:Collapsible:Content>
                </twig:Trace:Collapsible>
                <a href="#steps">
                    <twig:Trace:Line>
                        <twig:Trace:Icon>
                            <twig:ux:icon name="lucide:file-text" />
                        </twig:Trace:Icon>
                        <twig:Trace:Title>Read the last twelve deploys</twig:Trace:Title>
                        <twig:Trace:Icon>
                            <twig:ux:icon name="lucide:chevron-right" class="rtl:rotate-180" />
                        </twig:Trace:Icon>
                    </twig:Trace:Line>
                </a>
                <twig:Trace:Collapsible>
                    <twig:Trace:Collapsible:Trigger>
                        <twig:Trace:Line status="running">
                            <twig:Trace:Icon>
                                <twig:ux:icon name="lucide:search" />
                            </twig:Trace:Icon>
                            <twig:Trace:Title>Searching for the cache key</twig:Trace:Title>
                        </twig:Trace:Line>
                    </twig:Trace:Collapsible:Trigger>
                    <twig:Trace:Collapsible:Content>
                        <pre class="max-h-64 overflow-auto rounded-lg border border-input px-2.5 py-2.5 font-mono text-xs leading-relaxed whitespace-pre-wrap wrap-break-word text-foreground dark:bg-input/30">cache key image optimization</pre>
                    </twig:Trace:Collapsible:Content>
                </twig:Trace:Collapsible>
            </twig:Trace>
        </twig:Trace:Collapsible:Content>
    </twig:Trace:Collapsible>
</div>
```

### Commit

A commit is a collapsible row with a line for each changed file. Say what changed in the title, Added, Edited or Deleted, and put the line counts in the description. Each file opens its diff in a [Sheet](#sheet).

```twig {"preview":true,"collapseClass":true}
<div class="w-full max-w-sm" style="min-height: 400px">
    <twig:Trace>
        <twig:Trace:Collapsible open>
            <twig:Trace:Collapsible:Trigger>
                <twig:Trace:Line>
                    <twig:Trace:Icon>
                        <twig:ux:icon name="lucide:git-commit-horizontal" />
                    </twig:Trace:Icon>
                    <twig:Trace:Title>Committed a1b2c3: Fix the login redirect</twig:Trace:Title>
                </twig:Trace:Line>
            </twig:Trace:Collapsible:Trigger>
            <twig:Trace:Collapsible:Content>
                <twig:Trace>
                    {% for file in [
                        {
                            id: 'login-page',
                            path: 'app/login/page.tsx',
                            added: false,
                            additions: 2,
                            deletions: 2,
                            lines: [
                                {variant: 'hunk', text: '@@ -14,7 +14,7 @@ export default async function LoginPage({'},
                                {variant: 'default', text: '  searchParams,'},
                                {variant: 'default', text: '}) {'},
                                {variant: 'default', text: '  const { next } = await searchParams'},
                                {variant: 'removed', text: '  const redirectTo ='},
                                {variant: 'removed', text: '    next && next.startsWith("/") ? next : "/"'},
                                {variant: 'added', text: '  const redirectTo = safeRedirect(next)'},
                                {variant: 'added', text: ''},
                                {variant: 'default', text: '  return <LoginForm redirectTo={redirectTo} />'},
                                {variant: 'default', text: '}'},
                            ],
                        },
                        {
                            id: 'auth-redirect',
                            path: 'lib/auth/redirect.ts',
                            added: true,
                            additions: 7,
                            deletions: 0,
                            lines: [
                                {variant: 'hunk', text: '@@ -0,0 +1,7 @@'},
                                {variant: 'added', text: 'export function safeRedirect(next: string | undefined) {'},
                                {variant: 'added', text: '  if (!next || !next.startsWith("/") || next.startsWith("//")) {'},
                                {variant: 'added', text: '    return "/"'},
                                {variant: 'added', text: '  }'},
                                {variant: 'added', text: ''},
                                {variant: 'added', text: '  return next'},
                                {variant: 'added', text: '}'},
                            ],
                        },
                    ] %}
                        <twig:Sheet id="trace-commit-{{ file.id }}">
                            <twig:Sheet:Trigger>
                                <button type="button" {{ html_attr(sheet_trigger_attrs) }}>
                                    <twig:Trace:Line>
                                        <twig:Trace:Icon>
                                            <twig:ux:icon name="{{ file.added ? 'lucide:file-plus' : 'lucide:file-pen' }}" />
                                        </twig:Trace:Icon>
                                        <twig:Trace:Title>{{ file.added ? 'Added' : 'Edited' }} {{ file.path }}</twig:Trace:Title>
                                        <twig:Trace:Description class="font-mono">+{{ file.additions }} −{{ file.deletions }}</twig:Trace:Description>
                                        <twig:Trace:Icon>
                                            <twig:ux:icon name="lucide:chevron-right" class="rtl:rotate-180" />
                                        </twig:Trace:Icon>
                                    </twig:Trace:Line>
                                </button>
                            </twig:Sheet:Trigger>
                            <twig:Sheet:Content>
                                <twig:Sheet:Header>
                                    <twig:Sheet:Title class="font-mono">{{ file.path }}</twig:Sheet:Title>
                                    <twig:Sheet:Description>{{ file.additions }} additions, {{ file.deletions }} deletions</twig:Sheet:Description>
                                </twig:Sheet:Header>
                                <pre class="mx-6 mb-6 overflow-auto rounded-lg border border-input py-3 font-mono text-sm leading-relaxed text-foreground dark:bg-input/30"><code class="grid min-w-max">
                                    {%- for line in file.lines -%}
                                        <span class="{{ html_classes('relative block min-h-lh px-3 after:absolute after:top-0 after:start-1 after:select-none', {
                                            'text-muted-foreground': line.variant == 'hunk',
                                            "bg-muted after:text-muted-foreground after:content-['+']": line.variant == 'added',
                                            "bg-destructive/10 after:text-destructive after:content-['−']": line.variant == 'removed',
                                        }) }}">{{ line.text }}</span>
                                    {%- endfor -%}
                                </code></pre>
                            </twig:Sheet:Content>
                        </twig:Sheet>
                    {% endfor %}
                </twig:Trace>
            </twig:Trace:Collapsible:Content>
        </twig:Trace:Collapsible>
    </twig:Trace>
</div>
```

### Whole Turn

Three levels: the turn, a summary, and the rows. Text between steps sits between two `Trace` lists.

```twig {"preview":true,"collapseClass":true}
<div class="w-full max-w-sm text-sm">
    <twig:Trace:Collapsible open>
        <twig:Trace:Collapsible:Trigger>
            <twig:Trace:Line status="error">
                <twig:Trace:Title>Worked for 2m 4s, 1 command failed</twig:Trace:Title>
            </twig:Trace:Line>
        </twig:Trace:Collapsible:Trigger>
        <twig:Trace:Collapsible:Content class="flex flex-col gap-2">
            <twig:Trace>
                <twig:Trace:Collapsible>
                    <twig:Trace:Collapsible:Trigger>
                        <twig:Trace:Line>
                            <twig:Trace:Icon>
                                <twig:ux:icon name="lucide:brain" />
                            </twig:Trace:Icon>
                            <twig:Trace:Title>Thought</twig:Trace:Title>
                            <twig:Trace:Description>12s</twig:Trace:Description>
                        </twig:Trace:Line>
                    </twig:Trace:Collapsible:Trigger>
                    <twig:Trace:Collapsible:Content class="whitespace-pre-wrap">
                        I should check which package managers are installed before I scaffold anything.
                    </twig:Trace:Collapsible:Content>
                </twig:Trace:Collapsible>
                <twig:Trace:Collapsible open>
                    <twig:Trace:Collapsible:Trigger>
                        <twig:Trace:Line status="error">
                            <twig:Trace:Icon>
                                <twig:ux:icon name="lucide:terminal" />
                            </twig:Trace:Icon>
                            <twig:Trace:Title>Ran 2 commands, 1 failed</twig:Trace:Title>
                        </twig:Trace:Line>
                    </twig:Trace:Collapsible:Trigger>
                    <twig:Trace:Collapsible:Content>
                        <twig:Trace>
                            <twig:Trace:Line>
                                <twig:Trace:Icon>
                                    <twig:ux:icon name="lucide:terminal" />
                                </twig:Trace:Icon>
                                <twig:Trace:Title>Ran node --version</twig:Trace:Title>
                            </twig:Trace:Line>
                            <twig:Trace:Collapsible>
                                <twig:Trace:Collapsible:Trigger>
                                    <twig:Trace:Line status="error">
                                        <twig:Trace:Icon>
                                            <twig:ux:icon name="lucide:terminal" />
                                        </twig:Trace:Icon>
                                        <twig:Trace:Title>Failed to run pnpm --version</twig:Trace:Title>
                                    </twig:Trace:Line>
                                </twig:Trace:Collapsible:Trigger>
                                <twig:Trace:Collapsible:Content>
                                    <pre class="max-h-64 overflow-auto rounded-lg border border-destructive/30 px-2.5 py-2.5 font-mono text-xs leading-relaxed whitespace-pre-wrap wrap-break-word text-destructive dark:border-destructive/40">command not found: pnpm</pre>
                                </twig:Trace:Collapsible:Content>
                            </twig:Trace:Collapsible>
                        </twig:Trace>
                    </twig:Trace:Collapsible:Content>
                </twig:Trace:Collapsible>
            </twig:Trace>
            <p>Node is installed and pnpm is not. Reading the docs config.</p>
            <twig:Trace>
                <a href="#turn">
                    <twig:Trace:Line>
                        <twig:Trace:Icon>
                            <twig:ux:icon name="lucide:file-text" />
                        </twig:Trace:Icon>
                        <twig:Trace:Title>Read apps/v4/lib/docs.ts</twig:Trace:Title>
                        <twig:Trace:Icon>
                            <twig:ux:icon name="lucide:chevron-right" class="rtl:rotate-180" />
                        </twig:Trace:Icon>
                    </twig:Trace:Line>
                </a>
            </twig:Trace>
        </twig:Trace:Collapsible:Content>
    </twig:Trace:Collapsible>
</div>
```

### Reasoning

Render the reasoning of an assistant turn. The row is a `Trace:Line` until the first text arrives: give it `status="running"` and the title "Thinking" while it streams, then wrap it in a `Trace:Collapsible` with the reasoning as its content.

```twig {"preview":true}
<div class="flex w-full max-w-sm flex-col gap-4 py-6 text-sm">
    <twig:Message align="end">
        <twig:Message:Content>
            <twig:Bubble align="end" variant="muted">
                <twig:Bubble:Content>Why is the checkout deploy so slow?</twig:Bubble:Content>
            </twig:Bubble>
        </twig:Message:Content>
    </twig:Message>
    <twig:Message>
        <twig:Message:Content class="gap-4">
            <twig:Trace>
                <twig:Trace:Collapsible>
                    <twig:Trace:Collapsible:Trigger>
                        <twig:Trace:Line>
                            <twig:Trace:Icon>
                                <twig:ux:icon name="lucide:brain" />
                            </twig:Trace:Icon>
                            <twig:Trace:Title>Thought</twig:Trace:Title>
                        </twig:Trace:Line>
                    </twig:Trace:Collapsible:Trigger>
                    <twig:Trace:Collapsible:Content class="whitespace-pre-wrap">
                        The build log shows install and compile finish in under a minute. Image optimization takes almost four, and it reruns on every deploy because the cache key includes the commit hash.
                    </twig:Trace:Collapsible:Content>
                </twig:Trace:Collapsible>
            </twig:Trace>
            <p>Image optimization is the bottleneck. Key its cache on the lockfile and the image directory instead of the commit hash.</p>
        </twig:Message:Content>
    </twig:Message>
</div>
```

### Tool

Render the tool calls of an assistant turn. A finished call is a collapsible row with its output, a failed one has `status="error"`, and a running one is a `Trace:Line` with `status="running"`.

```twig {"preview":true,"collapseClass":true}
<div class="flex w-full max-w-sm flex-col gap-4 py-6 text-sm">
    <twig:Message align="end">
        <twig:Message:Content>
            <twig:Bubble align="end" variant="muted">
                <twig:Bubble:Content>Which package managers are installed?</twig:Bubble:Content>
            </twig:Bubble>
        </twig:Message:Content>
    </twig:Message>
    <twig:Message>
        <twig:Message:Content class="gap-4">
            <twig:Trace>
                {% for call in [
                    {command: 'node --version', output: 'v24.18.0', failed: false},
                    {command: 'npm --version', output: '11.6.2', failed: false},
                    {command: 'pnpm --version', output: 'command not found: pnpm', failed: true},
                ] %}
                    <twig:Trace:Collapsible>
                        <twig:Trace:Collapsible:Trigger>
                            <twig:Trace:Line :status="call.failed ? 'error' : null">
                                <twig:Trace:Icon>
                                    <twig:ux:icon name="lucide:terminal" />
                                </twig:Trace:Icon>
                                <twig:Trace:Title>{{ call.failed ? 'Failed to run' : 'Ran' }} {{ call.command }}</twig:Trace:Title>
                            </twig:Trace:Line>
                        </twig:Trace:Collapsible:Trigger>
                        <twig:Trace:Collapsible:Content>
                            <pre class="max-h-64 overflow-auto rounded-lg border border-input px-2.5 py-2.5 font-mono text-xs leading-relaxed whitespace-pre-wrap wrap-break-word text-foreground dark:bg-input/30">{{ call.output }}</pre>
                        </twig:Trace:Collapsible:Content>
                    </twig:Trace:Collapsible>
                {% endfor %}
            </twig:Trace>
            <p>Node 24 and npm 11 are installed. pnpm is not.</p>
        </twig:Message:Content>
    </twig:Message>
</div>
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

```twig {"preview":true}
<div class="flex w-full flex-col items-center gap-4">
    {# Arabic #}
    <twig:Trace class="max-w-sm py-6 text-sm" dir="rtl">
        <twig:Trace:Collapsible open>
            <twig:Trace:Collapsible:Trigger>
                <twig:Trace:Line>
                    <twig:Trace:Icon>
                        <twig:ux:icon name="lucide:terminal" />
                    </twig:Trace:Icon>
                    <twig:Trace:Title>تم تشغيل node --version</twig:Trace:Title>
                </twig:Trace:Line>
            </twig:Trace:Collapsible:Trigger>
            <twig:Trace:Collapsible:Content>v24.18.0</twig:Trace:Collapsible:Content>
        </twig:Trace:Collapsible>
        <twig:Trace:Line status="error">
            <twig:Trace:Icon>
                <twig:ux:icon name="lucide:circle-alert" />
            </twig:Trace:Icon>
            <twig:Trace:Title>فشل تشغيل pnpm lint</twig:Trace:Title>
        </twig:Trace:Line>
        <a href="#rtl">
            <twig:Trace:Line>
                <twig:Trace:Icon>
                    <twig:ux:icon name="lucide:file-text" />
                </twig:Trace:Icon>
                <twig:Trace:Title>تمت قراءة apps/v4/lib/docs.ts</twig:Trace:Title>
                <twig:Trace:Description>12 ثانية</twig:Trace:Description>
                <twig:Trace:Icon>
                    <twig:ux:icon name="lucide:chevron-right" class="rtl:rotate-180" />
                </twig:Trace:Icon>
            </twig:Trace:Line>
        </a>
    </twig:Trace>

    {# Hebrew #}
    <twig:Trace class="max-w-sm py-6 text-sm" dir="rtl">
        <twig:Trace:Collapsible open>
            <twig:Trace:Collapsible:Trigger>
                <twig:Trace:Line>
                    <twig:Trace:Icon>
                        <twig:ux:icon name="lucide:terminal" />
                    </twig:Trace:Icon>
                    <twig:Trace:Title>הורץ node --version</twig:Trace:Title>
                </twig:Trace:Line>
            </twig:Trace:Collapsible:Trigger>
            <twig:Trace:Collapsible:Content>v24.18.0</twig:Trace:Collapsible:Content>
        </twig:Trace:Collapsible>
        <twig:Trace:Line status="error">
            <twig:Trace:Icon>
                <twig:ux:icon name="lucide:circle-alert" />
            </twig:Trace:Icon>
            <twig:Trace:Title>הרצת pnpm lint נכשלה</twig:Trace:Title>
        </twig:Trace:Line>
        <a href="#rtl">
            <twig:Trace:Line>
                <twig:Trace:Icon>
                    <twig:ux:icon name="lucide:file-text" />
                </twig:Trace:Icon>
                <twig:Trace:Title>נקרא apps/v4/lib/docs.ts</twig:Trace:Title>
                <twig:Trace:Description>12 שניות</twig:Trace:Description>
                <twig:Trace:Icon>
                    <twig:ux:icon name="lucide:chevron-right" class="rtl:rotate-180" />
                </twig:Trace:Icon>
            </twig:Trace:Line>
        </a>
    </twig:Trace>
</div>
```

## Accessibility

- `Trace` adds no roles of its own. Each line gets its semantics from the element that wraps it.
- A `Trace:Line` on its own is a `span`. It is read as text and is not a tab stop.
- Wrap it in a `Trace:Collapsible:Trigger` and it becomes a button with `aria-expanded`. Wrap it in a link, or in the trigger for a dialog or sheet, and it takes the keyboard and focus behavior of that element.
- `Trace:Icon` is hidden from assistive tech, so `Trace:Title` is the accessible name. Write it as a sentence that says what happened, like "Edited trace.html.twig", not just "trace.html.twig".
- When a link opens a new tab, add "(opens in a new tab)" to the title with `sr-only`.
- Status is shown with color and motion, so put it in the title too. Write "Running pnpm build" while it runs and "Failed to run pnpm build" when it errors.
- The shimmer on a running title stops when the reader prefers reduced motion. The title still says what is happening.
- `Trace:Collapsible:Content` mutes its text. If you put a block with a muted background inside it, give the block `text-foreground` so it keeps its contrast.

## API Reference

::: api-reference
