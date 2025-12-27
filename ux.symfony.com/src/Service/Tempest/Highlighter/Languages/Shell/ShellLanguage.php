<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service\Tempest\Highlighter\Languages\Shell;

use App\Service\Tempest\Highlighter\Languages\Shell\Patterns\ShellCommandPattern;
use App\Service\Tempest\Highlighter\Languages\Shell\Patterns\ShellCommentPattern;
use App\Service\Tempest\Highlighter\Languages\Shell\Patterns\ShellOptionPattern;
use Tempest\Highlight\Languages\Base\BaseLanguage;

class ShellLanguage extends BaseLanguage
{
    public function getName(): string
    {
        return 'shell';
    }

    public function getAliases(): array
    {
        return ['bash', 'sh'];
    }

    public function getPatterns(): array
    {
        return [
            ...parent::getPatterns(),

            new ShellCommentPattern(),
            new ShellCommandPattern(),
            new ShellOptionPattern(),
        ];
    }
}
