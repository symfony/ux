<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\DataTable\Tests\Form;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Csrf\CsrfExtension;
use Symfony\Component\Form\Forms;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\UX\DataTable\Form\FilterFormType;

final class FilterFormTypeTest extends TestCase
{
    public function testIsAGetFormAllowingExtraFields(): void
    {
        $config = Forms::createFormFactory()->create(FilterFormType::class)->getConfig();

        self::assertSame('GET', $config->getMethod());
        self::assertTrue($config->getOption('allow_extra_fields'));
        self::assertSame('ux_data_table_filters', $config->getType()->getBlockPrefix());
    }

    public function testDisablesCsrfProtection(): void
    {
        if (!interface_exists(CsrfTokenManagerInterface::class)) {
            self::markTestSkipped('symfony/security-csrf is not installed.');
        }

        $factory = Forms::createFormFactoryBuilder()
            ->addExtension(new CsrfExtension($this->createStub(CsrfTokenManagerInterface::class)))
            ->getFormFactory();

        self::assertFalse($factory->create(FilterFormType::class)->getConfig()->getOption('csrf_protection'));
    }
}
