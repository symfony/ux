<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Autocomplete\Tests\Unit\Form;

use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\UX\Autocomplete\Checksum\ChecksumCalculator;
use Symfony\UX\Autocomplete\Form\AutocompleteChoiceTypeExtension;

final class AutocompleteChoiceTypeExtensionTest extends TestCase
{
    public function testOptionsAsHtmlStaysTrueWithoutAutocompleteUrl()
    {
        $resolved = $this->resolveOptions([
            'options_as_html' => true,
        ]);

        $this->assertTrue($resolved['options_as_html']);
    }

    public function testOptionsAsHtmlCanBeTrueWithAutocompleteUrl()
    {
        $resolved = $this->resolveOptions([
            'options_as_html' => true,
            'autocomplete_url' => '/path/to/autocomplete',
        ]);

        $this->assertTrue($resolved['options_as_html']);
    }

    public function testOptionsAsHtmlDefaultsToFalseWithAutocompleteUrl()
    {
        $resolved = $this->resolveOptions([
            'autocomplete_url' => '/path/to/autocomplete',
        ]);

        $this->assertFalse($resolved['options_as_html']);
    }

    private function resolveOptions(array $options): array
    {
        $extension = new AutocompleteChoiceTypeExtension(new ChecksumCalculator('s3cr3t'));
        $resolver = new OptionsResolver();
        $extension->configureOptions($resolver);

        return $resolver->resolve($options);
    }
}
