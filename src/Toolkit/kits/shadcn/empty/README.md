# Empty

Use the Empty component to display an empty state.

```twig {"preview":true,"height":"300px"}
<twig:Empty>
    <twig:Empty:Header>
        <twig:Empty:Media variant="icon">
            <twig:ux:icon name="tabler:folder-code" />
        </twig:Empty:Media>
        <twig:Empty:Title>No Projects Yet</twig:Empty:Title>
        <twig:Empty:Description>
            You haven&apos;t created any projects yet. Get started by creating your first project.
        </twig:Empty:Description>
    </twig:Empty:Header>
    <twig:Empty:Content class="flex-row justify-center gap-2">
        <twig:Button>Create Project</twig:Button>
        <twig:Button variant="outline">Import Project</twig:Button>
    </twig:Empty:Content>
    <twig:Button variant="link" as="a" href="#" size="sm" class="text-muted-foreground">
        Learn More <twig:ux:icon name="lucide:arrow-up-right" />
    </twig:Button>
</twig:Empty>
```

## Installation

::: installation

## Usage

```twig
<twig:Empty>
    <twig:Empty:Header>
        <twig:Empty:Media variant="icon">
            <twig:ux:icon name="lucide:braces" />
        </twig:Empty:Media>
        <twig:Empty:Title>No data</twig:Empty:Title>
        <twig:Empty:Description>No data found</twig:Empty:Description>
    </twig:Empty:Header>
    <twig:Empty:Content>
        <twig:Button>Add data</twig:Button>
    </twig:Empty:Content>
</twig:Empty>
```

## Examples

### Outline

Use the `border` utility class to create an outline empty state.

```twig {"preview":true,"height":"300px"}
<twig:Empty class="border border-dashed">
    <twig:Empty:Header>
        <twig:Empty:Media variant="icon">
            <twig:ux:icon name="tabler:cloud" />
        </twig:Empty:Media>
        <twig:Empty:Title>Cloud Storage Empty</twig:Empty:Title>
        <twig:Empty:Description>
            Upload files to your cloud storage to access them anywhere.
        </twig:Empty:Description>
    </twig:Empty:Header>
    <twig:Empty:Content>
        <twig:Button variant="outline" size="sm">
            Upload Files
        </twig:Button>
    </twig:Empty:Content>
</twig:Empty>
```

### Background

Use the `bg-*` utilities to add a background to the empty state.

```twig {"preview":true,"height":"300px"}
<twig:Empty class="h-full bg-muted/30">
    <twig:Empty:Header>
        <twig:Empty:Media variant="icon">
            <twig:ux:icon name="lucide:bell" />
        </twig:Empty:Media>
        <twig:Empty:Title>No Notifications</twig:Empty:Title>
        <twig:Empty:Description>
            You&apos;re all caught up. New notifications will appear here.
        </twig:Empty:Description>
    </twig:Empty:Header>
    <twig:Empty:Content>
        <twig:Button variant="outline">
            <twig:ux:icon name="lucide:refresh-ccw" />
            Refresh
        </twig:Button>
    </twig:Empty:Content>
</twig:Empty>
```

### Avatar

Use the `Empty:Media` component to display an avatar in the empty state.

```twig {"preview":true,"height":"300px"}
<twig:Empty>
    <twig:Empty:Header>
        <twig:Empty:Media>
            <twig:Avatar class="size-12">
                <twig:Avatar:Image
                    src="https://github.com/shadcn.png"
                    alt="@shadcn"
                    class="grayscale"
                />
                <twig:Avatar:Fallback>LR</twig:Avatar:Fallback>
            </twig:Avatar>
        </twig:Empty:Media>
        <twig:Empty:Title>User Offline</twig:Empty:Title>
        <twig:Empty:Description>
            This user is currently offline. You can leave a message to notify them or try again later.
        </twig:Empty:Description>
    </twig:Empty:Header>
    <twig:Empty:Content>
        <twig:Button size="sm">Leave Message</twig:Button>
    </twig:Empty:Content>
</twig:Empty>
```

### Avatar Group

Use the `Empty:Media` component to display an avatar group in the empty state.

