<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Upload\Tests\Exception;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Upload\Exception\ExceptionInterface;
use Symfony\UX\Upload\Exception\InvalidArgumentException as UploadInvalidArgumentException;
use Symfony\UX\Upload\Exception\RuntimeException as UploadRuntimeException;
use Symfony\UX\Upload\Exception\StorageException;
use Symfony\UX\Upload\Exception\UploadException;
use Symfony\UX\Upload\Exception\UploadSessionNotFoundException;
use Symfony\UX\Upload\Exception\ValidationException;

final class ExceptionHierarchyTest extends TestCase
{
    #[Test]
    public function invalidArgumentExceptionImplementsExceptionInterface(): void
    {
        $exception = new UploadInvalidArgumentException('test');

        $this->assertInstanceOf(ExceptionInterface::class, $exception);
        $this->assertInstanceOf(\InvalidArgumentException::class, $exception);
    }

    #[Test]
    public function runtimeExceptionIsAbstract(): void
    {
        $reflection = new \ReflectionClass(UploadRuntimeException::class);

        $this->assertTrue($reflection->isAbstract());
        $this->assertTrue($reflection->implementsInterface(ExceptionInterface::class));
    }

    #[Test]
    public function validationExceptionImplementsExceptionInterface(): void
    {
        $exception = new ValidationException('test');

        $this->assertInstanceOf(ExceptionInterface::class, $exception);
        $this->assertInstanceOf(UploadRuntimeException::class, $exception);
        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    #[Test]
    public function uploadSessionNotFoundExceptionImplementsExceptionInterface(): void
    {
        $exception = new UploadSessionNotFoundException('upload-123');

        $this->assertInstanceOf(ExceptionInterface::class, $exception);
        $this->assertInstanceOf(UploadRuntimeException::class, $exception);
        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    #[Test]
    public function uploadSessionNotFoundExceptionFormatsMessage(): void
    {
        $exception = new UploadSessionNotFoundException('upload-abc-456');

        $this->assertStringContainsString('upload-abc-456', $exception->getMessage());
        $this->assertStringContainsString('not found', $exception->getMessage());
    }

    #[Test]
    public function invalidArgumentExceptionPreservesMessage(): void
    {
        $exception = new UploadInvalidArgumentException('Custom error message');

        $this->assertSame('Custom error message', $exception->getMessage());
    }

    #[Test]
    public function uploadExceptionPreservesMessage(): void
    {
        $exception = new UploadException('Something went wrong');

        $this->assertSame('Something went wrong', $exception->getMessage());
    }

    #[Test]
    public function storageExceptionImplementsExceptionInterface(): void
    {
        $exception = new StorageException('test');

        $this->assertInstanceOf(ExceptionInterface::class, $exception);
        $this->assertInstanceOf(UploadRuntimeException::class, $exception);
        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    #[Test]
    public function uploadExceptionImplementsExceptionInterface(): void
    {
        $exception = new UploadException('test');

        $this->assertInstanceOf(ExceptionInterface::class, $exception);
        $this->assertInstanceOf(UploadRuntimeException::class, $exception);
        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    #[Test]
    public function validationExceptionPreservesMessage(): void
    {
        $exception = new ValidationException('Validation failed');

        $this->assertSame('Validation failed', $exception->getMessage());
    }

    #[Test]
    public function exceptionInterfaceExtendsThrowable(): void
    {
        $reflection = new \ReflectionClass(ExceptionInterface::class);

        $this->assertTrue($reflection->isInterface());
        $this->assertTrue($reflection->implementsInterface(\Throwable::class));
    }

    #[Test]
    public function allExceptionsAreCatchableViaExceptionInterface(): void
    {
        $exceptions = [
            new UploadInvalidArgumentException('test'),
            new UploadException('test'),
            new ValidationException('test'),
            new UploadSessionNotFoundException('id'),
            new StorageException('test'),
        ];

        foreach ($exceptions as $exception) {
            $caught = false;
            try {
                throw $exception;
            } catch (ExceptionInterface) {
                $caught = true;
            }
            $this->assertTrue($caught, \sprintf('%s should be catchable via ExceptionInterface', $exception::class));
        }
    }
}
