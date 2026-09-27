<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Css\Tests\Golden;

final class PandaExpectations
{
    /**
     * @var array<string, true>
     */
    private readonly array $passingIndex;

    /**
     * @param list<string>          $passing
     * @param array<string, string> $skipped reasons, indexed by case id
     */
    public function __construct(
        public readonly array $passing,
        public readonly array $skipped,
    ) {
        $this->passingIndex = array_fill_keys($passing, true);
    }

    public static function load(string $file): self
    {
        $data = require $file;

        return new self($data['passing'], $data['skipped']);
    }

    public function mustPass(string $id): bool
    {
        return isset($this->passingIndex[$id]);
    }

    public function skipReason(string $id): ?string
    {
        return $this->skipped[$id] ?? null;
    }

    public function dump(string $file): void
    {
        $passing = $this->passing;
        sort($passing);
        $skipped = $this->skipped;
        ksort($skipped);

        $lines = [
            '<?php',
            '',
            '/*',
            ' * This file is part of the Symfony package.',
            ' *',
            ' * (c) Fabien Potencier <fabien@symfony.com>',
            ' *',
            ' * For the full copyright and license information, please view the LICENSE',
            ' * file that was distributed with this source code.',
            ' */',
            '',
            'return [',
            "    'passing' => [",
        ];
        foreach ($passing as $id) {
            $lines[] = '        '.var_export($id, true).',';
        }
        $lines[] = '    ],';
        $lines[] = "    'skipped' => [";
        foreach ($skipped as $id => $reason) {
            $lines[] = '        '.var_export($id, true).' => '.var_export($reason, true).',';
        }
        $lines[] = '    ],';
        $lines[] = '];';

        file_put_contents($file, implode("\n", $lines)."\n");
    }
}
