# Sidebar

A collapsible sidebar layout with a header, body and footer.

```twig {"preview":true,"collapseClass":true}
<twig:Sidebar:Provider id="sidebar-demo" defaultOpen cookieName="" class="min-h-[640px] w-full">
    <twig:Sidebar collapsible="icon">
        <twig:Sidebar:Header>
            <twig:Sidebar:Menu>
                <twig:Sidebar:MenuItem>
                    <twig:DropdownMenu id="sidebar-team" class="w-full block">
                        <twig:DropdownMenu:Trigger>
                            <twig:Sidebar:MenuButton {{ ...dropdown_menu_trigger_attrs }} size="lg" tooltip="Acme Inc">
                                <div class="flex aspect-square size-8 items-center justify-center rounded-lg bg-sidebar-primary text-sidebar-primary-foreground">
                                    <twig:ux:icon name="lucide:command" class="size-4" aria-hidden="true" />
                                </div>
                                <div class="grid flex-1 text-left text-sm leading-tight group-data-[collapsible=icon]:hidden">
                                    <span class="truncate font-semibold">Acme Inc</span>
                                    <span class="truncate text-xs">Enterprise</span>
                                </div>
                                <twig:ux:icon name="lucide:chevrons-up-down" class="ms-auto size-4 group-data-[collapsible=icon]:hidden" aria-hidden="true" />
                            </twig:Sidebar:MenuButton>
                        </twig:DropdownMenu:Trigger>
                        <twig:DropdownMenu:Content class="w-56">
                            <twig:DropdownMenu:Label>Teams</twig:DropdownMenu:Label>
                            <twig:DropdownMenu:Item>Acme Inc</twig:DropdownMenu:Item>
                            <twig:DropdownMenu:Item>Acme Corp.</twig:DropdownMenu:Item>
                            <twig:DropdownMenu:Item>Evil Corp.</twig:DropdownMenu:Item>
                            <twig:DropdownMenu:Separator />
                            <twig:DropdownMenu:Item>Add team</twig:DropdownMenu:Item>
                        </twig:DropdownMenu:Content>
                    </twig:DropdownMenu>
                </twig:Sidebar:MenuItem>
            </twig:Sidebar:Menu>
        </twig:Sidebar:Header>
        <twig:Sidebar:Content>
            <twig:Sidebar:Group>
                <twig:Sidebar:GroupLabel>Platform</twig:Sidebar:GroupLabel>
                <twig:Sidebar:GroupContent>
                    <twig:Sidebar:Menu>
                        <twig:Sidebar:MenuItem>
                            <twig:Collapsible open class="group/collapsible">
                                <twig:Collapsible:Trigger>
                                    <twig:Sidebar:MenuButton {{ ...collapsible_trigger_attrs }} tooltip="Playground" active>
                                        <twig:ux:icon name="lucide:square-terminal" aria-hidden="true" />
                                        <span>Playground</span>
                                        <twig:ux:icon name="lucide:chevron-right" class="ms-auto transition-transform group-data-[state=open]/collapsible:rotate-90" aria-hidden="true" />
                                    </twig:Sidebar:MenuButton>
                                </twig:Collapsible:Trigger>
                                <twig:Collapsible:Content>
                                    <twig:Sidebar:MenuSub>
                                        <twig:Sidebar:MenuSubItem>
                                            <twig:Sidebar:MenuSubButton href="#"><span>History</span></twig:Sidebar:MenuSubButton>
                                        </twig:Sidebar:MenuSubItem>
                                        <twig:Sidebar:MenuSubItem>
                                            <twig:Sidebar:MenuSubButton href="#"><span>Starred</span></twig:Sidebar:MenuSubButton>
                                        </twig:Sidebar:MenuSubItem>
                                        <twig:Sidebar:MenuSubItem>
                                            <twig:Sidebar:MenuSubButton href="#"><span>Settings</span></twig:Sidebar:MenuSubButton>
                                        </twig:Sidebar:MenuSubItem>
                                    </twig:Sidebar:MenuSub>
                                </twig:Collapsible:Content>
                            </twig:Collapsible>
                        </twig:Sidebar:MenuItem>
                        <twig:Sidebar:MenuItem>
                            <twig:Sidebar:MenuButton as="a" href="#" tooltip="Models">
                                <twig:ux:icon name="lucide:bot" aria-hidden="true" />
                                <span>Models</span>
                            </twig:Sidebar:MenuButton>
                        </twig:Sidebar:MenuItem>
                        <twig:Sidebar:MenuItem>
                            <twig:Sidebar:MenuButton as="a" href="#" tooltip="Documentation">
                                <twig:ux:icon name="lucide:book-open" aria-hidden="true" />
                                <span>Documentation</span>
                            </twig:Sidebar:MenuButton>
                        </twig:Sidebar:MenuItem>
                        <twig:Sidebar:MenuItem>
                            <twig:Sidebar:MenuButton as="a" href="#" tooltip="Settings">
                                <twig:ux:icon name="lucide:settings-2" aria-hidden="true" />
                                <span>Settings</span>
                            </twig:Sidebar:MenuButton>
                        </twig:Sidebar:MenuItem>
                    </twig:Sidebar:Menu>
                </twig:Sidebar:GroupContent>
            </twig:Sidebar:Group>
            <twig:Sidebar:Group class="group-data-[collapsible=icon]:hidden">
                <twig:Sidebar:GroupLabel>Projects</twig:Sidebar:GroupLabel>
                <twig:Sidebar:GroupContent>
                    <twig:Sidebar:Menu>
                        <twig:Sidebar:MenuItem>
                            <twig:Sidebar:MenuButton as="a" href="#">
                                <twig:ux:icon name="lucide:frame" aria-hidden="true" />
                                <span>Design Engineering</span>
                            </twig:Sidebar:MenuButton>
                            <twig:DropdownMenu id="sidebar-project-1" side="right" align="start" class="absolute! right-1 top-1.5">
                                <twig:DropdownMenu:Trigger>
                                    <twig:Sidebar:MenuAction class="static!" {{ ...dropdown_menu_trigger_attrs }}>
                                        <twig:ux:icon name="lucide:more-horizontal" class="size-4" aria-hidden="true" />
                                        <span class="sr-only">More</span>
                                    </twig:Sidebar:MenuAction>
                                </twig:DropdownMenu:Trigger>
                                <twig:DropdownMenu:Content class="w-40">
                                    <twig:DropdownMenu:Item>View project</twig:DropdownMenu:Item>
                                    <twig:DropdownMenu:Item>Share project</twig:DropdownMenu:Item>
                                    <twig:DropdownMenu:Separator />
                                    <twig:DropdownMenu:Item>Delete project</twig:DropdownMenu:Item>
                                </twig:DropdownMenu:Content>
                            </twig:DropdownMenu>
                        </twig:Sidebar:MenuItem>
                        <twig:Sidebar:MenuItem>
                            <twig:Sidebar:MenuButton as="a" href="#">
                                <twig:ux:icon name="lucide:pie-chart" aria-hidden="true" />
                                <span>Sales &amp; Marketing</span>
                            </twig:Sidebar:MenuButton>
                            <twig:DropdownMenu id="sidebar-project-2" side="right" align="start" class="absolute! right-1 top-1.5">
                                <twig:DropdownMenu:Trigger>
                                    <twig:Sidebar:MenuAction class="static!" {{ ...dropdown_menu_trigger_attrs }}>
                                        <twig:ux:icon name="lucide:more-horizontal" class="size-4" aria-hidden="true" />
                                        <span class="sr-only">More</span>
                                    </twig:Sidebar:MenuAction>
                                </twig:DropdownMenu:Trigger>
                                <twig:DropdownMenu:Content class="w-40">
                                    <twig:DropdownMenu:Item>View project</twig:DropdownMenu:Item>
                                    <twig:DropdownMenu:Item>Share project</twig:DropdownMenu:Item>
                                    <twig:DropdownMenu:Separator />
                                    <twig:DropdownMenu:Item>Delete project</twig:DropdownMenu:Item>
                                </twig:DropdownMenu:Content>
                            </twig:DropdownMenu>
                        </twig:Sidebar:MenuItem>
                        <twig:Sidebar:MenuItem>
                            <twig:Sidebar:MenuButton as="a" href="#">
                                <twig:ux:icon name="lucide:map" aria-hidden="true" />
                                <span>Travel</span>
                            </twig:Sidebar:MenuButton>
                        </twig:Sidebar:MenuItem>
                    </twig:Sidebar:Menu>
                </twig:Sidebar:GroupContent>
            </twig:Sidebar:Group>
        </twig:Sidebar:Content>
        <twig:Sidebar:Footer>
            <twig:Sidebar:Menu>
                <twig:Sidebar:MenuItem>
                    <twig:DropdownMenu id="sidebar-account" side="top" class="w-full block">
                        <twig:DropdownMenu:Trigger>
                            <twig:Sidebar:MenuButton {{ ...dropdown_menu_trigger_attrs }} size="lg" tooltip="shadcn">
                                <div class="flex aspect-square size-8 items-center justify-center rounded-lg bg-sidebar-primary text-sidebar-primary-foreground">
                                    <twig:ux:icon name="lucide:user" class="size-4" aria-hidden="true" />
                                </div>
                                <div class="grid flex-1 text-left text-sm leading-tight group-data-[collapsible=icon]:hidden">
                                    <span class="truncate font-semibold">shadcn</span>
                                    <span class="truncate text-xs">m@example.com</span>
                                </div>
                                <twig:ux:icon name="lucide:chevrons-up-down" class="ms-auto size-4 group-data-[collapsible=icon]:hidden" aria-hidden="true" />
                            </twig:Sidebar:MenuButton>
                        </twig:DropdownMenu:Trigger>
                        <twig:DropdownMenu:Content class="w-56">
                            <twig:DropdownMenu:Label>My Account</twig:DropdownMenu:Label>
                            <twig:DropdownMenu:Item>Upgrade to Pro</twig:DropdownMenu:Item>
                            <twig:DropdownMenu:Separator />
                            <twig:DropdownMenu:Item>Account</twig:DropdownMenu:Item>
                            <twig:DropdownMenu:Item>Billing</twig:DropdownMenu:Item>
                            <twig:DropdownMenu:Item>Notifications</twig:DropdownMenu:Item>
                            <twig:DropdownMenu:Separator />
                            <twig:DropdownMenu:Item>Log out</twig:DropdownMenu:Item>
                        </twig:DropdownMenu:Content>
                    </twig:DropdownMenu>
                </twig:Sidebar:MenuItem>
            </twig:Sidebar:Menu>
        </twig:Sidebar:Footer>
        <twig:Sidebar:Rail />
    </twig:Sidebar>
    <twig:Sidebar:Inset>
        <header class="flex h-14 shrink-0 items-center gap-2 border-b px-4">
            <twig:Sidebar:Trigger class="-ml-1">
                <twig:ux:icon name="lucide:panel-left" aria-hidden="true" />
            </twig:Sidebar:Trigger>
            <div class="mx-1 h-4 w-px bg-border"></div>
            <span class="text-sm text-muted-foreground">Building Your Application &nbsp;/&nbsp; Data Fetching</span>
        </header>
        <div class="flex flex-1 flex-col gap-4 p-4">
            <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                <div class="aspect-video rounded-xl bg-muted/50"></div>
                <div class="aspect-video rounded-xl bg-muted/50"></div>
                <div class="aspect-video rounded-xl bg-muted/50"></div>
            </div>
            <div class="min-h-[200px] flex-1 rounded-xl bg-muted/50"></div>
        </div>
    </twig:Sidebar:Inset>
</twig:Sidebar:Provider>
```

