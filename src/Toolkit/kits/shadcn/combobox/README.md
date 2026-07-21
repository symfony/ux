# Combobox

Autocomplete input and command palette with a list of suggestions.

```twig {"preview":true,"height":"550px"}
{% set packages = [
    {value: 'turbo', label: 'UX Turbo'},
    {value: 'twig-component', label: 'UX Twig Component'},
    {value: 'live-component', label: 'UX Live Component'},
    {value: 'autocomplete', label: 'UX Autocomplete'},
    {value: 'icons', label: 'UX Icons'},
] %}
<twig:Combobox
    id="package"
    placeholder="Select package..."
    searchPlaceholder="Search packages..."
    :choices="packages"
/>
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

```twig {"preview":true,"height":"550px"}
{% set packages = [
    {value: 'turbo', label: 'UX Turbo'},
    {value: 'twig-component', label: 'UX Twig Component'},
    {value: 'live-component', label: 'UX Live Component'},
    {value: 'autocomplete', label: 'UX Autocomplete'},
    {value: 'icons', label: 'UX Icons'},
] %}
<twig:Combobox
    id="package-default"
    value="live-component"
    placeholder="Select package..."
    searchPlaceholder="Search packages..."
    :choices="packages"
/>
```

### With Form

```twig {"preview":true,"height":"550px"}
{% set packages = [
    {value: 'turbo', label: 'UX Turbo'},
    {value: 'twig-component', label: 'UX Twig Component'},
    {value: 'live-component', label: 'UX Live Component'},
    {value: 'autocomplete', label: 'UX Autocomplete'},
    {value: 'icons', label: 'UX Icons'},
] %}
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

```twig {"preview":true,"height":"550px"}
{% set packages = [
    {value: 'turbo', label: 'UX Turbo'},
    {value: 'twig-component', label: 'UX Twig Component'},
    {value: 'live-component', label: 'UX Live Component'},
    {value: 'autocomplete', label: 'UX Autocomplete'},
    {value: 'icons', label: 'UX Icons'},
] %}
<twig:Combobox
    id="package-clearable"
    value="live-component"
    placeholder="Select package..."
    searchPlaceholder="Search packages..."
    clearable
    :choices="packages"
/>
```

### With Groups

```twig {"preview":true,"height":"600px"}
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
<twig:Combobox
    id="package-grouped"
    placeholder="Select package..."
    searchPlaceholder="Search packages..."
    :choices="packages"
/>
```

### Empty State

```twig {"preview":true,"height":"550px"}
{% set frameworks = [
    {value: 'next', label: 'Next.js'},
    {value: 'sveltekit', label: 'SvelteKit'},
] %}
<twig:Combobox
    id="framework-empty"
    placeholder="Select framework..."
    searchPlaceholder="Search framework..."
    emptyMessage="No frameworks found. Try a different search."
    :choices="frameworks"
/>
```

### Long List

```twig {"preview":true,"height":"550px"}
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
<twig:Combobox
    id="language"
    placeholder="Select language..."
    searchPlaceholder="Search languages..."
    :choices="languages"
/>
```

## API Reference

::: api-reference
