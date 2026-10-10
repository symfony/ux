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

### Basic

A basic dropdown menu with labels and separators.

```twig {"preview":true}
<div class="flex items-start justify-center pt-6" style="min-height: 300px">
    <twig:DropdownMenu id="basic">
        <twig:DropdownMenu:Trigger>
            <twig:Button variant="outline" {{ ...dropdown_menu_trigger_attrs }}>Open</twig:Button>
        </twig:DropdownMenu:Trigger>
        <twig:DropdownMenu:Content>
            <twig:DropdownMenu:Group>
                <twig:DropdownMenu:Label>My Account</twig:DropdownMenu:Label>
                <twig:DropdownMenu:Item>Profile</twig:DropdownMenu:Item>
                <twig:DropdownMenu:Item>Billing</twig:DropdownMenu:Item>
                <twig:DropdownMenu:Item>Settings</twig:DropdownMenu:Item>
            </twig:DropdownMenu:Group>
            <twig:DropdownMenu:Separator />
            <twig:DropdownMenu:Item>GitHub</twig:DropdownMenu:Item>
            <twig:DropdownMenu:Item>Support</twig:DropdownMenu:Item>
            <twig:DropdownMenu:Item disabled>API</twig:DropdownMenu:Item>
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

### Shortcuts

Add `DropdownMenu:Shortcut` to show keyboard hints.

```twig {"preview":true}
<div class="flex items-start justify-center pt-6" style="min-height: 240px">
    <twig:DropdownMenu id="shortcuts">
        <twig:DropdownMenu:Trigger>
            <twig:Button variant="outline" {{ ...dropdown_menu_trigger_attrs }}>Open</twig:Button>
        </twig:DropdownMenu:Trigger>
        <twig:DropdownMenu:Content>
            <twig:DropdownMenu:Group>
                <twig:DropdownMenu:Label>My Account</twig:DropdownMenu:Label>
                <twig:DropdownMenu:Item>
                    Profile
                    <twig:DropdownMenu:Shortcut>⇧⌘P</twig:DropdownMenu:Shortcut>
                </twig:DropdownMenu:Item>
                <twig:DropdownMenu:Item>
                    Billing
                    <twig:DropdownMenu:Shortcut>⌘B</twig:DropdownMenu:Shortcut>
                </twig:DropdownMenu:Item>
                <twig:DropdownMenu:Item>
                    Settings
                    <twig:DropdownMenu:Shortcut>⌘S</twig:DropdownMenu:Shortcut>
                </twig:DropdownMenu:Item>
            </twig:DropdownMenu:Group>
            <twig:DropdownMenu:Separator />
            <twig:DropdownMenu:Item>
                Log out
                <twig:DropdownMenu:Shortcut>⇧⌘Q</twig:DropdownMenu:Shortcut>
            </twig:DropdownMenu:Item>
        </twig:DropdownMenu:Content>
    </twig:DropdownMenu>
</div>
```

### Icons

Combine icons with labels for quick scanning.

```twig {"preview":true}
<div class="flex items-start justify-center pt-6" style="min-height: 220px">
    <twig:DropdownMenu id="icons">
        <twig:DropdownMenu:Trigger>
            <twig:Button variant="outline" {{ ...dropdown_menu_trigger_attrs }}>Open</twig:Button>
        </twig:DropdownMenu:Trigger>
        <twig:DropdownMenu:Content>
            <twig:DropdownMenu:Item>
                <twig:ux:icon name="lucide:user" class="size-4" />
                Profile
            </twig:DropdownMenu:Item>
            <twig:DropdownMenu:Item>
                <twig:ux:icon name="lucide:credit-card" class="size-4" />
                Billing
            </twig:DropdownMenu:Item>
            <twig:DropdownMenu:Item>
                <twig:ux:icon name="lucide:settings" class="size-4" />
                Settings
            </twig:DropdownMenu:Item>
            <twig:DropdownMenu:Separator />
            <twig:DropdownMenu:Item variant="destructive">
                <twig:ux:icon name="lucide:log-out" class="size-4" />
                Log out
            </twig:DropdownMenu:Item>
        </twig:DropdownMenu:Content>
    </twig:DropdownMenu>
