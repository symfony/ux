# Checkbox

A control that allows the user to toggle between checked and not checked.

```twig {"preview":true}
<twig:Field:Group class="max-w-sm">
    <twig:Field orientation="horizontal">
        <twig:Checkbox id="terms-checkbox" name="terms-checkbox" />
        <twig:Label for="terms-checkbox">Accept terms and conditions</twig:Label>
    </twig:Field>
    <twig:Field orientation="horizontal">
        <twig:Checkbox id="terms-checkbox-2" name="terms-checkbox-2" checked />
        <twig:Field:Content>
            <twig:Field:Label for="terms-checkbox-2">Accept terms and conditions</twig:Field:Label>
            <twig:Field:Description>By clicking this checkbox, you agree to the terms.</twig:Field:Description>
        </twig:Field:Content>
    </twig:Field>
    <twig:Field orientation="horizontal" data-disabled>
        <twig:Checkbox id="toggle-checkbox" name="toggle-checkbox" disabled />
        <twig:Field:Label for="toggle-checkbox">Enable notifications</twig:Field:Label>
    </twig:Field>
    <twig:Field:Label>
        <twig:Field orientation="horizontal">
            <twig:Checkbox id="toggle-checkbox-2" name="toggle-checkbox-2" />
            <twig:Field:Content>
                <twig:Field:Title>Enable notifications</twig:Field:Title>
                <twig:Field:Description>You can enable or disable notifications at any time.</twig:Field:Description>
            </twig:Field:Content>
        </twig:Field>
    </twig:Field:Label>
</twig:Field:Group>
```

## Installation

::: installation

## Usage

```twig
<twig:Checkbox />
```

## Examples

### Basic

Pair the checkbox with `Field` and `Field:Label` for proper layout and labeling.

```twig {"preview":true}
<twig:Field:Group class="mx-auto w-56">
    <twig:Field orientation="horizontal">
        <twig:Checkbox id="terms-checkbox-basic" name="terms-checkbox-basic" />
        <twig:Field:Label for="terms-checkbox-basic">Accept terms and conditions</twig:Field:Label>
    </twig:Field>
</twig:Field:Group>
```

### Description

Use `Field:Content` and `Field:Description` for helper text.

```twig {"preview":true}
<twig:Field:Group class="mx-auto w-72">
    <twig:Field orientation="horizontal">
        <twig:Checkbox id="terms-checkbox-desc" name="terms-checkbox-desc" checked />
        <twig:Field:Content>
            <twig:Field:Label for="terms-checkbox-desc">Accept terms and conditions</twig:Field:Label>
            <twig:Field:Description>By clicking this checkbox, you agree to the terms and conditions.</twig:Field:Description>
        </twig:Field:Content>
    </twig:Field>
</twig:Field:Group>
```

### Disabled

Use the `disabled` attribute to prevent interaction and add the `data-disabled` attribute to the `Field` component for disabled styles.

```twig {"preview":true}
<twig:Field:Group class="mx-auto w-56">
    <twig:Field orientation="horizontal" data-disabled>
        <twig:Checkbox id="toggle-checkbox-disabled" name="toggle-checkbox-disabled" disabled />
        <twig:Field:Label for="toggle-checkbox-disabled">Enable notifications</twig:Field:Label>
    </twig:Field>
</twig:Field:Group>
```

### Group

Use multiple fields to create a checkbox list.

```twig {"preview":true}
<twig:Field:Set>
    <twig:Field:Legend variant="label">Show these items on the desktop:</twig:Field:Legend>
    <twig:Field:Description>Select the items you want to show on the desktop.</twig:Field:Description>
    <twig:Field:Group class="gap-3">
        <twig:Field orientation="horizontal">
            <twig:Checkbox id="finder-hard-disks" name="finder-hard-disks" checked />
            <twig:Field:Label for="finder-hard-disks" class="font-normal">Hard disks</twig:Field:Label>
        </twig:Field>
        <twig:Field orientation="horizontal">
            <twig:Checkbox id="finder-external-disks" name="finder-external-disks" checked />
            <twig:Field:Label for="finder-external-disks" class="font-normal">External disks</twig:Field:Label>
        </twig:Field>
        <twig:Field orientation="horizontal">
            <twig:Checkbox id="finder-cds-dvds" name="finder-cds-dvds" />
            <twig:Field:Label for="finder-cds-dvds" class="font-normal">CDs, DVDs, and iPods</twig:Field:Label>
        </twig:Field>
        <twig:Field orientation="horizontal">
            <twig:Checkbox id="finder-connected-servers" name="finder-connected-servers" />
            <twig:Field:Label for="finder-connected-servers" class="font-normal">Connected servers</twig:Field:Label>
        </twig:Field>
    </twig:Field:Group>
</twig:Field:Set>
```

### Table

