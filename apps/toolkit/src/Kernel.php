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

use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Icons\UXIconsBundle;
use Symfony\UX\Toolkit\Kit\KitContextRunner;
use Symfony\UX\Toolkit\Preview\PreviewAssetsGenerator;
use Symfony\UX\Toolkit\Preview\PreviewKitRegistry;
use Symfony\UX\Toolkit\Recipe\RecipeType;
use Symfony\UX\Toolkit\Tests\Fixtures\SecurityTwigStubExtension;
use Symfony\UX\Toolkit\UXToolkitBundle;
use Symfony\UX\TwigComponent\TwigComponentBundle;
use Symfonycasts\TailwindBundle\SymfonycastsTailwindBundle;
use TalesFromADev\Twig\Extra\Tailwind\Bridge\Symfony\Bundle\TalesFromADevTwigExtraTailwindBundle;
use Twig\Environment;
use Twig\Extra\TwigExtraBundle\TwigExtraBundle;

/**
 * Renders every example of the UX Toolkit kits in isolation, with the preview assets of its kit.
 */
final class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    private const THEMES = ['light', 'dark'];

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new TwigBundle();
        yield new TwigExtraBundle();
        yield new TwigComponentBundle();
        yield new UXIconsBundle();
        yield new SymfonycastsTailwindBundle();
        yield new TalesFromADevTwigExtraTailwindBundle();
        yield new UXToolkitBundle();
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        $container->extension('framework', [
            'default_locale' => 'en',
            'asset_mapper' => [
                'paths' => ['assets/'],
                'missing_import_mode' => 'strict',
            ],
        ]);

        $container->extension('twig_component', [
            'anonymous_template_directory' => 'components/',
            'defaults' => [],
        ]);

        // The icons the Toolkit HTML snapshots render with, so previews never call the Iconify API.
        $container->extension('ux_icons', [
            'icon_dir' => '%kernel.project_dir%/../../src/Toolkit/tests/Fixtures/icons',
            'iconify' => ['on_demand' => false],
        ]);

        $container->extension('symfonycasts_tailwind', [
            'binary_version' => 'v4.2.2',
            'input_css' => [],
        ]);

        $container->extension('ux_toolkit', [
            'preview' => [
                'kits' => array_filter(explode(\PATH_SEPARATOR, $_SERVER['UX_TOOLKIT_PREVIEW_KITS'] ?? '')),
            ],
        ]);

        $container->services()
            ->set(SecurityTwigStubExtension::class)->tag('twig.extension')
            ->alias(PreviewKitRegistry::class, '.ux_toolkit.preview.kit_registry')
            ->alias(KitContextRunner::class, 'ux_toolkit.kit.kit_context_runner');
    }

    #[Route('/', name: 'app_index')]
    public function index(Environment $twig, PreviewKitRegistry $kitRegistry): Response
    {
        return new Response($twig->render('index.html.twig', ['kits' => $kitRegistry->getKits()]));
    }

    #[AsCommand('app:examples', description: 'Lists the examples of the previewed kits as JSON')]
    public function examples(OutputInterface $output, PreviewKitRegistry $kitRegistry): int
    {
        $examples = [];
        foreach ($kitRegistry->getKits() as $kitName => $kit) {
            foreach ($kit->getRecipes() as $recipe) {
                foreach ($recipe->getExamples() as $example) {
                    $examples[] = ['kit' => $kitName, 'recipe' => $recipe->name, 'id' => $example['id']];
                }
            }
        }

        $output->writeln(json_encode($examples, \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES));

        return 0;
    }

    #[Route('/{kit}/{recipe}/{example}', name: 'app_preview')]
    public function preview(
        string $kit,
        string $recipe,
        string $example,
        Environment $twig,
        PreviewKitRegistry $kitRegistry,
        KitContextRunner $kitContextRunner,
        #[MapQueryParameter]
        string $theme = 'light',
    ): Response {
        if (!\in_array($theme, self::THEMES, true)) {
            throw new NotFoundHttpException();
        }

        $kitObject = $kitRegistry->getKit($kit) ?? throw new NotFoundHttpException();
        $recipeObject = $kitObject->getRecipe($recipe) ?? throw new NotFoundHttpException();
        $codeById = array_column($recipeObject->getExamples(), 'code', 'id');
        $code = $codeById[$example] ?? throw new NotFoundHttpException();

        $template = $twig->createTemplate($code);
        // Twig's random() draws from mt_rand(): a fixed seed keeps screenshots stable.
        mt_srand(0);
        $html = $kitContextRunner->runForKit($kitObject, static fn () => $template->render(), $recipeObject);

        return new Response($twig->render('preview.html.twig', [
            'title' => \sprintf('%s / %s / %s', $kit, $recipe, $example),
            'entrypoint' => PreviewAssetsGenerator::entrypointName($kit),
            'theme' => $theme,
            'is_block' => RecipeType::Block === $recipeObject->manifest->type,
            'html' => $html,
        ]));
    }
}
