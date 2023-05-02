<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Symfony\UX\LiveComponent\Tests\Functional\Form;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\LiveComponent\Tests\LiveComponentTestHelper;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

class ValidatableComponentTraitTest extends KernelTestCase
{
    use Factories;
    use HasBrowser;
    use LiveComponentTestHelper;
    use ResetDatabase;

    public function testFormValuesRebuildAfterFormChanges(): void
    {
        $dehydratedProps = $this->dehydrateComponent($this->mountComponent('validating_component'))->getProps();

        $createUrl = function (array $props, array $updated = []) {
            return '/_components/validating_component?props='.urlencode(json_encode($props)).'&updated='.urlencode(json_encode($updated));
        };

        $browser = $this->browser();
        $browser
            ->get($createUrl($dehydratedProps))
            ->assertSuccessful()
            ->assertContains('Has Error: no')
            ->assertContains('Error: ""')
        ;

        $crawler = $browser
            ->get($createUrl($dehydratedProps, ['name' => 'h', 'validatedFields' => ['name']]))
            ->assertSuccessful()
            ->assertContains('Has Error: yes')
            ->assertContains('Error: "This value is too short. It should have 3 characters or more."')
            ->crawler()
        ;

        $div = $crawler->filter('[data-controller="live"]');
        $dehydratedProps = json_decode($div->attr('data-live-props-value'), true);

        // make a normal GET request with no updates and verify validation still happens
        $browser
            ->get($createUrl($dehydratedProps))
            ->assertSuccessful()
            ->assertContains('Has Error: yes')
        ;

        $browser
            ->post('/_components/validating_component/resetValidationAction', [
                'body' => [
                    'data' => json_encode([
                        'props' => $dehydratedProps,
                    ]),
                ],
            ])
            ->assertSuccessful()
            ->assertContains('Has Error: no')
        ;
    }
}
