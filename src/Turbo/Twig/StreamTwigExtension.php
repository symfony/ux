<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Turbo\Twig;

use Symfony\UX\Turbo\Streams\StreamAdapterInterface;
use Symfony\WebpackEncoreBundle\Twig\StimulusTwigExtension;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 *
 * @final
 * @experimental
 */
class StreamTwigExtension extends AbstractExtension
{
    /**
     * @var StimulusTwigExtension
     */
    private $stimulus;

    /**
     * @var StreamAdapterInterface
     */
    private $adapter;

    /**
     * @var array
     */
    private $options;

    public function __construct(StimulusTwigExtension $stimulus, StreamAdapterInterface $adapter, array $options)
    {
        $this->stimulus = $stimulus;
        $this->adapter = $adapter;
        $this->options = $options;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('turbo_stream_from', [$this, 'streamFrom'], ['needs_environment' => true, 'is_safe' => ['html']]),
        ];
    }

    public function streamFrom(Environment $env, string $id, array $attrs = []): string
    {
        $options = array_merge($this->options, ['id' => $id]);

        $html = '<div ';
        $html .= $this->stimulus->renderStimulusController($env, [
            $this->adapter->getStimulusControllerName() => $this->adapter->createDataValues($options),
            'symfony--ux-turbo--core' => [],
        ]).' ';

        foreach ($attrs as $name => $value) {
            $name = twig_escape_filter($env, $name, 'html_attr');
            $value = twig_escape_filter($env, $value, 'html_attr');

            if (true === $value) {
                $html .= $name.'="'.$name.'" ';
            } elseif (false !== $value) {
                $html .= $name.'="'.$value.'" ';
            }
        }

        return trim($html).'></div>';
    }
}
