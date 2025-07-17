<?php

declare(strict_types=1);

namespace Symfony\UX\Autocomplete\Tests\Fixtures\Autocompleter;

class CustomAttributesProductAutocompleter extends CustomProductAutocompleter
{
    public function getAttributes(object $entity): array
    {
        return [
            'disabled' => true,
            'value' => 'This value should be replaced with the result of getValue()',
            'text' => 'This value should be replaced with the result of getText()',
        ];
    }
}