</div>
```

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

### Checkboxes Icons

Add icons to checkbox items.

```twig {"preview":true}
<div class="flex items-start justify-center pt-6" style="min-height: 220px">
    <twig:DropdownMenu id="checkboxes_icons">
        <twig:DropdownMenu:Trigger>
            <twig:Button variant="outline" {{ ...dropdown_menu_trigger_attrs }}>Notifications</twig:Button>
        </twig:DropdownMenu:Trigger>
        <twig:DropdownMenu:Content class="w-48">
            <twig:DropdownMenu:Group>
                <twig:DropdownMenu:Label>Notification Preferences</twig:DropdownMenu:Label>
                <twig:DropdownMenu:CheckboxItem checked>
                    <twig:ux:icon name="lucide:mail" class="size-4" />
                    Email notifications
                </twig:DropdownMenu:CheckboxItem>
                <twig:DropdownMenu:CheckboxItem>
                    <twig:ux:icon name="lucide:message-square" class="size-4" />
                    SMS notifications
                </twig:DropdownMenu:CheckboxItem>
                <twig:DropdownMenu:CheckboxItem checked>
                    <twig:ux:icon name="lucide:bell" class="size-4" />
                    Push notifications
                </twig:DropdownMenu:CheckboxItem>
            </twig:DropdownMenu:Group>
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

### Radio Icons

Show radio options with icons.

```twig {"preview":true}
<div class="flex items-start justify-center pt-6" style="min-height: 220px">
    <twig:DropdownMenu id="radio_icons">
        <twig:DropdownMenu:Trigger>
            <twig:Button variant="outline" {{ ...dropdown_menu_trigger_attrs }}>Payment Method</twig:Button>
        </twig:DropdownMenu:Trigger>
        <twig:DropdownMenu:Content class="min-w-56">
            <twig:DropdownMenu:Group>
                <twig:DropdownMenu:Label>Select Payment Method</twig:DropdownMenu:Label>
                <twig:DropdownMenu:RadioGroup value="card">
                    <twig:DropdownMenu:RadioItem value="card" checked>
                        <twig:ux:icon name="lucide:credit-card" class="size-4" />
                        Credit Card
                    </twig:DropdownMenu:RadioItem>
                    <twig:DropdownMenu:RadioItem value="paypal">
                        <twig:ux:icon name="lucide:wallet" class="size-4" />
                        PayPal
                    </twig:DropdownMenu:RadioItem>
                    <twig:DropdownMenu:RadioItem value="bank">
                        <twig:ux:icon name="lucide:building-2" class="size-4" />
                        Bank Transfer
                    </twig:DropdownMenu:RadioItem>
                </twig:DropdownMenu:RadioGroup>
            </twig:DropdownMenu:Group>
        </twig:DropdownMenu:Content>
    </twig:DropdownMenu>
</div>
```

### Destructive

Use `variant="destructive"` for irreversible actions.

```twig {"preview":true}
<div class="flex items-start justify-center pt-6" style="min-height: 220px">
    <twig:DropdownMenu id="destructive">
        <twig:DropdownMenu:Trigger>
            <twig:Button variant="outline" {{ ...dropdown_menu_trigger_attrs }}>Actions</twig:Button>
        </twig:DropdownMenu:Trigger>
        <twig:DropdownMenu:Content>
            <twig:DropdownMenu:Group>
                <twig:DropdownMenu:Item>
                    <twig:ux:icon name="lucide:pencil" class="size-4" />
                    Edit
                </twig:DropdownMenu:Item>
                <twig:DropdownMenu:Item>
                    <twig:ux:icon name="lucide:share" class="size-4" />
                    Share
                </twig:DropdownMenu:Item>
            </twig:DropdownMenu:Group>
            <twig:DropdownMenu:Separator />
            <twig:DropdownMenu:Group>
                <twig:DropdownMenu:Item variant="destructive">
                    <twig:ux:icon name="lucide:trash" class="size-4" />
                    Delete
                </twig:DropdownMenu:Item>
            </twig:DropdownMenu:Group>
        </twig:DropdownMenu:Content>
    </twig:DropdownMenu>
</div>
```

