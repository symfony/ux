# SidebarLeft

A left and right sidebar.

```twig {"preview":true}
<twig:Sidebar:Provider>
    <twig:SidebarLeft />
    <twig:Sidebar:Inset>
        <header class="sticky top-0 flex h-14 shrink-0 items-center gap-2 bg-background">
            <div class="flex flex-1 items-center gap-2 px-3">
                <twig:Sidebar:Trigger>
                    <twig:ux:icon name="lucide:panel-left" />
                </twig:Sidebar:Trigger>
                <twig:Separator
                    orientation="vertical"
                    class="me-2 data-vertical:h-4 data-vertical:self-auto"
                />
                <twig:Breadcrumb>
                    <twig:Breadcrumb:List>
                        <twig:Breadcrumb:Item>
                            <twig:Breadcrumb:Page class="line-clamp-1">
                                Project Management &amp; Task Tracking
                            </twig:Breadcrumb:Page>
                        </twig:Breadcrumb:Item>
                    </twig:Breadcrumb:List>
                </twig:Breadcrumb>
            </div>
        </header>
        <div class="flex flex-1 flex-col gap-4 p-4">
            <div class="mx-auto h-24 w-full max-w-3xl rounded-xl bg-muted/50"></div>
            <div class="mx-auto h-96 w-full max-w-3xl rounded-xl bg-muted/50"></div>
        </div>
    </twig:Sidebar:Inset>
    <twig:SidebarRight />
</twig:Sidebar:Provider>
```

## Installation

::: installation
