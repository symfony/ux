# Dropdown Menu

A menu triggered by a button, providing a list of actions or links.

```twig {"preview":true}
<div class="flex items-start justify-center pt-6" style="min-height: 460px">
    <twig:DropdownMenu id="demo">
        <twig:DropdownMenu:Trigger>
            <twig:Button variant="outline" {{ ...dropdown_menu_trigger_attrs }}>Open</twig:Button>
        </twig:DropdownMenu:Trigger>
        <twig:DropdownMenu:Content class="w-56">
            <twig:DropdownMenu:Label>My Account</twig:DropdownMenu:Label>
            <twig:DropdownMenu:Separator />
            <twig:DropdownMenu:Group>
                <twig:DropdownMenu:Item>
                    <twig:ux:icon name="lucide:user" class="size-4" />
                    Profile
                    <twig:DropdownMenu:Shortcut>⇧⌘P</twig:DropdownMenu:Shortcut>
                </twig:DropdownMenu:Item>
                <twig:DropdownMenu:Item>
                    <twig:ux:icon name="lucide:credit-card" class="size-4" />
                    Billing
                    <twig:DropdownMenu:Shortcut>⌘B</twig:DropdownMenu:Shortcut>
                </twig:DropdownMenu:Item>
                <twig:DropdownMenu:Item>
                    <twig:ux:icon name="lucide:settings" class="size-4" />
                    Settings
                    <twig:DropdownMenu:Shortcut>⌘S</twig:DropdownMenu:Shortcut>
                </twig:DropdownMenu:Item>
            </twig:DropdownMenu:Group>
            <twig:DropdownMenu:Separator />
            <twig:DropdownMenu:Group>
                <twig:DropdownMenu:Item>
                    <twig:ux:icon name="lucide:users" class="size-4" />
                    Team
                </twig:DropdownMenu:Item>
                <twig:DropdownMenu:Sub>
                    <twig:DropdownMenu:SubTrigger>
                        <twig:ux:icon name="lucide:user-plus" class="size-4" />
                        Invite users
                    </twig:DropdownMenu:SubTrigger>
                    <twig:DropdownMenu:SubContent>
                        <twig:DropdownMenu:Item>
                            <twig:ux:icon name="lucide:mail" class="size-4" />
                            Email
                        </twig:DropdownMenu:Item>
                        <twig:DropdownMenu:Item>
                            <twig:ux:icon name="lucide:message-square" class="size-4" />
                            Message
                        </twig:DropdownMenu:Item>
                        <twig:DropdownMenu:Separator />
                        <twig:DropdownMenu:Item>
                            <twig:ux:icon name="lucide:ellipsis" class="size-4" />
                            More...
                        </twig:DropdownMenu:Item>
                    </twig:DropdownMenu:SubContent>
                </twig:DropdownMenu:Sub>
                <twig:DropdownMenu:Item>
                    <twig:ux:icon name="lucide:plus" class="size-4" />
                    New Team
                    <twig:DropdownMenu:Shortcut>⌘T</twig:DropdownMenu:Shortcut>
                </twig:DropdownMenu:Item>
            </twig:DropdownMenu:Group>
            <twig:DropdownMenu:Separator />
            <twig:DropdownMenu:Item disabled>
                <twig:ux:icon name="lucide:cloud" class="size-4" />
                API
            </twig:DropdownMenu:Item>
            <twig:DropdownMenu:Separator />
            <twig:DropdownMenu:Item>
                <twig:ux:icon name="lucide:log-out" class="size-4" />
                Log out
                <twig:DropdownMenu:Shortcut>⇧⌘Q</twig:DropdownMenu:Shortcut>
            </twig:DropdownMenu:Item>
        </twig:DropdownMenu:Content>
    </twig:DropdownMenu>
</div>
```

## Installation

::: installation

## Usage

