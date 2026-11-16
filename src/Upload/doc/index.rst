Symfony UX Upload
=================

**EXPERIMENTAL** This bundle is currently experimental and is likely to change,
possibly significantly, before its first stable release.

Symfony UX Upload adds background file uploads to Symfony Forms. Users can
select, drop or paste files, follow their progress, and correct validation
errors without selecting or uploading the same files again. It is part of
`the Symfony UX initiative`_.

Use ``UploadType`` in place of ``FileType``. The field returns an
``UploadedFile`` compatible with HttpFoundation; your application decides where
to save it permanently.

The field supports multiple files, image previews and resumable uploads. Files
are uploaded to temporary local or Flysystem storage before your application
saves them permanently. See
:doc:`Customizing the Upload Field <customization>` for layouts and styling, and
:doc:`Retry and Resume <resume>` for transfer behavior.

Quick Start
-----------

Complete :doc:`Installation <installation>` first, including the route import,
assets, request limits and cleanup. The example below assumes an authenticated
user. For public forms, configure a session-bound upload context as described in
:doc:`Security <security>`.

Configure a named uploader:

.. code-block:: yaml

    # config/packages/ux_upload.yaml
    ux_upload:
        uploaders:
            documents:
                max_size: 50M
                allowed_types: [application/pdf]

Add a document field and a title to your form::

    // src/Form/DocumentType.php
    namespace App\Form;

    use Symfony\Component\Form\AbstractType;
    use Symfony\Component\Form\Extension\Core\Type\TextType;
    use Symfony\Component\Form\FormBuilderInterface;
    use Symfony\Component\Validator\Constraints\NotBlank;
    use Symfony\UX\Upload\Form\UploadType;

    final class DocumentType extends AbstractType
    {
        public function buildForm(FormBuilderInterface $builder, array $options): void
        {
            $builder
                ->add('document', UploadType::class, [
                    'uploader' => 'documents',
                    'constraints' => [new NotBlank()],
                ])
                ->add('title', TextType::class, ['constraints' => [new NotBlank()]])
            ;
        }
    }

Handle the form and save the file under a unique name::

    // src/Controller/DocumentController.php
    namespace App\Controller;

    use App\Form\DocumentType;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
    use Symfony\Component\DependencyInjection\Attribute\Autowire;
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\Routing\Attribute\Route;
    use Symfony\UX\Upload\Upload\UploadedFile;

    final class DocumentController extends AbstractController
    {
        #[Route('/documents/new', name: 'document_new')]
        public function __invoke(
            Request $request,
            #[Autowire('%kernel.project_dir%/var/documents')]
            string $directory,
        ): Response {
            $form = $this->createForm(DocumentType::class);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                /** @var UploadedFile|null $upload */
                $upload = $form->get('document')->getData();
                if (null !== $upload) {
                    $name = $upload->getId().'.'.($upload->guessExtension() ?? 'bin');
                    $upload->move($directory, $name);
                }

                $this->addFlash('success', 'Document saved.');

                return $this->redirectToRoute('document_new');
            }

            return $this->render('document/new.html.twig', ['form' => $form]);
        }
    }

Render the form:

.. code-block:: html+twig

    {# templates/document/new.html.twig #}
    {% extends 'base.html.twig' %}

    {% block body %}
        {% for message in app.flashes('success') %}
            <p>{{ message }}</p>
        {% endfor %}

        {{ form_start(form) }}
            {{ form_row(form.document) }}
            {{ form_row(form.title) }}
            <button>Save document</button>
        {{ form_end(form) }}
    {% endblock %}

Select a PDF and wait for its upload to finish. Submit with an empty title: the
title fails validation, but the document remains attached. Correct the title and
submit again without selecting the file again.

The value extends HttpFoundation's ``UploadedFile``, so ``move()`` works as with
``FileType``. A name derived from the upload ID gives repeated submissions the
same target and avoids collisions between client filenames. Keep the original
name as display metadata, not as a storage key.

Saving the file does not remove the temporary upload. Let scheduled cleanup
remove it so a repeated form submission can still use it. For database records
or remote storage, see :doc:`Persisting Uploaded Files <persistence>`.

Documentation
-------------

.. toctree::
    :hidden:

    installation
    form
    adopting
    uploaders
    architecture
    lifecycle
    storage
    persistence
    javascript
    customization
    events
    validation
    security
    resume
    live-component
    production
    testing
    debugging
    configuration

Using Upload
~~~~~~~~~~~~

- :doc:`Installation <installation>`: install the bundle and configure assets,
  routes and upload identity.
- :doc:`Symfony Forms <form>`: upload one or several files and configure fields.
- :doc:`Replacing FileType <adopting>`: adapt an existing upload form.
- :doc:`Named Uploaders <uploaders>`: share size and file-type limits across fields.
- :doc:`Persisting Uploaded Files <persistence>`: save files and application data.
- :doc:`Customizing the Upload Field <customization>`: choose a layout, enable
  styles or customize the form theme.
- :doc:`Live Components <live-component>`: update a component after an upload.

Validation and Deployment
~~~~~~~~~~~~~~~~~~~~~~~~~

- :doc:`File Validation <validation>`: restrict uploads and inspect their contents.
- :doc:`Security <security>`: identify visitors and protect upload requests.
- :doc:`Retry and Resume <resume>`: handle interrupted transfers and form retries.
- :doc:`Temporary Storage <storage>`: configure local or Flysystem storage.
- :doc:`Production <production>`: configure request limits, cleanup and deployment.
- :doc:`Testing Upload Workflows <testing>`: test application code and integration.
- :doc:`Debugging <debugging>`: diagnose rejected requests and missing files.

Reference
~~~~~~~~~

- :doc:`Configuration <configuration>`: bundle options and their defaults.
- :doc:`JavaScript API <javascript>`: browser events and custom upload interfaces.
- :doc:`Server Events <events>`: react to upload progress and completion.
- :doc:`Upload Lifecycle <lifecycle>`: understand temporary files and expiration.
- :doc:`Architecture <architecture>`: understand the package's extension points.

.. _`the Symfony UX initiative`: https://ux.symfony.com/
