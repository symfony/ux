<?php

namespace App\Model;

class Address
{
    public string $country;
    public string $city;

    public static function create(string $country, string $city): self
    {
        $address = new self();
        $address->country = $country;
        $address->city = $city;

        return $address;
    }
}
