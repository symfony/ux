# Examples

## Default

```twig {"preview":true,"height":"500px"}
<twig:Dialog id="delete_account">
    <twig:Dialog:Trigger>
        <twig:Button {{ ...trigger_attrs }}>Open</twig:Button>
    </twig:Dialog:Trigger>
    <twig:Dialog:Content>
        <twig:Dialog:Header>
            <twig:Dialog:Title>Are you absolutely sure?</twig:Dialog:Title>
            <twig:Dialog:Description>
                This action cannot be undone. This will permanently delete your account
                and remove your data from our servers.
            </twig:Dialog:Description>
        </twig:Dialog:Header>
    </twig:Dialog:Content>
</twig:Dialog>
```

## Custom close button

```twig {"preview":true,"height":"500px"}
<twig:Dialog id="share_link">
    <twig:Dialog:Trigger>
        <twig:Button variant="outline" {{ ...trigger_attrs }}>Share</twig:Button>
    </twig:Dialog:Trigger>
    <twig:Dialog:Content class="sm:max-w-md">
        <twig:Dialog:Header>
            <twig:Dialog:Title>Share link</twig:Dialog:Title>
            <twig:Dialog:Description>
                Anyone who has this link will be able to view this.
            </twig:Dialog:Description>
        </twig:Dialog:Header>
        <div class="flex items-center gap-2">
            <div class="grid flex-1 gap-2">
                <twig:Label for="link" class="sr-only">Link</twig:Label>
                <twig:Input id="link" value="https://ui.shadcn.com/docs/installation" readonly />
          </div>
        </div>
        <twig:Dialog:Footer class="sm:justify-start">
            <twig:Dialog:Close>
                <twig:Button type="button" variant="secondary" {{ ...close_attrs }}>
                    Close
                </twig:Button>
            </twig:Dialog:Close>
        </twig:Dialog:Footer>
    </twig:Dialog:Content>
</twig:Dialog>
```

## With form

```twig {"preview":true,"height":"500px"}
<twig:Dialog id="edit_profile">
    <twig:Dialog:Trigger>
        <twig:Button {{ ...trigger_attrs }} variant="outline">Open Dialog</twig:Button>
    </twig:Dialog:Trigger>
    <twig:Dialog:Content class="sm:max-w-[425px]">
        <twig:Dialog:Header>
            <twig:Dialog:Title>Edit profile</twig:Dialog:Title>
            <twig:Dialog:Description>
                Make changes to your profile here. Click save when you&apos;re done.
            </twig:Dialog:Description>
        </twig:Dialog:Header>
        <div class="grid gap-4">
            <div class="grid gap-3">
              <twig:Label for="name">Name</twig:Label>
              <twig:Input id="name" name="name" value="Pedro Duarte" />
            </div>
            <div class="grid gap-3">
              <twig:Label for="username">Username</twig:Label>
              <twig:Input id="username" name="username" value="@peduarte" />
          </div>
      </div>
        <twig:Dialog:Footer>
            <twig:Dialog:Close>
                <twig:Button variant="outline" {{ ...close_attrs }}>Cancel</twig:Button>
            </twig:Dialog:Close>
            <twig:Button type="submit">Save changes</twig:Button>
        </twig:Dialog:Footer>
    </twig:Dialog:Content>
</twig:Dialog>
```