```twig
<twig:DropdownMenu id="menu" side="bottom" align="start">
    <twig:DropdownMenu:Trigger>
        <twig:Button variant="outline" {{ ...dropdown_menu_trigger_attrs }}>Open</twig:Button>
    </twig:DropdownMenu:Trigger>
    <twig:DropdownMenu:Content class="w-40">
        <twig:DropdownMenu:Item>Profile</twig:DropdownMenu:Item>
        <twig:DropdownMenu:Item>Settings</twig:DropdownMenu:Item>
        <twig:DropdownMenu:Separator />
        <twig:DropdownMenu:Item>Log out</twig:DropdownMenu:Item>
    </twig:DropdownMenu:Content>
</twig:DropdownMenu>
```

## Examples

### Checkboxes

```twig {"preview":true}
<div class="flex items-start justify-center pt-6" style="min-height: 280px">
    <twig:DropdownMenu id="checkboxes">
        <twig:DropdownMenu:Trigger>
            <twig:Button variant="outline" {{ ...dropdown_menu_trigger_attrs }}>Open</twig:Button>
        </twig:DropdownMenu:Trigger>
        <twig:DropdownMenu:Content class="w-56">
            <twig:DropdownMenu:Label>Appearance</twig:DropdownMenu:Label>
            <twig:DropdownMenu:Separator />
            <twig:DropdownMenu:CheckboxItem checked>Status Bar</twig:DropdownMenu:CheckboxItem>
            <twig:DropdownMenu:CheckboxItem checked>Activity Bar</twig:DropdownMenu:CheckboxItem>
            <twig:DropdownMenu:CheckboxItem>Panel</twig:DropdownMenu:CheckboxItem>
            <twig:DropdownMenu:CheckboxItem disabled>Full Screen</twig:DropdownMenu:CheckboxItem>
        </twig:DropdownMenu:Content>
    </twig:DropdownMenu>
</div>
```

### Radio Group

```twig {"preview":true}
<div class="flex items-start justify-center pt-6" style="min-height: 240px">
    <twig:DropdownMenu id="radio">
        <twig:DropdownMenu:Trigger>
            <twig:Button variant="outline" {{ ...dropdown_menu_trigger_attrs }}>Open</twig:Button>
        </twig:DropdownMenu:Trigger>
        <twig:DropdownMenu:Content class="w-56">
            <twig:DropdownMenu:Label>Panel Position</twig:DropdownMenu:Label>
            <twig:DropdownMenu:Separator />
            <twig:DropdownMenu:RadioGroup value="bottom">
                <twig:DropdownMenu:RadioItem value="top">Top</twig:DropdownMenu:RadioItem>
                <twig:DropdownMenu:RadioItem value="bottom" checked>Bottom</twig:DropdownMenu:RadioItem>
                <twig:DropdownMenu:RadioItem value="right">Right</twig:DropdownMenu:RadioItem>
            </twig:DropdownMenu:RadioGroup>
        </twig:DropdownMenu:Content>
    </twig:DropdownMenu>
</div>
```

### Submenus

A `DropdownMenu:Item` can open a nested `DropdownMenu:SubContent` on hover or focus with `DropdownMenu:Sub` and `DropdownMenu:SubTrigger`.

```twig {"preview":true}
<div class="flex w-[400px] items-start pt-6 ps-6" style="min-height: 340px">
    <twig:DropdownMenu id="submenu">
        <twig:DropdownMenu:Trigger>
            <twig:Button variant="outline" {{ ...dropdown_menu_trigger_attrs }}>Open</twig:Button>
        </twig:DropdownMenu:Trigger>
        <twig:DropdownMenu:Content class="w-48">
            <twig:DropdownMenu:Item>New Tab</twig:DropdownMenu:Item>
            <twig:DropdownMenu:Item>New Window</twig:DropdownMenu:Item>
            <twig:DropdownMenu:Separator />
            <twig:DropdownMenu:Sub>
                <twig:DropdownMenu:SubTrigger>Share</twig:DropdownMenu:SubTrigger>
                <twig:DropdownMenu:SubContent>
                    <twig:DropdownMenu:Item>Copy link</twig:DropdownMenu:Item>
                    <twig:DropdownMenu:Item>Email</twig:DropdownMenu:Item>
                    <twig:DropdownMenu:Sub>
                        <twig:DropdownMenu:SubTrigger>More options</twig:DropdownMenu:SubTrigger>
                        <twig:DropdownMenu:SubContent>
                            <twig:DropdownMenu:Item>Messages</twig:DropdownMenu:Item>
                            <twig:DropdownMenu:Item>Notes</twig:DropdownMenu:Item>
                        </twig:DropdownMenu:SubContent>
                    </twig:DropdownMenu:Sub>
                </twig:DropdownMenu:SubContent>
            </twig:DropdownMenu:Sub>
            <twig:DropdownMenu:Separator />
            <twig:DropdownMenu:Item>Print</twig:DropdownMenu:Item>
        </twig:DropdownMenu:Content>
    </twig:DropdownMenu>
</div>
```

