Symfony Forms
=============

``FileUploadType`` turns a `Symfony Forms`_ file field into a persistent upload field. The Stimulus controller transfers bytes as soon as the user selects them, then a hidden field carries the signed reference through submission, validation errors and form re-renders.

The default form theme renders an accessible upload field, per-file and batch
progress, retry and removal controls, multiple-file support and an optional
preview gallery. It accepts file browsing, keyboard activation, drag-and-drop
and clipboard paste without application JavaScript.

Single File
-----------

::

    // src/Form/DocumentType.php
    use Symfony\Component\Form\AbstractType;
    use Symfony\Component\Form\FormBuilderInterface;
    use Symfony\UX\Upload\Form\FileUploadType;

    final class DocumentType extends AbstractType
    {
        public function buildForm(FormBuilderInterface $builder, array $options): void
        {
            $builder->add('document', FileUploadType::class, [
                'uploader' => 'documents',
                'required' => true,
                'show_preview' => false,
            ]);
        }
    }

The submitted value is ``CompletedUpload|null``:

::

    // src/Controller/DocumentController.php
    use Symfony\UX\Upload\Upload\CompletedUpload;

    /** @var CompletedUpload|null $upload */
    $upload = $form->get('document')->getData();

No transport option is required on the field. The generated widget uploads a file in one request when its size is at or below the selected uploader's ``chunk_size``, and uses the resumable chunk protocol above that threshold.

Multiple Files
--------------

::

    $builder->add('attachments', FileUploadType::class, [
        'uploader' => 'attachments',
        'multiple' => true,
        'max_files' => 5,
    ]);

The submitted value is ``list<CompletedUpload>``.

Options
-------

===================  ===========  ===========  ================================================================
Option               Type         Default      Description
===================  ===========  ===========  ================================================================
``uploader``         ``string``   ``default``  Named server policy
``max_size``         ``?string``  ``null``     Inherit the uploader limit; an explicit value may only narrow it
``max_files``        ``int``      ``1``        Maximum number of submitted references
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

``invalid_message`` defaults to ``The uploaded file reference is invalid or has expired.`` and behaves like any form type ``invalid_message``: it is shown when the hidden value fails to resolve, for example after ``form_token_ttl`` expiry, and is translated through the ``validators`` domain.

Standard Symfony Form options such as ``label``, ``label_attr``, ``help``,
``help_attr`` and ``row_attr`` retain their normal behavior. ``attr`` applies
to the native file input. UX Upload reserves its transport attributes and keeps
that input unnamed; only the hidden token field is submitted. Use
``widget_attr`` for classes, data attributes or additional Stimulus controllers
on the UX Upload container.

The type is unmapped by default and has no ``data_class``. Read it explicitly from the form, or add application mapping in your own form/data mapper.

The Bundle's global uploader limit defaults to 100 MiB. Set ``max_size: 0`` on an uploader to remove that limit; a field whose ``max_size`` remains ``null`` inherits the unlimited policy. An explicit field value cannot weaken a finite uploader limit.

In preview mode, image items use the preview element supplied by Twig. The file
picker disappears when ``max_files`` items are present and returns after an item
is removed. Failed and cancelled items keep their slot so users can retry or
remove them explicitly.

Errors have two presentation levels. A failure tied to one file appears in that
file's item. Field-level failures appear with the form field: Symfony
transformation and validation errors follow the active form theme, while
browser feedback with no identifiable file uses the field's client error area.

Lazy Metadata
-------------

These calls do not access storage:

::

    $upload->getId();
    $upload->getUploaderName();
    $upload->getTemporaryPath();
    $upload->getOriginalName();
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

``openStream()`` performs the explicit read. ``delete()`` explicitly deletes the temporary object.

Invalid Resubmission
--------------------

When another form field is invalid, the signed upload value remains in the form.
Re-render the same form and submit again without retransferring bytes, provided
the completed upload and form token have not expired.

The same valid form may also arrive twice. Persist by an application-owned idempotency key derived from ``CompletedUpload::getId()``, and do not delete the temporary object in the request. A first call that deletes it can make the second call fail at ``openStream()``.

The form rejects malformed, tampered, expired, cross-uploader, oversized,
disallowed-MIME and cross-field references as transformation failures.

Collection entries use a canonical field path such as
``post.attachments.*``. The wildcard covers only the dynamic collection index,
so removing or reordering another entry does not invalidate the upload. Tokens
remain isolated from other fields, collections, forms, owners and tenants.

Repeated Submission
-------------------

While a form submission is in progress, the Stimulus controller marks the form with ``aria-busy="true"`` and ignores another submit event. The guard is shared by all upload fields in the form and is released when another listener cancels the submission or the controller disconnects.

This is user-interface protection, not a server guarantee. Browser retries, proxies and concurrent requests can still submit the same signed value. Keep application persistence idempotent with ``CompletedUpload::getId()``.

Bundle and Business Metadata
----------------------------

Bundle metadata describes transport and temporary storage: upload ID, uploader,
temporary path, original name, detected MIME type, size, timestamps, checksum
and security context.

Business metadata belongs to your form/entity: title, caption, category,
visibility, owner permissions, final storage key and retention rules. Do not add
those fields to the upload token or treat the temporary path as business state.

See :doc:`File Validation <validation>` for transport and business-validation boundaries, :doc:`Customizing the Upload Field <customizing-upload-field>` for Twig and CSS, and :doc:`Persisting Uploaded Files <persisting-uploaded-files>` for final storage.

.. _`Symfony Forms`: https://symfony.com/doc/current/forms.html
