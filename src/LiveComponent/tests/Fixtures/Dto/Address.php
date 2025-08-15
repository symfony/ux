<?php

namespace Symfony\UX\LiveComponent\Tests\Fixtures\Dto;

use Symfony\Component\Serializer\Attribute\SerializedName;

class Address
{
    public string $address;

    #[SerializedName(serializedName: 'c')]
    public string $city;
}
