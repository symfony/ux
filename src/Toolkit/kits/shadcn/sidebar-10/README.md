# AppSidebar

A sidebar in a popover.

```twig {"preview":true}
<twig:Sidebar:Provider>
    <twig:AppSidebar />
    <twig:Sidebar:Inset>
        <header class="flex h-14 shrink-0 items-center gap-2">
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
            <div class="ms-auto px-3">
                <twig:NavActions />
            </div>
        </header>
        <div class="flex flex-1 flex-col gap-4 px-4 py-10">
            <div class="mx-auto h-24 w-full max-w-3xl rounded-xl bg-muted/50"></div>
            <div class="mx-auto min-h-80 w-full max-w-3xl flex-1 rounded-xl bg-muted/50"></div>
        </div>
    </twig:Sidebar:Inset>
</twig:Sidebar:Provider>
```

## Installation

::: installation
