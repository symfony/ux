# Dialog

A window overlaid on either the primary window or another dialog window, rendering the content underneath inert.

```twig {"preview":true}
<div style="min-height: 354px">
    <twig:Dialog id="edit_profile">
        <twig:Dialog:Trigger>
            <twig:Button variant="outline" {{ ...dialog_trigger_attrs }}>Open Dialog</twig:Button>
        </twig:Dialog:Trigger>
        <twig:Dialog:Content>
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
                    <twig:Button variant="outline" {{ ...dialog_close_attrs }}>Cancel</twig:Button>
                </twig:Dialog:Close>
                <twig:Button type="submit">Save changes</twig:Button>
            </twig:Dialog:Footer>
        </twig:Dialog:Content>
    </twig:Dialog>
</div>
```

## Installation

::: installation

## Usage

```twig
<twig:Dialog id="delete_account">
    <twig:Dialog:Trigger>
        <twig:Button {{ ...dialog_trigger_attrs }}>Open</twig:Button>
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

## Examples

### Custom Close Button

Replace the default close control with your own button.

```twig {"preview":true}
<div style="min-height: 354px">
    <twig:Dialog id="share_link">
        <twig:Dialog:Trigger>
            <twig:Button variant="outline" {{ ...dialog_trigger_attrs }}>Share</twig:Button>
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
                    <twig:Button type="button" {{ ...dialog_close_attrs }}>Close</twig:Button>
                </twig:Dialog:Close>
            </twig:Dialog:Footer>
        </twig:Dialog:Content>
    </twig:Dialog>
</div>
```

### No Close Button

Set the `showCloseButton` prop to `false` to hide the close button.

```twig {"preview":true}
<div style="min-height: 354px">
    <twig:Dialog id="no_close_button">
        <twig:Dialog:Trigger>
            <twig:Button variant="outline" {{ ...dialog_trigger_attrs }}>No Close Button</twig:Button>
        </twig:Dialog:Trigger>
        <twig:Dialog:Content showCloseButton="{{ false }}">
            <twig:Dialog:Header>
                <twig:Dialog:Title>No Close Button</twig:Dialog:Title>
                <twig:Dialog:Description>
                    This dialog doesn&apos;t have a close button in the top-right corner.
                </twig:Dialog:Description>
            </twig:Dialog:Header>
        </twig:Dialog:Content>
    </twig:Dialog>
</div>
```

### Sticky Footer

Keep actions visible while the content scrolls.

```twig {"preview":true}
<div style="min-height: 354px">
    <twig:Dialog id="sticky_footer">
        <twig:Dialog:Trigger>
            <twig:Button variant="outline" {{ ...dialog_trigger_attrs }}>Sticky Footer</twig:Button>
        </twig:Dialog:Trigger>
        <twig:Dialog:Content>
            <twig:Dialog:Header>
                <twig:Dialog:Title>Sticky Footer</twig:Dialog:Title>
                <twig:Dialog:Description>
                    This dialog has a sticky footer that stays visible while the content scrolls.
                </twig:Dialog:Description>
            </twig:Dialog:Header>
            <div class="-mx-4 max-h-[50vh] overflow-y-auto px-4">
                {% for i in 1..10 %}
                    <p class="mb-4 leading-normal">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.</p>
                {% endfor %}
            </div>
            <twig:Dialog:Footer>
                <twig:Dialog:Close>
                    <twig:Button variant="outline" {{ ...dialog_close_attrs }}>Close</twig:Button>
                </twig:Dialog:Close>
            </twig:Dialog:Footer>
        </twig:Dialog:Content>
    </twig:Dialog>
