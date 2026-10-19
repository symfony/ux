# AppSidebar

A sidebar with a sticky site header.

```twig {"preview":true}
<div class="[--header-height:calc(--spacing(14))]">
    <twig:Sidebar:Provider class="flex flex-col">
        <twig:SiteHeader />
        <div class="flex flex-1">
            <twig:AppSidebar />
            <twig:Sidebar:Inset>
                <div class="flex flex-1 flex-col gap-4 p-4">
                    <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                        <div class="aspect-video rounded-xl bg-muted/50"></div>
                        <div class="aspect-video rounded-xl bg-muted/50"></div>
                        <div class="aspect-video rounded-xl bg-muted/50"></div>
                    </div>
                    <div class="min-h-96 flex-1 rounded-xl bg-muted/50"></div>
                </div>
            </twig:Sidebar:Inset>
        </div>
    </twig:Sidebar:Provider>
</div>
```

## Installation

::: installation
