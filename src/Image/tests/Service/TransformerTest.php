<?php

namespace Symfony\UX\Image\Tests\Service;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Image\Service\Transformer;

class TransformerTest extends TestCase
{
    private Transformer $transformer;

    protected function setUp(): void
    {
        $this->transformer = new Transformer([
            // 'xs' => 320, Mobile Portrait BrowserStack
            'sm' => 640,
            'md' => 768,
            'lg' => 1024,
            'xl' => 1280,
            // 1366 Top Common Screen Resolutions Worldwide in 2024
            // 1440 Mobile Portrait BrowserStack
            // 1512 MacBook Pro 14” 2021
            '2xl' => 1536, // Top Common Screen Resolutions Worldwide in 2024
            // '3xl' => 1920, // DELL U2515H, Top Common Screen Resolutions Worldwide in 2024
            // 1600 DELL U2515H
            // 1800 MacBook Pro 14” 2021
            // 2048 DELL U2515H
            // '4xl' => 2560 // DELL U2515H, Tablet Landscape
        ]);
    }

    /**
     * @dataProvider provideWidthStrings
     */
    public function testParseWidth(string $input, array $expected): void
    {
        $result = $this->transformer->parseWidth($input);
        $this->assertEquals($expected, $result);
    }

    public static function provideWidthStrings(): array
    {
        return [
            'fixed width' => [
                '300',
                [
                    'default' => ['value' => 300, 'vw' => '0'],
                    'sm' => ['value' => 300, 'vw' => '0'],
                    'md' => ['value' => 300, 'vw' => '0'],
                    'lg' => ['value' => 300, 'vw' => '0'],
                    'xl' => ['value' => 300, 'vw' => '0'],
                    '2xl' => ['value' => 300, 'vw' => '0'],
                ],
            ],
            'fixed width large' => [
                '1000',
                [
                    'default' => ['value' => 1000, 'vw' => '0'],
                    'sm' => ['value' => 1000, 'vw' => '0'],
                    'md' => ['value' => 1000, 'vw' => '0'],
                    'lg' => ['value' => 1000, 'vw' => '0'],
                    'xl' => ['value' => 1000, 'vw' => '0'],
                    '2xl' => ['value' => 1000, 'vw' => '0'],
                ],
            ],
            'fixed breakpoints' => [
                'sm:50 md:100 lg:200',
                [
                    'default' => ['value' => 50, 'vw' => '0'],
                    'sm' => ['value' => 50, 'vw' => '0'],
                    'md' => ['value' => 100, 'vw' => '0'],
                    'lg' => ['value' => 200, 'vw' => '0'],
                    'xl' => ['value' => 200, 'vw' => '0'],
                    '2xl' => ['value' => 200, 'vw' => '0'],
                ],
            ],
            'fullscreen' => [
                '100vw',
                [
                    'default' => ['value' => 640, 'vw' => '100'],
                    'sm' => ['value' => 640, 'vw' => '100'],
                    'md' => ['value' => 768, 'vw' => '100'],
                    'lg' => ['value' => 1024, 'vw' => '100'],
                    'xl' => ['value' => 1280, 'vw' => '100'],
                    '2xl' => ['value' => 1536, 'vw' => '100'],
                ],
            ],
            'halfscreen and fixed' => [
                '50vw lg:400px',
                [
                    'default' => ['value' => 320, 'vw' => '50'],
                    'sm' => ['value' => 320, 'vw' => '50'],
                    'md' => ['value' => 384, 'vw' => '50'],
                    'lg' => ['value' => 400, 'vw' => '0'],
                    'xl' => ['value' => 400, 'vw' => '0'],
                    '2xl' => ['value' => 400, 'vw' => '0'],
                ],
            ],
            'mixed values' => [
                '400 sm:500 md:100vw',
                [
                    'default' => ['value' => 400, 'vw' => '0'],
                    'sm' => ['value' => 500, 'vw' => '0'],
                    'md' => ['value' => 768, 'vw' => '100'],
                    'lg' => ['value' => 1024, 'vw' => '100'],
                    'xl' => ['value' => 1280, 'vw' => '100'],
                    '2xl' => ['value' => 1536, 'vw' => '100'],
                ],
            ],
            'mixed values with gap' => [
                '100 lg:100vw',
                [
                    'default' => ['value' => 100, 'vw' => '0'],
                    'sm' => ['value' => 100, 'vw' => '0'],
                    'md' => ['value' => 100, 'vw' => '0'],
                    'lg' => ['value' => 1024, 'vw' => '100'],
                    'xl' => ['value' => 1280, 'vw' => '100'],
                    '2xl' => ['value' => 1536, 'vw' => '100'],
                ],
            ],
            'vw to fixed width' => [
                '100vw md:100',
                [
                    'default' => ['value' => 640, 'vw' => '100'],
                    'sm' => ['value' => 640, 'vw' => '100'],
                    'md' => ['value' => 100, 'vw' => '0'],
                    'lg' => ['value' => 100, 'vw' => '0'],
                    'xl' => ['value' => 100, 'vw' => '0'],
                    '2xl' => ['value' => 100, 'vw' => '0'],
                ],
            ],
            'large fixed to vw' => [
                '1000 lg:100vw',
                [
                    'default' => ['value' => 1000, 'vw' => '0'],
                    'sm' => ['value' => 1000, 'vw' => '0'],
                    'md' => ['value' => 1000, 'vw' => '0'],
                    'lg' => ['value' => 1024, 'vw' => '100'],
                    'xl' => ['value' => 1280, 'vw' => '100'],
                    '2xl' => ['value' => 1536, 'vw' => '100'],
                ],
            ],
        ];
    }

