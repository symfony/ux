# Context Menu

Displays a menu of actions triggered by a right click.

```twig {"preview":true,"height":"260px"}
<twig:ContextMenu>
    <twig:ContextMenu:Trigger class="flex aspect-video w-full max-w-xs items-center justify-center rounded-xl border border-dashed text-sm">
        <span class="hidden pointer-fine:inline-block">Right click here</span>
        <span class="hidden pointer-coarse:inline-block">Long press here</span>
    </twig:ContextMenu:Trigger>
    <twig:ContextMenu:Content class="w-48">
        <twig:ContextMenu:Group>
            <twig:ContextMenu:Item>
                Back
                <twig:ContextMenu:Shortcut>⌘[</twig:ContextMenu:Shortcut>
            </twig:ContextMenu:Item>
            <twig:ContextMenu:Item disabled>
                Forward
                <twig:ContextMenu:Shortcut>⌘]</twig:ContextMenu:Shortcut>
            </twig:ContextMenu:Item>
            <twig:ContextMenu:Item>
                Reload
                <twig:ContextMenu:Shortcut>⌘R</twig:ContextMenu:Shortcut>
            </twig:ContextMenu:Item>
            <twig:ContextMenu:Sub>
                <twig:ContextMenu:SubTrigger>More Tools</twig:ContextMenu:SubTrigger>
                <twig:ContextMenu:SubContent class="w-44">
                    <twig:ContextMenu:Group>
                        <twig:ContextMenu:Item>Save Page...</twig:ContextMenu:Item>
                        <twig:ContextMenu:Item>Create Shortcut...</twig:ContextMenu:Item>
                        <twig:ContextMenu:Item>Name Window...</twig:ContextMenu:Item>
                    </twig:ContextMenu:Group>
                    <twig:ContextMenu:Separator />
                    <twig:ContextMenu:Group>
                        <twig:ContextMenu:Item>Developer Tools</twig:ContextMenu:Item>
                    </twig:ContextMenu:Group>
                    <twig:ContextMenu:Separator />
                    <twig:ContextMenu:Group>
                        <twig:ContextMenu:Item variant="destructive">Delete</twig:ContextMenu:Item>
                    </twig:ContextMenu:Group>
                </twig:ContextMenu:SubContent>
            </twig:ContextMenu:Sub>
        </twig:ContextMenu:Group>
        <twig:ContextMenu:Separator />
        <twig:ContextMenu:Group>
            <twig:ContextMenu:CheckboxItem checked>Show Bookmarks</twig:ContextMenu:CheckboxItem>
            <twig:ContextMenu:CheckboxItem>Show Full URLs</twig:ContextMenu:CheckboxItem>
        </twig:ContextMenu:Group>
        <twig:ContextMenu:Separator />
        <twig:ContextMenu:Group>
            <twig:ContextMenu:RadioGroup value="pedro">
                <twig:ContextMenu:Label>People</twig:ContextMenu:Label>
                <twig:ContextMenu:RadioItem value="pedro" checked>Pedro Duarte</twig:ContextMenu:RadioItem>
                <twig:ContextMenu:RadioItem value="colm">Colm Tuite</twig:ContextMenu:RadioItem>
            </twig:ContextMenu:RadioGroup>
        </twig:ContextMenu:Group>
    </twig:ContextMenu:Content>
</twig:ContextMenu>
```

## Installation

::: installation

## Usage

```twig
<twig:ContextMenu>
    <twig:ContextMenu:Trigger class="flex h-40 items-center justify-center rounded-xl border border-dashed">
        Right click here
    </twig:ContextMenu:Trigger>
    <twig:ContextMenu:Content side="bottom">
        <twig:ContextMenu:Item>Back</twig:ContextMenu:Item>
        <twig:ContextMenu:Item>Reload</twig:ContextMenu:Item>
        <twig:ContextMenu:Separator />
        <twig:ContextMenu:Item variant="destructive">Delete</twig:ContextMenu:Item>
    </twig:ContextMenu:Content>
</twig:ContextMenu>
```

## Examples

### Basic

