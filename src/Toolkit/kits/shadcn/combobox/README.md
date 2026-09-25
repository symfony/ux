# Combobox

Autocomplete input with a list of suggestions.

```twig {"preview":true}
{% set frameworks = ['Next.js', 'SvelteKit', 'Nuxt.js', 'Remix', 'Astro'] %}
<div class="mx-auto w-full max-w-xs" style="min-height: 280px">
    <twig:Combobox id="framework-demo">
        <twig:Combobox:Input placeholder="Select a framework" />
        <twig:Combobox:Content>
            <twig:Combobox:Empty>No items found.</twig:Combobox:Empty>
            <twig:Combobox:List>
                {% for framework in frameworks %}
                    <twig:Combobox:Item value="{{ framework }}">{{ framework }}</twig:Combobox:Item>
                {% endfor %}
            </twig:Combobox:List>
        </twig:Combobox:Content>
    </twig:Combobox>
</div>
```

## Installation

::: installation

## Usage

```twig
<twig:Combobox id="framework">
    <twig:Combobox:Input placeholder="Select a framework" />
    <twig:Combobox:Content>
        <twig:Combobox:Empty>No items found.</twig:Combobox:Empty>
        <twig:Combobox:List>
            <twig:Combobox:Item value="next">Next.js</twig:Combobox:Item>
            <twig:Combobox:Item value="astro">Astro</twig:Combobox:Item>
        </twig:Combobox:List>
    </twig:Combobox:Content>
</twig:Combobox>
```

## Composition

### Simple

