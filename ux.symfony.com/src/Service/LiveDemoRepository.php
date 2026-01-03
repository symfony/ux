<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service;

use App\Model\LiveDemo;

class LiveDemoRepository
{
    /**
     * @return array<LiveDemo>
     */
    public function findAll(): array
    {
        return [
            new LiveDemo(
                'infinite-scroll-2',
                name: 'Infinite Scroll - 2/2',
                description: 'Loading on-scroll, flexible layout grid, colorfull loading animations and... more T-Shirts!',
                author: 'smnandre',
                publishedAt: '2024-06-07',
                tags: ['grid', 'pagination', 'loading', 'scroll'],
                longDescription: <<<EOF
                    The second and final part of the **Infinite Scroll Serie**, with a new range of (lovely) T-Shirts!
                    Now with `automatic loading on scroll`, a new trick and amazing `loading animations`!
                    EOF,
            ),
            new LiveDemo(
                'infinite-scroll',
                name: 'Infinite Scroll - 1/2',
                description: 'Load more items as you scroll down the page.',
                author: 'smnandre',
                publishedAt: '2024-06-07',
                tags: ['grid', 'pagination', 'navigation'],
                longDescription: <<<EOF
                    Infinite scroll allows users to continuously load content as they scroll down the page.
                    `Part One` of this demo shows how to `append new items` to the page with a [`LiveComponent`](/live-component).
                    EOF,
            ),
            new LiveDemo(
                'live-memory',
                name: 'Live Memory Card Game',
                description: 'A Memorable Game UX with Live Components!',
                author: 'smnandre',
                publishedAt: '2024-06-07',
                tags: ['game', 'time', 'events', 'LiveAction'],
                longDescription: <<<EOF
                    A Memorable Game UX with Live Components! Discover how to use Live Components to create a game with a vibrant interface,
                     rich interactions and real-time updates. This journey will take you through many features of Live Components, and you'll
                      learn how to use them to create a fun and engaging game.
                    EOF,
            ),
            new LiveDemo(
                'auto-validating-form',
                name: 'Auto-Validating Form',
                description: 'Create a form that validates each field in-real-time as the user enters data!',
                author: 'weaverryan',
                publishedAt: '2022-06-17',
                tags: ['form', 'validation', 'inline'],
                longDescription: <<<EOF
                    Enter a bad email or leave the password empty, and see how the
                    form validates in real time!

                    This renders a normal `Symfony` form but with extras added on top,
                    all generated from Symfony & Twig.
                    EOF,
            ),
            new LiveDemo(
                'form-collection-type',
                name: 'Embedded CollectionType Form',
                description: 'Create embedded forms with functional "add" and "remove" buttons all in Twig.',
                author: 'weaverryan',
                publishedAt: '2022-06-17',
                tags: ['form', 'collection'],
                longDescription: <<<EOF
                    Unlock the potential of Symfony's [`CollectionType`](https://symfony.com/doc/current/reference/forms/types/collection.html) while writing zero JavaScript.
                    This demo shows off adding and removing items entirely in PHP & Twig.
                    EOF,
            ),
            new LiveDemo(
                'dependent-form-fields',
                name: 'Dependent Form Fields',
                description: 'After selecting the first field, automatically reload the options for a second field.',
                author: 'weaverryan',
                publishedAt: '2022-06-17',
                tags: ['form', 'field', 'events'],
                longDescription: <<<EOF
                    Unleash the power of form events, thanks to [`LiveComponent`](/live-component) and [`DynamicForms`](https://github.com/SymfonyCasts/dynamic-forms).
                    EOF,
            ),
            new LiveDemo(
                'voting',
                name: 'Up & Down Voting',
                description: 'Save up & down votes live in pure Twig & PHP.',
                author: 'weaverryan',
                publishedAt: '2022-06-17',
                tags: ['form', 'LiveAction'],
                longDescription: <<<EOF
                    With each row as its own component, it's easy to add up & down voting + keep track of which items have been voted on.
                    This uses a [LiveAction](https://symfony.com/bundles/ux-live-component/current/index.html#actions) to save everything with Ajax.
                    EOF,
            ),
            new LiveDemo(
                'inline-edit',
                name: 'Inline Editing',
                description: 'Activate an inline editing form with real-time validation.',
                author: 'weaverryan',
                publishedAt: '2023-02-21',
                tags: ['form', 'inline', 'LiveAction'],
                longDescription: <<<EOF
                    Inline editing? Simple. Use LiveComponents to track if you're in "edit" mode, let
                    the user update any fields on your entity, and save through a `LiveAction`.
                    EOF,
            ),
            new LiveDemo(
                'chartjs',
                name: 'Auto-Updating Chart',
                description: 'Render & Update a Chart.js chart in real-time.',
                author: 'weaverryan',
                publishedAt: '2023-03-16',
                tags: ['chart', 'data', 'LiveAction', 'stimulus'],
                longDescription: <<<EOF
                    What do you get with Live Components + UX Chart.js + UX Autocomplete?

                    An auto-updating chart that you will ❤️.
                    EOF,
            ),
            new LiveDemo(
                'invoice',
                name: 'Invoice Creator',
                description: 'Create an invoice + line items that updates as you type.',
                author: 'weaverryan',
                publishedAt: '2023-04-20',
                tags: ['form', 'entity', 'events', 'LiveAction'],
                longDescription: <<<EOF
                    Create or edit an `Invoice` entity along with child components for each related `InvoiceItem` entity.

                    Children components emit events to communicate to the parent and everything is saved in a `saveInvoice` LiveAction method.
                    EOF,
            ),
            new LiveDemo(
                'product-form',
                name: 'Product Form + Category Modal',
                description: 'Create a Category on the fly - from inside a product form - via a modal.',
                author: 'weaverryan',
                publishedAt: '2023-04-20',
                tags: ['form', 'entity', 'events', 'modal'],
                longDescription: 'Open a child modal component to create a new Category.',
            ),
            new LiveDemo(
                'upload',
                name: 'Uploading files',
                description: 'Upload file from your live component through a LiveAction.',
                author: 'lustmored',
                publishedAt: '2023-06-26',
                tags: ['form', 'file', 'upload', 'LiveAction'],
                longDescription: 'File uploads are tricky. Submit them to a `#[LiveAction]` with the `files` modifier
                on `data-live-action` then process them.',
            ),
        ];
    }

    public function getNext(string $identifier, bool $loop = false): ?LiveDemo
    {
        $demos = $this->findAll();
        while ($demo = current($demos)) {
            if ($demo->getIdentifier() === $identifier) {
                return prev($demos) ?: ($loop ? end($demos) : null);
            }
            next($demos);
        }

        return null;
    }

    public function getPrevious(string $identifier, bool $loop = false): ?LiveDemo
    {
        $demos = $this->findAll();
        while ($demo = current($demos)) {
            if ($demo->getIdentifier() === $identifier) {
                return next($demos) ?: ($loop ? reset($demos) : null);
            }
            next($demos);
        }

        return null;
    }

    public function getMostRecent(): LiveDemo
    {
        $demos = $this->findAll();

        return current($demos);
    }

    public function find(string $identifier): LiveDemo
    {
        $demos = $this->findAll();
        foreach ($demos as $demo) {
            if ($demo->getIdentifier() === $identifier) {
                return $demo;
            }
        }

        throw new \InvalidArgumentException(\sprintf('Unknown demo "%s"', $identifier));
    }
}