    /**
     * @dataProvider provideSizesStrings
     */
    public function testGetSizes(array $widths, string $expected): void
    {
        $result = $this->transformer->getSizes($widths);
        $this->assertEquals($expected, $result);
    }

    public static function provideSizesStrings(): array
    {
        return [
            'fullscreen' => [
                [
                    'default' => ['value' => 640, 'vw' => '100'],
                    'sm' => ['value' => 640, 'vw' => '100'],
                    'md' => ['value' => 768, 'vw' => '100'],
                    'lg' => ['value' => 1024, 'vw' => '100'],
                    'xl' => ['value' => 1280, 'vw' => '100'],
                    '2xl' => ['value' => 1536, 'vw' => '100'],
                ],
                '100vw',
            ],
            'halfscreen' => [
                [
                    'default' => ['value' => 320, 'vw' => '50'],
                    'sm' => ['value' => 320, 'vw' => '50'],
                    'md' => ['value' => 384, 'vw' => '50'],
                    'lg' => ['value' => 512, 'vw' => '50'],
                    'xl' => ['value' => 640, 'vw' => '50'],
                    '2xl' => ['value' => 768, 'vw' => '50'],
                ],
                '50vw',
            ],
            'default value appears at end (W3C compliant)' => [
                [
                    'default' => ['value' => 640, 'vw' => '100'],
                    'sm' => ['value' => 640, 'vw' => '100'],
                    'md' => ['value' => 768, 'vw' => '80'],
                    'lg' => ['value' => 1024, 'vw' => '80'],
                    'xl' => ['value' => 1280, 'vw' => '80'],
                    '2xl' => ['value' => 1536, 'vw' => '80'],
                ],
                '(max-width: 768px) 100vw, 80vw',
            ],
            'mixed viewport and fixed widths' => [
                [
                    'default' => ['value' => 320, 'vw' => '50'],
                    'sm' => ['value' => 320, 'vw' => '50'],
                    'md' => ['value' => 384, 'vw' => '50'],
                    'lg' => ['value' => 400, 'vw' => '0'],
                    'xl' => ['value' => 400, 'vw' => '0'],
                    '2xl' => ['value' => 400, 'vw' => '0'],
                ],
                '(max-width: 1024px) 50vw, 400px',
            ],
            'viewport widths with breakpoint transitions' => [
                [
                    'default' => ['value' => 640, 'vw' => '100'],
                    'sm' => ['value' => 640, 'vw' => '100'],
                    'md' => ['value' => 768, 'vw' => '100'],
                    'lg' => ['value' => 1024, 'vw' => '100'],
                    'xl' => ['value' => 1152, 'vw' => '90'],
                    '2xl' => ['value' => 1382, 'vw' => '90'],
                ],
                '(max-width: 1280px) 100vw, 90vw',
            ],
            'fixed to viewport width transition' => [
                [
                    'default' => ['value' => 1000, 'vw' => '0'],
                    'sm' => ['value' => 1000, 'vw' => '0'],
                    'md' => ['value' => 1000, 'vw' => '0'],
                    'lg' => ['value' => 1024, 'vw' => '100'],
                    'xl' => ['value' => 1280, 'vw' => '100'],
                    '2xl' => ['value' => 1536, 'vw' => '100'],
                ],
                '(max-width: 1024px) 1000px, 100vw',
            ],
        ];
    }

    /**
     * @dataProvider provideSrcsetData
     */
    public function testGetSrcset(string $src, array $widths, string $expected): void
    {
        $result = $this->transformer->getSrcset(
            $src,
            $widths,
            fn($modifiers) => $src . '?' . http_build_query($modifiers)
        );
        $this->assertEquals($expected, $result);
    }