### Avatar

An account switcher dropdown triggered by an avatar.

```twig {"preview":true}
<div class="flex items-start justify-center pt-6" style="min-height: 240px">
    <twig:DropdownMenu id="avatar" align="end">
        <twig:DropdownMenu:Trigger>
            <twig:Button variant="ghost" size="icon" class="rounded-full" {{ ...dropdown_menu_trigger_attrs }}>
                <twig:Avatar>
                    <twig:Avatar:Image src="https://github.com/shadcn.png" alt="shadcn" />
                    <twig:Avatar:Fallback>LR</twig:Avatar:Fallback>
                </twig:Avatar>
            </twig:Button>
        </twig:DropdownMenu:Trigger>
        <twig:DropdownMenu:Content>
            <twig:DropdownMenu:Group>
                <twig:DropdownMenu:Item>
                    <twig:ux:icon name="lucide:badge-check" class="size-4" />
                    Account
                </twig:DropdownMenu:Item>
                <twig:DropdownMenu:Item>
                    <twig:ux:icon name="lucide:credit-card" class="size-4" />
                    Billing
                </twig:DropdownMenu:Item>
                <twig:DropdownMenu:Item>
                    <twig:ux:icon name="lucide:bell" class="size-4" />
                    Notifications
                </twig:DropdownMenu:Item>
            </twig:DropdownMenu:Group>
            <twig:DropdownMenu:Separator />
            <twig:DropdownMenu:Item>
                <twig:ux:icon name="lucide:log-out" class="size-4" />
                Sign Out
            </twig:DropdownMenu:Item>
        </twig:DropdownMenu:Content>
    </twig:DropdownMenu>
</div>
```

### Complex

A richer example combining groups, icons, and submenus.

