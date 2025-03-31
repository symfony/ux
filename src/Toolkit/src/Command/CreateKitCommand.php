<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\UX\Toolkit\Assert;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
#[AsCommand(
    name: 'ux:toolkit:create-kit',
    description: 'Create a new kit',
    hidden: true,
)]
class CreateKitCommand extends Command
{
    public function __construct(
        private readonly Filesystem $filesystem,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Get the kit name
        $question = new Question('What is the name of your kit?');
        $question->setValidator(function (?string $value) {
            if (empty($value)) {
                throw new \RuntimeException('Kit name cannot be empty');
            }
            Assert::kitName($value);

            return $value;
        });
        $kitName = $io->askQuestion($question);

        // Get the kit homepage
        $question = new Question('What is the homepage of your kit?');
        $question->setValidator(function (?string $value) {
            if (empty($value) || !filter_var($value, \FILTER_VALIDATE_URL)) {
                throw new \Exception('The homepage must be a valid URL');
            }

            return $value;
        });
        $kitHomepage = $io->askQuestion($question);

        // Get the kit author name
        $question = new Question('What is the author name of your kit?');
        $question->setValidator(function (?string $value) {
            if (empty($value)) {
                throw new \Exception('The author name cannot be empty');
            }

            return $value;
        });
        $kitAuthorName = $io->askQuestion($question);

        // Get the kit license
        $question = new Question('What is the license of your kit?');
        $question->setValidator(function (string $value) {
            if (empty($value)) {
                throw new \Exception('The license cannot be empty');
            }

            return $value;
        });
        $kitLicense = $io->askQuestion($question);

        // Create the kit
        $this->filesystem->dumpFile('manifest.json', json_encode([
            'name' => $kitName,
            'homepage' => $kitHomepage,
            'authors' => [$kitAuthorName],
            'license' => $kitLicense,
        ], \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES));
        $this->filesystem->dumpFile('templates/components/Button.html.twig', <<<TWIG
{% props type = 'button', variant = 'default' %}
{%- set style = html_cva(
    base: 'inline-flex items-center',
    variants: {
        variant: {
            default: "bg-primary text-primary-foreground hover:bg-primary/90",
            secondary: "bg-secondary text-secondary-foreground hover:bg-secondary/80",
        },
    },
) -%}

<button class="{{ style.apply({ variant }, attributes.render('class'))|tailwind_merge }}"
    {{ attributes.defaults({}).without('class') }}
>
    {%- block content %}{% endblock -%}
</button>
TWIG
        );
        $this->filesystem->dumpFile('docs/components/Button.twig', <<<TWIG
{% block meta %}
title: Button
description: The Button component is a versatile component that allows you to create clickable buttons with various styles and states.
{% endblock %}

{% block examples %}
# Basic Button

```twig
<twig:Button>
    Click me
</twig:Button>
```

# Button with Variants

```twig
<twig:Button variant="default">Default</twig:Button>
<twig:Button variant="secondary">Secondary</twig:Button>
```
{% endblock %}
TWIG
        );

        $io->success('Perfect, you can now start building your kit!');

        return self::SUCCESS;
    }
}
