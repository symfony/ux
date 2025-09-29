# Examples

## Default

```twig {"preview":true,"height":"500px"}
<twig:AlertDialog id="delete_account">
    <twig:AlertDialog:Trigger>
        <twig:Button {{ ...trigger_attrs }}>Open</twig:Button>
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
            <twig:AlertDialog:Action>Continue</twig:AlertDialog:Action>
        </twig:AlertDialog:Footer>
    </twig:AlertDialog:Content>
</twig:AlertDialog>
```

## Opened by default

```twig {"preview":true,"height":"500px"}
<twig:AlertDialog id="delete_account" open>
    <twig:AlertDialog:Trigger>
        <twig:Button {{ ...trigger_attrs }}>Open</twig:Button>
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
            <twig:AlertDialog:Action>Continue</twig:AlertDialog:Action>
        </twig:AlertDialog:Footer>
    </twig:AlertDialog:Content>
</twig:AlertDialog>
```
