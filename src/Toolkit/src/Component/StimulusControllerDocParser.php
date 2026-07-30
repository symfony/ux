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

/**
 * Builds the {@see StimulusControllerDoc} (values, targets, classes, outlets and actions) from a controller's source.
 *
 * The source is scanned by {@see StimulusControllerSource}: the code is the single source of truth
 * for value names/types and target names, while their descriptions come from `@value`/`@target`
 * docblock tags. Actions are opt-in — declared entirely by `@action` tags — so internal public
 * methods are not documented. Each value's `data-*` attribute is derived from the controller identifier.
 *
 * Documentation as a whole is opt-in: a controller carrying no `@value`/`@target`/`@action` tag
 * yields an empty doc and thus no API reference, even when it declares values or targets in code.
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class StimulusControllerDocParser
{
    public function parse(string $source, string $identifier): StimulusControllerDoc
    {
        $controller = new StimulusControllerSource($source);
        $tags = $controller->tags();

        // Documentation is opt-in: without any @value/@target/@action/@css-class/@outlet tag, the controller
        // exposes no API reference, even though its values/targets/classes/outlets could be read from the code.
        if ([] === $tags['value'] && [] === $tags['target'] && [] === $tags['action'] && [] === $tags['class'] && [] === $tags['outlet']) {
            return new StimulusControllerDoc();
        }

        $values = [];
        foreach ($controller->values() as $value) {
            $values[] = new StimulusControllerValue(
                $value['name'],
                StimulusController::valueAttribute($identifier, $value['name']),
                $value['type'],
                $value['default'],
                $tags['value'][$value['name']] ?? '',
            );
        }

        $targets = [];
        foreach ($controller->targets() as $name) {
            $targets[] = new StimulusControllerTarget($name, $tags['target'][$name] ?? '');
        }

        $classes = [];
        foreach ($controller->classes() as $name) {
            $classes[] = new StimulusControllerClass(
                $name,
                StimulusController::classAttribute($identifier, $name),
                $tags['class'][$name] ?? '',
            );
        }

        $outlets = [];
        foreach ($controller->outlets() as $name) {
            $outlets[] = new StimulusControllerOutlet(
                $name,
                StimulusController::outletAttribute($identifier, $name),
                $tags['outlet'][$name] ?? '',
            );
        }

        $actions = [];
        foreach ($tags['action'] as $name => $description) {
            $actions[] = new StimulusControllerAction($name, $description);
        }

        return new StimulusControllerDoc($values, $targets, $classes, $outlets, $actions);
    }
}
