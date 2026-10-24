<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\UX\Toolkit\Installer;

use Symfony\Component\Filesystem\Path;
use Symfony\UX\Toolkit\Component\StimulusController;
use Symfony\UX\Toolkit\Kit\Kit;

/**
 * Prefixes the references to the Stimulus controllers of a kit, once they are installed in a
 * sub-directory of the controllers root (e.g. "data-controller="dialog"" becomes "data-controller="ui--dialog"").
 *
 * Only the places where Stimulus reads an identifier are rewritten: "data-controller" values, the
 * controllers and dispatched events of "data-action" descriptors, "data-<identifier>-*" attribute names,
 * the "stimulus_*()" Twig functions and "getControllerForElementAndIdentifier()". Events created with
 * "new CustomEvent()" are left untouched, as they do not depend on the identifier of their controller.
 *
 * @author Hugo Alliaume <hugo@alliau.me>
 *
 * @internal
 */
final class StimulusIdentifierRewriter
{
    private readonly string $identifiersPattern;

    /**
     * @param array<string, list<string>> $identifiers the identifiers of the controllers to prefix, and the events each controller dispatches
     */
    public function __construct(
        private readonly array $identifiers,
        private readonly string $prefix,
    ) {
        $this->identifiersPattern = implode('|', array_map(static fn (string $identifier): string => preg_quote($identifier, '/'), array_keys($identifiers)));
    }

    /**
     * Prefixes the controllers that the recipes of the kit install through the "%CONTROLLERS_DIR%" placeholder.
     */
    public static function forKit(Kit $kit, string $prefix): self
    {
        if ('' === $prefix) {
            return new self([], $prefix);
        }

        $placeholder = InstallationDirectories::CONTROLLERS_DIR_PLACEHOLDER.'/';
        $identifiers = [];
        foreach ($kit->getRecipes() as $recipe) {
            foreach ($recipe->getFiles() as $file) {
                if (!str_starts_with($file->destinationRelativePathName, $placeholder) || !StimulusController::isFilename($file->destinationRelativePathName)) {
                    continue;
                }

                // "this.dispatch('select'", whose event is then sent as "<identifier>:select"
                preg_match_all('/\bthis\.dispatch\(\s*([\'"])([\w:-]+)\1/', file_get_contents(Path::join($recipe->absolutePath, $file->sourceRelativePathName)), $matches);
                $identifiers[StimulusController::identifier($file->destinationRelativePathName)] = $matches[2];
            }
        }

        return new self($identifiers, $prefix);
    }

    public function rewrite(string $contents): string
    {
        if ('' === $this->prefix || [] === $this->identifiers) {
            return $contents;
        }

        // "data-dialog-target" and "data-dialog-<name>-value|class|param", but not "data-dialog="modal""
        $contents = preg_replace(
            '/(?<![\w-])data-('.$this->identifiersPattern.')-(?=target(?![\w-])|[a-z0-9-]+?-(?:value|class|param)(?![\w-]))/',
            'data-'.$this->prefix.'$1-',
            $contents,
        );

        // data-controller="dialog live", 'data-controller': 'dialog', [data-controller~="dialog"], setAttribute('data-controller', 'dialog')
        $contents = preg_replace_callback(
            '/(data-controller[\'"]?\s*(?:~=|=|:|,)\s*)([\'"])(.*?)\2/s',
            fn (array $matches): string => $matches[1].$matches[2].$this->rewriteControllers($matches[3]).$matches[2],
            $contents,
        );

        // data-action="click->dialog#open", 'data-action': 'click->dialog#open', setAttribute('data-action', 'click->dialog#open')
        $contents = preg_replace_callback(
            '/(data-action[\'"]?\s*(?:=|:|,)\s*)([\'"])(.*?)\2/s',
            fn (array $matches): string => $matches[1].$matches[2].$this->rewriteActions($matches[3]).$matches[2],
            $contents,
        );

        // stimulus_controller('dialog', stimulus_action('dialog', stimulus_target('dialog' and getControllerForElementAndIdentifier(element, 'dialog'
        return preg_replace_callback(
            '/(\bstimulus_(?:controller|action|target)\(\s*|\bgetControllerForElementAndIdentifier\([^,()]*,\s*)([\'"])([\w-]+)\2/',
            fn (array $matches): string => $matches[1].$matches[2].$this->prefixIdentifier($matches[3]).$matches[2],
            $contents,
        );
    }

    private function rewriteControllers(string $controllers): string
    {
        // each identifier of "dialog live", separated by whitespace
        return preg_replace_callback(
            '/(?<=^|\s)[\w-]+(?=\s|$)/',
            fn (array $matches): string => $this->prefixIdentifier($matches[0]),
            $controllers,
        );
    }

    /**
     * Rewrites descriptors shaped like "[<event>->]<identifier>#<method>", where the event of
     * a controller dispatching it with "this.dispatch()" is prefixed by its identifier.
     */
    private function rewriteActions(string $actions): string
    {
        // "keydown.esc@window->dialog#close" and "dialog#close": the optional event, then the identifier before "#"
        return preg_replace_callback(
            '/(?<=^|\s)(?:([^\s>]+?)->)?([\w-]+)(?=#)/',
            fn (array $matches): string => ('' !== $matches[1] ? $this->rewriteEvent($matches[1]).'->' : '').$this->prefixIdentifier($matches[2]),
            $actions,
        );
    }

    private function rewriteEvent(string $event): string
    {
        // "calendar:select", optionally followed by a key filter or a global target, e.g. "calendar:select@window"
        if (!preg_match('/^([\w-]+):([\w-]+)(.*)$/s', $event, $matches) || !\in_array($matches[2], $this->identifiers[$matches[1]] ?? [], true)) {
            return $event;
        }

        return $this->prefix.$event;
    }

    private function prefixIdentifier(string $identifier): string
    {
        return isset($this->identifiers[$identifier]) ? $this->prefix.$identifier : $identifier;
    }
}
