# Form

A set of layout wrappers to build accessible forms with label, description and error-message slots.

```twig {"preview":true}
<twig:Form class="mx-auto w-full max-w-md">
    <twig:Form:Item>
        <twig:Form:Label for="form-email">Email</twig:Form:Label>
        <twig:Input id="form-email" name="email" type="email" placeholder="you@example.com" />
        <twig:Form:Description>We'll never share your email with anyone else.</twig:Form:Description>
    </twig:Form:Item>
    <twig:Form:Item>
        <twig:Form:Label for="form-bio">Bio</twig:Form:Label>
        <twig:Textarea id="form-bio" name="bio" placeholder="Tell us a bit about yourself" />
        <twig:Form:Description>You can @mention other users and organizations.</twig:Form:Description>
    </twig:Form:Item>
    <div class="flex justify-end gap-2">
        <twig:Button type="button" variant="outline">Cancel</twig:Button>
        <twig:Button type="submit">Save</twig:Button>
    </div>
</twig:Form>
```

## Installation

::: installation

## Usage

```twig
<twig:Form>
    <twig:Form:Item>
        <twig:Form:Label for="form-username">Username</twig:Form:Label>
        <twig:Input id="form-username" name="username" placeholder="shadcn" />
        <twig:Form:Description>This is your public display name.</twig:Form:Description>
    </twig:Form:Item>
    <twig:Button type="submit">Submit</twig:Button>
</twig:Form>
```

## Examples

### Validation

When a `Form:Item` contains a `Form:Message`, its `Form:Label` switches to the destructive color.

```twig {"preview":true}
<twig:Form class="mx-auto w-full max-w-md">
    <twig:Form:Item>
        <twig:Form:Label for="form-validation-email">Email</twig:Form:Label>
        <twig:Input id="form-validation-email" name="email" type="email" value="not-an-email" aria-invalid="true" />
        <twig:Form:Message>Please enter a valid email address.</twig:Form:Message>
    </twig:Form:Item>
</twig:Form>
```

## API Reference

::: api-reference
