# Card

A container that groups related content and actions into a box with optional header, content, and footer sections.

```twig {"preview":true,"height":"300px"}
<twig:Card class="w-[350px]">
    <twig:Card:Header>
        <twig:Card:Title>Card Title</twig:Card:Title>
        <twig:Card:Description>Card Description</twig:Card:Description>
    </twig:Card:Header>
    <twig:Card:Content>
        <p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Quisquam, quos.</p>
    </twig:Card:Content>
    <twig:Card:Footer class="justify-between">
        <twig:Button variant="outline">Cancel</twig:Button>
        <twig:Button>Action</twig:Button>
    </twig:Card:Footer>
</twig:Card>
```

## Installation

<!-- Placeholder: Installation -->

## Usage

<!-- Placeholder: Usage -->

## Examples

### Default

```twig {"preview":true,"height":"300px"}
<twig:Card class="w-[350px]">
    <twig:Card:Header>
        <twig:Card:Title>Card Title</twig:Card:Title>
        <twig:Card:Description>Card Description</twig:Card:Description>
    </twig:Card:Header>
    <twig:Card:Content>
        <p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Quisquam, quos.</p>
    </twig:Card:Content>
    <twig:Card:Footer class="justify-between">
        <twig:Button variant="outline">Cancel</twig:Button>
        <twig:Button>Action</twig:Button>
    </twig:Card:Footer>
</twig:Card>
```

### With Notifications

```twig {"preview":true,"height":"400px"}
{% set notifications = [
  { title: "Your call has been confirmed.", description: "1 hour ago"},
  { title: "You have a new message!",  description: "1 hour ago"},
  { title: "Your subscription is expiring soon!", description: "2 hours ago" },
] %}
<twig:Card class="w-[350px]">
    <twig:Card:Header>
        <twig:Card:Title>Notifications</twig:Card:Title>
        <twig:Card:Description>You have 3 unread messages.</twig:Card:Description>
    </twig:Card:Header>
    <twig:Card:Content>
        {%- for notification in notifications -%}
            <div class="mb-4 grid grid-cols-[25px_1fr] items-start pb-4 last:mb-0 last:pb-0">
                <span class="flex h-2 w-2 translate-y-1 rounded-full bg-sky-500"></span>
                <div class="space-y-1">
                    <p class="text-sm font-medium leading-none">
                        {{ notification.title }}
                    </p>
                    <p class="text-sm text-muted-foreground">
                        {{ notification.description }}
                    </p>
                </div>
            </div>
        {%- endfor -%}
    </twig:Card:Content>
    <twig:Card:Footer>
        <twig:Button class="w-full">
            <twig:ux:icon name="lucide:check" />
            Mark all as read
        </twig:Button>
    </twig:Card:Footer>
</twig:Card>
```
