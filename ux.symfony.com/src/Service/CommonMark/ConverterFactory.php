<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service\CommonMark;

use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;
use League\CommonMark\Extension\Mention\MentionExtension;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Tempest\Highlight\CommonMark\HighlightExtension;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
#[AsDecorator('twig.markdown.league_common_mark_converter_factory')]
final class ConverterFactory
{
    public function __invoke(): CommonMarkConverter
    {
        $converter = new CommonMarkConverter([
            'mentions' => [
                'github_handle' => [
                    'prefix' => '@',
                    'pattern' => '[a-z\d](?:[a-z\d]|-(?=[a-z\d])){0,38}(?!\w)',
                    'generator' => 'https://github.com/%s',
                ],
                'github_issue' => [
                    'prefix' => '#',
                    'pattern' => '\d+',
                    'generator' => 'https://github.com/symfony/ux/issues/%d',
                ],
            ],
            'external_link' => [
                'internal_hosts' => ['/(^|\.)symfony\.com$/'],
            ],
        ]);

        $converter->getEnvironment()
            ->addExtension(new ExternalLinkExtension())
            ->addExtension(new MentionExtension())
            ->addExtension(new HighlightExtension())
        ;

        return $converter;
    }
}
