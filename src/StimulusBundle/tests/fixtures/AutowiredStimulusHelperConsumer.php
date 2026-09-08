<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\StimulusBundle\Tests\fixtures;

use Symfony\UX\StimulusBundle\Helper\StimulusHelper;

/**
 * Stands in for the form type the documentation tells you to autowire the helper into.
 *
 * @internal
 */
final class AutowiredStimulusHelperConsumer
{
    public function __construct(public StimulusHelper $stimulusHelper)
    {
    }
}
