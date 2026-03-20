<?php

declare(strict_types=1);

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Native\Tests\Twig;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\UX\Native\EventListener\NativeListener;
use Symfony\UX\Native\Twig\NativeExtension;

final class NativeExtensionTest extends TestCase
{
    public function testIsNativeReturnsTrueWhenAttributeIsTrue()
    {
        $request = Request::create('/');
        $request->attributes->set(NativeListener::NATIVE_ATTRIBUTE, true);

        $extension = $this->createExtension($request);

        self::assertTrue($extension->isNative());
    }

    public function testIsNativeReturnsFalseWhenAttributeIsFalse()
    {
        $request = Request::create('/');
        $request->attributes->set(NativeListener::NATIVE_ATTRIBUTE, false);

        $extension = $this->createExtension($request);

        self::assertFalse($extension->isNative());
    }

    public function testIsNativeReturnsFalseWhenNoRequest()
    {
        $extension = $this->createExtension(null);

        self::assertFalse($extension->isNative());
    }

    public function testIsNativeReturnsFalseWhenAttributeNotSet()
    {
        $request = Request::create('/');

        $extension = $this->createExtension($request);

        self::assertFalse($extension->isNative());
    }

    public function testGetFunctionsRegistersUxIsNative()
    {
        $extension = $this->createExtension(null);

        $functions = $extension->getFunctions();
        $functionNames = array_map(static fn ($f) => $f->getName(), $functions);

        self::assertContains('ux_is_native', $functionNames);
    }

    private function createExtension(?Request $request): NativeExtension
    {
        $requestStack = new RequestStack();
        if (null !== $request) {
            $requestStack->push($request);
        }

        return new NativeExtension($requestStack);
    }
}
