<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\UX\Image\ConfigurationReporter;
use Symfony\UX\Image\ConfigurationValidator;

/**
 * Console command to validate UX Image configuration.
 *
 * @author Simon André <smn.andre@gmail.com>
 */
#[AsCommand(
    name: 'ux:image:validate',
    description: 'Validates the UX Image configuration and reports issues'
)]
final class ValidateConfigurationCommand extends Command
{
    /**
     * @param array<string, array<string, mixed>> $storages
     * @param array<string, array<string, mixed>> $profiles
     */
    public function __construct(
        private readonly ConfigurationReporter $reporter,
        private readonly ConfigurationValidator $validator,
        private readonly array $storages,
        private readonly array $profiles,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('UX Image Configuration Validation');

        $hasErrors = false;

        // Validate storages, including an automatically cleaned write/read probe.
        $storageWarnings = [...$this->reporter->getStorageWarnings(), ...$this->validator->validateStorages()];
        if ($storageWarnings) {
            $io->section('Storage Configuration Issues');
            foreach ($storageWarnings as $warning) {
                $io->warning($warning);
            }
            $hasErrors = true;
        } else {
            $io->success(\sprintf('All %d storage(s) are properly configured.', \count($this->storages)));
        }

        $driverErrors = $this->validator->validateDriver();
        if ($driverErrors) {
            $io->section('Driver Capability Issues');
            foreach ($driverErrors as $error) {
                $io->warning($error);
            }
            $hasErrors = true;
        } else {
            $io->success('The configured driver can encode every profile format.');
        }

        // Validate profiles
        $profileWarnings = $this->reporter->getProfileWarnings();
        if ($profileWarnings) {
            $io->section('Profile Configuration Issues');
            foreach ($profileWarnings as $warning) {
                $io->warning($warning);
            }
            $hasErrors = true;
        } else {
            $io->success(\sprintf('All %d profile(s) are properly configured.', \count($this->profiles)));
        }

        // Display detailed storage information
        if (!empty($this->storages)) {
            $io->section('Storage Details');
            $storageRows = [];
            foreach ($this->storages as $name => $config) {
                /** @var array<string, mixed> $cdn */
                $cdn = $config['cdn'] ?? [];
                $storageRows[] = [
                    $name,
                    $config['flysystem_service'] ?? $config['adapter_service'] ?? 'N/A',
                    $config['public_url_prefix'] ?? 'Not set',
                    isset($cdn['provider']) && \is_string($cdn['provider']) ? $cdn['provider'] : 'None',
                    isset($cdn['base_url']) && \is_string($cdn['base_url']) ? $cdn['base_url'] : 'N/A',
                ];
            }
            $io->table(
                ['Storage', 'Adapter', 'Public URL Prefix', 'CDN Provider', 'CDN Base URL'],
                $storageRows
            );
        }

        // Display detailed profile information
        if (!empty($this->profiles)) {
            $io->section('Profile Details');
            $profileRows = [];
            foreach ($this->profiles as $name => $config) {
                /** @var array<int, string> $profileFormats */
                $profileFormats = $config['formats'] ?? [];
                /** @var array<string, mixed> $profileVariants */
                $profileVariants = $config['variants'] ?? [];
                $profileRows[] = [
                    $name,
                    implode(', ', $profileFormats),
                    \count($profileVariants),
                    $config['processing'] ?? 'immediate',
                ];
            }
            $io->table(
                ['Profile', 'Formats', 'Variants', 'Processing'],
                $profileRows
            );
        }

        if ($hasErrors) {
            $io->error('Configuration validation found issues. Please review the warnings above.');

            return Command::FAILURE;
        }

        $io->success('Configuration validation passed! All settings are properly configured.');

        return Command::SUCCESS;
    }
}
