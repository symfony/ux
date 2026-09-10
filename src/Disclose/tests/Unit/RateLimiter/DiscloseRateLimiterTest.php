<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Disclose\Tests\Unit\RateLimiter;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\CacheStorage;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\UX\Disclose\RateLimiter\DiscloseRateLimiter;
use Symfony\UX\Disclose\RateLimiter\DiscloseRateLimitSubjectFactoryInterface;

/**
 * @author Jérôme Tamarelle <jerome@tamarelle.net>
 */
final class DiscloseRateLimiterTest extends TestCase
{
    public function testIdentityFallsBackToTheClientIpForAnonymousRequests(): void
    {
        $limiter = new DiscloseRateLimiter(null, []);
        $request = Request::create('/', server: ['REMOTE_ADDR' => '10.0.0.42']);

        self::assertSame('ip:10.0.0.42', $limiter->identity($request));
    }

    public function testIdentityUsesTheAuthenticatedUser(): void
    {
        $user = $this->createStub(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('mr_discloser');

        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn($user);

        $limiter = new DiscloseRateLimiter($security, []);
        $request = Request::create('/', server: ['REMOTE_ADDR' => '10.0.0.42']);

        self::assertSame('user:mr_discloser', $limiter->identity($request));
    }

    public function testIdentityDelegatesToACustomSubjectFactory(): void
    {
        $subjectFactory = $this->createStub(DiscloseRateLimitSubjectFactoryInterface::class);
        $subjectFactory->method('create')->willReturn('reference:demo-1');

        $limiter = new DiscloseRateLimiter(null, [], $subjectFactory);

        self::assertSame('reference:demo-1', $limiter->identity(Request::create('/')));
    }

    public function testConsumeDoesNothingWhenTheRateLimiterIsDisabled(): void
    {
        $limiter = new DiscloseRateLimiter(null, []);

        self::assertNull($limiter->consume(Request::create('/')));
    }

    public function testConsumesTokensUntilTheLimitIsReached(): void
    {
        $factory = new RateLimiterFactory([
            'id' => 'ux_disclose',
            'policy' => 'fixed_window',
            'limit' => 2,
            'interval' => '1 hour',
        ], new CacheStorage(new ArrayAdapter()));

        $limiter = new DiscloseRateLimiter(null, [$factory]);
        $request = Request::create('/', server: ['REMOTE_ADDR' => '10.0.0.42']);

        self::assertTrue($limiter->consume($request)->isAccepted());
        self::assertTrue($limiter->consume($request)->isAccepted());
        self::assertFalse($limiter->consume($request)->isAccepted());
    }

    public function testCombinesABurstAndADailyLimiter(): void
    {
        $burst = new RateLimiterFactory([
            'id' => 'ux_disclose_burst',
            'policy' => 'fixed_window',
            'limit' => 5,
            'interval' => '1 minute',
        ], new CacheStorage(new ArrayAdapter()));

        $daily = new RateLimiterFactory([
            'id' => 'ux_disclose_daily',
            'policy' => 'fixed_window',
            'limit' => 2,
            'interval' => '1 day',
        ], new CacheStorage(new ArrayAdapter()));

        $limiter = new DiscloseRateLimiter(null, [$burst, $daily]);
        $request = Request::create('/', server: ['REMOTE_ADDR' => '10.0.0.42']);

        // The tight daily quota caps the generous burst: only 2 disclosures
        // are accepted, the 3rd is rejected even though the burst has room.
        self::assertTrue($limiter->consume($request)->isAccepted());
        self::assertTrue($limiter->consume($request)->isAccepted());

        self::assertFalse($limiter->consume($request)->isAccepted());
    }
}
