# Alert Dialog

A modal dialog that interrupts the user with important content and expects a response.

```twig {"preview":true,"height":"300px"}
<twig:AlertDialog id="delete_account">
    <twig:AlertDialog:Trigger>
        <twig:Button variant="outline" {{ ...alert_dialog_trigger_attrs }}>Show Dialog</twig:Button>
    </twig:AlertDialog:Trigger>
    <twig:AlertDialog:Content>
        <twig:AlertDialog:Header>
            <twig:AlertDialog:Title>Are you absolutely sure?</twig:AlertDialog:Title>
            <twig:AlertDialog:Description>
                This action cannot be undone. This will permanently delete your
                account from our servers.
            </twig:AlertDialog:Description>
        </twig:AlertDialog:Header>
        <twig:AlertDialog:Footer>
            <twig:AlertDialog:Cancel>Cancel</twig:AlertDialog:Cancel>
            <twig:AlertDialog:Action>Continue</twig:AlertDialog:Action>
        </twig:AlertDialog:Footer>
    </twig:AlertDialog:Content>
</twig:AlertDialog>
```

## Installation

::: installation

## Usage

```twig
<twig:AlertDialog id="delete_account">
    <twig:AlertDialog:Trigger>
        <twig:Button variant="outline" {{ ...alert_dialog_trigger_attrs }}>Show Dialog</twig:Button>
    </twig:AlertDialog:Trigger>
    <twig:AlertDialog:Content>
        <twig:AlertDialog:Header>
            <twig:AlertDialog:Title>Are you absolutely sure?</twig:AlertDialog:Title>
            <twig:AlertDialog:Description>
                This action cannot be undone. This will permanently delete your
                account and remove your data from our servers.
            </twig:AlertDialog:Description>
        </twig:AlertDialog:Header>
        <twig:AlertDialog:Footer>
            <twig:AlertDialog:Cancel>Cancel</twig:AlertDialog:Cancel>
            <twig:AlertDialog:Action>Continue</twig:AlertDialog:Action>
        </twig:AlertDialog:Footer>
    </twig:AlertDialog:Content>
</twig:AlertDialog>
```

## Examples

### Basic

A basic alert dialog with a title, description, and cancel and continue buttons.

```twig {"preview":true,"height":"300px"}
<twig:AlertDialog id="alert-dialog-basic">
    <twig:AlertDialog:Trigger>
        <twig:Button variant="outline" {{ ...alert_dialog_trigger_attrs }}>Show Dialog</twig:Button>
    </twig:AlertDialog:Trigger>
    <twig:AlertDialog:Content>
        <twig:AlertDialog:Header>
            <twig:AlertDialog:Title>Are you absolutely sure?</twig:AlertDialog:Title>
            <twig:AlertDialog:Description>
                This action cannot be undone. This will permanently delete your
                account and remove your data from our servers.
            </twig:AlertDialog:Description>
        </twig:AlertDialog:Header>
        <twig:AlertDialog:Footer>
            <twig:AlertDialog:Cancel>Cancel</twig:AlertDialog:Cancel>
            <twig:AlertDialog:Action>Continue</twig:AlertDialog:Action>
        </twig:AlertDialog:Footer>
    </twig:AlertDialog:Content>
</twig:AlertDialog>
```

### Small

Use the `size="sm"` prop to make the alert dialog smaller.

```twig {"preview":true,"height":"300px"}
<twig:AlertDialog id="alert-dialog-small">
    <twig:AlertDialog:Trigger>
        <twig:Button variant="outline" {{ ...alert_dialog_trigger_attrs }}>Show Dialog</twig:Button>
    </twig:AlertDialog:Trigger>
    <twig:AlertDialog:Content size="sm">
        <twig:AlertDialog:Header>
            <twig:AlertDialog:Title>Allow accessory to connect?</twig:AlertDialog:Title>
            <twig:AlertDialog:Description>
                Do you want to allow the USB accessory to connect to this device?
            </twig:AlertDialog:Description>
        </twig:AlertDialog:Header>
        <twig:AlertDialog:Footer>
            <twig:AlertDialog:Cancel>Don't allow</twig:AlertDialog:Cancel>
            <twig:AlertDialog:Action>Allow</twig:AlertDialog:Action>
        </twig:AlertDialog:Footer>
    </twig:AlertDialog:Content>
</twig:AlertDialog>
```

### Media

