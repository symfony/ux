<?php

namespace Symfony\UX\Translator\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\UX\Translator\Tests\Kernel\EmptyAppKernel;
use Symfony\UX\Translator\Tests\Kernel\FrameworkAppKernel;

class UxTranslatorBundleTest extends TestCase
{
    public function provideKernels()
    {
        yield 'empty' => [new EmptyAppKernel('test', true)];
        yield 'framework' => [new FrameworkAppKernel('test', true)];
    }

    /**
     * @dataProvider provideKernels
     */
    public function testBootKernel(Kernel $kernel)
    {
        $kernel->boot();
        $this->assertArrayHasKey('UxTranslatorBundle', $kernel->getBundles());
    }
}
