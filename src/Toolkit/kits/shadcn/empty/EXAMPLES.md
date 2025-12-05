# Examples

## Outline

```twig {"preview":true,"height":"280px"}
<twig:Empty class="border border-dashed">
    <twig:Empty:Header>
        <twig:Empty:Media variant="icon">
            <twig:ux:icon name="tabler:cloud" class="size-5" />
        </twig:Empty:Media>
        <twig:Empty:Title>Cloud storage empty</twig:Empty:Title>
        <twig:Empty:Description>
            Upload files to your cloud storage to access them anywhere.
        </twig:Empty:Description>
    </twig:Empty:Header>
    <twig:Empty:Content>
        <twig:Button variant="outline" size="sm">
            Upload files
        </twig:Button>
    </twig:Empty:Content>
</twig:Empty>
```

## Background

```twig {"preview":true,"height":"280px"}
<twig:Empty class="from-muted/50 to-background h-full bg-linear-to-b from-30%">
    <twig:Empty:Header>
        <twig:Empty:Media variant="icon">
            <twig:ux:icon name="lucide:bell" class="size-5" />
        </twig:Empty:Media>
        <twig:Empty:Title>No notifications</twig:Empty:Title>
        <twig:Empty:Description>
            You're all caught up. New notifications will appear here.
        </twig:Empty:Description>
    </twig:Empty:Header>
    <twig:Empty:Content>
        <twig:Button variant="outline" size="sm">
            <twig:ux:icon name="lucide:refresh-ccw" class="size-4" />
            Refresh
        </twig:Button>
    </twig:Empty:Content>
</twig:Empty>
```

## Avatar

```twig {"preview":true,"height":"280px"}
<twig:Empty>
    <twig:Empty:Header>
        <twig:Empty:Media>
            <twig:Avatar class="size-12">
                <twig:Avatar:Image
                    src="https://github.com/shadcn.png"
                    alt="@shadcn"
                    class="grayscale"
                />
                <twig:Avatar:Text>LR</twig:Avatar:Text>
            </twig:Avatar>
        </twig:Empty:Media>
        <twig:Empty:Title>User offline</twig:Empty:Title>
        <twig:Empty:Description>
            This user is currently offline. You can leave a message to notify them or try again later.
        </twig:Empty:Description>
    </twig:Empty:Header>
    <twig:Empty:Content>
        <twig:Button size="sm">Leave message</twig:Button>
    </twig:Empty:Content>
</twig:Empty>
```

## Avatar group

```twig {"preview":true,"height":"300px"}
<twig:Empty>
    <twig:Empty:Header>
        <twig:Empty:Media>
            <div class="*:data-[slot=avatar]:ring-background flex -space-x-2 *:data-[slot=avatar]:size-12 *:data-[slot=avatar]:ring-2 *:data-[slot=avatar]:grayscale">
                <twig:Avatar>
                    <twig:Avatar:Image src="https://github.com/shadcn.png" alt="@shadcn" />
                    <twig:Avatar:Text>CN</twig:Avatar:Text>
                </twig:Avatar>
                <twig:Avatar>
                    <twig:Avatar:Image src="https://github.com/maxleiter.png" alt="@maxleiter" />
                    <twig:Avatar:Text>LR</twig:Avatar:Text>
                </twig:Avatar>
                <twig:Avatar>
                    <twig:Avatar:Image src="https://github.com/evilrabbit.png" alt="@evilrabbit" />
                    <twig:Avatar:Text>ER</twig:Avatar:Text>
                </twig:Avatar>
            </div>
        </twig:Empty:Media>
        <twig:Empty:Title>No team members</twig:Empty:Title>
        <twig:Empty:Description>
            Invite your team to collaborate on this project.
        </twig:Empty:Description>
    </twig:Empty:Header>
    <twig:Empty:Content>
        <twig:Button size="sm">
            <twig:ux:icon name="lucide:plus" class="size-4" />
            Invite members
        </twig:Button>
    </twig:Empty:Content>
</twig:Empty>
```