</div>
```

### Scrollable Content

Long content can scroll while the header stays in view.

```twig {"preview":true}
<div style="min-height: 354px">
    <twig:Dialog id="scrollable_content">
        <twig:Dialog:Trigger>
            <twig:Button variant="outline" {{ ...dialog_trigger_attrs }}>Scrollable Content</twig:Button>
        </twig:Dialog:Trigger>
        <twig:Dialog:Content>
            <twig:Dialog:Header>
                <twig:Dialog:Title>Scrollable Content</twig:Dialog:Title>
                <twig:Dialog:Description>
                    This is a dialog with scrollable content.
                </twig:Dialog:Description>
            </twig:Dialog:Header>
            <div class="-mx-4 max-h-[50vh] overflow-y-auto px-4">
                {% for i in 1..10 %}
                    <p class="mb-4 leading-normal">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.</p>
                {% endfor %}
            </div>
        </twig:Dialog:Content>
    </twig:Dialog>
</div>
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

```twig {"preview":true}
<div style="min-height: 354px">
    <div class="flex flex-col gap-4">
        {# Arabic #}
        <twig:Dialog id="dialog_rtl_ar">
            <twig:Dialog:Trigger>
                <twig:Button variant="outline" {{ ...dialog_trigger_attrs }}>فتح الحوار</twig:Button>
            </twig:Dialog:Trigger>
            <twig:Dialog:Content dir="rtl">
                <twig:Dialog:Header>
                    <twig:Dialog:Title>تعديل الملف الشخصي</twig:Dialog:Title>
                    <twig:Dialog:Description>
                        قم بإجراء تغييرات على ملفك الشخصي هنا. انقر فوق حفظ عند الانتهاء.
                    </twig:Dialog:Description>
                </twig:Dialog:Header>
                <div class="grid gap-4">
                    <div class="grid gap-3">
                        <twig:Label for="name-ar">الاسم</twig:Label>
                        <twig:Input id="name-ar" name="name" value="Pedro Duarte" />
                    </div>
                    <div class="grid gap-3">
                        <twig:Label for="username-ar">اسم المستخدم</twig:Label>
                        <twig:Input id="username-ar" name="username" value="@peduarte" />
                    </div>
                </div>
                <twig:Dialog:Footer>
                    <twig:Dialog:Close>
                        <twig:Button variant="outline" {{ ...dialog_close_attrs }}>إلغاء</twig:Button>
                    </twig:Dialog:Close>
                    <twig:Button type="submit">حفظ التغييرات</twig:Button>
                </twig:Dialog:Footer>
            </twig:Dialog:Content>
        </twig:Dialog>

        {# Hebrew #}
        <twig:Dialog id="dialog_rtl_he">
            <twig:Dialog:Trigger>
                <twig:Button variant="outline" {{ ...dialog_trigger_attrs }}>הצג דיאלוג</twig:Button>
            </twig:Dialog:Trigger>
            <twig:Dialog:Content dir="rtl">
                <twig:Dialog:Header>
                    <twig:Dialog:Title>ערוך נתונים</twig:Dialog:Title>
                    <twig:Dialog:Description>
                        ניתן לשנות נתונים כאן. לחץ שמור בסיום.
                    </twig:Dialog:Description>
                </twig:Dialog:Header>
                <div class="grid gap-4">
                    <div class="grid gap-3">
                        <twig:Label for="name-he">שם</twig:Label>
                        <twig:Input id="name-he" name="name" value="Pedro Duarte" />
                    </div>
                    <div class="grid gap-3">
                        <twig:Label for="username-he">שם משתמש</twig:Label>
                        <twig:Input id="username-he" name="username" value="@peduarte" />
                    </div>
                </div>
                <twig:Dialog:Footer>
                    <twig:Dialog:Close>
                        <twig:Button variant="outline" {{ ...dialog_close_attrs }}>בטל</twig:Button>
                    </twig:Dialog:Close>
                    <twig:Button type="submit">שמור שינויים</twig:Button>
                </twig:Dialog:Footer>
            </twig:Dialog:Content>
        </twig:Dialog>
    </div>
</div>
```

## API Reference

::: api-reference
