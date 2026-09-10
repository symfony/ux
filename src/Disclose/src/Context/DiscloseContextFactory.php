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

/**
 * Builds a disclose context, from scratch or out of an object at render time.
 *
 * Context providers are data source agnostic: they resolve an object into a
 * context, so the factory never depends on a specific persistence layer.
 *
 * @author Jérôme Tamarelle <jerome@tamarelle.net>
 *
 * @internal
 */
final class DiscloseContextFactory
{
    /**
     * @param iterable<ContextProviderInterface> $providers
     */
    public function __construct(private readonly iterable $providers = []) {}

    public function create(string $class, string|int|array $id, ?string $field = null, array $extra = []): DiscloseContext
    {
        return DiscloseContext::create($class, $id, $field, $extra);
    }

    public function createFromObject(object $subject, ?string $field = null, array $extra = []): DiscloseContext
    {
        foreach ($this->providers as $provider) {
            if (null !== $context = $provider->create($subject, $field, $extra)) {
                return $context;
            }
        }

        throw new \LogicException(\sprintf('Cannot build a disclose context from the object "%s". Register a disclose context provider or pass an explicit disclose context.', $subject::class));
    }
}
