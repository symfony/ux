# Menubar

A horizontal menu bar with dropdown menus, similar to desktop application menus.

```twig {"preview":true}
<div class="flex items-start justify-center" style="min-height: 320px">
    <twig:Menubar class="w-72">
        <twig:Menubar:Menu>
            <twig:Menubar:Trigger>
                <twig:Button variant="ghost" size="sm" {{ ...menubar_trigger_attrs }}>File</twig:Button>
            </twig:Menubar:Trigger>
            <twig:Menubar:Content>
                <twig:Menubar:Item>New Tab<twig:Menubar:Shortcut>⌘T</twig:Menubar:Shortcut></twig:Menubar:Item>
                <twig:Menubar:Item>New Window<twig:Menubar:Shortcut>⌘N</twig:Menubar:Shortcut></twig:Menubar:Item>
                <twig:Menubar:Item disabled>New Incognito Window</twig:Menubar:Item>
                <twig:Menubar:Separator />
                <twig:Menubar:Sub>
                    <twig:Menubar:SubTrigger>
                        <twig:Button variant="ghost" size="sm" class="w-full justify-start" {{ ...menubar_sub_trigger_attrs }}>
                            Share
                            <twig:ux:icon name="lucide:chevron-right" class="ms-auto size-4" aria-hidden="true" />
                        </twig:Button>
                    </twig:Menubar:SubTrigger>
                    <twig:Menubar:SubContent>
                        <twig:Menubar:Item>Email link</twig:Menubar:Item>
                        <twig:Menubar:Item>Messages</twig:Menubar:Item>
                        <twig:Menubar:Item>Notes</twig:Menubar:Item>
                    </twig:Menubar:SubContent>
                </twig:Menubar:Sub>
                <twig:Menubar:Separator />
                <twig:Menubar:Item>Print...<twig:Menubar:Shortcut>⌘P</twig:Menubar:Shortcut></twig:Menubar:Item>
            </twig:Menubar:Content>
        </twig:Menubar:Menu>
        <twig:Menubar:Menu>
            <twig:Menubar:Trigger>
                <twig:Button variant="ghost" size="sm" {{ ...menubar_trigger_attrs }}>Edit</twig:Button>
            </twig:Menubar:Trigger>
            <twig:Menubar:Content>
                <twig:Menubar:Item>Undo<twig:Menubar:Shortcut>⌘Z</twig:Menubar:Shortcut></twig:Menubar:Item>
                <twig:Menubar:Item>Redo<twig:Menubar:Shortcut>⇧⌘Z</twig:Menubar:Shortcut></twig:Menubar:Item>
                <twig:Menubar:Separator />
                <twig:Menubar:Sub>
                    <twig:Menubar:SubTrigger>
                        <twig:Button variant="ghost" size="sm" class="w-full justify-start" {{ ...menubar_sub_trigger_attrs }}>
                            Find
                            <twig:ux:icon name="lucide:chevron-right" class="ms-auto size-4" aria-hidden="true" />
                        </twig:Button>
                    </twig:Menubar:SubTrigger>
                    <twig:Menubar:SubContent>
                        <twig:Menubar:Item>Search the web</twig:Menubar:Item>
                        <twig:Menubar:Separator />
                        <twig:Menubar:Item>Find...</twig:Menubar:Item>
                        <twig:Menubar:Item>Find Next</twig:Menubar:Item>
                        <twig:Menubar:Item>Find Previous</twig:Menubar:Item>
                    </twig:Menubar:SubContent>
                </twig:Menubar:Sub>
                <twig:Menubar:Separator />
                <twig:Menubar:Item>Cut</twig:Menubar:Item>
                <twig:Menubar:Item>Copy</twig:Menubar:Item>
                <twig:Menubar:Item>Paste</twig:Menubar:Item>
            </twig:Menubar:Content>
        </twig:Menubar:Menu>
        <twig:Menubar:Menu>
            <twig:Menubar:Trigger>
                <twig:Button variant="ghost" size="sm" {{ ...menubar_trigger_attrs }}>View</twig:Button>
            </twig:Menubar:Trigger>
            <twig:Menubar:Content class="w-44">
                <twig:Menubar:CheckboxItem name="bookmarks">Bookmarks Bar</twig:Menubar:CheckboxItem>
                <twig:Menubar:CheckboxItem name="urls" checked>Full URLs</twig:Menubar:CheckboxItem>
                <twig:Menubar:Separator />
                <twig:Menubar:Item>Reload<twig:Menubar:Shortcut>⌘R</twig:Menubar:Shortcut></twig:Menubar:Item>
                <twig:Menubar:Item disabled>Force Reload<twig:Menubar:Shortcut>⇧⌘R</twig:Menubar:Shortcut></twig:Menubar:Item>
                <twig:Menubar:Separator />
                <twig:Menubar:Item>Toggle Fullscreen</twig:Menubar:Item>
                <twig:Menubar:Separator />
                <twig:Menubar:Item>Hide Sidebar</twig:Menubar:Item>
            </twig:Menubar:Content>
        </twig:Menubar:Menu>
        <twig:Menubar:Menu>
            <twig:Menubar:Trigger>
                <twig:Button variant="ghost" size="sm" {{ ...menubar_trigger_attrs }}>Profiles</twig:Button>
            </twig:Menubar:Trigger>
            <twig:Menubar:Content>
                <twig:Menubar:RadioGroup name="profile">
                    <twig:Menubar:RadioItem value="andy">Andy</twig:Menubar:RadioItem>
                    <twig:Menubar:RadioItem value="benoit" checked>Benoit</twig:Menubar:RadioItem>
                    <twig:Menubar:RadioItem value="luis">Luis</twig:Menubar:RadioItem>
                </twig:Menubar:RadioGroup>
                <twig:Menubar:Separator />
                <twig:Menubar:Item>Edit...</twig:Menubar:Item>
                <twig:Menubar:Separator />
                <twig:Menubar:Item>Add Profile...</twig:Menubar:Item>
            </twig:Menubar:Content>
        </twig:Menubar:Menu>
    </twig:Menubar>
</div>
```

