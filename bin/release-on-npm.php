<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Interactive npm release script for the Symfony UX monorepo.
 *
 * Usage:
 *   php bin/release-on-npm.php 2.36.0
 *   php bin/release-on-npm.php v2.36.0 --remote=origin
 *
 * Must be run on a {major}.x branch matching the version (2.36.0 → 2.x).
 * The release tag must already exist locally (created and pushed beforehand).
 */

require __DIR__.'/../vendor/autoload.php';

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\SingleCommandApplication;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

const PUBLISH_AUTO_RETRIES = 3;
const PUBLISH_RETRY_DELAY = 2;

chdir(__DIR__.'/..');

(new SingleCommandApplication())
    ->setName('Symfony UX — npm release')
    ->setDescription('Interactive npm release for the Symfony UX monorepo.')
    ->setHelp(<<<'HELP'
        Run this script manually after pushing a release tag (e.g. v2.36.0).
        The current branch must match the version's major (2.36.0 → 2.x).
        HELP)
    ->addArgument('version', InputArgument::REQUIRED, 'Version to release (e.g. 2.36.0). Leading "v" is stripped.')
    ->addOption('remote', null, InputOption::VALUE_REQUIRED, 'Git remote to push to.', 'upstream')
    ->setCode(static function (InputInterface $input, OutputInterface $output): int {
        $io = new SymfonyStyle($input, $output);

        $capture = static fn (array $cmd): string => trim((new Process($cmd))->mustRun()->getOutput());

        $run = static function (array $cmd, bool $tty = false) {
            $process = new Process($cmd);
            $process->setTimeout(null);
            if ($tty && Process::isTtySupported()) {
                $process->setTty(true);
            }

            return $process;
        };

        $io->title('Symfony UX — npm release');

        // 1. Normalize version
        $version = ltrim((string) $input->getArgument('version'), 'v');
        if (!preg_match('/^\d+\.\d+\.\d+$/', $version)) {
            $io->error(sprintf('Invalid version "%s". Expected MAJOR.MINOR.PATCH (e.g. 2.36.0).', $version));

            return 1;
        }
        $tag = 'v'.$version;
        $major = explode('.', $version)[0];
        $expectedBranch = $major.'.x';

        // 2. Branch guard
        $branch = $capture(['git', 'rev-parse', '--abbrev-ref', 'HEAD']);
        if ($branch !== $expectedBranch) {
            $io->error(sprintf(
                'Version %s requires branch %s, but current branch is %s.',
                $version, $expectedBranch, $branch,
            ));

            return 1;
        }

        // 3. Remote guard
        $remote = (string) $input->getOption('remote');
        $remoteProbe = new Process(['git', 'remote', 'get-url', $remote]);
        $remoteProbe->run();
        if (!$remoteProbe->isSuccessful()) {
            $available = $capture(['git', 'remote']);
            $io->error(sprintf(
                "Git remote '%s' does not exist. Available: %s",
                $remote, '' === $available ? '(none)' : str_replace("\n", ', ', $available),
            ));

            return 1;
        }

        // 4. Tag exists
        $tagProbe = new Process(['git', 'rev-parse', '--verify', '--quiet', 'refs/tags/'.$tag]);
        $tagProbe->run();
        if (!$tagProbe->isSuccessful()) {
            $io->error(sprintf('Tag %s not found locally. Did you create it?', $tag));

            return 1;
        }

        // 5. HEAD at tag commit?
        $headSha = $capture(['git', 'rev-parse', 'HEAD']);
        $tagSha = $capture(['git', 'rev-list', '-n', '1', $tag]);
        if ($headSha !== $tagSha) {
            $io->warning(sprintf(
                'HEAD (%s) is not at tag %s (%s).',
                substr($headSha, 0, 7), $tag, substr($tagSha, 0, 7),
            ));
            if (!$io->confirm('Continue anyway?', false)) {
                return 1;
            }
        }

        // 6. Clean working tree
        if ('' !== $capture(['git', 'status', '--porcelain'])) {
            $io->error('Working tree is not clean. Commit or stash changes first.');

            return 1;
        }

        // 7. Recap + final confirm
        $packageJsonFiles = array_merge(
            glob('src/*/assets/package.json') ?: [],
            glob('src/*/src/Bridge/*/assets/package.json') ?: [],
        );
        $io->definitionList(
            ['Branch' => $branch],
            ['Version' => $version],
            ['Tag' => $tag],
            ['Remote' => $remote],
            ['Workspace packages' => (string) count($packageJsonFiles)],
        );
        if (!$io->confirm('Proceed with npm release?', false)) {
            $io->warning('Aborted by user.');

            return 0;
        }

        // 8. npm login
        $io->section('Login to npm registry');
        if (!Process::isTtySupported()) {
            $io->error('TTY is required for npm login. Run this script from a terminal.');

            return 1;
        }
        $login = $run(['npm', 'login'], tty: true);
        $login->run();
        if (!$login->isSuccessful()) {
            $io->error('npm login failed.');

            return 1;
        }

        // 9. Install dependencies
        $io->section('Install JS dependencies');
        $run(['pnpm', 'install', '--frozen-lockfile'], tty: true)->mustRun();

        // 10. Bump versions
        $io->section(sprintf('Bump packages to %s', $version));
        $run(['pnpm', 'version', $version, '--no-git-tag-version', '--workspaces', '--no-workspaces-update'])->mustRun();

        // 11. Commit bump
        $io->section('Commit bump');
        $run(['git', 'add', ...$packageJsonFiles])->mustRun();
        $run(['git', 'commit', '-m', sprintf('Update versions to %s', $version)])->mustRun();
        $io->success(sprintf('Created commit: "Update versions to %s"', $version));

        // 12. Publish with retry
        $attempt = 0;
        while (true) {
            ++$attempt;
            $io->section(sprintf('Publish attempt %d', $attempt));
            $publish = $run(['pnpm', 'publish', '--recursive', '--access', 'public', '--no-git-checks'], tty: true);
            $publish->run();

            if ($publish->isSuccessful()) {
                $io->success('All packages published.');
                break;
            }

            if ($attempt < PUBLISH_AUTO_RETRIES) {
                $io->warning(sprintf('Attempt %d failed. Retrying in %ds…', $attempt, PUBLISH_RETRY_DELAY));
                sleep(PUBLISH_RETRY_DELAY);
                continue;
            }

            $io->error(sprintf('Publish failed %d times in a row.', $attempt));
            if (!$io->confirm('Retry once more?', true)) {
                $io->warning(sprintf(
                    'Aborting. Bump commit is local but not pushed. Re-run "pnpm publish --recursive --access public --no-git-checks" manually, then "git push %s %s".',
                    $remote, $branch,
                ));

                return 1;
            }
        }

        // 13. Push final
        $io->section('Push commit');
        if (!$io->confirm(sprintf('Push to %s/%s?', $remote, $branch), false)) {
            $io->warning(sprintf('Skipped push. Run manually: git push %s %s', $remote, $branch));

            return 0;
        }
        $run(['git', 'push', $remote, $branch])->mustRun();

        $io->success(sprintf('Release %s complete.', $version));

        return 0;
    })
    ->run();
