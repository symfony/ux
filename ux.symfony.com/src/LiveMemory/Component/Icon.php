<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\LiveMemory\Component;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\ExposeInTemplate;

use function Symfony\Component\String\u;

/**
 * @demo LiveMemory
 *
 * @author Simon André <smn.andre@gmail.com>
 */
#[AsTwigComponent(
    name: 'LiveMemory:Icon',
    template: 'demos/live_memory/components/LiveMemory/Icon.html.twig',
)]
class Icon
{
    /**
     * Name of the icon file without extension (ex: `symfony-ux`).
     */
    public string $name;

    /**
     * Accessibility label of the icon. Defaults to `name|title Icon` (ex: `Symfony Ux Icon`).
     */
    public ?string $label = null;

    protected string $iconDirectory;

    public function __construct(
        #[Autowire('%kernel.project_dir%')] string $projectDir,
    ) {
        $this->iconDirectory = $projectDir.'/assets/images/demos/live-memory/icons/';
    }

    public function mount(string $name): void
    {
        if (!preg_match('/[A-Za-z0-9-]+/', $name)) {
            throw new \InvalidArgumentException(\sprintf('Icon name can only contain letters, digits or dashes, "%s" provided.', $this->name));
        }

        $this->name = $name;
    }

    #[ExposeInTemplate]
    public function getLabel(): string
    {
        return $this->label ??= u($this->name)->title(true)->append(' Icon')->toString();
    }

    #[ExposeInTemplate]
    public function getRawSvg(): string
    {
        $path = $this->iconDirectory.'/'.$this->name.'.svg';
        if (!file_exists($path)) {
            throw new \InvalidArgumentException(\sprintf('File "%s" does not exist.', $path));
        }
        $svg = file_get_contents($path);

        $svg = u($svg)->collapseWhitespace();

        return $svg->toString();
    }
}
