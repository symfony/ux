<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\UX\Image\Command\ValidateConfigurationCommand;
use Symfony\UX\Image\ConfigurationReporter;
use Symfony\UX\Image\ConfigurationValidator;
use Symfony\UX\Image\ImageAsset;
use Symfony\UX\Image\Processor\ImageDriverInterface;
use Symfony\UX\Image\Storage\StoragePath;
use Symfony\UX\Image\Storage\StreamStorageInterface;

#[CoversClass(ValidateConfigurationCommand::class)]
#[CoversClass(ConfigurationValidator::class)]
final class ValidateConfigurationCommandTest extends TestCase
{
    public function testExecuteWithNoWarnings(): void
    {
        $storages = ['default' => ['flysystem_service' => 'test', 'public_url_prefix' => '/test']];
        $profiles = ['avatar' => ['formats' => ['png'], 'variants' => ['thumb' => ['width' => 100]]]];
        $reporter = new ConfigurationReporter($storages, $profiles);

        $command = new ValidateConfigurationCommand($reporter, self::validator($storages, $profiles), $storages, $profiles);

        $tester = new CommandTester($command);
        $exitCode = $tester->execute([]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('Configuration validation passed', $tester->getDisplay());
    }

    public function testExecuteWithWarnings(): void
    {
        $storages = ['default' => ['flysystem_service' => 'test']];
        $profiles = ['avatar' => ['formats' => ['png']]];
        $reporter = new ConfigurationReporter($storages, $profiles);

        $command = new ValidateConfigurationCommand($reporter, self::validator($storages, $profiles), $storages, $profiles);

        $tester = new CommandTester($command);
        $exitCode = $tester->execute([]);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('Configuration validation found issues', $tester->getDisplay());
        $this->assertStringContainsString('declares no variants', $tester->getDisplay());
    }

    public function testCommandDisplaysStorageDetails(): void
    {
        $storages = [
            'cdn_storage' => [
                'flysystem_service' => 's3.storage',
                'public_url_prefix' => '/cdn',
                'cdn' => [
                    'provider' => 'cloudinary',
                    'base_url' => 'https://example.com',
                ],
            ],
        ];
        $reporter = new ConfigurationReporter($storages, []);

        $command = new ValidateConfigurationCommand($reporter, self::validator($storages, []), $storages, []);

        $tester = new CommandTester($command);
        $tester->execute([]);

        $display = $tester->getDisplay();
        $this->assertStringContainsString('Storage Details', $display);
        $this->assertStringContainsString('cdn_storage', $display);
        $this->assertStringContainsString('cloudinary', $display);
    }

    public function testExecuteFailsWhenConfiguredCodecCannotBeEncoded(): void
    {
        $storages = [];
        $profiles = ['avatar' => ['formats' => ['webp'], 'variants' => ['thumb' => ['width' => 100]]]];
        $reporter = new ConfigurationReporter($storages, $profiles);
        $command = new ValidateConfigurationCommand($reporter, self::validator($storages, $profiles), $storages, $profiles);

        $tester = new CommandTester($command);

        self::assertSame(1, $tester->execute([]));
        self::assertStringContainsString('Driver Capability Issues', $tester->getDisplay());
        self::assertStringContainsString('cannot encode "webp"', $tester->getDisplay());
    }

    public function testValidatorReportsUnsupportedDriver(): void
    {
        $validator = new ConfigurationValidator(new ValidationProcessor(), new ValidationStorage(), 'missing', [], []);

        self::assertSame(['The configured processor does not support driver "missing".'], $validator->validateDriver());
    }

    public function testValidatorSkipsBuiltInDriverProbeForCustomProcessor(): void
    {
        $validator = new ConfigurationValidator(new ValidationProcessor(), new ValidationStorage(), null, [], []);

        self::assertSame([], $validator->validateDriver());
    }

    public function testValidatorRequiresStreamStorage(): void
    {
        $validator = new ConfigurationValidator(
            new ValidationProcessor(),
            $this->createStub(\Symfony\UX\Image\Storage\StorageInterface::class),
            'test',
            [],
            [],
        );

        self::assertStringContainsString('StreamStorageInterface', $validator->validateStorages()[0]);
    }

    public function testValidatorDetectsCorruptedStorageRoundTrip(): void
    {
        $validator = new ConfigurationValidator(new ValidationProcessor(), new ValidationStorage(corruptRead: true), 'test', [], []);

        self::assertStringContainsString('returned different bytes', $validator->validateStorages()[0]);
    }

    public function testValidatorReportsProbeAndCleanupFailures(): void
    {
        $validator = new ConfigurationValidator(
            new ValidationProcessor(),
            new ValidationStorage(writeFails: true, deleteFails: true),
            'test',
            [],
            [],
        );
        $errors = $validator->validateStorages();

        self::assertStringContainsString('failed its write/read/delete', $errors[0]);
        self::assertStringContainsString('could not remove', $errors[1]);
    }

    /**
     * @param array<string, array<string, mixed>> $storages
     * @param array<string, array<string, mixed>> $profiles
     */
    private static function validator(array $storages, array $profiles): ConfigurationValidator
    {
        return new ConfigurationValidator(new ValidationProcessor(), new ValidationStorage(), 'test', $storages, $profiles);
    }
}

final class ValidationProcessor implements ImageDriverInterface
{
    public function supports(string $driver): bool
    {
        return 'test' === $driver;
    }

