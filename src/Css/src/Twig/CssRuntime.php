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
    public function css(?array $styles): string
    {
        if (null === $styles) {
            return '';
        }
        if (null !== $this->validator) {
            ($this->validator)()->validate($styles);
        }

        $classes = $this->generator->generate($styles);
        $this->logClassesWithoutCss($classes);

        return $classes;
    }

    /**
     * @param string    $classes the classes of the part of the hash known when the template compiled
     * @param CssStyles $styles  the rest of the hash
     */
    public function append(string $classes, array $styles): string
    {
        $dynamic = $this->css($styles);

        return '' === $dynamic ? $classes : ('' === $classes ? $dynamic : $classes.' '.$dynamic);
    }

    private function logClassesWithoutCss(string $classes): void
    {
        if (null === $this->logger || null === $this->classesFile || '' === $classes) {
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

        foreach (explode(' ', $classes) as $class) {
            if (!isset($this->knownClasses[$class])) {
                $message = \sprintf('No CSS was generated for "%s"; use it in a template or cover it with ux_css.static_css.', $class);
                $this->logger->warning($message);
            }
        }
    }
}