### Alignment

Use the `align` prop on `DropdownMenu` to align the menu to the `start`, `center` or `end` of the trigger.

```twig {"preview":true}
<div class="flex items-start justify-center pt-6" style="min-height: 260px">
    <div class="flex flex-wrap justify-center gap-8">
        <twig:DropdownMenu id="align_start" align="start">
            <twig:DropdownMenu:Trigger>
                <twig:Button variant="outline" {{ ...dropdown_menu_trigger_attrs }}>Start</twig:Button>
            </twig:DropdownMenu:Trigger>
            <twig:DropdownMenu:Content class="w-40">
                <twig:DropdownMenu:Item><twig:ux:icon name="lucide:file" class="size-4" />New File</twig:DropdownMenu:Item>
                <twig:DropdownMenu:Item><twig:ux:icon name="lucide:folder" class="size-4" />New Folder</twig:DropdownMenu:Item>
            </twig:DropdownMenu:Content>
        </twig:DropdownMenu>
        <twig:DropdownMenu id="align_center" align="center">
            <twig:DropdownMenu:Trigger>
                <twig:Button variant="outline" {{ ...dropdown_menu_trigger_attrs }}>Center</twig:Button>
            </twig:DropdownMenu:Trigger>
            <twig:DropdownMenu:Content class="w-40">
                <twig:DropdownMenu:Item><twig:ux:icon name="lucide:file" class="size-4" />New File</twig:DropdownMenu:Item>
                <twig:DropdownMenu:Item><twig:ux:icon name="lucide:folder" class="size-4" />New Folder</twig:DropdownMenu:Item>
            </twig:DropdownMenu:Content>
        </twig:DropdownMenu>
        <twig:DropdownMenu id="align_end" align="end">
            <twig:DropdownMenu:Trigger>
                <twig:Button variant="outline" {{ ...dropdown_menu_trigger_attrs }}>End</twig:Button>
            </twig:DropdownMenu:Trigger>
            <twig:DropdownMenu:Content class="w-40">
                <twig:DropdownMenu:Item><twig:ux:icon name="lucide:file" class="size-4" />New File</twig:DropdownMenu:Item>
                <twig:DropdownMenu:Item><twig:ux:icon name="lucide:folder" class="size-4" />New Folder</twig:DropdownMenu:Item>
            </twig:DropdownMenu:Content>
        </twig:DropdownMenu>
    </div>
</div>
```

### With Dialog

An item can open a `Dialog`: close the menu and open the dialog from the same click.

