# Post Link

A link submitted as a form, with optional HTTP method spoofing, CSRF protection, and a confirmation prompt.

```twig {"preview":true,"height":"190px","collapseClass":true}
<twig:PostLink href="/link/newsletter/subscribe" class="rounded-md bg-violet-600 px-4 py-2 text-sm font-medium text-white hover:bg-violet-500">
    Subscribe
</twig:PostLink>
```

## Installation

::: installation

## Usage

```twig
<twig:PostLink href="/posts/42/publish">
    Publish
</twig:PostLink>
```

## Examples

### Custom Method

Set the `method` prop to submit the form with a spoofed HTTP method. A hidden `_method` field is added so Symfony can route the request to the matching controller.

> [!WARNING]
> Method spoofing only works when HTTP method override is enabled in your Symfony app. Set `framework.http_method_override: true` in `config/packages/framework.yaml`.

```twig {"preview":true,"height":"300px","collapseClass":true}
<twig:PostLink href="/link/posts/42" method="DELETE" class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-500">
    Delete post
</twig:PostLink>
```

### With Confirmation

Pass a `confirm` message to prompt the user with a native confirmation dialog before the form is submitted.

```twig {"preview":true,"height":"190px","collapseClass":true}
<twig:PostLink href="/link/posts/42/delete" confirm="Are you sure you want to delete this post?" class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-500">
    Delete post
</twig:PostLink>
```

### With CSRF Protection

Set `csrfTokenId` to add a hidden CSRF token field, protecting the form against cross-site request forgery.

```twig {"preview":true,"height":"300px","collapseClass":true}
<twig:PostLink href="/link/posts/42/delete" csrfTokenId="delete-post" class="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-500">
    Delete post
</twig:PostLink>
```

## API Reference

::: api-reference
