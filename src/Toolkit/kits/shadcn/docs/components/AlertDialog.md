# AlertDialog

A modal dialog that interrupts the user with important content and expects a response.

```twig {"preview":true}
<twig:AlertDialog>
    <twig:AlertDialog:Trigger>
        <twig:Button variant="outline">Show Dialog</twig:Button>
    </twig:AlertDialog:Trigger>
    <twig:AlertDialog:Content>
        <twig:AlertDialog:Header>
            <twig:AlertDialog:Title>Are you sure?</twig:AlertDialog:Title>
            <twig:AlertDialog:Description>
                This action cannot be undone. This will permanently delete your account
                and remove your data from our servers.
            </twig:AlertDialog:Description>
        </twig:AlertDialog:Header>
        <twig:AlertDialog:Footer>
            <twig:AlertDialog:Cancel>Cancel</twig:AlertDialog:Cancel>
            <twig:AlertDialog:Action>Continue</twig:AlertDialog:Action>
        </twig:AlertDialog:Footer>
    </twig:AlertDialog:Content>
</twig:AlertDialog>
```

## Installation

<!-- Placeholder: Installation -->

## Usage

<!-- Placeholder: Usage -->

## Examples

### Default

```twig {"preview":true}
<twig:AlertDialog>
    <twig:AlertDialog:Trigger>
        <twig:Button variant="outline">Show Dialog</twig:Button>
    </twig:AlertDialog:Trigger>
    <twig:AlertDialog:Content>
        <twig:AlertDialog:Header>
            <twig:AlertDialog:Title>Are you sure?</twig:AlertDialog:Title>
            <twig:AlertDialog:Description>
                This action cannot be undone. This will permanently delete your account
                and remove your data from our servers.
            </twig:AlertDialog:Description>
        </twig:AlertDialog:Header>
        <twig:AlertDialog:Footer>
            <twig:AlertDialog:Cancel>Cancel</twig:AlertDialog:Cancel>
            <twig:AlertDialog:Action>Continue</twig:AlertDialog:Action>
        </twig:AlertDialog:Footer>
    </twig:AlertDialog:Content>
</twig:AlertDialog>
```

### Destructive

```twig {"preview":true}
<twig:AlertDialog>
    <twig:AlertDialog:Trigger>
        <twig:Button variant="destructive">Delete Account</twig:Button>
    </twig:AlertDialog:Trigger>
    <twig:AlertDialog:Content>
        <twig:AlertDialog:Header>
            <twig:AlertDialog:Title>Are you absolutely sure?</twig:AlertDialog:Title>
            <twig:AlertDialog:Description>
                This action cannot be undone. This will permanently delete your account
                and remove your data from our servers.
            </twig:AlertDialog:Description>
        </twig:AlertDialog:Header>
        <twig:AlertDialog:Footer>
            <twig:AlertDialog:Cancel>Cancel</twig:AlertDialog:Cancel>
            <twig:AlertDialog:Action variant="destructive">Delete Account</twig:AlertDialog:Action>
        </twig:AlertDialog:Footer>
    </twig:AlertDialog:Content>
</twig:AlertDialog>
``` 
