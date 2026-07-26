# Radio Group

A set of checkable buttons—known as radio buttons—where no more than one of the buttons can be checked at a time.

```twig {"preview":true}
<twig:RadioGroup class="w-fit">
    <div class="flex items-center gap-3">
        <twig:RadioGroup:Item id="r1" name="spacing" value="default" />
        <twig:Label for="r1">Default</twig:Label>
    </div>
    <div class="flex items-center gap-3">
        <twig:RadioGroup:Item id="r2" name="spacing" value="comfortable" checked />
        <twig:Label for="r2">Comfortable</twig:Label>
    </div>
    <div class="flex items-center gap-3">
        <twig:RadioGroup:Item id="r3" name="spacing" value="compact" />
        <twig:Label for="r3">Compact</twig:Label>
    </div>
</twig:RadioGroup>
```

## Installation

::: installation

## Usage

```twig
<twig:RadioGroup class="w-fit">
    <div class="flex items-center gap-3">
        <twig:RadioGroup:Item id="option-one" name="option" value="option-one" checked />
        <twig:Label for="option-one">Option One</twig:Label>
    </div>
    <div class="flex items-center gap-3">
        <twig:RadioGroup:Item id="option-two" name="option" value="option-two" />
        <twig:Label for="option-two">Option Two</twig:Label>
    </div>
</twig:RadioGroup>
```

## Examples

### Description

Radio group items with a description using the `Field` component.

```twig {"preview":true}
<twig:RadioGroup class="w-fit">
    <twig:Field orientation="horizontal">
        <twig:RadioGroup:Item id="desc-r1" name="density" value="default" />
        <twig:Field:Content>
            <twig:Field:Label for="desc-r1">Default</twig:Field:Label>
            <twig:Field:Description>Standard spacing for most use cases.</twig:Field:Description>
        </twig:Field:Content>
    </twig:Field>
    <twig:Field orientation="horizontal">
        <twig:RadioGroup:Item id="desc-r2" name="density" value="comfortable" checked />
        <twig:Field:Content>
            <twig:Field:Label for="desc-r2">Comfortable</twig:Field:Label>
            <twig:Field:Description>More space between elements.</twig:Field:Description>
        </twig:Field:Content>
    </twig:Field>
    <twig:Field orientation="horizontal">
        <twig:RadioGroup:Item id="desc-r3" name="density" value="compact" />
        <twig:Field:Content>
            <twig:Field:Label for="desc-r3">Compact</twig:Field:Label>
            <twig:Field:Description>Minimal spacing for dense layouts.</twig:Field:Description>
        </twig:Field:Content>
    </twig:Field>
</twig:RadioGroup>
```

### Choice Card

Use `Field:Label` to wrap the entire `Field` for a clickable card-style selection.

```twig {"preview":true}
<twig:RadioGroup class="max-w-sm">
    <twig:Field:Label for="plus-plan" class="border border-input rounded-md has-[:checked]:bg-primary/5 has-[:checked]:border-primary/20 dark:has-[:checked]:bg-primary/10">
        <twig:Field orientation="horizontal" class="p-3">
            <twig:Field:Content>
                <twig:Field:Title>Plus</twig:Field:Title>
                <twig:Field:Description>For individuals and small teams.</twig:Field:Description>
            </twig:Field:Content>
            <twig:RadioGroup:Item id="plus-plan" name="plan" value="plus" checked />
        </twig:Field>
    </twig:Field:Label>
    <twig:Field:Label for="pro-plan" class="border border-input rounded-md has-[:checked]:bg-primary/5 has-[:checked]:border-primary/20 dark:has-[:checked]:bg-primary/10">
        <twig:Field orientation="horizontal" class="p-3">
            <twig:Field:Content>
                <twig:Field:Title>Pro</twig:Field:Title>
                <twig:Field:Description>For growing businesses.</twig:Field:Description>
            </twig:Field:Content>
            <twig:RadioGroup:Item id="pro-plan" name="plan" value="pro" />
        </twig:Field>
    </twig:Field:Label>
    <twig:Field:Label for="enterprise-plan" class="border border-input rounded-md has-[:checked]:bg-primary/5 has-[:checked]:border-primary/20 dark:has-[:checked]:bg-primary/10">
        <twig:Field orientation="horizontal" class="p-3">
            <twig:Field:Content>
                <twig:Field:Title>Enterprise</twig:Field:Title>
                <twig:Field:Description>For large teams and enterprises.</twig:Field:Description>
            </twig:Field:Content>
            <twig:RadioGroup:Item id="enterprise-plan" name="plan" value="enterprise" />
        </twig:Field>
    </twig:Field:Label>
</twig:RadioGroup>
```

