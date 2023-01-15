<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DataTable\Core\Twig;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\UX\DataTable\Core\RendererInterface;
use Symfony\UX\DataTable\Core\TableInterface;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @author Mathéo Daninos <mathéo.daninos@gmail.com>
 */
class DataTableExtension extends AbstractExtension
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('render_table', [$this, 'renderTable'], ['needs_environment' => true, 'is_safe' => ['html']]),
        ];
    }

    public function renderTable(Environment $environment, TableInterface $table): string
    {
        $renderer = $this->container->get($table->getRenderer());

        if (!$renderer instanceof RendererInterface) {
            throw new \Exception(sprintf('no Renderer service found with alias : %s', $table->getRenderer()));
        }

        return $renderer->render($environment, $table);
    }

    public function test(): string
    {
        return 'test';
    }
}
