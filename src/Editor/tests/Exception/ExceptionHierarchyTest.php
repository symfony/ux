<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Editor\Tests\Exception;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Editor\Exception\BridgeConfigMismatchException;
use Symfony\UX\Editor\Exception\ContentSchemaException;
use Symfony\UX\Editor\Exception\EditorExceptionInterface;
use Symfony\UX\Editor\Exception\IncompatibleConfigException;
use Symfony\UX\Editor\Exception\UnknownBridgeException;
use Symfony\UX\Editor\Exception\UnsupportedConversionException;
use Symfony\UX\Editor\Exception\Upload\InvalidSignatureException;
use Symfony\UX\Editor\Exception\Upload\UnsupportedFileException;
use Symfony\UX\Editor\Exception\Upload\UploadException;
use Symfony\UX\Editor\Exception\Upload\UploadHandlerException;

final class ExceptionHierarchyTest extends TestCase
{
    #[DataProvider('exceptionClasses')]
    public function testAllExceptionsImplementMarker(string $class)
    {
        $e = new $class('msg');
        self::assertInstanceOf(EditorExceptionInterface::class, $e);
        self::assertInstanceOf(\Throwable::class, $e);
    }

    public static function exceptionClasses(): array
    {
        return [
            [UnknownBridgeException::class],
            [BridgeConfigMismatchException::class],
            [IncompatibleConfigException::class],
            [UnsupportedConversionException::class],
            [ContentSchemaException::class],
            [UploadException::class],
            [InvalidSignatureException::class],
            [UnsupportedFileException::class],
            [UploadHandlerException::class],
        ];
    }

    public function testUploadExceptionsExtendUploadException()
    {
        self::assertInstanceOf(UploadException::class, new InvalidSignatureException('x'));
        self::assertInstanceOf(UploadException::class, new UnsupportedFileException('x'));
        self::assertInstanceOf(UploadException::class, new UploadHandlerException('x'));
    }
}