```twig {"preview":true}
<twig:Table>
    <twig:Table:Header>
        <twig:Table:Row>
            <twig:Table:Head class="w-8">
                <twig:Checkbox id="select-all-checkbox" name="select-all-checkbox" />
            </twig:Table:Head>
            <twig:Table:Head>Name</twig:Table:Head>
            <twig:Table:Head>Email</twig:Table:Head>
            <twig:Table:Head>Role</twig:Table:Head>
        </twig:Table:Row>
    </twig:Table:Header>
    <twig:Table:Body>
        <twig:Table:Row data-state="selected">
            <twig:Table:Cell>
                <twig:Checkbox id="row-1-checkbox" name="row-1-checkbox" checked />
            </twig:Table:Cell>
            <twig:Table:Cell class="font-medium">Sarah Chen</twig:Table:Cell>
            <twig:Table:Cell>sarah.chen@example.com</twig:Table:Cell>
            <twig:Table:Cell>Admin</twig:Table:Cell>
        </twig:Table:Row>
        <twig:Table:Row>
            <twig:Table:Cell>
                <twig:Checkbox id="row-2-checkbox" name="row-2-checkbox" />
            </twig:Table:Cell>
            <twig:Table:Cell class="font-medium">Marcus Rodriguez</twig:Table:Cell>
            <twig:Table:Cell>marcus.rodriguez@example.com</twig:Table:Cell>
            <twig:Table:Cell>User</twig:Table:Cell>
        </twig:Table:Row>
        <twig:Table:Row>
            <twig:Table:Cell>
                <twig:Checkbox id="row-3-checkbox" name="row-3-checkbox" />
            </twig:Table:Cell>
            <twig:Table:Cell class="font-medium">Priya Patel</twig:Table:Cell>
            <twig:Table:Cell>priya.patel@example.com</twig:Table:Cell>
            <twig:Table:Cell>User</twig:Table:Cell>
        </twig:Table:Row>
        <twig:Table:Row>
            <twig:Table:Cell>
                <twig:Checkbox id="row-4-checkbox" name="row-4-checkbox" />
            </twig:Table:Cell>
            <twig:Table:Cell class="font-medium">David Kim</twig:Table:Cell>
            <twig:Table:Cell>david.kim@example.com</twig:Table:Cell>
            <twig:Table:Cell>Editor</twig:Table:Cell>
        </twig:Table:Row>
    </twig:Table:Body>
</twig:Table>
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

```twig {"preview":true}
<div class="flex flex-col gap-12 w-full items-center">
    {# Arabic #}
    <twig:Field:Group class="max-w-sm" dir="rtl">
        <twig:Field orientation="horizontal">
            <twig:Checkbox id="terms-checkbox-ar" name="terms-checkbox-ar" />
            <twig:Label for="terms-checkbox-ar">قبول الشروط والأحكام</twig:Label>
        </twig:Field>
        <twig:Field orientation="horizontal">
            <twig:Checkbox id="terms-checkbox-2-ar" name="terms-checkbox-2-ar" checked />
            <twig:Field:Content>
                <twig:Field:Label for="terms-checkbox-2-ar">قبول الشروط والأحكام</twig:Field:Label>
                <twig:Field:Description>بالنقر على هذا المربع، فإنك توافق على الشروط.</twig:Field:Description>
            </twig:Field:Content>
        </twig:Field>
        <twig:Field orientation="horizontal" data-disabled>
            <twig:Checkbox id="toggle-checkbox-ar" name="toggle-checkbox-ar" disabled />
            <twig:Field:Label for="toggle-checkbox-ar">تفعيل الإشعارات</twig:Field:Label>
        </twig:Field>
        <twig:Field:Label>
            <twig:Field orientation="horizontal">
                <twig:Checkbox id="toggle-checkbox-2-ar" name="toggle-checkbox-2-ar" />
                <twig:Field:Content>
                    <twig:Field:Title>تفعيل الإشعارات</twig:Field:Title>
                    <twig:Field:Description>يمكنك تفعيل أو إلغاء تفعيل الإشعارات في أي وقت.</twig:Field:Description>
                </twig:Field:Content>
            </twig:Field>
        </twig:Field:Label>
    </twig:Field:Group>
    {# Hebrew #}
    <twig:Field:Group class="max-w-sm" dir="rtl">
        <twig:Field orientation="horizontal">
            <twig:Checkbox id="terms-checkbox-he" name="terms-checkbox-he" />
            <twig:Label for="terms-checkbox-he">קבל תנאים והגבלות</twig:Label>
        </twig:Field>
        <twig:Field orientation="horizontal">
            <twig:Checkbox id="terms-checkbox-2-he" name="terms-checkbox-2-he" checked />
            <twig:Field:Content>
                <twig:Field:Label for="terms-checkbox-2-he">קבל תנאים והגבלות</twig:Field:Label>
                <twig:Field:Description>על ידי לחיצה על תיבת הסימון הזו, אתה מסכים לתנאים.</twig:Field:Description>
            </twig:Field:Content>
        </twig:Field>
        <twig:Field orientation="horizontal" data-disabled>
            <twig:Checkbox id="toggle-checkbox-he" name="toggle-checkbox-he" disabled />
            <twig:Field:Label for="toggle-checkbox-he">הדלק התראות</twig:Field:Label>
        </twig:Field>
        <twig:Field:Label>
            <twig:Field orientation="horizontal">
                <twig:Checkbox id="toggle-checkbox-2-he" name="toggle-checkbox-2-he" />
                <twig:Field:Content>
                    <twig:Field:Title>הדלק התראות</twig:Field:Title>
                    <twig:Field:Description>אתה יכול להדליק או לכבות התראות בכל עת.</twig:Field:Description>
                </twig:Field:Content>
            </twig:Field>
        </twig:Field:Label>
    </twig:Field:Group>
</div>
```

## API Reference

::: api-reference
