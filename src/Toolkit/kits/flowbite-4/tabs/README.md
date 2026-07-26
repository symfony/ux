# Tabs

Use the following default tabs component example to show a list of links that the user can navigate from on your website.

```twig {"preview":true}
<twig:Tabs defaultValue="profile" class="max-w-xl w-full">
    <twig:Tabs:List>
        <twig:Tabs:Trigger value="profile">Profile</twig:Tabs:Trigger>
        <twig:Tabs:Trigger value="dashboard">Dashboard</twig:Tabs:Trigger>
        <twig:Tabs:Trigger value="settings">Settings</twig:Tabs:Trigger>
        <twig:Tabs:Trigger value="contact">Contact</twig:Tabs:Trigger>
        <twig:Tabs:Trigger value="disabled" disabled>Disabled</twig:Tabs:Trigger>
    </twig:Tabs:List>
    <twig:Tabs:Content value="profile">
        <div class="p-4 bg-neutral-secondary text-medium text-body rounded-base w-full">
            <p class="text-sm text-body">This is some placeholder content the <strong class="font-medium text-heading">Profile tab's associated content</strong>. Clicking another tab will toggle the visibility of this one for the next. The tab JavaScript swaps classes to control the content visibility and styling.</p>
        </div>
    </twig:Tabs:Content>
    <twig:Tabs:Content value="dashboard">
        <div class="p-4 bg-neutral-secondary text-medium text-body rounded-base w-full">
            <p class="text-sm text-body">This is some placeholder content the <strong class="font-medium text-heading">Dashboard tab's associated content</strong>. Clicking another tab will toggle the visibility of this one for the next. The tab JavaScript swaps classes to control the content visibility and styling.</p>
        </div>
    </twig:Tabs:Content>
    <twig:Tabs:Content value="settings">
        <div class="p-4 bg-neutral-secondary text-medium text-body rounded-base w-full">
            <p class="text-sm text-body">This is some placeholder content the <strong class="font-medium text-heading">Settings tab's associated content</strong>. Clicking another tab will toggle the visibility of this one for the next. The tab JavaScript swaps classes to control the content visibility and styling.</p>
        </div>
    </twig:Tabs:Content>
    <twig:Tabs:Content value="contact">
        <div class="p-4 bg-neutral-secondary text-medium text-body rounded-base w-full">
            <p class="text-sm text-body">This is some placeholder content the <strong class="font-medium text-heading">Contact tab's associated content</strong>. Clicking another tab will toggle the visibility of this one for the next. The tab JavaScript swaps classes to control the content visibility and styling.</p>
        </div>
    </twig:Tabs:Content>
</twig:Tabs>
```

## Installation

::: installation

## Usage

```twig
<twig:Tabs defaultValue="account" class="w-[400px]">
    <twig:Tabs:List>
        <twig:Tabs:Trigger value="account">Account</twig:Tabs:Trigger>
        <twig:Tabs:Trigger value="password">Password</twig:Tabs:Trigger>
    </twig:Tabs:List>
    <twig:Tabs:Content value="account">Make changes to your account here.</twig:Tabs:Content>
    <twig:Tabs:Content value="password">Change your password here.</twig:Tabs:Content>
</twig:Tabs>
```

## Examples

### Tabs with underline

Use this alternative tabs component style with an underline instead of a background when hovering and being active on a certain page.

```twig {"preview":true}
<twig:Tabs defaultValue="dashboard" class="max-w-[800px] w-full">
    <twig:Tabs:List variant="line">
        <twig:Tabs:Trigger value="profile">Profile</twig:Tabs:Trigger>
        <twig:Tabs:Trigger value="dashboard">Dashboard</twig:Tabs:Trigger>
        <twig:Tabs:Trigger value="settings">Settings</twig:Tabs:Trigger>
        <twig:Tabs:Trigger value="contacts">Contacts</twig:Tabs:Trigger>
        <twig:Tabs:Trigger value="disabled" disabled>Disabled</twig:Tabs:Trigger>
    </twig:Tabs:List>
</twig:Tabs>
```

### Tabs with icons

This is an example of the tabs component where you can also use a SVG powered icon to complement the text within the navigational tabs.