```twig {"preview":true}
<div class="flex items-start justify-center pt-6" style="min-height: 640px">
    <twig:DropdownMenu id="complex">
        <twig:DropdownMenu:Trigger>
            <twig:Button variant="outline" {{ ...dropdown_menu_trigger_attrs }}>Complex Menu</twig:Button>
        </twig:DropdownMenu:Trigger>
        <twig:DropdownMenu:Content class="w-44">
            <twig:DropdownMenu:Group>
                <twig:DropdownMenu:Label>File</twig:DropdownMenu:Label>
                <twig:DropdownMenu:Item>
                    <twig:ux:icon name="lucide:file" class="size-4" />
                    New File
                    <twig:DropdownMenu:Shortcut>⌘N</twig:DropdownMenu:Shortcut>
                </twig:DropdownMenu:Item>
                <twig:DropdownMenu:Item>
                    <twig:ux:icon name="lucide:folder" class="size-4" />
                    New Folder
                    <twig:DropdownMenu:Shortcut>⇧⌘N</twig:DropdownMenu:Shortcut>
                </twig:DropdownMenu:Item>
                <twig:DropdownMenu:Sub>
                    <twig:DropdownMenu:SubTrigger>
                        <twig:ux:icon name="lucide:folder-open" class="size-4" />
                        Open Recent
                    </twig:DropdownMenu:SubTrigger>
                    <twig:DropdownMenu:SubContent>
                        <twig:DropdownMenu:Group>
                            <twig:DropdownMenu:Label>Recent Projects</twig:DropdownMenu:Label>
                            <twig:DropdownMenu:Item>
                                <twig:ux:icon name="lucide:file-code" class="size-4" />
                                Project Alpha
                            </twig:DropdownMenu:Item>
                            <twig:DropdownMenu:Item>
                                <twig:ux:icon name="lucide:file-code" class="size-4" />
                                Project Beta
                            </twig:DropdownMenu:Item>
                            <twig:DropdownMenu:Sub>
                                <twig:DropdownMenu:SubTrigger>
                                    <twig:ux:icon name="lucide:ellipsis" class="size-4" />
                                    More Projects
                                </twig:DropdownMenu:SubTrigger>
                                <twig:DropdownMenu:SubContent>
                                    <twig:DropdownMenu:Item>
                                        <twig:ux:icon name="lucide:file-code" class="size-4" />
                                        Project Gamma
                                    </twig:DropdownMenu:Item>
                                    <twig:DropdownMenu:Item>
                                        <twig:ux:icon name="lucide:file-code" class="size-4" />
                                        Project Delta
                                    </twig:DropdownMenu:Item>
                                </twig:DropdownMenu:SubContent>
                            </twig:DropdownMenu:Sub>
                        </twig:DropdownMenu:Group>
                        <twig:DropdownMenu:Separator />
                        <twig:DropdownMenu:Group>
                            <twig:DropdownMenu:Item>
                                <twig:ux:icon name="lucide:folder-search" class="size-4" />
                                Browse...
                            </twig:DropdownMenu:Item>
                        </twig:DropdownMenu:Group>
                    </twig:DropdownMenu:SubContent>
                </twig:DropdownMenu:Sub>
                <twig:DropdownMenu:Separator />
                <twig:DropdownMenu:Item>
                    <twig:ux:icon name="lucide:save" class="size-4" />
                    Save
                    <twig:DropdownMenu:Shortcut>⌘S</twig:DropdownMenu:Shortcut>
                </twig:DropdownMenu:Item>
                <twig:DropdownMenu:Item>
                    <twig:ux:icon name="lucide:download" class="size-4" />
                    Export
                    <twig:DropdownMenu:Shortcut>⇧⌘E</twig:DropdownMenu:Shortcut>
                </twig:DropdownMenu:Item>
            </twig:DropdownMenu:Group>
            <twig:DropdownMenu:Separator />
            <twig:DropdownMenu:Group>
                <twig:DropdownMenu:Label>View</twig:DropdownMenu:Label>
                <twig:DropdownMenu:CheckboxItem checked>
                    <twig:ux:icon name="lucide:eye" class="size-4" />
                    Show Sidebar
                </twig:DropdownMenu:CheckboxItem>
                <twig:DropdownMenu:CheckboxItem>
                    <twig:ux:icon name="lucide:panels-top-left" class="size-4" />
                    Show Status Bar
                </twig:DropdownMenu:CheckboxItem>
                <twig:DropdownMenu:Sub>
                    <twig:DropdownMenu:SubTrigger>
                        <twig:ux:icon name="lucide:palette" class="size-4" />
                        Theme
                    </twig:DropdownMenu:SubTrigger>
                    <twig:DropdownMenu:SubContent>
                        <twig:DropdownMenu:Group>
                            <twig:DropdownMenu:Label>Appearance</twig:DropdownMenu:Label>
                            <twig:DropdownMenu:RadioGroup value="light">
                                <twig:DropdownMenu:RadioItem value="light" checked>
                                    <twig:ux:icon name="lucide:sun" class="size-4" />
                                    Light
                                </twig:DropdownMenu:RadioItem>
                                <twig:DropdownMenu:RadioItem value="dark">
                                    <twig:ux:icon name="lucide:moon" class="size-4" />
                                    Dark
                                </twig:DropdownMenu:RadioItem>
                                <twig:DropdownMenu:RadioItem value="system">
                                    <twig:ux:icon name="lucide:monitor" class="size-4" />
                                    System
                                </twig:DropdownMenu:RadioItem>
                            </twig:DropdownMenu:RadioGroup>
                        </twig:DropdownMenu:Group>
                    </twig:DropdownMenu:SubContent>
                </twig:DropdownMenu:Sub>
            </twig:DropdownMenu:Group>
            <twig:DropdownMenu:Separator />
            <twig:DropdownMenu:Group>
                <twig:DropdownMenu:Label>Account</twig:DropdownMenu:Label>
                <twig:DropdownMenu:Item>
                    <twig:ux:icon name="lucide:user" class="size-4" />
                    Profile
                    <twig:DropdownMenu:Shortcut>⇧⌘P</twig:DropdownMenu:Shortcut>
                </twig:DropdownMenu:Item>
                <twig:DropdownMenu:Item>
                    <twig:ux:icon name="lucide:credit-card" class="size-4" />
                    Billing
                </twig:DropdownMenu:Item>
                <twig:DropdownMenu:Sub>
                    <twig:DropdownMenu:SubTrigger>
                        <twig:ux:icon name="lucide:settings" class="size-4" />
                        Settings
                    </twig:DropdownMenu:SubTrigger>
                    <twig:DropdownMenu:SubContent>
                        <twig:DropdownMenu:Group>
                            <twig:DropdownMenu:Label>Preferences</twig:DropdownMenu:Label>
                            <twig:DropdownMenu:Item>
                                <twig:ux:icon name="lucide:keyboard" class="size-4" />
                                Keyboard Shortcuts
                            </twig:DropdownMenu:Item>
                            <twig:DropdownMenu:Item>
                                <twig:ux:icon name="lucide:languages" class="size-4" />
                                Language
                            </twig:DropdownMenu:Item>
                            <twig:DropdownMenu:Sub>
                                <twig:DropdownMenu:SubTrigger>
                                    <twig:ux:icon name="lucide:bell" class="size-4" />
                                    Notifications
                                </twig:DropdownMenu:SubTrigger>
                                <twig:DropdownMenu:SubContent>
                                    <twig:DropdownMenu:Group>
                                        <twig:DropdownMenu:Label>Notification Types</twig:DropdownMenu:Label>
                                        <twig:DropdownMenu:CheckboxItem checked>
                                            <twig:ux:icon name="lucide:bell" class="size-4" />
                                            Push Notifications
                                        </twig:DropdownMenu:CheckboxItem>
                                        <twig:DropdownMenu:CheckboxItem checked>
                                            <twig:ux:icon name="lucide:mail" class="size-4" />
                                            Email Notifications
                                        </twig:DropdownMenu:CheckboxItem>
                                    </twig:DropdownMenu:Group>
                                </twig:DropdownMenu:SubContent>
                            </twig:DropdownMenu:Sub>
                        </twig:DropdownMenu:Group>
                        <twig:DropdownMenu:Separator />
                        <twig:DropdownMenu:Group>
                            <twig:DropdownMenu:Item>
                                <twig:ux:icon name="lucide:shield" class="size-4" />
                                Privacy & Security
                            </twig:DropdownMenu:Item>
                        </twig:DropdownMenu:Group>
                    </twig:DropdownMenu:SubContent>
                </twig:DropdownMenu:Sub>
            </twig:DropdownMenu:Group>
            <twig:DropdownMenu:Separator />
            <twig:DropdownMenu:Group>
                <twig:DropdownMenu:Item>
                    <twig:ux:icon name="lucide:circle-help" class="size-4" />
                    Help & Support
                </twig:DropdownMenu:Item>
                <twig:DropdownMenu:Item>
                    <twig:ux:icon name="lucide:file-text" class="size-4" />
                    Documentation
                </twig:DropdownMenu:Item>
            </twig:DropdownMenu:Group>
            <twig:DropdownMenu:Separator />
            <twig:DropdownMenu:Group>
                <twig:DropdownMenu:Item variant="destructive">
                    <twig:ux:icon name="lucide:log-out" class="size-4" />
                    Sign Out
                    <twig:DropdownMenu:Shortcut>⇧⌘Q</twig:DropdownMenu:Shortcut>
                </twig:DropdownMenu:Item>
            </twig:DropdownMenu:Group>
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

## Accessibility

- `DropdownMenu:Content` renders `role="menu"` and its entries `role="menuitem"`, `role="menuitemcheckbox"` or `role="menuitemradio"`, with `aria-checked` kept in sync by the controller.
- `DropdownMenu:Trigger` exposes `aria-haspopup="menu"`, an `aria-expanded` reflecting the open state and an `aria-controls` pointing at the menu.
- `ArrowDown` and `ArrowUp` move between items and wrap around, `Home` and `End` jump to the first and last, and `Escape` closes the menu and returns focus to the trigger. Disabled items are skipped.
- `DropdownMenu:Separator` renders `role="separator"`, and `DropdownMenu:Group` renders `role="group"`. Label a group with `aria-labelledby` pointing at its `DropdownMenu:Label`.
- A disabled item renders `aria-disabled="true"` rather than being removed, so its presence stays discoverable.
- `DropdownMenu:Shortcut` shows the key combination visually. It is announced as part of the item text, so keep it short.

## API Reference

::: api-reference
