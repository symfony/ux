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

namespace Symfony\UX\LiveComponent\Tests\Functional\EventListener;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\LiveComponent\LiveComponentHydrator;
use Symfony\UX\LiveComponent\Tests\ContainerBC;
use Symfony\UX\LiveComponent\Tests\Fixture\Component\FormComponent1;
use Symfony\UX\TwigComponent\ComponentFactory;
use Zenstruck\Browser\Response\HtmlResponse;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * @author Jakub Caban <kuba.iluvatar@gmail.com>
 */
class ComponentWithFormTest extends KernelTestCase
{
    use ContainerBC;
    use Factories;
    use HasBrowser;
    use ResetDatabase;

    public function testHandleCheckboxChanges(): void
    {
        /** @var LiveComponentHydrator $hydrator */
        $hydrator = self::getContainer()->get('ux.live_component.component_hydrator');

        /** @var ComponentFactory $factory */
        $factory = self::getContainer()->get('ux.twig_component.component_factory');

        /** @var FormComponent1 $component */
        $component = $factory->create('form_component1');

        $dehydrated = $hydrator->dehydrate($component);
        $bareForm = [
            'text' => '',
            'textarea' => '',
            'range' => '',
            'choice' => '',
            'choice_expanded' => '',
            'choice_multiple' => ['2'],
            'checkbox_checked' => '1',
            'file' => '',
            'hidden' => '',
        ];
        $fullBareData = array_merge(
            $bareForm,
            [
                'checkbox' => null,
            ]
        );

        $this->browser()
            ->throwExceptions()
            ->get('/_components/form_component1?'.http_build_query($dehydrated))
            ->assertSuccessful()
            ->assertContains('<input type="checkbox" id="form_choice_multiple_1" name="form[choice_multiple][]" value="2" checked="checked" />')
            ->assertContains('<input type="checkbox" id="form_choice_multiple_0" name="form[choice_multiple][]" value="1" />')
            ->assertContains('<input type="checkbox" id="form_checkbox" name="form[checkbox]" required="required" value="1" />')
            ->assertContains('<input type="checkbox" id="form_checkbox_checked" name="form[checkbox_checked]" required="required" value="1" checked="checked" />')
            ->use(function (HtmlResponse $response) use (&$fullBareData, &$dehydrated, &$bareForm) {
                $data = json_decode(
                    $response->crawler()->filter('div')->first()->attr('data-live-data-value'),
                    true
                );
                self::assertEquals($fullBareData, $data['form']);

                // check both multiple fields
                $bareForm['choice_multiple'] = $fullBareData['choice_multiple'] = ['2', '1'];

                $dehydrated['form'] = $bareForm;
            })
            ->get('/_components/form_component1?'.http_build_query($dehydrated))
            ->assertContains('<input type="checkbox" id="form_choice_multiple_1" name="form[choice_multiple][]" value="2" checked="checked" />')
            ->assertContains('<input type="checkbox" id="form_choice_multiple_0" name="form[choice_multiple][]" value="1" checked="checked" />')
            ->use(function (HtmlResponse $response) use (&$fullBareData, &$dehydrated, &$bareForm) {
                $data = json_decode(
                    $response->crawler()->filter('div')->first()->attr('data-live-data-value'),
                    true
                );
                self::assertEquals($fullBareData, $data['form']);

                // uncheck multiple, check single checkbox
                $bareForm['checkbox'] = $fullBareData['checkbox'] = '1';
                $bareForm['choice_multiple'] = $fullBareData['choice_multiple'] = [];

                // uncheck previously checked checkbox
                unset($bareForm['checkbox_checked']);
                $fullBareData['checkbox_checked'] = null;

                $dehydrated['form'] = $bareForm;
            })
            ->get('/_components/form_component1?'.http_build_query($dehydrated))
            ->assertContains('<input type="checkbox" id="form_choice_multiple_1" name="form[choice_multiple][]" value="2" />')
            ->assertContains('<input type="checkbox" id="form_choice_multiple_0" name="form[choice_multiple][]" value="1" />')
            ->assertContains('<input type="checkbox" id="form_checkbox" name="form[checkbox]" required="required" value="1" checked="checked" />')
            ->assertContains('<input type="checkbox" id="form_checkbox_checked" name="form[checkbox_checked]" required="required" value="1" />')
            ->use(function (HtmlResponse $response) use ($fullBareData) {
                $data = json_decode(
                    $response->crawler()->filter('div')->first()->attr('data-live-data-value'),
                    true
                );
                self::assertEquals($fullBareData, $data['form']);
            })
        ;
    }
}
