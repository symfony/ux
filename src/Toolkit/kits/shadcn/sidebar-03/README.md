# AppSidebar

A sidebar with submenus.

```twig {"preview":true}
<twig:Sidebar:Provider>
    <twig:AppSidebar />
    <twig:Sidebar:Inset>
        <header class="flex h-16 shrink-0 items-center gap-2 border-b">
            <div class="flex items-center gap-2 px-3">
                <twig:Sidebar:Trigger>
                    <twig:ux:icon name="lucide:panel-left" />
                </twig:Sidebar:Trigger>
                <twig:Separator
                    orientation="vertical"
                    class="me-2 data-vertical:h-4 data-vertical:self-auto"
                />
                <twig:Breadcrumb>
                    <twig:Breadcrumb:List>
                        <twig:Breadcrumb:Item class="hidden md:block">
                            <twig:Breadcrumb:Link href="#">
                                Build Your Application
                            </twig:Breadcrumb:Link>
                        </twig:Breadcrumb:Item>
                        <twig:Breadcrumb:Separator class="hidden md:block" />
                        <twig:Breadcrumb:Item>
                            <twig:Breadcrumb:Page>Data Fetching</twig:Breadcrumb:Page>
                        </twig:Breadcrumb:Item>
                    </twig:Breadcrumb:List>
                </twig:Breadcrumb>
            </div>
        </header>
        <div class="flex flex-1 flex-col gap-4 p-4">
            <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                <div class="aspect-video rounded-xl bg-muted/50"></div>
                <div class="aspect-video rounded-xl bg-muted/50"></div>
                <div class="aspect-video rounded-xl bg-muted/50"></div>
            </div>
            <div class="min-h-96 flex-1 rounded-xl bg-muted/50"></div>
        </div>
    </twig:Sidebar:Inset>
</twig:Sidebar:Provider>
```

## Installation

::: installation