### Fieldset

Use `Field:Set` and `Field:Legend` to group radio items with a label and description.

```twig {"preview":true}
<twig:Field:Set class="w-full max-w-xs">
    <twig:Field:Legend variant="label">Subscription Plan</twig:Field:Legend>
    <twig:Field:Description>Yearly and lifetime plans offer significant savings.</twig:Field:Description>
    <twig:RadioGroup class="gap-3">
        <twig:Field orientation="horizontal">
            <twig:RadioGroup:Item id="plan-monthly" name="subscription" value="monthly" checked />
            <twig:Field:Label for="plan-monthly" class="font-normal">Monthly ($9.99/month)</twig:Field:Label>
        </twig:Field>
        <twig:Field orientation="horizontal">
            <twig:RadioGroup:Item id="plan-yearly" name="subscription" value="yearly" />
            <twig:Field:Label for="plan-yearly" class="font-normal">Yearly ($99.99/year)</twig:Field:Label>
        </twig:Field>
        <twig:Field orientation="horizontal">
            <twig:RadioGroup:Item id="plan-lifetime" name="subscription" value="lifetime" />
            <twig:Field:Label for="plan-lifetime" class="font-normal">Lifetime ($299.99)</twig:Field:Label>
        </twig:Field>
    </twig:RadioGroup>
</twig:Field:Set>
```

### Disabled

Use the `disabled` prop on `RadioGroup:Item` to disable individual items.

```twig {"preview":true}
<twig:RadioGroup class="w-fit">
    <twig:Field orientation="horizontal" data-disabled="true">
        <twig:RadioGroup:Item id="disabled-1" name="disabled-example" value="option1" disabled />
        <twig:Field:Label for="disabled-1" class="font-normal">Disabled</twig:Field:Label>
    </twig:Field>
    <twig:Field orientation="horizontal">
        <twig:RadioGroup:Item id="disabled-2" name="disabled-example" value="option2" checked />
        <twig:Field:Label for="disabled-2" class="font-normal">Option 2</twig:Field:Label>
    </twig:Field>
    <twig:Field orientation="horizontal">
        <twig:RadioGroup:Item id="disabled-3" name="disabled-example" value="option3" />
        <twig:Field:Label for="disabled-3" class="font-normal">Option 3</twig:Field:Label>
    </twig:Field>
</twig:RadioGroup>
```

### Invalid

Use `aria-invalid` on `RadioGroup:Item` and `data-invalid` on `Field` to show validation errors.

