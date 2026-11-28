# Tabs

Tabs can be used to show a list of links in a tabbed format.

```twig {"preview":true}
<twig:Tabs id="tabs-default" defaultValue="tab-2">
    <twig:Tabs:List aria-label="Tabs">
        <twig:Tabs:Trigger value="tab-1">Tab 1</twig:Tabs:Trigger>
        <twig:Tabs:Trigger value="tab-2">Tab 2</twig:Tabs:Trigger>
        <twig:Tabs:Trigger value="tab-3">Tab 3</twig:Tabs:Trigger>
    </twig:Tabs:List>
</twig:Tabs>
```

## Installation

::: installation

## Usage

```twig
<twig:Tabs
    id="settings"
    defaultValue="account"
    variant="default | box | border | lift"
    size="xs | sm | md | lg | xl"
    placement="top | bottom"
>
    <twig:Tabs:List aria-label="Settings">
        <twig:Tabs:Trigger value="account">Account</twig:Tabs:Trigger>
        <twig:Tabs:Trigger value="password">Password</twig:Tabs:Trigger>
    </twig:Tabs:List>
    <twig:Tabs:Content value="account">Make changes to your account here.</twig:Tabs:Content>
    <twig:Tabs:Content value="password">Change your password here.</twig:Tabs:Content>
</twig:Tabs>
```

