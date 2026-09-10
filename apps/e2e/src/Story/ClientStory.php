<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Story;

use App\Factory\ClientFactory;
use Zenstruck\Foundry\Attribute\AsFixture;
use Zenstruck\Foundry\Story;

#[AsFixture(name: 'disclose')]
final class ClientStory extends Story
{
    public function build(): void
    {
        ClientFactory::createSequence([
            ['name' => 'Bruce Wayne', 'email' => 'bruce@wayne.example', 'phone' => '+1 202 555 0100'],
            ['name' => 'Diana Prince', 'email' => 'diana@wayne.example', 'phone' => '+1 202 555 0101'],
            ['name' => 'Clark Kent', 'email' => 'clark@wayne.example', 'phone' => '+1 202 555 0102'],
        ]);
    }
}
