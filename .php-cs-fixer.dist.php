<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

$fileHeaderParts = [
    <<<'EOF'
        This file is part of the Symfony package.

        (c) Fabien Potencier <fabien@symfony.com>

        EOF,
    <<<'EOF'

        For the full copyright and license information, please view the LICENSE
        file that was distributed with this source code.
        EOF,
];

return (new PhpCsFixer\Config())
    ->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
    ->setRules([
        '@PHP8x4Migration' => true, // take lowest version from `git grep -h '"php"' **/composer.json | uniq | sort`
        '@PHPUnit11x0Migration:risky' => true,
        '@Symfony' => true,
        '@Symfony:risky' => true,
        'header_comment' => [
            'header' => implode('', $fileHeaderParts),
            'validator' => implode('', [
                '/',
                preg_quote($fileHeaderParts[0], '/'),
                '(?P<EXTRA>.*)??',
                preg_quote($fileHeaderParts[1], '/'),
                '/s',
            ]),
        ],
    ])
    ->setRuleCustomisationPolicy(new class implements PhpCsFixer\Config\RuleCustomisationPolicyInterface {
        public function getPolicyVersionForCache(): string
        {
            return hash_file('xxh128', __FILE__);
        }

        public function getRuleCustomisers(): array
        {
            return [
                'void_return' => static function (SplFileInfo $file) {
                    // temporary hack due to bug: https://github.com/symfony/symfony/issues/62734
                    if (!$file instanceof Symfony\Component\Finder\SplFileInfo) {
                        return false;
                    }

                    $relativePathname = $file->getRelativePathname();

                    if (
                        str_contains($relativePathname, '/Tests/') // don't touch test files, as massive change with little benefit - as outside of public contract anyway
                        || str_contains($relativePathname, '/Test/') // public namespace not following the rule, do not mistake it with `/Tests/`
                    ) {
                        return false;
                    }

                    return true;
                },
            ];
        }
    })
    ->setRiskyAllowed(true)
    ->setFinder((new PhpCsFixer\Finder())
        ->in(__DIR__)
        ->notPath('#/Fixtures/#')
        ->notPath('#/assets/#')
        ->notPath('#/var/#')
        // apps/
        ->notPath(['#config/#', '#public/#', 'importmap.php'])
    )
;
