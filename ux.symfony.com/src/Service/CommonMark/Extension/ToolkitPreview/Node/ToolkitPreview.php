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

namespace App\Service\CommonMark\Extension\ToolkitPreview\Node;

use App\Enum\ToolkitKitId;
use League\CommonMark\Node\Block\AbstractBlock;
use League\CommonMark\Node\StringContainerInterface;

final class ToolkitPreview extends AbstractBlock implements StringContainerInterface
{
    private string $literal = '';

    /**
     * @param array<string,mixed> $options
     */
    public function __construct(
        private readonly ToolkitKitId $kitId,
        private readonly array $options = [],
    ) {
        parent::__construct();
    }

    public function setLiteral(string $literal): void
    {
        $this->literal = $literal;
    }

    public function getLiteral(): string
    {
        return $this->literal;
    }

    public function getKitId(): ToolkitKitId
    {
        return $this->kitId;
    }

    /**
     * @return array<string,mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }
}
