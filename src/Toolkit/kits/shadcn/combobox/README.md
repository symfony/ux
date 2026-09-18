# Combobox

Autocomplete input and command palette with a list of suggestions.

```twig {"preview":true}
{% set packages = [
    {value: 'turbo', label: 'UX Turbo'},
    {value: 'twig-component', label: 'UX Twig Component'},
    {value: 'live-component', label: 'UX Live Component'},
    {value: 'autocomplete', label: 'UX Autocomplete'},
    {value: 'icons', label: 'UX Icons'},
] %}
<div style="min-height: 220px">
    <twig:Combobox
        id="package"
        placeholder="Select package..."
        searchPlaceholder="Search packages..."
        :choices="packages"
    />
</div>
```

## Installation

::: installation

## Usage

```twig
{% set packages = [
    {value: 'turbo', label: 'UX Turbo'},
    {value: 'twig-component', label: 'UX Twig Component'},
    {value: 'live-component', label: 'UX Live Component'},
    {value: 'autocomplete', label: 'UX Autocomplete'},
    {value: 'icons', label: 'UX Icons'},
] %}
<twig:Combobox
    id="package-usage"
    placeholder="Select package..."
    searchPlaceholder="Search packages..."
    :choices="packages"
/>
```

## Examples

### With Default Value

```twig {"preview":true}
{% set packages = [
    {value: 'turbo', label: 'UX Turbo'},
    {value: 'twig-component', label: 'UX Twig Component'},
    {value: 'live-component', label: 'UX Live Component'},
    {value: 'autocomplete', label: 'UX Autocomplete'},
    {value: 'icons', label: 'UX Icons'},
] %}
<div style="min-height: 220px">
    <twig:Combobox
        id="package-default"
        value="live-component"
        placeholder="Select package..."
        searchPlaceholder="Search packages..."
        :choices="packages"
    />
</div>
```

### With Form

```twig {"preview":true}
{% set packages = [
    {value: 'turbo', label: 'UX Turbo'},
    {value: 'twig-component', label: 'UX Twig Component'},
    {value: 'live-component', label: 'UX Live Component'},
    {value: 'autocomplete', label: 'UX Autocomplete'},
    {value: 'icons', label: 'UX Icons'},
] %}
<div style="min-height: 220px">
    <form method="post" action="#" class="flex max-w-xs flex-col gap-4">
        <twig:Combobox
            id="package-form"
            name="package"
            placeholder="Select package..."
            searchPlaceholder="Search packages..."
            :choices="packages"
            :required="true"
        />
        <button
            type="submit"
            class="inline-flex h-9 items-center justify-center rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground shadow-xs hover:bg-primary/90"
        >Submit</button>
    </form>
</div>
```

### Disabled

```twig {"preview":true}
{% set packages = [
    {value: 'turbo', label: 'UX Turbo'},
    {value: 'twig-component', label: 'UX Twig Component'},
    {value: 'live-component', label: 'UX Live Component'},
] %}
<twig:Combobox
    id="package-disabled"
    placeholder="Select package..."
    searchPlaceholder="Search packages..."
    :choices="packages"
    :disabled="true"
/>
```

### Clearable

```twig {"preview":true}
{% set packages = [
    {value: 'turbo', label: 'UX Turbo'},
    {value: 'twig-component', label: 'UX Twig Component'},
    {value: 'live-component', label: 'UX Live Component'},
    {value: 'autocomplete', label: 'UX Autocomplete'},
    {value: 'icons', label: 'UX Icons'},
] %}
<div style="min-height: 220px">
    <twig:Combobox
        id="package-clearable"
        value="live-component"
        placeholder="Select package..."
        searchPlaceholder="Search packages..."
        clearable
        :choices="packages"
    />
</div>
```

### With Groups

```twig {"preview":true}
{% set packages = [
    {label: 'Stimulus', choices: [
        {value: 'twig-component', label: 'UX Twig Component'},
        {value: 'live-component', label: 'UX Live Component'},
        {value: 'autocomplete', label: 'UX Autocomplete'},
        {value: 'turbo', label: 'UX Turbo'},
    ]},
    {label: 'Frontend Frameworks', choices: [
        {value: 'react', label: 'UX React'},
        {value: 'vue', label: 'UX Vue'},
        {value: 'svelte', label: 'UX Svelte'},
    ]},
    {label: 'UI & Visualization', choices: [
        {value: 'icons', label: 'UX Icons'},
        {value: 'chartjs', label: 'UX Chart.js'},
        {value: 'map', label: 'UX Map'},
    ]},
] %}
<div style="min-height: 504px">
    <twig:Combobox
        id="package-grouped"
        placeholder="Select package..."
        searchPlaceholder="Search packages..."
        :choices="packages"
    />
</div>
```

