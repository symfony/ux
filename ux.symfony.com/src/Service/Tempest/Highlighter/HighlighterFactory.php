<?php

declare(strict_types=1);

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service\Tempest\Highlighter;

use App\Service\Tempest\Highlighter\Languages\Shell\ShellLanguage;
use Tempest\Highlight\Highlighter;
use Tempest\Highlight\Injection;
use Tempest\Highlight\Languages\Base\Injections\DeletionInjection;
use Tempest\Highlight\Languages\Twig\TwigLanguage;

final readonly class HighlighterFactory
{
    public static function create(): Highlighter
    {
        $highlighter = new Highlighter();

        $highlighter->addLanguage(new ShellLanguage());
        $highlighter->addLanguage(new class extends TwigLanguage {
            public function getInjections(): array
            {
                // The `{-` and `-}` syntaxes from DeletionInjection conflicts with Twig's own syntax
                // @see https://github.com/tempestphp/highlight/issues/182
                return array_filter(
                    parent::getInjections(),
                    static fn (Injection $injection) => !$injection instanceof DeletionInjection
                );
            }
        });

        return $highlighter;
    }
}