```twig {"preview":true,"height":"240px"}
<twig:ContextMenu>
    <twig:ContextMenu:Trigger class="flex aspect-video w-full max-w-xs items-center justify-center rounded-xl border border-dashed text-sm">
        <span class="hidden pointer-fine:inline-block">Right click here</span>
        <span class="hidden pointer-coarse:inline-block">Long press here</span>
    </twig:ContextMenu:Trigger>
    <twig:ContextMenu:Content>
        <twig:ContextMenu:Group>
            <twig:ContextMenu:Item>Back</twig:ContextMenu:Item>
            <twig:ContextMenu:Item disabled>Forward</twig:ContextMenu:Item>
            <twig:ContextMenu:Item>Reload</twig:ContextMenu:Item>
        </twig:ContextMenu:Group>
    </twig:ContextMenu:Content>
</twig:ContextMenu>
```

### Submenu

Use `ContextMenu:Sub` to nest secondary actions.

```twig {"preview":true,"height":"260px"}
<twig:ContextMenu>
    <twig:ContextMenu:Trigger class="flex aspect-video w-full max-w-xs items-center justify-center rounded-xl border border-dashed text-sm">
        <span class="hidden pointer-fine:inline-block">Right click here</span>
        <span class="hidden pointer-coarse:inline-block">Long press here</span>
    </twig:ContextMenu:Trigger>
    <twig:ContextMenu:Content>
        <twig:ContextMenu:Group>
            <twig:ContextMenu:Item>
                Copy
                <twig:ContextMenu:Shortcut>⌘C</twig:ContextMenu:Shortcut>
            </twig:ContextMenu:Item>
            <twig:ContextMenu:Item>
                Cut
                <twig:ContextMenu:Shortcut>⌘X</twig:ContextMenu:Shortcut>
            </twig:ContextMenu:Item>
        </twig:ContextMenu:Group>
        <twig:ContextMenu:Sub>
            <twig:ContextMenu:SubTrigger>More Tools</twig:ContextMenu:SubTrigger>
            <twig:ContextMenu:SubContent>
                <twig:ContextMenu:Group>
                    <twig:ContextMenu:Item>Save Page...</twig:ContextMenu:Item>
                    <twig:ContextMenu:Item>Create Shortcut...</twig:ContextMenu:Item>
                    <twig:ContextMenu:Item>Name Window...</twig:ContextMenu:Item>
                </twig:ContextMenu:Group>
                <twig:ContextMenu:Separator />
                <twig:ContextMenu:Group>
                    <twig:ContextMenu:Item>Developer Tools</twig:ContextMenu:Item>
                </twig:ContextMenu:Group>
                <twig:ContextMenu:Separator />
                <twig:ContextMenu:Group>
                    <twig:ContextMenu:Item variant="destructive">Delete</twig:ContextMenu:Item>
                </twig:ContextMenu:Group>
            </twig:ContextMenu:SubContent>
        </twig:ContextMenu:Sub>
    </twig:ContextMenu:Content>
</twig:ContextMenu>
```

### Shortcuts

Add `ContextMenu:Shortcut` to show keyboard hints.

```twig {"preview":true,"height":"240px"}
<twig:ContextMenu>
    <twig:ContextMenu:Trigger class="flex aspect-video w-full max-w-xs items-center justify-center rounded-xl border border-dashed text-sm">
        <span class="hidden pointer-fine:inline-block">Right click here</span>
        <span class="hidden pointer-coarse:inline-block">Long press here</span>
    </twig:ContextMenu:Trigger>
    <twig:ContextMenu:Content>
        <twig:ContextMenu:Group>
            <twig:ContextMenu:Item>
                Back
                <twig:ContextMenu:Shortcut>⌘[</twig:ContextMenu:Shortcut>
            </twig:ContextMenu:Item>
            <twig:ContextMenu:Item disabled>
                Forward
                <twig:ContextMenu:Shortcut>⌘]</twig:ContextMenu:Shortcut>
            </twig:ContextMenu:Item>
            <twig:ContextMenu:Item>
                Reload
                <twig:ContextMenu:Shortcut>⌘R</twig:ContextMenu:Shortcut>
            </twig:ContextMenu:Item>
        </twig:ContextMenu:Group>
        <twig:ContextMenu:Separator />
        <twig:ContextMenu:Group>
            <twig:ContextMenu:Item>
                Save
                <twig:ContextMenu:Shortcut>⌘S</twig:ContextMenu:Shortcut>
            </twig:ContextMenu:Item>
            <twig:ContextMenu:Item>
                Save As...
                <twig:ContextMenu:Shortcut>⇧⌘S</twig:ContextMenu:Shortcut>
            </twig:ContextMenu:Item>
        </twig:ContextMenu:Group>
    </twig:ContextMenu:Content>
</twig:ContextMenu>
```

