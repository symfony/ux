<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App;

use App\Entity\Game;
use App\Entity\Player;
use App\Entity\Team;
use App\Form\GameType;
use Symfony\Bundle\DebugBundle\DebugBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Bundle\WebProfilerBundle\WebProfilerBundle;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\UX\Collection\CollectionBundle;
use Symfony\WebpackEncoreBundle\WebpackEncoreBundle;
use Twig\Environment;

/**
 * @author Kévin Dunglas <kevin@dunglas.fr>
 */
class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new TwigBundle();
        yield new CollectionBundle();
        yield new WebpackEncoreBundle();
        yield new WebProfilerBundle();
        yield new DebugBundle();
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'secret' => 'ChangeMe',
            'test' => 'test' === ($_SERVER['APP_ENV'] ?? 'dev'),
            'router' => [
                'utf8' => true,
            ],
            'profiler' => [
                'only_exceptions' => false,
            ],
        ]);

        $container->extension('webpack_encore', ['output_path' => 'build']);
        $container->extension('web_profiler', [
            'toolbar' => true,
            'intercept_redirects' => false,
        ]);
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import('@WebProfilerBundle/Resources/config/routing/wdt.xml')->prefix('/_wdt');
        $routes->import('@WebProfilerBundle/Resources/config/routing/profiler.xml')->prefix('/_profiler');

        $routes->add('form', '/{type<^basic|template$>?basic}')->controller('kernel::form');
    }

    public function getProjectDir(): string
    {
        return __DIR__;
    }

    public function form(Request $request, Environment $twig, FormFactoryInterface $formFactory, string $type): Response
    {
        $game = new Game();
        $team1 = new Team();
        $team1->name = 'Symfony UX Team';
        $player1 = new Player();
        $player1->firstName = 'Player';
        $player1->lastName = 'A1';
        $team1->addPlayer($player1);
        $player2 = new Player();
        $player2->firstName = 'Player';
        $player2->lastName = 'A2';
        $team1->addPlayer($player2);
        $team2 = new Team();
        $team2->name = 'Symfony Core Team';
        $game->addTeam($team1);
        $game->addTeam($team2);

        $form = $formFactory->create(GameType::class, $game);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Update array keys
            $form = $formFactory->create(GameType::class, $form->getData());
        }

        return new Response(
            $twig->render("{$type}_form.html.twig", ['form' => $form->createView()])
        );
    }
}
