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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\UX\Image\Exception\ExceptionInterface;
use Symfony\UX\Image\Exception\RuntimeException;
use Symfony\UX\Image\ImageAsset;
use Symfony\UX\Image\Processor\ImageProcessorInterface;
use Symfony\UX\Image\Regeneration\ImageAssetBatchQuery;
use Symfony\UX\Image\Regeneration\RegenerationServiceResolver;
use Symfony\UX\Image\Storage\StorageInterface;
use Symfony\UX\Image\Storage\StorageName;
use Symfony\UX\Image\Storage\StoragePath;
use Symfony\UX\Image\Storage\StreamStorageInterface;

#[AsCommand(
    name: 'ux:image:regenerate',
    description: 'Regenerate and persist image assets supplied by the application',
)]
final class RegenerateVariantsCommand extends Command
{
    /**
     * @param array<string, array<string, mixed>> $profiles
     * @param array<string, array<string, mixed>> $storages
     */
    public function __construct(
        private readonly ImageProcessorInterface $processor,
        private readonly array $profiles,
        private readonly RegenerationServiceResolver $services,
        private readonly StorageInterface $storage,
        private readonly array $storages,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('image-profile', null, InputOption::VALUE_REQUIRED, 'Image profile to regenerate (required)')
            ->addOption('storage', 's', InputOption::VALUE_REQUIRED, 'Storage to query (required)')
            ->addOption('batch-size', null, InputOption::VALUE_REQUIRED, 'Assets per bounded batch (1-1000)', '100')
            ->addOption('after', null, InputOption::VALUE_REQUIRED, 'Opaque provider cursor to resume after')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Enumerate assets without processing or persisting')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Regenerate assets already using the current profile revision')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $profile = $input->getOption('image-profile');
        $storage = $input->getOption('storage');
        $batchSize = filter_var($input->getOption('batch-size'), \FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 1000]]);
        $after = $input->getOption('after');

        if (!\is_string($profile) || '' === trim($profile) || !\is_string($storage) || '' === trim($storage) || false === $batchSize) {
            $io->error('Options --image-profile and --storage are required; --batch-size must be between 1 and 1000.');

            return Command::INVALID;
        }
        try {
            new StorageName($storage);
        } catch (\InvalidArgumentException $e) {
            $io->error($e->getMessage());

            return Command::INVALID;
        }
        if ('default_public' !== $storage && !isset($this->storages[$storage])) {
            $io->error(\sprintf('Unknown image storage "%s". Available storages: %s.', $storage, implode(', ', array_keys($this->storages))));

            return Command::INVALID;
        }
        $config = $this->profiles[$profile] ?? null;
        if (!\is_array($config)) {
            $io->error(\sprintf('Unknown image profile "%s". Available profiles: %s.', $profile, implode(', ', array_keys($this->profiles))));

            return Command::INVALID;
        }
        if (null !== $after && (!\is_string($after) || '' === $after)) {
            $io->error('--after must be a non-empty opaque cursor.');

            return Command::INVALID;
        }

        try {
            $provider = $this->services->provider();
            $persister = $this->services->persister();
        } catch (ExceptionInterface $e) {
            $io->error($e->getMessage().' Configure and tag the application services before running this command.');

            return Command::INVALID;
        }

        $dryRun = (bool) $input->getOption('dry-run');
        $force = (bool) $input->getOption('force');
        $revision = hash('sha256', json_encode($config, \JSON_THROW_ON_ERROR));
        $cursor = $after;
        $seenCursorWindow = [];
        $processed = 0;
        $skipped = 0;
        $failed = 0;

        $io->title(\sprintf('Regenerating profile "%s" in storage "%s"', $profile, $storage));

        do {
            $requestedCursor = $cursor;
            try {
                $batch = $provider->fetch(new ImageAssetBatchQuery($profile, $storage, $batchSize, $requestedCursor));
            } catch (\Throwable $e) {
                $io->error('Provider failed: '.$e->getMessage());
                $this->writeResumeInstruction($io, $cursor);

                return Command::FAILURE;
            }

            if (\count($batch->items) > $batchSize) {
                $io->error(\sprintf('Provider returned %d assets for a batch limit of %d.', \count($batch->items), $batchSize));

                return Command::FAILURE;
            }

            foreach ($batch->items as $reference) {
                if ($reference->asset->storageName !== $storage) {
                    $io->error(\sprintf('Provider returned asset "%s" from storage "%s", expected "%s".', $reference->id, $reference->asset->storageName, $storage));

                    return Command::FAILURE;
                }
                $expectsVariants = [] !== ($config['variants'] ?? []) && [] !== ($config['formats'] ?? []);
                $hasGeneratedVariants = !$reference->asset->getImageSourceSet()->isEmpty();
                if (!$force
                    && $reference->asset->profile === $profile
                    && $reference->asset->profileRevision === $revision
                    && (!$expectsVariants || $hasGeneratedVariants)) {
                    ++$skipped;
                    $cursor = $reference->cursor;
                    continue;
                }
                if ($dryRun) {
                    ++$processed;
                    $cursor = $reference->cursor;
                    continue;
                }

                try {
                    $variants = $this->processor->generateVariants($reference->asset, $config);
                    $updated = new ImageAsset(
                        storageName: $reference->asset->storageName,
                        path: $reference->asset->path,
                        originalFilename: $reference->asset->originalFilename,
                        mimeType: $reference->asset->mimeType,
                        width: $reference->asset->width,
                        height: $reference->asset->height,
                        variants: $variants,
                        profile: $profile,
                        profileRevision: $revision,
                    );
                    if (!$persister->compareAndSwap($reference, $updated)) {
                        $this->cleanupNewVariants($reference->asset, $updated);
                        $io->error(\sprintf('Asset "%s" changed concurrently; its new generation was discarded.', $reference->id));
                        $this->writeResumeInstruction($io, $cursor);

                        return Command::FAILURE;
                    }
                } catch (\Throwable $e) {
                    ++$failed;
                    if (isset($updated)) {
                        try {
                            $this->cleanupNewVariants($reference->asset, $updated);
                        } catch (\Throwable $cleanupError) {
                            $io->warning('Could not clean the unpublished generation: '.$cleanupError->getMessage());
                        }
                    }
                    $io->error(\sprintf('Failed at asset "%s" after cursor "%s": %s', $reference->id, $cursor ?? '<start>', $e->getMessage()));
                    $this->writeResumeInstruction($io, $cursor);

                    return Command::FAILURE;
                }

                ++$processed;
                $cursor = $reference->cursor;
                $io->writeln(\sprintf('Persisted %s; checkpoint %s', $reference->id, $cursor));
                try {
                    $this->cleanupObsoleteVariants($reference->asset, $updated);
                } catch (\Throwable $e) {
                    $io->warning(\sprintf('Asset "%s" is durable, but obsolete variants could not be cleaned: %s', $reference->id, $e->getMessage()));
                }
                unset($updated);
            }

            $next = $batch->nextCursor;
            if (null !== $next) {
                if (\in_array($next, $seenCursorWindow, true) || $next === $requestedCursor) {
                    $io->error(\sprintf('Provider returned a non-progressing cursor "%s".', $next));

                    return Command::FAILURE;
                }
                $seenCursorWindow[] = $next;
                if (\count($seenCursorWindow) > 1024) {
                    array_shift($seenCursorWindow);
                }
                $cursor = $next;
            }
        } while (null !== $batch->nextCursor);

        $io->text(\sprintf('Processed: %d, Skipped: %d, Failed: %d', $processed, $skipped, $failed));
        if ($dryRun) {
            $io->success(\sprintf('Dry run complete. %d asset(s) would be regenerated; nothing was processed or persisted.', $processed));
        } else {
            $io->success(\sprintf('Regeneration complete. %d asset(s) were processed and atomically persisted.', $processed));
        }

        return Command::SUCCESS;
    }

    private function writeResumeInstruction(SymfonyStyle $io, ?string $cursor): void
    {
        if (null === $cursor) {
            $io->note('Resume by running the same command without --after.');

            return;
        }

        $io->note(\sprintf('Resume with --after=%s', escapeshellarg($cursor)));
    }

    private function cleanupNewVariants(ImageAsset $old, ImageAsset $new): void
    {
        $this->deleteVariantDifference($new, $old);
    }

    private function cleanupObsoleteVariants(ImageAsset $old, ImageAsset $new): void
    {
        $this->deleteVariantDifference($old, $new);
    }

    private function deleteVariantDifference(ImageAsset $from, ImageAsset $keep): void
    {
        if (!$this->storage instanceof StreamStorageInterface) {
            throw new RuntimeException('Variant cleanup requires a stream-capable image storage.');
        }

        $keepPaths = [];
        foreach ($keep->getFilePaths() as $path) {
            $keepPaths[StoragePath::fromAssetPath($path)->value] = true;
        }
        $originalPath = StoragePath::fromAssetPath($from->path)->value;
        foreach ($from->getFilePaths() as $path) {
            $storagePath = StoragePath::fromAssetPath($path);
            if ($storagePath->value === $originalPath || isset($keepPaths[$storagePath->value])) {
                continue;
            }
            $this->storage->deletePath($from->storageName, $storagePath);
        }
    }
}
