<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\Attribute;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @experimental
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
class AsTwigComponent
{
    public string $name;
    public ?string $template;

    public function __construct(string $name, ?string $template = null)
    {
        $this->name = $name;
        $this->template = $template;
    }
}