```twig {"preview":true}
<div class="flex items-start justify-center pt-6" style="min-height: 360px">
    <twig:Dialog id="dropdown_dialog">
        <twig:DropdownMenu id="dialog">
            <twig:DropdownMenu:Trigger>
                <twig:Button variant="outline" {{ ...dropdown_menu_trigger_attrs }}>Open</twig:Button>
            </twig:DropdownMenu:Trigger>
            <twig:DropdownMenu:Content class="w-56">
                <twig:DropdownMenu:Label>My Account</twig:DropdownMenu:Label>
                <twig:DropdownMenu:Separator />
                <twig:DropdownMenu:Item>
                    <twig:ux:icon name="lucide:user" class="size-4" />
                    Profile
                </twig:DropdownMenu:Item>
                <twig:DropdownMenu:Separator />
                <twig:DropdownMenu:Item data-action="click->dropdown-menu#close click->dialog#open">
                    <twig:ux:icon name="lucide:user-plus" class="size-4" />
                    Invite users
                </twig:DropdownMenu:Item>
            </twig:DropdownMenu:Content>
        </twig:DropdownMenu>
        <twig:Dialog:Content class="sm:max-w-[425px]">
            <twig:Dialog:Header>
                <twig:Dialog:Title>Invite team members</twig:Dialog:Title>
                <twig:Dialog:Description>Invite your team members to collaborate.</twig:Dialog:Description>
            </twig:Dialog:Header>
            <div class="grid gap-4">
                <div class="grid gap-3">
                    <twig:Label for="invite-email">Email address</twig:Label>
                    <twig:Input id="invite-email" type="email" placeholder="user@example.com" />
                </div>
            </div>
            <twig:Dialog:Footer>
                <twig:Dialog:Close>
                    <twig:Button variant="outline" {{ ...dialog_close_attrs }}>Cancel</twig:Button>
                </twig:Dialog:Close>
                <twig:Button type="submit">Send invite</twig:Button>
            </twig:Dialog:Footer>
        </twig:Dialog:Content>
    </twig:Dialog>
</div>
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

```twig {"preview":true}
<div class="flex flex-col items-center gap-6 py-6" style="min-height: 420px">
    <div dir="rtl">
        <twig:DropdownMenu id="rtl_ar">
            <twig:DropdownMenu:Trigger>
                <twig:Button variant="outline" {{ ...dropdown_menu_trigger_attrs }}>افتح القائمة</twig:Button>
            </twig:DropdownMenu:Trigger>
            <twig:DropdownMenu:Content class="w-48">
                <twig:DropdownMenu:Item>الملف الشخصي</twig:DropdownMenu:Item>
                <twig:DropdownMenu:Sub>
                    <twig:DropdownMenu:SubTrigger>مشاركة</twig:DropdownMenu:SubTrigger>
                    <twig:DropdownMenu:SubContent>
                        <twig:DropdownMenu:Item>بريد إلكتروني</twig:DropdownMenu:Item>
                        <twig:DropdownMenu:Item>رسالة</twig:DropdownMenu:Item>
                    </twig:DropdownMenu:SubContent>
                </twig:DropdownMenu:Sub>
                <twig:DropdownMenu:Separator />
                <twig:DropdownMenu:Item>تسجيل الخروج</twig:DropdownMenu:Item>
            </twig:DropdownMenu:Content>
        </twig:DropdownMenu>
    </div>
    <div dir="rtl">
        <twig:DropdownMenu id="rtl_he">
            <twig:DropdownMenu:Trigger>
                <twig:Button variant="outline" {{ ...dropdown_menu_trigger_attrs }}>פתח תפריט</twig:Button>
            </twig:DropdownMenu:Trigger>
            <twig:DropdownMenu:Content class="w-48">
                <twig:DropdownMenu:Item>פרופיל</twig:DropdownMenu:Item>
                <twig:DropdownMenu:Sub>
                    <twig:DropdownMenu:SubTrigger>שיתוף</twig:DropdownMenu:SubTrigger>
                    <twig:DropdownMenu:SubContent>
                        <twig:DropdownMenu:Item>אימייל</twig:DropdownMenu:Item>
                        <twig:DropdownMenu:Item>הודעה</twig:DropdownMenu:Item>
                    </twig:DropdownMenu:SubContent>
                </twig:DropdownMenu:Sub>
                <twig:DropdownMenu:Separator />
                <twig:DropdownMenu:Item>התנתקות</twig:DropdownMenu:Item>
            </twig:DropdownMenu:Content>
        </twig:DropdownMenu>
    </div>
</div>
```

## API Reference

::: api-reference
