<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Twig\Components\Toolkit;

use App\Enum\ToolkitKitId;
use App\Service\Toolkit\ToolkitService;
use App\Util\SourceCleaner;
use Symfony\Component\HttpFoundation\UriSigner;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\String\AbstractString;
use Symfony\UX\Toolkit\Asset\Component;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Tempest\Highlight\Highlighter;

use function Symfony\Component\String\s;

#[AsTwigComponent]
class ComponentDoc
{
    public ToolkitKitId $kitId;
    public Component $component;

    public function __construct(private readonly ToolkitService $toolkitService) {
    }

    public function getContent(): string
    {
        return $this->formatContent($this->component->doc->markdownContent);
    }

    private function formatContent(string $markdownContent): string
    {
        $markdownContent = s($markdownContent);

        $markdownContent = $this->insertInstallation($markdownContent);
        $markdownContent = $this->insertUsage($markdownContent);
        $markdownContent = $this->adaptPreviewableCodeBlocks($markdownContent);

        return $markdownContent;
    }

    private function insertInstallation(AbstractString $markdownContent): AbstractString
    {
        return $markdownContent->replace(
            '<!-- Placeholder: Installation -->',
            $this->toolkitService->renderInstallationSteps($this->kitId, $this->component)
        );
    }

    private function insertUsage(AbstractString $markdownContent): AbstractString
    {
        $firstTwigPreviewBlock = $markdownContent->match('/```twig.*?\n(.+?)```/s');
        $firstTwigPreviewBlock = $firstTwigPreviewBlock ? trim($firstTwigPreviewBlock[1]) : '';

        return $markdownContent->replace(
            '<!-- Placeholder: Usage -->',
            '```twig'."\n".$firstTwigPreviewBlock."\n".'```'
        );
    }

    /**
     * Iterate over code blocks, and add the option "kit" if the option "preview" exists.
     */
    private function adaptPreviewableCodeBlocks(AbstractString $markdownContent): AbstractString
    {
        return $markdownContent->replaceMatches('/```(?P<lang>[a-z]+) +(?P<options>\{.+?\})\n/', function (array $matches) {
            $lang = $matches['lang'];
            $options = json_decode($matches['options'], true, flags: \JSON_THROW_ON_ERROR);

            if ($options['preview'] ?? false) {
                $options['kit'] = $this->kitId->value;
            }

            return \sprintf('```%s %s'."\n", $lang, json_encode($options, \JSON_THROW_ON_ERROR));
        });
    }
}
