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
        '@PHP8x1Migration' => true, // take lowest version from `git grep -h '"php"' **/composer.json | uniq | sort`
        '@PHPUnit9x1Migration:risky' => true,
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
    ->setRiskyAllowed(true)
    ->setFinder((new PhpCsFixer\Finder())
        ->in(__DIR__)
        ->append([__FILE__])
        ->notPath('#/Fixtures/#')
        ->notPath('#/assets/#')
        ->notPath('#/var/#')
        // apps/
        ->notPath(['#config/#', '#public/#', 'importmap.php'])
    )
;
