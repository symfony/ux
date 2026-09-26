# Select

Displays a list of options for the user to pick from—triggered by a button.

```twig {"preview":true}
<div class="mx-auto w-full max-w-48" style="min-height: 320px">
    <twig:Select id="demo" class="block">
        <twig:Select:Trigger class="w-full">
            <twig:Select:Value placeholder="Select a fruit" />
        </twig:Select:Trigger>
        <twig:Select:Content>
            <twig:Select:Group id="demo-fruits">
                <twig:Select:Label>Fruits</twig:Select:Label>
                <twig:Select:Item value="apple">Apple</twig:Select:Item>
                <twig:Select:Item value="banana">Banana</twig:Select:Item>
                <twig:Select:Item value="blueberry">Blueberry</twig:Select:Item>
                <twig:Select:Item value="grapes">Grapes</twig:Select:Item>
                <twig:Select:Item value="pineapple">Pineapple</twig:Select:Item>
            </twig:Select:Group>
        </twig:Select:Content>
    </twig:Select>
</div>
```

## Installation

::: installation

## Usage

```twig
<twig:Select id="fruit" name="fruit" value="banana">
    <twig:Select:Trigger>
        <twig:Select:Value placeholder="Select a fruit" />
    </twig:Select:Trigger>
    <twig:Select:Content position="item-aligned | popper">
        <twig:Select:Group id="fruits">
            <twig:Select:Label>Fruits</twig:Select:Label>
            <twig:Select:Item value="apple">Apple</twig:Select:Item>
            <twig:Select:Item value="banana">Banana</twig:Select:Item>
        </twig:Select:Group>
        <twig:Select:Separator />
        <twig:Select:Group id="vegetables">
            <twig:Select:Label>Vegetables</twig:Select:Label>
            <twig:Select:Item value="carrot">Carrot</twig:Select:Item>
        </twig:Select:Group>
    </twig:Select:Content>
</twig:Select>
```

Set `name` to render the hidden input that carries the selected value on form submission, and `value` to
preselect an option. Selecting an option dispatches a `select:change` event carrying the new `value` and
`label`.

## Examples

### Align Item With Trigger

Use the `position` prop on `Select:Content` to control alignment. With `position="item-aligned"` (the
default), the popup is placed so the selected item appears over the trigger. With `position="popper"`, it
is aligned to the trigger edge.

```twig {"preview":true}
<div class="flex w-full max-w-xs flex-col gap-6" style="min-height: 340px">
    <twig:Field>
        <twig:Field:Label for="align-item-aligned">Item aligned</twig:Field:Label>
        <twig:Select id="align-item-aligned" value="banana">
            <twig:Select:Trigger class="w-full">
                <twig:Select:Value placeholder="Select a fruit" />
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
        <twig:Field:Label for="align-popper">Popper</twig:Field:Label>
        <twig:Select id="align-popper" value="banana">
            <twig:Select:Trigger class="w-full">
                <twig:Select:Value placeholder="Select a fruit" />
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

Use `Select:Group`, `Select:Label` and `Select:Separator` to organize items.

```twig {"preview":true}
<div class="mx-auto w-full max-w-48" style="min-height: 380px">
    <twig:Select id="groups" class="block">
        <twig:Select:Trigger class="w-full">
            <twig:Select:Value placeholder="Select a fruit" />
        </twig:Select:Trigger>
        <twig:Select:Content>
            <twig:Select:Group id="groups-fruits">
                <twig:Select:Label>Fruits</twig:Select:Label>
                <twig:Select:Item value="apple">Apple</twig:Select:Item>
                <twig:Select:Item value="banana">Banana</twig:Select:Item>
                <twig:Select:Item value="blueberry">Blueberry</twig:Select:Item>
            </twig:Select:Group>
            <twig:Select:Separator />
            <twig:Select:Group id="groups-vegetables">
                <twig:Select:Label>Vegetables</twig:Select:Label>
                <twig:Select:Item value="carrot">Carrot</twig:Select:Item>
                <twig:Select:Item value="broccoli">Broccoli</twig:Select:Item>
                <twig:Select:Item value="spinach">Spinach</twig:Select:Item>
            </twig:Select:Group>
        </twig:Select:Content>
    </twig:Select>
