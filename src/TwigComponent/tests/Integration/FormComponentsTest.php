<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\TwigComponent\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Twig\Environment;

final class FormComponentsTest extends KernelTestCase
{
    public function testRendersWholeForm(): void
    {
        $form = $this->createForm();

        self::assertSame(
            '<form name="form" method="post"><div id="form"><div><label for="form_name" class="required">Name</label><input type="text" id="form_name" name="form[name]" required="required" aria-describedby="form_name_help" /><div id="form_name_help" class="help-text">i will help you</div></div></div></form>',
            self::getContainer()->get(Environment::class)->createTemplate('<twig:Form :form="form" />')->render(['form' => $form->createView()]),
        );
    }

    public function testRendersPassedFieldToFormAsContent(): void
    {
        $form = $this->createForm();

        self::assertSame(
            '<form name="form" method="post"><div><label for="form_name" class="required">Name</label><input type="text" id="form_name" name="form[name]" required="required" aria-describedby="form_name_help" /><div id="form_name_help" class="help-text">i will help you</div></div></form>',
            self::getContainer()->get(Environment::class)->createTemplate('<twig:Form :form="form"><twig:Form:Row :field="form.name" /></twig:Form>')->render(['form' => $form->createView()]),
        );
    }

    public function testRendersPassedFieldToFormAsContentUsingAllComponents(): void
    {
        $form = $this->createForm();

        self::assertSame(
            '<form name="form" method="post"><div><label for="form_name" class="required">Name</label><input type="text" id="form_name" name="form[name]" required="required" /><div id="form_name_help" class="help-text">i will help you</div></div></form>',
            self::getContainer()->get(Environment::class)->createTemplate('<twig:Form :form="form"><div><twig:Form:Label :field="form.name" /><twig:Form:Errors :field="form.name" /><twig:Form:Widget :field="form.name" /><twig:Form:Help :field="form.name" /></div></twig:Form>')->render(['form' => $form->createView()]),
        );
    }

    public function testRendersFormRow(): void
    {
        $form = $this->createForm();

        self::assertSame(
            '<div class="custom-row"><label for="form_name" class="required">Name</label><input type="text" id="form_name" name="form[name]" required="required" aria-describedby="form_name_help" /><div id="form_name_help" class="help-text">i will help you</div></div>',
            self::getContainer()->get(Environment::class)->createTemplate('<twig:Form:Row :field="form.name" class="custom-row" />')->render(['form' => $form->createView()]),
        );
    }

    public function testRendersFormLabel(): void
    {
        $form = $this->createForm();

        self::assertSame(
            '<label class="custom-label required" for="form_name">Name</label>',
            self::getContainer()->get(Environment::class)->createTemplate('<twig:Form:Label :field="form.name" class="custom-label" />')->render(['form' => $form->createView()]),
        );
    }

    public function testRendersFormLabelWithCustomLabel(): void
    {
        $form = $this->createForm();

        self::assertSame(
            '<label for="form_name" class="required">Your name</label>',
            self::getContainer()->get(Environment::class)->createTemplate('<twig:Form:Label :field="form.name" label="Your name" />')->render(['form' => $form->createView()]),
        );
    }

    public function testRendersFormWidget(): void
    {
        $form = $this->createForm();

        self::assertSame(
            '<input type="text" id="form_name" name="form[name]" required="required" class="custom-widget" />',
            self::getContainer()->get(Environment::class)->createTemplate('<twig:Form:Widget :field="form.name" class="custom-widget" />')->render(['form' => $form->createView()]),
        );
    }

    public function testRendersFormHelp(): void
    {
        $form = $this->createForm();

        self::assertSame(
            '<div id="form_name_help" class="custom-help help-text">i will help you</div>',
            self::getContainer()->get(Environment::class)->createTemplate('<twig:Form:Help :field="form.name" class="custom-help" />')->render(['form' => $form->createView()]),
        );
    }

    /**
     * @testWith [false, ""]
     *           [true, "<ul><li>invalid name</li></ul>"]
     */
    public function testRendersFormErrors(bool $withError, string $expected): void
    {
        $form = $this->createForm();
        if ($withError) {
            $form->get('name')->addError(new FormError('invalid name'));
        }

        self::assertSame(
            $expected,
            self::getContainer()->get(Environment::class)->createTemplate('<twig:Form:Errors :field="form.name" />')->render(['form' => $form->createView()]),
        );
    }

    private function createForm(): FormInterface
    {
        return self::getContainer()->get(FormFactoryInterface::class)->createBuilder()
            ->add('name', TextType::class, ['help' => 'i will help you'])
            ->getForm();
    }
}
