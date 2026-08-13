# Regeneration

Regeneration starts from application persistence, never from a storage scan.
Only the application knows which files are originals, who owns them, and where
the updated `ImageAsset` must be saved.

Implement one provider and one persister:

```php
use Doctrine\DBAL\Connection;
use Symfony\UX\Image\ImageAsset;
use Symfony\UX\Image\Regeneration\ImageAssetBatch;
use Symfony\UX\Image\Regeneration\ImageAssetBatchQuery;
use Symfony\UX\Image\Regeneration\ImageAssetPersisterInterface;
use Symfony\UX\Image\Regeneration\ImageAssetProviderInterface;
use Symfony\UX\Image\Regeneration\ImageAssetReference;

final class ProductImageProvider implements ImageAssetProviderInterface
{
    public function __construct(private Connection $connection)
    {
    }

    public function fetch(ImageAssetBatchQuery $query): ImageAssetBatch
    {
        $rows = $this->connection->fetchAllAssociative(
            <<<'SQL'
                SELECT id, image_asset, image_version
                FROM product
                WHERE image_profile = :profile
                  AND image_storage = :storage
                  AND (:after IS NULL OR id > :after)
                ORDER BY id ASC
                LIMIT :limit
                SQL,
            [
                'profile' => $query->getProfile(),
                'storage' => $query->getStorage(),
                'after' => $query->getAfter(),
                'limit' => $query->getLimit(),
            ],
            ['limit' => \Doctrine\DBAL\ParameterType::INTEGER],
        );

        $references = array_map(static function (array $row): ImageAssetReference {
            $data = json_decode($row['image_asset'], true, 512, \JSON_THROW_ON_ERROR);

            return new ImageAssetReference(
                id: (string) $row['id'],
                cursor: (string) $row['id'],
                version: (string) $row['image_version'],
                asset: ImageAsset::fromArray($data),
            );
        }, $rows);

        $nextCursor = \count($references) === $query->getLimit()
            ? $references[array_key_last($references)]->getCursor()
            : null;

        return new ImageAssetBatch($references, $nextCursor);
    }
}

final class ProductImagePersister implements ImageAssetPersisterInterface
{
    public function __construct(private Connection $connection)
    {
    }

    public function compareAndSwap(ImageAssetReference $reference, ImageAsset $asset): bool
    {
        $updatedRows = $this->connection->executeStatement(
            <<<'SQL'
                UPDATE product
                SET image_asset = :asset,
                    image_version = image_version + 1
                WHERE id = :id
                  AND image_version = :version
                SQL,
            [
                'asset' => json_encode($asset->toArray(), \JSON_THROW_ON_ERROR),
                'id' => $reference->getId(),
                'version' => $reference->getVersion(),
            ],
        );

        return 1 === $updatedRows;
    }
}
```

This example uses `product.id` as a keyset cursor and `image_version` as the
optimistic-lock token. Adapt the table and column names to the application, but
keep the stable ordering and single-statement compare-and-swap.

Wire the pair explicitly:

```yaml
# config/services.yaml
services:
    App\Image\ProductImageProvider:
        tags: ['ux_image.regeneration.provider']

    App\Image\ProductImagePersister:
        tags: ['ux_image.regeneration.persister']
```

Exactly one service with each tag is required. Zero or multiple services make
the command exit with `INVALID` and an actionable message.
With Symfony's usual `autoconfigure: true`, implementing the interfaces adds
these tags automatically. Declare them explicitly when autoconfiguration is
disabled.

Run a single profile and storage:

```bash
php bin/console ux:image:regenerate \
    --image-profile=product \
    --storage=product_images \
    --batch-size=100
```

`--after=<cursor>` resumes from an opaque provider cursor. On a processing or
persistence failure, the command exits with `FAILURE` and prints the last
durable cursor. `--dry-run` traverses every batch but neither generates files
nor persists metadata. `--force` regenerates assets whose profile revision is
already current.

An asset is skipped as current only when its profile revision matches and the
profile's expected variants are present. Deferred or asynchronous assets with
an empty source set are regenerated even if older persisted data accidentally
contains the current revision.

The provider must keep memory bounded, return no more than the requested limit,
use stable keyset ordering, and never derive identity from a filename. The
persister owns the compare-and-swap transaction for each returned asset.
Generated variants use immutable generation keys: a CAS conflict discards only
the unpublished generation, while a successful CAS makes the former generation
eligible for cleanup.