### Groups

```twig {"preview":true,"height":"260px"}
<twig:ContextMenu>
    <twig:ContextMenu:Trigger class="flex aspect-video w-full max-w-xs items-center justify-center rounded-xl border border-dashed text-sm">
        <span class="hidden pointer-fine:inline-block">Right click here</span>
        <span class="hidden pointer-coarse:inline-block">Long press here</span>
    </twig:ContextMenu:Trigger>
    <twig:ContextMenu:Content>
        <twig:ContextMenu:Group>
            <twig:ContextMenu:Label>File</twig:ContextMenu:Label>
            <twig:ContextMenu:Item>
                New File
                <twig:ContextMenu:Shortcut>⌘N</twig:ContextMenu:Shortcut>
            </twig:ContextMenu:Item>
            <twig:ContextMenu:Item>
                Open File
                <twig:ContextMenu:Shortcut>⌘O</twig:ContextMenu:Shortcut>
            </twig:ContextMenu:Item>
            <twig:ContextMenu:Item>
                Save
                <twig:ContextMenu:Shortcut>⌘S</twig:ContextMenu:Shortcut>
            </twig:ContextMenu:Item>
        </twig:ContextMenu:Group>
        <twig:ContextMenu:Separator />
        <twig:ContextMenu:Group>
            <twig:ContextMenu:Label>Edit</twig:ContextMenu:Label>
            <twig:ContextMenu:Item>
                Undo
                <twig:ContextMenu:Shortcut>⌘Z</twig:ContextMenu:Shortcut>
            </twig:ContextMenu:Item>
            <twig:ContextMenu:Item>
                Redo
                <twig:ContextMenu:Shortcut>⇧⌘Z</twig:ContextMenu:Shortcut>
            </twig:ContextMenu:Item>
        </twig:ContextMenu:Group>
        <twig:ContextMenu:Separator />
        <twig:ContextMenu:Group>
            <twig:ContextMenu:Item>
                Cut
                <twig:ContextMenu:Shortcut>⌘X</twig:ContextMenu:Shortcut>
            </twig:ContextMenu:Item>
            <twig:ContextMenu:Item>
                Copy
                <twig:ContextMenu:Shortcut>⌘C</twig:ContextMenu:Shortcut>
            </twig:ContextMenu:Item>
            <twig:ContextMenu:Item>
                Paste
                <twig:ContextMenu:Shortcut>⌘V</twig:ContextMenu:Shortcut>
            </twig:ContextMenu:Item>
        </twig:ContextMenu:Group>
        <twig:ContextMenu:Separator />
        <twig:ContextMenu:Group>
            <twig:ContextMenu:Item variant="destructive">
                Delete
                <twig:ContextMenu:Shortcut>⌫</twig:ContextMenu:Shortcut>
            </twig:ContextMenu:Item>
        </twig:ContextMenu:Group>
    </twig:ContextMenu:Content>
</twig:ContextMenu>
```

### Icons

```twig {"preview":true,"height":"240px"}
<twig:ContextMenu>
    <twig:ContextMenu:Trigger class="flex aspect-video w-full max-w-xs items-center justify-center rounded-xl border border-dashed text-sm">
        <span class="hidden pointer-fine:inline-block">Right click here</span>
        <span class="hidden pointer-coarse:inline-block">Long press here</span>
    </twig:ContextMenu:Trigger>
    <twig:ContextMenu:Content>
        <twig:ContextMenu:Group>
            <twig:ContextMenu:Item>
                <twig:ux:icon name="lucide:copy" />
                Copy
            </twig:ContextMenu:Item>
            <twig:ContextMenu:Item>
                <twig:ux:icon name="lucide:scissors" />
                Cut
            </twig:ContextMenu:Item>
            <twig:ContextMenu:Item>
                <twig:ux:icon name="lucide:clipboard-paste" />
                Paste
            </twig:ContextMenu:Item>
        </twig:ContextMenu:Group>
        <twig:ContextMenu:Separator />
        <twig:ContextMenu:Group>
            <twig:ContextMenu:Item variant="destructive">
                <twig:ux:icon name="lucide:trash-2" />
                Delete
            </twig:ContextMenu:Item>
        </twig:ContextMenu:Group>
    </twig:ContextMenu:Content>
</twig:ContextMenu>
```

