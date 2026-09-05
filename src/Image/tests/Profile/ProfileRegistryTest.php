<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Image\Tests\Profile;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\UX\Image\Exception\UnknownImageProfileException;
use Symfony\UX\Image\Profile\ImageProfile;
use Symfony\UX\Image\Profile\ProcessingMode;
use Symfony\UX\Image\Profile\ProfileRegistry;
use Symfony\UX\Image\Profile\VariantDefinition;
use Symfony\UX\Image\Transformation\ResizeMode;

#[CoversClass(ProfileRegistry::class)]
#[CoversClass(ImageProfile::class)]
#[CoversClass(ProcessingMode::class)]
#[CoversClass(VariantDefinition::class)]
#[CoversClass(UnknownImageProfileException::class)]
final class ProfileRegistryTest extends TestCase
{
    public function testCompilesTypedProfileAndStableRevision()
    {
        $configuration = ['formats' => ['webp'], 'variants' => ['thumb' => ['width' => 100, 'mode' => 'crop', 'position' => '50% 30%']]];
        $profile = new ProfileRegistry(['avatar' => $configuration])->get('avatar');

        self::assertSame('avatar', $profile->name);
        self::assertSame(['webp'], $profile->formats);
        self::assertSame(ProcessingMode::Immediate, $profile->processing);
        self::assertSame(ResizeMode::Crop, $profile->variants['thumb']->mode);
        self::assertSame(0.3, $profile->variants['thumb']->focalPoint->y);
        self::assertSame(hash('sha256', json_encode($configuration, \JSON_THROW_ON_ERROR)), $profile->revision());
    }

    public function testUnknownProfileFailsWithAvailableNames()
    {
        $registry = new ProfileRegistry(['avatar' => ['formats' => ['jpeg'], 'variants' => []]]);

        $this->expectException(UnknownImageProfileException::class);
        $this->expectExceptionMessage('Available profiles: avatar');
        $registry->get('missing');
    }

    public function testCompilesExplicitProcessingModes()
    {
        $registry = new ProfileRegistry([
            'deferred' => ['processing' => 'deferred'],
            'async' => ['processing' => 'async'],
        ]);

        self::assertSame(ProcessingMode::Deferred, $registry->get('deferred')->processing);
        self::assertSame(ProcessingMode::Async, $registry->get('async')->processing);
    }

    public function testRejectsInvalidProcessingModeOutsideTheContainer()
    {
        $this->expectException(\InvalidArgumentException::class);

        new ProfileRegistry(['invalid' => ['processing' => 'later']]);
    }

    public function testRejectsNonStringProcessingModeOutsideTheContainer()
    {
        $this->expectException(\InvalidArgumentException::class);

        new ProfileRegistry(['invalid' => ['processing' => true]]);
    }

    public function testUnknownProfileExplainsWhenNoneAreConfigured()
    {
        $this->expectException(UnknownImageProfileException::class);
        $this->expectExceptionMessage('No profiles are configured');

        new ProfileRegistry([])->get('missing');
    }

    public function testHasProfile()
    {
        $registry = new ProfileRegistry(['avatar' => ['formats' => ['jpeg'], 'variants' => []]]);

        self::assertTrue($registry->has('avatar'));
        self::assertFalse($registry->has('missing'));
    }

    public function testRejectsNonArrayVariants()
    {
        $this->expectException(\InvalidArgumentException::class);

        new ProfileRegistry(['avatar' => ['formats' => ['jpeg'], 'variants' => 'invalid']]);
    }

    public function testIgnoresMalformedVariantEntry()
    {
        $profile = new ProfileRegistry(['avatar' => ['formats' => ['jpeg'], 'variants' => [0 => 'invalid']]])->get('avatar');

        self::assertSame([], $profile->variants);
    }

    public function testRejectsNonArrayFormats()
    {
        $this->expectException(\InvalidArgumentException::class);

        new ProfileRegistry(['avatar' => ['formats' => 'jpeg', 'variants' => []]]);
    }

    public function testVariantRequiresDimension()
    {
        $this->expectException(\InvalidArgumentException::class);

        new VariantDefinition('empty', null, null, ResizeMode::Fit, 80, new \Symfony\UX\Image\Transformation\FocalPoint());
    }
}
