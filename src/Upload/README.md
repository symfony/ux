# Symfony UX Upload

**EXPERIMENTAL** This bundle is currently experimental and is likely to change,
possibly significantly, before its first stable release.

**This repository is a READ-ONLY sub-tree split**. See
https://github.com/symfony/ux to create issues or submit pull requests.

UX Upload turns a Symfony file field into a complete upload field whose value
survives form validation and re-rendering. It provides an accessible
drag-and-drop interface with progress, multiple files, paste, retry and removal,
plus an optional preview gallery. Uploaded files survive validation errors and
form re-renders without being selected or transferred again.

By default, files are transferred as soon as they are selected. Small files use
one request; larger files use a resumable chunk protocol with pause and resume.
UX Upload keeps the result in temporary storage and `FileUploadType` returns a
lazy `CompletedUpload`; the application decides if and where to persist it
permanently.

Here, a larger file is one above `chunk_size` but still within the configured
application limit, which defaults to 100 MiB. UX Upload is not designed for
multi-gigabyte browser-to-cloud ingestion. Use a provider-native direct or
multipart upload protocol for that workload.

```text
select file
    -> small file: one request
    -> large file: resumable chunks
    -> temporary file
    -> signed form value
    -> invalid form: re-render and submit again without transfer
    -> valid form: CompletedUpload then application storage
```

## Upload Experience

The default form theme provides:

- mouse, keyboard, drag-and-drop and clipboard file selection;
- per-file and batch progress, transfer speed and estimated time for direct and chunked transfers;
- compact and dropzone layouts, multiple files, validation feedback and optional previews;
- pause and resume for chunked uploads;
- retry, cancellation and completed-file removal;
- translated labels and live announcements for assistive technology.

The form theme is one composable contract made of ten blocks: row, widget,
picker, item, visual, progress, actions, summary, client errors and start.
Applications normally override the related blocks they need in one custom form
theme. Standard Symfony Form options render the label, help, validation errors,
row and native file input attributes; `widget_attr` customizes the UX Upload
container. Two optional Baseline 2026 stylesheets provide compact and dropzone
treatments; neither is loaded automatically.

## Installation

```bash
composer require symfony/ux-upload
```

This initial release does not include a Symfony Flex recipe. Register the bundle
and its routes as described in [Installation](doc/installation.rst). The Bundle
adds its default form theme when TwigBundle is installed, and the default local
storage keeps temporary files outside `public/`.

## First Upload

Define a named uploader:

```yaml
# config/packages/ux_upload.yaml
ux_upload:
    compression: false

    uploaders:
        documents:
            max_size: 50M
            allowed_types: [application/pdf]
            chunk_size: 5M
            parallel_chunks: 3
            completed_ttl: 86400
```

Add the field to a normal Symfony Form:

```php
// src/Form/DocumentType.php
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\UX\Upload\Form\FileUploadType;

final class DocumentType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('document', FileUploadType::class, [
            'uploader' => 'documents',
        ]);
    }
}
```

Render and submit it like any other form:

```twig
{# templates/document/new.html.twig #}
{{ form_start(form) }}
    {{ form_row(form.document) }}
    <button type="submit">Create document</button>
{{ form_end(form) }}
```

Copy the completed bytes into storage owned by your application:

```php
// src/Controller/DocumentController.php
use Symfony\UX\Upload\Upload\CompletedUpload;

$form->handleRequest($request);

if ($form->isSubmitted() && $form->isValid()) {
    /** @var CompletedUpload $upload */
    $upload = $form->get('document')->getData();

    $stream = $upload->openStream();
    try {
        $document = $documentStorage->storeOnce(
            uploadId: $upload->getId(),
            stream: $stream,
            originalName: $upload->getOriginalName(),
            mimeType: $upload->getMimeType(),
        );
    } finally {
        fclose($stream);
    }

    // Persist $document with your normal application transaction.
}
```

`$documentStorage` and `storeOnce()` are application code. `storeOnce()` must
use an application-owned uniqueness rule and atomically return the existing
document when the upload ID was already stored. The service may write to a
local directory, S3, a
database-backed media library or another provider. UX Upload does not require
an application storage interface and does not move temporary files into a
final destination.

Reading metadata such as name, MIME type, size, checksum or expiration performs
no storage access. `openStream()` is the explicit operation that reads the
temporary object. Make application persistence idempotent, normally using the
upload ID as an input to an application-owned uniqueness rule. Do not call
`delete()` in the form request: a repeated request may still need the stream.
Schedule `ux:upload:cleanup` to remove temporary copies after expiration.

## Ownership Boundary

UX Upload owns:

- browser transfer, chunks, retry and resume;
- automatic one-request transfer for files up to `chunk_size`;
- assembly under `completed_prefix`;
- signed metadata and security context;
- transport validation, integrity checks and temporary expiration;
- explicit browser removal and scheduled cleanup.

Your application owns:

- business metadata such as document title, category and permissions;
- the final storage key and storage provider;
- database transactions and application retention;
- delivery and deletion of the final file.

Do not persist `CompletedUpload::getTemporaryPath()` as a permanent application
file reference. The path is inside the Bundle's cleanup scope.

## Documentation

- [Start here](doc/index.rst)
- [Symfony Forms](doc/form.rst)
- [Upload lifecycle](doc/upload-lifecycle.rst)
- [Temporary storage](doc/storage.rst)
- [Persisting uploaded files](doc/persisting-uploaded-files.rst)
- [File validation](doc/validation.rst)
- [Configuration](doc/configuration.rst)

Requirements: PHP 8.4+ and Symfony 7.4 or 8.0. The bundled browser adapter uses
StimulusBundle 3.0+. LiveComponent, Flysystem, CSRF and the cleanup command are
optional integrations.

## License

Symfony UX Upload is available under the [MIT License](LICENSE).
