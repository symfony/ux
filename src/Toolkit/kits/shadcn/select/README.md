# Select

Displays a list of options for the user to pick from—triggered by a button.

```twig {"preview":true}
<twig:Select id="select-fruit-demo">
    <twig:Select:Trigger>
        <twig:Button variant="outline" class="w-full max-w-48" {{ ...select_trigger_attrs }}>
            <twig:Select:Value placeholder="Select a fruit" />
            <twig:ux:icon name="lucide:chevron-down" class="text-muted-foreground" />
        </twig:Button>
    </twig:Select:Trigger>
    <twig:Select:Content>
        <twig:Select:Group>
            <twig:Select:Label>Fruits</twig:Select:Label>
            <twig:Select:Item value="apple">Apple</twig:Select:Item>
            <twig:Select:Item value="banana">Banana</twig:Select:Item>
            <twig:Select:Item value="blueberry">Blueberry</twig:Select:Item>
            <twig:Select:Item value="grapes">Grapes</twig:Select:Item>
            <twig:Select:Item value="pineapple">Pineapple</twig:Select:Item>
        </twig:Select:Group>
    </twig:Select:Content>
</twig:Select>
```

## Installation

::: installation

## Usage

```twig
<twig:Select id="select-theme" name="theme" value="system">
    <twig:Select:Trigger>
        <twig:Button variant="outline" class="w-48" {{ ...select_trigger_attrs }}>
            <twig:Select:Value placeholder="Theme" />
            <twig:ux:icon name="lucide:chevron-down" class="text-muted-foreground" />
        </twig:Button>
    </twig:Select:Trigger>
    <twig:Select:Content>
        <twig:Select:Group>
            <twig:Select:Item value="light">Light</twig:Select:Item>
            <twig:Select:Item value="dark">Dark</twig:Select:Item>
            <twig:Select:Item value="system">System</twig:Select:Item>
        </twig:Select:Group>
    </twig:Select:Content>
</twig:Select>
```

## Examples

### Align Item With Trigger

Use `position="item-aligned"` to align the selected item with the trigger, or `position="popper"` to align the popup edge with it.

```twig {"preview":true,"height":"300px"}
<div class="grid w-full max-w-xs gap-6">
    <twig:Field>
        <twig:Field:Label>Item aligned</twig:Field:Label>
        <twig:Field:Description>The selected item is aligned with the trigger.</twig:Field:Description>
        <twig:Select id="select-align-item" value="banana">
            <twig:Select:Trigger>
                <twig:Button variant="outline" class="w-full" {{ ...select_trigger_attrs }}>
                    <twig:Select:Value />
                    <twig:ux:icon name="lucide:chevron-down" class="text-muted-foreground" />
                </twig:Button>
            </twig:Select:Trigger>
            <twig:Select:Content position="item-aligned">
                <twig:Select:Group>
                    <twig:Select:Item value="apple">Apple</twig:Select:Item>
                    <twig:Select:Item value="banana">Banana</twig:Select:Item>
                    <twig:Select:Item value="blueberry">Blueberry</twig:Select:Item>
                    <twig:Select:Item value="grapes">Grapes</twig:Select:Item>
                    <twig:Select:Item value="pineapple">Pineapple</twig:Select:Item>
                </twig:Select:Group>
            </twig:Select:Content>
        </twig:Select>
    </twig:Field>
    <twig:Field>
        <twig:Field:Label>Popper</twig:Field:Label>
        <twig:Field:Description>The popup edge is aligned with the trigger.</twig:Field:Description>
        <twig:Select id="select-align-popper" value="banana">
            <twig:Select:Trigger>
                <twig:Button variant="outline" class="w-full" {{ ...select_trigger_attrs }}>
                    <twig:Select:Value />
                    <twig:ux:icon name="lucide:chevron-down" class="text-muted-foreground" />
                </twig:Button>
            </twig:Select:Trigger>
            <twig:Select:Content position="popper">
                <twig:Select:Group>
                    <twig:Select:Item value="apple">Apple</twig:Select:Item>
                    <twig:Select:Item value="banana">Banana</twig:Select:Item>
                    <twig:Select:Item value="blueberry">Blueberry</twig:Select:Item>
                    <twig:Select:Item value="grapes">Grapes</twig:Select:Item>
                    <twig:Select:Item value="pineapple">Pineapple</twig:Select:Item>
                </twig:Select:Group>
            </twig:Select:Content>
        </twig:Select>
    </twig:Field>
</div>
```

