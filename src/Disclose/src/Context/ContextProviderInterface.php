<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Disclose\Context;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Turns an object into a disclose context.
 *
 * Used when the component receives a subject object (an entity, an ODM
 * document, or any value a provider understands) instead of an explicit
 * context. Providers run in order: the first one that recognizes the subject
 * wins. The contract is data source agnostic.
 *
 * @author Jérôme Tamarelle <jerome@tamarelle.net>
 */
#[AutoconfigureTag('ux.disclose.context_provider')]
interface ContextProviderInterface
{
    /**
     * Builds the disclose context for the subject, or returns null when this
     * provider does not handle it.
     */
    public function create(object $subject, ?string $field = null, array $extra = []): ?DiscloseContext;
}
