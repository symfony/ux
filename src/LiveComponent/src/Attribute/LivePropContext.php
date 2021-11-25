<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Attribute;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @experimental
 *
 * @internal
 */
final class LivePropContext
{
    private LiveProp $liveProp;
    private \ReflectionProperty $reflectionProperty;

    public function __construct(LiveProp $liveProp, \ReflectionProperty $reflectionProperty)
    {
        $this->liveProp = $liveProp;
        $this->reflectionProperty = $reflectionProperty;
    }

    public function liveProp(): LiveProp
    {
        return $this->liveProp;
    }

    public function reflectionProperty(): \ReflectionProperty
    {
        return $this->reflectionProperty;
    }
}