```twig {"preview":true,"height":"300px"}
<twig:Empty>
    <twig:Empty:Header>
        <twig:Empty:Media>
            <div class="flex -space-x-2 *:data-[slot=avatar]:size-12 *:data-[slot=avatar]:ring-2 *:data-[slot=avatar]:ring-background *:data-[slot=avatar]:grayscale">
                <twig:Avatar>
                    <twig:Avatar:Image src="https://github.com/shadcn.png" alt="@shadcn" />
                    <twig:Avatar:Fallback>CN</twig:Avatar:Fallback>
                </twig:Avatar>
                <twig:Avatar>
                    <twig:Avatar:Image src="https://github.com/maxleiter.png" alt="@maxleiter" />
                    <twig:Avatar:Fallback>LR</twig:Avatar:Fallback>
                </twig:Avatar>
                <twig:Avatar>
                    <twig:Avatar:Image src="https://github.com/evilrabbit.png" alt="@evilrabbit" />
                    <twig:Avatar:Fallback>ER</twig:Avatar:Fallback>
                </twig:Avatar>
            </div>
        </twig:Empty:Media>
        <twig:Empty:Title>No Team Members</twig:Empty:Title>
        <twig:Empty:Description>
            Invite your team to collaborate on this project.
        </twig:Empty:Description>
    </twig:Empty:Header>
    <twig:Empty:Content>
        <twig:Button size="sm">
            <twig:ux:icon name="lucide:plus" />
            Invite Members
        </twig:Button>
    </twig:Empty:Content>
</twig:Empty>
```

### InputGroup

You can add an `InputGroup` component to the `Empty:Content` component.

```twig {"preview":true,"height":"300px"}
<twig:Empty>
    <twig:Empty:Header>
        <twig:Empty:Title>404 - Not Found</twig:Empty:Title>
        <twig:Empty:Description>
            The page you&apos;re looking for doesn&apos;t exist. Try searching for what you need below.
        </twig:Empty:Description>
    </twig:Empty:Header>
    <twig:Empty:Content>
        <twig:InputGroup class="sm:w-3/4">
            <twig:InputGroup:Input placeholder="Try searching for pages..." />
            <twig:InputGroup:Addon>
                <twig:ux:icon name="lucide:search" />
            </twig:InputGroup:Addon>
            <twig:InputGroup:Addon align="inline-end">
                <twig:Kbd>/</twig:Kbd>
            </twig:InputGroup:Addon>
        </twig:InputGroup>
        <twig:Empty:Description>
            Need help? <a href="#">Contact support</a>
        </twig:Empty:Description>
    </twig:Empty:Content>
</twig:Empty>
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

```twig {"preview":true,"height":"600px"}
<div class="flex w-full flex-col gap-8">
    {# Arabic #}
    <twig:Empty dir="rtl">
        <twig:Empty:Header>
            <twig:Empty:Media variant="icon">
                <twig:ux:icon name="tabler:folder-code" />
            </twig:Empty:Media>
            <twig:Empty:Title>لا توجد مشاريع بعد</twig:Empty:Title>
            <twig:Empty:Description>
                لم تقم بإنشاء أي مشاريع بعد. ابدأ بإنشاء مشروعك الأول.
            </twig:Empty:Description>
        </twig:Empty:Header>
        <twig:Empty:Content class="flex-row justify-center gap-2">
            <twig:Button>إنشاء مشروع</twig:Button>
            <twig:Button variant="outline">استيراد مشروع</twig:Button>
        </twig:Empty:Content>
        <twig:Button variant="link" as="a" href="#" size="sm" class="text-muted-foreground">
            تعرف على المزيد <twig:ux:icon name="lucide:arrow-up-right" class="rtl:-rotate-90" data-icon="inline-end" />
        </twig:Button>
    </twig:Empty>

    {# Hebrew #}
    <twig:Empty dir="rtl">
        <twig:Empty:Header>
            <twig:Empty:Media variant="icon">
                <twig:ux:icon name="tabler:folder-code" />
            </twig:Empty:Media>
            <twig:Empty:Title>אין מיזמים עדיין</twig:Empty:Title>
            <twig:Empty:Description>
                עדיין לא יצרת מיזמים. התחל ביצירת המיזם הראשון שלך.
            </twig:Empty:Description>
        </twig:Empty:Header>
        <twig:Empty:Content class="flex-row justify-center gap-2">
            <twig:Button>צור מיזם</twig:Button>
            <twig:Button variant="outline">ייבא מיזם</twig:Button>
        </twig:Empty:Content>
        <twig:Button variant="link" as="a" href="#" size="sm" class="text-muted-foreground">
            למד עוד <twig:ux:icon name="lucide:arrow-up-right" class="rtl:-rotate-90" data-icon="inline-end" />
        </twig:Button>
    </twig:Empty>
</div>
```

## API Reference

::: api-reference
