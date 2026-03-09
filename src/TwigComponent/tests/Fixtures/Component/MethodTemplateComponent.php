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
use Symfony\UX\TwigComponent\Attribute\FromMethod;

#[AsTwigComponent('method_template_component', template: new FromMethod('getDynamicTemplate'))]
class MethodTemplateComponent
{
    public string $type = 'default';
    
    public function getDynamicTemplate(): string
    {
        return sprintf('components/dynamic_%s.html.twig', $this->type);
    }
}