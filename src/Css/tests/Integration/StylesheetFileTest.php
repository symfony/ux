<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Css\Tests\Integration;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\UX\Css\Tests\Fixtures\TestKernel;
use Twig\Error\SyntaxError;

final class StylesheetFileTest extends TestCase
{
    private const CONFIG = [
        'tokens' => ['colors' => ['red' => '#f00'], 'spacing' => ['md' => '1rem']],
        'semantic_tokens' => ['colors' => ['primary' => '{colors.red}', 'fg' => '{colors.red}']],
    ];

    private string $projectDir;

    protected function setUp(): void
    {
        $this->projectDir = sys_get_temp_dir().'/ux_css_project_'.uniqid();
        new Filesystem()->mkdir($this->projectDir.'/templates');

        $this->writeTemplate('home.html.twig', '<p class="{{ css({ p: \'md\', color: \'primary\' }) }}"></p>');
        $this->writeTemplate('card.html.twig', '<div class="{{ css({ _hover: { bg: \'fg\' } }) }}"></div>');
        $this->writeTemplate('plain.html.twig', '<p>No styles here</p>');
    }

    protected function tearDown(): void
    {
        new Filesystem()->remove($this->projectDir);
    }

    public function testCacheWarmupWritesTheStylesOfEveryTemplateInCompactForm(): void
    {
        $this->bootKernel(debug: false);

        $css = $this->readStylesheet();

        $this->assertStringStartsWith('@layer tokens,utilities;', $css);
        $this->assertStringContainsString('.p_md{padding:var(--spacing-md)}', $css);
        $this->assertStringContainsString('.hover\:bg_fg:is(:hover, [data-hover]){background:var(--colors-fg)}', $css);
    }

    public function testCacheWarmupFailsOnAnInvalidCssCall(): void
    {
        $this->writeTemplate('broken.html.twig', '{{ css({ colr: \'primary\' }) }}');

        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('Unknown property "colr". Did you mean "color"');

        $this->bootKernel(debug: false);
    }

    public function testCacheWarmupFailsOnEveryCssError(): void
    {
        $this->writeTemplate('broken.html.twig', '{{ css({ p: \'md\' }, { color: \'fg\' }) }}');

        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('css() takes a single hash of styles');

        $this->bootKernel(debug: false);
    }

    public function testADebugKernelBootsWhenATemplateHasAValueTheEngineRefuses(): void
    {
        $this->writeTemplate('broken.html.twig', '{{ css({ zIndex: 1e999 }) }}');

        $this->bootKernel();

        $this->assertStringContainsString('.p_md {', $this->readStylesheet());
    }

    public function testTheDevListenerWritesTheFileBeforeTheResponse(): void
    {
        $kernel = $this->bootKernel();
        new Filesystem()->remove($this->stylesheetPath());

        $kernel->handle(Request::create('/'));

        $this->assertStringContainsString(".p_md {\n    padding: var(--spacing-md);\n}", $this->readStylesheet());
    }

    public function testOnlyChangedTemplatesAreReadAgain(): void
    {
        $kernel = $this->bootKernel();
        $dumper = $this->service($kernel, 'ux_css.stylesheet_dumper');
        $dumper->update();

        $template = '<div class="{{ css({ _hover: { bg: \'primary\' } }) }}"></div>';
        $this->writeTemplate('card.html.twig', $template, time());
        $readAgain = $dumper->update();

        $this->assertSame(['card.html.twig'], $readAgain);
        $this->assertStringContainsString('hover\:bg_primary', $this->readStylesheet());
        $this->assertStringNotContainsString('hover\:bg_fg', $this->readStylesheet());
    }

    public function testALongRunningProcessSeesDeletedAndNewTemplates(): void
    {
        $kernel = $this->bootKernel();
        $dumper = $this->service($kernel, 'ux_css.stylesheet_dumper');
        $dumper->update();
        new Filesystem()->remove($this->projectDir.'/templates/card.html.twig');
        $this->writeTemplate('new.html.twig', '<p class="{{ css({ color: \'red\' }) }}"></p>');

        $dumper->update();
        $dumper->update();

        $this->assertStringNotContainsString('hover\:bg_fg', $this->readStylesheet());
        $this->assertStringContainsString('.c_red {', $this->readStylesheet());
    }

    public function testAConfigChangeCompilesTheTemplatesAgain(): void
    {
        $buildDir = TestKernel::temporaryDirectory();
        $kernel = $this->bootKernel(buildDir: $buildDir);
        $this->service($kernel, 'twig')->render('home.html.twig');
        $config = ['tokens' => self::CONFIG['tokens'], 'semantic_tokens' => ['colors' => ['fg' => '{colors.red}']]];
        $changedKernel = $this->bootKernel(config: $config, buildDir: $buildDir);
        $twig = $this->service($changedKernel, 'twig');

        $this->expectException(SyntaxError::class);
        $this->expectExceptionMessage('Unknown colors token "primary"');

        $twig->render('home.html.twig');
    }

