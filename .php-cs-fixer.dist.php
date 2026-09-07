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
                    // parallel workers pass a plain SplFileInfo, which has no getRelativePathname()
                    $pathname = str_replace('\\', '/', $file->getPathname());

                    // `src/*/src/Test/` ships test helpers meant to be extended by users, adding a native return type there would break their subclasses
                    return !str_contains($pathname, '/src/Test/');
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
