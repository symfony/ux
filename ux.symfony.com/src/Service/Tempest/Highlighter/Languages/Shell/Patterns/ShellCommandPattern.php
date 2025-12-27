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
    input: 'git commit -m "Initial commit"',
    output: ['git'],
)]
#[PatternTest(
    input: 'docker run -d nginx',
    output: ['docker'],
)]
class ShellCommandPattern implements Pattern
{
    use IsPattern;

    public function getPattern(): string
    {
        return '/^(?P<match>[a-zA-Z0-9_\-\/]+)\b/';
    }

    public function getTokenType(): TokenTypeEnum
    {
        return TokenTypeEnum::KEYWORD;
    }
}
