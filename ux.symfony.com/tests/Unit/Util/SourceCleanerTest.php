<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Tests\Unit\Util;

use App\Util\SourceCleaner;
use PHPUnit\Framework\TestCase;

class SourceCleanerTest extends TestCase
{
    public function testItRemovesNamespaceAndPhpTag(): void
    {
        $source = <<<EOF
            <?php

            namespace App\Bar;

            class Foo
            {
            }
            EOF;

        $expected = <<<EOF
            class Foo
            {
            }
            EOF;

        $this->assertSame($expected, SourceCleaner::cleanupPhpFile($source));
    }

    public function testItRemovesClass(): void
    {
        $source = <<<EOF
            <?php

            namespace App\Bar;

            class Foo
            {
                public function foo()
                {
                    \$foo = '';
                    \$bar = function() use (\$foo) {};
                }
            }
            EOF;

        $expected = <<<EOF
            public function foo()
            {
                \$foo = '';
                \$bar = function() use (\$foo) {};
            }
            EOF;

        $this->assertSame($expected, SourceCleaner::cleanupPhpFile($source, removeClass: true));
    }

    public function testItExtractsTwigBlock(): void
    {
        $source = <<<EOF
            {% extends 'ux_packages/package.html.twig' %}

            {% block component_header %}
                {% component PackageHeader with {
                    package: 'cropperjs',
                    eyebrowText: 'Craft the perfect image'
                } %}
                    {% block title_header %}
                        Crop Photos with
                        <span class="playfair ps-2">Cropper.js</span>
                    {% endblock %}
                {% endcomponent %}
            {% endblock %}

            {% block demo_content %}
                Content of target block.
                <div>
                    <p>Some more content that is indented</p>
                    {% block tricky_inner_block %}Foo{% endblock %}
                </div>
            {% endblock %}
            EOF;

        $expected = <<<EOF
            {% extends 'base.html.twig' %}

            {% block body %}
                Content of target block.
                <div>
                    <p>Some more content that is indented</p>
                    {% block tricky_inner_block %}Foo{% endblock %}
                </div>
            {% endblock %}
            EOF;

        $this->assertSame($expected, SourceCleaner::extractTwigBlock($source, 'demo_content'));
    }

    public function testItRemovesExcessHtml(): void
    {
        $input = <<<EOF
            <div class="p-4 markdown-container shadow-blur shadow-blur--rainbow mt-5 row" data-controller="markdown">
                <div class="col-12 col-md-5">
                    <textarea rows="3" class="form-control" aria-label="Type markdown into this box"
                        data-markdown-target="input"
                    >Writing JavaScript is a **dream** with Stimulus 🤩</textarea>
                </div>
                <div class="col-12 col-md-2 text-center">
                    <button class="btn btn-sm btn-dark mt-3" data-action="markdown#render">
                        Convert <i class="fa fa-arrow-right"></i>
                    </button>
                </div>
                <div class="col-12 col-md-5 mt-3 mt-md-0">
                    <div style="min-height: 86px;" class="markdown-form-render-container p-2" data-markdown-target="preview">
                        <small class="fw-light">(click "Convert")</small>
                    </div>
                </div>
            </div>
            EOF;

        $expected = <<<EOF
            <div data-controller="markdown">
                <textarea
                    data-markdown-target="input"
                >Writing JavaScript is a **dream** with Stimulus 🤩</textarea>
                <button data-action="markdown#render">
                    Convert <i></i>
                </button>
                <div data-markdown-target="preview">
                    <small>(click "Convert")</small>
                </div>
            </div>
            EOF;

        $this->assertSame($expected, SourceCleaner::removeExcessHtml($input));
    }
}
