<?php

namespace Symfony\UX\Icons\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\UX\Icons\Icon;

class IconAccessibilityTest extends TestCase
{
    public function testTitleIsIncludedInOutput()
    {
        $icon = new Icon('<path d="M0 0h24v24H0z" fill="none"/>', ['title' => 'Test Icon']);
        $html = $icon->toHtml();
        $this->assertMatchesRegularExpression('/<title( id="[^"]*")?>Test Icon<\/title>/', $html);
    }

    public function testDescIsIncludedInOutput()
    {
        $icon = new Icon('<circle cx="12" cy="12" r="10"/>', ['desc' => 'This is a test circle']);
        $html = $icon->toHtml();
        $this->assertMatchesRegularExpression('/<desc( id="[^"]*")?>This is a test circle<\/desc>/', $html);
    }

    public function testTitleAndDescWithCustomAriaLabelledBy()
    {
        $attributes = [
            'title' => 'My Line',
            'desc' => 'This is a diagonal line',
            'aria-labelledby' => 'custom-id',
        ];
        $icon = new Icon('<line x1="0" y1="0" x2="10" y2="10"/>', $attributes);

        $html = $icon->toHtml();
        $this->assertStringContainsString('<title>My Line</title>', $html);
        $this->assertStringContainsString('<desc>This is a diagonal line</desc>', $html);
        $this->assertStringContainsString('aria-labelledby="custom-id"', $html);
    }

    public function testToStringReturnsHtml()
    {
        $icon = new Icon('<path d="M0 0h24v24H0z"/>');
        $this->assertSame($icon->toHtml(), (string) $icon);
    }
}
