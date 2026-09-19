# Date Picker

A date picker built by composing a `Popover` and a `Calendar`.

```twig {"preview":true,"height":"420px"}
<div class="flex items-start justify-center pt-6" style="min-height: 400px">
    <twig:DatePicker>
        <twig:DatePicker:Trigger>
            <twig:Button
                variant="outline"
                class="w-[212px] justify-between text-start font-normal data-[empty=true]:text-muted-foreground"
                {{ ...date_picker_trigger_attrs }}
            >
                <twig:DatePicker:Value placeholder="Pick a date" />
                <twig:ux:icon name="lucide:chevron-down" data-icon="inline-end" />
            </twig:Button>
        </twig:DatePicker:Trigger>
        <twig:DatePicker:Content>
            <twig:Calendar mode="single" today="2026-03-15" />
        </twig:DatePicker:Content>
    </twig:DatePicker>
</div>
```

## Installation

::: installation

## Usage

```twig
<twig:DatePicker
    selected="2026-03-15"
    dateStyle="full | long | medium | short"
    :closeOnSelect="true"
>
    <twig:DatePicker:Trigger>
        <twig:Button variant="outline" {{ ...date_picker_trigger_attrs }}>
            <twig:DatePicker:Value placeholder="Pick a date" />
        </twig:Button>
    </twig:DatePicker:Trigger>
    <twig:DatePicker:Content side="top | right | bottom | left" align="start | center | end">
        <twig:Calendar mode="single" name="date" selected="2026-03-15" />
    </twig:DatePicker:Content>
</twig:DatePicker>
```

There is no date picker component upstream: it is a composition, and so is this recipe. `DatePicker` hosts the `popover` and `date-picker` controllers on a single element, `DatePicker:Trigger` and `DatePicker:Input` expose an attributes bag to spread onto your own element, and the selection itself belongs to the nested `Calendar`.

Dates are exchanged as `Y-m-d` strings, as in the `Calendar`. Set `selected` on both components: the `Calendar` owns the grid, while `DatePicker` needs it to render the formatted selection on the trigger server-side. Set `name` on the `Calendar` to mirror the selection into hidden inputs and submit it with a form.

Picking a day formats it with `dateStyle` and `locale`, writes it into every `DatePicker:Value` and `DatePicker:Input`, and closes the popover once the selection is complete — in `range` mode, only after both ends are set. Pass `:closeOnSelect="false"` to keep it open.

## Examples

### Basic

A date picker inside a `Field`, so it is labelled like any other form control.

```twig {"preview":true,"height":"420px"}
<div class="flex items-start justify-center pt-6" style="min-height: 400px">
    <twig:Field class="w-44">
        <twig:Field:Label for="date-picker-basic">Date</twig:Field:Label>
        <twig:DatePicker>
            <twig:DatePicker:Trigger>
                <twig:Button
                    id="date-picker-basic"
                    variant="outline"
                    class="w-full justify-start font-normal data-[empty=true]:text-muted-foreground"
                    {{ ...date_picker_trigger_attrs }}
                >
                    <twig:DatePicker:Value placeholder="Pick a date" />
                </twig:Button>
            </twig:DatePicker:Trigger>
            <twig:DatePicker:Content>
                <twig:Calendar mode="single" today="2026-03-15" />
            </twig:DatePicker:Content>
        </twig:DatePicker>
    </twig:Field>
</div>
```

### Range Picker

Set `mode="range"` on the `Calendar` and pass both ends to `selected`. The two dates are joined by `separator`, and the popover only closes once the end date is picked.

```twig {"preview":true,"height":"460px"}
<div class="flex items-start justify-center pt-6" style="min-height: 440px">
    <twig:Field class="w-60">
        <twig:Field:Label for="date-picker-range">Date Picker Range</twig:Field:Label>
        <twig:DatePicker :selected="['2026-01-20', '2026-02-09']" dateStyle="medium">
            <twig:DatePicker:Trigger>
                <twig:Button
                    id="date-picker-range"
                    variant="outline"
                    class="w-full justify-start px-2.5 font-normal data-[empty=true]:text-muted-foreground"
                    {{ ...date_picker_trigger_attrs }}
                >
                    <twig:ux:icon name="lucide:calendar" data-icon="inline-start" />
                    <twig:DatePicker:Value placeholder="Pick a date" />
                </twig:Button>
            </twig:DatePicker:Trigger>
            <twig:DatePicker:Content>
                <twig:Calendar
                    mode="range"
                    :selected="['2026-01-20', '2026-02-09']"
                    :numberOfMonths="2"
                    today="2026-03-15"
                />
            </twig:DatePicker:Content>
        </twig:DatePicker>
    </twig:Field>
</div>
```

