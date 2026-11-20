<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Bench;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

final class AgainstInstaller
{
    private const APP_CODE_ENTRIES = ['assets', 'bin', 'config', 'public', 'src', 'templates', 'Caddyfile', 'frankenphp.ini'];

    private readonly Filesystem $filesystem;

    public function __construct(
        private readonly FrankenPhpServer $server,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {
        $this->filesystem = new Filesystem();
    }

    public static function directoryName(string $ref): string
    {
        return Side::slug($ref);
    }

    /**
     * @return array<string, string> package name => absolute path
     */
    public static function packagesIn(string $worktree): array
    {
        $manifests = [
            ...glob(\sprintf('%s/src/*/composer.json', $worktree)) ?: [],
            ...glob(\sprintf('%s/src/*/src/Bridge/*/composer.json', $worktree)) ?: [],
        ];

        $packages = [];
        foreach ($manifests as $manifest) {
            $name = json_decode((string) file_get_contents($manifest), true)['name'] ?? null;
            if (!\is_string($name)) {
                continue;
            }

            $packages[$name] = (string) realpath(\dirname($manifest));
        }

        ksort($packages);

        return $packages;
    }

    /**
     * @param callable(string): void $log
     */
    public function install(string $ref, int $port, callable $log): Side
    {
        $root = \dirname($this->projectDir, 2);
        $directoryName = self::directoryName($ref);
        $base = \sprintf('%s/var/bench/against/%s', $this->projectDir, $directoryName);

        $log(\sprintf('Resolving %s', $ref));
        $sha = trim($this->run(['git', 'rev-parse', '--verify', '--end-of-options', \sprintf('%s^{commit}', $ref)], $root));
        $short = substr($sha, 0, 11);
        $app = \sprintf('%s/app', $base);
        $worktree = \sprintf('%s/ux', $base);

        if ($this->isInstalled($base, $sha)) {
            $log(\sprintf('Reusing %s (%s)', $ref, $short));
            $this->copyAppCode($app);
            $this->run(['composer', 'dump-autoload', '--no-dev', '--classmap-authoritative'], $app, ['COMPOSER_NO_INTERACTION' => '1']);
            $this->server->phpCli(['bin/console', 'cache:clear', '--quiet'], $app);

            return $this->side($ref, $base, $port, $short);
        }

        $log(\sprintf('Installing %s (%s)', $ref, $short));
        $this->filesystem->mkdir($base);

        if ($this->filesystem->exists($worktree)) {
            $this->removeWorktree($worktree, $root);
        }
        $this->run(['git', 'worktree', 'add', '--detach', $worktree, $sha], $root);

        try {
            $this->filesystem->remove([$app, \sprintf('%s/composer.json', $base), \sprintf('%s/sha', $base)]);
            $this->copyAppCode($app);
            $this->filesystem->copy($this->sourceComposerJson(), \sprintf('%s/composer.json', $app));
            $this->rewriteComposerJson($app, $worktree, $ref);
            $this->run(['composer', 'update', '--no-dev', '--classmap-authoritative', '--no-progress'], $app, ['COMPOSER_NO_INTERACTION' => '1']);
            $this->server->phpCli(['bin/console', 'cache:clear', '--quiet'], $app);
            $this->filesystem->copy($this->sourceComposerJson(), \sprintf('%s/composer.json', $base));
            file_put_contents(\sprintf('%s/sha', $base), $sha);
        } catch (\Throwable $e) {
            try {
                $this->removeWorktree($worktree, $root);
            } catch (\RuntimeException) {
            }

            throw $e;
        }

        $this->removeWorktree($worktree, $root);

        return $this->side($ref, $base, $port, $short);
    }

    private function isInstalled(string $base, string $sha): bool
    {
        $shaFile = \sprintf('%s/sha', $base);

        return is_file($shaFile)
            && $sha === trim((string) file_get_contents($shaFile))
            && is_file(\sprintf('%s/app/vendor/autoload.php', $base))
            && is_file(\sprintf('%s/composer.json', $base))
            && file_get_contents($this->sourceComposerJson()) === file_get_contents(\sprintf('%s/composer.json', $base));
    }

    private function side(string $ref, string $base, int $port, string $short): Side
    {
        $app = \sprintf('%s/app', $base);

        return new Side($ref, $app, \sprintf('%s/pages', $base), $port, $short, false, VendorVersions::read($app), false);
    }

    private function sourceComposerJson(): string
    {
        return \sprintf('%s/composer.json', $this->projectDir);
    }

    private function copyAppCode(string $app): void
    {
        $this->filesystem->mkdir($app);

        foreach (self::APP_CODE_ENTRIES as $entry) {
            $source = \sprintf('%s/%s', $this->projectDir, $entry);
            $target = \sprintf('%s/%s', $app, $entry);

            if (is_dir($source)) {
                $this->filesystem->mirror($source, $target, null, ['delete' => true, 'override' => true]);
            } else {
                $this->filesystem->copy($source, $target, true);
            }
        }
    }

    private function removeWorktree(string $worktree, string $root): void
    {
        $this->run(['git', 'worktree', 'remove', '--force', $worktree], $root);
    }

    private function rewriteComposerJson(string $app, string $worktree, string $ref): void
    {
        $file = \sprintf('%s/composer.json', $app);
        $composerJson = json_decode((string) file_get_contents($file), true, 512, \JSON_THROW_ON_ERROR);

        $rewritten = ComposerJsonRewriter::rewrite($composerJson, self::packagesIn($worktree), ComposerJsonRewriter::versionForRef($ref));

        file_put_contents($file, json_encode($rewritten, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_THROW_ON_ERROR));
    }

    /**
     * @param list<string>         $command
     * @param array<string,string> $env
     */
    private function run(array $command, string $cwd, array $env = []): string
    {
        $process = new Process($command, $cwd, $env);
        $process->setTimeout(null);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new \RuntimeException(trim($process->getErrorOutput()) ?: trim($process->getOutput()));
        }

        return $process->getOutput();
    }
}
