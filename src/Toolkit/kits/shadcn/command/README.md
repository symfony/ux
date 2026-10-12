# Command

Command menu for search and quick actions.

```twig {"preview":true}
<div class="flex w-full max-w-sm flex-col" style="min-height: 392px">
    <twig:Command class="max-w-sm rounded-lg border">
        <twig:Command:Input placeholder="Type a command or search..." />
        <twig:Command:List>
            <twig:Command:Empty>No results found.</twig:Command:Empty>
            <twig:Command:Group heading="Suggestions">
                <twig:Command:Item>
                    <twig:ux:icon name="lucide:calendar" />
                    <span>Calendar</span>
                </twig:Command:Item>
                <twig:Command:Item>
                    <twig:ux:icon name="lucide:smile" />
                    <span>Search Emoji</span>
                </twig:Command:Item>
                <twig:Command:Item disabled>
                    <twig:ux:icon name="lucide:calculator" />
                    <span>Calculator</span>
                </twig:Command:Item>
            </twig:Command:Group>
            <twig:Command:Separator />
            <twig:Command:Group heading="Settings">
                <twig:Command:Item>
                    <twig:ux:icon name="lucide:user" />
                    <span>Profile</span>
                    <twig:Command:Shortcut>⌘P</twig:Command:Shortcut>
                </twig:Command:Item>
                <twig:Command:Item>
                    <twig:ux:icon name="lucide:credit-card" />
                    <span>Billing</span>
                    <twig:Command:Shortcut>⌘B</twig:Command:Shortcut>
                </twig:Command:Item>
                <twig:Command:Item>
                    <twig:ux:icon name="lucide:settings" />
                    <span>Settings</span>
                    <twig:Command:Shortcut>⌘S</twig:Command:Shortcut>
                </twig:Command:Item>
            </twig:Command:Group>
        </twig:Command:List>
    </twig:Command>
</div>
```

## Installation

::: installation

## Usage

```twig
<twig:Command class="max-w-sm rounded-lg border">
    <twig:Command:Input placeholder="Type a command or search..." />
    <twig:Command:List>
        <twig:Command:Empty>No results found.</twig:Command:Empty>
        <twig:Command:Group heading="Suggestions">
            <twig:Command:Item>Calendar</twig:Command:Item>
            <twig:Command:Item>Search Emoji</twig:Command:Item>
            <twig:Command:Item>Calculator</twig:Command:Item>
        </twig:Command:Group>
        <twig:Command:Separator />
        <twig:Command:Group heading="Settings">
            <twig:Command:Item>Profile</twig:Command:Item>
            <twig:Command:Item>Billing</twig:Command:Item>
            <twig:Command:Item>Settings</twig:Command:Item>
        </twig:Command:Group>
    </twig:Command:List>
</twig:Command>
```

To run an action when an item is chosen, with a click or with `Enter`, listen to the `click` event of the item, or render the item as a link with `as="a"`.

## Examples

### Basic

A simple command menu in a dialog.

```twig {"preview":true}
<div class="flex flex-col gap-4" style="min-height: 300px">
    <twig:Dialog id="command-basic">
        <twig:Dialog:Trigger>
            <twig:Button variant="outline" class="w-fit" {{ ...dialog_trigger_attrs }}>Open Menu</twig:Button>
        </twig:Dialog:Trigger>
        <twig:Command:Dialog>
            <twig:Command>
                <twig:Command:Input placeholder="Type a command or search..." />
                <twig:Command:List>
                    <twig:Command:Empty>No results found.</twig:Command:Empty>
                    <twig:Command:Group heading="Suggestions">
                        <twig:Command:Item>Calendar</twig:Command:Item>
                        <twig:Command:Item>Search Emoji</twig:Command:Item>
                        <twig:Command:Item>Calculator</twig:Command:Item>
                    </twig:Command:Group>
                </twig:Command:List>
            </twig:Command>
        </twig:Command:Dialog>
    </twig:Dialog>
</div>
```

### Shortcuts

