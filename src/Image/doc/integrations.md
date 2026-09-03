# Integrations and customization

UX Image is deliberately backend-first. Compose its processing and rendering
contracts with the interaction, persistence and delivery tools already used by
the application.

Optional Symfony integrations are activated by an active bundle, not by
package presence alone. Flex normally updates `config/bundles.php`; without
Flex, register `TwigComponentBundle`, `FlysystemBundle` or `DoctrineBundle`
before enabling the corresponding configuration below.

## Symfony Forms

Use a normal `FileType`; validate authorization and file constraints before
calling the processor:

```php
// src/Form/ProductImageType.php
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\File;

final class ProductImageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('image', FileType::class, [
            'mapped' => false,
            'constraints' => [
                new File(maxSize: '6M', mimeTypes: ['image/jpeg', 'image/png']),
            ],
        ]);
    }
}
```

The Form constraint provides user-facing validation. UX Image still inspects
the actual bytes and enforces its independent processing budgets.

## Symfony UX Upload

Use UX Upload when users need progress, chunking, retry or resumability. The
two bundles meet at an application-owned adapter:

```text
staged upload id
    │ resolve + authorize + claim with UploadManagerInterface
    ▼
ClaimedUpload { storage, path, originalName, mimeType }
    │ read the configured UX Upload storage into a temporary local file
    ▼
UploadedFile
    │ ImageProcessorInterface::process()
    ▼
ImageAsset
```

UX Upload does not expose a direct `ClaimedUpload::toUploadedFile()` helper.
Keep the storage lookup and temporary-file lifecycle in one application
service, and delete the temporary copy in a `finally` block. Claim only after
the owner and current user have been checked; processing does not replace that
authorization boundary.

UX Image intentionally does not embed an upload widget. This keeps the image
pipeline usable with Forms, APIs, console imports and third-party upload
transports.

## Doctrine

Install and register DoctrineBundle, enable the DBAL type and map the value
object:

```yaml
# config/packages/ux_image.yaml
ux_image:
    doctrine_type: true
```

```php
// src/Entity/Product.php
use Doctrine\ORM\Mapping as ORM;
use Symfony\UX\Image\Doctrine\ImageAssetType;
use Symfony\UX\Image\ImageAsset;

#[ORM\Column(type: ImageAssetType::NAME, nullable: true)]
private ?ImageAsset $image = null;
```

Persist the returned asset in the same application transaction as its owner.
File storage and a relational database are not one distributed transaction:
define cleanup for files that were generated before a failed database flush.

## Messenger and asynchronous processing

`processing: async` is a transport-neutral hook, not a complete Messenger workflow.
Implement `ImageProcessingDispatcherInterface` and own:

- a stable owner identifier;
- the message and handler;
- retries and idempotency;
- persistence of the finished `ImageAsset`;
- cleanup of abandoned generations.

For scheduled profile migrations, the bounded
[`ux:image:regenerate`](regeneration.md) contract is usually simpler.

## Flysystem and object storage

Reference any `FilesystemOperator` under a named storage. UX Image writes the
original and variants through the same stream contract. See
[Storage](storage.md) for S3 configuration and transactional limitations.

## CDN delivery

The Cloudinary and Imgix builders generate delivery URLs. They do not upload
files or configure a provider source. Use them only when the provider already
addresses the stored path. Implement `CdnUrlBuilderInterface` for another
provider or signed delivery.

## TwigComponent, themes and design systems

The Twig functions are the lowest-friction rendering API:

```twig
{{ ux_picture(product.image, {
    alt: product.name,
    class: 'product-media',
}) }}
```

Use `<twig:ux:image>` when the application already composes TwigComponents.
The component service is registered only when `TwigComponentBundle` is active
in the kernel; merely having the package in `vendor/` does not enable it.
Override its template at
`templates/bundles/UXImageBundle/components/Image.html.twig`.

Prefer styling an application-owned wrapper or class over replacing the
semantic `<picture>` structure. Bootstrap, Tailwind and custom design systems
need no bridge package because the renderer emits standard HTML and accepts
application classes. PHP callers of `ImageRendererInterface` may also pass safe
extra attributes through the `attributes:` argument of `ImageRenderOptions`.

## LiveComponent and Turbo

Processing mutates storage and usually belongs to a form action or live action.
Once the model owns the new `ImageAsset`, re-render the component or Turbo
Frame normally. The returned `<picture>` requires no frontend initialization.

Avoid starting expensive image processing on every live re-render. Trigger it
from an explicit action and display a pending state when the application uses
Messenger.

## Replacing extension points

| Need                                | Contract                                          |
| ----------------------------------- | ------------------------------------------------- |
| Complete processing pipeline        | `ImageProcessorInterface` and `processor_service` |
| Driver primitives or tagged backend | `ImageDriverInterface`                            |
| Custom storage backend              | `StorageInterface` / `StreamStorageInterface`     |
| Signed or private URLs              | `UrlAdapterInterface`                             |
| Different CDN syntax                | `CdnUrlBuilderInterface`                          |
| Different HTML                      | `ImageRendererInterface`                          |
| Safe SVG rasterization              | `SvgPolicyInterface`                              |

Prefer one complete application service over partially overriding several
internal services. Compile the container in a functional test so missing
optional dependencies and invalid codec selections fail before deployment.
