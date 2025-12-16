# Examples

## Icon

Use `InputGroup:Icon` to add a leading or trailing icon to an input. Padding is applied automatically.

```twig {"preview":true}
<div class="max-w-md">
  <twig:InputGroup>
    <twig:InputGroup:Icon>
      {{ ux_icon('lucide:search') }}
    </twig:InputGroup:Icon>
    <twig:InputGroup:Input
      id="search"
      name="query"
      type="search"
      placeholder="Search..."
    />
  </twig:InputGroup>
</div>
```

## Trailing icon

Position the icon at the end using `position="end"`.

```twig {"preview":true}
<div class="max-w-md">
  <twig:InputGroup>
    <twig:InputGroup:Input
      id="email"
      name="email"
      type="email"
      placeholder="Enter your email"
    />
    <twig:InputGroup:Icon position="end">
      {{ ux_icon('lucide:mail') }}
    </twig:InputGroup:Icon>
  </twig:InputGroup>
</div>
```

## Both icons

Combine leading and trailing icons.

```twig {"preview":true}
<div class="max-w-md">
  <twig:InputGroup>
    <twig:InputGroup:Icon>
      {{ ux_icon('lucide:user') }}
    </twig:InputGroup:Icon>
    <twig:InputGroup:Input
      id="username"
      name="username"
      placeholder="Username"
    />
    <twig:InputGroup:Icon position="end">
      {{ ux_icon('lucide:check') }}
    </twig:InputGroup:Icon>
  </twig:InputGroup>
</div>
```

## Two trailing icons

Group multiple icons together in a single `InputGroup:Icon`. Add extra padding to accommodate the additional icons.

```twig {"preview":true}
<div class="max-w-md">
  <twig:InputGroup>
    <twig:InputGroup:Input
      id="password"
      name="password"
      type="password"
      placeholder="Enter password"
      class="pr-16"
    />
    <twig:InputGroup:Icon position="end" class="gap-1">
      {{ ux_icon('lucide:eye', { class: 'cursor-pointer pointer-events-auto hover:text-foreground' }) }}
      {{ ux_icon('lucide:copy', { class: 'cursor-pointer pointer-events-auto hover:text-foreground' }) }}
    </twig:InputGroup:Icon>
  </twig:InputGroup>
</div>
```

## Search with icon and shortcut

Combine icons with other elements like keyboard hints.

```twig {"preview":true}
<div class="max-w-md">
  <twig:InputGroup>
    <twig:InputGroup:Icon>
      {{ ux_icon('lucide:search') }}
    </twig:InputGroup:Icon>
    <twig:InputGroup:Input
      id="search"
      name="query"
      type="search"
      placeholder="Search documentation..."
      class="pr-14"
    />
    <twig:Kbd class="hidden lg:inline-flex pointer-events-none absolute right-[9px] top-1/2 h-5 -translate-y-1/2">
      ⌘K
    </twig:Kbd>
  </twig:InputGroup>
</div>
```

## Textarea

Use `InputGroup:Textarea` for multi-line input. Use `align="start"` on icons to position them at the top.

```twig {"preview":true}
<div class="max-w-md">
  <twig:InputGroup>
    <twig:InputGroup:Icon align="start">
      {{ ux_icon('lucide:message-square') }}
    </twig:InputGroup:Icon>
    <twig:InputGroup:Textarea
      id="message"
      name="message"
      placeholder="Type your message..."
      rows="4"
    />
  </twig:InputGroup>
</div>
```

## Textarea with trailing icon

Position an icon at the end of a textarea.

```twig {"preview":true}
<div class="max-w-md">
  <twig:InputGroup>
    <twig:InputGroup:Textarea
      id="notes"
      name="notes"
      placeholder="Add notes..."
      rows="3"
    />
    <twig:InputGroup:Icon position="end" align="start">
      {{ ux_icon('lucide:pencil') }}
    </twig:InputGroup:Icon>
  </twig:InputGroup>
</div>
```

## Textarea with character count

Use `InputGroup:Addon` with `align="block-end"` to add content below the textarea. Add `class="border-t"` for a separator line.

```twig {"preview":true, "height":"300px"}
<div class="max-w-md">
  <twig:InputGroup>
    <twig:InputGroup:Textarea
      id="message"
      name="message"
      placeholder="Enter your message"
      rows="4"
    />
    <twig:InputGroup:Addon align="block-end" class="border-t">
      <twig:InputGroup:Text class="text-xs">
        120 characters left
      </twig:InputGroup:Text>
    </twig:InputGroup:Addon>
  </twig:InputGroup>
</div>
```

## Addon with icon

Use `InputGroup:Addon` for flexible positioning of icons and other elements.

```twig {"preview":true}
<div class="max-w-md">
  <twig:InputGroup>
    <twig:InputGroup:Addon>
      {{ ux_icon('lucide:dollar-sign') }}
    </twig:InputGroup:Addon>
    <twig:InputGroup:Input
      id="price"
      name="price"
      type="number"
      placeholder="0.00"
    />
    <twig:InputGroup:Addon align="inline-end">
      <twig:InputGroup:Text>USD</twig:InputGroup:Text>
    </twig:InputGroup:Addon>
  </twig:InputGroup>
</div>
```

## Block start addon

Position content above the input using `align="block-start"`. Use `border-b` for a separator below and `border-t` for a separator above.

```twig {"preview":true, "height":"300px"}
<div class="max-w-md">
  <twig:InputGroup>
    <twig:InputGroup:Addon align="block-start" class="border-b">
      <twig:InputGroup:Text class="text-xs font-semibold">Bio</twig:InputGroup:Text>
    </twig:InputGroup:Addon>
    <twig:InputGroup:Textarea
      id="bio"
      name="bio"
      placeholder="Tell us about yourself..."
      rows="3"
    />
    <twig:InputGroup:Addon align="block-end" class="justify-end border-t">
      <twig:InputGroup:Text class="text-xs">Max 500 characters</twig:InputGroup:Text>
    </twig:InputGroup:Addon>
  </twig:InputGroup>
</div>
```

## Spinner

Show loading indicators while processing input.

```twig {"preview":true, "height":"300px"}
<div class="max-w-md flex flex-col gap-4">
  <twig:InputGroup data-disabled="true">
    <twig:InputGroup:Input placeholder="Searching..." disabled />
    <twig:InputGroup:Addon align="inline-end">
      <twig:Spinner />
    </twig:InputGroup:Addon>
  </twig:InputGroup>

  <twig:InputGroup data-disabled="true">
    <twig:InputGroup:Input placeholder="Processing..." disabled />
    <twig:InputGroup:Addon>
      <twig:Spinner />
    </twig:InputGroup:Addon>
  </twig:InputGroup>

  <twig:InputGroup data-disabled="true">
    <twig:InputGroup:Input placeholder="Saving changes..." disabled />
    <twig:InputGroup:Addon align="inline-end">
      <twig:InputGroup:Text>Saving...</twig:InputGroup:Text>
      <twig:Spinner />
    </twig:InputGroup:Addon>
  </twig:InputGroup>

  <twig:InputGroup data-disabled="true">
    <twig:InputGroup:Input placeholder="Refreshing data..." disabled />
    <twig:InputGroup:Addon>
      {{ ux_icon('lucide:loader', { class: 'animate-spin' }) }}
    </twig:InputGroup:Addon>
    <twig:InputGroup:Addon align="inline-end">
      <twig:InputGroup:Text class="text-muted-foreground">Please wait...</twig:InputGroup:Text>
    </twig:InputGroup:Addon>
  </twig:InputGroup>
</div>
```