    public static function provideSrcsetData(): array
    {
        return [
            'basic widths' => [
                '/image.jpg',
                [
                    'default' => ['value' => 300, 'vw' => '0'],
                    'sm' => ['value' => 400, 'vw' => '0'],
                ],
                '/image.jpg?width=300 300w, /image.jpg?width=400 400w',
            ],
            'duplicate widths removed (W3C compliant)' => [
                '/image.jpg',
                [
                    'default' => ['value' => 640, 'vw' => '0'],
                    'sm' => ['value' => 640, 'vw' => '0'],
                    'md' => ['value' => 640, 'vw' => '0'],
                    'lg' => ['value' => 640, 'vw' => '0'],
                ],
                '/image.jpg?width=640 640w',
            ],
            'partial duplicates removed' => [
                '/image.jpg',
                [
                    'default' => ['value' => 300, 'vw' => '0'],
                    'sm' => ['value' => 300, 'vw' => '0'],
                    'md' => ['value' => 400, 'vw' => '0'],
                    'lg' => ['value' => 400, 'vw' => '0'],
                    'xl' => ['value' => 500, 'vw' => '0'],
                ],
                '/image.jpg?width=300 300w, /image.jpg?width=400 400w, /image.jpg?width=500 500w',
            ],
            'mixed unique and duplicate widths' => [
                '/image.jpg',
                [
                    'default' => ['value' => 100, 'vw' => '0'],
                    'sm' => ['value' => 200, 'vw' => '0'],
                    'md' => ['value' => 200, 'vw' => '0'],
                    'lg' => ['value' => 300, 'vw' => '0'],
                    'xl' => ['value' => 300, 'vw' => '0'],
                    '2xl' => ['value' => 400, 'vw' => '0'],
                ],
                '/image.jpg?width=100 100w, /image.jpg?width=200 200w, /image.jpg?width=300 300w, /image.jpg?width=400 400w',
            ],
        ];
    }

    /**
     * @dataProvider provideInitialWidthData
     */
    public function testGetInitialWidth(array $widths, string $pattern, int $expected): void
    {
        $result = $this->transformer->getInitialWidth($widths, $pattern);
        $this->assertEquals($expected, $result);
    }

    public static function provideInitialWidthData(): array
    {
        return [
            'viewport width pattern' => [
                [
                    'default' => ['value' => 640, 'vw' => '100'],
                    'sm' => ['value' => 640, 'vw' => '100'],
                    'md' => ['value' => 768, 'vw' => '100'],
                ],
                '100vw',
                640,
            ],
            'fixed width pattern' => [
                [
                    'default' => ['value' => 300, 'vw' => '0'],
                    'sm' => ['value' => 400, 'vw' => '0'],
                ],
                '300',
                300,
            ],
            'mixed pattern starting with fixed' => [
                [
                    'default' => ['value' => 400, 'vw' => '0'],
                    'md' => ['value' => 768, 'vw' => '100'],
                ],
                '400 md:100vw',
                400,
            ],
        ];
    }

    public function testGetDensityBasedWidths(): void
    {
        $transformer = new Transformer();

        $widths = $transformer->getDensityBasedWidths(100, 'x1 x2');
        $this->assertEquals([100, 200], $widths);

        $widths = $transformer->getDensityBasedWidths(100, '1x 2x 3x');
        $this->assertEquals([100, 200, 300], $widths);
    }

    /**
     * Tests that custom breakpoint names work correctly without filling in standard breakpoint names.
     */
    public function testCustomBreakpointNames(): void
    {
        $transformer = new Transformer([
            'mobile' => 640,
            'tablet' => 768,
            'desktop' => 1024,
        ]);

        $result = $transformer->parseWidth('mobile:200 tablet:400 desktop:800');

        // Expected: Only custom breakpoints and default
        $this->assertEquals([
            'default' => ['value' => 200, 'vw' => '0'],
            'mobile' => ['value' => 200, 'vw' => '0'],
            'tablet' => ['value' => 400, 'vw' => '0'],
            'desktop' => ['value' => 800, 'vw' => '0'],
        ], $result);

        // Standard breakpoints should NOT be present when using custom names
        $this->assertArrayNotHasKey('sm', $result);
        $this->assertArrayNotHasKey('md', $result);
        $this->assertArrayNotHasKey('lg', $result);
        $this->assertArrayNotHasKey('xl', $result);
        $this->assertArrayNotHasKey('2xl', $result);
    }
}
