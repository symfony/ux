# AppSidebar

Collapsible nested sidebars.

```twig {"preview":true}
<twig:Sidebar:Provider style="--sidebar-width: 350px; --sidebar-width-icon: 3rem; --sidebar-width-mobile: 18rem;">
    <twig:AppSidebar />
    <twig:Sidebar:Inset>
        <header class="sticky top-0 flex shrink-0 items-center gap-2 border-b bg-background p-4">
            <twig:Sidebar:Trigger class="-ms-1">
                <twig:ux:icon name="lucide:panel-left" />
            </twig:Sidebar:Trigger>
            <twig:Separator
                orientation="vertical"
                class="me-2 data-vertical:h-4 data-vertical:self-auto"
            />
            <twig:Breadcrumb>
                <twig:Breadcrumb:List>
                    <twig:Breadcrumb:Item class="hidden md:block">
                        <twig:Breadcrumb:Link href="#">All Inboxes</twig:Breadcrumb:Link>
                    </twig:Breadcrumb:Item>
                    <twig:Breadcrumb:Separator class="hidden md:block" />
                    <twig:Breadcrumb:Item>
                        <twig:Breadcrumb:Page>Inbox</twig:Breadcrumb:Page>
                    </twig:Breadcrumb:Item>
                </twig:Breadcrumb:List>
            </twig:Breadcrumb>
        </header>
        <div class="flex flex-1 flex-col gap-4 p-4">
            {%- for index in 1..24 %}
                <div class="aspect-video h-12 w-full rounded-lg bg-muted/50"></div>
            {%- endfor %}
        </div>
    </twig:Sidebar:Inset>
</twig:Sidebar:Provider>
```

## Installation

::: installation
