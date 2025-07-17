<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\Tests\Integration\Twig;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\TwigComponent\Twig\PropsNode;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Node\TextNode;
use Twig\Source;

/**
 * @author Simon André <smn.andre@gmail.com>
 *
 * @internal
 */
class ComponentPropsParserTest extends KernelTestCase
{
    /**
     * @dataProvider providePropsData
     */
    public function testPropsData(string $template, array $props, string $text): void
    {
        $loader = new ArrayLoader(['template' => $template]);

        /** @var Environment $twig */
        $twig = self::getContainer()->get(Environment::class);
        $twig->setLoader($loader);

        $tokenStream = $twig->tokenize(new Source($template, 'template'));
        $foo = $twig->parse($tokenStream);

        $body = $foo->getNode('body')->getNode('0');
        $this->assertTrue($body->hasNode(0));

        $propsNode = $body->getNode(0);
        $this->assertInstanceOf(PropsNode::class, $propsNode);
        $this->assertTrue($propsNode->hasAttribute('names'));

        foreach ($props as $name => $value) {
            $this->assertContains($name, $propsNode->getAttribute('names'));
            if (null === $value) {
                $this->assertFalse($propsNode->hasNode($name));
                continue;
            }
            $this->assertTrue($propsNode->hasNode($name));
            $this->assertTrue($propsNode->getNode($name)->hasAttribute('value'));
            $this->assertSame($value, $propsNode->getNode($name)->getAttribute('value'));
        }

        $this->assertTrue($body->hasNode(1));
        $this->assertInstanceOf(TextNode::class, $body->getNode(1));
        $this->assertTrue($body->getNode(1)->hasAttribute('data'));
        $this->assertSame($text, $body->getNode(1)->getAttribute('data'));
    }

    /**
     * @return iterable<string, array{0: string, 1: array<string, string|int|null>, 2: string}>
     */
    public static function providePropsData(): iterable
    {
        yield 'One Prop with value' => [
            '{% props propA=123 %} foo ',
            [
                'propA' => 123,
            ],
            ' foo ',
        ];
        yield 'One Prop without value' => [
            '{% props propA %} foo ',
            [
                'propA' => null,
            ],
            ' foo ',
        ];
        yield 'No Props with values' => [
            '{% props propA, propB %} foo ',
            [
                'propA' => null,
                'propB' => null,
            ],
            ' foo ',
        ];
        yield 'All Props with values' => [
            '{% props propA=1, propB=2 %} foo ',
            [
                'propA' => 1,
                'propB' => 2,
            ],
            ' foo ',
        ];
        yield 'Some Props with values' => [
            '{% props propA, propB=2 %} foo ',
            [
                'propA' => null,
                'propB' => 2,
            ],
            ' foo ',
        ];
        yield 'One Prop with value and trailing comma' => [
            '{% props propA=123, %} foo ',
            [
                'propA' => 123,
            ],
            ' foo ',
        ];
        yield 'One Prop without value and trailing comma' => [
            '{% props propA, %} foo ',
            [
                'propA' => null,
            ],
            ' foo ',
        ];
        yield 'No Props with values and trailing comma' => [
            '{% props propA, propB, %} foo ',
            [
                'propA' => null,
                'propB' => null,
            ],
            ' foo ',
        ];
        yield 'All Props with values and trailing comma' => [
            '{% props propA=1, propB=2, %} foo ',
            [
                'propA' => 1,
                'propB' => 2,
            ],
            ' foo ',
        ];
        yield 'Some Props with values and trailing comma' => [
            '{% props propA, propB=2, %} foo ',
            [
                'propA' => null,
                'propB' => 2,
            ],
            ' foo ',
        ];
    }
}
