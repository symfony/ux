<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Upload\Tests {
    final class NativeFunctions
    {
        public const string PASSTHROUGH = "\0passthrough";

        /**
         * @var array<string, list<mixed>>
         */
        private static array $results = [];

        public static function mock(string $function, mixed ...$results): void
        {
            self::$results[$function] = $results;
        }

        public static function reset(): void
        {
            self::$results = [];
        }

        public static function result(string $function, ?bool &$handled): mixed
        {
            if (!isset(self::$results[$function]) || [] === self::$results[$function]) {
                $handled = false;

                return null;
            }

            $result = array_shift(self::$results[$function]);
            $handled = self::PASSTHROUGH !== $result;

            return $result;
        }

        public static function passthrough(string $function, mixed ...$arguments): mixed
        {
            return \Closure::fromCallable($function)(...$arguments);
        }
    }
}

namespace Symfony\UX\Upload\Storage {
    use Symfony\UX\Upload\Tests\NativeFunctions;

    function fopen(string $filename, string $mode)
    {
        $result = NativeFunctions::result(__FUNCTION__, $handled);

        return $handled ? $result : NativeFunctions::passthrough('fopen', $filename, $mode);
    }

    function fread($stream, int $length): string|false
    {
        $result = NativeFunctions::result(__FUNCTION__, $handled);

        return $handled ? $result : NativeFunctions::passthrough('fread', $stream, $length);
    }

    function fwrite($stream, string $data): int|false
    {
        $result = NativeFunctions::result(__FUNCTION__, $handled);

        return $handled ? $result : NativeFunctions::passthrough('fwrite', $stream, $data);
    }

    function tempnam(string $directory, string $prefix): string|false
    {
        $result = NativeFunctions::result(__FUNCTION__, $handled);

        return $handled ? $result : NativeFunctions::passthrough('tempnam', $directory, $prefix);
    }
}

namespace Symfony\UX\Upload\Upload {
    use Symfony\UX\Upload\Tests\NativeFunctions;

    function fopen(string $filename, string $mode)
    {
        $result = NativeFunctions::result(__FUNCTION__, $handled);

        return $handled ? $result : NativeFunctions::passthrough('fopen', $filename, $mode);
    }

    function fwrite($stream, string $data): int|false
    {
        $result = NativeFunctions::result(__FUNCTION__, $handled);

        return $handled ? $result : NativeFunctions::passthrough('fwrite', $stream, $data);
    }
}

namespace Symfony\UX\Upload\Hash {
    use Symfony\UX\Upload\Tests\NativeFunctions;

    function fread($stream, int $length): string|false
    {
        $result = NativeFunctions::result(__FUNCTION__, $handled);

        return $handled ? $result : NativeFunctions::passthrough('fread', $stream, $length);
    }
}

namespace Symfony\UX\Upload {
    use Symfony\UX\Upload\Tests\NativeFunctions;

    function fread($stream, int $length): string|false
    {
        $result = NativeFunctions::result(__FUNCTION__, $handled);

        return $handled ? $result : NativeFunctions::passthrough('fread', $stream, $length);
    }

    function inflate_init(int $encoding, array $options = []): \InflateContext|false
    {
        $result = NativeFunctions::result(__FUNCTION__, $handled);

        return $handled ? $result : NativeFunctions::passthrough('inflate_init', $encoding, $options);
    }

    function interface_exists(string $interface, bool $autoload = true): bool
    {
        $result = NativeFunctions::result(__FUNCTION__, $handled);

        return $handled ? $result : NativeFunctions::passthrough('interface_exists', $interface, $autoload);
    }
}