## Installation

::: installation

## Usage

```twig
<twig:Menubar>
    <twig:Menubar:Menu>
        <twig:Menubar:Trigger>
            <twig:Button variant="ghost" size="sm" {{ ...menubar_trigger_attrs }}>File</twig:Button>
        </twig:Menubar:Trigger>
        <twig:Menubar:Content>
            <twig:Menubar:Item>New Tab</twig:Menubar:Item>
            <twig:Menubar:Item>New Window</twig:Menubar:Item>
        </twig:Menubar:Content>
    </twig:Menubar:Menu>
    <twig:Menubar:Menu>
        <twig:Menubar:Trigger>
            <twig:Button variant="ghost" size="sm" {{ ...menubar_trigger_attrs }}>Edit</twig:Button>
        </twig:Menubar:Trigger>
        <twig:Menubar:Content>
            <twig:Menubar:Item>Undo</twig:Menubar:Item>
            <twig:Menubar:Item>Redo</twig:Menubar:Item>
        </twig:Menubar:Content>
    </twig:Menubar:Menu>
</twig:Menubar>
```

## Examples

### Checkbox Items

Use `Menubar:CheckboxItem` for options that can be toggled on and off. Set the `checked` prop to make an item checked initially.

```twig {"preview":true}
<div class="flex items-start justify-center" style="min-height: 280px">
    <twig:Menubar class="w-72">
        <twig:Menubar:Menu>
            <twig:Menubar:Trigger>
                <twig:Button variant="ghost" size="sm" {{ ...menubar_trigger_attrs }}>View</twig:Button>
            </twig:Menubar:Trigger>
            <twig:Menubar:Content class="w-64">
                <twig:Menubar:CheckboxItem name="bookmarks-bar">Always Show Bookmarks Bar</twig:Menubar:CheckboxItem>
                <twig:Menubar:CheckboxItem name="full-urls" checked>Always Show Full URLs</twig:Menubar:CheckboxItem>
                <twig:Menubar:Separator />
                <twig:Menubar:Item>Reload<twig:Menubar:Shortcut>⌘R</twig:Menubar:Shortcut></twig:Menubar:Item>
                <twig:Menubar:Item disabled>Force Reload<twig:Menubar:Shortcut>⇧⌘R</twig:Menubar:Shortcut></twig:Menubar:Item>
            </twig:Menubar:Content>
        </twig:Menubar:Menu>
        <twig:Menubar:Menu>
            <twig:Menubar:Trigger>
                <twig:Button variant="ghost" size="sm" {{ ...menubar_trigger_attrs }}>Format</twig:Button>
            </twig:Menubar:Trigger>
            <twig:Menubar:Content>
                <twig:Menubar:CheckboxItem name="strikethrough" checked>Strikethrough</twig:Menubar:CheckboxItem>
                <twig:Menubar:CheckboxItem name="code">Code</twig:Menubar:CheckboxItem>
                <twig:Menubar:CheckboxItem name="superscript">Superscript</twig:Menubar:CheckboxItem>
            </twig:Menubar:Content>
        </twig:Menubar:Menu>
    </twig:Menubar>
</div>
```

