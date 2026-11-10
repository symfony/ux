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
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\UX\Css\UXCssBundle;
use Symfony\UX\Icons\UXIconsBundle;
use Symfony\UX\TwigComponent\TwigComponentBundle;
use TalesFromADev\Twig\Extra\Tailwind\Bridge\Symfony\Bundle\TalesFromADevTwigExtraTailwindBundle;
use Twig\Environment;
use Twig\Extra\TwigExtraBundle\TwigExtraBundle;

/**
 * Renders the same dashboard twice: with the shadcn recipes of the UX Toolkit (Tailwind CSS), and with their UX CSS twins.
 */
final class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    private const STATUSES = ['published', 'draft', 'archived'];
    private const AUTHORS = ['Ada Lovelace', 'Grace Hopper', 'Alan Turing', 'Margaret Hamilton', 'Linus Torvalds'];

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new TwigBundle();
        yield new TwigExtraBundle();
        yield new TwigComponentBundle();
        yield new UXIconsBundle();
        yield new UXCssBundle();
        yield new TalesFromADevTwigExtraTailwindBundle();
    }

    protected function configureContainer(ContainerConfigurator $container): void
    {
        // https://symfony.com/doc/current/performance.html
        $container->parameters()->set('.container.dumper.inline_factories', true);

        $container->extension('framework', [
            'enabled_locales' => ['en'],
        ]);

        $container->extension('twig_component', [
            'anonymous_template_directory' => 'components/',
            'defaults' => [],
        ]);

        $container->extension('ux_icons', [
            'icon_dir' => '%kernel.project_dir%/assets/icons',
            'iconify' => ['on_demand' => false],
        ]);

        // The shadcn theme, read through the same CSS variables as the Tailwind side
        $container->extension('ux_css', [
            'preflight' => true,
            'default_tokens' => 'panda',
            'tokens' => [
                'colors' => [
                    'background' => 'var(--background)',
                    'foreground' => 'var(--foreground)',
                    'card' => ['DEFAULT' => 'var(--card)', 'foreground' => 'var(--card-foreground)'],
                    'popover' => ['DEFAULT' => 'var(--popover)', 'foreground' => 'var(--popover-foreground)'],
                    'primary' => ['DEFAULT' => 'var(--primary)', 'foreground' => 'var(--primary-foreground)'],
                    'secondary' => ['DEFAULT' => 'var(--secondary)', 'foreground' => 'var(--secondary-foreground)'],
                    'muted' => ['DEFAULT' => 'var(--muted)', 'foreground' => 'var(--muted-foreground)'],
                    'accent' => ['DEFAULT' => 'var(--accent)', 'foreground' => 'var(--accent-foreground)'],
                    'destructive' => 'var(--destructive)',
                    'border' => 'var(--border)',
                    'input' => 'var(--input)',
                    'ring' => 'var(--ring)',
                ],
                'radii' => [
                    'sm' => 'calc(var(--radius) * 0.6)',
                    'md' => 'calc(var(--radius) * 0.8)',
                    'lg' => 'var(--radius)',
                    'xl' => 'calc(var(--radius) * 1.4)',
                    '2xl' => 'calc(var(--radius) * 1.8)',
                    '3xl' => 'calc(var(--radius) * 2.2)',
                    '4xl' => 'calc(var(--radius) * 2.6)',
                ],
            ],
        ]);
    }

    #[Route('/tailwind', name: 'app_tailwind', methods: ['GET'])]
    public function tailwind(Environment $twig): Response
    {
        return new Response($twig->render('tailwind.html.twig', $this->data()));
    }

    #[Route('/ux-css', name: 'app_ux_css', methods: ['GET'])]
    public function uxCss(Environment $twig): Response
    {
        return new Response($twig->render('ux_css.html.twig', $this->data()));
    }

    /**
     * @return array<string, mixed>
     */
    private function data(): array
    {
        $posts = [];
        for ($i = 1; $i <= 25; ++$i) {
            $author = self::AUTHORS[$i % \count(self::AUTHORS)];
            $words = explode(' ', $author);
            $posts[] = [
                'title' => \sprintf('Post number %d about Symfony UX', $i),
                'author' => $author,
                'initials' => implode('', array_map(static fn (string $word): string => $word[0], $words)),
                'status' => self::STATUSES[$i % \count(self::STATUSES)],
            ];
        }

        return [
            'stats' => [
                ['label' => 'Total posts', 'value' => '1,284', 'trend' => '+12%', 'note' => 'Since last month'],
                ['label' => 'Published', 'value' => '942', 'trend' => '+8%', 'note' => 'Live on the site'],
                ['label' => 'Drafts', 'value' => '231', 'trend' => '-3%', 'note' => 'Waiting for review'],
                ['label' => 'Views', 'value' => '48.2k', 'trend' => '+21%', 'note' => 'Over the last 30 days'],
            ],
            'posts' => $posts,
            'fields' => [
                ['id' => 'title', 'label' => 'Title', 'placeholder' => 'A great title'],
                ['id' => 'slug', 'label' => 'Slug', 'placeholder' => 'a-great-title'],
                ['id' => 'category', 'label' => 'Category', 'placeholder' => 'News'],
                ['id' => 'tags', 'label' => 'Tags', 'placeholder' => 'symfony, ux'],
            ],
        ];
    }
}