```twig {"preview":true}
<twig:Field:Set class="w-full max-w-xs">
    <twig:Field:Legend variant="label">Notification Preferences</twig:Field:Legend>
    <twig:Field:Description>Choose how you want to receive notifications.</twig:Field:Description>
    <twig:RadioGroup>
        <twig:Field orientation="horizontal" data-invalid="true">
            <twig:RadioGroup:Item id="invalid-email" name="notifications" value="email" checked aria-invalid="true" />
            <twig:Field:Label for="invalid-email" class="font-normal">Email only</twig:Field:Label>
        </twig:Field>
        <twig:Field orientation="horizontal" data-invalid="true">
            <twig:RadioGroup:Item id="invalid-sms" name="notifications" value="sms" aria-invalid="true" />
            <twig:Field:Label for="invalid-sms" class="font-normal">SMS only</twig:Field:Label>
        </twig:Field>
        <twig:Field orientation="horizontal" data-invalid="true">
            <twig:RadioGroup:Item id="invalid-both" name="notifications" value="both" aria-invalid="true" />
            <twig:Field:Label for="invalid-both" class="font-normal">Both Email &amp; SMS</twig:Field:Label>
        </twig:Field>
    </twig:RadioGroup>
</twig:Field:Set>
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

```twig {"preview":true}
<div class="flex flex-col gap-8">
    <twig:RadioGroup class="w-fit" dir="rtl">
        <twig:Field orientation="horizontal">
            <twig:RadioGroup:Item id="r1-rtl-ar" name="density-rtl-ar" value="default" dir="rtl" />
            <twig:Field:Content>
                <twig:Field:Label for="r1-rtl-ar" dir="rtl">افتراضي</twig:Field:Label>
                <twig:Field:Description dir="rtl">تباعد قياسي لمعظم حالات الاستخدام.</twig:Field:Description>
            </twig:Field:Content>
        </twig:Field>
        <twig:Field orientation="horizontal">
            <twig:RadioGroup:Item id="r2-rtl-ar" name="density-rtl-ar" value="comfortable" checked dir="rtl" />
            <twig:Field:Content>
                <twig:Field:Label for="r2-rtl-ar" dir="rtl">مريح</twig:Field:Label>
                <twig:Field:Description dir="rtl">مساحة أكبر بين العناصر.</twig:Field:Description>
            </twig:Field:Content>
        </twig:Field>
        <twig:Field orientation="horizontal">
            <twig:RadioGroup:Item id="r3-rtl-ar" name="density-rtl-ar" value="compact" dir="rtl" />
            <twig:Field:Content>
                <twig:Field:Label for="r3-rtl-ar" dir="rtl">مضغوط</twig:Field:Label>
                <twig:Field:Description dir="rtl">تباعد أدنى للتخطيطات الكثيفة.</twig:Field:Description>
            </twig:Field:Content>
        </twig:Field>
    </twig:RadioGroup>
    <twig:RadioGroup class="w-fit" dir="rtl">
        <twig:Field orientation="horizontal">
            <twig:RadioGroup:Item id="r1-rtl-he" name="density-rtl-he" value="default" dir="rtl" />
            <twig:Field:Content>
                <twig:Field:Label for="r1-rtl-he" dir="rtl">ברירת מחדל</twig:Field:Label>
                <twig:Field:Description dir="rtl">ריווח סטנדרטי לרוב מקרי השימוש.</twig:Field:Description>
            </twig:Field:Content>
        </twig:Field>
        <twig:Field orientation="horizontal">
            <twig:RadioGroup:Item id="r2-rtl-he" name="density-rtl-he" value="comfortable" checked dir="rtl" />
            <twig:Field:Content>
                <twig:Field:Label for="r2-rtl-he" dir="rtl">נוח</twig:Field:Label>
                <twig:Field:Description dir="rtl">יותר מקום בין האלמנטים.</twig:Field:Description>
            </twig:Field:Content>
        </twig:Field>
        <twig:Field orientation="horizontal">
            <twig:RadioGroup:Item id="r3-rtl-he" name="density-rtl-he" value="compact" dir="rtl" />
            <twig:Field:Content>
                <twig:Field:Label for="r3-rtl-he" dir="rtl">דחוס</twig:Field:Label>
                <twig:Field:Description dir="rtl">ריווח מינימלי לממשקים דחוסים.</twig:Field:Description>
            </twig:Field:Content>
        </twig:Field>
    </twig:RadioGroup>
</div>
```

## API Reference

::: api-reference
