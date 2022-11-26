<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\Tests\Fixtures\Component;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Tests\Fixtures\Service\ServiceA;

#[AsTwigComponent('parent_form_component')]
final class ParentFormComponent
{
    public ?string $content = null;

    public ?string $content2 = null;
}
