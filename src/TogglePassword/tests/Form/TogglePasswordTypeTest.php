<?php

declare(strict_types=1);

namespace Symfony\UX\TogglePassword\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\UX\TogglePassword\Tests\Kernel\TwigAppKernel;
use Twig\Environment;

class TogglePasswordTypeTest extends TestCase
{
    public function testRenderFormWithToggle(): void
    {
        $container = $this->givenServiceContainer();
        $form = $container->get(FormFactoryInterface::class)->createBuilder()
            ->add('password', PasswordType::class, ['toggle' => true])
            ->getForm()
        ;

        $rendered = $container->get(Environment::class)->render('toggle_password_form.html.twig', ['form' => $form->createView()]);

        self::assertSame('<form name="form" method="post"><div id="form"><div><label for="form_password" class="required">Password</label><div class="toggle-password-container"><input type="password" id="form_password" name="form[password]" required="required" data-controller="symfony--ux-toggle-password--toggle-password" data-symfony--ux-toggle-password--toggle-password-hidden-label-value="Hide" data-symfony--ux-toggle-password--toggle-password-visible-label-value="Show" data-symfony--ux-toggle-password--toggle-password-hidden-icon-value="Default" data-symfony--ux-toggle-password--toggle-password-visible-icon-value="Default" data-symfony--ux-toggle-password--toggle-password-button-classes-value="[&quot;toggle-password-button&quot;]" /></div></div></div></form>
', $rendered);
    }

    public function testRenderFormWithoutToggle(): void
    {
        $container = $this->givenServiceContainer();
        $form = $container->get(FormFactoryInterface::class)->createBuilder()
            ->add('password', PasswordType::class)
            ->getForm()
        ;

        $rendered = $container->get(Environment::class)->render('toggle_password_form.html.twig', ['form' => $form->createView()]);

        self::assertSame('<form name="form" method="post"><div id="form"><div><label for="form_password" class="required">Password</label><input type="password" id="form_password" name="form[password]" required="required" /></div></div></form>
', $rendered);
    }

    private function givenServiceContainer(): ?object
    {
        $kernel = new TwigAppKernel('test', true);
        $kernel->boot();

        return $kernel->getContainer()->get('test.service_container');
    }
}
