<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Router\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\UX\Router\CacheWarmer\RoutesCacheWarmer;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
#[AsCommand(
    name: 'ux:router:warm-cache',
    description: 'Warm the routes cache (dump JS/TS route files)',
)]
final class WarmCacheCommand extends Command
{
    public function __construct(
        private RoutesCacheWarmer $cacheWarmer,
        private string $cacheDir,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->comment('Warming the routes cache...');

        $this->cacheWarmer->warmUp($this->cacheDir);

        $io->success('Routes cache warmed.');

        return Command::SUCCESS;
    }
}
