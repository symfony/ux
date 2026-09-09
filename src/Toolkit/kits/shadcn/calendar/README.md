# Calendar

A calendar for selecting a single date, several dates, or a range of dates.

```twig {"preview":true,"height":"340px"}
<twig:Calendar
    mode="single"
    today="2026-03-15"
    selected="2026-03-15"
    captionLayout="dropdown"
    class="mx-auto rounded-lg border"
/>
```

## Installation

::: installation

## Usage

```twig
<twig:Calendar
    mode="single | multiple | range"
    name="date"
    selected="2026-03-15"
    month="2026-03-01"
    captionLayout="label | dropdown"
/>
```

Dates are exchanged as `Y-m-d` strings. When `name` is set, the selection is mirrored into
hidden inputs (`name`, `name[]` in `multiple` mode, `name[from]` and `name[to]` in `range` mode)
so the calendar can be submitted with a form.

## Examples

### Basic

A calendar with no initial selection. Use `class="rounded-lg border"` to frame it.

```twig {"preview":true,"height":"320px"}
<twig:Calendar
    mode="single"
    today="2026-03-15"
    month="2026-03-01"
    class="mx-auto rounded-lg border"
/>
```

### Range Calendar

Use `mode="range"` to select a period. The first entry of `selected` is the start of the range,
the second one its end.

```twig {"preview":true,"height":"360px"}
<twig:Card class="mx-auto w-fit p-0">
    <twig:Card:Content class="p-0">
        <twig:Calendar
            mode="range"
            today="2026-03-15"
            month="2026-01-01"
            :selected="['2026-01-12', '2026-02-11']"
            numberOfMonths="2"
            minDate="1900-01-01"
            maxDate="2026-03-15"
        />
    </twig:Card:Content>
</twig:Card>
```

### Multiple

Use `mode="multiple"` to select any number of individual dates.

```twig {"preview":true,"height":"320px"}
<twig:Card class="mx-auto w-fit p-0">
    <twig:Card:Content class="p-0">
        <twig:Calendar
            mode="multiple"
            today="2026-03-15"
            month="2026-03-01"
            :selected="['2026-03-04', '2026-03-11', '2026-03-18']"
        />
    </twig:Card:Content>
</twig:Card>
```

### Month and Year Selector

Use `captionLayout="dropdown"` to replace the caption with month and year dropdowns. Their range
is bounded by `startMonth` and `endMonth`.

```twig {"preview":true,"height":"320px"}
<twig:Calendar
    mode="single"
    today="2026-03-15"
    month="2026-03-01"
    captionLayout="dropdown"
    startMonth="2024-01-01"
    endMonth="2028-12-01"
    class="mx-auto rounded-lg border"
/>
```

### Presets

Anything placed inside the calendar is rendered below the grid, and is within reach of the
`calendar` controller: a button carrying `data-action="click->calendar#selectDate"` and a
`data-calendar-date-param` selects that date and jumps to its month.

```twig {"preview":true,"height":"400px"}
<twig:Card size="sm" class="mx-auto w-fit max-w-[300px]">
    <twig:Card:Content>
        <twig:Calendar
            mode="single"
            today="2026-03-15"
            selected="2026-02-12"
            fixedWeeks
            class="p-0 [--cell-size:--spacing(9.5)]"
        >
            <div class="mt-3 flex flex-wrap gap-2 border-t pt-3">
                {% for preset in [{label: 'Today', days: 0}, {label: 'Tomorrow', days: 1}, {label: 'In 3 days', days: 3}, {label: 'In a week', days: 7}, {label: 'In 2 weeks', days: 14}] %}
                    <twig:Button
                        variant="outline"
                        size="sm"
                        class="flex-1"
                        data-action="click->calendar#selectDate"
                        data-calendar-date-param="{{ '2026-03-15'|date_modify('+' ~ preset.days ~ ' days')|date('Y-m-d') }}"
                    >{{ preset.label }}</twig:Button>
                {% endfor %}
            </div>
        </twig:Calendar>
    </twig:Card:Content>
</twig:Card>
```

### Date and Time Picker

