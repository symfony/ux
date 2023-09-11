<?php

namespace App\Twig;

use App\Util\FilenameHelper;
use App\Util\SourceCleaner;
use Highlight\Highlighter;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class CodeBlock
{
    public string $filename;
    public string $height = '300px';
    public bool $showFilename = true;
    public ?string $language = null;
    /**
     * For a Twig file, set to a block name to "zoom into" only showing
     * that one block.
     */
    public ?string $targetTwigBlock = null;
    /**
     * If $targetTwigBlock is set, should we show the "{% extends %}" code?
     */
    public bool $showTwigExtends = true;

    /**
     * If true, remove most HTML attributes and empty HTML elements.
     */
    public bool $stripExcessHtml = false;

    public function __construct(
        private Highlighter $highlighter,
        #[Autowire('%kernel.project_dir%')] private string $rootDir,
    ) {
    }

    public function highlightSource(): string
    {
        $content = $this->getRawSource();
        if ('php' === $this->getLanguage()) {
            $content = SourceCleaner::cleanupPhpFile($content);
        }

        $pieces = $this->splitAndProcessSource($content);

        $highlighted = [];
        foreach ($pieces as $piece) {
            if ($piece['highlight']) {
                $highlighted[] = $this->highlighter->highlight($this->getLanguage(), $piece['content'])->value;
            } else {
                $highlighted[] = $piece['content'];
            }
        }

        return implode('', $highlighted);
    }

    public function getRawSource(): string
    {
        $path = $this->rootDir.'/'.$this->filename;
        if (!file_exists($path)) {
            throw new \InvalidArgumentException(sprintf('File "%s" does not exist.', $path));
        }

        $content = file_get_contents($path);

        if ($this->targetTwigBlock) {
            $content = SourceCleaner::extractTwigBlock($content, $this->targetTwigBlock, $this->showTwigExtends);
        }

        if ($this->stripExcessHtml) {
            $content = SourceCleaner::removeExcessHtml($content);
        }

        return $content;
    }

    public function getClassString(): string
    {
        return 'terminal-code'.($this->showFilename ? '' : ' terminal-code-no-filename');
    }

    public function getGithubLink(): string
    {
        return sprintf('https://github.com/symfony/ux/blob/2.x/ux.symfony.com/%s', $this->filename);
    }

    private function getLanguage(): string
    {
        if (null !== $this->language) {
            return $this->language;
        }

        $parts = explode('.', $this->filename);

        return array_pop($parts);
    }

    public function getElementId(): ?string
    {
        return FilenameHelper::getElementId($this->filename);
    }

    /**
     * If necessary, split the source into multiple parts.
     *
     * This allows us to inject some HTML (e.g. a <span> around use statements)
     * that will be kept raw / not highlighted.
     */
    private function splitAndProcessSource(string $content): array
    {
        // collapse use statements
        $lines = explode("\n", $content);
        $firstUseLine = null;
        $lastUseLine = null;
        foreach ($lines as $i => $line) {
            if (str_starts_with($line, 'use ')) {
                if (null === $firstUseLine) {
                    $firstUseLine = $i;
                }

                $lastUseLine = $i;
            }
        }

        $parts = [
            ['content' => $content, 'highlight' => true],
        ];
        if (null !== $firstUseLine && null !== $lastUseLine && $lastUseLine > $firstUseLine) {
            $parts = [];

            // everything before the first use statement
            $beforeContent = implode("\n", \array_slice($lines, 0, $firstUseLine));
            if ($beforeContent) {
                $parts[] = [
                    'content' => implode("\n", \array_slice($lines, 0, $firstUseLine))."\n",
                    'highlight' => true,
                ];
            }

            // the use statements + surrounding span
            $parts[] = [
                'content' => '<span class="hljs-comment" role="button" title="Expand use statements" data-action="click->code-expander#expandUseStatements">// ... use statements hidden - click to show </span>',
                'highlight' => false,
            ];
            $parts[] = [
                'content' => '<span data-code-expander-target="useStatements" style="display: none;">',
                'highlight' => false,
            ];
            $parts[] = [
                'content' => implode("\n", \array_slice($lines, $firstUseLine, $lastUseLine - $firstUseLine + 1)),
                'highlight' => true,
            ];
            $parts[] = [
                'content' => '</span>',
                'highlight' => false,
            ];

            // 2 chunk is everything after the last use statement
            $parts[] = [
                'content' => implode("\n", \array_slice($lines, $lastUseLine + 1)),
                'highlight' => true,
            ];
        }

        return $parts;
    }
}
