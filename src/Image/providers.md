# Providers

## Available Providers

### LiipImagine (Default)

Local image processing using LiipImagineBundle:

```yaml
# config/packages/ux_image.yaml
ux_image:
  provider: liip_imagine
  liip_imagine:
    driver: gd # or imagick
    cache: default
```

### Cloudinary

Cloud-based image processing using Cloudinary:

```yaml
# config/packages/ux_image.yaml
ux_image:
  provider: cloudinary
  cloudinary:
    cloud_name: your_cloud_name
    api_key: your_api_key
    api_secret: your_api_secret
```

## Using Providers in Templates

You can specify a provider per image:

```twig
{# Use default provider #}
<twig:img
    src="/images/hero.jpg"
    alt="Hero image"
    width="800"
/>

{# Use specific provider #}
<twig:img
    provider="cloudinary"
    src="/images/hero.jpg"
    alt="Hero image"
    width="800"
/>
```

## Provider Configuration

Each provider can be configured in your `ux_image.yaml`:

```yaml
# config/packages/ux_image.yaml
ux_image:
  provider: liip_imagine
  providers:
    liip_imagine:
      driver: gd
      cache: default
      filters:
        # LiipImagine filters configuration

    cloudinary:
      cloud_name: "%env(CLOUDINARY_CLOUD_NAME)%"
      api_key: "%env(CLOUDINARY_API_KEY)%"
      api_secret: "%env(CLOUDINARY_API_SECRET)%"
      secure: true
```

## Custom Providers

You can create your own provider by implementing the `ProviderInterface`:

```php
namespace App\Provider;

use Symfony\UX\Image\Provider\ProviderInterface;

class CustomProvider implements ProviderInterface
{
    public function getName(): string
    {
        return 'custom';
    }

    public function generateUrl(string $src, array $options): string
    {
        // Your URL generation logic here
    }
}
```

Register your provider:

```yaml
# config/services.yaml
services:
  App\Provider\CustomProvider:
    tags: ["ux.image.provider"]
```