### Groups

Use `Select:Group`, `Select:Label`, and `Select:Separator` to organize items.

```twig {"preview":true}
<twig:Select id="select-groups">
    <twig:Select:Trigger>
        <twig:Button variant="outline" class="w-full max-w-48" {{ ...select_trigger_attrs }}>
            <twig:Select:Value placeholder="Select a fruit" />
            <twig:ux:icon name="lucide:chevron-down" class="text-muted-foreground" />
        </twig:Button>
    </twig:Select:Trigger>
    <twig:Select:Content>
        <twig:Select:Group>
            <twig:Select:Label>Fruits</twig:Select:Label>
            <twig:Select:Item value="apple">Apple</twig:Select:Item>
            <twig:Select:Item value="banana">Banana</twig:Select:Item>
            <twig:Select:Item value="blueberry">Blueberry</twig:Select:Item>
        </twig:Select:Group>
        <twig:Select:Separator />
        <twig:Select:Group>
            <twig:Select:Label>Vegetables</twig:Select:Label>
            <twig:Select:Item value="carrot">Carrot</twig:Select:Item>
            <twig:Select:Item value="broccoli">Broccoli</twig:Select:Item>
            <twig:Select:Item value="spinach">Spinach</twig:Select:Item>
        </twig:Select:Group>
    </twig:Select:Content>
</twig:Select>
```

### Scrollable

A select with many items that scrolls.

```twig {"preview":true,"height":"300px"}
{% set timezoneGroups = {
    'North America': {
        est: 'Eastern Standard Time',
        cst: 'Central Standard Time',
        mst: 'Mountain Standard Time',
        pst: 'Pacific Standard Time',
        akst: 'Alaska Standard Time',
        hst: 'Hawaii Standard Time',
    },
    'Europe & Africa': {
        gmt: 'Greenwich Mean Time',
        cet: 'Central European Time',
        eet: 'Eastern European Time',
        west: 'Western European Summer Time',
        cat: 'Central Africa Time',
        eat: 'East Africa Time',
    },
    'Asia': {
        msk: 'Moscow Time',
        ist: 'India Standard Time',
        cst_china: 'China Standard Time',
        jst: 'Japan Standard Time',
        kst: 'Korea Standard Time',
        ist_indonesia: 'Indonesia Central Standard Time',
    },
    'Australia & Pacific': {
        awst: 'Australian Western Standard Time',
        acst: 'Australian Central Standard Time',
        aest: 'Australian Eastern Standard Time',
        nzst: 'New Zealand Standard Time',
        fjt: 'Fiji Time',
    },
    'South America': {
        art: 'Argentina Time',
        bot: 'Bolivia Time',
        brt: 'Brasilia Time',
        clt: 'Chile Standard Time',
    },
} %}
<twig:Select id="select-timezone">
    <twig:Select:Trigger>
        <twig:Button variant="outline" class="w-full max-w-64" {{ ...select_trigger_attrs }}>
            <twig:Select:Value placeholder="Select a timezone" />
            <twig:ux:icon name="lucide:chevron-down" class="text-muted-foreground" />
        </twig:Button>
    </twig:Select:Trigger>
    <twig:Select:Content>
        {% for label, timezones in timezoneGroups %}
            <twig:Select:Group>
                <twig:Select:Label>{{ label }}</twig:Select:Label>
                {% for value, timezone in timezones %}
                    <twig:Select:Item value="{{ value }}">{{ timezone }}</twig:Select:Item>
                {% endfor %}
            </twig:Select:Group>
        {% endfor %}
    </twig:Select:Content>
</twig:Select>
```

### Disabled

```twig {"preview":true}
<twig:Select id="select-disabled" disabled>
    <twig:Select:Trigger>
        <twig:Button variant="outline" class="w-full max-w-48" {{ ...select_trigger_attrs }}>
            <twig:Select:Value placeholder="Select a fruit" />
            <twig:ux:icon name="lucide:chevron-down" class="text-muted-foreground" />
        </twig:Button>
    </twig:Select:Trigger>
    <twig:Select:Content>
        <twig:Select:Group>
            <twig:Select:Item value="apple">Apple</twig:Select:Item>
            <twig:Select:Item value="banana">Banana</twig:Select:Item>
            <twig:Select:Item value="blueberry">Blueberry</twig:Select:Item>
            <twig:Select:Item value="grapes" disabled>Grapes</twig:Select:Item>
            <twig:Select:Item value="pineapple">Pineapple</twig:Select:Item>
        </twig:Select:Group>
    </twig:Select:Content>
</twig:Select>
```

