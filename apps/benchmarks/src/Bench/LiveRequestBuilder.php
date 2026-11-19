<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Bench;

final class LiveRequestBuilder
{
    private const LIVE_SELECTOR = '[data-controller~="live"]';

    public function build(string $pageHtml, LiveScenario $scenario): LiveRequest
    {
        $document = \Dom\HTMLDocument::createFromString($pageHtml, \LIBXML_NOERROR);
        $root = $this->root($document, $scenario);

        $updated = $scenario->updated;
        if (null !== $scenario->selectRows) {
            $updated['selected'] = $this->selectedRows($root, $scenario->selectRows);
        }

        $props = $root->getAttribute('data-live-props-value')
            ?? throw new \RuntimeException(\sprintf('Component "%s" has no data-live-props-value attribute.', $scenario->component));

        $data = [
            'props' => json_decode($props, true, flags: \JSON_THROW_ON_ERROR),
            'updated' => (object) $updated,
        ];

        $children = $this->children($root, $scenario);
        if ([] !== $children) {
            $data['children'] = $children;
        }

        $url = $root->getAttribute('data-live-url-value');
        if (null !== $scenario->action) {
            $data['args'] = $scenario->args;
            $url .= '/'.rawurlencode($scenario->action);
        }

        $body = http_build_query(['data' => json_encode($data, \JSON_THROW_ON_ERROR)]);

        return new LiveRequest($url, $body, $scenario->page);
    }

    private function root(\Dom\HTMLDocument $document, LiveScenario $scenario): \Dom\Element
    {
        $selector = \sprintf('[data-live-name-value="%s"]', $scenario->component);

        return $document->querySelector($selector)
            ?? throw new \RuntimeException(\sprintf('Component "%s" not found on "%s".', $scenario->component, $scenario->page));
    }

    /**
     * @return array<string, array{fingerprint: string, tag: string}>
     */
    private function children(\Dom\Element $root, LiveScenario $scenario): array
    {
        $children = [];
        foreach ($root->querySelectorAll(self::LIVE_SELECTOR) as $child) {
            if ($child->parentElement->closest(self::LIVE_SELECTOR) === $root) {
                $id = $child->getAttribute('id')
                    ?? throw new \RuntimeException(\sprintf('A live child of component "%s" has no id.', $scenario->component));
                $children[$id] = [
                    'fingerprint' => (string) $child->getAttribute('data-live-fingerprint-value'),
                    'tag' => strtolower($child->tagName),
                ];
            }
        }

        return $children;
    }

    /**
     * @return list<string>
     */
    private function selectedRows(\Dom\Element $root, int $count): array
    {
        $values = [];
        foreach ($root->querySelectorAll('input[type="checkbox"][value]') as $checkbox) {
            if (str_starts_with((string) $checkbox->getAttribute('id'), 'select-post-')) {
                $values[] = $checkbox->getAttribute('value');
            }
        }

        return \array_slice($values, 0, $count);
    }
}
