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
    input: '-a --help --version',
    output: ['-a', '--help', '--version'],
)]
#[PatternTest(
    input: 'curl -X POST https://example.com',
    output: ['-X'],
)]
#[PatternTest(
    input: 'composer require --dev symfony/ux-toolkit',
    output: ['--dev'],
)]
class ShellOptionPattern implements Pattern
{
    use IsPattern;

    public function getPattern(): string
    {
        return '/\s+(?P<match>--?[a-zA-Z0-9_\-]+)/';
    }

    public function getTokenType(): TokenTypeEnum
    {
        return TokenTypeEnum::PROPERTY;
    }
}