The tabs follow the [WAI-ARIA tabs pattern](https://www.w3.org/WAI/ARIA/apg/patterns/tabs/): a click or the arrow keys select a tab, and `Home` and `End` select the first and the last one. Give each `Tabs` a unique `id`, and give the `Tabs:List` an `aria-label` that describes the tabs.

## Examples

### Border

```twig {"preview":true}
<twig:Tabs id="tabs-border" defaultValue="tab-2" variant="border">
    <twig:Tabs:List aria-label="Tabs">
        <twig:Tabs:Trigger value="tab-1">Tab 1</twig:Tabs:Trigger>
        <twig:Tabs:Trigger value="tab-2">Tab 2</twig:Tabs:Trigger>
        <twig:Tabs:Trigger value="tab-3">Tab 3</twig:Tabs:Trigger>
    </twig:Tabs:List>
</twig:Tabs>
```

### Lift

```twig {"preview":true}
<twig:Tabs id="tabs-lift" defaultValue="tab-2" variant="lift">
    <twig:Tabs:List aria-label="Tabs">
        <twig:Tabs:Trigger value="tab-1">Tab 1</twig:Tabs:Trigger>
        <twig:Tabs:Trigger value="tab-2">Tab 2</twig:Tabs:Trigger>
        <twig:Tabs:Trigger value="tab-3">Tab 3</twig:Tabs:Trigger>
    </twig:Tabs:List>
</twig:Tabs>
```

### Box

```twig {"preview":true}
<twig:Tabs id="tabs-box" defaultValue="tab-2" variant="box">
    <twig:Tabs:List aria-label="Tabs">
        <twig:Tabs:Trigger value="tab-1">Tab 1</twig:Tabs:Trigger>
        <twig:Tabs:Trigger value="tab-2">Tab 2</twig:Tabs:Trigger>
        <twig:Tabs:Trigger value="tab-3">Tab 3</twig:Tabs:Trigger>
    </twig:Tabs:List>
</twig:Tabs>
```

### Box Using Radio Inputs

daisyUI can also build tabs with radio inputs, which switch without JavaScript. The inputs are announced as radio buttons, not as tabs, so prefer the `Tabs` component when the tabs control panels. The `name` of each tab group must be unique.

```twig {"preview":true}
<div class="tabs tabs-box">
    <input type="radio" name="tabs-box-radio" class="tab" aria-label="Tab 1" />
    <input type="radio" name="tabs-box-radio" class="tab" aria-label="Tab 2" checked="checked" />
    <input type="radio" name="tabs-box-radio" class="tab" aria-label="Tab 3" />
</div>
```

### Sizes

```twig {"preview":true}
<div class="flex flex-col items-center gap-6">
    <twig:Tabs id="tabs-size-xs" defaultValue="tab-2" variant="lift" size="xs">
        <twig:Tabs:List aria-label="Tabs">
            <twig:Tabs:Trigger value="tab-1">Xsmall</twig:Tabs:Trigger>
            <twig:Tabs:Trigger value="tab-2">Xsmall</twig:Tabs:Trigger>
            <twig:Tabs:Trigger value="tab-3">Xsmall</twig:Tabs:Trigger>
        </twig:Tabs:List>
    </twig:Tabs>
    <twig:Tabs id="tabs-size-sm" defaultValue="tab-2" variant="lift" size="sm">
        <twig:Tabs:List aria-label="Tabs">
            <twig:Tabs:Trigger value="tab-1">Small</twig:Tabs:Trigger>
            <twig:Tabs:Trigger value="tab-2">Small</twig:Tabs:Trigger>
            <twig:Tabs:Trigger value="tab-3">Small</twig:Tabs:Trigger>
        </twig:Tabs:List>
    </twig:Tabs>
    <twig:Tabs id="tabs-size-md" defaultValue="tab-2" variant="lift">
        <twig:Tabs:List aria-label="Tabs">
            <twig:Tabs:Trigger value="tab-1">Medium</twig:Tabs:Trigger>
            <twig:Tabs:Trigger value="tab-2">Medium</twig:Tabs:Trigger>
            <twig:Tabs:Trigger value="tab-3">Medium</twig:Tabs:Trigger>
        </twig:Tabs:List>
    </twig:Tabs>
    <twig:Tabs id="tabs-size-lg" defaultValue="tab-2" variant="lift" size="lg">
        <twig:Tabs:List aria-label="Tabs">
            <twig:Tabs:Trigger value="tab-1">Large</twig:Tabs:Trigger>
            <twig:Tabs:Trigger value="tab-2">Large</twig:Tabs:Trigger>
            <twig:Tabs:Trigger value="tab-3">Large</twig:Tabs:Trigger>
        </twig:Tabs:List>
    </twig:Tabs>
    <twig:Tabs id="tabs-size-xl" defaultValue="tab-2" variant="lift" size="xl">
        <twig:Tabs:List aria-label="Tabs">
            <twig:Tabs:Trigger value="tab-1">Xlarge</twig:Tabs:Trigger>
            <twig:Tabs:Trigger value="tab-2">Xlarge</twig:Tabs:Trigger>
            <twig:Tabs:Trigger value="tab-3">Xlarge</twig:Tabs:Trigger>
        </twig:Tabs:List>
    </twig:Tabs>
</div>
```

### Border With Content

```twig {"preview":true}
<twig:Tabs id="tabs-border-content" defaultValue="tab-2" variant="border" class="w-full">
    <twig:Tabs:List aria-label="Tabs">
        <twig:Tabs:Trigger value="tab-1">Tab 1</twig:Tabs:Trigger>
        <twig:Tabs:Trigger value="tab-2">Tab 2</twig:Tabs:Trigger>
        <twig:Tabs:Trigger value="tab-3">Tab 3</twig:Tabs:Trigger>
    </twig:Tabs:List>
    <twig:Tabs:Content value="tab-1" class="border-base-300 bg-base-100 p-10">Tab content 1</twig:Tabs:Content>
    <twig:Tabs:Content value="tab-2" class="border-base-300 bg-base-100 p-10">Tab content 2</twig:Tabs:Content>
    <twig:Tabs:Content value="tab-3" class="border-base-300 bg-base-100 p-10">Tab content 3</twig:Tabs:Content>
</twig:Tabs>
```

### Lift With Content

```twig {"preview":true}
<twig:Tabs id="tabs-lift-content" defaultValue="tab-2" variant="lift" class="w-full">
    <twig:Tabs:List aria-label="Tabs">
        <twig:Tabs:Trigger value="tab-1">Tab 1</twig:Tabs:Trigger>
        <twig:Tabs:Trigger value="tab-2">Tab 2</twig:Tabs:Trigger>
        <twig:Tabs:Trigger value="tab-3">Tab 3</twig:Tabs:Trigger>
    </twig:Tabs:List>
    <twig:Tabs:Content value="tab-1" class="bg-base-100 border-base-300 p-6">Tab content 1</twig:Tabs:Content>
    <twig:Tabs:Content value="tab-2" class="bg-base-100 border-base-300 p-6">Tab content 2</twig:Tabs:Content>
    <twig:Tabs:Content value="tab-3" class="bg-base-100 border-base-300 p-6">Tab content 3</twig:Tabs:Content>
</twig:Tabs>
```

### Lift With Icons And Content

```twig {"preview":true}
<twig:Tabs id="tabs-lift-icons" defaultValue="tab-2" variant="lift" class="w-full">
    <twig:Tabs:List aria-label="Tabs">
        <twig:Tabs:Trigger value="tab-1">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4 me-2"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z" /></svg>
            Live
        </twig:Tabs:Trigger>
        <twig:Tabs:Trigger value="tab-2">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4 me-2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.182 15.182a4.5 4.5 0 0 1-6.364 0M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0ZM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Z" /></svg>
            Laugh
        </twig:Tabs:Trigger>
        <twig:Tabs:Trigger value="tab-3">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-4 me-2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" /></svg>
            Love
        </twig:Tabs:Trigger>
    </twig:Tabs:List>
    <twig:Tabs:Content value="tab-1" class="bg-base-100 border-base-300 p-6">Tab content 1</twig:Tabs:Content>
    <twig:Tabs:Content value="tab-2" class="bg-base-100 border-base-300 p-6">Tab content 2</twig:Tabs:Content>
    <twig:Tabs:Content value="tab-3" class="bg-base-100 border-base-300 p-6">Tab content 3</twig:Tabs:Content>
</twig:Tabs>
```

### Lift With Content On Bottom

```twig {"preview":true}
<twig:Tabs id="tabs-lift-bottom" defaultValue="tab-2" variant="lift" placement="bottom" class="w-full">
    <twig:Tabs:List aria-label="Tabs">
        <twig:Tabs:Trigger value="tab-1">Tab 1</twig:Tabs:Trigger>
        <twig:Tabs:Trigger value="tab-2">Tab 2</twig:Tabs:Trigger>
        <twig:Tabs:Trigger value="tab-3">Tab 3</twig:Tabs:Trigger>
    </twig:Tabs:List>
    <twig:Tabs:Content value="tab-1" class="bg-base-100 border-base-300 p-6">Tab content 1</twig:Tabs:Content>
    <twig:Tabs:Content value="tab-2" class="bg-base-100 border-base-300 p-6">Tab content 2</twig:Tabs:Content>
    <twig:Tabs:Content value="tab-3" class="bg-base-100 border-base-300 p-6">Tab content 3</twig:Tabs:Content>
</twig:Tabs>
```

### Box With Content

```twig {"preview":true}
<twig:Tabs id="tabs-box-content" defaultValue="tab-2" variant="box" class="w-full">
    <twig:Tabs:List aria-label="Tabs">
        <twig:Tabs:Trigger value="tab-1">Tab 1</twig:Tabs:Trigger>
        <twig:Tabs:Trigger value="tab-2">Tab 2</twig:Tabs:Trigger>
        <twig:Tabs:Trigger value="tab-3">Tab 3</twig:Tabs:Trigger>
    </twig:Tabs:List>
    <twig:Tabs:Content value="tab-1" class="bg-base-100 border-base-300 p-6">Tab content 1</twig:Tabs:Content>
    <twig:Tabs:Content value="tab-2" class="bg-base-100 border-base-300 p-6">Tab content 2</twig:Tabs:Content>
    <twig:Tabs:Content value="tab-3" class="bg-base-100 border-base-300 p-6">Tab content 3</twig:Tabs:Content>
</twig:Tabs>
```

### Horizontal Scroll

The tabs scroll horizontally when there's no space.

```twig {"preview":true}
<div class="max-w-60 overflow-x-auto">
    <twig:Tabs id="tabs-scroll" defaultValue="tab-2" variant="lift" class="min-w-max">
        <twig:Tabs:List aria-label="Tabs">
            <twig:Tabs:Trigger value="tab-1" class="z-1">Tab title 1</twig:Tabs:Trigger>
            <twig:Tabs:Trigger value="tab-2" class="z-1">Tab title 2</twig:Tabs:Trigger>
            <twig:Tabs:Trigger value="tab-3" class="z-1">Tab title 3</twig:Tabs:Trigger>
            <twig:Tabs:Trigger value="tab-4" class="z-1">Tab title 4</twig:Tabs:Trigger>
        </twig:Tabs:List>
        <twig:Tabs:Content value="tab-1" class="sticky start-0 max-w-60 border-base-300 bg-base-100 p-6">Tab content 1</twig:Tabs:Content>
        <twig:Tabs:Content value="tab-2" class="sticky start-0 max-w-60 border-base-300 bg-base-100 p-6">Tab content 2</twig:Tabs:Content>
        <twig:Tabs:Content value="tab-3" class="sticky start-0 max-w-60 border-base-300 bg-base-100 p-6">Tab content 3</twig:Tabs:Content>
        <twig:Tabs:Content value="tab-4" class="sticky start-0 max-w-60 border-base-300 bg-base-100 p-6">Tab content 4</twig:Tabs:Content>
    </twig:Tabs>
</div>
```

### Custom Color

```twig {"preview":true}
<twig:Tabs id="tabs-custom-color" defaultValue="tab-2" variant="lift">
    <twig:Tabs:List aria-label="Tabs">
        <twig:Tabs:Trigger value="tab-1">Tab 1</twig:Tabs:Trigger>
        <twig:Tabs:Trigger value="tab-2" class="text-primary [--tab-bg:orange] [--tab-border-color:red]">Tab 2</twig:Tabs:Trigger>
        <twig:Tabs:Trigger value="tab-3">Tab 3</twig:Tabs:Trigger>
    </twig:Tabs:List>
</twig:Tabs>
```

### Disabled

A disabled tab can't be selected, and the arrow keys skip it.

```twig {"preview":true}
<twig:Tabs id="tabs-disabled" defaultValue="tab-1" variant="lift">
    <twig:Tabs:List aria-label="Tabs">
        <twig:Tabs:Trigger value="tab-1">Tab 1</twig:Tabs:Trigger>
        <twig:Tabs:Trigger value="tab-2" disabled>Tab 2</twig:Tabs:Trigger>
        <twig:Tabs:Trigger value="tab-3">Tab 3</twig:Tabs:Trigger>
    </twig:Tabs:List>
</twig:Tabs>
```

### RTL

To enable RTL support, set the `dir="rtl"` attribute on the root element.

```twig {"preview":true}
<div class="flex w-full flex-col gap-8">
    <twig:Tabs id="tabs-rtl-ar" defaultValue="tab-2" variant="lift" class="w-full" dir="rtl">
        <twig:Tabs:List aria-label="علامات التبويب">
            <twig:Tabs:Trigger value="tab-1">التبويب 1</twig:Tabs:Trigger>
            <twig:Tabs:Trigger value="tab-2">التبويب 2</twig:Tabs:Trigger>
            <twig:Tabs:Trigger value="tab-3">التبويب 3</twig:Tabs:Trigger>
        </twig:Tabs:List>
        <twig:Tabs:Content value="tab-1" class="bg-base-100 border-base-300 p-6">محتوى التبويب 1</twig:Tabs:Content>
        <twig:Tabs:Content value="tab-2" class="bg-base-100 border-base-300 p-6">محتوى التبويب 2</twig:Tabs:Content>
        <twig:Tabs:Content value="tab-3" class="bg-base-100 border-base-300 p-6">محتوى التبويب 3</twig:Tabs:Content>
    </twig:Tabs>
    <twig:Tabs id="tabs-rtl-he" defaultValue="tab-2" variant="lift" class="w-full" dir="rtl">
        <twig:Tabs:List aria-label="תוויות">
            <twig:Tabs:Trigger value="tab-1">התווית 1</twig:Tabs:Trigger>
            <twig:Tabs:Trigger value="tab-2">התווית 2</twig:Tabs:Trigger>
            <twig:Tabs:Trigger value="tab-3">התווית 3</twig:Tabs:Trigger>
        </twig:Tabs:List>
        <twig:Tabs:Content value="tab-1" class="bg-base-100 border-base-300 p-6">תוכן התווית 1</twig:Tabs:Content>
        <twig:Tabs:Content value="tab-2" class="bg-base-100 border-base-300 p-6">תוכן התווית 2</twig:Tabs:Content>
        <twig:Tabs:Content value="tab-3" class="bg-base-100 border-base-300 p-6">תוכן התווית 3</twig:Tabs:Content>
    </twig:Tabs>
</div>
```

## API Reference

::: api-reference
