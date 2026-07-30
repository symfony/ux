# Native Select

A styled native HTML select element with consistent design system integration.

```twig {"preview":true}
<twig:NativeSelect>
    <twig:NativeSelect:Option value="">Select status</twig:NativeSelect:Option>
    <twig:NativeSelect:Option value="todo">Todo</twig:NativeSelect:Option>
    <twig:NativeSelect:Option value="in-progress">In Progress</twig:NativeSelect:Option>
    <twig:NativeSelect:Option value="done">Done</twig:NativeSelect:Option>
    <twig:NativeSelect:Option value="cancelled">Cancelled</twig:NativeSelect:Option>
</twig:NativeSelect>
```

## Installation

::: installation

## Usage

```twig
<twig:NativeSelect name="fruit">
    <twig:NativeSelect:Option value="">Select a fruit</twig:NativeSelect:Option>
    <twig:NativeSelect:Option value="apple">Apple</twig:NativeSelect:Option>
    <twig:NativeSelect:Option value="banana">Banana</twig:NativeSelect:Option>
</twig:NativeSelect>
```

Native `option` and `optgroup` elements can also be used directly.

```twig
<twig:NativeSelect name="fruit">
    <option value="">Select a fruit</option>
    <option value="apple">Apple</option>
    <option value="banana">Banana</option>
</twig:NativeSelect>
```

## Examples

### Basic

```twig {"preview":true}
<twig:NativeSelect>
    <twig:NativeSelect:Option value="">Select a fruit</twig:NativeSelect:Option>
    <twig:NativeSelect:Option value="apple">Apple</twig:NativeSelect:Option>
    <twig:NativeSelect:Option value="banana">Banana</twig:NativeSelect:Option>
    <twig:NativeSelect:Option value="blueberry">Blueberry</twig:NativeSelect:Option>
    <twig:NativeSelect:Option value="grapes" disabled>Grapes</twig:NativeSelect:Option>
    <twig:NativeSelect:Option value="pineapple">Pineapple</twig:NativeSelect:Option>
</twig:NativeSelect>
```

### With Groups

```twig {"preview":true}
<twig:NativeSelect>
    <twig:NativeSelect:Option value="">Select a food</twig:NativeSelect:Option>
    <twig:NativeSelect:OptGroup label="Fruits">
        <twig:NativeSelect:Option value="apple">Apple</twig:NativeSelect:Option>
        <twig:NativeSelect:Option value="banana">Banana</twig:NativeSelect:Option>
        <twig:NativeSelect:Option value="blueberry">Blueberry</twig:NativeSelect:Option>
    </twig:NativeSelect:OptGroup>
    <twig:NativeSelect:OptGroup label="Vegetables">
        <twig:NativeSelect:Option value="carrot">Carrot</twig:NativeSelect:Option>
        <twig:NativeSelect:Option value="broccoli">Broccoli</twig:NativeSelect:Option>
        <twig:NativeSelect:Option value="spinach">Spinach</twig:NativeSelect:Option>
    </twig:NativeSelect:OptGroup>
</twig:NativeSelect>
```

### Sizes

```twig {"preview":true}
<div class="flex flex-col items-start gap-4">
    <twig:NativeSelect size="sm">
        <twig:NativeSelect:Option value="">Select a fruit</twig:NativeSelect:Option>
        <twig:NativeSelect:Option value="apple">Apple</twig:NativeSelect:Option>
        <twig:NativeSelect:Option value="banana">Banana</twig:NativeSelect:Option>
        <twig:NativeSelect:Option value="blueberry">Blueberry</twig:NativeSelect:Option>
    </twig:NativeSelect>
    <twig:NativeSelect size="default">
        <twig:NativeSelect:Option value="">Select a fruit</twig:NativeSelect:Option>
        <twig:NativeSelect:Option value="apple">Apple</twig:NativeSelect:Option>
        <twig:NativeSelect:Option value="banana">Banana</twig:NativeSelect:Option>
        <twig:NativeSelect:Option value="blueberry">Blueberry</twig:NativeSelect:Option>
    </twig:NativeSelect>
</div>
```

### With Field

```twig {"preview":true}
<twig:Field>
    <twig:Field:Label for="native-select-country">Country</twig:Field:Label>
    <twig:NativeSelect id="native-select-country">
        <twig:NativeSelect:Option value="">Select a country</twig:NativeSelect:Option>
        <twig:NativeSelect:Option value="us">United States</twig:NativeSelect:Option>
        <twig:NativeSelect:Option value="uk">United Kingdom</twig:NativeSelect:Option>
        <twig:NativeSelect:Option value="ca">Canada</twig:NativeSelect:Option>
        <twig:NativeSelect:Option value="au">Australia</twig:NativeSelect:Option>
    </twig:NativeSelect>
    <twig:Field:Description>Select your country of residence.</twig:Field:Description>
</twig:Field>
```

### Disabled

```twig {"preview":true}
<twig:NativeSelect disabled>
    <twig:NativeSelect:Option value="">Disabled</twig:NativeSelect:Option>
    <twig:NativeSelect:Option value="apple">Apple</twig:NativeSelect:Option>
    <twig:NativeSelect:Option value="banana">Banana</twig:NativeSelect:Option>
    <twig:NativeSelect:Option value="blueberry">Blueberry</twig:NativeSelect:Option>
</twig:NativeSelect>
```

### Invalid

```twig {"preview":true}
<twig:NativeSelect aria-invalid="true">
    <twig:NativeSelect:Option value="">Error state</twig:NativeSelect:Option>
    <twig:NativeSelect:Option value="apple">Apple</twig:NativeSelect:Option>
    <twig:NativeSelect:Option value="banana">Banana</twig:NativeSelect:Option>
    <twig:NativeSelect:Option value="blueberry">Blueberry</twig:NativeSelect:Option>
</twig:NativeSelect>
```

## Native Select vs Select

Use `NativeSelect` for native browser behavior, better performance, or mobile-optimized dropdowns. Use `Select` for custom styling, animations, or richer interactions.

## RTL

Use the `dir` attribute to switch the select direction.

```twig {"preview":true}
<twig:NativeSelect dir="rtl">
    <twig:NativeSelect:Option value="">اختر الحالة</twig:NativeSelect:Option>
    <twig:NativeSelect:Option value="todo">مهام</twig:NativeSelect:Option>
    <twig:NativeSelect:Option value="in-progress">قيد التنفيذ</twig:NativeSelect:Option>
    <twig:NativeSelect:Option value="done">منجز</twig:NativeSelect:Option>
    <twig:NativeSelect:Option value="cancelled">ملغي</twig:NativeSelect:Option>
</twig:NativeSelect>
```

```twig {"preview":true}
<twig:NativeSelect dir="rtl">
    <twig:NativeSelect:Option value="">בחר סטטוס</twig:NativeSelect:Option>
    <twig:NativeSelect:Option value="todo">לעשות</twig:NativeSelect:Option>
    <twig:NativeSelect:Option value="in-progress">בתהליך</twig:NativeSelect:Option>
    <twig:NativeSelect:Option value="done">הושלם</twig:NativeSelect:Option>
    <twig:NativeSelect:Option value="cancelled">בוטל</twig:NativeSelect:Option>
</twig:NativeSelect>
```

## API Reference

::: api-reference