### Invalid

Add `data-invalid` to `Field` and `aria-invalid` to the trigger element to show an error state.

```twig {"preview":true}
<twig:Field data-invalid class="w-full max-w-48">
    <twig:Field:Label>Fruit</twig:Field:Label>
    <twig:Select id="select-invalid">
        <twig:Select:Trigger>
            <twig:Button variant="outline" aria-invalid="true" {{ ...select_trigger_attrs }}>
                <twig:Select:Value placeholder="Select a fruit" />
                <twig:ux:icon name="lucide:chevron-down" class="text-muted-foreground" />
            </twig:Button>
        </twig:Select:Trigger>
        <twig:Select:Content>
            <twig:Select:Group>
                <twig:Select:Item value="apple">Apple</twig:Select:Item>
                <twig:Select:Item value="banana">Banana</twig:Select:Item>
                <twig:Select:Item value="blueberry">Blueberry</twig:Select:Item>
            </twig:Select:Group>
        </twig:Select:Content>
    </twig:Select>
    <twig:Field:Error>Please select a fruit.</twig:Field:Error>
</twig:Field>
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

```twig {"preview":true,"height":"300px"}
<div class="grid gap-6">
    <twig:Select id="select-fruit-ar" dir="rtl">
        <twig:Select:Trigger>
            <twig:Button variant="outline" class="w-40" {{ ...select_trigger_attrs }}>
                <twig:Select:Value placeholder="اختر فاكهة" />
                <twig:ux:icon name="lucide:chevron-down" class="text-muted-foreground" />
            </twig:Button>
        </twig:Select:Trigger>
        <twig:Select:Content>
            <twig:Select:Group>
                <twig:Select:Label>الفواكه</twig:Select:Label>
                <twig:Select:Item value="apple">تفاح</twig:Select:Item>
                <twig:Select:Item value="banana">موز</twig:Select:Item>
                <twig:Select:Item value="blueberry">توت أزرق</twig:Select:Item>
                <twig:Select:Item value="grapes">عنب</twig:Select:Item>
                <twig:Select:Item value="pineapple">أناناس</twig:Select:Item>
            </twig:Select:Group>
            <twig:Select:Separator />
            <twig:Select:Group>
                <twig:Select:Label>الخضروات</twig:Select:Label>
                <twig:Select:Item value="carrot">جزر</twig:Select:Item>
                <twig:Select:Item value="broccoli">بروكلي</twig:Select:Item>
                <twig:Select:Item value="spinach">سبانخ</twig:Select:Item>
            </twig:Select:Group>
        </twig:Select:Content>
    </twig:Select>
    <twig:Select id="select-fruit-he" dir="rtl">
        <twig:Select:Trigger>
            <twig:Button variant="outline" class="w-40" {{ ...select_trigger_attrs }}>
                <twig:Select:Value placeholder="בחר פרי" />
                <twig:ux:icon name="lucide:chevron-down" class="text-muted-foreground" />
            </twig:Button>
        </twig:Select:Trigger>
        <twig:Select:Content>
            <twig:Select:Group>
                <twig:Select:Label>פירות</twig:Select:Label>
                <twig:Select:Item value="apple">תפוח</twig:Select:Item>
                <twig:Select:Item value="banana">בננה</twig:Select:Item>
                <twig:Select:Item value="blueberry">אוכמניה</twig:Select:Item>
                <twig:Select:Item value="grapes">ענבים</twig:Select:Item>
                <twig:Select:Item value="pineapple">אננס</twig:Select:Item>
            </twig:Select:Group>
            <twig:Select:Separator />
            <twig:Select:Group>
                <twig:Select:Label>ירקות</twig:Select:Label>
                <twig:Select:Item value="carrot">גזר</twig:Select:Item>
                <twig:Select:Item value="broccoli">ברוקולי</twig:Select:Item>
                <twig:Select:Item value="spinach">תרד</twig:Select:Item>
            </twig:Select:Group>
        </twig:Select:Content>
    </twig:Select>
</div>
```

## API Reference

::: api-reference