### Empty State

```twig {"preview":true}
{% set frameworks = [
    {value: 'next', label: 'Next.js'},
    {value: 'sveltekit', label: 'SvelteKit'},
] %}
<div style="min-height: 220px">
    <twig:Combobox
        id="framework-empty"
        placeholder="Select framework..."
        searchPlaceholder="Search framework..."
        emptyMessage="No frameworks found. Try a different search."
        :choices="frameworks"
    />
</div>
```

### Long List

```twig {"preview":true}
{% set languages = [
    {value: 'php', label: 'PHP'},
    {value: 'javascript', label: 'JavaScript'},
    {value: 'typescript', label: 'TypeScript'},
    {value: 'python', label: 'Python'},
    {value: 'ruby', label: 'Ruby'},
    {value: 'go', label: 'Go'},
    {value: 'rust', label: 'Rust'},
    {value: 'java', label: 'Java'},
    {value: 'kotlin', label: 'Kotlin'},
    {value: 'swift', label: 'Swift'},
    {value: 'csharp', label: 'C#'},
    {value: 'cpp', label: 'C++'},
    {value: 'c', label: 'C'},
    {value: 'scala', label: 'Scala'},
    {value: 'elixir', label: 'Elixir'},
    {value: 'erlang', label: 'Erlang'},
    {value: 'haskell', label: 'Haskell'},
    {value: 'ocaml', label: 'OCaml'},
    {value: 'fsharp', label: 'F#'},
    {value: 'clojure', label: 'Clojure'},
    {value: 'dart', label: 'Dart'},
    {value: 'r', label: 'R'},
    {value: 'matlab', label: 'MATLAB'},
    {value: 'perl', label: 'Perl'},
    {value: 'lua', label: 'Lua'},
    {value: 'julia', label: 'Julia'},
    {value: 'groovy', label: 'Groovy'},
    {value: 'shell', label: 'Shell'},
    {value: 'powershell', label: 'PowerShell'},
    {value: 'sql', label: 'SQL'},
    {value: 'html', label: 'HTML'},
    {value: 'css', label: 'CSS'},
    {value: 'sass', label: 'Sass'},
    {value: 'less', label: 'Less'},
    {value: 'graphql', label: 'GraphQL'},
    {value: 'yaml', label: 'YAML'},
    {value: 'toml', label: 'TOML'},
    {value: 'json', label: 'JSON'},
    {value: 'xml', label: 'XML'},
    {value: 'markdown', label: 'Markdown'},
    {value: 'latex', label: 'LaTeX'},
    {value: 'zig', label: 'Zig'},
    {value: 'nim', label: 'Nim'},
    {value: 'crystal', label: 'Crystal'},
    {value: 'reason', label: 'ReasonML'},
    {value: 'elm', label: 'Elm'},
    {value: 'purescript', label: 'PureScript'},
    {value: 'coffeescript', label: 'CoffeeScript'},
    {value: 'objective-c', label: 'Objective-C'},
    {value: 'vba', label: 'VBA'},
    {value: 'cobol', label: 'COBOL'},
] %}
<div style="min-height: 454px">
    <twig:Combobox
        id="language"
        placeholder="Select language..."
        searchPlaceholder="Search languages..."
        :choices="languages"
    />
</div>
```

## Accessibility

- The trigger renders `role="combobox"` with `aria-haspopup="listbox"`, `aria-expanded` and an `aria-controls` pointing at the listbox, so assistive tech announces the widget and its state.
- The list renders `role="listbox"`, each entry `role="option"` with `aria-selected`, and a grouped list wraps its entries in `role="group"` labelled by the group heading.
- The filter input renders `role="searchbox"` with `aria-autocomplete="list"`, so typing is announced as filtering rather than free text entry.
- `ArrowDown` and `ArrowUp` move through the options, `Home` and `End` jump to the first and last, `Enter` selects the highlighted option and `Escape` closes the list and returns focus to the trigger.
- Set `id` so the internal ARIA references are unique, and give the combobox a visible `Label` bound with `for`, since the trigger's own text is only the current selection.
- The clear button carries `aria-label="Clear selection"`. Translate it when your interface is not in English.

## API Reference

::: api-reference