```twig {"preview":true}
<div class="flex flex-col gap-4" style="min-height: 300px">
    <twig:Dialog id="command-shortcuts">
        <twig:Dialog:Trigger>
            <twig:Button variant="outline" class="w-fit" {{ ...dialog_trigger_attrs }}>Open Menu</twig:Button>
        </twig:Dialog:Trigger>
        <twig:Command:Dialog>
            <twig:Command>
                <twig:Command:Input placeholder="Type a command or search..." />
                <twig:Command:List>
                    <twig:Command:Empty>No results found.</twig:Command:Empty>
                    <twig:Command:Group heading="Settings">
                        <twig:Command:Item>
                            <twig:ux:icon name="lucide:user" />
                            <span>Profile</span>
                            <twig:Command:Shortcut>⌘P</twig:Command:Shortcut>
                        </twig:Command:Item>
                        <twig:Command:Item>
                            <twig:ux:icon name="lucide:credit-card" />
                            <span>Billing</span>
                            <twig:Command:Shortcut>⌘B</twig:Command:Shortcut>
                        </twig:Command:Item>
                        <twig:Command:Item>
                            <twig:ux:icon name="lucide:settings" />
                            <span>Settings</span>
                            <twig:Command:Shortcut>⌘S</twig:Command:Shortcut>
                        </twig:Command:Item>
                    </twig:Command:Group>
                </twig:Command:List>
            </twig:Command>
        </twig:Command:Dialog>
    </twig:Dialog>
</div>
```

### Groups

A command menu with groups, icons and separators.

```twig {"preview":true}
<div class="flex flex-col gap-4" style="min-height: 470px">
    <twig:Dialog id="command-groups">
        <twig:Dialog:Trigger>
            <twig:Button variant="outline" class="w-fit" {{ ...dialog_trigger_attrs }}>Open Menu</twig:Button>
        </twig:Dialog:Trigger>
        <twig:Command:Dialog>
            <twig:Command>
                <twig:Command:Input placeholder="Type a command or search..." />
                <twig:Command:List>
                    <twig:Command:Empty>No results found.</twig:Command:Empty>
                    <twig:Command:Group heading="Suggestions">
                        <twig:Command:Item>
                            <twig:ux:icon name="lucide:calendar" />
                            <span>Calendar</span>
                        </twig:Command:Item>
                        <twig:Command:Item>
                            <twig:ux:icon name="lucide:smile" />
                            <span>Search Emoji</span>
                        </twig:Command:Item>
                        <twig:Command:Item>
                            <twig:ux:icon name="lucide:calculator" />
                            <span>Calculator</span>
                        </twig:Command:Item>
                    </twig:Command:Group>
                    <twig:Command:Separator />
                    <twig:Command:Group heading="Settings">
                        <twig:Command:Item>
                            <twig:ux:icon name="lucide:user" />
                            <span>Profile</span>
                            <twig:Command:Shortcut>⌘P</twig:Command:Shortcut>
                        </twig:Command:Item>
                        <twig:Command:Item>
                            <twig:ux:icon name="lucide:credit-card" />
                            <span>Billing</span>
                            <twig:Command:Shortcut>⌘B</twig:Command:Shortcut>
                        </twig:Command:Item>
                        <twig:Command:Item>
                            <twig:ux:icon name="lucide:settings" />
                            <span>Settings</span>
                            <twig:Command:Shortcut>⌘S</twig:Command:Shortcut>
                        </twig:Command:Item>
                    </twig:Command:Group>
                </twig:Command:List>
            </twig:Command>
        </twig:Command:Dialog>
    </twig:Dialog>
</div>
```

### Scrollable

Scrollable command menu with multiple items.

