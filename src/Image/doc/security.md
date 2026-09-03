# Security

UX Image treats uploaded names and declared MIME types as untrusted metadata.
The stored extension is derived from the image signature and filenames are
generated with cryptographically secure randomness. Non-images are rejected
before a storage directory or object is created.

The built-in storages accept canonical relative paths only. Absolute paths,
backslashes, NUL bytes and `.` or `..` segments are rejected before any
filesystem operation.

SVG files are rejected by default, including apparently benign SVG files.
Allowing SVG safely requires an explicit application policy backed by a
maintained sanitizer or rasterizer. Do not serve arbitrary SVG uploads from
the application's origin.

Applications may replace `SvgPolicyInterface` with a service that rasterizes
or sanitizes the input. A policy must return a new raster `UploadedFile`; SVG
output from a custom policy is rejected before storage.

Input processing has finite defaults: 20 MB, 12,000 pixels on either axis and
40 megapixels. These checks happen from image headers before a full decode.
Drivers also fail when a requested codec is unavailable; UX Image never writes
another codec under the requested extension.

Store local uploads outside executable directories. If images are public,
configure the web server so uploaded files can never be executed as PHP and
send `X-Content-Type-Options: nosniff`.

## Configure budgets for the use case

```yaml
# config/packages/ux_image.yaml
ux_image:
    limits:
        max_input_bytes: 6000000
        max_width: 6000
        max_height: 6000
        max_megapixels: 24
        max_variants: 8
        max_output_megapixels: 48
```

These are server-side safety limits, independent of Form constraints or client
validation. Lower them for avatars and thumbnails. Raise them only after
measuring peak memory and processing time with production-like files.

The output budget covers all formats. Four variants in three formats produce
twelve encoded files and must be budgeted as such.

## Authorization and ownership

UX Image does not decide who may upload or replace an owner's image. Check
authentication, object-level authorization and tenant ownership before calling
`process()`. Persist the returned asset only against the authorized owner.

If the upload came from UX Upload or another staging transport, verify that the
current user owns the staged object before converting it to an `UploadedFile`.
Do not accept a storage path or `ImageAsset` JSON supplied directly by a client.

## Public and private originals

A public local root makes originals directly addressable unless the web server
denies them. Use a private local root, private object storage and an
application-defined signed URL adapter when originals contain sensitive data.

Cloudinary and Imgix builders do not create private provider sources or sign
delivery. A `base_url` is not an access-control boundary.

## Cache and replacement

Generated filenames are random, which supports immutable public caching.
Application replacement remains a two-system operation:

1. process and store the new generation;
2. persist the new `ImageAsset`;
3. delete the old files after persistence succeeds.

Do not delete the old generation before the database change is durable. For
remote object stores, make cleanup idempotent and retryable.

## Production checklist

- run `php bin/console ux:image:validate` during deployment;
- verify GD/Intervention can encode every configured format;
- keep the local storage root outside executable paths or disable script
  execution in that directory;
- configure `nosniff`, an appropriate Content Security Policy and immutable
  cache headers for generated files;
- test real CDN delivery, not only URL string generation;
- monitor rejection counts, processing latency, memory and cleanup failures;
- bound Messenger retries and make asynchronous handlers idempotent;
- back up persisted `ImageAsset` metadata together with its storage objects;
- exercise regeneration with `--dry-run` and a small batch before migrations.
