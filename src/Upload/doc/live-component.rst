Live Components
===============

``ComponentWithUploadTrait`` lets a completed upload update a `Live Component`_ property without submitting a Symfony Form.

The property stores the signed token, not file bytes or a storage path. ``getUpload()`` resolves it lazily to ``CompletedUpload``.

Component
---------

::

    // src/Twig/Components/ProfilePhoto.php
    namespace App\Twig\Components;

    use App\Storage\PhotoStorage;
    use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
    use Symfony\UX\LiveComponent\Attribute\LiveAction;
    use Symfony\UX\LiveComponent\Attribute\LiveProp;
    use Symfony\UX\LiveComponent\DefaultActionTrait;
    use Symfony\UX\Upload\LiveComponent\Attribute\UploadTarget;
    use Symfony\UX\Upload\LiveComponent\ComponentWithUploadTrait;
    use Symfony\UX\Upload\Upload\CompletedUpload;

    #[AsLiveComponent]
    final class ProfilePhoto
    {
        use ComponentWithUploadTrait;
        use DefaultActionTrait;

        public function __construct(private PhotoStorage $photos)
        {
        }

        #[LiveProp]
        #[UploadTarget(uploader: 'avatar')]
        public ?string $photo = null;

        public function getPhotoUpload(): ?CompletedUpload
        {
            return $this->getUpload('photo');
        }

        #[LiveAction]
        public function save(): void
        {
            $upload = $this->getUpload('photo');
            if (null === $upload) {
                return;
            }

            if (!$this->photos->hasUpload($upload->getId())) {
                $stream = $upload->openStream();
                try {
                    $this->photos->storeOnce(
                        $upload->getId(),
                        $stream,
                        $upload->getOriginalName(),
                    );
                } finally {
                    fclose($stream);
                }
            }
        }
    }

Only nullable string ``#[LiveProp]`` properties carrying ``#[UploadTarget]`` may be written by ``applyUpload()``. Invalid, expired, cross-owner or cross-tenant tokens are ignored. Setting ``uploader`` is recommended when the component expects one named uploader; tokens produced by another uploader are then rejected.

``PhotoStorage`` is application code. Its ``storeOnce()`` operation must enforce the upload ID as an idempotency key for repeated Live Actions.

Symfony Forms bind upload tokens to their complete field path. A Live target is a separate authorization boundary: the explicit ``#[UploadTarget]`` property replaces that field binding so the same completed token can leave the form widget and enter the Live Component. Signature, expiration, temporary path, owner, tenant and optional uploader binding are still verified server-side.

Template
--------

.. code-block:: html+twig

    {# templates/components/ProfilePhoto.html.twig #}
    <div{{ attributes.defaults(
        stimulus_controller('symfony/ux-upload/live-upload', {property: 'photo'})
    ) }}>
        {# Render any FileUploadType widget inside this element, for example
           from a page-level form view: {{ form_widget(form.photo) }}.
           When its transfer completes, the bridge below applies the signed
           token to the "photo" prop; the form is never submitted. #}

        {% if this.photoUpload %}
            <p>
                {{ this.photoUpload.originalName }}
                ({{ this.photoUpload.size }} bytes)
            </p>

            <button
                type="button"
                data-action="live#action"
                data-live-action-param="clearUpload"
                data-live-property-param="photo"
            >Remove</button>
        {% endif %}
    </div>

The bridge listens for ``symfony--ux-upload--upload:complete``, extracts ``result.token``, and invokes the ``applyUpload`` Live Action. The target property name is a controller value.

Clear
-----

``clearUpload('photo')`` resolves the token, calls ``CompletedUpload::delete()`` and sets the property to ``null``. It deletes only the Bundle-owned temporary copy. Deletion of an application-owned photo remains application code.

``symfony/ux-live-component`` is an optional PHP integration dependency and ``@symfony/ux-live-component`` is an optional JavaScript peer dependency. Applications not using this integration do not load it.

.. _`Live Component`: https://symfony.com/bundles/ux-live-component/current/index.html
