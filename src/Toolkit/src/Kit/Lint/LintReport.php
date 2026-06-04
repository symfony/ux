<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Kit\Lint;

/**
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class LintReport
{
    /**
     * @var list<LintIssue>
     */
    private array $issues = [];

    public function add(LintIssue $issue): void
    {
        $this->issues[] = $issue;
    }

    /**
     * @return list<LintIssue>
     */
    public function getIssues(): array
    {
        return $this->issues;
    }

    /**
     * @return list<LintIssue>
     */
    public function getErrors(): array
    {
        return array_values(array_filter($this->issues, static fn (LintIssue $i) => LintSeverity::Error === $i->severity));
    }

    /**
     * @return list<LintIssue>
     */
    public function getWarnings(): array
    {
        return array_values(array_filter($this->issues, static fn (LintIssue $i) => LintSeverity::Warning === $i->severity));
    }

    public function getErrorCount(): int
    {
        return \count($this->getErrors());
    }

    public function getWarningCount(): int
    {
        return \count($this->getWarnings());
    }

    public function hasErrors(): bool
    {
        return $this->getErrorCount() > 0;
    }
}
