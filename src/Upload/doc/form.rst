Symfony Forms
=============

Use ``UploadType`` to upload files before a `Symfony Form`_ is submitted. The
field keeps completed uploads when another field fails validation, so users can
correct the form without uploading their files again.

The examples below build on :doc:`the quick start <index>`. For layouts, image
previews and styling, see :doc:`Customizing the Upload Field <customization>`.

Single File
-----------

These examples use the ``documents`` uploader from
:doc:`the quick start <index>`.

::

    // src/Form/DocumentType.php
    namespace App\Form;

    use Symfony\Component\Form\AbstractType;
    use Symfony\Component\Form\FormBuilderInterface;
    use Symfony\UX\Upload\Form\UploadType;

    final class DocumentType extends AbstractType
    {
        public function buildForm(FormBuilderInterface $builder, array $options): void
        {
            $builder->add('document', UploadType::class, [
                'uploader' => 'documents',
                'required' => true,
                'show_preview' => false,
            ]);
        }
    }

On submission, the form returns ``UploadedFile|null``::

    // src/Controller/DocumentController.php
    use Symfony\UX\Upload\Upload\UploadedFile;

    /** @var UploadedFile|null $upload */
    $upload = $form->get('document')->getData();

The browser uploads the file before submitting the form. A hidden field holds a
signed token that identifies the completed upload; Symfony resolves that token
to ``UploadedFile`` when handling the form. The field chooses a single request
or chunks automatically; see :doc:`Retry and Resume <resume>`.

Multiple Files
--------------

::

    $builder->add('attachments', UploadType::class, [
        'uploader' => 'documents',
        'multiple' => true,
        'max_files' => 5,
    ]);

The submitted value is ``list<UploadedFile>``.

Reading File Metadata
---------------------

The returned ``UploadedFile`` contains metadata verified when the upload
completed. These methods do not read the stored file::

    $upload->getId();
    $upload->getUploaderName();
    $upload->getStoragePath();
    $upload->getClientOriginalName();
    $upload->getMimeType();
    $upload->getSize();
    $upload->getCreatedAt();
    $upload->getExpiresAt();
    $upload->getChecksum();
    $upload->getChecksumAlgorithm();
    $upload->getOwnerId();
    $upload->getTenantId();
    $upload->getFieldName();
    $upload->isExpired();

``openStream()`` reads the stored bytes as a stream, without a local copy.
``delete()`` explicitly deletes the temporary object.

.. _ux-upload-storage-reads:

HttpFoundation Compatibility
----------------------------

The value extends ``Symfony\Component\HttpFoundation\File\UploadedFile``. Code
written for ``FileType`` keeps working::

    use Symfony\Component\HttpFoundation\File\UploadedFile;

    /** @var UploadedFile|null $upload */
    $upload = $form->get('document')->getData();
    $upload->getClientOriginalName();
    $upload->move($directory, $name);

``move()`` writes the stored bytes straight to its target. The other reads need
a local path: ``getPathname()``, ``getRealPath()``, ``getContent()``,
``openFile()`` or a cast to string copy the stored bytes to a local file the
first time. With a remote storage, that first call downloads the file. The local
file is removed with the value, unless ``move()`` took it.

``getMimeType()`` and ``getSize()`` return the values verified when the upload
completed. They do not read the file. ``getClientMimeType()`` returns the same
verified type.

The local copy is the whole file, written to the system temporary directory. For
a large file or a remote storage, prefer ``move()`` or ``openStream()``.

The ``File`` and ``Image`` constraints also request a local path. Adding one to
an upload field can therefore read the complete file from remote storage during
form validation. The no-read guarantee applies to token resolution and metadata
getters, not to the entire submission.

In the ``dev`` environment, the Symfony profiler inspects the submitted form
data. That inspection requests the path, so it triggers the local copy on every
valid submission.

Handling Validation Errors
--------------------------

When another form field is invalid, the signed upload value remains in the form.
Re-render the same form and submit again without retransferring bytes, provided
the completed upload and form token have not expired.

The form rejects malformed, tampered, expired, cross-uploader, oversized,
disallowed-MIME and cross-field references as transformation failures.

Collection entries use a canonical field path such as ``post.attachments.*``.
The wildcard covers only the dynamic collection index, so removing or reordering
another entry does not invalidate the upload. Tokens remain isolated from other
fields, collections, forms, owners and tenants.

Without JavaScript
------------------