```twig {"preview":true,"height":"480px"}
<twig:Card size="sm" class="mx-auto w-fit">
    <twig:Card:Content>
        <twig:Calendar
            mode="single"
            today="2026-03-15"
            selected="2026-03-12"
            class="p-0"
        />
    </twig:Card:Content>
    <twig:Card:Footer class="border-t bg-card">
        <twig:Field:Group>
            <twig:Field>
                <twig:Field:Label for="time-from">Start Time</twig:Field:Label>
                <twig:InputGroup>
                    <twig:InputGroup:Input id="time-from" type="time" step="1" value="10:30:00" class="appearance-none [&::-webkit-calendar-picker-indicator]:hidden [&::-webkit-calendar-picker-indicator]:appearance-none" />
                    <twig:InputGroup:Addon>
                        <twig:ux:icon name="lucide:clock" class="text-muted-foreground" />
                    </twig:InputGroup:Addon>
                </twig:InputGroup>
            </twig:Field>
            <twig:Field>
                <twig:Field:Label for="time-to">End Time</twig:Field:Label>
                <twig:InputGroup>
                    <twig:InputGroup:Input id="time-to" type="time" step="1" value="12:30:00" class="appearance-none [&::-webkit-calendar-picker-indicator]:hidden [&::-webkit-calendar-picker-indicator]:appearance-none" />
                    <twig:InputGroup:Addon>
                        <twig:ux:icon name="lucide:clock" class="text-muted-foreground" />
                    </twig:InputGroup:Addon>
                </twig:InputGroup>
            </twig:Field>
        </twig:Field:Group>
    </twig:Card:Footer>
</twig:Card>
```

### Booked Dates

Every entry of `modifiers` is rendered as a `data-<name>` attribute on the matching days, which
is enough to style them with an arbitrary utility.

```twig {"preview":true,"height":"320px"}
{% set bookedDates = (0..14)|map(offset => '2026-02-12'|date_modify('+' ~ offset ~ ' days')|date('Y-m-d')) %}
<twig:Card class="mx-auto w-fit p-0">
    <twig:Card:Content class="p-0">
        <twig:Calendar
            mode="single"
            today="2026-03-15"
            month="2026-02-01"
            selected="2026-02-03"
            :disabled="bookedDates"
            :modifiers="{booked: bookedDates}"
            class="[&_[data-booked=true][data-disabled=true]]:opacity-100 [&_[data-booked=true]>button]:line-through"
        />
    </twig:Card:Content>
</twig:Card>
```

### Custom Cell Size

Cells are sized with the `--cell-size` CSS variable, which can be overridden per breakpoint.

```twig {"preview":true,"height":"460px"}
<twig:Card class="mx-auto w-fit p-0">
    <twig:Card:Content class="p-0">
        <twig:Calendar
            mode="range"
            today="2026-03-15"
            month="2026-12-01"
            :selected="['2026-12-08', '2026-12-18']"
            captionLayout="dropdown"
            class="[--cell-size:--spacing(10)] md:[--cell-size:--spacing(12)]"
        />
    </twig:Card:Content>
</twig:Card>
```

### Week Numbers

Use `showWeekNumber` to prepend a column with the ISO week number.

```twig {"preview":true,"height":"320px"}
<twig:Card class="mx-auto w-fit p-0">
    <twig:Card:Content class="p-0">
        <twig:Calendar
            mode="single"
            today="2026-03-15"
            month="2026-02-01"
            selected="2026-02-03"
            showWeekNumber
        />
    </twig:Card:Content>
</twig:Card>
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element. Pair it with `locale`
so the month, weekday and day labels are formatted for that language, and with `weekStartsOn`
so the week starts on the expected day.

```twig {"preview":true,"height":"760px"}
<div class="flex flex-col items-center gap-12">
    {# Arabic #}
    <twig:Calendar
        dir="rtl"
        locale="ar"
        weekStartsOn="6"
        mode="single"
        today="2026-03-15"
        selected="2026-03-15"
        captionLayout="dropdown"
        class="rounded-lg border [--cell-size:--spacing(9)]"
    />

    {# Hebrew #}
    <twig:Calendar
        dir="rtl"
        locale="he"
        weekStartsOn="0"
        mode="single"
        today="2026-03-15"
        selected="2026-03-15"
        captionLayout="dropdown"
        class="rounded-lg border [--cell-size:--spacing(9)]"
    />
</div>
```

## API Reference

::: api-reference
