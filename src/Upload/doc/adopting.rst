Replacing FileType
==================

``UploadType`` takes the place of a ``FileType`` field. Change the type and keep
the rest of the form, the controller and the validation constraints::

    // src/Form/DocumentType.php
    use Symfony\UX\Upload\Form\UploadType;

    // before: $builder->add('attachment', FileType::class);
    $builder->add('attachment', UploadType::class);

What Stays the Same
-------------------

The submitted value extends
``Symfony\Component\HttpFoundation\File\UploadedFile``. Code that type-hints the
HttpFoundation class receives it unchanged::

    // src/Controller/DocumentController.php
    use Symfony\Component\HttpFoundation\File\UploadedFile;

    /** @var UploadedFile|null $attachment */
    $attachment = $form->get('attachment')->getData();

    if (null !== $attachment) {
        $name = $attachment->getId().'.'.($attachment->guessExtension() ?? 'bin');
        $attachment->move($directory, $name);
    }

The same holds for the rest of the ``FileType`` contract:

- the field is required and mapped by default;
- a property typed ``?UploadedFile`` or ``?File`` receives the value;
- ``multiple: true`` returns a list of files, without a limit on their number;
- an empty submission returns ``null``, or an empty list with ``multiple``;
- the ``File`` constraint validates the value;
- a functional test selects a file with
  ``$form['document[attachment]']->upload($path)``.

Without JavaScript
------------------

When the Stimulus controller is not running, the browser submits the file with
the form, as it does for ``FileType``. The field sends that file through the
same uploader, with the same size and type rules, and returns the same value.

The background upload, its progress, pause and resume need JavaScript. Without
it, the limits of PHP apply and the file must be chosen again after a validation
error.

What Changes
------------

============================================  ===============================================  =================================================
Behavior                                      ``FileType``                                     ``UploadType``
============================================  ===============================================  =================================================
When the file is sent                         With the form                                    Before the form, in the background
After another field is invalid                The file must be chosen again                    The upload is kept; nothing is sent again
PHP upload limits                             Apply to the file                                Apply to direct multipart requests
``$request->files`` on final form submission  Contains the file                                Empty when JavaScript already uploaded the file
Where the bytes live                          A PHP temporary file, removed after the request  The bundle temporary storage, until cleanup
Anonymous visitors                            Accepted                                         Need an identified context or ``allow_anonymous``
============================================  ===============================================  =================================================

The application chooses the final filename. Deriving it from the upload ID
avoids collisions between two files with the same client filename and gives
repeated submissions a stable target. Keep the client filename as display
metadata, not as a storage key.

The direct transport is a multipart POST. Its file is subject to
``upload_max_filesize``, and the complete request to ``post_max_size``. The
internal direct endpoint receives the file in ``$request->files``. Chunked
requests do not send the whole file as a multipart POST, but proxy and server
limits still apply to each request. See :doc:`Production <production>`.

The second row is the reason to switch: with ``FileType``, a validation error on
any field discards the selected file.

Reading the File
----------------

``move()`` writes the file from the temporary storage straight to its target.
``getPathname()`` and ``getContent()`` first copy it to a local path. To send
the file to another storage, read the stream instead::

    use Symfony\UX\Upload\Upload\UploadedFile;

    /** @var UploadedFile $attachment */
    $stream = $attachment->openStream();

See :doc:`Persisting Uploaded Files <persistence>` for complete examples.

The temporary object stays in the bundle storage after ``move()``. The scheduled
cleanup removes it once it expires.

Limits
------

- ``getMimeType()`` and ``getClientMimeType()`` both return the type detected on
  the server. The type announced by the browser is not kept.
- Types from other bundles that extend or embed ``FileType`` keep using
  ``FileType``.
- Code that reads ``$request->files`` directly must read the form data instead.
