# Collapsible

An interactive component which expands/collapses a panel.

```twig {"preview":true,"height":"300px"}
<twig:Collapsible class="flex w-[350px] flex-col gap-2 self-start">
    <div class="flex items-center justify-between gap-4 px-4">
        <h4 class="text-sm font-semibold">Order #4189</h4>
        <twig:Collapsible:Trigger>
            <twig:Button variant="ghost" size="icon" class="size-8" {{ ...collapsible_trigger_attrs }}>
                <twig:ux:icon name="lucide:chevrons-up-down" class="size-4" />
                <span class="sr-only">Toggle details</span>
            </twig:Button>
        </twig:Collapsible:Trigger>
    </div>
    <div class="flex items-center justify-between rounded-md border px-4 py-2 text-sm">
        <span class="text-muted-foreground">Status</span>
        <span class="font-medium">Shipped</span>
    </div>
    <twig:Collapsible:Content class="flex flex-col gap-2">
        <div class="rounded-md border px-4 py-2 text-sm">
            <p class="font-medium">Shipping address</p>
            <p class="text-muted-foreground">100 Market St, San Francisco</p>
        </div>
        <div class="rounded-md border px-4 py-2 text-sm">
            <p class="font-medium">Items</p>
            <p class="text-muted-foreground">2x Studio Headphones</p>
        </div>
    </twig:Collapsible:Content>
</twig:Collapsible>
```

## Installation

::: installation

## Usage

```twig
<twig:Collapsible>
    <twig:Collapsible:Trigger>
        <twig:Button variant="ghost" {{ ...collapsible_trigger_attrs }}>Can I use this in my project?</twig:Button>
    </twig:Collapsible:Trigger>
    <twig:Collapsible:Content>
        Yes. Free to use for personal and commercial projects. No attribution required.
    </twig:Collapsible:Content>
</twig:Collapsible>
```

## Examples

### Basic

```twig {"preview":true,"height":"300px"}
<twig:Card class="mx-auto w-full max-w-sm self-start">
    <twig:Card:Content>
        <twig:Collapsible class="rounded-md data-[state=open]:bg-muted">
            <twig:Collapsible:Trigger>
                <twig:Button variant="ghost" class="group w-full" {{ ...collapsible_trigger_attrs }}>
                    Product details
                    <twig:ux:icon name="lucide:chevron-down" class="ml-auto transition-transform group-data-[state=open]:rotate-180" />
                </twig:Button>
            </twig:Collapsible:Trigger>
            <twig:Collapsible:Content class="flex flex-col items-start gap-2 p-2.5 pt-0 text-sm">
                <div>
                    This panel can be expanded or collapsed to reveal additional content.
                </div>
                <twig:Button size="xs">Learn More</twig:Button>
            </twig:Collapsible:Content>
        </twig:Collapsible>
    </twig:Card:Content>
</twig:Card>
```

### Settings Panel

Use a trigger button to reveal additional settings.

