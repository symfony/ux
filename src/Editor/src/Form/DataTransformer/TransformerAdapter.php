<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\UX\Editor\Content\EditorContentInterface;

/**
 * @internal
 */
final class TransformerAdapter implements DataTransformerInterface
{
    public function __construct(private readonly EditorContentTransformerInterface $inner)
    {
    }

    public function transform(mixed $value): mixed
    {
        if (null === $value) {
            return '';
        }
        if (!$value instanceof EditorContentInterface) {
            throw new TransformationFailedException('Expected EditorContentInterface, got '.get_debug_type($value));
        }
        $raw = $this->inner->transform($value);

        return \is_string($raw) ? $raw : json_encode($raw, \JSON_THROW_ON_ERROR);
    }

    public function reverseTransform(mixed $value): mixed
    {
        if (null === $value || '' === $value) {
            return null;
        }
        if (StorageShape::Scalar !== $this->inner->getStorageShape() && \is_string($value)) {
            try {
                $value = json_decode($value, true, 512, \JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                throw new TransformationFailedException('Invalid JSON for editor content', 0, $e);
            }
        }

        return $this->inner->reverseTransform($value);
    }
}