    public function process(UploadedFile $file, ?string $profile = null, string $storage = 'default_public'): ImageAsset
    {
        throw new \BadMethodCallException();
    }

    public function generateVariants(ImageAsset $imageAsset, array $variantConfigs): array
    {
        throw new \BadMethodCallException();
    }

    public function resize(string $inputPath, string $outputPath, int $width, int $height, string $mode = 'fit', string $position = 'center'): void
    {
        throw new \BadMethodCallException();
    }

    public function convert(string $inputPath, string $outputPath, string $format, int $quality = 80): void
    {
        if ('png' !== $format || !copy($inputPath, $outputPath)) {
            throw new \RuntimeException('Unsupported test codec.');
        }
    }

    public function extractMetadata(UploadedFile $file): array
    {
        throw new \BadMethodCallException();
    }
}

final class ValidationStorage implements StreamStorageInterface
{
    /** @var array<string, string> */
    private array $objects = [];

    public function __construct(
        private readonly bool $corruptRead = false,
        private readonly bool $writeFails = false,
        private readonly bool $deleteFails = false,
    ) {
    }

    public function store(UploadedFile $file, string $storageName, ?string $directory = null): string
    {
        throw new \BadMethodCallException();
    }

    public function delete(ImageAsset $imageAsset): bool
    {
        throw new \BadMethodCallException();
    }

    public function exists(ImageAsset $imageAsset): bool
    {
        return false;
    }

    public function getPublicUrl(ImageAsset $imageAsset, ?string $variant = null): string
    {
        throw new \BadMethodCallException();
    }

    public function getFilePath(ImageAsset $imageAsset): string
    {
        throw new \BadMethodCallException();
    }

    public function readStream(string $storageName, StoragePath $path)
    {
        $stream = fopen('php://temp', 'w+');
        fwrite($stream, $this->corruptRead ? 'corrupted' : $this->objects[$storageName.':'.$path->value]);
        rewind($stream);

        return $stream;
    }

    public function writeStream(string $storageName, StoragePath $path, $stream): void
    {
        if ($this->writeFails) {
            throw new \RuntimeException('write failed');
        }
        $contents = stream_get_contents($stream);
        if (false === $contents) {
            throw new \RuntimeException('Could not read test stream.');
        }
        $this->objects[$storageName.':'.$path->value] = $contents;
    }

    public function deletePath(string $storageName, StoragePath $path): void
    {
        if ($this->deleteFails) {
            throw new \RuntimeException('delete failed');
        }
        unset($this->objects[$storageName.':'.$path->value]);
    }
}