### Radio Group

Wrap `Menubar:RadioItem` components in a `Menubar:RadioGroup` so only one can be selected at a time. The group's `name` is shared with every item, so you only set it once.

```twig {"preview":true}
<div class="flex items-start justify-center" style="min-height: 240px">
    <twig:Menubar class="w-72">
        <twig:Menubar:Menu>
            <twig:Menubar:Trigger>
                <twig:Button variant="ghost" size="sm" {{ ...menubar_trigger_attrs }}>Profiles</twig:Button>
            </twig:Menubar:Trigger>
            <twig:Menubar:Content>
                <twig:Menubar:RadioGroup name="profile">
                    <twig:Menubar:RadioItem value="andy">Andy</twig:Menubar:RadioItem>
                    <twig:Menubar:RadioItem value="benoit" checked>Benoit</twig:Menubar:RadioItem>
                    <twig:Menubar:RadioItem value="luis">Luis</twig:Menubar:RadioItem>
                </twig:Menubar:RadioGroup>
                <twig:Menubar:Separator />
                <twig:Menubar:Item>Edit...</twig:Menubar:Item>
                <twig:Menubar:Item>Add Profile...</twig:Menubar:Item>
            </twig:Menubar:Content>
        </twig:Menubar:Menu>
        <twig:Menubar:Menu>
            <twig:Menubar:Trigger>
                <twig:Button variant="ghost" size="sm" {{ ...menubar_trigger_attrs }}>Theme</twig:Button>
            </twig:Menubar:Trigger>
            <twig:Menubar:Content>
                <twig:Menubar:RadioGroup name="theme">
                    <twig:Menubar:RadioItem value="light">Light</twig:Menubar:RadioItem>
                    <twig:Menubar:RadioItem value="dark">Dark</twig:Menubar:RadioItem>
                    <twig:Menubar:RadioItem value="system" checked>System</twig:Menubar:RadioItem>
                </twig:Menubar:RadioGroup>
            </twig:Menubar:Content>
        </twig:Menubar:Menu>
    </twig:Menubar>
</div>
```

### Submenus

Nest a `Menubar:Sub` inside a `Menubar:Content` to build a submenu, with its own `Menubar:SubTrigger` and `Menubar:SubContent`.

