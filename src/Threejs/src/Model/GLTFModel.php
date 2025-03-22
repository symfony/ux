<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Threejs\Model;


/**
 * @author Sylvain Blondeau <contact@sylvainblondeau.dev>
 *
 */
final class GLTFModel extends Model
{
   public const string TYPE = 'GLTF';

   public string $type = self::TYPE;
   
}