```twig {"preview":true,"height":"300px"}
<twig:Card class="mx-auto w-full max-w-xs self-start" size="sm">
    <twig:Card:Header>
        <twig:Card:Title>Radius</twig:Card:Title>
        <twig:Card:Description>Set the corner radius of the element.</twig:Card:Description>
    </twig:Card:Header>
    <twig:Card:Content>
        <twig:Collapsible class="flex items-start gap-2">
            <div class="grid w-full grid-cols-2 gap-2">
                <twig:Field>
                    <twig:Field:Label for="radius-1" class="sr-only">Radius X</twig:Field:Label>
                    <twig:Input id="radius-1" placeholder="0" value="0" />
                </twig:Field>
                <twig:Field>
                    <twig:Field:Label for="radius-2" class="sr-only">Radius Y</twig:Field:Label>
                    <twig:Input id="radius-2" placeholder="0" value="0" />
                </twig:Field>
                <twig:Collapsible:Content class="col-span-full grid grid-cols-subgrid gap-2">
                    <twig:Field>
                        <twig:Field:Label for="radius-3" class="sr-only">Radius X</twig:Field:Label>
                        <twig:Input id="radius-3" placeholder="0" value="0" />
                    </twig:Field>
                    <twig:Field>
                        <twig:Field:Label for="radius-4" class="sr-only">Radius Y</twig:Field:Label>
                        <twig:Input id="radius-4" placeholder="0" value="0" />
                    </twig:Field>
                </twig:Collapsible:Content>
            </div>
            <twig:Collapsible:Trigger>
                <twig:Button variant="outline" size="icon" class="group" {{ ...collapsible_trigger_attrs }}>
                    <twig:ux:icon name="lucide:maximize" class="size-4 group-data-[state=open]:hidden" />
                    <twig:ux:icon name="lucide:minimize" class="size-4 hidden group-data-[state=open]:block" />
                </twig:Button>
            </twig:Collapsible:Trigger>
        </twig:Collapsible>
    </twig:Card:Content>
</twig:Card>
```

### File Tree

Use nested collapsibles to build a file tree.

