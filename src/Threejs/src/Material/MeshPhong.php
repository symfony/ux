<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Threejs\Material;

/**
 * @author Sylvain Blondeau <contact@sylvainblondeau.dev>
 *
 */
final class MeshPhong extends Material
{
    public const string TYPE = 'MeshPhong';
    
    public string $type = self::TYPE;
    
}