## Installation

::: installation

## Usage

Wrap your layout in a `Sidebar:Provider`, place the `Sidebar` itself next to a `Sidebar:Inset` holding the page content, and add a `Sidebar:Trigger` wherever you want to toggle it.

```twig
<twig:Sidebar:Provider id="my-sidebar">
    <twig:Sidebar>
        <twig:Sidebar:Header>
            <span class="px-2 font-semibold">My App</span>
        </twig:Sidebar:Header>
        <twig:Sidebar:Content>
            <twig:Sidebar:Group>
                <twig:Sidebar:GroupLabel>Application</twig:Sidebar:GroupLabel>
                <twig:Sidebar:GroupContent>
                    <twig:Sidebar:Menu>
                        <twig:Sidebar:MenuItem>
                            <twig:Sidebar:MenuButton as="a" href="/dashboard" active>
                                <twig:ux:icon name="lucide:layout-dashboard" aria-hidden="true" />
                                <span>Dashboard</span>
                            </twig:Sidebar:MenuButton>
                        </twig:Sidebar:MenuItem>
                        <twig:Sidebar:MenuItem>
                            <twig:Sidebar:MenuButton as="a" href="/projects">
                                <twig:ux:icon name="lucide:folder" aria-hidden="true" />
                                <span>Projects</span>
                            </twig:Sidebar:MenuButton>
                        </twig:Sidebar:MenuItem>
                    </twig:Sidebar:Menu>
                </twig:Sidebar:GroupContent>
            </twig:Sidebar:Group>
        </twig:Sidebar:Content>
        <twig:Sidebar:Rail />
    </twig:Sidebar>
    <twig:Sidebar:Inset>
        <header class="flex h-14 items-center gap-2 border-b px-4">
            <twig:Sidebar:Trigger>
                <twig:ux:icon name="lucide:panel-left" aria-hidden="true" />
            </twig:Sidebar:Trigger>
        </header>
        <main class="flex-1 p-4">
            {# your page content #}
        </main>
    </twig:Sidebar:Inset>
</twig:Sidebar:Provider>
```

