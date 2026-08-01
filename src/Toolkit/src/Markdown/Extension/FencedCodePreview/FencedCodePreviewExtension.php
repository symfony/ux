<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Markdown\Extension\FencedCodePreview;

use League\CommonMark\Environment\EnvironmentBuilderInterface;
use League\CommonMark\Event\DocumentParsedEvent;
use League\CommonMark\Extension\CommonMark\Node\Block\FencedCode;
use League\CommonMark\Extension\ExtensionInterface;
use Symfony\UX\Toolkit\Assert;
use Symfony\UX\Toolkit\Markdown\CodeOptions;
use Symfony\UX\Toolkit\Markdown\PreviewTabsBuilder;

/**
 * The inline preview: a fenced code block whose info string carries `{"preview": true, ...}` (e.g. a
 * `twig` block with `{"preview": true}`) is turned into a `Tabs(Preview, Code)` block.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 */
final class FencedCodePreviewExtension implements ExtensionInterface
{
    public function __construct()
    {
        Assert::commonMarkAvailable();
    }

    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment->addEventListener(DocumentParsedEvent::class, $this->onDocumentParsed(...));
    }

    public function onDocumentParsed(DocumentParsedEvent $event): void
    {
        // Collect first, then mutate: replacing nodes while iterating the document is unsafe.
        $previews = [];
        foreach ($event->getDocument()->iterator() as $node) {
            if ($node instanceof FencedCode && null !== ($parsed = self::parsePreviewInfo($node->getInfo()))) {
                $previews[] = [$node, $parsed];
            }
        }

        foreach ($previews as [$node, [$language, $options]]) {
            $node->replaceWith(PreviewTabsBuilder::build($node->getLiteral(), $options, $language));
        }
    }

    /**
     * @return array{0: string, 1: CodeOptions}|null [language, options] when the info string opts into a preview
     */
    private static function parsePreviewInfo(?string $info): ?array
    {
        if (null === $info || '' === $info) {
            return null;
        }

        if (!preg_match('/^(?<language>\S+)\s+(?<json>\{.*\})$/s', $info, $matches)) {
            return null;
        }

        // The preview opt-in is a routing trigger, not a CodeOptions field: only `"preview": true` blocks
        // become tabs; every other JSON info string (e.g. a `filename`) stays a plain fenced block.
        $decoded = json_decode($matches['json'], true);
        if (!\is_array($decoded) || true !== ($decoded['preview'] ?? null)) {
            return null;
        }

        return [$matches['language'], CodeOptions::fromDecoded($decoded)];
    }
}
