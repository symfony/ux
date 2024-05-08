<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig\Components\Code;

use App\Util\FilenameHelper;
use App\Util\SourceCleaner;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('CodeBlock', template: 'components/Code/CodeBlock.html.twig')]
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
        #[Autowire('%kernel.project_dir%')] private string $rootDir,
    ) {
    }

    /**
     * Returns a list of source code pieces, extracted from the filename
     * argument, ready to be renderer in the template.
     *
     * Every piece is composed as an array {content, highlight} with
     *      content: the raw source code (after cleaning)
     *      highlight: whether they must be syntax-highlighted or not
     *
     * @return list<array{content: string, highlight: ?bool}>
     */
    public function prepareSource(): array
    {
        $content = $this->getRawSource();
        if ('php' === $this->getLanguage()) {
            $content = SourceCleaner::cleanupPhpFile($content);
        }

        return $this->splitAndProcessSource($content);
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

    public function getLanguage(): string
    {
        if (null !== $this->language) {
            return $this->language;
        }

        if ($ext = strrchr($this->filename, '.')) {
            return $this->language = substr($ext, 1);
        }

        throw new \RuntimeException('Unable to detect the code language');
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
                'content' => '<span class="hljs-comment" role="button" title="Expand use statements" data-action="click->code-expander#expandUseStatements">
<pre><code class="nohighlight">// ... use statements hidden - click to show</code></pre></span>',
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