Use the `AlertDialog:Media` component to add a media element such as an icon or image to the alert dialog.

```twig {"preview":true,"height":"300px"}
<twig:AlertDialog id="alert-dialog-media">
    <twig:AlertDialog:Trigger>
        <twig:Button variant="outline" {{ ...alert_dialog_trigger_attrs }}>Share Project</twig:Button>
    </twig:AlertDialog:Trigger>
    <twig:AlertDialog:Content>
        <twig:AlertDialog:Header>
            <twig:AlertDialog:Media>
                <twig:ux:icon name="lucide:circle-fading-plus" />
            </twig:AlertDialog:Media>
            <twig:AlertDialog:Title>Share this project?</twig:AlertDialog:Title>
            <twig:AlertDialog:Description>
                Anyone with the link will be able to view and edit this project.
            </twig:AlertDialog:Description>
        </twig:AlertDialog:Header>
        <twig:AlertDialog:Footer>
            <twig:AlertDialog:Cancel>Cancel</twig:AlertDialog:Cancel>
            <twig:AlertDialog:Action>Share</twig:AlertDialog:Action>
        </twig:AlertDialog:Footer>
    </twig:AlertDialog:Content>
</twig:AlertDialog>
```

### Small with Media

Use the `size="sm"` prop to make the alert dialog smaller and the `AlertDialog:Media` component to add a media element such as an icon or image to the alert dialog.

```twig {"preview":true,"height":"350px"}
<twig:AlertDialog id="alert-dialog-small-with-media">
    <twig:AlertDialog:Trigger>
        <twig:Button variant="outline" {{ ...alert_dialog_trigger_attrs }}>Show Dialog</twig:Button>
    </twig:AlertDialog:Trigger>
    <twig:AlertDialog:Content size="sm">
        <twig:AlertDialog:Header>
            <twig:AlertDialog:Media>
                <twig:ux:icon name="lucide:bluetooth" />
            </twig:AlertDialog:Media>
            <twig:AlertDialog:Title>Allow accessory to connect?</twig:AlertDialog:Title>
            <twig:AlertDialog:Description>
                Do you want to allow the USB accessory to connect to this device?
            </twig:AlertDialog:Description>
        </twig:AlertDialog:Header>
        <twig:AlertDialog:Footer>
            <twig:AlertDialog:Cancel>Don't allow</twig:AlertDialog:Cancel>
            <twig:AlertDialog:Action>Allow</twig:AlertDialog:Action>
        </twig:AlertDialog:Footer>
    </twig:AlertDialog:Content>
</twig:AlertDialog>
```

### Destructive

Use the `AlertDialog:Action` component to add a destructive action button to the alert dialog.

