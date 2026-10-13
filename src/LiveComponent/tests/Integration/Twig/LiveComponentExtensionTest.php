<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\LiveComponent\Tests\Integration\Twig;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class LiveComponentExtensionTest extends KernelTestCase
{
    public function testGetComponentUrl(): void
    {
        $rendered = self::getContainer()->get('twig')->render('component_url.html.twig', [
            'date' => new \DateTime('2022-10-06-0'),
        ]);

        $this->assertStringContainsString('/_components/component1?props=%7B%22prop1%22:null,%22prop2%22:%222022-10-06T00:00:00%2B00:00%22,%22prop3%22:null,', $rendered);
        $this->assertStringContainsString('/alt/alternate_route?', $rendered);
        $this->assertStringContainsString('http://localhost/_components/with_absolute_url?', $rendered);
    }

    #[DataProvider('provideLiveActionTemplates')]
    public function testRenderLiveAction(string $source, string $expected): void
    {
        $template = self::getContainer()->get('twig')->createTemplate($source);

        $rendered = $template->render();

        $this->assertSame($expected, trim($rendered));
    }

    public static function provideLiveActionTemplates(): iterable
    {
        yield 'printed in an HTML tag' => [
            "<button {{ live_action('save', {name: 'a\"b'}) }}>",
            '<button data-action="live#action" data-live-name-param="a&quot;b" data-live-action-param="save">',
        ];

        yield 'concatenated with a string' => [
            "{{ ('data-controller=\"foo\" ' ~ live_action('save'))|raw }}",
            'data-controller="foo" data-action="live#action" data-live-action-param="save"',
        ];

        yield 'spread into a component tag' => [
            "<twig:input_component {{ ...live_action('save', {id: 5}, {debounce: 300}) }} />",
            '<input data-action="live#action" data-live-id-param="5" data-live-action-param="debounce(300)|save" />',
        ];

        yield 'spread into a component tag with an event and a value to escape' => [
            "<twig:input_component {{ ...live_action('save', {name: 'a\"b'}, [], 'keydown.esc') }} />",
            '<input data-action="keydown.esc-&gt;live#action" data-live-name-param="a&quot;b" data-live-action-param="save" />',
        ];

        yield 'combined with another Stimulus helper' => [
            "<div {{ live_action('save')|stimulus_controller('foo') }}>",
            '<div data-controller="foo" data-action="live#action" data-live-action-param="save">',
        ];
    }
}