    public function testTheFileIsNotRewrittenWhenNothingChanged(): void
    {
        $kernel = $this->bootKernel();
        $kernel->handle(Request::create('/'));
        file_put_contents($this->stylesheetPath(), 'untouched');

        $kernel->handle(Request::create('/'));

        $this->assertSame('untouched', $this->readStylesheet());
    }

    public function testProfilerRequestsAreIgnored(): void
    {
        $kernel = $this->bootKernel();
        new Filesystem()->remove($this->stylesheetPath());

        $kernel->handle(Request::create('/_wdt/abcdef'));
        $kernel->handle(Request::create('/_profiler/abcdef'));

        $this->assertFileDoesNotExist($this->stylesheetPath());
    }

    public function testADeletedTemplateLosesItsRules(): void
    {
        $kernel = $this->bootKernel();
        $kernel->handle(Request::create('/'));
        new Filesystem()->remove($this->projectDir.'/templates/card.html.twig');

        $kernel->handle(Request::create('/'));

        $this->assertStringNotContainsString('hover\:bg_fg', $this->readStylesheet());
        $this->assertStringContainsString('.p_md {', $this->readStylesheet());
    }

    public function testABrokenTemplateKeepsItsRulesAndTheOthers(): void
    {
        $kernel = $this->bootKernel();
        $kernel->handle(Request::create('/'));
        $this->writeTemplate('home.html.twig', '<p class="{{ css({ p: ', time());

        $response = $kernel->handle(Request::create('/'));

        $this->assertSame(404, $response->getStatusCode());
        $this->assertStringContainsString('.p_md {', $this->readStylesheet());
        $this->assertStringContainsString('hover\:bg_fg', $this->readStylesheet());
    }

    public function testAssetMapperServesTheFileWithADigestThatFollowsItsContent(): void
    {
        $kernel = $this->bootKernel();
        $kernel->handle(Request::create('/'));
        $before = $this->service($kernel, 'asset_mapper')->getAsset('ux_css/styles.css')?->digest;

        $this->writeTemplate('card.html.twig', '<div class="{{ css({ color: \'fg\' }) }}"></div>', time());
        $kernel->handle(Request::create('/'));
        $freshKernel = $this->bootKernel();
        $after = $this->service($freshKernel, 'asset_mapper')->getAsset('ux_css/styles.css')?->digest;

        $this->assertNotNull($before);
        $this->assertNotSame($before, $after);
    }

    public function testStaticCssRulesAreWrittenToo(): void
    {
        $rule = ['properties' => ['color' => ['*']], 'conditions' => ['hover']];
        $config = self::CONFIG + ['static_css' => ['css' => [$rule]]];

        $this->bootKernel(config: $config);

        $this->assertStringContainsString('.hover\:c_red:is(:hover, [data-hover]) {', $this->readStylesheet());
        $this->assertStringContainsString('.c_primary {', $this->readStylesheet());
    }

    public function testDynamicClassesWithoutCssAreLoggedInDebug(): void
    {
        $kernel = $this->bootKernel();
        $kernel->handle(Request::create('/'));
        $logger = $this->service($kernel, 'logger');
        $logger->cleanLogs();
        $template = $this->service($kernel, 'twig')->createTemplate('{{ css({ color: color }) }}');

        $template->render(['color' => 'fg']);
        $template->render(['color' => 'primary']);

        $messages = array_column($logger->cleanLogs(), 1);
        $expected = 'No CSS was generated for "c_fg"; use it in a template or cover it with ux_css.static_css.';
        $this->assertSame([$expected], $messages);
    }

    private function bootKernel(
        bool $debug = true,
        array $config = self::CONFIG,
        ?string $buildDir = null,
    ): KernelInterface {
        $kernel = new TestKernel($config, 'test', $debug, $this->projectDir, $buildDir);
        $kernel->boot();

        return $kernel;
    }

    private function service(KernelInterface $kernel, string $id): object
    {
        return $kernel->getContainer()->get('test.service_container')->get($id);
    }

    private function writeTemplate(string $name, string $code, ?int $modifiedAt = null): void
    {
        $path = $this->projectDir.'/templates/'.$name;
        file_put_contents($path, $code);
        touch($path, $modifiedAt ?? time() - 60);
    }

    private function stylesheetPath(): string
    {
        return $this->projectDir.'/var/ux_css/styles.css';
    }

    private function readStylesheet(): string
    {
        return file_get_contents($this->stylesheetPath());
    }
}
