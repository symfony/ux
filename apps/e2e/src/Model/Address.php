<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