### Date of Birth

Pair `captionLayout="dropdown"` with `startMonth` and `endMonth` so a distant year is a couple of clicks away rather than a hundred.

```twig {"preview":true,"height":"420px"}
<div class="flex items-start justify-center pt-6" style="min-height: 400px">
    <twig:Field class="w-44">
        <twig:Field:Label for="date-picker-dob">Date of birth</twig:Field:Label>
        <twig:DatePicker dateStyle="medium">
            <twig:DatePicker:Trigger>
                <twig:Button
                    id="date-picker-dob"
                    variant="outline"
                    class="w-full justify-start font-normal data-[empty=true]:text-muted-foreground"
                    {{ ...date_picker_trigger_attrs }}
                >
                    <twig:DatePicker:Value placeholder="Select date" />
                </twig:Button>
            </twig:DatePicker:Trigger>
            <twig:DatePicker:Content>
                <twig:Calendar
                    mode="single"
                    captionLayout="dropdown"
                    startMonth="1926-01-01"
                    endMonth="2026-12-01"
                    today="2026-03-15"
                />
            </twig:DatePicker:Content>
        </twig:DatePicker>
    </twig:Field>
</div>
```

### Input

Spread `date_picker_input_attrs` onto a text input to keep it in sync with the calendar in both directions: it's rendered server-side already holding the current selection formatted, picking a day writes the formatted date back into it, and typing a date moves the calendar to match. <kbd>↓</kbd> opens the popover from the input.

```twig {"preview":true,"height":"440px"}
<div class="flex items-start justify-center pt-6" style="min-height: 420px">
    <twig:Field class="w-56">
        <twig:Field:Label for="date-picker-input">Subscription Date</twig:Field:Label>
        <twig:DatePicker selected="2026-06-01">
            <twig:InputGroup>
                <twig:DatePicker:Input>
                    <twig:InputGroup:Input
                        id="date-picker-input"
                        placeholder="June 1, 2026"
                        {{ ...date_picker_input_attrs }}
                    />
                </twig:DatePicker:Input>
                <twig:InputGroup:Addon align="inline-end">
                    <twig:DatePicker:Trigger>
                        <twig:InputGroup:Button
                            variant="ghost"
                            size="icon-xs"
                            {{ ...date_picker_trigger_attrs }}
                        >
                            <twig:ux:icon name="lucide:calendar" />
                            <span class="sr-only">Select date</span>
                        </twig:InputGroup:Button>
                    </twig:DatePicker:Trigger>
                </twig:InputGroup:Addon>
            </twig:InputGroup>
            <twig:DatePicker:Content align="end">
                <twig:Calendar mode="single" selected="2026-06-01" today="2026-03-15" />
            </twig:DatePicker:Content>
        </twig:DatePicker>
    </twig:Field>
</div>
```

### Time Picker

The date picker only handles the date. Pair it with a native `<twig:Input type="time" />` to collect a time alongside it.

