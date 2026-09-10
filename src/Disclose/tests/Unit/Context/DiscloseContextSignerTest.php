<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Disclose\Tests\Unit\Context;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\UX\Disclose\Checksum\ChecksumCalculator;
use Symfony\UX\Disclose\Context\DiscloseContext;
use Symfony\UX\Disclose\Context\DiscloseContextSigner;
use Symfony\UX\Disclose\Context\ExpiredDiscloseContextException;

/**
 * @author Jérôme Tamarelle <jerome@tamarelle.net>
 */
final class DiscloseContextSignerTest extends TestCase
{
    public function testSignsAndRestoresAContext(): void
    {
        $signer = new DiscloseContextSigner(new ChecksumCalculator('secret'), 3600);
        $context = DiscloseContext::create('App\Entity\User', 42, 'email');

        $params = $signer->sign($context);
        self::assertArrayHasKey('d', $params);
        self::assertArrayHasKey('h', $params);

        $restored = $signer->fromRequest($this->request($params));

        self::assertSame('App\Entity\User', $restored->class);
        self::assertSame(42, $restored->id);
        self::assertSame('email', $restored->field);
    }

    public function testRejectsATamperedSignature(): void
    {
        $signer = new DiscloseContextSigner(new ChecksumCalculator('secret'), 3600);
        $params = $signer->sign(DiscloseContext::create('App\Entity\User', 42));

        $params['h'] = 'AAAA';

        $this->expectException(BadRequestHttpException::class);

        $signer->fromRequest($this->request($params));
    }

    public function testRejectsAnExpiredContext(): void
    {
        $signer = new DiscloseContextSigner(new ChecksumCalculator('secret'), 3600);
        $params = $signer->sign(DiscloseContext::create('App\Entity\User', 42), time() - 1);

        $this->expectException(ExpiredDiscloseContextException::class);

        $signer->fromRequest($this->request($params));
    }

    public function testAcceptsAContextBeforeItsExpiry(): void
    {
        $signer = new DiscloseContextSigner(new ChecksumCalculator('secret'), 3600);
        $params = $signer->sign(DiscloseContext::create('App\Entity\User', 42), time() + 60);

        $restored = $signer->fromRequest($this->request($params));

        self::assertSame('App\Entity\User', $restored->class);
    }

    public function testDoesNotAddAnExpiryWithoutATtl(): void
    {
        $signer = new DiscloseContextSigner(new ChecksumCalculator('secret'));
        $params = $signer->sign(DiscloseContext::create('App\Entity\User', 42));

        // A context signed without a TTL never expires and round-trips intact.
        $restored = $signer->fromRequest($this->request($params));

        self::assertSame('App\Entity\User', $restored->class);
    }

    public function testRejectsAMissingContext(): void
    {
        $signer = new DiscloseContextSigner(new ChecksumCalculator('secret'), 3600);

        $this->expectException(BadRequestHttpException::class);

        $signer->fromRequest(new Request());
    }

    /**
     * @param array{d: string, h: string} $params
     */
    private function request(array $params): Request
    {
        return new Request($params);
    }
}