### Checkboxes

Use `ContextMenu:CheckboxItem` for toggles.

```twig {"preview":true,"height":"240px"}
<twig:ContextMenu>
    <twig:ContextMenu:Trigger class="flex aspect-video w-full max-w-xs items-center justify-center rounded-xl border border-dashed text-sm">
        <span class="hidden pointer-fine:inline-block">Right click here</span>
        <span class="hidden pointer-coarse:inline-block">Long press here</span>
    </twig:ContextMenu:Trigger>
    <twig:ContextMenu:Content>
        <twig:ContextMenu:Group>
            <twig:ContextMenu:CheckboxItem checked>Show Bookmarks Bar</twig:ContextMenu:CheckboxItem>
            <twig:ContextMenu:CheckboxItem>Show Full URLs</twig:ContextMenu:CheckboxItem>
            <twig:ContextMenu:CheckboxItem checked>Show Developer Tools</twig:ContextMenu:CheckboxItem>
        </twig:ContextMenu:Group>
    </twig:ContextMenu:Content>
</twig:ContextMenu>
```

### Radio

Use `ContextMenu:RadioItem` for exclusive choices.

```twig {"preview":true,"height":"260px"}
<twig:ContextMenu>
    <twig:ContextMenu:Trigger class="flex aspect-video w-full max-w-xs items-center justify-center rounded-xl border border-dashed text-sm">
        <span class="hidden pointer-fine:inline-block">Right click here</span>
        <span class="hidden pointer-coarse:inline-block">Long press here</span>
    </twig:ContextMenu:Trigger>
    <twig:ContextMenu:Content>
        <twig:ContextMenu:Group>
            <twig:ContextMenu:Label>People</twig:ContextMenu:Label>
            <twig:ContextMenu:RadioGroup value="pedro">
                <twig:ContextMenu:RadioItem value="pedro" checked>Pedro Duarte</twig:ContextMenu:RadioItem>
                <twig:ContextMenu:RadioItem value="colm">Colm Tuite</twig:ContextMenu:RadioItem>
            </twig:ContextMenu:RadioGroup>
        </twig:ContextMenu:Group>
        <twig:ContextMenu:Separator />
        <twig:ContextMenu:Group>
            <twig:ContextMenu:Label>Theme</twig:ContextMenu:Label>
            <twig:ContextMenu:RadioGroup value="light">
                <twig:ContextMenu:RadioItem value="light" checked>Light</twig:ContextMenu:RadioItem>
                <twig:ContextMenu:RadioItem value="dark">Dark</twig:ContextMenu:RadioItem>
                <twig:ContextMenu:RadioItem value="system">System</twig:ContextMenu:RadioItem>
            </twig:ContextMenu:RadioGroup>
        </twig:ContextMenu:Group>
    </twig:ContextMenu:Content>
</twig:ContextMenu>
```

### Destructive

Use `variant="destructive"` to style the menu item as destructive.

```twig {"preview":true,"height":"240px"}
<twig:ContextMenu>
    <twig:ContextMenu:Trigger class="flex aspect-video w-full max-w-xs items-center justify-center rounded-xl border border-dashed text-sm">
        <span class="hidden pointer-fine:inline-block">Right click here</span>
        <span class="hidden pointer-coarse:inline-block">Long press here</span>
    </twig:ContextMenu:Trigger>
    <twig:ContextMenu:Content>
        <twig:ContextMenu:Group>
            <twig:ContextMenu:Item>
                <twig:ux:icon name="lucide:pencil" />
                Edit
            </twig:ContextMenu:Item>
            <twig:ContextMenu:Item>
                <twig:ux:icon name="lucide:share" />
                Share
            </twig:ContextMenu:Item>
        </twig:ContextMenu:Group>
        <twig:ContextMenu:Separator />
        <twig:ContextMenu:Group>
            <twig:ContextMenu:Item variant="destructive">
                <twig:ux:icon name="lucide:trash-2" />
                Delete
            </twig:ContextMenu:Item>
        </twig:ContextMenu:Group>
    </twig:ContextMenu:Content>
</twig:ContextMenu>
```

