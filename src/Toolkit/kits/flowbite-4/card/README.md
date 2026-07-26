# Card

Use these responsive card components to show data entries and information to your users in multiple forms and contexts such as for your blog, application, user profiles, and more.

```twig {"preview":true}
<twig:Card class="max-w-sm">
    <twig:Card:Header>
        <twig:Card:Title as="h5">Noteworthy technology acquisitions 2021</twig:Card:Title>
    </twig:Card:Header>

    <twig:Card:Content>
        <p>Here are the biggest technology acquisitions of 2025 so far, in reverse chronological order.</p>
    </twig:Card:Content>
</twig:Card>
```

## Installation

::: installation

## Usage

```twig
<twig:Card>
    <twig:Card:Header>
        <twig:Card:Title>Card Title</twig:Card:Title>
    </twig:Card:Header>
    <twig:Card:Content>
        <p>Card Content</p>
    </twig:Card:Content>
    <twig:Card:Footer>
        <p>Card Footer</p>
    </twig:Card:Footer>
</twig:Card>
```

## Examples

### Card with button

Use the following example of a card element if you also want to have an action button.

```twig {"preview":true}
<twig:Card class="max-w-sm">
    <twig:Card:Header>
        <twig:Card:Title as="h5">Noteworthy technology acquisitions 2021</twig:Card:Title>
    </twig:Card:Header>

    <twig:Card:Content>
        <p>Here are the biggest technology acquisitions of 2025 so far, in reverse chronological order.</p>
    </twig:Card:Content>

    <twig:Card:Footer>
        <twig:Button as="a" href="#">
            Read more
            <twig:ux:icon name="flowbite:arrow-right-outline" class="w-4 h-4 ms-1.5 -me-0.5" aria-hidden="true"/>
        </twig:Button>
    </twig:Card:Footer>
</twig:Card>
```

### Card with image

Use this alternative styled card with an image for features, blog posts, and more.

```twig {"preview":true}
<twig:Card class="max-w-sm">
    <twig:Card:Header>
        <a href="#">
            <img
                class="rounded-base"
                src="https://images.unsplash.com/photo-1535189043414-47a3c49a0bed?ixlib=rb-4.1.0&q=85&fm=jpg&crop=entropy&cs=srgb&w=1000"
                alt="Bukchon Hanok Village by Y K" />
        </a>
    </twig:Card:Header>

    <twig:Card:Content>
        <twig:Badge border="bordered" class="mb-2">
            <twig:ux:icon name="flowbite:fire-outline" class="w-3 h-3 me-1" aria-hidden="true"/>
            Trending
        </twig:Badge>
        <a href="#">
            <twig:Card:Title class="mb-2" as="h5">Streamlining your design process today.</twig:Card:Title>
        </a>
        <p>In today’s fast-paced digital landscape, fostering seamless collaboration among Developers and IT Operations.</p>
    </twig:Card:Content>

    <twig:Card:Footer>
        <twig:Button variant="secondary" as="a" href="#">
            Read more
            <twig:ux:icon name="flowbite:arrow-right-outline" class="w-4 h-4 ms-1.5 -me-0.5" aria-hidden="true"/>
        </twig:Button>
    </twig:Card:Footer>
</twig:Card>
```

### Card with form inputs

Use this card example where you can add form input elements that can be used for authentication actions or any other context where you need to receive information from your users.

```twig {"preview":true}
<twig:Card class="w-full max-w-sm">
    <twig:Card:Header>
        <twig:Card:Title as="h5">Sign in to our platform</twig:Card:Title>
    </twig:Card:Header>

    <twig:Card:Content>
        <div class="mb-4">
            <twig:Label for="email" class="mb-2.5">Your email</twig:Label>
            <twig:Input type="email" id="email" placeholder="example@company.com" required />
        </div>

        <div>
            <twig:Label for="password" class="mb-2.5">Your password</twig:Label>
            <twig:Input type="password" id="password" placeholder="•••••••••" required />
        </div>

        <div class="flex items-start mt-6">
            <div class="flex items-center">
                <twig:Checkbox id="checkbox-remember" value="" />
                <twig:Label for="checkbox-remember" class="ms-2">Remember me</twig:Label>
            </div>
            <a href="#" class="ms-auto text-sm font-medium text-fg-brand hover:underline">Lost Password?</a>
        </div>
    </twig:Card:Content>

    <twig:Card:Footer>
        <twig:Button type="submit" class="w-full mb-3">
            Login to your account
        </twig:Button>

        <div class="text-sm font-medium text-body">Not registered? <a href="#" class="text-fg-brand hover:underline">Create account</a></div>
    </twig:Card:Footer>
</twig:Card>
```

## API Reference

::: api-reference