```twig {"preview":true,"height":"350px"}
<twig:AlertDialog id="alert-dialog-destructive">
    <twig:AlertDialog:Trigger>
        <twig:Button variant="destructive" {{ ...alert_dialog_trigger_attrs }}>Delete Chat</twig:Button>
    </twig:AlertDialog:Trigger>
    <twig:AlertDialog:Content size="sm">
        <twig:AlertDialog:Header>
            <twig:AlertDialog:Media class="bg-destructive/10 text-destructive dark:bg-destructive/20 dark:text-destructive">
                <twig:ux:icon name="lucide:trash-2" />
            </twig:AlertDialog:Media>
            <twig:AlertDialog:Title>Delete chat?</twig:AlertDialog:Title>
            <twig:AlertDialog:Description>
                This will permanently delete this chat conversation. View
                <a href="#">Settings</a> delete any memories saved during this chat.
            </twig:AlertDialog:Description>
        </twig:AlertDialog:Header>
        <twig:AlertDialog:Footer>
            <twig:AlertDialog:Cancel>Cancel</twig:AlertDialog:Cancel>
            <twig:AlertDialog:Action variant="destructive">Delete</twig:AlertDialog:Action>
        </twig:AlertDialog:Footer>
    </twig:AlertDialog:Content>
</twig:AlertDialog>
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

```twig {"preview":true,"height":"350px"}
<div class="flex flex-col gap-8">
    <div dir="rtl" class="flex gap-4">
        <twig:AlertDialog id="rtl-ar-default">
            <twig:AlertDialog:Trigger>
                <twig:Button variant="outline" {{ ...alert_dialog_trigger_attrs }}>إظهار الحوار</twig:Button>
            </twig:AlertDialog:Trigger>
            <twig:AlertDialog:Content dir="rtl">
                <twig:AlertDialog:Header>
                    <twig:AlertDialog:Title>هل أنت متأكد تمامًا؟</twig:AlertDialog:Title>
                    <twig:AlertDialog:Description>
                        لا يمكن التراجع عن هذا الإجراء. سيتم حذف حسابك نهائيًا من خوادمنا.
                    </twig:AlertDialog:Description>
                </twig:AlertDialog:Header>
                <twig:AlertDialog:Footer>
                    <twig:AlertDialog:Cancel>إلغاء</twig:AlertDialog:Cancel>
                    <twig:AlertDialog:Action>متابعة</twig:AlertDialog:Action>
                </twig:AlertDialog:Footer>
            </twig:AlertDialog:Content>
        </twig:AlertDialog>
        <twig:AlertDialog id="rtl-ar-sm">
            <twig:AlertDialog:Trigger>
                <twig:Button variant="outline" {{ ...alert_dialog_trigger_attrs }}>إظهار الحوار (صغير)</twig:Button>
            </twig:AlertDialog:Trigger>
            <twig:AlertDialog:Content size="sm" dir="rtl">
                <twig:AlertDialog:Header>
                    <twig:AlertDialog:Media>
                        <twig:ux:icon name="lucide:bluetooth" />
                    </twig:AlertDialog:Media>
                    <twig:AlertDialog:Title>السماح للملحق بالاتصال؟</twig:AlertDialog:Title>
                    <twig:AlertDialog:Description>
                        هل تريد السماح لملحق USB بالاتصال بهذا الجهاز؟
                    </twig:AlertDialog:Description>
                </twig:AlertDialog:Header>
                <twig:AlertDialog:Footer>
                    <twig:AlertDialog:Cancel>عدم السماح</twig:AlertDialog:Cancel>
                    <twig:AlertDialog:Action>السماح</twig:AlertDialog:Action>
                </twig:AlertDialog:Footer>
            </twig:AlertDialog:Content>
        </twig:AlertDialog>
    </div>
    <div dir="rtl" class="flex gap-4">
        <twig:AlertDialog id="rtl-he-default">
            <twig:AlertDialog:Trigger>
                <twig:Button variant="outline" {{ ...alert_dialog_trigger_attrs }}>הצג דיאלוג</twig:Button>
            </twig:AlertDialog:Trigger>
            <twig:AlertDialog:Content dir="rtl">
                <twig:AlertDialog:Header>
                    <twig:AlertDialog:Title>האם אתה בטוח לחלוטין?</twig:AlertDialog:Title>
                    <twig:AlertDialog:Description>
                        לא ניתן לבטל. זה ימחק לצמיתות את החשבון שלך מהשרתים שלנו.
                    </twig:AlertDialog:Description>
                </twig:AlertDialog:Header>
                <twig:AlertDialog:Footer>
                    <twig:AlertDialog:Cancel>ביטול</twig:AlertDialog:Cancel>
                    <twig:AlertDialog:Action>המשך</twig:AlertDialog:Action>
                </twig:AlertDialog:Footer>
            </twig:AlertDialog:Content>
        </twig:AlertDialog>
        <twig:AlertDialog id="rtl-he-sm">
            <twig:AlertDialog:Trigger>
                <twig:Button variant="outline" {{ ...alert_dialog_trigger_attrs }}>הצג דיאלוג (קטן)</twig:Button>
            </twig:AlertDialog:Trigger>
            <twig:AlertDialog:Content size="sm" dir="rtl">
                <twig:AlertDialog:Header>
                    <twig:AlertDialog:Media>
                        <twig:ux:icon name="lucide:bluetooth" />
                    </twig:AlertDialog:Media>
                    <twig:AlertDialog:Title>לחבר התקן?</twig:AlertDialog:Title>
                    <twig:AlertDialog:Description>
                        חבר התקן USB למכשיר זה?
                    </twig:AlertDialog:Description>
                </twig:AlertDialog:Header>
                <twig:AlertDialog:Footer>
                    <twig:AlertDialog:Cancel>דחה</twig:AlertDialog:Cancel>
                    <twig:AlertDialog:Action>אשר</twig:AlertDialog:Action>
                </twig:AlertDialog:Footer>
            </twig:AlertDialog:Content>
        </twig:AlertDialog>
    </div>
</div>
```

## API Reference

::: api-reference