A single-line input and a flat list (see [Basic](#basic)).

```text
Combobox
├── Combobox:Input
└── Combobox:Content
    ├── Combobox:Empty
    └── Combobox:List
        ├── Combobox:Item
        └── Combobox:Item
```

### With chips

Multiple selection with `multiple`, chips, and a chips input (see [Multiple](#multiple)).

```text
Combobox
├── Combobox:Chips
│   └── Combobox:ChipsInput
└── Combobox:Content
    ├── Combobox:Empty
    └── Combobox:List
        ├── Combobox:Item
        └── Combobox:Item
```

### With groups and collection

Nested items per group using `Combobox:Collection` inside each `Combobox:Group`, with a separator
between groups (see [Groups](#groups)).

```text
Combobox
├── Combobox:Input
└── Combobox:Content
    ├── Combobox:Empty
    └── Combobox:List
        ├── Combobox:Group
        │   ├── Combobox:Label
        │   └── Combobox:Collection
        │       ├── Combobox:Item
        │       └── Combobox:Item
        ├── Combobox:Separator
        └── Combobox:Group
            ├── Combobox:Label
            └── Combobox:Collection
                ├── Combobox:Item
                └── Combobox:Item
```

## Examples

### Basic

A simple combobox with a list of frameworks.

```twig {"preview":true}
{% set frameworks = ['Next.js', 'SvelteKit', 'Nuxt.js', 'Remix', 'Astro'] %}
<div class="mx-auto w-full max-w-xs" style="min-height: 280px">
    <twig:Combobox id="framework-basic">
        <twig:Combobox:Input placeholder="Select a framework" />
        <twig:Combobox:Content>
            <twig:Combobox:Empty>No items found.</twig:Combobox:Empty>
            <twig:Combobox:List>
                {% for framework in frameworks %}
                    <twig:Combobox:Item value="{{ framework }}">{{ framework }}</twig:Combobox:Item>
                {% endfor %}
            </twig:Combobox:List>
        </twig:Combobox:Content>
    </twig:Combobox>
</div>
```

### Multiple

A combobox with multiple selection using `multiple` and `Combobox:Chips`.

```twig {"preview":true}
{% set frameworks = ['Next.js', 'SvelteKit', 'Nuxt.js', 'Remix', 'Astro'] %}
<div class="mx-auto w-full max-w-xs" style="min-height: 280px">
    <twig:Combobox id="framework-multiple" multiple autoHighlight :value="['Next.js']">
        <twig:Combobox:Chips>
            <twig:Combobox:ChipsInput placeholder="Add framework" />
        </twig:Combobox:Chips>
        <twig:Combobox:Content>
            <twig:Combobox:Empty>No items found.</twig:Combobox:Empty>
            <twig:Combobox:List>
                {% for framework in frameworks %}
                    <twig:Combobox:Item value="{{ framework }}">{{ framework }}</twig:Combobox:Item>
                {% endfor %}
            </twig:Combobox:List>
        </twig:Combobox:Content>
    </twig:Combobox>
</div>
```

### Clear Button

Use the `showClear` prop to show a clear button.

```twig {"preview":true}
{% set frameworks = ['Next.js', 'SvelteKit', 'Nuxt.js', 'Remix', 'Astro'] %}
<div class="mx-auto w-full max-w-xs" style="min-height: 280px">
    <twig:Combobox id="framework-clear" value="Next.js">
        <twig:Combobox:Input placeholder="Select a framework" showClear />
        <twig:Combobox:Content>
            <twig:Combobox:Empty>No items found.</twig:Combobox:Empty>
            <twig:Combobox:List>
                {% for framework in frameworks %}
                    <twig:Combobox:Item value="{{ framework }}">{{ framework }}</twig:Combobox:Item>
                {% endfor %}
            </twig:Combobox:List>
        </twig:Combobox:Content>
    </twig:Combobox>
</div>
```

### Groups

Use `Combobox:Group` and `Combobox:Separator` to group items.

```twig {"preview":true}
{% set timezones = [
    {label: 'Americas', items: ['(GMT-5) New York', '(GMT-8) Los Angeles', '(GMT-6) Chicago', '(GMT-5) Toronto', '(GMT-8) Vancouver', '(GMT-3) São Paulo']},
    {label: 'Europe', items: ['(GMT+0) London', '(GMT+1) Paris', '(GMT+1) Berlin', '(GMT+1) Rome', '(GMT+1) Madrid', '(GMT+1) Amsterdam']},
    {label: 'Asia/Pacific', items: ['(GMT+9) Tokyo', '(GMT+8) Shanghai', '(GMT+8) Singapore', '(GMT+4) Dubai', '(GMT+11) Sydney', '(GMT+9) Seoul']},
] %}
<div class="mx-auto w-full max-w-xs" style="min-height: 380px">
    <twig:Combobox id="timezone-groups">
        <twig:Combobox:Input placeholder="Select a timezone" />
        <twig:Combobox:Content>
            <twig:Combobox:Empty>No timezones found.</twig:Combobox:Empty>
            <twig:Combobox:List>
                {% for timezone in timezones %}
                    <twig:Combobox:Group id="{{ loop.index }}">
                        <twig:Combobox:Label>{{ timezone.label }}</twig:Combobox:Label>
                        <twig:Combobox:Collection>
                            {% for item in timezone.items %}
                                <twig:Combobox:Item value="{{ item }}">{{ item }}</twig:Combobox:Item>
                            {% endfor %}
                        </twig:Combobox:Collection>
                        {% if not loop.last %}<twig:Combobox:Separator />{% endif %}
                    </twig:Combobox:Group>
                {% endfor %}
            </twig:Combobox:List>
        </twig:Combobox:Content>
    </twig:Combobox>
</div>
```

### Custom Items

You can render a custom component inside `Combobox:Item`. Set the `label` prop so the filter matches
the country name rather than the whole item text.

```twig {"preview":true}
{% set countries = [
    {value: 'argentina', label: 'Argentina', continent: 'South America', code: 'ar'},
    {value: 'australia', label: 'Australia', continent: 'Oceania', code: 'au'},
    {value: 'brazil', label: 'Brazil', continent: 'South America', code: 'br'},
    {value: 'canada', label: 'Canada', continent: 'North America', code: 'ca'},
    {value: 'china', label: 'China', continent: 'Asia', code: 'cn'},
    {value: 'egypt', label: 'Egypt', continent: 'Africa', code: 'eg'},
    {value: 'france', label: 'France', continent: 'Europe', code: 'fr'},
    {value: 'germany', label: 'Germany', continent: 'Europe', code: 'de'},
    {value: 'japan', label: 'Japan', continent: 'Asia', code: 'jp'},
    {value: 'kenya', label: 'Kenya', continent: 'Africa', code: 'ke'},
    {value: 'united-states', label: 'United States', continent: 'North America', code: 'us'},
] %}
<div class="mx-auto w-full max-w-xs" style="min-height: 380px">
    <twig:Combobox id="country-custom">
        <twig:Combobox:Input placeholder="Search countries..." />
        <twig:Combobox:Content>
            <twig:Combobox:Empty>No countries found.</twig:Combobox:Empty>
            <twig:Combobox:List>
                {% for country in countries %}
                    <twig:Combobox:Item value="{{ country.value }}" label="{{ country.label }}">
                        <twig:Item size="xs" class="p-0">
                            <twig:Item:Content>
                                <twig:Item:Title class="whitespace-nowrap">{{ country.label }}</twig:Item:Title>
                                <twig:Item:Description>{{ country.continent }} ({{ country.code }})</twig:Item:Description>
                            </twig:Item:Content>
                        </twig:Item>
                    </twig:Combobox:Item>
                {% endfor %}
            </twig:Combobox:List>
        </twig:Combobox:Content>
    </twig:Combobox>
</div>
```

### Invalid

Use the `aria-invalid` attribute to mark the combobox as invalid.

```twig {"preview":true}
{% set frameworks = ['Next.js', 'SvelteKit', 'Nuxt.js', 'Remix', 'Astro'] %}
<div class="mx-auto w-full max-w-xs" style="min-height: 280px">
    <twig:Combobox id="framework-invalid">
        <twig:Combobox:Input placeholder="Select a framework" aria-invalid="true" />
        <twig:Combobox:Content>
            <twig:Combobox:Empty>No items found.</twig:Combobox:Empty>
            <twig:Combobox:List>
                {% for framework in frameworks %}
                    <twig:Combobox:Item value="{{ framework }}">{{ framework }}</twig:Combobox:Item>
                {% endfor %}
            </twig:Combobox:List>
        </twig:Combobox:Content>
    </twig:Combobox>
</div>
```

### Disabled

Use the `disabled` prop to disable the combobox.

```twig {"preview":true}
{% set frameworks = ['Next.js', 'SvelteKit', 'Nuxt.js', 'Remix', 'Astro'] %}
<div class="mx-auto w-full max-w-xs" style="min-height: 120px">
    <twig:Combobox id="framework-disabled">
        <twig:Combobox:Input placeholder="Select a framework" disabled />
        <twig:Combobox:Content>
            <twig:Combobox:Empty>No items found.</twig:Combobox:Empty>
            <twig:Combobox:List>
                {% for framework in frameworks %}
                    <twig:Combobox:Item value="{{ framework }}">{{ framework }}</twig:Combobox:Item>
                {% endfor %}
            </twig:Combobox:List>
        </twig:Combobox:Content>
    </twig:Combobox>
</div>
```

### Auto Highlight

Use the `autoHighlight` prop to automatically highlight the first item while filtering.

```twig {"preview":true}
{% set frameworks = ['Next.js', 'SvelteKit', 'Nuxt.js', 'Remix', 'Astro'] %}
<div class="mx-auto w-full max-w-xs" style="min-height: 280px">
    <twig:Combobox id="framework-auto-highlight" autoHighlight>
        <twig:Combobox:Input placeholder="Select a framework" />
        <twig:Combobox:Content>
            <twig:Combobox:Empty>No items found.</twig:Combobox:Empty>
            <twig:Combobox:List>
                {% for framework in frameworks %}
                    <twig:Combobox:Item value="{{ framework }}">{{ framework }}</twig:Combobox:Item>
                {% endfor %}
            </twig:Combobox:List>
        </twig:Combobox:Content>
    </twig:Combobox>
</div>
```

### Popup

You can open the combobox from a button, or any other element, by spreading `combobox_trigger_attrs`
onto it. Move the `Combobox:Input` inside the `Combobox:Content`, and display the selection with a
`Combobox:Value`.

```twig {"preview":true}
{% set countries = [
    {value: 'argentina', label: 'Argentina'},
    {value: 'australia', label: 'Australia'},
    {value: 'brazil', label: 'Brazil'},
    {value: 'canada', label: 'Canada'},
    {value: 'france', label: 'France'},
    {value: 'germany', label: 'Germany'},
    {value: 'japan', label: 'Japan'},
    {value: 'united-states', label: 'United States'},
] %}
<div class="mx-auto w-64" style="min-height: 340px">
    <twig:Combobox id="country-popup">
        <twig:Combobox:Trigger>
            <twig:Button
                variant="outline"
                class="w-full justify-between font-normal"
                {{ ...combobox_trigger_attrs }}
            >
                <twig:Combobox:Value placeholder="Select country" />
                <twig:ux:icon name="lucide:chevron-down" data-icon="inline-end" />
            </twig:Button>
        </twig:Combobox:Trigger>
        <twig:Combobox:Content>
            <twig:Combobox:Input placeholder="Search" :showTrigger="false" />
            <twig:Combobox:Empty>No items found.</twig:Combobox:Empty>
            <twig:Combobox:List>
                {% for country in countries %}
                    <twig:Combobox:Item value="{{ country.value }}" label="{{ country.label }}">{{ country.label }}</twig:Combobox:Item>
                {% endfor %}
            </twig:Combobox:List>
        </twig:Combobox:Content>
    </twig:Combobox>
</div>
```

### Input Group

You can add an addon to the combobox by rendering an `InputGroup:Addon` inside the `Combobox:Input`.

```twig {"preview":true}
{% set timezones = [
    {label: 'Americas', items: ['(GMT-5) New York', '(GMT-8) Los Angeles', '(GMT-6) Chicago']},
    {label: 'Europe', items: ['(GMT+0) London', '(GMT+1) Paris', '(GMT+1) Berlin']},
    {label: 'Asia/Pacific', items: ['(GMT+9) Tokyo', '(GMT+8) Shanghai', '(GMT+11) Sydney']},
] %}
<div class="mx-auto w-full max-w-xs" style="min-height: 380px">
    <twig:Combobox id="timezone-input-group">
        <twig:Combobox:Input placeholder="Select a timezone">
            <twig:InputGroup:Addon>
                <twig:ux:icon name="lucide:globe" />
            </twig:InputGroup:Addon>
        </twig:Combobox:Input>
        <twig:Combobox:Content>
            <twig:Combobox:Empty>No timezones found.</twig:Combobox:Empty>
            <twig:Combobox:List>
                {% for timezone in timezones %}
                    <twig:Combobox:Group id="{{ loop.index }}">
                        <twig:Combobox:Label>{{ timezone.label }}</twig:Combobox:Label>
                        <twig:Combobox:Collection>
                            {% for item in timezone.items %}
                                <twig:Combobox:Item value="{{ item }}">{{ item }}</twig:Combobox:Item>
                            {% endfor %}
                        </twig:Combobox:Collection>
                    </twig:Combobox:Group>
                {% endfor %}
            </twig:Combobox:List>
        </twig:Combobox:Content>
    </twig:Combobox>
</div>
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

```twig {"preview":true}
{% set categories = {
    ar: {'التكنولوجيا': 'technology', 'التصميم': 'design', 'الأعمال': 'business', 'التسويق': 'marketing'},
    he: {'טכנולוגיה': 'technology', 'עיצוב': 'design', 'עסקים': 'business', 'שיווק': 'marketing'},
} %}
<div class="mx-auto flex w-full max-w-xs flex-col gap-6" style="min-height: 420px">
    {# Arabic #}
    <twig:Combobox id="categories-ar" dir="rtl" multiple autoHighlight :value="['التكنولوجيا']">
        <twig:Combobox:Chips>
            <twig:Combobox:ChipsInput placeholder="أضف فئات" />
        </twig:Combobox:Chips>
        <twig:Combobox:Content>
            <twig:Combobox:Empty>لم يتم العثور على فئات.</twig:Combobox:Empty>
            <twig:Combobox:List>
                {% for category in categories.ar|keys %}
                    <twig:Combobox:Item value="{{ category }}">{{ category }}</twig:Combobox:Item>
                {% endfor %}
            </twig:Combobox:List>
        </twig:Combobox:Content>
    </twig:Combobox>

    {# Hebrew #}
    <twig:Combobox id="categories-he" dir="rtl" multiple autoHighlight :value="['טכנולוגיה']">
        <twig:Combobox:Chips>
            <twig:Combobox:ChipsInput placeholder="הוסף קטגוריות" />
        </twig:Combobox:Chips>
        <twig:Combobox:Content>
            <twig:Combobox:Empty>לא נמצאו קטגוריות.</twig:Combobox:Empty>
            <twig:Combobox:List>
                {% for category in categories.he|keys %}
                    <twig:Combobox:Item value="{{ category }}">{{ category }}</twig:Combobox:Item>
                {% endfor %}
            </twig:Combobox:List>
        </twig:Combobox:Content>
    </twig:Combobox>
</div>
```

## Accessibility

- The text input renders `role="combobox"` with `aria-haspopup="listbox"`, `aria-expanded` and an
  `aria-controls` pointing at the list, so assistive tech announces the widget and its state.
- `Combobox:List` renders `role="listbox"` with `aria-multiselectable`, each `Combobox:Item` renders
  `role="option"` with `aria-selected`, and a `Combobox:Group` wraps its items in `role="group"`
  labelled by its `Combobox:Label`.
- The highlighted item is referenced by `aria-activedescendant` on the input, so the focus stays in
  the input while the reading order follows the highlight.
- `ArrowDown` and `ArrowUp` move through the items, `Home` and `End` jump to the first and last,
  `Enter` selects the highlighted item and `Escape` closes the list and returns focus to the input.
  In a multiple selection, `Backspace` on an empty input removes the last chip.
- Set `id` so the internal ARIA references are unique, and give the combobox a visible `Field:Label`
  bound to the input with `for`, since the input's own text is only the current selection.
- The clear button carries `aria-label="Clear selection"` and each chip's remove button
  `aria-label="Remove"`. Translate them when your interface is not in English.

## API Reference

::: api-reference
