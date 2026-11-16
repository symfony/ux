Debugging
=========

Start with the failed request in your browser's Network panel, then check the
route, uploader configuration and upload context on the server. The sections
below cover common failures.

Inspect Routes and Configuration
--------------------------------

.. code-block:: terminal

    $ php bin/console debug:router ux_upload_init
    $ php bin/console debug:router ux_upload_remove
    $ php bin/console debug:config ux_upload
    $ php bin/console debug:container Symfony\\UX\\Upload\\Storage\\StorageInterface

Invalid or Expired Form Value
-----------------------------

Check:

- ``form_token_ttl`` and ``completed_ttl``;
- stable ``APP_SECRET``;
- owner, tenant and full form-field context;
- that the token was created by the field's named uploader;
- that field ``max_size`` and ``allowed_types`` still accept the metadata.

Token resolution intentionally does not query storage. If transformation
succeeds but ``openStream()`` fails, inspect explicit browser removal, cleanup,
provider lifecycle rules and the configured storage service.

Remove Fails
------------

The default route is ``DELETE /_ux/upload/remove``. Verify:

- the request sends the completed token;
- cookie credentials or authorization headers are present;
- CSRF header is valid when CSRF support is installed;
- the current upload context matches the signed context;
- custom proxy/CORS rules permit ``DELETE``.

Flysystem
---------

Set ``flysystem_service`` to the exact service ID:

.. code-block:: terminal

    $ php bin/console debug:container uploads.storage

The bundle never selects a filesystem by type. Ensure its root permits reads,
writes, listing and deletion below ``completed_prefix``.

Cleanup
-------

.. code-block:: terminal

    $ php bin/console ux:upload:cleanup --age=24h -vv

Completed cleanup parses expiration from generated basenames. Pending cleanup
uses session age. Inspect both ``temp_dir`` and ``completed_prefix``.

Browser
-------

Use the Network panel to inspect initialization, chunk ``PUT`` requests,
completion ``POST``, resume requests and remove ``DELETE``. Token resolution
does not read the storage backend. A ``File`` or ``Image`` constraint, the
profiler or application code may still load the bytes on the server during
submission; see :ref:`ux-upload-storage-reads`.
