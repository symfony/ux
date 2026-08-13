<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Upload\LiveComponent\Attribute;

/**
 * Marks a #[LiveProp] as a destination for completed uploads.
 *
 * A property carrying this attribute may be filled by the trait's
 * applyUpload() live action from a client-side upload completion. Only
 * properties explicitly marked here can be written this way: the action
 * receives the target property name from the (untrusted) client, so the
 * attribute is the allow-list that prevents writing arbitrary properties.
 *
 * Set $uploader to accept only tokens produced by that named uploader.
 * The property must also be a nullable string #[LiveProp].
 *
 * @see \Symfony\UX\Upload\LiveComponent\ComponentWithUploadTrait
 *
 * @author Simon André <smn.andre@gmail.com>
 */
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final readonly class UploadTarget
{
    public function __construct(public ?string $uploader = null)
    {
        if ('' === $this->uploader) {
            throw new \InvalidArgumentException('The upload target uploader cannot be empty.');
        }
    }
}