```twig {"preview":true,"height":"440px"}
<div class="flex items-start justify-center pt-6" style="min-height: 420px">
    <twig:Field orientation="horizontal" class="w-auto items-start gap-4">
        <twig:Field class="w-40">
            <twig:Field:Label for="date-picker-time">Date</twig:Field:Label>
            <twig:DatePicker dateStyle="medium">
                <twig:DatePicker:Trigger>
                    <twig:Button
                        id="date-picker-time"
                        variant="outline"
                        class="w-full justify-between font-normal data-[empty=true]:text-muted-foreground"
                        {{ ...date_picker_trigger_attrs }}
                    >
                        <twig:DatePicker:Value placeholder="Select date" />
                        <twig:ux:icon name="lucide:chevron-down" data-icon="inline-end" />
                    </twig:Button>
                </twig:DatePicker:Trigger>
                <twig:DatePicker:Content>
                    <twig:Calendar mode="single" captionLayout="dropdown" today="2026-03-15" />
                </twig:DatePicker:Content>
            </twig:DatePicker>
        </twig:Field>
        <twig:Field class="w-36">
            <twig:Field:Label for="time-picker">Time</twig:Field:Label>
            <twig:Input
                type="time"
                id="time-picker"
                step="1"
                value="10:30:00"
                class="appearance-none bg-background [&::-webkit-calendar-picker-indicator]:hidden [&::-webkit-calendar-picker-indicator]:appearance-none"
            />
        </twig:Field>
    </twig:Field>
</div>
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element. Pass the same `locale` to the `DatePicker` and to the `Calendar`, so the trigger label and the grid are formatted for the same language, and pin the numbering system with the `-u-nu-` extension as the `Calendar` documents.

```twig {"preview":true,"height":"520px"}
<div class="flex flex-col items-center gap-6 pt-6" style="min-height: 500px">
    {# Arabic #}
    <twig:DatePicker dir="rtl" locale="ar-u-nu-latn" selected="2026-03-15">
        <twig:DatePicker:Trigger>
            <twig:Button
                variant="outline"
                class="w-[212px] justify-between text-start font-normal data-[empty=true]:text-muted-foreground"
                {{ ...date_picker_trigger_attrs }}
            >
                <twig:DatePicker:Value placeholder="اختر تاريخًا" />
                <twig:ux:icon name="lucide:chevron-down" data-icon="inline-end" />
            </twig:Button>
        </twig:DatePicker:Trigger>
        <twig:DatePicker:Content>
            <twig:Calendar
                locale="ar-u-nu-latn"
                weekStartsOn="6"
                mode="single"
                selected="2026-03-15"
                today="2026-03-15"
            />
        </twig:DatePicker:Content>
    </twig:DatePicker>

    {# Hebrew #}
    <twig:DatePicker dir="rtl" locale="he-u-nu-latn" selected="2026-03-15">
        <twig:DatePicker:Trigger>
            <twig:Button
                variant="outline"
                class="w-[212px] justify-between text-start font-normal data-[empty=true]:text-muted-foreground"
                {{ ...date_picker_trigger_attrs }}
            >
                <twig:DatePicker:Value placeholder="בחר תאריך" />
                <twig:ux:icon name="lucide:chevron-down" data-icon="inline-end" />
            </twig:Button>
        </twig:DatePicker:Trigger>
        <twig:DatePicker:Content>
            <twig:Calendar
                locale="he-u-nu-latn"
                weekStartsOn="0"
                mode="single"
                selected="2026-03-15"
                today="2026-03-15"
            />
        </twig:DatePicker:Content>
    </twig:DatePicker>
</div>
```

## Natural Language Input

`DatePicker:Input` reads what is typed with `Date.parse()`, so it accepts `Y-m-d` and the other formats the browser recognises, but not phrases such as `tomorrow` or `in 2 days`. Parsing those is a library's job, and [chrono-node](https://github.com/wanasit/chrono) does it, in several languages.

Install it, either with `importmap:require` for AssetMapper, or `npm` for Webpack Encore:

```shell
# With AssetMapper
php bin/console importmap:require chrono-node

# With npm
npm install chrono-node
```

Then rewrite `#parse()` in `date_picker_controller.js`, the single place where typed text becomes a `Y-m-d` string:

```js
import { Controller } from '@hotwired/stimulus';
import * as chrono from 'chrono-node';

export default class extends Controller {
    // ...

    #parse(text) {
        const value = text.trim();
        if ('' === value) {
            return null;
        }

        // `forwardDate` resolves an ambiguous phrase such as `friday` to the next one.
        const parsed = chrono.parseDate(value, undefined, { forwardDate: true });

        return parsed ? this.#toDateString(parsed) : null;
    }
}
```

`chrono.parseDate()` returns a local `Date`, or `null` when it finds no date in the text, and `#toDateString()` already turns that `Date` into the `Y-m-d` string the `Calendar` expects. Reach for a localized parser, `chrono.fr`, `chrono.de`, `chrono.es`, `chrono.ja`, to match the language of your application, and `chrono.en.GB` to read `25/12/2026` as day-first.

## Accessibility

- `DatePicker:Trigger` exposes `aria-haspopup="dialog"` and an `aria-expanded` reflecting whether the calendar is open, plus a `data-empty` flag while nothing is selected.
- `DatePicker:Content` wraps a `Popover:Content`, which renders `role="dialog"` with an `aria-hidden` mirroring the open state.
- Opening the picker moves focus into the popover: to the element carrying `[autofocus]` if there is one, otherwise the first focusable control, which for a calendar is its focusable day.
- `Escape` closes the picker and returns focus to the trigger. A click outside closes it too, but focus simply leaving it does not, so keyboard users should close with `Escape`.
- `DatePicker:Value` renders the `placeholder` text while nothing is selected, so a trigger built from it always has a visible and accessible name.
- `DatePicker:Input` is a real text input, so give it a `Field:Label`. Typing a date moves the calendar to that month, and `ArrowDown` opens the picker.
- Inside the popover the nested `Calendar` keeps its own keyboard handling: one day in the tab order, arrow keys, `Home` and `End`, `PageUp` and `PageDown`.

## API Reference

::: api-reference
