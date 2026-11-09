<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Css\Twig;

use Psr\Log\LoggerInterface;
use Symfony\UX\Css\CssClassList;
use Symfony\UX\Css\Engine\ClassNameGenerator;
use Symfony\UX\Css\Validation\StyleValidator;

/**
 * The classes of the style hashes only known when the template renders.
 *
 * @psalm-import-type CssStyles from \Symfony\UX\Css\CssReference
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class CssRuntime
{
    /**
     * How many style hashes keep their classes in memory before the cache starts over.
     */
    private const CACHED_HASHES = 1000;

    /**
     * @var array<string, array<string, string>> the classes of the last hashes, by serialized hash
     */
    private array $cache = [];

    /**
     * @var array<string, true>
     */
    private array $knownClasses = [];
    private int|false $knownClassesTime = false;

    /**
     * @param (\Closure(): StyleValidator)|null $validator   validates the dynamic hashes, in debug only
     * @param string|null                       $classesFile lists the classes that have CSS, in debug only
     */
    public function __construct(
        private readonly ClassNameGenerator $generator,
        private readonly ?\Closure $validator = null,
        private readonly ?string $classesFile = null,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * @param CssStyles|null $styles
     */
    public function css(?array $styles): CssClassList
    {
        return new CssClassList(null === $styles ? [] : $this->classes($styles));
    }

    /**
     * @param array<string, string> $classes the classes of the part of the hash known when the template compiled, mapped to their merge key
     * @param CssStyles             $styles  the rest of the hash
     */
    public function append(array $classes, array $styles): CssClassList
    {
        return new CssClassList([...$classes, ...$this->classes($styles)]);
    }

    /**
     * @param CssStyles $styles
     *
     * @return array<string, string>
     */
    private function classes(array $styles): array
    {
        if (null !== $this->validator) {
            ($this->validator)()->validate($styles);
        }

        $classes = $this->generatedClasses($styles);
        $this->logClassesWithoutCss(array_keys($classes));

        return $classes;
    }

    /**
     * @param CssStyles $styles
     *
     * @return array<string, string>
     */
    private function generatedClasses(array $styles): array
    {
        try {
            $key = serialize($styles);
        } catch (\Exception) {
            // a value that cannot be serialized, like a closure, is for the generator to refuse
            return $this->generator->classes($styles);
        }

        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }
        if (\count($this->cache) >= self::CACHED_HASHES) {
            $this->cache = [];
        }

        return $this->cache[$key] = $this->generator->classes($styles);
    }

    /**
     * @param list<string|int> $classes
     */
    private function logClassesWithoutCss(array $classes): void
    {
        if (null === $this->logger || null === $this->classesFile || [] === $classes) {
            return;
        }

        // the file changes between two requests of a long-running process
        clearstatcache(true, $this->classesFile);
        $time = is_file($this->classesFile) ? filemtime($this->classesFile) : false;
        if (false === $time) {
            return;
        }
        if ($time !== $this->knownClassesTime) {
            $known = json_decode(file_get_contents($this->classesFile), true, flags: \JSON_THROW_ON_ERROR);
            $this->knownClasses = array_fill_keys($known, true);
            $this->knownClassesTime = $time;
        }

        foreach ($classes as $class) {
            if (!isset($this->knownClasses[$class])) {
                $message = \sprintf('No CSS was generated for "%s"; use it in a template or cover it with ux_css.static_css.', $class);
                $this->logger->warning($message);
            }
        }
    }
}
