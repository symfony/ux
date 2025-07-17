<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Turbo\Broadcaster;

use Symfony\UX\Turbo\Doctrine\ClassUtil;
use Twig\Environment;

/**
 * Renders the incoming entity using Twig before passing it to a broadcaster.
 *
 * @author Kévin Dunglas <kevin@dunglas.fr>
 */
final class TwigBroadcaster implements BroadcasterInterface
{
    private IdAccessor $idAccessor;

    /**
     * @param array<string, string> $templatePrefixes
     */
    public function __construct(
        private BroadcasterInterface $broadcaster,
        private Environment $twig,
        private array $templatePrefixes = [],
        ?IdAccessor $idAccessor = null,
    ) {
        $this->idAccessor = $idAccessor ?? new IdAccessor();
    }

    public function broadcast(object $entity, string $action, array $options): void
    {
        if (!isset($options['id']) && null !== $id = $this->idAccessor->getEntityId($entity)) {
            $options['id'] = $id;
        }

        $class = ClassUtil::getEntityClass($entity);

        if (null === $template = $options['template'] ?? null) {
            $template = $class;
            foreach ($this->templatePrefixes as $namespace => $prefix) {
                if (str_starts_with($template, $namespace)) {
                    $template = substr_replace($template, $prefix, 0, \strlen($namespace));
                    break;
                }
            }

            $template = str_replace('\\', '/', $template).'.stream.html.twig';
        }

        // Will throw if the template or the block doesn't exist
        $options['rendered_action'] = $this->twig
            ->load($template)
            ->renderBlock($action, [
                'entity' => $entity,
                'action' => $action,
                'id' => implode('-', (array) ($options['id'] ?? [])),
            ] + $options);

        $this->broadcaster->broadcast($entity, $action, $options);
    }
}