```twig {"preview":true}
<div class="flex flex-col gap-4" style="min-height: 520px">
    <twig:Dialog id="command-scrollable">
        <twig:Dialog:Trigger>
            <twig:Button variant="outline" class="w-fit" {{ ...dialog_trigger_attrs }}>Open Menu</twig:Button>
        </twig:Dialog:Trigger>
        <twig:Command:Dialog>
            <twig:Command>
                <twig:Command:Input placeholder="Type a command or search..." />
                <twig:Command:List>
                    <twig:Command:Empty>No results found.</twig:Command:Empty>
                    <twig:Command:Group heading="Navigation">
                        <twig:Command:Item>
                            <twig:ux:icon name="lucide:house" />
                            <span>Home</span>
                            <twig:Command:Shortcut>⌘H</twig:Command:Shortcut>
                        </twig:Command:Item>
                        <twig:Command:Item>
                            <twig:ux:icon name="lucide:inbox" />
                            <span>Inbox</span>
                            <twig:Command:Shortcut>⌘I</twig:Command:Shortcut>
                        </twig:Command:Item>
                        <twig:Command:Item>
                            <twig:ux:icon name="lucide:file-text" />
                            <span>Documents</span>
                            <twig:Command:Shortcut>⌘D</twig:Command:Shortcut>
                        </twig:Command:Item>
                        <twig:Command:Item>
                            <twig:ux:icon name="lucide:folder" />
                            <span>Folders</span>
                            <twig:Command:Shortcut>⌘F</twig:Command:Shortcut>
                        </twig:Command:Item>
                    </twig:Command:Group>
                    <twig:Command:Separator />
                    <twig:Command:Group heading="Actions">
                        <twig:Command:Item>
                            <twig:ux:icon name="lucide:plus" />
                            <span>New File</span>
                            <twig:Command:Shortcut>⌘N</twig:Command:Shortcut>
                        </twig:Command:Item>
                        <twig:Command:Item>
                            <twig:ux:icon name="lucide:folder-plus" />
                            <span>New Folder</span>
                            <twig:Command:Shortcut>⇧⌘N</twig:Command:Shortcut>
                        </twig:Command:Item>
                        <twig:Command:Item>
                            <twig:ux:icon name="lucide:copy" />
                            <span>Copy</span>
                            <twig:Command:Shortcut>⌘C</twig:Command:Shortcut>
                        </twig:Command:Item>
                        <twig:Command:Item>
                            <twig:ux:icon name="lucide:scissors" />
                            <span>Cut</span>
                            <twig:Command:Shortcut>⌘X</twig:Command:Shortcut>
                        </twig:Command:Item>
                        <twig:Command:Item>
                            <twig:ux:icon name="lucide:clipboard-paste" />
                            <span>Paste</span>
                            <twig:Command:Shortcut>⌘V</twig:Command:Shortcut>
                        </twig:Command:Item>
                        <twig:Command:Item>
                            <twig:ux:icon name="lucide:trash" />
                            <span>Delete</span>
                            <twig:Command:Shortcut>⌫</twig:Command:Shortcut>
                        </twig:Command:Item>
                    </twig:Command:Group>
                    <twig:Command:Separator />
                    <twig:Command:Group heading="View">
                        <twig:Command:Item>
                            <twig:ux:icon name="lucide:layout-grid" />
                            <span>Grid View</span>
                        </twig:Command:Item>
                        <twig:Command:Item>
                            <twig:ux:icon name="lucide:list" />
                            <span>List View</span>
                        </twig:Command:Item>
                        <twig:Command:Item>
                            <twig:ux:icon name="lucide:zoom-in" />
                            <span>Zoom In</span>
                            <twig:Command:Shortcut>⌘+</twig:Command:Shortcut>
                        </twig:Command:Item>
                        <twig:Command:Item>
                            <twig:ux:icon name="lucide:zoom-out" />
                            <span>Zoom Out</span>
                            <twig:Command:Shortcut>⌘-</twig:Command:Shortcut>
                        </twig:Command:Item>
                    </twig:Command:Group>
                    <twig:Command:Separator />
                    <twig:Command:Group heading="Account">
                        <twig:Command:Item>
                            <twig:ux:icon name="lucide:user" />
                            <span>Profile</span>
                            <twig:Command:Shortcut>⌘P</twig:Command:Shortcut>
                        </twig:Command:Item>
                        <twig:Command:Item>
                            <twig:ux:icon name="lucide:credit-card" />
                            <span>Billing</span>
                            <twig:Command:Shortcut>⌘B</twig:Command:Shortcut>
                        </twig:Command:Item>
                        <twig:Command:Item>
                            <twig:ux:icon name="lucide:settings" />
                            <span>Settings</span>
                            <twig:Command:Shortcut>⌘S</twig:Command:Shortcut>
                        </twig:Command:Item>
                        <twig:Command:Item>
                            <twig:ux:icon name="lucide:bell" />
                            <span>Notifications</span>
                        </twig:Command:Item>
                        <twig:Command:Item>
                            <twig:ux:icon name="lucide:circle-help" />
                            <span>Help & Support</span>
                        </twig:Command:Item>
                    </twig:Command:Group>
                    <twig:Command:Separator />
                    <twig:Command:Group heading="Tools">
                        <twig:Command:Item>
                            <twig:ux:icon name="lucide:calculator" />
                            <span>Calculator</span>
                        </twig:Command:Item>
                        <twig:Command:Item>
                            <twig:ux:icon name="lucide:calendar" />
                            <span>Calendar</span>
                        </twig:Command:Item>
                        <twig:Command:Item>
                            <twig:ux:icon name="lucide:image" />
                            <span>Image Editor</span>
                        </twig:Command:Item>
                        <twig:Command:Item>
                            <twig:ux:icon name="lucide:code" />
                            <span>Code Editor</span>
                        </twig:Command:Item>
                    </twig:Command:Group>
                </twig:Command:List>
            </twig:Command>
        </twig:Command:Dialog>
    </twig:Dialog>
</div>
```

### Links

Render an item as a link with `as="a"`: a click and `Enter` follow it.

