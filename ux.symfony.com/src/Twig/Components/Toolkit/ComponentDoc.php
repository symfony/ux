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
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\UX\Toolkit\Recipe\Recipe;
use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

use function Symfony\Component\String\s;

#[AsTwigComponent]
class ComponentDoc
{
    public ToolkitKitId $kitId;
    public Recipe $component;

    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly ToolkitService $toolkitService,
    ) {
    }

    public function getContent(): string
    {
        $examples = $this->getExamples();

        return $this->adaptPreviewableCodeBlocks(\sprintf(<<<MARKDOWN
            # %s

            %s

            %s

            ## Installation

            %s

            ## Usage

            %s

            ## Examples

            %s
            MARKDOWN,
            $this->component->manifest->name,
            $this->component->manifest->description,
            current($examples),
            $this->toolkitService->renderInstallationSteps($this->kitId, $this->component),
            preg_replace('/^```twig.*\n/', '```twig'.\PHP_EOL, current($examples)),
            array_reduce(array_keys($examples), function (string $acc, string $exampleTitle) use ($examples) {
                $acc .= '### '.$exampleTitle.\PHP_EOL.$examples[$exampleTitle].\PHP_EOL;

                return $acc;
            }, '')
        ));
    }

    /**
     * @return array<string, string>
     */
    private function getExamples(): array
    {
        $examplesMdPath = Path::join($this->component->absolutePath, 'EXAMPLES.md');

        $markdown = s($this->filesystem->readFile($examplesMdPath));

        // Remove "# Examples" header
        $markdown = $markdown->replace('# Examples', '');

        // Split the markdown for each title and content
        $examples = [];
        foreach (explode(\PHP_EOL, $markdown) as $line) {
            if (str_starts_with($line, '## ')) {
                // This is a new example title
                $title = trim(substr($line, 2));
                $examples[$title] = '';
            } elseif (isset($title)) {
                // This line belongs to the last example
                $examples[$title] .= $line.\PHP_EOL;
            }
        }

        if ([] === $examples) {
            throw new \LogicException(\sprintf('No examples found in "%s".', $examplesMdPath));
        }

        foreach ($examples as $title => &$example) {
            $example = trim($example);
        }

        return $examples;
    }

    /**
     * Iterate over code blocks, and add the option "kit" if the option "preview" exists.
     */
    private function adaptPreviewableCodeBlocks(string $markdownContent): string
    {
        return s($markdownContent)->replaceMatches('/```(?P<lang>[a-z]+) +(?P<options>\{.+?\})\n/', function (array $matches) {
            $lang = $matches['lang'];
            $options = json_decode($matches['options'], true, flags: \JSON_THROW_ON_ERROR);

            if ($options['preview'] ?? false) {
                $options['kit'] = $this->kitId->value;
            }

            return \sprintf('```%s %s'.\PHP_EOL, $lang, json_encode($options, \JSON_THROW_ON_ERROR));
        })->toString();
    }
}