### Using the sidebar as a page layout

`Sidebar:Inset` wraps the page content, so you will typically want it in your base layout. Blocks defined inside a component tag belong to that component (and `content` is already the name of its default block), so a child template cannot override them through `{% extends %}`. Wrap the sidebar in a small layout component instead: capture its `content` block in a variable, render it inside `Sidebar:Inset`, and let your pages fill it:

```twig
{# templates/components/AppLayout.html.twig #}
{% set page_content %}{% block content %}{% endblock %}{% endset %}
<twig:Sidebar:Provider>
    <twig:Sidebar>
        {# ... #}
    </twig:Sidebar>
    <twig:Sidebar:Inset>
        <header class="flex h-14 items-center gap-2 border-b px-4">
            <twig:Sidebar:Trigger>
                <twig:ux:icon name="lucide:panel-left" aria-hidden="true" />
            </twig:Sidebar:Trigger>
        </header>
        {{ page_content|raw }}
    </twig:Sidebar:Inset>
</twig:Sidebar:Provider>
```

```twig
{# templates/dashboard.html.twig #}
{% extends 'base.html.twig' %}

{% block body %}
    <twig:AppLayout>
        <h1>Dashboard</h1>
    </twig:AppLayout>
{% endblock %}
```

## Examples

### Menu badges and actions

