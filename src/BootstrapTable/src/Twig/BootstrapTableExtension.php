<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\BootstrapTable\Twig;

use Symfony\UX\BootstrapTable\Model\Table;
use Symfony\WebpackEncoreBundle\Dto\StimulusControllersDto;
use Symfony\WebpackEncoreBundle\Twig\StimulusTwigExtension;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @author Mathéo Daninos <mathéo.daninos@gmail.com>
 *
 */
class BootstrapTableExtension extends AbstractExtension
{
    private $stimulus;

    public function __construct(StimulusTwigExtension $stimulus)
    {
        $this->stimulus = $stimulus;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('render_table', [$this, 'renderTable'], ['needs_environment' => true, 'is_safe' => ['html']])
        ];
    }

    public function renderTable(Environment $environment, Table $table): string
    {
        $dto = new StimulusControllersDto($environment);
        $dto->addController('symfony/bootstrap-table/table', $table->renderView());

        return '<table ' . $dto . '></table>';
    }
}
