<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Translator\Tests\Fixtures;

use Symfony\Component\Translation\Loader\LoaderInterface;
use Symfony\Component\Translation\MessageCatalogue;

final class EntityTranslationsLoader implements LoaderInterface
{
    public function __construct(
        private EntityMetadataRegistry $registry,
    ) {
    }

    public function load(mixed $resource, string $locale, string $domain = 'messages'): MessageCatalogue
    {
        $this->registry->load();

        return new MessageCatalogue($locale, [$domain => ['entity.greeting' => 'Hello']]);
    }
}