Use `Sidebar:MenuBadge` for counters and `Sidebar:MenuAction` for a secondary action on a menu item; render menu items as links with `as="a"` on `Sidebar:MenuButton`.

```twig {"preview":true}
<twig:Sidebar:Provider id="sidebar-menu-example" cookieName="" class="min-h-[320px] w-full">
    <twig:Sidebar collapsible="none" class="rounded-lg border border-sidebar-border">
        <twig:Sidebar:Content>
            <twig:Sidebar:Group>
                <twig:Sidebar:GroupLabel>Mailbox</twig:Sidebar:GroupLabel>
                <twig:Sidebar:GroupContent>
                    <twig:Sidebar:Menu>
                        <twig:Sidebar:MenuItem>
                            <twig:Sidebar:MenuButton as="a" href="#" active>
                                <twig:ux:icon name="lucide:inbox" aria-hidden="true" />
                                <span>Inbox</span>
                            </twig:Sidebar:MenuButton>
                            <twig:Sidebar:MenuBadge>24</twig:Sidebar:MenuBadge>
                        </twig:Sidebar:MenuItem>
                        <twig:Sidebar:MenuItem>
                            <twig:Sidebar:MenuButton as="a" href="#">
                                <twig:ux:icon name="lucide:star" aria-hidden="true" />
                                <span>Starred</span>
                            </twig:Sidebar:MenuButton>
                            <twig:Sidebar:MenuAction showOnHover>
                                <twig:ux:icon name="lucide:plus" aria-hidden="true" />
                                <span class="sr-only">Add</span>
                            </twig:Sidebar:MenuAction>
                        </twig:Sidebar:MenuItem>
                        <twig:Sidebar:MenuItem>
                            <twig:Sidebar:MenuButton as="a" href="#">
                                <twig:ux:icon name="lucide:mail" aria-hidden="true" />
                                <span>Drafts</span>
                            </twig:Sidebar:MenuButton>
                            <twig:Sidebar:MenuBadge>3</twig:Sidebar:MenuBadge>
                        </twig:Sidebar:MenuItem>
                    </twig:Sidebar:Menu>
                </twig:Sidebar:GroupContent>
            </twig:Sidebar:Group>
        </twig:Sidebar:Content>
    </twig:Sidebar>
</twig:Sidebar:Provider>
```