```twig {"preview":true}
<div class="flex w-full max-w-sm flex-col">
    <twig:Command class="max-w-sm rounded-lg border">
        <twig:Command:Input placeholder="Search a page..." />
        <twig:Command:List>
            <twig:Command:Empty>No results found.</twig:Command:Empty>
            <twig:Command:Group heading="Pages">
                <twig:Command:Item as="a" href="#home">
                    <twig:ux:icon name="lucide:house" />
                    <span>Home</span>
                </twig:Command:Item>
                <twig:Command:Item as="a" href="#inbox">
                    <twig:ux:icon name="lucide:inbox" />
                    <span>Inbox</span>
                </twig:Command:Item>
                <twig:Command:Item as="a" href="#settings">
                    <twig:ux:icon name="lucide:settings" />
                    <span>Settings</span>
                </twig:Command:Item>
            </twig:Command:Group>
        </twig:Command:List>
    </twig:Command>
</div>
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

```twig {"preview":true}
<div class="flex w-full max-w-sm flex-col gap-4">
    {# Arabic #}
    <twig:Command class="max-w-sm rounded-lg border" dir="rtl">
        <twig:Command:Input placeholder="اكتب أمرًا أو ابحث..." />
        <twig:Command:List>
            <twig:Command:Empty>لم يتم العثور على نتائج.</twig:Command:Empty>
            <twig:Command:Group heading="اقتراحات">
                <twig:Command:Item>
                    <twig:ux:icon name="lucide:calendar" />
                    <span>التقويم</span>
                </twig:Command:Item>
                <twig:Command:Item>
                    <twig:ux:icon name="lucide:smile" />
                    <span>البحث عن الرموز التعبيرية</span>
                </twig:Command:Item>
                <twig:Command:Item disabled>
                    <twig:ux:icon name="lucide:calculator" />
                    <span>الآلة الحاسبة</span>
                </twig:Command:Item>
            </twig:Command:Group>
            <twig:Command:Separator />
            <twig:Command:Group heading="الإعدادات">
                <twig:Command:Item>
                    <twig:ux:icon name="lucide:user" />
                    <span>الملف الشخصي</span>
                    <twig:Command:Shortcut>⌘P</twig:Command:Shortcut>
                </twig:Command:Item>
                <twig:Command:Item>
                    <twig:ux:icon name="lucide:credit-card" />
                    <span>الفوترة</span>
                    <twig:Command:Shortcut>⌘B</twig:Command:Shortcut>
                </twig:Command:Item>
                <twig:Command:Item>
                    <twig:ux:icon name="lucide:settings" />
                    <span>الإعدادات</span>
                    <twig:Command:Shortcut>⌘S</twig:Command:Shortcut>
                </twig:Command:Item>
            </twig:Command:Group>
        </twig:Command:List>
    </twig:Command>

    {# Hebrew #}
    <twig:Command class="max-w-sm rounded-lg border" dir="rtl">
        <twig:Command:Input placeholder="הקלד פקודה או חפש..." />
        <twig:Command:List>
            <twig:Command:Empty>לא נמצאו תוצאות.</twig:Command:Empty>
            <twig:Command:Group heading="הצעות">
                <twig:Command:Item>
                    <twig:ux:icon name="lucide:calendar" />
                    <span>לוח שנה</span>
                </twig:Command:Item>
                <twig:Command:Item>
                    <twig:ux:icon name="lucide:smile" />
                    <span>חפש אמוג'י</span>
                </twig:Command:Item>
                <twig:Command:Item disabled>
                    <twig:ux:icon name="lucide:calculator" />
                    <span>מחשבון</span>
                </twig:Command:Item>
            </twig:Command:Group>
            <twig:Command:Separator />
            <twig:Command:Group heading="הגדרות">
                <twig:Command:Item>
                    <twig:ux:icon name="lucide:user" />
                    <span>פרופיל</span>
                    <twig:Command:Shortcut>⌘P</twig:Command:Shortcut>
                </twig:Command:Item>
                <twig:Command:Item>
                    <twig:ux:icon name="lucide:credit-card" />
                    <span>חיוב</span>
                    <twig:Command:Shortcut>⌘B</twig:Command:Shortcut>
                </twig:Command:Item>
                <twig:Command:Item>
                    <twig:ux:icon name="lucide:settings" />
                    <span>הגדרות</span>
                    <twig:Command:Shortcut>⌘S</twig:Command:Shortcut>
                </twig:Command:Item>
            </twig:Command:Group>
        </twig:Command:List>
    </twig:Command>
</div>
```

## Accessibility

- `Command:Input` is a combobox that controls the `Command:List` listbox. The selected item is announced through `aria-activedescendant`, so the focus stays in the input while the selection moves.
- `ArrowDown` and `ArrowUp` move the selection, `Home` and `End` jump to the first and last item, and `Alt` with an arrow jumps to the next or previous group. `Enter` clicks the selected item. Disabled items are skipped.
- `Command:Dialog` renders a `Dialog:Content` labelled by a visually hidden title and description. Set the `title` and `description` props to translate them.

## API Reference

::: api-reference
