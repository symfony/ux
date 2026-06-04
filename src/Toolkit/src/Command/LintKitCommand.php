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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Path;
use Symfony\UX\Toolkit\Kit\KitFactory;
use Symfony\UX\Toolkit\Kit\Lint\KitLinter;
use Symfony\UX\Toolkit\Kit\Lint\LintIssue;
use Symfony\UX\Toolkit\Kit\Lint\LintReport;
use Symfony\UX\Toolkit\Kit\Lint\LintSeverity;

/**
 * Lint a Kit: structure, manifests, and declared-vs-used dependencies.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
#[AsCommand(
    name: 'ux:toolkit:lint-kit',
    description: 'Lint a local Kit (structure, manifests, dependencies).',
)]
class LintKitCommand extends Command
{
    public function __construct(
        private readonly KitFactory $kitFactory,
        private readonly KitLinter $kitLinter,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('kit-path', InputArgument::OPTIONAL, 'The path to the kit to lint', '.')
            ->addOption('fail-on-warning', null, InputOption::VALUE_NONE, 'Exit 1 when at least one warning is reported (errors always exit 1)')
            ->setHelp(
                <<<'EOF'
                    To lint a Kit in the current directory:
                        <info>php %command.full_name%</info>

                    Or in another directory:
                        <info>php %command.full_name% ./kits/shadcn</info>
                        <info>php %command.full_name% /path/to/my-kit</info>

                    Exits 0 when no errors are found (warnings are allowed by default), 1 otherwise.
                    Pass <info>--fail-on-warning</info> to also fail on warnings.
                    EOF
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $kitPath = Path::makeAbsolute($input->getArgument('kit-path'), getcwd());

        try {
            $kit = $this->kitFactory->createKitFromAbsolutePath($kitPath);
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            $io->title(\sprintf('Linting kit "%s"', $kitPath));
            $io->error(\sprintf('Unable to load kit: %s', $e->getMessage()));

            return 1;
        }

        $io->title(\sprintf('Linting kit "%s"', $kit->manifest->name));

        $report = $this->kitLinter->lint($kit);

        $this->renderIssues($io, $report);

        $errors = $report->getErrorCount();
        $warnings = $report->getWarningCount();

        if (0 === $errors && 0 === $warnings) {
            $io->success('No issues found.');

            return Command::SUCCESS;
        }

        if ($errors > 0) {
            $io->error(\sprintf('%d error(s), %d warning(s).', $errors, $warnings));

            return Command::FAILURE;
        }

        if ($input->getOption('fail-on-warning')) {
            $io->error(\sprintf('%d warning(s).', $warnings));

            return Command::FAILURE;
        }

        $io->warning(\sprintf('%d warning(s).', $warnings));

        return Command::SUCCESS;
    }

    private function renderIssues(SymfonyStyle $io, LintReport $report): void
    {
        $issues = $report->getIssues();
        if ([] === $issues) {
            return;
        }

        usort($issues, static function (LintIssue $a, LintIssue $b) {
            return [$a->recipe ?? '', $a->severity->value, $a->category]
                <=> [$b->recipe ?? '', $b->severity->value, $b->category];
        });

        $byRecipe = [];
        foreach ($issues as $issue) {
            $byRecipe[$issue->recipe ?? '(kit)'][] = $issue;
        }

        foreach ($byRecipe as $recipeName => $list) {
            $io->section($recipeName);
            foreach ($list as $issue) {
                $badge = LintSeverity::Error === $issue->severity ? '❌' : '⚠️';
                $head = \sprintf('%s <fg=cyan>%s</>', $badge, $issue->category);
                if (null !== $issue->file) {
                    $head .= \sprintf(' <fg=gray>(%s)</>', $issue->file);
                }
                $io->writeln($head);
                $io->writeln('    '.$issue->message);
                $io->newLine();
            }
        }
    }
}
