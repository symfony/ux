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
<twig:NativeSelect>
    <twig:NativeSelect:Option value="">Select a fruit</twig:NativeSelect:Option>
    <twig:NativeSelect:Option value="apple">Apple</twig:NativeSelect:Option>
    <twig:NativeSelect:Option value="banana">Banana</twig:NativeSelect:Option>
    <twig:NativeSelect:Option value="blueberry">Blueberry</twig:NativeSelect:Option>
</twig:NativeSelect>
```

## Examples

### Groups

Use `NativeSelect:OptGroup` to organize options into labeled groups.

```twig {"preview":true}
<twig:NativeSelect>
    <twig:NativeSelect:Option value="">Select department</twig:NativeSelect:Option>
    <twig:NativeSelect:OptGroup label="Engineering">
        <twig:NativeSelect:Option value="frontend">Frontend</twig:NativeSelect:Option>
        <twig:NativeSelect:Option value="backend">Backend</twig:NativeSelect:Option>
        <twig:NativeSelect:Option value="devops">DevOps</twig:NativeSelect:Option>
    </twig:NativeSelect:OptGroup>
    <twig:NativeSelect:OptGroup label="Sales">
        <twig:NativeSelect:Option value="sales-rep">Sales Rep</twig:NativeSelect:Option>
        <twig:NativeSelect:Option value="account-manager">Account Manager</twig:NativeSelect:Option>
        <twig:NativeSelect:Option value="sales-director">Sales Director</twig:NativeSelect:Option>
    </twig:NativeSelect:OptGroup>
    <twig:NativeSelect:OptGroup label="Operations">
        <twig:NativeSelect:Option value="support">Customer Support</twig:NativeSelect:Option>
        <twig:NativeSelect:Option value="product-manager">Product Manager</twig:NativeSelect:Option>
        <twig:NativeSelect:Option value="ops-manager">Operations Manager</twig:NativeSelect:Option>
    </twig:NativeSelect:OptGroup>
</twig:NativeSelect>
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

Set `aria-invalid="true"` to display the error state.

```twig {"preview":true}
<twig:NativeSelect aria-invalid="true">
    <twig:NativeSelect:Option value="">Error state</twig:NativeSelect:Option>
    <twig:NativeSelect:Option value="apple">Apple</twig:NativeSelect:Option>
    <twig:NativeSelect:Option value="banana">Banana</twig:NativeSelect:Option>
    <twig:NativeSelect:Option value="blueberry">Blueberry</twig:NativeSelect:Option>
</twig:NativeSelect>
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

```twig {"preview":true}
<div class="flex flex-col items-center gap-4">
    {# Arabic #}
    <div dir="rtl">
        <twig:NativeSelect>
            <twig:NativeSelect:Option value="">اختر الحالة</twig:NativeSelect:Option>
            <twig:NativeSelect:Option value="todo">مهام</twig:NativeSelect:Option>
            <twig:NativeSelect:Option value="in-progress">قيد التنفيذ</twig:NativeSelect:Option>
            <twig:NativeSelect:Option value="done">منجز</twig:NativeSelect:Option>
            <twig:NativeSelect:Option value="cancelled">ملغي</twig:NativeSelect:Option>
        </twig:NativeSelect>
    </div>

    {# Hebrew #}
    <div dir="rtl">
        <twig:NativeSelect>
            <twig:NativeSelect:Option value="">בחר סטטוס</twig:NativeSelect:Option>
            <twig:NativeSelect:Option value="todo">לעשות</twig:NativeSelect:Option>
            <twig:NativeSelect:Option value="in-progress">בתהליך</twig:NativeSelect:Option>
            <twig:NativeSelect:Option value="done">הושלם</twig:NativeSelect:Option>
            <twig:NativeSelect:Option value="cancelled">בוטל</twig:NativeSelect:Option>
        </twig:NativeSelect>
    </div>
</div>
```

## API Reference

::: api-reference
