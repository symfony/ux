<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Tests\Functional;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Spatie\Snapshots\Drivers\HtmlDriver;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\UX\Toolkit\Kit\Kit;
use Symfony\UX\Toolkit\Kit\KitContextRunner;
use Symfony\UX\Toolkit\Kit\KitFactory;
use Symfony\UX\Toolkit\Kit\KitSynchronizer;
use Symfony\UX\Toolkit\Recipe\Recipe;
use Symfony\UX\Toolkit\Recipe\RecipeSynchronizer;
use Symfony\UX\Toolkit\Registry\LocalRegistry;
use Symfony\UX\Toolkit\Tests\TestHelperTrait;

class ComponentsRenderingTest extends WebTestCase
{
    use MatchesSnapshots;
    use TestHelperTrait;

    private const KITS_DIR = __DIR__.'/../../kits';

    /**
     * @return iterable<string, string, string>
     */
    public static function provideTestComponentRendering(): iterable
    {
        $filesystem = new Filesystem();
        $kitSynchronizer = new KitSynchronizer($filesystem, new RecipeSynchronizer());

        foreach (LocalRegistry::getAvailableKitsName() as $kitName) {
            $kit = self::createLocalKit($kitName);
            $kitSynchronizer->synchronize($kit);

            foreach ($kit->getRecipes() as $recipe) {
                foreach ($recipe->getExamples() as $i => $example) {
                    yield \sprintf('Kit %s, component %s, example #%d', $kitName, $recipe->name, $i) => [$kitName, $recipe->name, $example['code']];
                }
            }
        }
    }

    #[DataProvider('provideTestComponentRendering')]
    #[Group('skip-on-lowest')]
    public function testComponentRendering(string $kitName, string $recipeName, string $code)
    {
        $twig = self::getContainer()->get('twig');
        /** @var KitContextRunner $kitContextRunner */
        $kitContextRunner = self::getContainer()->get('ux_toolkit.kit.kit_context_runner');

        $kit = $this->instantiateKit($kitName);
        $template = $twig->createTemplate($code);
        $renderedCode = $kitContextRunner->runForKit($kit, static fn () => $template->render());

        $this->assertCodeRenderedMatchesHtmlSnapshot($kit, $kit->getRecipe($recipeName), $code, $renderedCode);
    }

    private function instantiateKit(string $kitName): Kit
    {
        $kitFactory = self::getContainer()->get('ux_toolkit.kit.kit_factory');

        self::assertInstanceOf(KitFactory::class, $kitFactory);

        return $kitFactory->createKitFromAbsolutePath(Path::join(__DIR__, '../../kits', $kitName));
    }

    private function assertCodeRenderedMatchesHtmlSnapshot(Kit $kit, Recipe $recipe, string $code, string $renderedCode): void
    {
        $info = \sprintf(
            <<<HTML
                <!--
                - Kit: %s
                - Component: %s
                - Code:
                ```twig
                %s
                ```
                - Rendered code (prettified for testing purposes, run "php vendor/bin/phpunit -d --update-snapshots" to update snapshots): -->
                HTML,
            $kit->manifest->name,
            $recipe->manifest->name,
            trim($code)
        );

        $this->assertMatchesSnapshot($renderedCode, new class($info) extends HtmlDriver {
            public function __construct(private string $info)
            {
            }

            public function serialize($data): string
            {
                // Encode non-ASCII as numeric entities so libxml parses the input the same way
                // on every version: libxml < 2.14 assumes Latin-1 when no charset is declared and
                // corrupts non-ASCII text (e.g. RTL Arabic/Hebrew) into broken per-byte entities.
                $serialized = parent::serialize(mb_encode_numericentity($data, [0x80, 0x10FFFF, 0, 0x1FFFFF], 'UTF-8'));

                // Decode entities back to raw UTF-8 so snapshots are readable and identical across
                // libxml versions (libxml < 2.14 re-emits non-ASCII as entities, >= 2.14 emits raw
                // characters). Structural entities must stay escaped, so shield them while decoding.
                $shield = ['&amp;' => "\1", '&lt;' => "\2", '&gt;' => "\3", '&quot;' => "\4"];
                $serialized = strtr(html_entity_decode(strtr($serialized, $shield), \ENT_QUOTES | \ENT_HTML5, 'UTF-8'), array_flip($shield));

                $serialized = str_replace(['<html><body>', '</body></html>'], '', $serialized);
                $serialized = trim($serialized);

                return $this->info."\n".$serialized;
            }
        });
    }
}
