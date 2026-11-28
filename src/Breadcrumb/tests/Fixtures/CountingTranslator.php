<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Breadcrumb\Tests\Fixtures;

use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class CountingTranslator implements TranslatorInterface, LocaleAwareInterface
{
    public int $calls = 0;

    private string $locale = 'en';

    public function trans(?string $id, array $parameters = [], ?string $domain = null, ?string $locale = null): string
    {
        ++$this->calls;

        return \sprintf('%s@%s', $id ?? '', $this->locale);
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }
}
