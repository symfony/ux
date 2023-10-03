<?php

namespace Symfony\UX\Autocomplete\Tests\Fixtures\Autocompleter;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\UX\Autocomplete\Doctrine\EntitySearchUtil;
use Symfony\UX\Autocomplete\EntityAutocompleterInterface;
use Symfony\UX\Autocomplete\Tests\Fixtures\Entity\Product;

class CustomGroupByProductAutocompleter extends CustomProductAutocompleter
{
    public function getGroupBy(): mixed
    {
        return 'category.name';
    }
}
