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

use Twig\Environment;

/**
 * Renders the incoming entity using Twig before passing it to a broadcaster.
 *
 * @author Kévin Dunglas <kevin@dunglas.fr>
 */
final class TwigBroadcaster implements BroadcasterInterface
{
    private $broadcaster;
    private $twig;
    private $templatePrefixes;
    private $idAccessor;

    /**
     * @param array<string, string> $templatePrefixes
     */
    public function __construct(BroadcasterInterface $broadcaster, Environment $twig, array $templatePrefixes = [], IdAccessor $idAccessor = null)
    {
        $this->broadcaster = $broadcaster;
        $this->twig = $twig;
        $this->templatePrefixes = $templatePrefixes;
        $this->idAccessor = $idAccessor ?? new IdAccessor();
    }

    /**
     * {@inheritdoc}
     */
    public function broadcast(object $entity, string $action, array $options): void
    {
        if (!isset($options['id']) && null !== $id = $this->idAccessor->getEntityId($entity)) {
            $options['id'] = $id;
        }

        if (null === $template = $options['template'] ?? null) {
            $template = \get_class($entity);
            foreach ($this->templatePrefixes as $namespace => $prefix) {
                if (0 === strpos($template, $namespace)) {
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