</div>
```

### Scrollable

A select with many items that scrolls.

```twig {"preview":true}
<div class="mx-auto w-full max-w-64" style="min-height: 380px">
    <twig:Select id="scrollable" class="block">
        <twig:Select:Trigger class="w-full">
            <twig:Select:Value placeholder="Select a timezone" />
        </twig:Select:Trigger>
        <twig:Select:Content>
            <twig:Select:Group id="scrollable-north-america">
                <twig:Select:Label>North America</twig:Select:Label>
                <twig:Select:Item value="est">Eastern Standard Time</twig:Select:Item>
                <twig:Select:Item value="cst">Central Standard Time</twig:Select:Item>
                <twig:Select:Item value="mst">Mountain Standard Time</twig:Select:Item>
                <twig:Select:Item value="pst">Pacific Standard Time</twig:Select:Item>
                <twig:Select:Item value="akst">Alaska Standard Time</twig:Select:Item>
                <twig:Select:Item value="hst">Hawaii Standard Time</twig:Select:Item>
            </twig:Select:Group>
            <twig:Select:Group id="scrollable-europe-africa">
                <twig:Select:Label>Europe &amp; Africa</twig:Select:Label>
                <twig:Select:Item value="gmt">Greenwich Mean Time</twig:Select:Item>
                <twig:Select:Item value="cet">Central European Time</twig:Select:Item>
                <twig:Select:Item value="eet">Eastern European Time</twig:Select:Item>
                <twig:Select:Item value="west">Western European Summer Time</twig:Select:Item>
                <twig:Select:Item value="cat">Central Africa Time</twig:Select:Item>
                <twig:Select:Item value="eat">East Africa Time</twig:Select:Item>
            </twig:Select:Group>
            <twig:Select:Group id="scrollable-asia">
                <twig:Select:Label>Asia</twig:Select:Label>
                <twig:Select:Item value="msk">Moscow Time</twig:Select:Item>
                <twig:Select:Item value="ist">India Standard Time</twig:Select:Item>
                <twig:Select:Item value="cst_china">China Standard Time</twig:Select:Item>
                <twig:Select:Item value="jst">Japan Standard Time</twig:Select:Item>
                <twig:Select:Item value="kst">Korea Standard Time</twig:Select:Item>
                <twig:Select:Item value="ist_indonesia">Indonesia Central Standard Time</twig:Select:Item>
            </twig:Select:Group>
            <twig:Select:Group id="scrollable-australia-pacific">
                <twig:Select:Label>Australia &amp; Pacific</twig:Select:Label>
                <twig:Select:Item value="awst">Australian Western Standard Time</twig:Select:Item>
                <twig:Select:Item value="acst">Australian Central Standard Time</twig:Select:Item>
                <twig:Select:Item value="aest">Australian Eastern Standard Time</twig:Select:Item>
                <twig:Select:Item value="nzst">New Zealand Standard Time</twig:Select:Item>
                <twig:Select:Item value="fjt">Fiji Time</twig:Select:Item>
            </twig:Select:Group>
            <twig:Select:Group id="scrollable-south-america">
                <twig:Select:Label>South America</twig:Select:Label>
                <twig:Select:Item value="art">Argentina Time</twig:Select:Item>
                <twig:Select:Item value="bot">Bolivia Time</twig:Select:Item>
                <twig:Select:Item value="brt">Brasilia Time</twig:Select:Item>
                <twig:Select:Item value="clt">Chile Standard Time</twig:Select:Item>
            </twig:Select:Group>
        </twig:Select:Content>
    </twig:Select>
</div>
```

### Disabled

Set `disabled` on the root to disable the whole control, or on a single `Select:Item` to disable just that
option.

```twig {"preview":true}
<div class="mx-auto w-full max-w-48">
    <twig:Select id="disabled" class="block" disabled>
        <twig:Select:Trigger class="w-full">
            <twig:Select:Value placeholder="Select a fruit" />
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
</div>
```

### Invalid

Add the `data-invalid` attribute to the `Field` component and the `aria-invalid` attribute to the
`Select:Trigger` component to show an error state.

```twig {"preview":true}
<div class="w-full max-w-48" style="min-height: 280px">
    <twig:Field data-invalid="true">
        <twig:Field:Label for="invalid">Fruit</twig:Field:Label>
        <twig:Select id="invalid">
            <twig:Select:Trigger class="w-full" aria-invalid="true">
                <twig:Select:Value placeholder="Select a fruit" />
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
</div>
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

