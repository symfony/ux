<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Markdown\Extension\CodePreview\Node;

use League\CommonMark\Node\Block\AbstractBlock;

/**
 * The live-render primitive: a code snippet plus rendering options, with no kit knowledge.
 *
 * These nodes are built programmatically (by the `{"preview": true}` fenced code form and by the
 * `::: example` directive), not parsed from a dedicated markdown syntax.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class CodePreview extends AbstractBlock
{
    /**
     * @param array<string, mixed> $options
     */
    public function __construct(
        private readonly string $code,
        private readonly array $options = [],
    ) {
        parent::__construct();
    }

    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }
}
