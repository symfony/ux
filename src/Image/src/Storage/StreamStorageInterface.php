<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Storage;

use Symfony\UX\Image\Exception\ExceptionInterface;

interface StreamStorageInterface extends StorageInterface
{
    /**
     * @return resource
     *
     * @throws ExceptionInterface
     */
    public function readStream(string $storageName, StoragePath $path);

    /**
     * @param resource $stream
     *
     * @throws ExceptionInterface
     */
    public function writeStream(string $storageName, StoragePath $path, $stream): void;

    /**
     * @throws ExceptionInterface
     */
    public function deletePath(string $storageName, StoragePath $path): void;
}
