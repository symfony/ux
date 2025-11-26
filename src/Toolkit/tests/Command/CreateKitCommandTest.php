<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Tests\Command;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Zenstruck\Console\Test\InteractsWithConsole;

class CreateKitCommandTest extends KernelTestCase
{
    use InteractsWithConsole;

    private string $cwd;
    private Filesystem $filesystem;
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->cwd = getcwd();

        $this->filesystem = new Filesystem();
        $this->tmpDir = $this->filesystem->tempnam(sys_get_temp_dir(), 'ux_toolkit_github_');
        $this->filesystem->remove($this->tmpDir);
        $this->filesystem->mkdir($this->tmpDir);

        chdir($this->tmpDir);
    }

    protected function tearDown(): void
    {
        chdir($this->cwd);
        $this->filesystem->remove($this->tmpDir);
    }

    public function testShouldBeAbleToCreateAKit()
    {
        $this->bootKernel();
        $this->consoleCommand('ux:toolkit:create-kit')
            ->addInput('MyKit')
            ->addInput('http://example.com')
            ->addInput('MIT')
            ->execute()
            ->assertSuccessful()
            ->assertOutputContains('Your kit has been created successfully, happy coding!')
        ;

        $this->assertStringEqualsFile(
            $this->tmpDir.'/manifest.json',
            <<<'JSON'
                {
                    "$schema": "../vendor/symfony/ux-toolkit/schema-kit-v1.json",
                    "name": "MyKit",
                    "description": "A custom kit for Symfony UX Toolkit.",
                    "homepage": "http://example.com",
                    "license": "MIT"
                }
                JSON
        );
        $this->assertStringEqualsFile(
            $this->tmpDir.'/Button/manifest.json',
            <<<'JSON'
                {
                    "$schema": "../vendor/symfony/ux-toolkit/schema-kit-recipe-v1.json",
                    "name": "Button",
                    "description": "A clickable element that triggers actions or events, supporting various styles and states.",
                    "copy-files": {
                        "templates/": "templates/"
                    },
                    "dependencies": {
                        "composer": [
                            "twig/extra-bundle",
                            "twig/html-extra:^3.12.0",
                            "tales-from-a-dev/twig-tailwind-extra:^1.0.0"
                        ]
                    }
                }
                JSON
        );
        $this->assertStringEqualsFile(
            $this->tmpDir.'/Button/templates/components/Button.html.twig',
            <<<'TWIG'
                {% props type = 'button', variant = 'default' %}
                {%- set style = html_cva(
                    base: 'inline-flex items-center',
                    variants: {
                        variant: {
                            default: "bg-primary text-primary-foreground hover:bg-primary/90",
                            secondary: "bg-secondary text-secondary-foreground hover:bg-secondary/80",
                        },
                    },
                ) -%}

                <button
                    class="{{ style.apply({ variant }, attributes.render('class'))|tailwind_merge }}"
                    {{ attributes.defaults({ type: 'submit'}) }}
                >
                    {%- block content %}{% endblock -%}
                </button>
                TWIG
        );
    }
}