```twig {"preview":true}
<div class="flex items-start justify-center" style="min-height: 320px">
    <twig:Menubar class="w-72">
        <twig:Menubar:Menu>
            <twig:Menubar:Trigger>
                <twig:Button variant="ghost" size="sm" {{ ...menubar_trigger_attrs }}>File</twig:Button>
            </twig:Menubar:Trigger>
            <twig:Menubar:Content>
                <twig:Menubar:Sub>
                    <twig:Menubar:SubTrigger>
                        <twig:Button variant="ghost" size="sm" class="w-full justify-start" {{ ...menubar_sub_trigger_attrs }}>
                            Share
                            <twig:ux:icon name="lucide:chevron-right" class="ms-auto size-4" aria-hidden="true" />
                        </twig:Button>
                    </twig:Menubar:SubTrigger>
                    <twig:Menubar:SubContent>
                        <twig:Menubar:Item>Email link</twig:Menubar:Item>
                        <twig:Menubar:Item>Messages</twig:Menubar:Item>
                        <twig:Menubar:Item>Notes</twig:Menubar:Item>
                    </twig:Menubar:SubContent>
                </twig:Menubar:Sub>
                <twig:Menubar:Separator />
                <twig:Menubar:Item>Print...<twig:Menubar:Shortcut>⌘P</twig:Menubar:Shortcut></twig:Menubar:Item>
            </twig:Menubar:Content>
        </twig:Menubar:Menu>
        <twig:Menubar:Menu>
            <twig:Menubar:Trigger>
                <twig:Button variant="ghost" size="sm" {{ ...menubar_trigger_attrs }}>Edit</twig:Button>
            </twig:Menubar:Trigger>
            <twig:Menubar:Content>
                <twig:Menubar:Item>Undo<twig:Menubar:Shortcut>⌘Z</twig:Menubar:Shortcut></twig:Menubar:Item>
                <twig:Menubar:Item>Redo<twig:Menubar:Shortcut>⇧⌘Z</twig:Menubar:Shortcut></twig:Menubar:Item>
                <twig:Menubar:Separator />
                <twig:Menubar:Sub>
                    <twig:Menubar:SubTrigger>
                        <twig:Button variant="ghost" size="sm" class="w-full justify-start" {{ ...menubar_sub_trigger_attrs }}>
                            Find
                            <twig:ux:icon name="lucide:chevron-right" class="ms-auto size-4" aria-hidden="true" />
                        </twig:Button>
                    </twig:Menubar:SubTrigger>
                    <twig:Menubar:SubContent>
                        <twig:Menubar:Item>Find...</twig:Menubar:Item>
                        <twig:Menubar:Item>Find Next</twig:Menubar:Item>
                        <twig:Menubar:Item>Find Previous</twig:Menubar:Item>
                    </twig:Menubar:SubContent>
                </twig:Menubar:Sub>
                <twig:Menubar:Separator />
                <twig:Menubar:Item>Cut</twig:Menubar:Item>
                <twig:Menubar:Item>Copy</twig:Menubar:Item>
                <twig:Menubar:Item>Paste</twig:Menubar:Item>
            </twig:Menubar:Content>
        </twig:Menubar:Menu>
    </twig:Menubar>
</div>
```

### With Icons

Add a `ux:icon` inside a `Menubar:Item` to pair each entry with an icon.