### Sides

Use the `side` prop on `ContextMenu:Content` to choose which side of the pointer the menu appears on.

```twig {"preview":true,"height":"300px"}
<div class="grid w-full max-w-sm grid-cols-2 gap-4">
    <twig:ContextMenu>
        <twig:ContextMenu:Trigger class="flex aspect-video w-full max-w-xs items-center justify-center rounded-xl border border-dashed text-sm">
            <span class="hidden pointer-fine:inline-block">Right click (top)</span>
            <span class="hidden pointer-coarse:inline-block">Long press (top)</span>
        </twig:ContextMenu:Trigger>
        <twig:ContextMenu:Content side="top">
        <twig:ContextMenu:Group>
            <twig:ContextMenu:Item>Back</twig:ContextMenu:Item>
            <twig:ContextMenu:Item>Forward</twig:ContextMenu:Item>
            <twig:ContextMenu:Item>Reload</twig:ContextMenu:Item>
        </twig:ContextMenu:Group>
        </twig:ContextMenu:Content>
    </twig:ContextMenu>
    <twig:ContextMenu>
        <twig:ContextMenu:Trigger class="flex aspect-video w-full max-w-xs items-center justify-center rounded-xl border border-dashed text-sm">
            <span class="hidden pointer-fine:inline-block">Right click (right)</span>
            <span class="hidden pointer-coarse:inline-block">Long press (right)</span>
        </twig:ContextMenu:Trigger>
        <twig:ContextMenu:Content side="right">
        <twig:ContextMenu:Group>
            <twig:ContextMenu:Item>Back</twig:ContextMenu:Item>
            <twig:ContextMenu:Item>Forward</twig:ContextMenu:Item>
            <twig:ContextMenu:Item>Reload</twig:ContextMenu:Item>
        </twig:ContextMenu:Group>
        </twig:ContextMenu:Content>
    </twig:ContextMenu>
    <twig:ContextMenu>
        <twig:ContextMenu:Trigger class="flex aspect-video w-full max-w-xs items-center justify-center rounded-xl border border-dashed text-sm">
            <span class="hidden pointer-fine:inline-block">Right click (bottom)</span>
            <span class="hidden pointer-coarse:inline-block">Long press (bottom)</span>
        </twig:ContextMenu:Trigger>
        <twig:ContextMenu:Content side="bottom">
        <twig:ContextMenu:Group>
            <twig:ContextMenu:Item>Back</twig:ContextMenu:Item>
            <twig:ContextMenu:Item>Forward</twig:ContextMenu:Item>
            <twig:ContextMenu:Item>Reload</twig:ContextMenu:Item>
        </twig:ContextMenu:Group>
        </twig:ContextMenu:Content>
    </twig:ContextMenu>
    <twig:ContextMenu>
        <twig:ContextMenu:Trigger class="flex aspect-video w-full max-w-xs items-center justify-center rounded-xl border border-dashed text-sm">
            <span class="hidden pointer-fine:inline-block">Right click (left)</span>
            <span class="hidden pointer-coarse:inline-block">Long press (left)</span>
        </twig:ContextMenu:Trigger>
        <twig:ContextMenu:Content side="left">
        <twig:ContextMenu:Group>
            <twig:ContextMenu:Item>Back</twig:ContextMenu:Item>
            <twig:ContextMenu:Item>Forward</twig:ContextMenu:Item>
            <twig:ContextMenu:Item>Reload</twig:ContextMenu:Item>
        </twig:ContextMenu:Group>
        </twig:ContextMenu:Content>
    </twig:ContextMenu>
</div>
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

```twig {"preview":true,"height":"420px"}
<div class="flex w-full flex-col gap-6">
    <div dir="rtl">
        <twig:ContextMenu>
            <twig:ContextMenu:Trigger class="flex aspect-video w-full max-w-xs items-center justify-center rounded-xl border border-dashed text-sm">
                <span class="hidden pointer-fine:inline-block">انقر بزر الماوس الأيمن هنا</span>
                <span class="hidden pointer-coarse:inline-block">اضغط مطولاً هنا</span>
            </twig:ContextMenu:Trigger>
            <twig:ContextMenu:Content class="w-48">
            <twig:ContextMenu:Group>
                <twig:ContextMenu:Sub>
                    <twig:ContextMenu:SubTrigger>التنقل</twig:ContextMenu:SubTrigger>
                    <twig:ContextMenu:SubContent class="w-44">
                        <twig:ContextMenu:Group>
                            <twig:ContextMenu:Item>
                                <twig:ux:icon name="lucide:arrow-left" />
                                رجوع
                                <twig:ContextMenu:Shortcut>⌘[</twig:ContextMenu:Shortcut>
                            </twig:ContextMenu:Item>
                            <twig:ContextMenu:Item disabled>
                                <twig:ux:icon name="lucide:arrow-right" />
                                تقدم
                                <twig:ContextMenu:Shortcut>⌘]</twig:ContextMenu:Shortcut>
                            </twig:ContextMenu:Item>
                            <twig:ContextMenu:Item>
                                <twig:ux:icon name="lucide:rotate-cw" />
                                إعادة تحميل
                                <twig:ContextMenu:Shortcut>⌘R</twig:ContextMenu:Shortcut>
                            </twig:ContextMenu:Item>
                        </twig:ContextMenu:Group>
                    </twig:ContextMenu:SubContent>
                </twig:ContextMenu:Sub>
                <twig:ContextMenu:Sub>
                    <twig:ContextMenu:SubTrigger>المزيد من الأدوات</twig:ContextMenu:SubTrigger>
                    <twig:ContextMenu:SubContent class="w-44">
                        <twig:ContextMenu:Group>
                            <twig:ContextMenu:Item>حفظ الصفحة...</twig:ContextMenu:Item>
                            <twig:ContextMenu:Item>إنشاء اختصار...</twig:ContextMenu:Item>
                            <twig:ContextMenu:Item>تسمية النافذة...</twig:ContextMenu:Item>
                        </twig:ContextMenu:Group>
                        <twig:ContextMenu:Separator />
                        <twig:ContextMenu:Group>
                            <twig:ContextMenu:Item>أدوات المطور</twig:ContextMenu:Item>
                        </twig:ContextMenu:Group>
                        <twig:ContextMenu:Separator />
                        <twig:ContextMenu:Group>
                            <twig:ContextMenu:Item variant="destructive">حذف</twig:ContextMenu:Item>
                        </twig:ContextMenu:Group>
                    </twig:ContextMenu:SubContent>
                </twig:ContextMenu:Sub>
            </twig:ContextMenu:Group>
            <twig:ContextMenu:Separator />
            <twig:ContextMenu:Group>
                <twig:ContextMenu:CheckboxItem checked>إظهار الإشارات المرجعية</twig:ContextMenu:CheckboxItem>
                <twig:ContextMenu:CheckboxItem>إظهار عناوين URL الكاملة</twig:ContextMenu:CheckboxItem>
            </twig:ContextMenu:Group>
            <twig:ContextMenu:Separator />
            <twig:ContextMenu:Group>
                <twig:ContextMenu:RadioGroup value="pedro">
                    <twig:ContextMenu:Label>الأشخاص</twig:ContextMenu:Label>
                    <twig:ContextMenu:RadioItem value="pedro" checked>Pedro Duarte</twig:ContextMenu:RadioItem>
                    <twig:ContextMenu:RadioItem value="colm">Colm Tuite</twig:ContextMenu:RadioItem>
                </twig:ContextMenu:RadioGroup>
            </twig:ContextMenu:Group>
            </twig:ContextMenu:Content>
        </twig:ContextMenu>
    </div>
    <div dir="rtl">
        <twig:ContextMenu>
            <twig:ContextMenu:Trigger class="flex aspect-video w-full max-w-xs items-center justify-center rounded-xl border border-dashed text-sm">
                <span class="hidden pointer-fine:inline-block">לחץ לחיצה ימנית כאן</span>
                <span class="hidden pointer-coarse:inline-block">לחץ לחיצה ארוכה כאן</span>
            </twig:ContextMenu:Trigger>
            <twig:ContextMenu:Content class="w-48">
            <twig:ContextMenu:Group>
                <twig:ContextMenu:Sub>
                    <twig:ContextMenu:SubTrigger>ניווט</twig:ContextMenu:SubTrigger>
                    <twig:ContextMenu:SubContent class="w-44">
                        <twig:ContextMenu:Group>
                            <twig:ContextMenu:Item>
                                <twig:ux:icon name="lucide:arrow-left" />
                                חזור
                                <twig:ContextMenu:Shortcut>⌘[</twig:ContextMenu:Shortcut>
                            </twig:ContextMenu:Item>
                            <twig:ContextMenu:Item disabled>
                                <twig:ux:icon name="lucide:arrow-right" />
                                קדימה
                                <twig:ContextMenu:Shortcut>⌘]</twig:ContextMenu:Shortcut>
                            </twig:ContextMenu:Item>
                            <twig:ContextMenu:Item>
                                <twig:ux:icon name="lucide:rotate-cw" />
                                רענן
                                <twig:ContextMenu:Shortcut>⌘R</twig:ContextMenu:Shortcut>
                            </twig:ContextMenu:Item>
                        </twig:ContextMenu:Group>
                    </twig:ContextMenu:SubContent>
                </twig:ContextMenu:Sub>
                <twig:ContextMenu:Sub>
                    <twig:ContextMenu:SubTrigger>כלים נוספים</twig:ContextMenu:SubTrigger>
                    <twig:ContextMenu:SubContent class="w-44">
                        <twig:ContextMenu:Group>
                            <twig:ContextMenu:Item>שמור עמוד...</twig:ContextMenu:Item>
                            <twig:ContextMenu:Item>צור קיצור דרך...</twig:ContextMenu:Item>
                            <twig:ContextMenu:Item>שם חלון...</twig:ContextMenu:Item>
                        </twig:ContextMenu:Group>
                        <twig:ContextMenu:Separator />
                        <twig:ContextMenu:Group>
                            <twig:ContextMenu:Item>כלי מפתח</twig:ContextMenu:Item>
                        </twig:ContextMenu:Group>
                        <twig:ContextMenu:Separator />
                        <twig:ContextMenu:Group>
                            <twig:ContextMenu:Item variant="destructive">מחק</twig:ContextMenu:Item>
                        </twig:ContextMenu:Group>
                    </twig:ContextMenu:SubContent>
                </twig:ContextMenu:Sub>
            </twig:ContextMenu:Group>
            <twig:ContextMenu:Separator />
            <twig:ContextMenu:Group>
                <twig:ContextMenu:CheckboxItem checked>הצג סימניות</twig:ContextMenu:CheckboxItem>
                <twig:ContextMenu:CheckboxItem>הצג כתובות URL מלאות</twig:ContextMenu:CheckboxItem>
            </twig:ContextMenu:Group>
            <twig:ContextMenu:Separator />
            <twig:ContextMenu:Group>
                <twig:ContextMenu:RadioGroup value="pedro">
                    <twig:ContextMenu:Label>אנשים</twig:ContextMenu:Label>
                    <twig:ContextMenu:RadioItem value="pedro" checked>Pedro Duarte</twig:ContextMenu:RadioItem>
                    <twig:ContextMenu:RadioItem value="colm">Colm Tuite</twig:ContextMenu:RadioItem>
                </twig:ContextMenu:RadioGroup>
            </twig:ContextMenu:Group>
            </twig:ContextMenu:Content>
        </twig:ContextMenu>
    </div>
</div>
```

## Accessibility

- `ContextMenu:Content` renders `role="menu"` and its entries `role="menuitem"`, `role="menuitemcheckbox"` or `role="menuitemradio"`, with `aria-checked` kept in sync by the controller.
- The menu opens on right click, on the Menu key or `Shift+F10` when the trigger has focus, and on a long press on touch screens.
- `ArrowDown` and `ArrowUp` move between items and wrap around, `Home` and `End` jump to the first and last, and `Escape` closes the menu. Disabled items are skipped.
- `ContextMenu:Separator` renders `role="separator"`, and `ContextMenu:Group` renders `role="group"`.
- A disabled item renders `aria-disabled="true"` rather than being removed, so its presence stays discoverable.
- The trigger is not a focusable element by default. Give it `tabindex="0"` when keyboard users must be able to open the menu, and give the menu an accessible name with `aria-label` on `ContextMenu:Content`.

## API Reference

::: api-reference
