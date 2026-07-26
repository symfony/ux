<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Component;

use function Symfony\Component\String\u;

/**
 * Maps between a Stimulus controller's filename and its identifier, following the Stimulus
 * convention: a snake_case `<name>_controller.js` file exposes the kebab-case `<name>` identifier
 * used in `data-controller`. Single source of truth shared by the linter and the doc renderer.
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class StimulusController
{
    private const FILENAME_SUFFIX = '_controller.js';

    public static function isFilename(string $filename): bool
    {
        return str_ends_with($filename, self::FILENAME_SUFFIX);
    }

    /**
     * Derives the `data-controller` identifier from a controller filename or path
     * (e.g. `assets/controllers/alert_dialog_controller.js` => `alert-dialog`).
     */
    public static function identifier(string $filename): string
    {
        return (string) u(basename($filename, self::FILENAME_SUFFIX))->kebab();
    }

    /**
     * The controller filename an identifier maps to (e.g. `alert-dialog` => `alert_dialog_controller.js`).
     */
    public static function filename(string $identifier): string
    {
        return u($identifier)->snake().self::FILENAME_SUFFIX;
    }

    /**
     * The `data-*` attribute a Stimulus value binds to, matching Stimulus' dasherization
     * (e.g. identifier `widget` + value `autoClose` => `data-widget-auto-close-value`).
     */
    public static function valueAttribute(string $identifier, string $value): string
    {
        return \sprintf('data-%s-%s-value', $identifier, u($value)->kebab());
    }

    /**
     * The `data-*` attribute a Stimulus class binds to, matching Stimulus' dasherization
     * (e.g. identifier `widget` + class `success` => `data-widget-success-class`).
     */
    public static function classAttribute(string $identifier, string $class): string
    {
        return \sprintf('data-%s-%s-class', $identifier, u($class)->kebab());
    }

    /**
     * The `data-*` attribute a Stimulus outlet binds to, matching Stimulus' dasherization
     * (e.g. identifier `widget` + outlet `user-status` => `data-widget-user-status-outlet`).
     */
    public static function outletAttribute(string $identifier, string $outlet): string
    {
        return \sprintf('data-%s-%s-outlet', $identifier, u($outlet)->kebab());
    }
}