```twig {"preview":true}
<div class="flex items-start justify-center" style="min-height: 200px">
    <twig:Menubar class="w-72">
        <twig:Menubar:Menu>
            <twig:Menubar:Trigger>
                <twig:Button variant="ghost" size="sm" {{ ...menubar_trigger_attrs }}>File</twig:Button>
            </twig:Menubar:Trigger>
            <twig:Menubar:Content>
                <twig:Menubar:Item>
                    <twig:ux:icon name="lucide:file" class="me-2 size-4" />
                    New File<twig:Menubar:Shortcut>⌘N</twig:Menubar:Shortcut>
                </twig:Menubar:Item>
                <twig:Menubar:Item>
                    <twig:ux:icon name="lucide:folder" class="me-2 size-4" />
                    Open Folder
                </twig:Menubar:Item>
                <twig:Menubar:Separator />
                <twig:Menubar:Item>
                    <twig:ux:icon name="lucide:save" class="me-2 size-4" />
                    Save<twig:Menubar:Shortcut>⌘S</twig:Menubar:Shortcut>
                </twig:Menubar:Item>
            </twig:Menubar:Content>
        </twig:Menubar:Menu>
        <twig:Menubar:Menu>
            <twig:Menubar:Trigger>
                <twig:Button variant="ghost" size="sm" {{ ...menubar_trigger_attrs }}>Edit</twig:Button>
            </twig:Menubar:Trigger>
            <twig:Menubar:Content>
                <twig:Menubar:Item>
                    <twig:ux:icon name="lucide:settings" class="me-2 size-4" />
                    Preferences
                </twig:Menubar:Item>
                <twig:Menubar:Item>
                    <twig:ux:icon name="lucide:trash" class="me-2 size-4" />
                    Delete
                </twig:Menubar:Item>
                <twig:Menubar:Separator />
                <twig:Menubar:Item>
                    <twig:ux:icon name="lucide:help-circle" class="me-2 size-4" />
                    Help
                </twig:Menubar:Item>
            </twig:Menubar:Content>
        </twig:Menubar:Menu>
    </twig:Menubar>
</div>
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

```twig {"preview":true}
<div class="flex flex-col items-center gap-6" style="min-height: 480px">
    {# Arabic #}
    <div dir="rtl">
        <twig:Menubar class="w-72">
            <twig:Menubar:Menu>
                <twig:Menubar:Trigger>
                    <twig:Button variant="ghost" size="sm" {{ ...menubar_trigger_attrs }}>ملف</twig:Button>
                </twig:Menubar:Trigger>
                <twig:Menubar:Content>
                    <twig:Menubar:Item>علامة تبويب جديدة<twig:Menubar:Shortcut>⌘T</twig:Menubar:Shortcut></twig:Menubar:Item>
                    <twig:Menubar:Item>نافذة جديدة<twig:Menubar:Shortcut>⌘N</twig:Menubar:Shortcut></twig:Menubar:Item>
                    <twig:Menubar:Item disabled>نافذة التصفح المتخفي الجديدة</twig:Menubar:Item>
                    <twig:Menubar:Separator />
                    <twig:Menubar:Sub>
                        <twig:Menubar:SubTrigger>
                            <twig:Button variant="ghost" size="sm" class="w-full justify-start" {{ ...menubar_sub_trigger_attrs }}>
                                مشاركة
                                <twig:ux:icon name="lucide:chevron-right" class="ms-auto size-4 rtl:rotate-180" aria-hidden="true" />
                            </twig:Button>
                        </twig:Menubar:SubTrigger>
                        <twig:Menubar:SubContent>
                            <twig:Menubar:Item>رابط البريد الإلكتروني</twig:Menubar:Item>
                            <twig:Menubar:Item>الرسائل</twig:Menubar:Item>
                            <twig:Menubar:Item>الملاحظات</twig:Menubar:Item>
                        </twig:Menubar:SubContent>
                    </twig:Menubar:Sub>
                    <twig:Menubar:Separator />
                    <twig:Menubar:Item>طباعة...<twig:Menubar:Shortcut>⌘P</twig:Menubar:Shortcut></twig:Menubar:Item>
                </twig:Menubar:Content>
            </twig:Menubar:Menu>
            <twig:Menubar:Menu>
                <twig:Menubar:Trigger>
                    <twig:Button variant="ghost" size="sm" {{ ...menubar_trigger_attrs }}>تعديل</twig:Button>
                </twig:Menubar:Trigger>
                <twig:Menubar:Content>
                    <twig:Menubar:Item>تراجع<twig:Menubar:Shortcut>⌘Z</twig:Menubar:Shortcut></twig:Menubar:Item>
                    <twig:Menubar:Item>إعادة<twig:Menubar:Shortcut>⇧⌘Z</twig:Menubar:Shortcut></twig:Menubar:Item>
                    <twig:Menubar:Separator />
                    <twig:Menubar:Sub>
                        <twig:Menubar:SubTrigger>
                            <twig:Button variant="ghost" size="sm" class="w-full justify-start" {{ ...menubar_sub_trigger_attrs }}>
                                بحث
                                <twig:ux:icon name="lucide:chevron-right" class="ms-auto size-4 rtl:rotate-180" aria-hidden="true" />
                            </twig:Button>
                        </twig:Menubar:SubTrigger>
                        <twig:Menubar:SubContent>
                            <twig:Menubar:Item>البحث على الويب</twig:Menubar:Item>
                            <twig:Menubar:Separator />
                            <twig:Menubar:Item>بحث...</twig:Menubar:Item>
                            <twig:Menubar:Item>البحث التالي</twig:Menubar:Item>
                            <twig:Menubar:Item>البحث السابق</twig:Menubar:Item>
                        </twig:Menubar:SubContent>
                    </twig:Menubar:Sub>
                    <twig:Menubar:Separator />
                    <twig:Menubar:Item>قص</twig:Menubar:Item>
                    <twig:Menubar:Item>نسخ</twig:Menubar:Item>
                    <twig:Menubar:Item>لصق</twig:Menubar:Item>
                </twig:Menubar:Content>
            </twig:Menubar:Menu>
            <twig:Menubar:Menu>
                <twig:Menubar:Trigger>
                    <twig:Button variant="ghost" size="sm" {{ ...menubar_trigger_attrs }}>عرض</twig:Button>
                </twig:Menubar:Trigger>
                <twig:Menubar:Content class="w-44">
                    <twig:Menubar:CheckboxItem name="ar-bookmarks">شريط الإشارات المرجعية</twig:Menubar:CheckboxItem>
                    <twig:Menubar:CheckboxItem name="ar-urls" checked>عناوين URL الكاملة</twig:Menubar:CheckboxItem>
                    <twig:Menubar:Separator />
                    <twig:Menubar:Item>إعادة تحميل<twig:Menubar:Shortcut>⌘R</twig:Menubar:Shortcut></twig:Menubar:Item>
                    <twig:Menubar:Item disabled>إعادة تحميل قسري<twig:Menubar:Shortcut>⇧⌘R</twig:Menubar:Shortcut></twig:Menubar:Item>
                    <twig:Menubar:Separator />
                    <twig:Menubar:Item>تبديل وضع ملء الشاشة</twig:Menubar:Item>
                    <twig:Menubar:Separator />
                    <twig:Menubar:Item>إخفاء الشريط الجانبي</twig:Menubar:Item>
                </twig:Menubar:Content>
            </twig:Menubar:Menu>
            <twig:Menubar:Menu>
                <twig:Menubar:Trigger>
                    <twig:Button variant="ghost" size="sm" {{ ...menubar_trigger_attrs }}>الملفات الشخصية</twig:Button>
                </twig:Menubar:Trigger>
                <twig:Menubar:Content>
                    <twig:Menubar:RadioGroup name="ar-profile">
                        <twig:Menubar:RadioItem value="andy">Andy</twig:Menubar:RadioItem>
                        <twig:Menubar:RadioItem value="benoit" checked>Benoit</twig:Menubar:RadioItem>
                        <twig:Menubar:RadioItem value="luis">Luis</twig:Menubar:RadioItem>
                    </twig:Menubar:RadioGroup>
                    <twig:Menubar:Separator />
                    <twig:Menubar:Item>تعديل...</twig:Menubar:Item>
                    <twig:Menubar:Separator />
                    <twig:Menubar:Item>إضافة ملف شخصي...</twig:Menubar:Item>
                </twig:Menubar:Content>
            </twig:Menubar:Menu>
        </twig:Menubar>
    </div>

    {# Hebrew #}
    <div dir="rtl">
        <twig:Menubar class="w-72">
            <twig:Menubar:Menu>
                <twig:Menubar:Trigger>
                    <twig:Button variant="ghost" size="sm" {{ ...menubar_trigger_attrs }}>קובץ</twig:Button>
                </twig:Menubar:Trigger>
                <twig:Menubar:Content>
                    <twig:Menubar:Item>כרטיסייה חדשה<twig:Menubar:Shortcut>⌘T</twig:Menubar:Shortcut></twig:Menubar:Item>
                    <twig:Menubar:Item>חלון חדש<twig:Menubar:Shortcut>⌘N</twig:Menubar:Shortcut></twig:Menubar:Item>
                    <twig:Menubar:Item disabled>חלון גלישה בסתר חדש</twig:Menubar:Item>
                    <twig:Menubar:Separator />
                    <twig:Menubar:Sub>
                        <twig:Menubar:SubTrigger>
                            <twig:Button variant="ghost" size="sm" class="w-full justify-start" {{ ...menubar_sub_trigger_attrs }}>
                                שתף
                                <twig:ux:icon name="lucide:chevron-right" class="ms-auto size-4 rtl:rotate-180" aria-hidden="true" />
                            </twig:Button>
                        </twig:Menubar:SubTrigger>
                        <twig:Menubar:SubContent>
                            <twig:Menubar:Item>קישור אימייל</twig:Menubar:Item>
                            <twig:Menubar:Item>הודעות</twig:Menubar:Item>
                            <twig:Menubar:Item>הערות</twig:Menubar:Item>
                        </twig:Menubar:SubContent>
                    </twig:Menubar:Sub>
                    <twig:Menubar:Separator />
                    <twig:Menubar:Item>הדפס...<twig:Menubar:Shortcut>⌘P</twig:Menubar:Shortcut></twig:Menubar:Item>
                </twig:Menubar:Content>
            </twig:Menubar:Menu>
            <twig:Menubar:Menu>
                <twig:Menubar:Trigger>
                    <twig:Button variant="ghost" size="sm" {{ ...menubar_trigger_attrs }}>ערוך</twig:Button>
                </twig:Menubar:Trigger>
                <twig:Menubar:Content>
                    <twig:Menubar:Item>בטל<twig:Menubar:Shortcut>⌘Z</twig:Menubar:Shortcut></twig:Menubar:Item>
                    <twig:Menubar:Item>בצע שוב<twig:Menubar:Shortcut>⇧⌘Z</twig:Menubar:Shortcut></twig:Menubar:Item>
                    <twig:Menubar:Separator />
                    <twig:Menubar:Sub>
                        <twig:Menubar:SubTrigger>
                            <twig:Button variant="ghost" size="sm" class="w-full justify-start" {{ ...menubar_sub_trigger_attrs }}>
                                מצא
                                <twig:ux:icon name="lucide:chevron-right" class="ms-auto size-4 rtl:rotate-180" aria-hidden="true" />
                            </twig:Button>
                        </twig:Menubar:SubTrigger>
                        <twig:Menubar:SubContent>
                            <twig:Menubar:Item>חפש באינטרנט</twig:Menubar:Item>
                            <twig:Menubar:Separator />
                            <twig:Menubar:Item>מצא...</twig:Menubar:Item>
                            <twig:Menubar:Item>מצא הבא</twig:Menubar:Item>
                            <twig:Menubar:Item>מצא הקודם</twig:Menubar:Item>
                        </twig:Menubar:SubContent>
                    </twig:Menubar:Sub>
                    <twig:Menubar:Separator />
                    <twig:Menubar:Item>גזור</twig:Menubar:Item>
                    <twig:Menubar:Item>העתק</twig:Menubar:Item>
                    <twig:Menubar:Item>הדבק</twig:Menubar:Item>
                </twig:Menubar:Content>
            </twig:Menubar:Menu>
            <twig:Menubar:Menu>
                <twig:Menubar:Trigger>
                    <twig:Button variant="ghost" size="sm" {{ ...menubar_trigger_attrs }}>תצוגה</twig:Button>
                </twig:Menubar:Trigger>
                <twig:Menubar:Content class="w-44">
                    <twig:Menubar:CheckboxItem name="he-bookmarks">סרגל סימניות</twig:Menubar:CheckboxItem>
                    <twig:Menubar:CheckboxItem name="he-urls" checked>כתובות URL מלאות</twig:Menubar:CheckboxItem>
                    <twig:Menubar:Separator />
                    <twig:Menubar:Item>רענן<twig:Menubar:Shortcut>⌘R</twig:Menubar:Shortcut></twig:Menubar:Item>
                    <twig:Menubar:Item disabled>רענן בכוח<twig:Menubar:Shortcut>⇧⌘R</twig:Menubar:Shortcut></twig:Menubar:Item>
                    <twig:Menubar:Separator />
                    <twig:Menubar:Item>החלף מסך מלא</twig:Menubar:Item>
                    <twig:Menubar:Separator />
                    <twig:Menubar:Item>הסתר סרגל צד</twig:Menubar:Item>
                </twig:Menubar:Content>
            </twig:Menubar:Menu>
            <twig:Menubar:Menu>
                <twig:Menubar:Trigger>
                    <twig:Button variant="ghost" size="sm" {{ ...menubar_trigger_attrs }}>פרופילים</twig:Button>
                </twig:Menubar:Trigger>
                <twig:Menubar:Content>
                    <twig:Menubar:RadioGroup name="he-profile">
                        <twig:Menubar:RadioItem value="andy">Andy</twig:Menubar:RadioItem>
                        <twig:Menubar:RadioItem value="benoit" checked>Benoit</twig:Menubar:RadioItem>
                        <twig:Menubar:RadioItem value="luis">Luis</twig:Menubar:RadioItem>
                    </twig:Menubar:RadioGroup>
                    <twig:Menubar:Separator />
                    <twig:Menubar:Item>ערוך...</twig:Menubar:Item>
                    <twig:Menubar:Separator />
                    <twig:Menubar:Item>הוסף פרופיל...</twig:Menubar:Item>
                </twig:Menubar:Content>
            </twig:Menubar:Menu>
        </twig:Menubar>
    </div>
</div>
```

## API Reference

::: api-reference