```twig {"preview":true,"height":"600px"}
<twig:Card class="mx-auto w-full max-w-[16rem] gap-2 self-start" size="sm">
    <twig:Card:Header>
        <twig:Tabs defaultValue="explorer">
            <twig:Tabs:List class="w-full">
                <twig:Tabs:Trigger value="explorer">Explorer</twig:Tabs:Trigger>
                <twig:Tabs:Trigger value="outline">Outline</twig:Tabs:Trigger>
            </twig:Tabs:List>
        </twig:Tabs>
    </twig:Card:Header>
    <twig:Card:Content>
        <div class="flex flex-col gap-1">
            {# Folder: components #}
            <twig:Collapsible>
                <twig:Collapsible:Trigger>
                    <twig:Button variant="ghost" size="sm" class="group w-full justify-start transition-none hover:bg-accent hover:text-accent-foreground" {{ ...collapsible_trigger_attrs }}>
                        <twig:ux:icon name="lucide:chevron-right" class="transition-transform group-data-[state=open]:rotate-90" />
                        <twig:ux:icon name="lucide:folder" />
                        components
                    </twig:Button>
                </twig:Collapsible:Trigger>
                <twig:Collapsible:Content class="mt-1 ml-5">
                    <div class="flex flex-col gap-1">
                        {# Nested folder: components/ui #}
                        <twig:Collapsible>
                            <twig:Collapsible:Trigger>
                                <twig:Button variant="ghost" size="sm" class="group w-full justify-start transition-none hover:bg-accent hover:text-accent-foreground" {{ ...collapsible_trigger_attrs }}>
                                    <twig:ux:icon name="lucide:chevron-right" class="transition-transform group-data-[state=open]:rotate-90" />
                                    <twig:ux:icon name="lucide:folder" />
                                    ui
                                </twig:Button>
                            </twig:Collapsible:Trigger>
                            <twig:Collapsible:Content class="mt-1 ml-5">
                                <div class="flex flex-col gap-1">
                                    {% for file in ['button.tsx', 'card.tsx', 'dialog.tsx', 'input.tsx', 'select.tsx', 'table.tsx'] %}
                                        <twig:Button variant="link" size="sm" class="w-full justify-start gap-2 text-foreground">
                                            <twig:ux:icon name="lucide:file" />
                                            <span>{{ file }}</span>
                                        </twig:Button>
                                    {% endfor %}
                                </div>
                            </twig:Collapsible:Content>
                        </twig:Collapsible>
                        {% for file in ['login-form.tsx', 'register-form.tsx'] %}
                            <twig:Button variant="link" size="sm" class="w-full justify-start gap-2 text-foreground">
                                <twig:ux:icon name="lucide:file" />
                                <span>{{ file }}</span>
                            </twig:Button>
                        {% endfor %}
                    </div>
                </twig:Collapsible:Content>
            </twig:Collapsible>

            {# Folder: lib #}
            <twig:Collapsible>
                <twig:Collapsible:Trigger>
                    <twig:Button variant="ghost" size="sm" class="group w-full justify-start transition-none hover:bg-accent hover:text-accent-foreground" {{ ...collapsible_trigger_attrs }}>
                        <twig:ux:icon name="lucide:chevron-right" class="transition-transform group-data-[state=open]:rotate-90" />
                        <twig:ux:icon name="lucide:folder" />
                        lib
                    </twig:Button>
                </twig:Collapsible:Trigger>
                <twig:Collapsible:Content class="mt-1 ml-5">
                    <div class="flex flex-col gap-1">
                        {% for file in ['utils.ts', 'cn.ts', 'api.ts'] %}
                            <twig:Button variant="link" size="sm" class="w-full justify-start gap-2 text-foreground">
                                <twig:ux:icon name="lucide:file" />
                                <span>{{ file }}</span>
                            </twig:Button>
                        {% endfor %}
                    </div>
                </twig:Collapsible:Content>
            </twig:Collapsible>

            {# Folder: hooks #}
            <twig:Collapsible>
                <twig:Collapsible:Trigger>
                    <twig:Button variant="ghost" size="sm" class="group w-full justify-start transition-none hover:bg-accent hover:text-accent-foreground" {{ ...collapsible_trigger_attrs }}>
                        <twig:ux:icon name="lucide:chevron-right" class="transition-transform group-data-[state=open]:rotate-90" />
                        <twig:ux:icon name="lucide:folder" />
                        hooks
                    </twig:Button>
                </twig:Collapsible:Trigger>
                <twig:Collapsible:Content class="mt-1 ml-5">
                    <div class="flex flex-col gap-1">
                        {% for file in ['use-media-query.ts', 'use-debounce.ts', 'use-local-storage.ts'] %}
                            <twig:Button variant="link" size="sm" class="w-full justify-start gap-2 text-foreground">
                                <twig:ux:icon name="lucide:file" />
                                <span>{{ file }}</span>
                            </twig:Button>
                        {% endfor %}
                    </div>
                </twig:Collapsible:Content>
            </twig:Collapsible>

            {# Folder: types #}
            <twig:Collapsible>
                <twig:Collapsible:Trigger>
                    <twig:Button variant="ghost" size="sm" class="group w-full justify-start transition-none hover:bg-accent hover:text-accent-foreground" {{ ...collapsible_trigger_attrs }}>
                        <twig:ux:icon name="lucide:chevron-right" class="transition-transform group-data-[state=open]:rotate-90" />
                        <twig:ux:icon name="lucide:folder" />
                        types
                    </twig:Button>
                </twig:Collapsible:Trigger>
                <twig:Collapsible:Content class="mt-1 ml-5">
                    <div class="flex flex-col gap-1">
                        {% for file in ['index.d.ts', 'api.d.ts'] %}
                            <twig:Button variant="link" size="sm" class="w-full justify-start gap-2 text-foreground">
                                <twig:ux:icon name="lucide:file" />
                                <span>{{ file }}</span>
                            </twig:Button>
                        {% endfor %}
                    </div>
                </twig:Collapsible:Content>
            </twig:Collapsible>

            {# Folder: public #}
            <twig:Collapsible>
                <twig:Collapsible:Trigger>
                    <twig:Button variant="ghost" size="sm" class="group w-full justify-start transition-none hover:bg-accent hover:text-accent-foreground" {{ ...collapsible_trigger_attrs }}>
                        <twig:ux:icon name="lucide:chevron-right" class="transition-transform group-data-[state=open]:rotate-90" />
                        <twig:ux:icon name="lucide:folder" />
                        public
                    </twig:Button>
                </twig:Collapsible:Trigger>
                <twig:Collapsible:Content class="mt-1 ml-5">
                    <div class="flex flex-col gap-1">
                        {% for file in ['favicon.ico', 'logo.svg', 'images'] %}
                            <twig:Button variant="link" size="sm" class="w-full justify-start gap-2 text-foreground">
                                <twig:ux:icon name="lucide:file" />
                                <span>{{ file }}</span>
                            </twig:Button>
                        {% endfor %}
                    </div>
                </twig:Collapsible:Content>
            </twig:Collapsible>

            {# Root files #}
            {% for file in ['app.tsx', 'layout.tsx', 'globals.css', 'package.json', 'tsconfig.json', 'README.md', '.gitignore'] %}
                <twig:Button variant="link" size="sm" class="w-full justify-start gap-2 text-foreground">
                    <twig:ux:icon name="lucide:file" />
                    <span>{{ file }}</span>
                </twig:Button>
            {% endfor %}
        </div>
    </twig:Card:Content>
</twig:Card>
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

```twig {"preview":true,"height":"550px"}
<div class="flex w-[350px] flex-col gap-4 self-start">
    {# Arabic #}
    <twig:Collapsible class="flex flex-col gap-2" dir="rtl">
        <div class="flex items-center justify-between gap-4 px-4">
            <h4 class="text-sm font-semibold">الطلب #4189</h4>
            <twig:Collapsible:Trigger>
                <twig:Button variant="ghost" size="icon" class="size-8" {{ ...collapsible_trigger_attrs }}>
                    <twig:ux:icon name="lucide:chevrons-up-down" class="size-4" />
                    <span class="sr-only">Toggle details</span>
                </twig:Button>
            </twig:Collapsible:Trigger>
        </div>
        <div class="flex items-center justify-between rounded-md border px-4 py-2 text-sm">
            <span class="text-muted-foreground">الحالة</span>
            <span class="font-medium">تم الشحن</span>
        </div>
        <twig:Collapsible:Content class="flex flex-col gap-2">
            <div class="rounded-md border px-4 py-2 text-sm">
                <p class="font-medium">عنوان الشحن</p>
                <p class="text-muted-foreground">100 Market St, San Francisco</p>
            </div>
            <div class="rounded-md border px-4 py-2 text-sm">
                <p class="font-medium">العناصر</p>
                <p class="text-muted-foreground">2x سماعات الاستوديو</p>
            </div>
        </twig:Collapsible:Content>
    </twig:Collapsible>

    {# Hebrew #}
    <twig:Collapsible class="flex flex-col gap-2" dir="rtl">
        <div class="flex items-center justify-between gap-4 px-4">
            <h4 class="text-sm font-semibold">הזמנה #4189</h4>
            <twig:Collapsible:Trigger>
                <twig:Button variant="ghost" size="icon" class="size-8" {{ ...collapsible_trigger_attrs }}>
                    <twig:ux:icon name="lucide:chevrons-up-down" class="size-4" />
                    <span class="sr-only">Toggle details</span>
                </twig:Button>
            </twig:Collapsible:Trigger>
        </div>
        <div class="flex items-center justify-between rounded-md border px-4 py-2 text-sm">
            <span class="text-muted-foreground">סטטוס</span>
            <span class="font-medium">נשלח</span>
        </div>
        <twig:Collapsible:Content class="flex flex-col gap-2">
            <div class="rounded-md border px-4 py-2 text-sm">
                <p class="font-medium">כתובת משלוח</p>
                <p class="text-muted-foreground">100 Market St, San Francisco</p>
            </div>
            <div class="rounded-md border px-4 py-2 text-sm">
                <p class="font-medium">מוצרים</p>
                <p class="text-muted-foreground">2x אוזניות סטודיו</p>
            </div>
        </twig:Collapsible:Content>
    </twig:Collapsible>
</div>
```

## API Reference

::: api-reference