### Off-canvas collapsing

Use `collapsible="offcanvas"` to fully hide the sidebar when collapsed. The `Sidebar:Trigger` automatically targets the surrounding `Sidebar:Provider`.

```twig {"preview":true}
<twig:Sidebar:Provider id="sidebar-offcanvas-example" cookieName="" class="min-h-[320px] w-full">
    <twig:Sidebar collapsible="offcanvas">
        <twig:Sidebar:Header>
            <span class="px-2 font-semibold">My App</span>
        </twig:Sidebar:Header>
        <twig:Sidebar:Content>
            <twig:Sidebar:Group>
                <twig:Sidebar:GroupContent>
                    <twig:Sidebar:Menu>
                        <twig:Sidebar:MenuItem>
                            <twig:Sidebar:MenuButton as="a" href="#" active>
                                <twig:ux:icon name="lucide:inbox" aria-hidden="true" />
                                <span>Inbox</span>
                            </twig:Sidebar:MenuButton>
                        </twig:Sidebar:MenuItem>
                        <twig:Sidebar:MenuItem>
                            <twig:Sidebar:MenuButton as="a" href="#">
                                <twig:ux:icon name="lucide:search" aria-hidden="true" />
                                <span>Search</span>
                            </twig:Sidebar:MenuButton>
                        </twig:Sidebar:MenuItem>
                    </twig:Sidebar:Menu>
                </twig:Sidebar:GroupContent>
            </twig:Sidebar:Group>
        </twig:Sidebar:Content>
        <twig:Sidebar:Rail />
    </twig:Sidebar>
    <twig:Sidebar:Inset>
        <header class="flex h-14 items-center gap-2 border-b px-4">
            <twig:Sidebar:Trigger>
                <twig:ux:icon name="lucide:panel-left" aria-hidden="true" />
            </twig:Sidebar:Trigger>
        </header>
    </twig:Sidebar:Inset>
</twig:Sidebar:Provider>
```

## API Reference

::: api-reference
