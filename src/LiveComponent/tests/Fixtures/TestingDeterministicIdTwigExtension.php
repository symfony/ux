<?php

namespace Symfony\UX\LiveComponent\Tests\Fixtures;

use Symfony\UX\LiveComponent\Twig\DeterministicTwigIdCalculator;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class TestingDeterministicIdTwigExtension extends AbstractExtension
{
    public function __construct(private DeterministicTwigIdCalculator $deterministicIdCalculator)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_id_for_test', [$this, 'getIdForTest']),
        ];
    }

    public function getIdForTest(): string
    {
        return $this->deterministicIdCalculator->calculateDeterministicId();
    }
}