The file input carries the field name and the token field is rendered
``disabled``. When the Stimulus controller connects, it removes the name of the
file input and enables the token field.

When the controller is not running, the browser submits the file itself and the
form is multipart. The field then stores that file through the uploader of the
field, with the same policy, and returns the same ``UploadedFile``. A refusal by
the uploader is reported as an error of the field, with the message the upload
widget would have shown.

This path follows the ``allow_anonymous`` setting: a file submitted by a visitor
with no owner and no tenant is refused unless the application allows anonymous
uploads.

Submission During an Upload
---------------------------

While a file is uploading or paused, the Stimulus controller marks the file
input as invalid with ``setCustomValidity()``. The browser refuses to submit the
form and shows the ``ux_upload.upload_in_progress`` message on the field. The
input becomes valid again when the upload completes, fails, or is cancelled.

A file that has not started yet (``auto_upload: false``) does not block the
form: it is submitted without that file. A form with the ``novalidate``
attribute skips browser validation, so it is not blocked either.

Repeated Submission
-------------------

While a form submission is in progress, the Stimulus controller marks the form
with ``aria-busy="true"`` and ignores another submit event. The guard is shared
by all upload fields in the form and is released when another listener cancels
the submission or the controller disconnects.

This is user-interface protection, not a server guarantee. Browser retries,
proxies and concurrent requests can still submit the same signed value. Keep
application persistence idempotent with ``UploadedFile::getId()``.

Saving Uploaded Files
---------------------

Keep titles, captions, permissions and other application data in your form or
entity. Save the file to permanent storage before recording its final location;
see :doc:`Persisting Uploaded Files <persistence>`.

For size, file-type and content checks, see :doc:`File Validation <validation>`.

Options
-------

===================  ===========  ===========  ================================================================
Option               Type         Default      Description
===================  ===========  ===========  ================================================================
``uploader``         ``string``   ``default``  Named server policy
``max_size``         ``?string``  ``null``     Inherit the uploader limit; an explicit value may only narrow it
``max_files``        ``?int``     ``null``     Maximum file count with ``multiple``; ``null`` means no limit
``allowed_types``    ``array``    ``[]``       MIME restrictions; cannot weaken uploader policy
``help_text``        ``?string``  generated    Help below the field
``auto_upload``      ``bool``     ``true``     Start after selection
``layout``           ``string``   ``compact``  Semantic layout: ``compact`` or ``dropzone``
``show_preview``     ``bool``     ``false``    Render image previews in either layout
``compression``      ``bool``     ``false``    Request compression for direct bodies and chunks
``multiple``         ``bool``     ``false``    Return a list instead of one value
``widget_attr``      ``array``    ``[]``       HTML attributes for the UX Upload container
``invalid_message``  ``string``   see below    Error shown when the submitted reference is invalid or expired
===================  ===========  ===========  ================================================================

``invalid_message`` defaults to
``The uploaded file reference is invalid or has expired.`` and behaves like any
form type ``invalid_message``: it is shown when the hidden value fails to
resolve, for example after ``form_token_ttl`` expiry, and is translated through
the ``validators`` domain.

Standard Symfony Form options such as ``label``, ``label_attr``, ``help``,
``help_attr`` and ``row_attr`` retain their normal behavior. ``attr`` applies to
the native file input. UX Upload reserves its transport attributes. Once
connected, the Stimulus controller submits the hidden token field in place of
that input. Use ``widget_attr`` for classes, data attributes or additional
Stimulus controllers on the UX Upload container.

Like ``FileType``, the type is required and mapped by default. Pass
``required: false`` for an optional file. It has no ``data_class``: the view
data is the signed token, not the file.

The bundle's global uploader limit defaults to 100 MiB. Set ``max_size: 0`` on
an uploader to remove that limit; a field whose ``max_size`` remains ``null``
inherits the unlimited policy. An explicit field value cannot weaken a finite
uploader limit.

In preview mode, image items use the preview element supplied by Twig.

In every layout, the file picker disappears once each allowed file is uploaded:
after the file of a single field, or after ``max_files`` files of a ``multiple``
field. It returns when an item is removed. Failed and cancelled items keep their
slot so users can retry or remove them explicitly, and the picker stays visible
until they do.

Errors have two presentation levels. A failure tied to one file appears in that
file's item. Field-level failures appear with the form field: Symfony
transformation and validation errors follow the active form theme, while browser
feedback with no identifiable file uses the field's client error area.

.. _`Symfony Form`: https://symfony.com/doc/current/forms.html
