<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Upload\LiveComponent;

use Symfony\Contracts\Service\Attribute\Required;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\Upload\LiveComponent\Attribute\UploadTarget;
use Symfony\UX\Upload\Token\UploadTokenHandler;
use Symfony\UX\Upload\Upload\CompletedUpload;

/**
 * Bridges a client-side upload completion into a Live Component prop.
 *
 * The chunked upload happens entirely on the client (dropzone + Stimulus
 * controller). When it finishes, the JS controller dispatches a signed
 * upload token -- the same token FileUploadType already round-trips. The
 * companion Stimulus bridge (assets/src/live-upload.ts) picks that token up
 * and calls the applyUpload() live action below through the LiveComponent
 * JS Component API (getComponent(element).action(...)). The action resolves
 * the token server-side and stores it on a #[LiveProp], which triggers a
 * re-render -- no full form submission, no page reload.
 *
 * Only props marked #[UploadTarget] can be filled: the action receives the
 * target property name from the client, so the attribute is the allow-list
 * that stops a crafted request from writing any other property. The token
 * itself is signed, so a tampered, expired, cross-owner, cross-tenant or
 * wrong-uploader token resolves to null and is ignored.
 *
 * The prop stores the signed token (a compact, tamper-proof string that
 * survives hydration). Use getUpload() to resolve it back to a
 * CompletedUpload for rendering. Form field binding is intentionally replaced
 * here by the explicit #[UploadTarget] property allow-list.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
trait ComponentWithUploadTrait
{
    private UploadTokenHandler $uploadTokenHandler;

    /**
     * Per-class cache of property names carrying #[UploadTarget].
     *
     * @var array<class-string, array<string, UploadTarget>>
     */
    private static array $uploadTargets = [];

    #[Required]
    public function setUploadTokenHandler(UploadTokenHandler $uploadTokenHandler): void
    {
        $this->uploadTokenHandler = $uploadTokenHandler;
    }

    /**
     * Store a completed upload token onto the given #[UploadTarget] prop.
     *
     * Called by the Stimulus bridge when the client-side upload completes.
     */
    #[LiveAction]
    public function applyUpload(#[LiveArg] string $property, #[LiveArg] string $token): void
    {
        $target = $this->uploadTarget($property);

        if (null === $this->uploadTokenHandler->resolveForLiveTarget($token, $target->uploader)) {
            // Tampered, expired or otherwise invalid token: ignore silently.
            return;
        }

        new \ReflectionProperty($this, $property)->setValue($this, $token);
    }

    /**
     * Clear a previously applied upload from the given #[UploadTarget] prop.
     */
    #[LiveAction]
    public function clearUpload(#[LiveArg] string $property): void
    {
        $target = $this->uploadTarget($property);

        $reflection = new \ReflectionProperty($this, $property);
        $value = $reflection->isInitialized($this) ? $reflection->getValue($this) : null;
        if (\is_string($value) && null !== ($upload = $this->uploadTokenHandler->resolveForLiveTarget($value, $target->uploader))) {
            $upload->delete();
        }
        $reflection->setValue($this, null);
    }

    /**
     * Resolve the token held by an #[UploadTarget] prop into a CompletedUpload.
     *
     * Returns null when the prop is empty or holds an invalid/expired token.
     */
    protected function getUpload(string $property): ?CompletedUpload
    {
        $target = $this->uploadTarget($property);

        $reflection = new \ReflectionProperty($this, $property);
        $value = $reflection->isInitialized($this) ? $reflection->getValue($this) : null;

        return \is_string($value) ? $this->uploadTokenHandler->resolveForLiveTarget($value, $target->uploader) : null;
    }

    private function uploadTarget(string $property): UploadTarget
    {
        if (!isset(self::uploadTargets()[$property])) {
            throw new \InvalidArgumentException(\sprintf('Property "%s" is not an upload target on "%s". Add the #[%s] attribute to the #[LiveProp] that should receive uploads.', $property, static::class, UploadTarget::class));
        }

        return self::uploadTargets()[$property];
    }

    /**
     * @return array<string, UploadTarget>
     */
    private static function uploadTargets(): array
    {
        if (isset(self::$uploadTargets[static::class])) {
            return self::$uploadTargets[static::class];
        }

        $targets = [];
        foreach (new \ReflectionClass(static::class)->getProperties() as $property) {
            $attributes = $property->getAttributes(UploadTarget::class);
            if ([] === $attributes) {
                continue;
            }
            $type = $property->getType();
            if (!$type instanceof \ReflectionNamedType || 'string' !== $type->getName() || !$type->allowsNull()) {
                throw new \LogicException(\sprintf('Upload target property "%s::$%s" must have the "?string" type.', static::class, $property->getName()));
            }
            if ([] === $property->getAttributes(LiveProp::class)) {
                throw new \LogicException(\sprintf('Upload target property "%s::$%s" must also carry the #[%s] attribute.', static::class, $property->getName(), LiveProp::class));
            }
            $targets[$property->getName()] = $attributes[0]->newInstance();
        }

        return self::$uploadTargets[static::class] = $targets;
    }
}
