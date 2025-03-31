# Avatar

The Avatar component displays a user's profile picture or a fallback representation when no image is available.

## Examples

### Avatar with Image

```twig
<twig:Avatar>
    <twig:Avatar:Image src="https://github.com/symfony.png" alt="@symfony" />
</twig:Avatar>
```

### Avatar with Fallback

```twig
<twig:Avatar>
    <twig:Avatar:Fallback>JF</twig:Avatar:Fallback>
</twig:Avatar>
``` 
