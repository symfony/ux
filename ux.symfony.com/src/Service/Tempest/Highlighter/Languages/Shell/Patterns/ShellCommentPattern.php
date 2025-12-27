<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service\Tempest\Highlighter\Languages\Shell\Patterns;

use Tempest\Highlight\IsPattern;
use Tempest\Highlight\Pattern;
use Tempest\Highlight\PatternTest;
use Tempest\Highlight\Tokens\TokenTypeEnum;

#[PatternTest(
    input: '# This is a comment',
    output: ['# This is a comment'],
)]
#[PatternTest(
    input: 'echo "Hello World" # Greet the world',
    output: ['# Greet the world'],
)]
class ShellCommentPattern implements Pattern
{
    use IsPattern;

    public function getPattern(): string
    {
        return '/(?P<match>#.*$)/m';
    }

    public function getTokenType(): TokenTypeEnum
    {
        return TokenTypeEnum::COMMENT;
    }
}
