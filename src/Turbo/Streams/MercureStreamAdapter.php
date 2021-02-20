<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Turbo\Streams;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 *
 * @experimental
 */
class MercureStreamAdapter implements StreamAdapterInterface
{
    public function getStimulusControllerName(): string
    {
        return 'symfony--ux-turbo--mercure';
    }

    public function createDataValues(array $options): array
    {
        if (!($options['hub'] ?? null)) {
            throw new \InvalidArgumentException('To use the Mercure Turbo Stream adapter, a Mercure hub URL must be provided. Try configuring a hub URL in config/packages/turbo.yaml.');
        }

        return [
            'hub' => $options['hub'],
            'topic' => $options['id'],
        ];
    }
}
