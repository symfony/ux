# Switch

A control that allows the user to toggle between checked and not checked.

```twig {"preview":true}
<div class="flex items-center space-x-2">
    <twig:Switch id="airplane-mode" />
    <twig:Label for="airplane-mode">Airplane Mode</twig:Label>
</div>
```

## Installation

::: installation

## Usage

```twig
<twig:Switch />
```

## Examples

### Description

```twig {"preview":true}
<twig:Field orientation="horizontal" class="max-w-sm">
    <twig:Field:Content>
        <twig:Field:Label for="switch-focus-mode">Share across devices</twig:Field:Label>
        <twig:Field:Description>Focus is shared across devices, and turns off when you leave the app.</twig:Field:Description>
    </twig:Field:Content>
    <twig:Switch id="switch-focus-mode" />
</twig:Field>
```

### Choice Card

Card-style selection where `Field:Label` wraps the entire `Field` for a clickable card pattern.

```twig {"preview":true}
<twig:Field:Group class="w-full max-w-sm">
    <twig:Field:Label for="switch-share">
        <twig:Field orientation="horizontal">
            <twig:Field:Content>
                <twig:Field:Title>Share across devices</twig:Field:Title>
                <twig:Field:Description>Focus is shared across devices, and turns off when you leave the app.</twig:Field:Description>
            </twig:Field:Content>
            <twig:Switch id="switch-share" />
        </twig:Field>
    </twig:Field:Label>
    <twig:Field:Label for="switch-notifications">
        <twig:Field orientation="horizontal">
            <twig:Field:Content>
                <twig:Field:Title>Enable notifications</twig:Field:Title>
                <twig:Field:Description>Receive notifications when focus mode is enabled or disabled.</twig:Field:Description>
            </twig:Field:Content>
            <twig:Switch id="switch-notifications" checked />
        </twig:Field>
    </twig:Field:Label>
</twig:Field:Group>
```

### Disabled

Add the `disabled` prop to the `Switch` to disable it. Add the `data-disabled` prop to the `Field` for styling.

```twig {"preview":true}
<twig:Field orientation="horizontal" data-disabled class="w-fit">
    <twig:Switch id="switch-disabled-unchecked" disabled />
    <twig:Field:Label for="switch-disabled-unchecked">Disabled</twig:Field:Label>
</twig:Field>
```

### Invalid

Add `aria-invalid="true"` to the `Switch` to indicate an invalid state. Add `data-invalid` to the `Field` for styling.

```twig {"preview":true}
<twig:Field orientation="horizontal" class="max-w-sm" data-invalid>
    <twig:Field:Content>
        <twig:Field:Label for="switch-terms">Accept terms and conditions</twig:Field:Label>
        <twig:Field:Description>You must accept the terms and conditions to continue.</twig:Field:Description>
    </twig:Field:Content>
    <twig:Switch id="switch-terms" aria-invalid="true" />
</twig:Field>
```

### Size

Use the `size` prop to change the size of the switch.

```twig {"preview":true}
<twig:Field:Group class="w-full max-w-[10rem]">
    <twig:Field orientation="horizontal">
        <twig:Switch id="switch-size-sm" size="sm" />
        <twig:Field:Label for="switch-size-sm">Small</twig:Field:Label>
    </twig:Field>
    <twig:Field orientation="horizontal">
        <twig:Switch id="switch-size-default" size="default" />
        <twig:Field:Label for="switch-size-default">Default</twig:Field:Label>
    </twig:Field>
</twig:Field:Group>
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

```twig {"preview":true}
<div class="w-full flex flex-col items-center gap-8">
    <twig:Field orientation="horizontal" class="max-w-sm" dir="rtl">
        <twig:Field:Content>
            <twig:Field:Label for="switch-rtl-ar">المشاركة عبر الأجهزة</twig:Field:Label>
            <twig:Field:Description>يتم مشاركة التركيز عبر الأجهزة، ويتم إيقاف تشغيله عند مغادرة التطبيق.</twig:Field:Description>
        </twig:Field:Content>
        <twig:Switch id="switch-rtl-ar" />
    </twig:Field>

    <twig:Field orientation="horizontal" class="max-w-sm" dir="rtl">
        <twig:Field:Content>
            <twig:Field:Label for="switch-rtl-he">שיתוף בין מכשירים</twig:Field:Label>
            <twig:Field:Description>המיקוד משותף בין מכשירים, וכבה כשאתה עוזב את התוכנה.</twig:Field:Description>
        </twig:Field:Content>
        <twig:Switch id="switch-rtl-he" />
    </twig:Field>
</div>
```

## API Reference

::: api-reference
