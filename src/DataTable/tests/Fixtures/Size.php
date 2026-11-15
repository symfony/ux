<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DataTable\Tests\Fixtures;

use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

enum Size implements TranslatableInterface
{
    case Small;
    case Large;

    public function trans(TranslatorInterface $translator, ?string $locale = null): string
    {
        return $translator->trans('size.'.strtolower($this->name), [], 'sizes', $locale);
    }
}