```twig {"preview":true}
<twig:Tabs defaultValue="dashboard" class="max-w-[800px] w-full">
    <twig:Tabs:List variant="line">
        <twig:Tabs:Trigger value="profile">
            <twig:ux:icon name="flowbite:user-circle-outline" class="size-4 me-2" aria-hidden="true"/>
            Profile
        </twig:Tabs:Trigger>
        <twig:Tabs:Trigger value="dashboard">
            <twig:ux:icon name="flowbite:grid-outline" class="size-4 me-2" aria-hidden="true"/>
            Dashboard
        </twig:Tabs:Trigger>
        <twig:Tabs:Trigger value="settings">
            <twig:ux:icon name="flowbite:adjustments-vertical-outline" class="size-4 me-2" aria-hidden="true"/>
            Settings
        </twig:Tabs:Trigger>
        <twig:Tabs:Trigger value="contacts">
            <twig:ux:icon name="flowbite:user-headset-outline" class="size-4 me-2" aria-hidden="true"/>
            Contacts
        </twig:Tabs:Trigger>
        <twig:Tabs:Trigger value="disabled" disabled>
            Disabled
        </twig:Tabs:Trigger>
    </twig:Tabs:List>
</twig:Tabs>
```

### Pills tabs

If you want to use pills as a style for the tabs component you can do so by using this example.

```twig {"preview":true}
<twig:Tabs defaultValue="dashboard" class="max-w-[800px] w-full">
    <twig:Tabs:List variant="pill">
        <twig:Tabs:Trigger value="profile">Profile</twig:Tabs:Trigger>
        <twig:Tabs:Trigger value="dashboard">Dashboard</twig:Tabs:Trigger>
        <twig:Tabs:Trigger value="settings">Settings</twig:Tabs:Trigger>
        <twig:Tabs:Trigger value="contacts">Contacts</twig:Tabs:Trigger>
        <twig:Tabs:Trigger value="disabled" disabled>Disabled</twig:Tabs:Trigger>
    </twig:Tabs:List>
</twig:Tabs>
```

### Vertical

Use this example to show a vertically aligned set of tabs on the left side of the page.

```twig {"preview":true}
<twig:Tabs defaultValue="profile" orientation="vertical" class="max-w-xl w-full">
    <twig:Tabs:List variant="pill">
        <twig:Tabs:Trigger value="profile">Profile</twig:Tabs:Trigger>
        <twig:Tabs:Trigger value="dashboard">Dashboard</twig:Tabs:Trigger>
        <twig:Tabs:Trigger value="settings">Settings</twig:Tabs:Trigger>
        <twig:Tabs:Trigger value="contact">Contact</twig:Tabs:Trigger>
        <twig:Tabs:Trigger value="disabled" disabled>Disabled</twig:Tabs:Trigger>
    </twig:Tabs:List>
    <twig:Tabs:Content value="profile">
        <div class="p-6 bg-neutral-secondary text-medium text-body rounded-base w-full h-full">
            <h3 class="text-lg font-semibold text-heading mb-4">Profile Tab</h3>
            <p class="mb-2">This is some placeholder content the Profile tab's associated content, clicking another tab will toggle the visibility of this one for the next.</p>
            <p>The tab JavaScript swaps classes to control the content visibility and styling.</p>
        </div>
    </twig:Tabs:Content>
    <twig:Tabs:Content value="dashboard">
        <div class="p-6 bg-neutral-secondary text-medium text-body rounded-base w-full h-full">
            <h3 class="text-lg font-semibold text-heading mb-4">Dashboard Tab</h3>
        </div>
    </twig:Tabs:Content>
    <twig:Tabs:Content value="settings">
        <div class="p-6 bg-neutral-secondary text-medium text-body rounded-base w-full h-full">
            <h3 class="text-lg font-semibold text-heading mb-4">Settings Tab</h3>
        </div>
    </twig:Tabs:Content>
    <twig:Tabs:Content value="contact">
        <div class="p-6 bg-neutral-secondary text-medium text-body rounded-base w-full h-full">
            <h3 class="text-lg font-semibold text-heading mb-4">Contact Tab</h3>
        </div>
    </twig:Tabs:Content>
</twig:Tabs>
```

## API Reference

::: api-reference
