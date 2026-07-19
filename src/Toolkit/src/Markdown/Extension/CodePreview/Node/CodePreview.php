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
use Symfony\UX\Toolkit\Markdown\CodeOptions;

/**
 * The live-render primitive: a code snippet plus rendering options, with no kit knowledge.
 *
 * These nodes are built programmatically (by the `{"preview": true}` fenced code form), not parsed
 * from a dedicated markdown syntax.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class CodePreview extends AbstractBlock
{
    public function __construct(
        private readonly string $code,
        private readonly CodeOptions $options = new CodeOptions(),
    ) {
        parent::__construct();
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getOptions(): CodeOptions
    {
        return $this->options;
    }
}
