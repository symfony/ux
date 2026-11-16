Live Components
===============

``ComponentWithUploadTrait`` lets a completed upload update a `Live Component`_
property without submitting a Symfony Form. The property stores a signed token;
``getUpload()`` resolves it lazily to ``UploadedFile``.

Install and configure `Live Component`_ before using this integration.

Configure the Uploader
----------------------

Complete :doc:`Installation <installation>` and configure the uploader used by
both the field and its Live target:

.. code-block:: yaml

    # config/packages/ux_upload.yaml
    ux_upload:
        uploaders:
            avatars:
                max_size: 2M
                allowed_types: [image/jpeg, image/png, image/webp]

Component
---------

The component builds its upload field from the current property. After a Live
render, the field therefore receives the completed upload again. Clearing the
property also clears the rendered field::

    // src/Twig/Components/ProfilePhoto.php
    namespace App\Twig\Components;

    use Symfony\Component\Form\FormFactoryInterface;
    use Symfony\Component\Form\FormView;
    use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
    use Symfony\UX\LiveComponent\Attribute\LiveProp;
    use Symfony\UX\LiveComponent\DefaultActionTrait;
    use Symfony\UX\Upload\Form\UploadType;
    use Symfony\UX\Upload\LiveComponent\Attribute\UploadTarget;
    use Symfony\UX\Upload\LiveComponent\ComponentWithUploadTrait;
    use Symfony\UX\Upload\Upload\UploadedFile;

    #[AsLiveComponent]
    final class ProfilePhoto
    {
        use ComponentWithUploadTrait;
        use DefaultActionTrait;

        #[LiveProp]
        #[UploadTarget(uploader: 'avatars')]
        public ?string $photo = null;

        public function __construct(private readonly FormFactoryInterface $formFactory)
        {
        }

        public function getPhotoUpload(): ?UploadedFile
        {
            return $this->getUpload('photo');
        }

        public function getUploadForm(): FormView
        {
            return $this->formFactory->createNamedBuilder('profile_photo')
                ->add('photo', UploadType::class, [
                    'uploader' => 'avatars',
                    'show_preview' => true,
                    'required' => false,
                    'data' => $this->getPhotoUpload(),
                ])
                ->getForm()
                ->createView();
        }
    }

Only nullable string ``#[LiveProp]`` properties carrying ``#[UploadTarget]`` may
be written by ``applyUpload()``. Invalid, expired, cross-owner or cross-tenant
tokens are ignored. The uploader name also rejects tokens from another uploader.

Template
--------

Render the field inside the bridge's element. This component does not submit a
form; the field transfers the file and the bridge applies its token:

.. code-block:: html+twig

    {# templates/components/ProfilePhoto.html.twig #}
    <div{{ attributes.defaults(
        stimulus_controller('symfony/ux-upload/live-upload', {property: 'photo'})
    ) }}>
        {{ form_row(this.uploadForm.photo) }}

        {% if this.photoUpload %}
            <p>{{ this.photoUpload.clientOriginalName }}</p>
            <button
                type="button"
                data-action="live#action"
                data-live-action-param="clearUpload"
                data-live-property-param="photo"
            >Remove</button>
        {% endif %}
    </div>

Render the component on a page:

.. code-block:: html+twig

    {# templates/profile/edit.html.twig #}
    <twig:ProfilePhoto />

The bridge listens for ``symfony--ux-upload--upload:complete``, extracts
``result.token`` and invokes the ``applyUpload`` Live Action. The target
property name is a controller value.

Symfony Forms bind upload tokens to their complete field path. A Live target
uses the explicit ``#[UploadTarget]`` property as its authorization boundary.
Signature, expiration, temporary path, owner, tenant and uploader are still
verified server-side.

Clear and Persist
-----------------

``clearUpload('photo')`` resolves the token, deletes the bundle-owned temporary
file and sets the property to ``null``. It does not delete application-owned
photos. Do not put the field inside ``data-live-ignore`` in this example: its
new form value is how the component restores or clears the upload UI.

To keep the photo permanently, resolve the property in your own Live Action and
pass the upload to your application service. Follow
:doc:`Persisting Uploaded Files <persistence>` for idempotent storage and
temporary-file retention.

``symfony/ux-live-component`` is an optional PHP integration dependency and
``@symfony/ux-live-component`` is an optional JavaScript peer dependency.
Applications not using this integration do not load it.

.. _`Live Component`: https://symfony.com/bundles/ux-live-component/current/index.html
