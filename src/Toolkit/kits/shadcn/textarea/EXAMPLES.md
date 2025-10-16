# Examples

## Default

```twig {"preview":true}
<twig:Textarea placeholder="Type your message here." class="max-w-sm" />
```

## With default content

```twig {"preview":true}
<twig:Textarea class="max-w-sm">This is the default content of the textarea.</twig:Textarea>
```

## With Label

```twig {"preview":true}
<div class="grid w-sm gap-1.5">
    <twig:Label for="message">Your message</twig:Label>
    <twig:Textarea id="message" placeholder="Type your message here." />
</div>
```

## Disabled

```twig {"preview":true}
<twig:Textarea placeholder="Type your message here." disabled class="max-w-sm" />
```