```twig {"preview":true}
<div class="flex flex-col items-center gap-4" style="min-height: 460px">
    {# Arabic #}
    <div dir="rtl">
        <twig:Select id="rtl-ar">
            <twig:Select:Trigger class="w-48">
                <twig:Select:Value placeholder="اختر فاكهة" />
            </twig:Select:Trigger>
            <twig:Select:Content>
                <twig:Select:Group id="rtl-ar-fruits">
                    <twig:Select:Label>الفواكه</twig:Select:Label>
                    <twig:Select:Item value="apple">تفاح</twig:Select:Item>
                    <twig:Select:Item value="banana">موز</twig:Select:Item>
                    <twig:Select:Item value="blueberry">توت أزرق</twig:Select:Item>
                </twig:Select:Group>
                <twig:Select:Separator />
                <twig:Select:Group id="rtl-ar-vegetables">
                    <twig:Select:Label>الخضروات</twig:Select:Label>
                    <twig:Select:Item value="carrot">جزر</twig:Select:Item>
                    <twig:Select:Item value="broccoli">بروكلي</twig:Select:Item>
                    <twig:Select:Item value="spinach">سبانخ</twig:Select:Item>
                </twig:Select:Group>
            </twig:Select:Content>
        </twig:Select>
    </div>

    {# Hebrew #}
    <div dir="rtl">
        <twig:Select id="rtl-he">
            <twig:Select:Trigger class="w-48">
                <twig:Select:Value placeholder="בחר פרי" />
            </twig:Select:Trigger>
            <twig:Select:Content>
                <twig:Select:Group id="rtl-he-fruits">
                    <twig:Select:Label>פירות</twig:Select:Label>
                    <twig:Select:Item value="apple">תפוח</twig:Select:Item>
                    <twig:Select:Item value="banana">בננה</twig:Select:Item>
                    <twig:Select:Item value="blueberry">אוכמניה</twig:Select:Item>
                </twig:Select:Group>
                <twig:Select:Separator />
                <twig:Select:Group id="rtl-he-vegetables">
                    <twig:Select:Label>ירקות</twig:Select:Label>
                    <twig:Select:Item value="carrot">גזר</twig:Select:Item>
                    <twig:Select:Item value="broccoli">ברוקולי</twig:Select:Item>
                    <twig:Select:Item value="spinach">תרד</twig:Select:Item>
                </twig:Select:Group>
            </twig:Select:Content>
        </twig:Select>
    </div>
</div>
```

## Accessibility

- `Select:Trigger` is a `role="combobox"` button wired to the popup with `aria-haspopup="listbox"`,
  `aria-controls` and `aria-expanded`, which the Stimulus controller keeps in sync as the popup opens and
  closes.
- The popup exposes a `role="listbox"` viewport labelled by the trigger, and each `Select:Item` is a
  `role="option"` carrying `aria-selected`. Focus moves to the options themselves (roving `tabindex="-1"`),
  so the active option is the focused element.
- Keyboard: <kbd>Enter</kbd>, <kbd>Space</kbd>, <kbd>↓</kbd> and <kbd>↑</kbd> open the popup;
  <kbd>↓</kbd>/<kbd>↑</kbd> move between options, <kbd>Home</kbd>/<kbd>End</kbd> jump to the first and last;
  typing letters jumps to the first option starting with them; <kbd>Enter</kbd> or <kbd>Space</kbd> selects;
  <kbd>Escape</kbd> closes and returns focus to the trigger; <kbd>Tab</kbd> closes and moves on.
- `Select:Value` renders its `placeholder` server-side; the controller replaces it with the selected
  option's label on connect. Without JavaScript the trigger therefore shows the placeholder, so keep the
  value in a `Field:Description` or a server-rendered summary when that matters.
- Give `Select:Group` an `id` so its `Select:Label` is wired with `aria-labelledby` and the group is
  announced with a name. A group without an `id` is announced as an unnamed group.
- Set `aria-invalid="true"` on `Select:Trigger` for the invalid styling, and point `aria-describedby` at the
  message explaining what is wrong, as the `Invalid` example does.
- `disabled` on the root disables the trigger itself; `disabled` on an item marks it `aria-disabled` and
  takes it out of the keyboard order while leaving it visible.

## API Reference

::: api-reference
