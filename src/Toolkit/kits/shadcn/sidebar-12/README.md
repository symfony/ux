# AppSidebar

A sidebar with a calendar.

```twig {"preview":true}
<twig:Sidebar:Provider>
    <twig:AppSidebar />
    <twig:Sidebar:Inset>
        <header class="sticky top-0 flex h-16 shrink-0 items-center gap-2 border-b bg-background px-4">
            <twig:Sidebar:Trigger class="-ms-1">
                <twig:ux:icon name="lucide:panel-left" />
            </twig:Sidebar:Trigger>
            <twig:Separator
                orientation="vertical"
                class="me-2 data-vertical:h-4 data-vertical:self-auto"
            />
            <twig:Breadcrumb>
                <twig:Breadcrumb:List>
                    <twig:Breadcrumb:Item>
                        <twig:Breadcrumb:Page>October 2024</twig:Breadcrumb:Page>
                    </twig:Breadcrumb:Item>
                </twig:Breadcrumb:List>
            </twig:Breadcrumb>
        </header>
        <div class="flex flex-1 flex-col gap-4 p-4">
            <div class="grid auto-rows-min gap-4 md:grid-cols-5">
                {%- for i in 1..20 %}
                    <div class="aspect-square rounded-xl bg-muted/50"></div>
                {%- endfor %}
            </div>
        </div>
    </twig:Sidebar:Inset>
</twig:Sidebar:Provider>
```

## Installation

::: installation
