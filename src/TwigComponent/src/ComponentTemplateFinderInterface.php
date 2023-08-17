<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent;

/**
 * @author Matheo Daninos <matheo.daninos@gmail.com>
 */
interface ComponentTemplateFinderInterface
{
    public function findAnonymousComponentTemplate(string $name): ?string;
}
